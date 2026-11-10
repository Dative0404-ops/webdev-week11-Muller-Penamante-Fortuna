@extends('layouts.app')

@section('title', 'Local scopes')

@section('content')
<h1 class="page-title">Local scopes (Step 3)</h1>
<p class="page-sub">Two scopes chained: <code>upcoming('2026-12-01')</code> &rarr; <code>minCapacity(500)</code>.</p>

<div class="card">
    <h2>Generated SQL</h2>
    <pre style="white-space:pre-wrap">{{ $sql }}</pre>
</div>

<div class="card">
    <h2>Results (up to 50)</h2>
    <table>
        <thead><tr><th>#</th><th>Title</th><th>Starts</th><th>Capacity</th><th>Venue</th></tr></thead>
        <tbody>
            @forelse ($events as $event)
                <tr>
                    <td>{{ $event->id }}</td>
                    <td>{{ $event->title }}</td>
                    <td>{{ optional($event->starts_at)->format('M d, Y H:i') }}</td>
                    <td>{{ $event->details['capacity'] ?? '—' }}</td>
                    <td>{{ $event->venue->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No events match both scopes.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
