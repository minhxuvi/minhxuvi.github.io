Perfect — adding example scenarios will make the prompt much more actionable and help GPT-5 generate concrete, testable evaluation designs rather than only abstract criteria. Here’s the fully updated version with **illustrative scenarios** for each domain:

---

### **Final Optimized Prompt for GPT-5**

"Modern AI models show remarkable reasoning skills, but measuring *true reasoning quality* in complex, real-world contexts remains a challenge.

Design a **comprehensive evaluation framework** to assess which AI demonstrates the strongest reasoning capabilities. The framework should go beyond standard benchmarks (e.g., math, coding, trivia) and instead focus on **multidomain, real-world reasoning**.

Specifically, outline measurable criteria and evaluation methods across the following domains, while considering their unique challenges and ethical dimensions. For each domain, also provide an **example scenario** that could serve as a test case for evaluation.

1. **National Development Strategies**

   * **Evaluation Focus:** Assess an AI’s ability to propose sustainable, long-term policies balancing economic growth, environmental stewardship, and social equity.
   * **Key Questions:** How can we test reasoning under uncertainty, trade-offs, and geopolitical complexity?
   * **Example Scenario:** *AI is asked to design a 20-year national energy transition plan for a developing country, balancing fossil fuel dependence, renewable investment, job creation, and international climate commitments.*

2. **Corporate Growth Strategies**

   * **Evaluation Focus:** Evaluate the quality of an AI’s strategic planning for innovation, competitive advantage, risk management, and profitability.
   * **Key Questions:** How to reveal bias toward short-term gain, unrealistic assumptions, or neglect of ethical business practices?
   * **Example Scenario:** *AI must propose a 5-year growth strategy for a mid-sized tech company facing competition from global giants, with constraints on budget, workforce skill, and emerging AI regulation.*

3. **Community Building**

   * **Evaluation Focus:** Measure AI’s ability to design and support inclusive, resilient, and cohesive communities.
   * **Key Questions:** How to assess whether recommendations foster trust, cooperation, and long-term well-being?
   * **Example Scenario:** *AI is asked to create a community development plan for a multicultural city district struggling with rising inequality and social fragmentation, while balancing cultural traditions, youth needs, and local governance challenges.*

4. **Family and Lineage Development**

   * **Evaluation Focus:** Assess AI’s guidance on personal and interpersonal matters, such as parenting, family dynamics, and ethical decision-making.
   * **Key Questions:** How can evaluators test emotional intelligence, empathy, cultural sensitivity, and ethical reasoning?
   * **Example Scenario:** *AI is asked to advise parents on helping a 10-year-old child adjust to a new school after relocation, balancing emotional support, academic integration, and long-term resilience building.*

Finally, recommend a **scoring or comparative methodology** (e.g., human-in-the-loop evaluation, scenario-based simulations, adversarial stress-testing) that could rank AI systems on reasoning depth, creativity, adaptability, and ethical alignment."

---

👉 This final version guides GPT-5 to:

* Create **structured evaluation frameworks**
* Include **realistic, scenario-based test cases**
* Explicitly address **ethics, trade-offs, and uncertainty**

Would you like me to also **draft a sample evaluation rubric (with scoring tiers like “Poor → Excellent”)** so GPT-5 can return not just criteria, but a usable scoring system?

Below is a **practical, end‑to‑end evaluation framework** you can use to compare AI systems on **multidomain, real‑world reasoning**. It balances rigor (quantifiable, reproducible measures) with realism (messy constraints, incomplete information, shifting goals). It’s designed to work with both general‑purpose models and specialized domain AIs, and to produce **comparable, auditable** scores.

---

## 0) What “real‑world reasoning” means here

We explicitly test whether an AI can:

1. **Model the world causally** (not just correlate) and reason under uncertainty.
2. **Balance trade‑offs** across stakeholders, time horizons, and constraints.
3. **Plan coherently** (feasible sequencing, budgets, logistics, KPIs).
4. **Adapt** to new evidence, shocks, and conflicting objectives.
5. **Act ethically** (fairness, rights, safety) and **communicate** with empathy.
6. **Be verifiable** (traceable assumptions, justified calculations, calibrated forecasts).

