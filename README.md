# FlowForge - Workflow Orchestration System

FlowForge is a highly scalable, production-ready workflow orchestration system built with Laravel. It allows you to create, version, and reliably execute complex asynchronous workflows based on Directed Acyclic Graphs (DAG).

## Tech Stack

- **Framework**: Laravel
- **Database**: PostgreSQL
- **Queue/Cache**: Redis
- **Real-time**: Laravel Events & Broadcasting (Laravel Echo / Pusher / Reverb)
- **Frontend**: Blade UI
- **Infrastructure**: Docker & Docker Compose

## Architecture

The system follows a clean, scalable architecture:
`Controller → Service → Repository → Model → Job (Async Execution)`

- **Separation of Concerns:** Controllers contain no business logic. Services handle orchestration.
- **Asynchronous Execution:** Heavy reliance on Laravel Queue (Jobs) for the execution engine.
- **Idempotency & Concurrency:** Robust locking and state management to prevent race conditions.

---

## Prerequisites

Before setting up the project, ensure you have the following installed on your machine:
- [Docker](https://www.docker.com/) & Docker Compose
- [Git](https://git-scm.com/)

---

## Installation & Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd flowforge
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   ```
   Ensure the following core variables are configured for Docker:
   ```dotenv
   DB_CONNECTION=pgsql
   DB_HOST=postgres
   DB_PORT=5432
   DB_DATABASE=flowforge
   DB_USERNAME=sail
   DB_PASSWORD=password

   REDIS_HOST=redis
   REDIS_PASSWORD=null
   REDIS_PORT=6379

   QUEUE_CONNECTION=redis
   BROADCAST_DRIVER=log # change to pusher or reverb for actual WebSockets
   ```

3. **Start Docker Containers**
   We use Docker to spin up PHP-FPM, Nginx, PostgreSQL, and Redis.
   ```bash
   docker-compose up -d
   ```

4. **Install Dependencies & Initialize App**
   Run the following commands inside the `app` container:
   ```bash
   # Install PHP dependencies
   docker-compose exec app composer install

   # Generate Application Key
   docker-compose exec app php artisan key:generate

   # Run Database Migrations
   docker-compose exec app php artisan migrate

   # Install Frontend Dependencies (for Real-time UI / Blade)
   docker-compose exec app npm install
   docker-compose exec app npm run build
   ```

---

## Running the Application

To ensure the execution engine and background tasks function correctly, you must start the Laravel queue worker.

### 1. Run the Queue Worker
The queue worker handles the execution of DAG steps concurrently.
```bash
docker-compose exec app php artisan queue:work redis --tries=3 --backoff=5,10,20
```

### 2. Run the WebSocket Server (Optional / Real-time)
If you are using Laravel Reverb or another local WebSocket server for real-time monitoring:
```bash
docker-compose exec app php artisan reverb:start
```

---

## API Documentation

### Workflows

#### 1. Create Workflow
Create a new workflow with a DAG definition. The system automatically creates version `1`.

- **Endpoint:** `POST /api/workflows`
- **Body:**
```json
{
  "name": "Data Processing Pipeline",
  "tenant_id": "uuid-v4-string",
  "definition": {
    "steps": [
      {
        "id": "step_A",
        "type": "http",
        "depends_on": []
      },
      {
        "id": "step_B",
        "type": "delay",
        "depends_on": ["step_A"]
      }
    ]
  }
}
```

#### 2. Update Workflow (Versioning)
Updates to a workflow are **immutable**. Instead of overwriting, this endpoint creates a new workflow version and increments the version number.

- **Endpoint:** `PUT /api/workflows/{id}`
- **Body:**
```json
{
  "definition": {
    "steps": [ ... ]
  }
}
```

#### 3. Get Workflow
Retrieve a workflow, including its latest DAG definition version.

- **Endpoint:** `GET /api/workflows/{id}`

#### 4. List Workflows
List all workflows with pagination and filtering by `tenant_id`.

- **Endpoint:** `GET /api/workflows`

---

## Core Engines & Features

### 1. DAG Engine (Parser & Validator)
The DAG Engine acts as the brain of the execution process:
- **Dependency Validation:** Ensures `depends_on` arrays reference valid step IDs.
- **Cycle Detection:** Prevents infinite loops/deadlocks (e.g., A → B → C → A).
- **Topological Sorting:** Determines the exact safe execution order for the worker pool.

### 2. Execution Engine
The engine processes the DAG asynchronously using a Queue-based approach:
- **Dependency Aware:** Step B will automatically wait in the queue (`release()`) until Step A is marked as `success`.
- **Parallel Processing:** Branches in the DAG execute simultaneously on multiple queue workers.
- **Concurrency Safety:** Uses `Cache::lock` to prevent race conditions and duplicate executions of the same step.

### 3. Resilience & Retry Mechanism
Designed to handle real-world API instability and transient errors:
- **Exponential Backoff:** Retries failing jobs natively with increasing delays (e.g., 5s, 10s, 20s).
- **Idempotency:** Execution locks ensure that retried steps don't produce duplicate side effects.
- **State Tracking:** Tracks the `attempt` count and records the `last_error` in the `step_runs` database table for deep observability.
- **Conditional Retry:** Discriminates between retryable errors (Network Timeout, HTTP 500) and fatal errors (Validation Error, Syntax Error).

### 4. Real-time Observation
FlowForge broadcasts execution progress in real-time, allowing frontends to react instantly without polling.

- **Channel:** `workflow.{workflowRunId}`
- **Emitted Events:** `WorkflowStarted`, `StepStarted`, `StepSucceeded`, `StepFailed`, `WorkflowCompleted`
- **Frontend Listener Example:**
```javascript
import Echo from 'laravel-echo';

Echo.private(`workflow.${workflowRunId}`)
    .listen('StepStarted', (e) => {
        console.log(`Step ${e.step_id} is running... Attempt: ${e.attempt}`);
    })
    .listen('StepSucceeded', (e) => {
        console.log(`Step ${e.step_id} completed successfully!`);
    })
    .listen('StepFailed', (e) => {
        console.error(`Step ${e.step_id} failed:`, e.error_message);
    });
```
