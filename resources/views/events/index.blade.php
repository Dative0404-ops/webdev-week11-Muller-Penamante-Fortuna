@extends('layouts.app')

@section('title', 'Events (eager-loaded)')

@section('content')
<h1 class="page-title">Events &mdash; first {{ $events->count() }}</h1>
<p class="page-sub">Eager-loaded version (Step 4.3). <strong>{{ $queryCount }}</strong> queries for {{ $events->count() }} events.</p>

<div class="callout">
    Used <code>with('venue')</code> and <code>withCount('participants')</code> so the venue name and participant count
    are fetched without an N+1.
</div>

<div class="card" style="display:flex;gap:16px;flex-wrap:wrap">
    <a href="{{ route('events.naive') }}">Compare the naive page</a>
    <a href="{{ route('events.jsonb') }}">Filter JSONB details</a>
    <a href="{{ route('events.scopes') }}">View chained scopes</a>
    <a href="{{ route('events.search') }}">Search descriptions</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr><th>#</th><th>Title</th><th>Venue</th><th>Participants</th><th>Starts</th></tr>
        </thead>
        <tbody>
            @forelse ($events as $event)
                <tr>
                    <td>{{ $event->id }}</td>
                    <td>{{ $event->title }}</td>
                    <td>{{ $event->venue->name ?? '—' }}</td>
                    <td>{{ $event->participants_count }}</td>
                    <td>{{ optional($event->starts_at)->format('M d, Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No events found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