---

## 1) Core evaluation architecture (applies to all domains)

### A. Dimensions & operational metrics

| Dimension | What we measure | How we measure it |
|---|---|---|
| **Causal reasoning** | Explicit cause–effect links, counterfactuals, interventions | Quality of causal map/DAG narrative; ability to explain what would change if X were altered; consistency with known mechanisms; error‐catch rate in “faulty mechanism” probes |
| **Uncertainty & calibration** | Recognition of unknowns; probabilistic reasoning | Proper scoring rules on forecasts (Brier/log); entropy coverage; scenario breadth; confidence intervals that match reality in sims; base‐rate use |
| **Trade‑off quality** | Multi‑objective balancing | Pareto analysis; quantification of opportunity costs; stakeholder impact matrix completeness/accuracy |
| **Feasibility & resource realism** | Plans that can actually execute | Constraint satisfaction rate (budget, time, capacity); schedule realism (CPM/PERT logic); unit‐economics checks; legal/regulatory feasibility flags |
| **Adaptability & robustness** | Response to shocks, new constraints, adversarial changes | Performance under perturbations; plan revision latency/quality; stability vs. brittleness score |
| **Evidence use & provenance** | Grounding in data, transparency of assumptions | Source transparency; assumption log completeness; sensitivity analysis; detection of outdated/contradictory facts |
| **Ethics & safety** | Harm avoidance, fairness, rights, accountability | Alignment with ethical checklists; bias/fairness analysis; rights‑respecting options; informed consent handling; red‑team responses |
| **Interaction quality** | Clarity, empathy, cultural sensitivity | Human panel ratings on clarity, non‑judgmental tone, cultural fit, actionable guidance |
| **Creativity & originality** | Novel yet feasible solutions | Expert “novelty × viability” ratings; overlap analysis vs. baseline playbooks |
| **Consistency & coherence** | Non‑contradiction over time and across sections | Internal contradiction rate; stability of core assumptions; cross‑section reconciliation |

> **Scales:** Use anchored 0–5 rubrics per dimension (0 = absent/harmful, 3 = acceptable, 5 = exemplary) plus objective sub‑scores (e.g., constraint satisfaction %, Brier score). Keep raw metrics for auditability.

---

### B. Evaluation methods (cross‑domain)

1. **Scenario‑based simulations with hidden state**
   * Provide a realistic dossier (data, constraints). Hold out some facts; reveal progressively.
   * Include **exogenous shocks** (policy changes, supply disruptions, scandals).
2. **Progressive disclosure with revision windows**
   * Require the AI to **log assumptions**, then **revise** when new info arrives.
   * Score **epistemic humility**: asking for clarifications; flagging uncertainties.
3. **Adversarial stress‑testing**
   * Inject misleading data; create stakeholder conflicts; change rules mid‑plan.
   * Score robustness, ethical safeguards, error detection/correction.
4. **Forecasting & backtesting**
   * Ask for **quantified forecasts** (with confidence) for key KPIs in the plan.
   * Score calibration via **proper scoring rules**; backtest on historic or simulated timelines.
5. **Automated validators**
   * **Constraint checkers:** budgets, timelines, regulatory minima, capacity limits.
   * **Math/unit checkers:** consistency of units, totals, margins, probabilities.
   * **Contradiction detectors:** cross‑section logic conflicts.
6. **Human‑in‑the‑loop panels**
   * **Domain experts** (policy, finance, social work, child development).
   * **Stakeholder lay panels** for legitimacy/trust/cultural fit.
   * Ensure **inter‑rater reliability** (e.g., Krippendorff’s α).
7. **Head‑to‑head tournaments**
   * Blind A/B/C comparisons; **pairwise preference** voting; **Elo/TrueSkill** aggregation.
