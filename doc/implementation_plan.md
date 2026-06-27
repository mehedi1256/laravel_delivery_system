# Laravel Developer Assessment Implementation Plan

## Goal Description
The goal is to build a robust Laravel REST API with PostgreSQL and Redis to address the full "Laravel Developer Assessment." The system will act as a Delivery Management System, demonstrating best practices in database design, query optimization, handling large datasets, background processing, caching, real-time broadcasting, and automated testing.

## Proposed Changes

### Setup & Configuration
- Create a new Laravel project.
- Configure `.env` for PostgreSQL and Redis connections.

### 01. Database & Eloquent Basics
- **Migrations**: Create schemas for `users`, `deliveries`, and `delivery_logs`. Apply `softDeletes()` to `deliveries` and `users`.
- **Models**: Define `HasMany` and `BelongsTo` relationships. 
- **Query**: Implement efficient querying on large tables using `withLatest()` and `withCount()`.

### 02. Query Optimization
- **Indexes**: Add a composite index on `deliveries(user_id, status, created_at)`.
- **Documentation**: Document the `EXPLAIN` diagnosis process for slow queries.

### 03. Handling Large Datasets
- **Command Refactor**: Rewrite the notification Artisan command using `LazyCollection` (`->cursor()`) or chunking.
- **Pagination**: Implement cursor-based pagination for the `GET /deliveries` endpoint.
- **Reports**: Refactor long-running report endpoints to dispatch a Job and return a status payload immediately.

### 04. Export Pipelines
- **CSV Export Job**: Implement a chunked export job using `fputcsv` streaming, returning a signed URL valid for 1 hour.
- **Dashboard Query**: Write an optimized aggregation query and implement a Redis caching strategy.

### 05. API Design
- **Versioning**: Create API Resource classes for `v1` (with deprecation logic) and `v2`.
- **Middleware**: Create `TenantMiddleware` to inject tenant context and apply dynamic `RateLimiter` based on subscription plans.

### 06. Queues & Jobs
- **Import Tracking**: Use Laravel Job Batching (`Bus::batch()`) to track progress of CSV rows and catch individual failures.
- **Retry Strategy**: Implement exponential backoff in notification jobs.

### 07. Events & Broadcasting
- **Observer**: Create `DeliveryObserver` hooking into state changes to log updates.
- **Real-time**: Configure Laravel Reverb/Pusher and broadcast `DeliveryStatusUpdated` on a private channel.

### 08. Caching & Performance
- **Tenant Cache**: Use cache tags for tenant isolation and easy invalidation.
- **Memory Leaks**: Apply memory management techniques inside bulk processing commands to avoid exhaustion.

### 09. Testing
- **Factories**: Create `DeliveryFactory` with realistic local test data formatting.
- **Pest Tests**: Write feature tests using fakes (`Event::fake()`, `Queue::fake()`) to verify logic.

### 10. Code Review & Architecture
- **Refactoring**: Implement clean architecture with FormRequests, Actions, and Jobs to replace "fat controllers".
- **Debugging**: Address theoretical snippet bugs (e.g. N+1 queries, loops, un-chunked memory loads).
