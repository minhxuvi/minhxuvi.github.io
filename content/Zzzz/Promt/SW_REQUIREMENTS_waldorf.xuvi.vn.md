# SW_REQUIREMENT.md — Waldorf XuVi Learning Platform

## 1. Product overview

A Waldorf (Steiner) education platform for families and educators, accessed as an **open, self-serve platform**. Any person can register a `user` account; from there they may act as a **Guardian** of one or more student profiles they create, a **Teacher** who creates and leads classes, or both.

- **Curriculum**: Based on Rudolf Steiner's classical Waldorf method, updated with the latest research on child development.
- **Async AI Lesson Planning**: Generated per study block by AI from inputs (location, teaching schedule, student overview) and processed asynchronously via background queues.
- **Individual Student Profiles** (`learning_profiles`): Tracks interests, aptitudes, strengths/weaknesses, and completed assignments; used by AI to generate tailored exercises. Core fields: `display_name`, `date_of_birth`, `interests[]`, `aptitudes[]`, `strengths_weaknesses[]`, `owner_user_id` (nullable — set for self-owned adult profiles or post-18 claims), `created_by_user_id`, `guardianship_attested_at`.
- **Progress Tracking**: Reports and statistics for teachers and parents.
- **Collaborative Updates**: Teachers and parents update student upbringing and learning journey details; AI adjusts exercises and lesson plans accordingly.
- **Student Self-Updates**: Age-appropriate or parental-consent self-editing.
- **Lifelong Learning Record**: Profiles carry into adulthood as personal records for scholarships, career, or study-abroad dossiers.
- **Identity & ReBAC Model**: Two system account roles (`user` and `admin`). Domain privileges (Guardian, Teacher, Self) are resolved dynamically via Relationship-Based Access Control (ReBAC) — never inferred from an institutional affiliation, since none exists. `admin` is a single global platform role (moderation + PDPD compliance oversight), not scoped per school.

## 2. Technical requirements & infrastructure

- **Web platform**: **Laravel 13+** (API) + **Vue 3 SPA** (Frontend).
- **Mobile apps**: **Flutter** (iOS + Android) — Phase 6.
- **Production Hosting & CI/CD Deployment**: Standard **cPanel VPS** (no Root SSH required).
  - Database: **MariaDB 10+**.
  - Background Queue: Handled via **cPanel Cron Job** triggering Laravel Scheduler (`php artisan schedule:run`) using the `database` queue driver (or Upstash Serverless Redis).
  - **CI/CD Pipeline**: GitHub Actions (`appleboy/ssh-action` over SSH) running automated PHP 8.4 tests (Pest/PHPUnit), `.env` backup/restore, pre-migration DB dumps (`mysqldump`), artisan migrations, config/route caching, and queue restarts.
- **API Single Source of Trust**: `api/openapi.yaml` (OpenAPI 3.1, spec-first). All frontend and mobile clients are auto-generated from this contract.
- **Frontend UI/UX**: Vue 3 + TypeScript + Vite + **Tailwind CSS** + **Lucide Icons** (`lucide-vue-next`), adhering to **UI-UX-PRO-MAX** design guidelines with a Vietnamese-first i18n interface.

## 3. Confirmed architectural decisions

