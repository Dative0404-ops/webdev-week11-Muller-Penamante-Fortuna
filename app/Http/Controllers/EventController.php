<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Week 11 controller: N+1 hunt, JSONB filters and full-text search.
 */
class EventController extends Controller
{
    /**
     * Step 4.3 — the FIXED /events page.
     * Eager-loads the venue and counts participants, so only 1 query runs
     * (instead of 1 + N + N).
     */
    public function index()
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Eager loading: with('venue') loads all venues in 1 extra query;
        // withCount('participants') adds the count via a sub-select, no N+1.
        $events = Event::with('venue')
            ->withCount('participants')
            ->orderBy('id')
            ->limit(50)
            ->get();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        return view('events.index', compact('events', 'queryCount'));
    }

    /**
     * Step 4.1 — the NAIVE version that uses lazy loading.
     * We temporarily disable preventLazyLoading so the loop actually runs
     * (otherwise it throws on the first access), then we count the queries
     * to expose the N+1 pattern.
     *
     * Step 4.2 — we also capture the error that preventLazyLoading throws
     * when a relation is accessed without being eager-loaded.
     */
    public function naive()
    {
        // --- 4.2: capture the error preventLazyLoading throws (it is ON by default) ---
        $lazyError = null;
        $preventLazyLoading = Model::preventsLazyLoading();

        try {
            // Hydrate multiple models so Eloquent applies the lazy-load guard.
            $probeEvents = Event::orderBy('id')->limit(2)->get();
            if ($probeEvents->count() > 1) {
                $probeEvents->first()->venue;
            }
        } catch (LazyLoadingViolationException $ex) {
            $lazyError = $ex->getMessage();
        } finally {
            Model::preventLazyLoading($preventLazyLoading);
        }

        // --- 4.1: count queries for the lazy version ---
        // Allow lazy loading only while measuring, then restore the app setting.
        $preventLazyLoading = Model::preventsLazyLoading();
        Model::preventLazyLoading(false);

        try {
            DB::flushQueryLog();
            DB::enableQueryLog();

            // No with() / withCount(): each venue and count lookup is separate.
            $events = Event::orderBy('id')->limit(50)->get();

            foreach ($events as $event) {
                $event->venue->name; // lazy: one query per event
                $event->setAttribute('naive_participants_count', $event->participants()->count());
            }

            $naiveQueryCount = count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            Model::preventLazyLoading($preventLazyLoading);
        }

        return view('events.naive', compact('events', 'naiveQueryCount', 'lazyError'));
    }

    /**
     * Step 5 — JSONB filter demo.
     * Filters events by details->category using two different Eloquent forms
     * and shows the generated SQL for each (via toSql()).
     */
    public function jsonbFilter()
    {
        $category = request('category', 'astronomy'); // the rare value

        // Form A: arrow form (details->key).
        $queryA = Event::where('details->category', $category);
        $sqlA = $queryA->toSql();

        // Form B: whereJsonContains on the whole column.
        $queryB = Event::whereJsonContains('details', ['category' => $category]);
        $sqlB = $queryB->toSql();

        $eventsA = $queryA->with('venue')->withCount('participants')->limit(50)->get();
        $eventsB = $queryB->with('venue')->withCount('participants')->limit(50)->get();

        return view('events.jsonb', compact('category', 'sqlA', 'sqlB', 'eventsA', 'eventsB'));
    }

    /**
     * Step 6 — full-text search page at /events/search?q=...
     * Uses whereFullText on the description column, shows at most 50 results,
     * and shows the SQL Laravel produces.
     */
    public function search()
    {
        $q = request('q', 'firefly');

        if ($q) {
            DB::enableQueryLog();

            $events = Event::whereFullText('description', $q)
                ->with('venue')
                ->withCount('participants')
                ->limit(50)
                ->get();

            $queryCount = count(DB::getQueryLog());
            DB::disableQueryLog();

            // The SQL of the full-text part.
            $sql = Event::whereFullText('description', $q)->toSql();
        } else {
            $events = collect();
            $queryCount = 0;
            $sql = '';
        }

        return view('events.search', compact('q', 'events', 'queryCount', 'sql'));
    }

    /**
     * Bonus: chain two local scopes together (Step 3).
     * Example: upcoming events with a minimum capacity.
     */
    public function scopesDemo()
    {
        // Chain: upcoming() + minCapacity(). Both scopes take a parameter.
        $events = Event::upcoming('2026-12-01')
            ->minCapacity(500)
            ->with('venue')
            ->withCount('participants')
            ->limit(50)
            ->get();

        $sql = Event::upcoming('2026-12-01')->minCapacity(500)->toSql();

        return view('events.scopes', compact('events', 'sql'));
    }
}
