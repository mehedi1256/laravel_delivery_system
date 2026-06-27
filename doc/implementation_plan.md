# Laravel Developer Assessment Implementation Plan

## Goal Description
Build a robust Laravel 13 REST API with PostgreSQL to address the full "Laravel Developer Assessment." The system will act as a Delivery Management System, demonstrating best practices in database design, query optimization, handling large datasets, background processing, caching, real-time broadcasting, and automated testing. 

*New additions:* Queue/jobs and caching will run on the database driver, the API will be fully documented using Swagger, and authentication will be handled via Laravel Sanctum.

## Proposed Changes

### Setup & Configuration
- **Laravel Framework**: Create a new Laravel 13 project.
- **Environment**: Configure `.env` for PostgreSQL and Database (for queue processing and caching).
- **Authentication**: Utilize the built-in Laravel Sanctum. Build a full login and registration system secured with Sanctum API tokens.
- **Database Seeder**: Implement a `DatabaseSeeder` to leverage Model Factories and populate the database with realistic test data.
- **Background Services (Jobs & Queues)**:
  - **Notification Service**: Handled exclusively via Laravel jobs to dispatch notifications with retry strategies.
  - **Export Service**: Handled via chunked Laravel jobs for generating large CSVs asynchronously.
  - **Import Service**: Handled via Laravel Job Batching for processing large CSV uploads with progress tracking.
- **Architecture**: Use the **Repository Design Pattern** to abstract database access and keep controllers clean.
- **API Documentation**: Install `darkaonline/l5-swagger` to generate OpenAPI/Swagger documentation for all endpoints.

### 01. Database & Eloquent Basics
- **Migrations**: Create schemas for `users`, `deliveries`, and `delivery_logs`. Apply `softDeletes()` to `deliveries` and `users`.
- **Models**: Define `HasMany` and `BelongsTo` relationships. 
- **Query**: Implement efficient querying on large tables using `withLatest()` and `withCount()`.

### 02. Query Optimization
- **Indexes**: Add a composite index on `deliveries(user_id, status, created_at)` via migration.
- **Documentation**: Document the `EXPLAIN` diagnosis process for diagnosing the 10+ second slow queries involving joins.

### 03. Handling Large Datasets
- **Memory-safe Data Processing**: Rewrite the notification Artisan command using `->chunk()` or `LazyCollection` (`->cursor()`) to prevent memory exhaustion when processing 100,000+ records.
- **Cursor-based Pagination**: Replace offset pagination with `->cursorPaginate()` for the `GET /deliveries` endpoint.
- **Deferring Long-running Work**: Refactor the long-running report endpoint to dispatch a background Job (using the database queue) and return a status payload immediately.

### 04. Export Pipelines
- **Large CSV Export**: Implement a background job that streams 50,000 delivery records to a CSV file without exhausting memory, and generates a signed download link expiring after one hour.
- **Dashboard Aggregation Query**: Write an optimized aggregation query (total deliveries, success rate, average time) grouped by week for the last 3 months, utilizing Database caching.

### 05. API Design
- **Versioned API Resource**: Create API Resource classes for `v1` (with deprecation communication) and `v2` (restructuring the `user` field as a nested `assigned_agent`).
- **Tenant Middleware & Rate Limiting**: Create a middleware that identifies the tenant from the request and configures dynamic rate limiting based on their subscription plan. (JWT auth will secure these endpoints).

### 06. Queues & Jobs
- **Import Job with Progress Tracking**: Use Laravel Job Batching (`Bus::batch()`) via the database queue driver to import 5,000 CSV rows, tracking progress and isolating individual row failures.
- **Retry & Failure Handling**: Implement an exponential backoff retry strategy (`backoff()`) for third-party API notification jobs, logging failures and alerting when retries are exhausted.

### 07. Events & Broadcasting
- **Model Observer**: Create `DeliveryObserver` hooking into state changes (`updated` event) to automatically record a log entry when a delivery's status changes.
- **Real-time Status Updates**: Configure Laravel Reverb (or Pusher) to broadcast a `DeliveryStatusUpdated` event on a private, authorized channel to update the assigned driver's frontend in real time.

### 08. Caching & Performance
- **Cache Strategy for Tenant Data**: Implement a caching layer for tenant route data using Database cache tags (e.g., `Cache::tags(['tenant:'.$id])`) to ensure isolation and easy invalidation without affecting other tenants.
- **Fixing a Memory-leaking Command**: Address the Artisan command memory leak by disabling query logs, using `unset()`, and leveraging PHP garbage collection inside the loop.

### 09. Testing
- **Feature Test**: Write Pest feature tests for the `POST /api/v1/imports` endpoint using `Event::fake()` and `Queue::fake()` to verify job dispatch, event firing, and response shape.
- **Model Factory**: Create `DeliveryFactory` producing realistic test data (Dhaka-area coordinates, `bn_BD` local phone numbers, appropriate timestamps) using Faker.

### 10. Code Review & Debugging
- **Identify the Bugs**: Review and document theoretical snippets regarding N+1 queries, un-chunked data loads in endpoints, and incorrect middleware database lookups.
- **Refactor a Fat Controller**: Refactor an inline 80+ line controller into FormRequests (validation), Actions/Services (business logic), and Jobs (notifications).
- **Intermittent 500 Errors**: Outline the step-by-step investigation, isolation, and resolution process for production-level concurrent load bottlenecks (e.g., DB connection limits, cache stampedes).
