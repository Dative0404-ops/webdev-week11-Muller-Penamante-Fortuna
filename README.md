# WebDev Week 11 — Campus Events (Laravel + PostgreSQL)

An extended **Campus Events** app built with Laravel 12 and PostgreSQL 18. It
keeps the Week 10 relational model and adds 20,000 seeded events, JSONB details,
reusable Eloquent scopes, N+1 demonstrations, and indexed full-text search.

---

## 1. The data rules

| Link | What the system needs |
|---|---|
| Venue and Event | A venue hosts many events. An event happens at one venue. A venue that still has events must **not** be deletable. |
| Event and Participant | An event has many participants. A participant joins many events. Each registration stores a **status** (`registered` / `waitlisted`). The same participant cannot register for the same event twice. If an event or participant is deleted, their registrations disappear too. |
| Note | A note belongs to **either** an event **or** a venue, using one `notes` table. |

---

## 2. ERD (text form)

```
venues                          events
------                          ------
id (PK)  ───────1:N──────────►  id (PK)
name                            venue_id (FK → venues.id, ON DELETE RESTRICT)
address                         title
capacity                        description
                                 starts_at
                                 details (JSONB: dress_code, capacity, tags, category)
        │                              │
        │ (polymorphic)                │ (polymorphic)
        │                              │
        └──────────┐        ┌──────────┘
                   ▼        ▼
                    notes
                    -----
                    id (PK)
                    body
                    notable_id      ← points at an event OR a venue (no FK possible)
                    notable_type    ← "App\Models\Event" or "App\Models\Venue"


events                          participants
------                          ------------
id (PK)  ────┐            ┌──── id (PK)
             │            │     name
             │            │     email (UNIQUE)
             ▼            ▼
        event_participant            (the registration / pivot table)
        -----------------
        id (PK)
        event_id        (FK → events.id,       ON DELETE CASCADE)
        participant_id  (FK → participants.id, ON DELETE CASCADE)
        status          ('registered' | 'waitlisted')
        UNIQUE (event_id, participant_id)
```

---

## 3. Relationship table

| Link | Type | Model that holds the foreign key | Relationship methods | ON DELETE | Why this choice |
|---|---|---|---|---|---|
| Venue ↔ Event | One-to-many (1:N) | **Event** (`events.venue_id`) | `Venue::events()` (hasMany) / `Event::venue()` (belongsTo) | **RESTRICT** | A venue that still has events must not be deletable — the database refuses to delete a parent that is still referenced. |
| Event ↔ Participant | Many-to-many (N:N) | **EventParticipant pivot** (`event_participant`) | `Event::participants()` / `Participant::events()` (both belongsToMany) | **CASCADE** | If an event or a participant is deleted, their registration rows must disappear too. |
| Note → Event / Venue | Polymorphic (1:N + 1:N) | **Note** (`notes.notable_id` + `notes.notable_type`) | `Event::notes()` / `Venue::notes()` (morphMany), `Note::notable()` (morphTo) | **none (no FK)** | `notable_id` can point at two different tables, so PostgreSQL cannot enforce a single reference — the database allows an orphan note; only our code can prevent it. |

Extra rule enforced by the database: the composite **`UNIQUE (event_id, participant_id)`** on the pivot stops the same participant registering for the same event twice.

---

## 4. Tables created

| Table | Purpose | Key columns |
|---|---|---|
| `venues` | Places that host events | `id` (PK), `name`, `address`, `capacity` |
| `events` | Events at a venue | `id` (PK), `venue_id` (FK, RESTRICT), `title`, `description`, `starts_at`, `details` (JSONB) |
| `participants` | People who join events | `id` (PK), `name`, `email` (UNIQUE) |
| `event_participant` | Registrations (pivot) | `id` (PK), `event_id` (FK, CASCADE), `participant_id` (FK, CASCADE), `status`, UNIQUE(event_id, participant_id) |
| `notes` | Notes on an event or a venue | `id` (PK), `body`, `notable_id`, `notable_type` |