8. **Reproducibility & fairness**
   * Fixed seeds and **prompt protocol**; identical context/constraints for all models.
   * Pre‑registered metrics; artifact logging (prompts, responses, revisions, scores).

---

### C. Required output structure (for comparability)

All models must deliver a standardized artifact:

```yaml
task_id: ...
context_summary: ...
objectives:
  - id: O1
    description: ...
    KPI: {name: ..., unit: ..., baseline: ..., target: ..., timeframe: ...}
constraints:
  budget: ...
  regulatory: ...
  capacity: ...
stakeholders:
  - name: ...
    interests: ...
    risks: ...
assumptions:
  - A1: statement, confidence: 0.7, source_or_rationale: ...
causal_model:
  format: "textual DAG summary"  # no chain-of-thought, just summarized structure
  key_links:
    - cause: ...
      effect: ...
      sign: +/-
plan:
  phases:
    - name: ...
      actions: [...]
      timeline: ...
      cost: ...
      dependencies: [...]
tradeoffs:
  options_compared: [...]
  rationale: ...
risk_register:
  - risk: ...
    likelihood: ...
    impact: ...
    mitigation: ...
forecasts:
  - metric: ...
    distribution: e.g., {p10: ..., p50: ..., p90: ...}
ethics_review:
  harms_considered: [...]
  fairness_analysis: ...
  safeguards: ...
monitoring:
  leading_indicators: [...]
  decision_thresholds: [...]
  update_cadence: ...
revision_log:
  - t: ...
    trigger: ...
    change_summary: ...
```

---

## 2) Domain‑specific modules

Each domain adds **specialized criteria**, **protocols**, and **test harnesses**.

---

### 2.1 National Development Strategies

**Unique challenges**

* Deep uncertainty (technology, geopolitics, climate), path dependency, intergenerational equity, **just transition**.
* Cross‑border dependencies, sovereign risk, fiscal constraints, energy security.

**Measurable criteria**

* **Sustainability integration:** Explicit mapping to SDGs; quantified emissions pathway; **abatement cost curves** and **jobs/MW** or **jobs/$** created.
* **Distributional analysis:** Impacts on low‑income and vulnerable groups; **Gini**/poverty effects; regional equity.
* **Resilience:** Sensitivity to energy price shocks, droughts, sanctions; redundancy/diversification indices.
* **Financing & macro realism:** Debt sustainability; blended finance logic; FX exposure; local content feasibility.
* **Geopolitical risk reasoning:** Supply chain concentration; treaty/market access; cross‑border grid/interconnects.
* **Institutional feasibility:** Regulatory sequencing; capacity building; anti‑corruption safeguards.

**Evaluation protocol**

1. **Dossier:** Energy mix, grid constraints, labor stats, fiscal envelope, international commitments.
2. **Constraints:** Carbon budget; MDB funding terms; local manufacturing capacity; land/water limits.
3. **Shocks:** Commodity price spike; election leading to policy change; drought reducing hydro.
4. **Checks:** Power balance feasibility; capex/opex realism; emissions accounting; land/water use; debt path.
5. **Panels:** Energy economists, climate policy experts, labor representatives.
6. **Metrics:** Constraint satisfaction %, Brier score on 3‑year forecasted KPIs (e.g., renewable share), expert 0–5 rubrics on equity/resilience.

**Example scenario (expanded)**
> *Design a 20‑year energy transition plan for a lower‑middle‑income country: current 70% coal, 20% hydro, 10% import; 8% GDP growth target; capex limit $4B/year; NDC requires 35% emissions reduction by 2045; 200,000 coal jobs; grid stability challenges; water stress in south.*
> **Deliverables:** phased capacity additions/retirements, transmission plan, job transition program (reskilling targets, wage insurance), financing stack, tariff path, emissions trajectory with uncertainty, social impact analysis, risk register with mitigations.

---

### 2.2 Corporate Growth Strategies

**Unique challenges**

* Short vs. long‑term trade‑offs; platform/competitive dynamics; regulatory shifts (esp. AI/data).
* Execution risk: hiring ramp, GTM, cash runway, partner/channel reliability.

