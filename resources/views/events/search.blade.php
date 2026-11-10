@extends('layouts.app')

@section('title', 'Full-text search')

@section('content')
<h1 class="page-title">Full-text search</h1>
<p class="page-sub">Step 6: <code>whereFullText</code> on <code>description</code>, at most 50 results.</p>

<div class="card">
    <form method="get" action="{{ route('events.search') }}" style="display:flex;gap:10px;align-items:center">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search descriptions…"
               style="flex:1;padding:10px 12px;border:1px solid var(--line);border-radius:8px;font-size:14px">
        <button type="submit" style="background:var(--brand);color:#fff;border:0;padding:10px 18px;border-radius:8px;cursor:pointer;font-size:14px">Search</button>
    </form>
</div>

@if ($q)
    <div class="card">
        <h2>SQL Laravel produces</h2>
        <pre style="white-space:pre-wrap">{{ $sql }}</pre>
        <p class="hint"><strong>{{ $events->count() }}</strong> results &middot; <strong>{{ $queryCount }}</strong> queries for this page.</p>
    </div>

    <div class="card">
        <table>
            <thead><tr><th>#</th><th>Title</th><th>Description</th><th>Venue</th></tr></thead>
            <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td>{{ $event->id }}</td>
                        <td>{{ $event->title }}</td>
                        <td>{{ $event->description }}</td>
                        <td>{{ $event->venue->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">No matches for “{{ $q }}”.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
