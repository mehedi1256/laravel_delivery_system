# Part 10: Code Review & Debugging

## 10.1 — Identify the Bugs

### Snippet A: N+1 Query in Controller Loop
**The Problem:** The controller action creates a delivery and its associated waypoints inside a `foreach` loop. This results in the classic "N+1 Query Problem." If the user submits 50 waypoints, Laravel will execute 51 separate `INSERT` statements to the database.
**Production Risk:** Under high traffic, this will exhaust database connections, spike database CPU utilization, and drastically increase latency for the client.
**Corrected Version:** 
Instead of looping and saving individually, use Laravel's `insert()` method or Eloquent's `createMany()` relationship method to do a bulk insert in a single query.
```php
$delivery = Delivery::create($request->only('details'));

$waypoints = collect($request->waypoints)->map(function ($waypoint) {
    return ['location' => $waypoint['location'], 'status' => 'pending'];
});

// Single INSERT statement for all waypoints
$delivery->waypoints()->createMany($waypoints);
```

### Snippet B: Database Lookup in Middleware
**The Problem:** The middleware queries the database for the current tenant on *every single request*.
**Production Risk:** Middleware runs before the controller. If you have 500 requests per second, you are running 500 identical tenant lookup queries per second. This is an immense waste of DB resources.
**Corrected Version:** 
Cache the tenant lookup using Laravel's Cache facade. (We successfully implemented this in `TenantMiddleware.php` in this project!)
```php
$tenantId = $request->header('X-Tenant-ID');
$tenant = Cache::remember('tenant_'.$tenantId, 3600, function () use ($tenantId) {
    return Tenant::find($tenantId);
});
```

### Snippet C: Memory Exhaustion on Large Datasets
**The Problem:** Loading all records from a 100,000-row table into memory using `Model::all()` or `->get()` and returning them in a single response.
**Production Risk:** PHP will hit its `memory_limit` (usually 128MB or 256MB) and crash with a fatal error (HTTP 500). Furthermore, returning a JSON response of 100,000 records will lock the client browser.
**Corrected Version:** 
Use `cursorPaginate()` for API responses, or `chunk()` / `cursor()` for background processing.
```php
// For an API response
return DeliveryResource::collection(Delivery::cursorPaginate(100));

// For background processing
foreach (Delivery::cursor() as $delivery) {
    // Process single record without keeping previous in memory
}
```

---

## 10.2 — Refactor a Fat Controller

A "Fat Controller" handles too many responsibilities (validation, business logic, DB, cache, notifications). This violates the Single Responsibility Principle (SRP). 

**Refactored Structure:**
1. **Form Requests (`App\Http\Requests\DeliveryRequest`)**: Moves input validation rules and authorization out of the controller.
2. **Repositories (`App\Repositories\DeliveryRepository`)**: Handles all database interaction (`create`, `update`). Keeps Eloquent queries out of the controller.
3. **Services (`App\Services\DeliveryService`)**: Orchestrates the business logic. It takes the validated DTO, calls the Repository to write to the database, clears the necessary Cache tags, and triggers Events.
4. **Events/Listeners (`App\Events\DeliveryCreated`)**: The service fires a `DeliveryCreated` event. An asynchronous listener handles the actual notification dispatch, keeping it off the main HTTP thread.
5. **Controller (`App\Http\Controllers\DeliveryController`)**: Becomes "Skinny". It simply injects the Service, passes the validated request data to it, and returns the JSON response.

**Corrected Controller:**
```php
public function store(DeliveryRequest $request, DeliveryService $service)
{
    $delivery = $service->createDelivery($request->validated());
    return new DeliveryResource($delivery);
}
```

---

## 10.3 — Production: Intermittent 500 Errors

When random 500 errors occur under moderate load and are difficult to reproduce locally, the issue is almost always related to **shared resource exhaustion** (Database Connections, Redis Connections, PHP-FPM workers, or external API rate limits).

**Investigation Process:**
1. **Check Error Logs:** First, check `storage/logs/laravel.log`, Sentry, or Datadog to identify the exact Exception being thrown.
2. **Analyze Resource Metrics:** Look at the AWS RDS/PostgreSQL dashboard. Are we hitting the `max_connections` limit? Look at Redis CPU and connection counts. Look at the web server (Nginx/PHP-FPM) to see if the worker pool is exhausted (`server reached max_children`).
3. **Database Locks/Deadlocks:** Check if slow, long-running queries are locking rows, causing subsequent fast queries to time out waiting for the lock. 

**Likely Culprits & Solutions:**
- **DB Connection Limits:** PHP-FPM processes don't share database connections efficiently by default. Under load, 500 concurrent users will open 500 DB connections, exceeding PostgreSQL's default limits. **Solution:** Implement PgBouncer for connection pooling.
- **External API Timeouts:** If a third-party API (like SMS notification) takes 3 seconds to respond, it holds the PHP worker hostage for 3 seconds. **Solution:** Move all external API calls to background queues (`ShouldQueue`).
- **N+1 Queries:** A sudden influx of data combined with an N+1 query can cause a previously "fast" page to take 15 seconds, maxing out PHP execution time limits. **Solution:** Use Laravel Telescope or Clockwork to profile query counts.
