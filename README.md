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

### Docker Review

- `Dockerfile` uses a multi-stage build: Node builds Vite assets, then PHP-FPM serves the Laravel app with optimized Composer dependencies.
- `docker-compose.yml` runs the full local stack: PHP-FPM app, Nginx, PostgreSQL, Redis, queue worker, scheduler, Reverb WebSocket server, and an optional one-shot asset builder.
- Nginx is exposed on `http://localhost:8080`.
- Reverb is exposed to the browser on `ws://localhost:8082`, while Laravel containers talk to it internally through `reverb:8080`.
- Redis is used for queue and cache. PostgreSQL is exposed on host port `5433`.

### Fresh Start

1. **Clone the repository**

```bash
git clone <repository-url>
cd flowforge
```

2. **Create the environment file**

```bash
cp .env.example .env
```

For Docker, keep these important values:

```dotenv
APP_URL=http://localhost:8080

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=flowforge
DB_USERNAME=flowforge
DB_PASSWORD=secret

QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_HOST=redis

BROADCAST_CONNECTION=reverb
REVERB_HOST=reverb
REVERB_PORT=8080
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8082
```

3. **Build and start the stack**

```bash
docker compose up -d --build
```

This starts:

- `app`: Laravel PHP-FPM
- `nginx`: HTTP entrypoint
- `postgres`: database
- `redis`: queue/cache backend
- `queue`: workflow step worker
- `scheduler`: Laravel scheduler for cron triggers
- `reverb`: WebSocket server

4. **Install local assets into the bind-mounted project**

The Docker image contains built assets, but local development bind-mounts the project directory over the container code. Run this once after a fresh clone:

```bash
docker compose run --rm assets
```

5. **Generate app key and prepare the database**

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

6. **Clear cached config and routes after changing `.env` or routes**

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
```

7. **Open the app**

```text
http://localhost:8080
```

---

## Running the Application

The default Docker stack starts the queue worker and Reverb automatically.

### Queue Worker

The queue worker handles DAG step execution and retries.

```bash
docker compose logs -f queue
docker compose restart queue
```

To run it manually instead:

```bash
docker compose exec app php artisan queue:work redis --tries=3 --backoff=5,10,20 --timeout=300
```

### Reverb WebSocket Server

Reverb powers real-time workflow updates. It listens inside Docker on `reverb:8080` and is exposed to the browser on `localhost:8082`.

```bash
docker compose logs -f reverb
docker compose restart reverb
```

To run it manually instead:

```bash
docker compose exec app php artisan reverb:start --host=0.0.0.0 --port=8080
```

### Scheduler (Cron Triggers)

Cron-based workflow triggers run via the Laravel scheduler. In Docker, the `scheduler`
service runs `php artisan schedule:work` continuously.

```bash
docker compose logs -f scheduler
docker compose restart scheduler
```

To run it manually instead:

```bash
docker compose exec app php artisan schedule:work
```

### Useful Local Commands

```bash
docker compose ps
docker compose logs -f app
docker compose logs -f nginx
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app vendor/bin/phpunit
docker compose down
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

Standard query params:

- `page` (int)
- `per_page` (int, max 100)
- `search` (string)
- `sort_by` (name, created_at, updated_at, runs_count)
- `sort_dir` (asc|desc)
- `trigger_type` (manual|cron|webhook)

#### 5. Trigger Workflow by Webhook

Webhook workflows are triggered through a public tokenized endpoint. The token is stored in the workflow trigger config as `webhook_token`.

- **Endpoint:** `POST /api/webhooks/{token}`
- **Auth:** Public endpoint, token-based, rate limited.
- **Body:** Any JSON payload from the external system.

```bash
curl -X POST http://localhost:8080/api/webhooks/{token} \
  -H "Content-Type: application/json" \
  -d '{"event":"user.created","id":123}'
```

The workflow receives the request as input:

```json
{
  "payload": { "event": "user.created", "id": 123 },
  "headers": { "content-type": "application/json" },
  "received_at": "2026-05-04T12:00:00+00:00"
}
```

#### Workflow Timeout

Workflow definitions may include `workflow_timeout_seconds`. If omitted, FlowForge uses a 300 second default. Timed out runs are marked `failed`, and unfinished steps receive `last_error = "Workflow timed out"`.

---

## Users API

### List Users

- **Endpoint:** `GET /api/users`

Standard query params:

- `page` (int)
- `per_page` (int, max 100)
- `search` (string)
- `sort_by` (name, email, created_at)
- `sort_dir` (asc|desc)
- `role` (string)

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

For environments where WebSocket is unavailable, the run monitor UI includes a lightweight polling
fallback that keeps status and logs updated.

- **Channel:** `workflow.{workflowRunId}`
- **Emitted Events:** `WorkflowStarted`, `StepStarted`, `StepSucceeded`, `StepFailed`, `WorkflowCompleted`
- **Frontend Listener Example:**

```javascript
import Echo from "laravel-echo";

Echo.private(`workflow.${workflowRunId}`)
    .listen("StepStarted", (e) => {
        console.log(`Step ${e.step_id} is running... Attempt: ${e.attempt}`);
    })
    .listen("StepSucceeded", (e) => {
        console.log(`Step ${e.step_id} completed successfully!`);
    })
    .listen("StepFailed", (e) => {
        console.error(`Step ${e.step_id} failed:`, e.error_message);
    });
```

---

## Deployment Checklist

Before moving to production, ensure the following:

- [ ] Redis is configured as the `QUEUE_CONNECTION` and `CACHE_DRIVER`.
- [ ] Database credentials and `tenant_id` scopes are securely managed.
- [ ] Supervisor (or similar process monitor) is configured to keep `php artisan queue:work` running.
- [ ] A proper WebSocket server (Pusher/Reverb/Soketi) is deployed and configured in `.env` for real-time features.
- [ ] Horizon is installed (optional but recommended) to monitor Redis queues visually.