---

## 5. Setup

```bash
# 1) install dependencies
composer install

# 2) copy the environment file and set your PostgreSQL credentials
copy .env.example .env
php artisan key:generate
#   edit .env -> DB_CONNECTION=pgsql, DB_DATABASE=campus_events,
#                DB_USERNAME, DB_PASSWORD

# 3) create the database (once)
createdb campus_events

# 4) build the schema and the dataset
php artisan migrate:fresh --seed

# 5) browse the app
php artisan serve
#   http://127.0.0.1:8000/                    -> home dashboard
#   http://127.0.0.1:8000/events               -> eager-loaded events
#   http://127.0.0.1:8000/events/naive         -> N+1 comparison
#   http://127.0.0.1:8000/events/jsonb         -> JSONB filters
#   http://127.0.0.1:8000/events/scopes        -> chained local scopes
#   http://127.0.0.1:8000/events/search?q=firefly -> full-text search
#   http://127.0.0.1:8000/report               -> Week 10 relationship report
```

The `.env` file is **not** committed (it may hold real credentials).
`.env.example` shows the same keys with an empty password.

---

## 6. Seeded dataset

`php artisan migrate:fresh --seed` produces:

- **3** venues
- **20,000** events
- **200** participants
- **500-1,500** registrations across the first 100 events (the exact total varies by seed run)
- **0** notes from this Week 11 seeder; the polymorphic `notes` table and Week 10 report remain available

Events are inserted in batches of 1,000 and registrations in batches of 500 to
keep the seed operation efficient. The term `firefly` and category `astronomy`
each occur in 100 of 20,000 rows (0.5%), making the filter examples selective.

---

## 7. What PostgreSQL enforces vs. what our code must enforce

**Enforced by PostgreSQL:**

1. `events.venue_id` → inserting an event with a non-existent venue fails (FK).
2. `events.venue_id` `ON DELETE RESTRICT` → deleting a venue that still has events fails.
3. `UNIQUE (event_id, participant_id)` → registering the same participant twice for the same event fails.

**NOT enforced by PostgreSQL:**

4. A note whose `notable_id` points at a missing event/venue is **accepted**. PostgreSQL cannot stop this because `notable_id` may reference *two* different tables (`events` or `venues`), and a single foreign key can only point at one table. Keeping orphan notes out is a job for our application code.

---

## 8. The seven report queries

Implemented in `app/Http/Controllers/ReportController.php`, shown on `/report`:

1. Every venue with the number of events it hosts — `Venue::withCount('events')`
2. All events at one chosen venue — `$venue->events()`
3. All participants of one chosen event, with registration status — `$event->participants()` (pivot `status`)
4. All events one chosen participant has joined — `$participant->events()`
5. All events that have at least one waitlisted participant — `Event::whereHas('participants', ...)`
6. All notes of one chosen event, and all notes of one chosen venue — `$event->notes()`, `$venue->notes()`
7. For one chosen note, the name and kind of the thing it belongs to — `$note->notable` (morphTo)

---

## 9. Week 11 scopes and query counts

`Event` defines reusable local scopes in `app/Models/Event.php`:

| Scope | What it filters | Example |
|---|---|---|
| `upcoming($from)` | Events starting on or after the supplied date/time | `Event::upcoming('2026-12-01')` |
| `minCapacity($min)` | Events whose JSONB `details.capacity` is at least the supplied value | `Event::minCapacity(500)` |
| `category($category)` | Events matching a JSONB category | `Event::category('workshop')` |

The `/events/scopes` page chains the first two scopes and shows the generated
SQL. Two scopes accept parameters, and the chain passes a date and a capacity.

The `/events/naive` and `/events` pages compare the same first 50 events. Query
logging covers the database reads used to build each table:

| Page | Queries for 50 events | Why |
|---|---:|---|
| `/events/naive` | 101 | 1 event query + 50 lazy venue queries + 50 participant-count queries |
| `/events` | 2 | 1 event query with a count subquery + 1 eager venue query |

