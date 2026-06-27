# Part 2.1: Index Strategy Justification

## Requirement
The `deliveries` table has grown to 100,000+ rows. A frequently used query filters by `user_id`, `status`, and a `created_at` date range.

## Implementation
This index was implemented in the migration `database/migrations/2024_01_01_000003_create_deliveries_table.php` on line 24:
```php
$table->index(['user_id', 'status', 'created_at']);
```

## Justification
When designing a composite (multi-column) index for a query that contains both equality filters (`=`) and range filters (`>` , `<`, `BETWEEN`), the order of the columns is critical due to how B-Trees operate (they are evaluated left-to-right).

1. **`user_id` (Equality Match - High Cardinality):** Placed first. It filters the 100,000+ rows down to just the handful of deliveries belonging to a specific user. This is an exact match and eliminates the most rows immediately.
2. **`status` (Equality Match - Low Cardinality):** Placed second. Out of the user's deliveries, we exactly match the specific status (e.g., 'pending'). B-Trees can efficiently chain multiple equality checks together.
3. **`created_at` (Range Match):** Placed last. In B-Tree indexes, once the database encounters a range condition, it stops seeking and must scan the rest of the index leaf nodes for that condition. If we had placed `created_at` *before* `status`, the database would not be able to use the `status` part of the index effectively. Placing the range filter last ensures maximum efficiency for the preceding equality checks.

Because of this specific order `(user_id, status, created_at)`, PostgreSQL can perform an extremely fast **Index Seek** directly to the relevant records without scanning the table.
