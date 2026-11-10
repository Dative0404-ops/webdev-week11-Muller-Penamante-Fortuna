@extends('layouts.app')

@section('title', 'Events (naive / N+1)')

@section('content')
<h1 class="page-title">Events &mdash; naive version (N+1)</h1>
<p class="page-sub">Step 4.1: lazy loading. <strong>{{ $naiveQueryCount }}</strong> queries for {{ $events->count() }} events.</p>

<div class="callout">
    Each row triggered its own query for the venue and another for the participant count &mdash;
    that is the classic <strong>N+1</strong> problem.
</div>

<div class="card">
    <h2>Query counts</h2>
    <table>
        <thead><tr><th>Version</th><th>Queries</th><th>Pattern</th></tr></thead>
        <tbody>
            <tr><td>Naive (lazy)</td><td><strong>{{ $naiveQueryCount }}</strong></td><td>1 (load events) + {{ $events->count() }} (venue each) + {{ $events->count() }} (count each)</td></tr>
            <tr><td>Fixed (eager)</td><td><strong>2</strong></td><td>1 (load events) + 1 (load venues) &mdash; count is a sub-select</td></tr>
        </tbody>
    </table>
    <p style="margin-top:12px;font-size:13px;color:var(--muted)">
        Difference: the lazy version hits the database once <em>per event</em> for the venue and once more for the count,
        so {{ $events->count() }} events cost {{ $naiveQueryCount }} queries. The eager version loads all venues in a single query and computes the count as a
        sub-select, so it stays at 2 queries no matter how many events are listed.
    </p>
</div>

<div class="card">
    <h2>Step 4.2 &mdash; preventLazyLoading() error</h2>
    @if ($lazyError)
        <pre style="white-space:pre-wrap;color:#b91c1c">{{ $lazyError }}</pre>
    @else
        <p class="empty">No error captured.</p>
    @endif
    <p style="margin-top:10px;font-size:13px;color:var(--muted)">
        <code>Model::preventLazyLoading()</code> throws a <code>LazyLoadingViolationException</code> the moment we touch
        a relationship that was not eager-loaded, so an N+1 bug cannot slip through in development.
    </p>
</div>

<div class="card">
    <h2>First 50 events</h2>
    <table>
        <thead>
            <tr><th>#</th><th>Title</th><th>Venue</th><th>Participants</th></tr>
        </thead>
        <tbody>
            @forelse ($events as $event)
                <tr>
                    <td>{{ $event->id }}</td>
                    <td>{{ $event->title }}</td>
                    <td>{{ $event->venue->name ?? '—' }}</td>
                    <td>{{ $event->naive_participants_count }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">No events found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
