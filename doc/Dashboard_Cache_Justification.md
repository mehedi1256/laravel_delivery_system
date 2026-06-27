# Part 4.2: Dashboard Query & Caching Decision

## The Query Implementation
The dashboard API endpoint has been implemented at `GET /api/reports/dashboard` inside `app/Http/Controllers/DashboardController.php`.

The query leverages highly efficient PostgreSQL aggregation functions to calculate the required data directly in the database, avoiding the fatal mistake of pulling 100,000+ rows into PHP memory to map/reduce.

```php
DB::table('deliveries')
    ->select(
        DB::raw("DATE_TRUNC('week', created_at) as week"),
        DB::raw("COUNT(id) as total_deliveries"),
        DB::raw("SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(id), 0) as success_rate"),
        DB::raw("AVG(EXTRACT(EPOCH FROM (updated_at - created_at))) as avg_delivery_time_seconds")
    )
    ->where('tenant_id', $tenantId)
    ->where('created_at', '>=', now()->subMonths(3))
    ->groupBy('week')
    ->orderBy('week', 'desc')
    ->get();
```

## Caching Decision: YES (Using Redis Cache Tags)

**Justification:**
1. **Computational Cost**: Even with perfect indexes, running `SUM`, `COUNT`, `AVG`, and `DATE_TRUNC` groupings across hundreds of thousands of records takes a measurable amount of database CPU and disk I/O.
2. **Read-Heavy Nature of Dashboards**: Users tend to load their dashboards frequently. Running this query on every single page load for every tenant would bring the database to its knees under high traffic.
3. **The Stale Data Problem**: The main argument *against* caching is that delivery statuses change frequently, and users want up-to-date success rates. 
4. **The Solution (Cache Tags)**: We solve the stale data problem by utilizing **Redis Cache Tags**. We tag the dashboard cache uniquely to the tenant (`tenant:{tenant_id}`). 
   - The query result is cached for 1 hour.
   - When any delivery belonging to that tenant changes status, the `DeliveryObserver` can run `Cache::tags(['tenant:'.$tenantId, 'dashboard'])->flush();`.
   - This ensures the dashboard is always **lightning fast** on reads, but guarantees **fresh data** immediately after an update, without flushing the cache for the other 1,000 tenants in the system.