The naive page also demonstrates `Model::preventLazyLoading()` by deliberately
touching an unloaded relationship on a multi-row result and displaying the
`LazyLoadingViolationException`. It temporarily permits lazy loading only while
measuring the 101-query example, then restores the development setting. The
eager-loaded page replaces those per-row queries with `with('venue')` and
`withCount('participants')`.

---

## 10. JSONB and full-text search

`events.details` is JSONB and stores `dress_code`, numeric `capacity`, string
`category`, and an array of `tags`. These values are flexible event metadata;
core fields such as title, venue, start time, and searchable description remain
normal columns.

The `/events/jsonb` page runs both forms against the rare category:

```php
Event::where('details->category', 'astronomy')->get();
Event::whereJsonContains('details', ['category' => 'astronomy'])->get();
```

The first form generates an equality predicate on `details->>'category'`; the
second generates JSONB containment (`details @> ...`). The migration creates a
B-tree expression index for the first and a GIN index for the second.

The `/events/search?q=firefly` page uses `whereFullText('description', $q)`,
shows Laravel's SQL, and limits rendered results to 50. A PostgreSQL GIN index
on `to_tsvector('english', description)` supports that full-text predicate.

---

## 11. PostgreSQL `EXPLAIN ANALYZE` evidence

Plans were collected on PostgreSQL after `ANALYZE events`, using the seeded
20,000-row table and 100-match selective values. Times are one local run and
will vary by hardware and cache state. To simulate having no usable index
without dropping the migration-managed indexes, the no-index comparison turned
off `enable_indexscan`, `enable_bitmapscan`, and `enable_indexonlyscan` for the
session; PostgreSQL then used a sequential scan for each query.

### No usable index

| Query | No-index plan | Execution time |
|---|---|---:|
| `details->>'category' = 'astronomy'` | Seq Scan; 19,900 rows removed by filter | 4.240 ms |
| `details @> '{"category":"astronomy"}'` | Seq Scan; 19,900 rows removed by filter | 6.164 ms |
| Full-text `firefly` | Seq Scan; 19,900 rows removed by filter | 207.623 ms |

### Best available index

| Query | Index | PostgreSQL plan | Execution time |
|---|---|---|---:|
| `details->>'category' = 'astronomy'` | `events_details_category_idx` (B-tree expression) | Bitmap Index Scan + Bitmap Heap Scan | 0.166 ms |
| `details @> '{"category":"astronomy"}'` | `events_details_gin_idx` (GIN) | Bitmap Index Scan + Bitmap Heap Scan | 0.395 ms |
| Full-text `firefly` | `events_description_fts_idx` (GIN) | Bitmap Index Scan + Bitmap Heap Scan | 0.255 ms |

The same expression B-tree index exists but is not used for this broad query:
`Event::where('details->category', '!=', 'not-a-category')`. It returns all
20,000 rows, so PostgreSQL chooses a `Seq Scan` (7.684 ms in this run) rather
than visiting the index and then fetching nearly every table row.

---

## 12. Index persistence and migration notes

All three indexes are created in
`database/migrations/2026_11_10_000002_add_search_indexes_to_events.php`, so
they are rebuilt by `php artisan migrate:fresh --seed`. Laravel's schema
builder handles the JSONB GIN index; the category expression index and the
full-text GIN index use raw SQL because they index PostgreSQL expressions.
That raw SQL is guarded for the PostgreSQL driver, so SQLite-based application
tests can still run without trying to create PostgreSQL-only index types.

---

## 13. Verification

Run the automated checks with:

```bash
php artisan test
```

The feature tests cover a clean home page and verify the lazy/eager event pages
show five versus two queries for a two-event fixture, as well as the captured
lazy-loading exception. In the 20,000-event seeded application the corresponding
table shows 101 versus 2 queries for 50 events.