| Area | Decision |
| --- | --- |
| Mobile apps | Flutter (single Dart codebase for iOS + Android) |
| CI/CD Pipeline | GitHub Actions workflow: PHP 8.4 test execution → SSH deployment to cPanel VPS (no staging gate, auto-deploy on green CI) → `.env` backup → `mysqldump` pre-migration backup → `artisan migrate` (auto-restore backup + fail the run on error) → config/route/view cache → queue restart |
| AI provider | Adapter layer with **DeepSeek prompt-caching driver** (context caching) as primary; OpenAI, Anthropic, Gemini as fallbacks. Evaluate building this on Laravel 13's native AI SDK (see §4.4) before writing a fully custom adapter. |
| Async AI Execution | All long-running AI requests (>2s) are queued and executed asynchronously; frontend receives immediate job ID and polls or listens for completion |
| Frontend Stack | Vue 3 + TypeScript + Vite SPA + Tailwind CSS + Lucide Icons |
| UI/UX Standards | Integrated **UI-UX-PRO-MAX** system rules with a natural Waldorf visual theme |
| API Contract | Canonical `openapi.yaml` checked into repo; contract tests run in CI |
| Auth | Laravel Sanctum — **SPA cookie/session mode** (stateful domains, CSRF-protected) for the Vue frontend; **personal access tokens** (with rotation/expiry policy) for Flutter mobile clients. Custom Auth controllers for registration, login, Google SSO. |
| System Roles | `user` and `admin` only (domain permissions resolved via ReBAC pivot relationships — no per-school admin role, since there is no school tenant) |
| Public Reg & SSO | Email/password self-registration + Google OAuth 2.0 (Socialite), default role `user` |
| Enrollment & Consent Model | Classes are **invite-only** (shareable `join_code`, no public directory) and user-created; the creator permanently owns the class record and can transfer active lead-teacher rights to another teacher (see §3.1). A student only becomes visible to a class's teachers after the lead teacher **manually approves** a Guardian-initiated enrollment request (see `class_enrollments`, §3.1) — this is the access gate that keeps unrelated adults from reaching a child's data. |
| AI Usage Quotas | Per-user/per-day request and token caps enforced at the adapter layer before dispatch; over-quota requests return `429`, not a silently queued job. Tracked against the `ai_jobs` ledger. |
| Background Queues | cPanel Cron Job running `schedule:run` every minute using Laravel `database` driver (or Serverless Redis); no root Supervisor required |
| Testing & Quality | Pest PHP, Laravel Pint, Larastan / PHPStan 2, Playwright E2E |

### 3.1 Relationship-Based Access Control (ReBAC) & Context Model

Domain permissions are decoupled from system account roles to support dual-role users (e.g., a guardian who is also a teacher), and — because there is no institutional tenant to fall back on — **every cross-user data access must be justified by an explicit pivot row**, not by role alone:

1. **`learning_profile_guardians` Pivot** (`user_id`, `profile_id`, `relationship_type`, `is_primary`):
   - Legal guardian rights: PDPD consent, profile export/deletion, edit approval for non-guardians.
   - Creating a profile for a minor requires the creating user to self-attest guardianship at creation time (`guardianship_attested_at`); the attestation is logged for PDPD audit and is subject to admin review if disputed or reported (see §5).
   - Adding a **second** guardian to an existing profile requires an invite-and-accept flow approved by an existing `is_primary` guardian — it is not self-service for the invitee.
2. **`class_teachers` Pivot** (`user_id`, `class_id`, `role`: `lead` | `subject`):
   - Teaching rights: Lesson plan generation, class assignments, tailored AI exercises — scoped strictly to students with an **approved** row in `class_enrollments` (below), never to all platform students.
   - **Class ownership & admin transfer**: `classes.created_by_user_id` permanently records the class's creator, who receives the initial `lead` role. The current `lead` teacher can transfer `lead` (class-admin rights) to another `class_teachers` member at any time, but `created_by_user_id` never changes — it remains the permanent audit/dispute-resolution anchor for the class, mirroring the guardianship dispute mechanism in §5.
   - **Discovery**: classes are invite-only, not publicly listed. The current `lead` teacher generates a shareable `classes.join_code`; there is no public class directory (reduces the surface area for a stranger to find a class containing children).
3. **`class_enrollments` Pivot** *(new)* (`profile_id`, `class_id`, `status`: `pending` | `approved` | `rejected` | `withdrawn`, `requested_by_user_id`, `approved_by_user_id`, timestamps):
   - A Guardian holding a class's `join_code` requests enrollment on behalf of a `learning_profile` they control.
   - The class's current `lead` teacher manually approves or rejects every request — there is no auto-approval path.
   - Only an `approved` row grants the class's teachers read/write access to that profile's relevant progress and assignment data — this is the child-safety access gate referenced in §9.
4. **Self Context**:
   - `learning_profiles.owner_user_id` is set when an adult self-registers their own profile, or when the §5 Claim Profile workflow completes. `actor_context = self` applies whenever `acting_user_id == owner_user_id`.
5. **Parent-Teacher Dual Context**:
   - A single `user` can hold records in all three pivot tables above.
   - Legal/PDPD actions require Guardian authority; curriculum actions require Teacher context with an approved enrollment.
6. **Audit Trail Context**:
   - Every data modification logs an `actor_context` (`guardian` | `teacher` | `self` | `admin`) for PDPD compliance (Nghị định 13/2023).

## 4. Design system, AI adapter & deployment architecture

### 4.1 Deployment Pipeline Architecture (GitHub Actions ➔ cPanel VPS)

