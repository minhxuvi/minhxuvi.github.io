Here’s **Section 1: QA Methodology & Linux & Docker** for the demo interview.
This section is designed for **~1 hour** with **12 questions** (theory + practical + scenario-based). Each question includes **detailed sample answers**.

---

## ✅ Section 1: QA Methodology & Linux & Docker

### **Topic Goals**

- Assess understanding of QA principles, test planning, and defect lifecycle.
- Evaluate Linux command-line proficiency.
- Check Docker fundamentals and containerization concepts.

---

### **Q1. Explain the difference between Verification and Validation in QA.**

**Sample Answer:**

- **Verification** ensures the product is built **according to specifications** (e.g., reviews, static analysis).
- **Validation** ensures the product **meets user needs** (e.g., functional testing, UAT).
- Example:
  - Verification: Checking design documents against requirements.
  - Validation: Running end-to-end tests to confirm expected behavior.

---

### **Q2. What is a Test Plan? What are its key components?**

**Sample Answer:**
A **Test Plan** is a document outlining the testing strategy, scope, objectives, resources, schedule, and deliverables.
**Key components:**

- Scope & Objectives
- Test Items
- Test Environment
- Test Approach (manual/automation)
- Entry & Exit Criteria
- Risk & Mitigation
- Deliverables

---

### **Q3. How do you prioritize test cases?**

**Sample Answer:**

- Based on **Risk & Impact**:
  - High-risk features → test first.
- **Business Criticality**:
  - Core workflows > optional features.
- **Frequency of Use**:
  - Common user paths > rare scenarios.
- **Defect History**:
  - Areas with past issues get higher priority.

---

### **Q4. Describe the Bug Lifecycle.**

**Sample Answer:**

- **New → Assigned → Open → Fixed → Retest → Verified → Closed**
- Possible states: **Rejected**, **Deferred**, **Duplicate**, **Cannot Reproduce**.
- Tools: JIRA, Bugzilla, etc.

---

### **Q5. How do you ensure test coverage for a complex system?**

**Sample Answer:**

- Use **Requirement Traceability Matrix (RTM)** to map requirements → test cases.
- Apply **code coverage tools** (e.g., gcov, coverage.py).
- Perform **boundary value analysis**, **equivalence partitioning**, and **negative testing**.

---

### **Q6. Linux: How do you check CPU and memory usage in real time?**

**Sample Answer:**

- `top` or `htop` → real-time CPU/memory usage.
- `free -m` → memory usage summary.
- `vmstat` → system performance.
- `ps aux | grep <process>` → process-specific usage.

---

### **Q7. Linux: How do you find which process is using a specific port?**

**Sample Answer:**

- `lsof -i :<port>`
- `netstat -tulnp | grep <port>`
- `ss -ltnp | grep <port>` (modern alternative).

---

### **Q8. Docker: Explain the difference between an Image and a Container.**

**Sample Answer:**

- **Image**: A read-only template with instructions to create a container (e.g., OS + app).
- **Container**: A running instance of an image with its own filesystem, processes, and network.
- Analogy: Image = Class, Container = Object.

---

### **Q9. How do you persist data in Docker containers?**

**Sample Answer:**

- Use **Volumes**:
  - `docker volume create mydata`
  - `docker run -v mydata:/app/data myimage`
- Or **Bind Mounts**:
  - `docker run -v /host/path:/container/path myimage`.

---

### **Q10. How do you troubleshoot a failing Docker container?**

**Sample Answer:**

- `docker ps -a` → check container status.
- `docker logs <container>` → view logs.
- `docker exec -it <container> /bin/bash` → enter container.
- Check resource limits: `docker inspect <container>`.

---

### **Q11. Scenario: Your automated tests fail intermittently on a Linux CI server. How do you debug?**

**Sample Answer:**

- Check **logs** for patterns.
- Verify **resource usage** (CPU/memory).
- Check **network stability** if tests depend on external services.
- Add **retry logic** for flaky tests.
- Use **Dockerized environment** for consistency.

---

### **Q12. Scenario: You need to run tests in an isolated environment without installing dependencies on the host. What’s your approach?**

**Sample Answer:**

- Use **Docker** to containerize the test environment.
- Create a **Dockerfile** with all dependencies.
- Run tests inside the container:

  ```bash
  docker build -t test-env .
  docker run --rm test-env pytest
  ```

---

✅ **Time Allocation:**

- Theory (Q1–Q5): ~20 mins
- Linux (Q6–Q7): ~10 mins
- Docker (Q8–Q10): ~15 mins
- Scenarios (Q11–Q12): ~15 mins

---

👉 Do you want me to **move on to Section 2: Gen AI & AI Tools** now? Or should I **also include a quick difficulty rating and expected answers for senior-level performance** for Section 1?