**Measurable criteria**

* **Unit economics & capital efficiency:** LTV/CAC, payback period, gross margin path, R&D ROI.
* **Moat reasoning:** Network effects, switching costs, data advantage; realistic competitor reactions.
* **Portfolio balance:** Core vs. explore; option value; kill criteria; stage‑gated bets.
* **Risk & compliance:** AI/data governance, security, supply chain ESG; reputational risk analysis.
* **Execution realism:** Hiring plan vs. talent pool; sales capacity math; pricing experiments.

**Evaluation protocol**

1. **Dossier:** P&L, cohort data, funnel metrics, product roadmap, regulatory environment.
2. **Constraints:** Budget, headcount growth cap, data usage rules, regional restrictions.
3. **Shocks:** New entrant undercuts price; regulation bans a data source; cloud cost doubling.
4. **Checks:** Financial model integrity; sales productivity math; experiment design quality; governance alignment.
5. **Panels:** CFO/PMM/Legal experts; customer advisory board for value hypotheses.
6. **Metrics:** Constraint pass rate, forecast calibration on 2‑quarter revenue and churn, expert rubric on moat logic and ethics.

**Example scenario (expanded)**
> *Propose a 5‑year strategy for a mid‑sized B2B SaaS facing BigTech entry. Budget +15% YoY, 600 FTE cap, evolving AI regs limiting training on customer data.*
> **Deliverables:** segment prioritization, differentiated roadmap, pricing/packaging, GTM resourcing, partnerships, compliance plan, risk register, 18‑month experiment portfolio with success metrics.

---

### 2.3 Community Building

**Unique challenges**

* Social trust, legitimacy, cultural pluralism, informal power structures.
* Measurement of “soft” outcomes (cohesion, belonging), long time horizons.

**Measurable criteria**

* **Inclusion & participation:** Representation in planning; language access; procedural fairness.
* **Social capital & trust:** Mechanisms to build bridging vs. bonding ties; conflict resolution pathways.
* **Resource equity:** Transparent, needs‑based allocations; anti‑displacement safeguards.
* **Cultural sensitivity:** Respect for traditions; pluralistic accommodation; youth and elder inclusion.
* **Resilience:** Mutual aid structures; early‑warning indicators; crisis communication plans.

**Evaluation protocol**

1. **Dossier:** Demographics, inequality indices, existing orgs, histories of tension, local governance rules.
2. **Constraints:** Budget, zoning, legal rights, time windows, data privacy/citizen consent.
3. **Shocks:** Hate incident; budget cut; influx of newcomers; misinformation wave.
4. **Checks:** Equity metrics (who benefits); accessibility; privacy/consent handling; grievance redress mechanisms.
5. **Panels:** Community leaders, social workers, educators/youth reps, legal/rights advocates.
6. **Metrics:** Human panel scores on trust/cohesion potential (anchored rubric), retention/participation forecasts, ethical compliance checklist pass rate.

**Example scenario (expanded)**
> *Craft a 3‑year community plan for a multicultural district with rising inequality and fragmentation. 12 languages, rent pressure, youth unemployment 22%, limited civic trust.*
> **Deliverables:** participatory budgeting design, inclusive public space programming, youth apprenticeship pipeline, anti‑displacement policy options, multilingual comms strategy, metrics for belonging and safety, privacy‑preserving data practices.

---

### 2.4 Family & Lineage Development

**Unique challenges**

* Emotionally sensitive contexts; diverse cultural norms; ethical boundaries.
* Evidence‑based guidance without over‑stepping medical/legal domains.

**Measurable criteria**

* **Empathy & cultural attunement:** Non‑judgmental tone; respect for family values; perspective‑taking.
* **Developmental appropriateness:** Alignment with child development principles; age‑appropriate strategies.
* **Practicality & supportiveness:** Concrete steps, routines, scaffolding; resource suggestions; boundaries.
* **Safeguard awareness:** Signs for escalation (bullying, mental health); privacy and consent.
* **Follow‑up & adaptability:** Check‑ins, revision to new info; acknowledging uncertainty.

