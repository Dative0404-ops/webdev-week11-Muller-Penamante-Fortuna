@extends('layouts.app')

@section('title', 'JSONB filter')

@section('content')
<h1 class="page-title">JSONB filter by <code>details-&gt;category</code></h1>
<p class="page-sub">Step 5: two ways to filter the JSONB column, using the rare value <strong>{{ $category }}</strong>.</p>

<div class="card">
    <h2>Form A &mdash; arrow form: <code>where('details->category', ...)</code></h2>
    <pre style="white-space:pre-wrap">{{ $sqlA }}</pre>
    <p class="hint">{{ $eventsA->count() }} matching events (showing up to 50).</p>
    <table>
        <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Venue</th></tr></thead>
        <tbody>
            @forelse ($eventsA as $event)
                <tr>
                    <td>{{ $event->id }}</td>
                    <td>{{ $event->title }}</td>
                    <td>{{ $event->details['category'] ?? '—' }}</td>
                    <td>{{ $event->venue->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">No matches.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Form B &mdash; containment: <code>whereJsonContains('details', ['category' =&gt; ...])</code></h2>
    <pre style="white-space:pre-wrap">{{ $sqlB }}</pre>
    <p class="hint">{{ $eventsB->count() }} matching events (showing up to 50).</p>
    <table>
        <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Venue</th></tr></thead>
        <tbody>
            @forelse ($eventsB as $event)
                <tr>
                    <td>{{ $event->id }}</td>
                    <td>{{ $event->title }}</td>
                    <td>{{ $event->details['category'] ?? '—' }}</td>
                    <td>{{ $event->venue->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">No matches.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