```
[ GitHub Repo (Push to main) ]
             │
             ▼
[ GitHub Actions Runner (Ubuntu / PHP 8.4) ]
   ├── 1. Install & Cache Composer Dependencies
   ├── 2. Prepare SQLite in-memory env & generate keys (APP_KEY, OAuth)
   └── 3. Run Test Suite (php artisan test)
             │ (Passes)
             ▼
[ SSH Action to cPanel VPS — no staging gate, auto-deploys on green CI ]
   ├── 1. Git fetch & hard reset (with .env backup & restore)
   ├── 2. Enable Maintenance Mode (php artisan down)
   ├── 3. Production Composer Install (--no-dev --optimize-autoloader)
   ├── 4. PHP-based mysqldump database backup to ~/.deploy-backups
   ├── 5. Run Database Migrations (php artisan migrate --force)
   │        ├── On success ──────────────────────────────┐
   │        └── On failure → auto-restore the step-4      │
   │            mysqldump backup, keep Maintenance Mode    │
   │            ON, fail the GitHub Actions run (red ✕)    │
   │            so the team is alerted immediately          │
   │                                                        ▼
   ├── 6. Optimize Caches (config, route, view) & Restart Queues
   └── 7. Disable Maintenance Mode (php artisan up)
```

Auto-restore is safe here specifically because Maintenance Mode (step 2) blocks all writes for the duration of the migration attempt — the step-4 backup is guaranteed to be the exact pre-deploy state, so restoring it on failure can't clobber any data written in between. If migration fails, the site stays in Maintenance Mode after the restore (not brought back up) until the failure is investigated — a failed pipeline run should never auto-heal into "up" with an unknown DB state.

### 4.2 UI/UX Design System (UI-UX-PRO-MAX Standards)

- **Waldorf Aesthetic & Palette**:
  - Backgrounds: Warm Beige (`bg-stone-50`), Soft Cream (`bg-amber-50/30`), White Cards (`bg-white`).
  - Primary Accents: Sage Green (`bg-emerald-700`, `text-emerald-800`), Warm Terracotta/Amber (`bg-amber-700`).
  - Text: Dark Charcoal (`text-stone-800`), Muted Grey (`text-stone-500`).
  - Typography: Clear scale contrast (`text-2xl font-bold tracking-tight text-stone-800`), generous line-height (`leading-relaxed`).
  - Borders & Shadows: Soft organic borders (`border-stone-200/80`), rounded containers (`rounded-2xl`), subtle elevation (`shadow-sm` → `shadow-md`).
- **Mandatory UX Component States**:
  1. **Loading State**: Shimmer skeleton loaders (`animate-pulse bg-stone-200`) or Lucide spinning icon (`Loader2`) during async AI jobs.
  2. **Empty State**: Friendly illustration/icon, clear text, and prominent CTA button when lists are empty.
  3. **Error State**: Inline alerts with soft backgrounds (`bg-rose-50 text-rose-700 border-rose-200`).
  4. **Micro-interactions**: Hover effects (`hover:-translate-y-0.5 hover:shadow-md transition-all duration-200`), active button compression.
  5. **Accessibility State**: Full keyboard navigation, visible focus rings, ARIA labels on icon-only buttons, and WCAG 2.1 AA color contrast — verify the Sage/Amber/Stone palette against contrast ratios explicitly, since several accent combinations above sit close to the AA threshold.

### 4.3 Single Source of Trust Architecture

```
api/openapi.yaml   (canonical contract — the ONLY source of truth)
   ├── Laravel 13 API  (Controllers & Form Requests matching contract)
   ├── Web SPA          (Vue 3 + TS; generated via @hey-api/openapi-ts)
   └── Flutter apps     (Dart client generated via openapi-generator)
```

### 4.4 AI Adapter & Waldorf Prompt Directives