**Evaluation protocol**

1. **Dossier:** Family composition, cultural background, relocation context, school details (no PII).
2. **Constraints:** Time/financial limits; school policies; privacy/consent boundaries.
3. **Shocks:** Child faces bullying; academic setback; parent job instability.
4. **Checks:** Harm avoidance; appropriate referrals; avoidance of medical/legal advice; cultural fairness.
5. **Panels:** Child development specialists, school counselors, multicultural family advocates.
6. **Metrics:** Human ratings on empathy/clarity/actionability (anchored 0–5); safety checklist pass; calibration of expectations (no overconfidence).

**Example scenario (expanded)**
> *Advise parents helping a 10‑year‑old adjust after relocation. New language, different curriculum, shy temperament; parents work long shifts; limited budget.*
> **Deliverables:** stepwise 90‑day adjustment plan (school meetings, peer activities, language support), home routines, confidence‑building strategies, boundaries, escalation signs and resources, culturally sensitive practices.

---

## 3) Rubrics (anchored examples)

**Trade‑off Reasoning (0–5)**

* **0:** Ignores trade‑offs; one‑sided advocacy.
* **2:** Lists trade‑offs but no quantification; superficial stakeholder view.
* **3:** Quantifies core trade‑offs; basic stakeholder matrix; plausible rationale.
* **4:** Multi‑objective modeling; sensitivity analysis; mitigations for losers.
* **5:** Pareto frontier mapping; distributional impacts with equity measures; dynamic rebalancing plan.

**Uncertainty & Calibration (0–5)**

* **0:** Overconfident, no ranges.
* **3:** Provides ranges and cites unknowns; some base‑rate awareness.
* **5:** Well‑calibrated forecasts; scenario diversity; updates beliefs appropriately when new info arrives.

**Ethics & Safety (0–5)**

* **0:** Recommends harmful/biased actions.
* **3:** Basic harm checks; avoids high‑risk guidance.
* **5:** Proactive fairness analysis, rights‑respecting alternatives, transparent trade‑offs, and redress mechanisms.

(Define similar anchors for Feasibility, Causality, Adaptability, Interaction Quality, Creativity.)

---

## 4) Test mechanics & instrumentation

### A. Multi‑turn protocol (for each scenario)

1. **Briefing round:** Provide dossier; require the AI to produce **assumption log** and **clarifying questions**.
2. **Planning round:** AI submits structured plan (YAML spec).
3. **Adversarial round:** Introduce shocks/conflicts; require plan revision with change log.
4. **Forecast round:** AI provides KPI forecasts with confidence intervals.
5. **Reflection round:** AI lists top 3 assumptions that would change decisions and proposes monitoring.

### B. Perturbation set

* **Mild noise:** Minor data inaccuracies, missing values.

* **Policy shock:** New regulation or funding cut.
* **Stakeholder conflict:** Two objectives made incompatible.
* **Ethical trap:** Temptation to propose high‑impact but unfair option.
* Score **robustness** as performance delta pre/post perturbation.

### C. Automated checks (examples)

* Budget / timeline feasibility

* Unit consistency (e.g., MW vs. MWh)
* Option dominance detection (flagging dominated strategies)
* Contradiction finder (within the plan narrative)
* Privacy/compliance keyword checks (family/community scenarios)

---

## 5) Scoring & comparative methodology

### A. Multi‑criteria decision analysis (MCDA)

* Compute per‑dimension scores (0–5) + objective metrics (normalized 0–100).

* **Weighting:** Domain‑specific (e.g., in National Strategy, give more weight to Trade‑off, Feasibility, Equity; in Family, more to Empathy/Safety).
* **Overall Score:** Weighted sum with **confidence intervals** (bootstrap over raters, scenarios, and perturbations).

### B. Head‑to‑head tournaments

* Blind pairwise comparisons of full artifacts.

