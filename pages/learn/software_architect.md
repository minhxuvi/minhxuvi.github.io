# Software architect

## Goals

-   Fast
-   Secure
-   Reliable
-   Easy to maintain

## Mindset

-   Understand the business
-   Define the system's Goals
-   Work for client's client
-   Talk to the right people with the right language.

## Process

### Understand the system requirements

-   What the system should do
-   Right after setting the goal. The goal is the most important thing to archive.
-   Bussiness flow...

### Understand none functional requirements

What the system should be deal with

-   Performance
    -   Latency - time to finish a task
    -   Throughput - number of tasks in a period of time
-   Load
-   Data volume
-   Concurrent users
-   SLA - Service Level Agreement

#### Quality

-   Scalability
-   Managebilaty
    -   System should report the problems.
-   Modularity
-   Extensibility
-   Testability

--- Never start working on a system before setting requirements ---

### Map the components

-   Web app
-   Web API
-   Mobile app
-   Desktop app
-   **Console app**
-   Services

### Select the right technology

#### Considerations

-   Can the technology solve the problem?
-   Is the technology mature?
-   Is the technology popular?
-   Is the technology easy to maintain?

#### Backend

#### Frontend

#### Database

NoSQL vs SQL

### Design the system

#### Components architecture

##### Layers

Loosely coupled components, each one responsible for a specific task.
Exception handling.

-   User interface
    -   Json handling
    -   Authentication
-   Business logic
    -   Validation
    -   Enrichment
    -   Computation
-   Data access
    -   Database
    -   File system
    -   External services

##### Interfaces

##### DI - Dependency Injection

##### SOLID

##### Naming conventions

##### Exception handling

-   Don't try catch for just for logging.
-   Catch only specific exceptions.
-   try catch smallest block of code.

##### Logging

-   Track errors
-   Gather data
    -   Performance
    -   Which modules is most visited
    -   User's flow

##### Caching

##### Security

#### Design patterns

Read the book

-   Factory
-   Repository
-   Facade

### Write architecture document

-   Write in plain English as simple as possible. Draw diagram if needed.

#### Document structure

-   Background
    -   Business POV: system, role, reason to change, expected impacts.
    -   Why? validate your point of view, make sure you are on the same page with the client.
-   Requirements

    -   Functional (no more than 5, no more thans 3 lines each)
        > Short and consice is enough.
    -   Non-functional
        > Extrimely accurate & specific

-   Executive summary
    > Focus on reader languages. Use chart or diagram. Write after all other sections are done.
-   Architecture overview

    -   General description: type, major requirement.
    -   High level diagram
        > Logic diagram only, don't mix with physical hw.
    -   Walkthrough
        > How the system works, how the components interact with each other.

-   Components
    > Most important
    -   Role
    -   Technology stack
    -   Components architecture
        -   Describe API: include method name
        -   Describe layers: SOLID
    -   Development instructions

### Implement the system

## Design patterns

### Singleton

A class that can have only one instance. Provide a global point of access to the instance.

Why?

-   Lazy loading
-   Only need one instance

### Factory

DI - Dependency Injection

### Repository

Module không liên quan đến datastore không cần care về cách lấy dữ liệu.

### Facade

Chia thành layer để abstract sự phức tạp phía dưới.

### Command

All the information needed to perform an action is encapsulated in a command object.

The invoker has now idea what the command does. Just execute it.