- **Implementation note**: Laravel 13 (released March 17, 2026) ships a first-party, stable **Laravel AI SDK** with native abstractions for text generation, agents, and embeddings. Before building the DeepSeek/OpenAI/Anthropic/Gemini adapter fully from scratch, spike whether it can sit on top of this SDK — it may absorb a meaningful share of the adapter-layer work.
- **Prompt Caching Strategy**: Prompts are split into a *Stable Prefix* (System prompt → Waldorf guardrails → Curriculum context) + *Variable Suffix* (Block/Student data) to maximize DeepSeek cache hits.
- **Data Minimization for Third-Party AI Calls**: Student identifiers sent in the Variable Suffix must be pseudonymized (profile ID / first name only, no full name, DOB, or free-text guardian notes) unless a specific field is demonstrably required for the exercise being generated. DeepSeek is a non-EU/US processor; cross-border transfer of children's personal data under Vietnam's PDPD (Nghị định 13/2023) should be confirmed with legal counsel before Phase 3 — see §5 and §12.
- **Waldorf Pedagogical Directives (In Stable Prefix)**:
  - *Rhythm & Age-Appropriateness*: Strict adherence to Steiner developmental stages.
  - *Head, Heart, Hand Balance*: Integrates cognitive, artistic/emotional, and physical/craft dimensions.
  - *Screen-Free Bias*: Focuses on tactile, outdoor, real-world, and artistic student exercises.
  - *Vietnamese Cultural Context*: Incorporates local folklore, seasonal nature rhythms, and traditional crafts (bamboo, rattan).
  - *Child-Safety Guardrail*: AI output is screened against a content-safety policy before it reaches a student or guardian; flagged output is routed to human review instead of being shown.

## 5. Privacy, compliance & 18+ profile claim

- **Vietnam PDPD (Nghị định 13/2023)**: Consent tracking, encryption at rest for sensitive student data, soft deletes, audit logging with `actor_context`. Children's data should be treated as sensitive personal data by default; confirm exact consent-age thresholds and cross-border transfer obligations with legal counsel before Phase 2 sign-off (open item, §12).
- **Guardianship Attestation & Disputes**: Since registration is open (§1), profile creation for a minor relies on the creator's self-attestation of guardianship rather than institutional verification. The platform must provide a reporting mechanism for a disputed/wrongful guardianship claim, with `admin` able to investigate, suspend access, and reassign or remove the profile pending resolution.
- **18+ Profile Ownership Handover**:
  - When a student reaches age 18, a "Claim Profile" verification workflow allows the adult student's personal `user` account to claim primary ownership (`is_primary` owner, sets `learning_profiles.owner_user_id`) from guardians.
  - Transfers full legal control while preserving historical audit logs.

## 6. Development standards & environment

- **Local Dev DB**: MySQL 8.4 LTS or MariaDB 10.11 (Docker/Homebrew) — matches production MariaDB 10+.
- **Local Queue Driver**: `database` driver.
- **Production Queue Execution (cPanel)**:
  - Cron Job entry: `* * * * * cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1`
  - In `routes/console.php`:
    ```php
    Schedule::command('queue:work --stop-when-empty --tries=3 --timeout=120')
        ->everyMinute()
        ->withoutOverlapping();
    ```
- **Local Toolchain**: PHP 8.4+ (Laravel 13 requires PHP 8.3+ as a floor, so 8.4 is fully compatible), Composer 2.10+, **Node.js 24 (Active LTS, supported through Apr 2028)** for CI/CD and local builds, npm (`lucide-vue-next`, `@hey-api/openapi-ts`, Tailwind CSS v3/v4). Node 26 is currently a "Current" release and doesn't enter LTS until October 2026 — hold off adopting it project-wide until then unless a specific Node 26 feature is required.
- **CI/CD Deployment Toolchain**: GitHub Actions workflow deploying to cPanel via `appleboy/ssh-action`, using automated `mysqldump` backups to `~/.deploy-backups`. Backup retention window and at-rest encryption for this directory are not yet specified — see §12.

## 7. Delivery phases (Fresh Build Blueprint)

| Phase | Scope | Status |
| --- | --- | --- |
| **Phase 0** | **Scaffold & CI/CD Deployment Pipeline**: Deploy empty/bare Laravel 13 app + Vue 3 SPA scaffold to cPanel VPS via GitHub Actions SSH workflow (automated PHP 8.4 test suite, `.env` backup, pre-migration `mysqldump`, auto-migrate, config cache, queue restart) | ⏳ Planned |
| **Phase 1** | **Foundation & Domain Contracts**: OpenAPI 3.1 contract (`openapi.yaml`), core DB migrations, Sanctum auth & Vue SPA initial shell | ⏳ Planned |
| **Phase 2** | **Auth & Identity**: Registration, Google SSO, ReBAC matrix (`guardians`, `teachers`, **`class_enrollments`**), guardianship attestation & dispute handling, PDPD audit trail with `actor_context` | ⏳ Planned |
| **Phase 3** | **Curriculum & Async AI Engine**: Study blocks, DeepSeek caching adapter (evaluate Laravel 13 AI SDK), AI usage quotas, cPanel Queue integration for background lesson generation | ⏳ Planned |
| **Phase 4** | **Student Profiles, Progress Reports & Adulthood (18+) Claim Workflow** | ⏳ Planned |
| **Phase 5** | **Web App Polish**: Full UI-UX-PRO-MAX Waldorf Design System implementation (incl. accessibility pass) & Production Verification | ⏳ Planned |
| **Phase 6** | **Mobile Apps (Flutter)** & Tailored Exercise Generation | ⏳ Planned |