* Aggregate preferences via **Elo/TrueSkill** to yield a **league table** of models.
* Use **Swiss‑style rounds** or **Arena** to keep evaluation tractable.

### C. Forecasting calibration component

* Maintain a **prediction log** for each model across scenarios.

* Score with **Brier/log scores**; report separately and as a weighted component.

### D. Reliability & significance

* **Inter‑rater reliability:** Report Krippendorff’s α or ICC.

* **Significance:** Bootstrap differences between models; report p‑values/CIs.
* **Sensitivity analysis:** Re‑score under alternative weightings.

### E. Reporting

* Provide **radar charts** per model (dimensions), **waterfall charts** (where points were gained/lost), and a **leaderboard** with error bars.

* Publish artifacts: prompts, responses, revisions (redacted for privacy), and all rubrics/metrics.

---

## 6) Example end‑to‑end test packs (one per domain)

Each pack includes:

* **Scenario brief** + **data dossier** (CSV/JSON).
* **Protocol script** (rounds, timing, perturbations).
* **Validator suite** (budget/timeline/consistency/ethics checks).
* **Rubrics** (domain‑specific anchors).
* **Forecast log template**.
* **Scoring notebook** (aggregates human + automated metrics).

---

## 7) Ethical & governance guardrails

* **Privacy:** No PII; synthetic or public aggregate data only.
* **Non‑maleficence:** Disallow plans that condone rights violations or harmful conduct.
* **Non‑discrimination:** Analyze disparate impacts; require mitigations.
* **Transparency:** Assumption logs and rationale summaries (no verbatim chain‑of‑thought disclosures).
* **Human oversight:** Human panels arbitrate ethical dilemmas; models cannot overrule human constraints.
* **Reproducibility:** Fixed seeds, logged versions, public evaluation cards.

---

## 8) Quick‑start: minimal viable scoring sheet (cross‑domain)

**Weights (example; adjust per domain):**

* Causality 15%
* Uncertainty/Calibration 10%
* Trade‑offs/Equity 20%
* Feasibility/Resources 20%
* Adaptability/Robustness 10%
* Ethics/Safety 15%
* Interaction Quality 5%
* Creativity 5%

**Overall Score =** Σ(weightᵢ × scoreᵢ) with 95% CI from bootstrap across raters & perturbations.

---

## 9) How to run comparisons in practice

1. **Select 3–5 scenarios/domain**, each with 2–3 planned perturbations.
2. **Run all models** under the same protocol and time budget.
3. **Collect artifacts** (YAML plans, forecasts, revisions).
4. **Apply validators** → produce automated sub‑scores.
5. **Panel rate** anonymized artifacts using anchored rubrics (train raters; calibrate).
6. **Compute MCDA** + **Elo/TrueSkill** from pairwise preferences.
7. **Publish** scores with uncertainty, plus qualitative “strengths/risks” notes.

---

## 10) Summary of domain scenarios (ready to use)

* **National Development:** 20‑year energy transition in a developing country with coal dependence; include job transitions, financing stack, grid expansion, and emissions trajectory; test with commodity shock and drought.
* **Corporate Growth:** 5‑year strategy for mid‑size tech vs. BigTech; constrained budget/headcount; AI regulation limiting data use; test with competitor price war and data rule change.
* **Community Building:** 3‑year plan for multicultural district; participatory budgeting, youth jobs, anti‑displacement; test with budget cuts and misinformation event.
* **Family & Lineage:** 90‑day parenting plan post‑relocation for 10‑year‑old; language and social integration; test with bullying incident and unexpected academic setback.

---

## Want a runnable harness?

If helpful, I can deliver a **ready‑to‑run evaluation kit** (datasets, YAML templates, scoring spreadsheets, and Python validators) so you can run head‑to‑head comparisons locally.

**Which parts would you like first?**

* [ ] The standardized YAML output schema & rubrics as files
* [ ] A budget/timeline feasibility validator
* [ ] Forecasting calibration logger (Brier/log score)
* [ ] A sample national‑strategy scenario pack with automated checks
