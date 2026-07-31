You are an expert full-stack developer and software architect. Build the first MVP version of a Personalized Education Web Application using Laravel 13 and MySQL.

The application architecture must be API-first so that it can easily support mobile applications (iOS/Android) in the future, while providing a clean web dashboard for data management.

### 1. CORE CONCEPT & ROLES

The core of the system is the "Student Profile". The platform serves three main user roles:

- **Teacher**: Can view/edit student profiles, add progress logs, and trigger AI lesson generation.
- **Parent**: Can view their child's profile and update home-based progress logs.
- **Student**: Can view their own profile and access generated exercises.
- **Admin**: Full system management.

### 2. DATABASE SCHEMA (MySQL)

Generate Laravel migrations with proper foreign keys, indexes, and soft deletes for the following tables:

- `users`: id, name, email, password, role (enum: admin, teacher, parent, student), timestamps, softDeletes.
- `curriculum_blocks`: id, name (string), description (text), start_date (date), end_date (date), timestamps, softDeletes.
- `student_profiles`: id, user_id (foreign key to users, unique), vision (text), mission (text), education_method (enum: waldorf, montessori, stem, traditional), interests (text), ai_generation_status (enum: idle, generating, failed), timestamps, softDeletes.
- `student_relations`: id, student_profile_id (foreign key), user_id (foreign key to users), relation_type (enum: parent, teacher), timestamps.
- `capabilities`: id, student_profile_id (foreign key), skill_name (string), current_level (text), notes (text), timestamps.
- `progress_logs`: id, student_profile_id (foreign key), updated_by (foreign key to users), content (text), logs_date (date), timestamps, softDeletes.
- `personalization_state`:
  - id
  - student_profile_id (foreign key to student_profiles, unique)
  - ai_condensed_memory (json or text - AI-synthesized cumulative analysis of the student's psychological & developmental state)
  - learning_style_nuances (text - behavioral traits, e.g., "responds well to physical movement/rhythm")
  - current_bottlenecks (text - core challenges the student is facing)
  - last_processed_log_id (foreign key to progress_logs, nullable - tracks up to which log the AI has already summarized)
  - timestamps
- `suggested_exercises`: id, student_profile_id (foreign key), curriculum_block_id (foreign key, nullable), title (string), description (text), expected_outcome (text), status (enum: pending, active, archived), timestamps, softDeletes.
- `exercise_submissions`: id, suggested_exercise_id (foreign key), student_notes (text), teacher_evaluation (text), status (enum: submitted, reviewed), timestamps.
- `media_attachments`: id, attachable_id (integer), attachable_type (string for polymorphic relation to support progress_logs and exercise_submissions), file_path (string), file_type (enum: image, video, document), timestamps.

### 3. BACKEND ARCHITECTURE (Laravel 13)

Please implement the following components:

- **Authentication**: Setup Sanctum or standard token-based authentication for secure API endpoints.
- **Controllers & Routes**:
  - `StudentProfileController`: CRUD operations for profiles, filtered by user permissions.
  - `ProgressLogController`: Allow teachers/parents to post daily/weekly learning logs.
  - `AIExerciseController`: Trigger the AI engine to generate localized, method-specific exercises.
- **AI Integration Service (`AIEngineService`)**:
  - Create a service that connects to an external Ollama/OpenAI-compatible endpoint via `Illuminate\Support\Facades\Http`.
  - Fetch the endpoint from `.env` (`OLLAMA_API_URL` and `OLLAMA_MODEL`).
  - Read student profile data (method, capabilities, interests, vision) and send a structured prompt to the AI.
  - The AI must output pure JSON matching the `suggested_exercises` table schema.
- **Background Jobs**: Use Laravel Queue (`ShouldQueue`) for the AI integration to process LLM API requests asynchronously and avoid timing out web requests.

### 4. EDUCATION METHOD LOGIC (Crucial for AI Prompting)

Inside the `AIEngineService`, the prompt construction must respect the chosen `education_method`:

- If `waldorf`: Focus tasks on experiential learning, natural materials, storytelling, and arts.
- If `montessori`: Focus tasks on self-directed activity, practical life skills, and hands-on learning.
- If `stem`: Focus tasks on logic, problem-solving, and technical integration.

### 5. FRONTEND INTERFACE (Simple & Modern)

- Provide a clean, modern web interface using Tailwind CSS (via Blade or Inertia.js) to demonstrate the Student Profile dashboard, where a teacher can click a "Generate AI Learning Path" button and see the results update dynamically via AJAX/API.

### OUTPUT EXPECTATION

Please generate all necessary Migrations, Models (with relationships defined), Controllers, Services, Jobs, API/Web Routes, and a basic Dashboard View for the Student Profile. Ensure clean code, proper validation, and error handling (Try-Catch blocks for the AI API calls).