## 8. MVP resource scope (Phase 0–3 focus)

- **Infrastructure & Deployment**: GitHub Actions workflow file (`.github/workflows/deploy.yml`), SSH secrets (`SERVER_HOST`, `SERVER_USER`, `SERVER_SSH_KEY`), `.env.example`, cPanel Cron Job schedule setup.
- **Auth**: Registration, Login, Logout, Google OAuth exchange, `/me`.
- **Identity & ReBAC**: `users`, `learning_profiles`, `learning_profile_guardians`, `class_teachers`, **`class_enrollments`**.
- **Curriculum**: `grades`, `subjects`, `study_blocks`, `classes` (incl. `created_by_user_id` and `join_code` for invite-only enrollment).
- **AI Async Jobs**: `lesson_plans`, `ai_jobs` ledger (tracking prompt cache hits, token costs, and per-user quota consumption).
- **Progress**: `assignments`, `student_progress_reports`.

## 9. Architectural baselines for code generation

1. **Phase 0 Deployment Priority**: Verify that the bare Laravel 13 + Vue 3 SPA project successfully passes tests and deploys to cPanel VPS via GitHub Actions SSH workflow before building complex domain endpoints.
2. **Spec-First Enforcement**: Do not write API routes or Vue API calls without updating `api/openapi.yaml` first.
3. **ReBAC Policy Enforcement**: Always verify permissions via Laravel Policies checking ReBAC pivot relationships and record `actor_context`.
4. **Non-blocking UI**: All AI generation endpoints must return a `202 Accepted` with a Job ID. The Vue frontend must show UI-UX-PRO-MAX Skeleton loaders or progress badges.
5. **Tailwind + Lucide UI**: All Vue components must use Tailwind utility classes with the Waldorf color palette (Sage/Amber/Stone) and `lucide-vue-next` icons.
6. **Child-Safety Access Gate**: No non-guardian (teacher) may read or write a `learning_profile`'s personal data until a corresponding `class_enrollments` row is `approved`. Policy tests must explicitly cover the `pending`, `rejected`, and `withdrawn` states, not just the happy path.
7. **AI Cost Guardrail**: Every AI adapter call must check the caller's quota before dispatch. Over-quota calls return `429 Too Many Requests`; they are never silently queued or degraded.

## 10. Non-functional requirements *(draft — confirm with stakeholders before Phase 1 sign-off)*

| Category | Draft target |
| --- | --- |
| API latency | p95 < 500ms for non-AI endpoints |
| AI job latency | p95 job completion < 2 min; user is notified if a job exceeds 5 min |
| Availability | 99.5% monthly (single cPanel VPS — no HA failover in MVP; document this caveat to users) |
| Accessibility | WCAG 2.1 AA across the Vue SPA |
| Browser support | Latest 2 versions of Chrome, Safari, Firefox, Edge; mobile Safari/Chrome |
| Data export | Guardian-initiated profile export fulfilled within [X] days |
| Data deletion | Guardian-initiated deletion fulfilled within [Y] days, barring active legal hold |
| Backup retention | `~/.deploy-backups` retention window and encryption-at-rest: **TBD** |

## 11. Glossary

- **ReBAC** — Relationship-Based Access Control; permissions derived from explicit relationship rows (guardian, teacher, enrollment) rather than a static role.
- **PDPD** — Vietnam's Personal Data Protection Decree, Nghị định 13/2023.
- **actor_context** — Audit-log field recording which relationship (`guardian`/`teacher`/`self`/`admin`) authorized a given action.
- **Learning Profile** — A student's persistent record (interests, aptitudes, progress); may be guardian-managed or self-owned.
- **Guardian** — A user with legal/consent authority over a learning profile via `learning_profile_guardians`.
- **Lead / Subject Teacher** — Roles within `class_teachers`; a lead teacher can approve enrollments, a subject teacher cannot.
- **Study Block** — A Waldorf curriculum unit used as AI lesson-planning input.
- **Claim Profile** — The 18+ workflow transferring profile ownership from guardian to the adult student.
- **Stable Prefix / Variable Suffix** — The two-part prompt structure (fixed system context vs. per-request data) used to maximize AI provider cache hits.
