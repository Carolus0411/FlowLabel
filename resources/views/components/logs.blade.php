@props([
    'data'
])
<x-card>
    <div class="space-y-4">
        <h2 class="text-lg font-semibold">Histories</h2>
        <table class="table table-xs table-zebra">
        <thead>
        <tr>
            <th>User</th>
            <th>Action</th>
            <th>Time</th>
        </tr>
        </thead>
        <tbody>
        @isset ($data->logs)
        @forelse ($data->logs()->with('user')->latest()->limit(8)->get() as $log)
        <tr>
            <td>{{ $log->user->name }}</td>
            <td>{{ $log->action }}</td>
            <td>{{ $log->created_at->diffForHumans() }}</td>
        </tr>
        @empty
        <tr><td colspan="3">No data found.</td></tr>
        @endforelse
        @else
        <tr><td colspan="3">No logs available.</td></tr>
        @endisset
        </tbody>
        </table>
    </div>
</x-card>
