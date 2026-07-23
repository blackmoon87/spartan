@extends('layouts/app')

@section('title', $title ?? 'Dashboard')

@section('content')
<h1 style="margin-bottom: 24px;">📊 Dashboard</h1>

<div class="stats">
    <div class="stat-card">
        <div class="num">{{ $stats['total_projects'] ?? 0 }}</div>
        <div class="label">Projects</div>
    </div>
    <div class="stat-card">
        <div class="num">{{ $stats['total_tasks'] ?? 0 }}</div>
        <div class="label">Total Tasks</div>
    </div>
    <div class="stat-card">
        <div class="num">{{ $stats['completed_tasks'] ?? 0 }}</div>
        <div class="label">Completed</div>
    </div>
    <div class="stat-card">
        <div class="num">{{ $stats['active_users'] ?? 0 }}</div>
        <div class="label">Team Members</div>
    </div>
</div>

@if(!empty($topProjects))
<div class="card">
    <h2>🏆 Top Projects by Task Count</h2>
    <table>
        <thead><tr><th>Project</th><th>Tasks</th></tr></thead>
        <tbody>
        @foreach($topProjects as $proj)
        <tr>
            <td><a href="/project/{{ $proj['slug'] }}">{{ $proj['name'] }}</a></td>
            <td>{{ $proj['task_count'] }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if(!empty($recentTasks))
<div class="card">
    <h2>🕐 Recent Tasks</h2>
    <table>
        <thead><tr><th>Task</th><th>Assignee</th><th>Status</th><th>Priority</th></tr></thead>
        <tbody>
        @foreach($recentTasks as $task)
        <tr>
            <td>{{ $task['title'] }}</td>
            <td>{{ $task['assignee'] ?? 'Unassigned' }}</td>
            <td>
                @if($task['status'] === 'done')
                <span class="badge badge-done">✓ Done</span>
                @elseif($task['status'] === 'in_progress')
                <span class="badge badge-progress">⏳ In Progress</span>
                @else
                <span class="badge badge-todo">📋 Todo</span>
                @endif
            </td>
            <td>
                <span class="badge badge-{{ $task['priority'] }}">{{ ucfirst($task['priority']) }}</span>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@can('manage_projects')
<div class="card" style="border-color: var(--primary);">
    <h2>🔧 Manager Actions</h2>
    <p style="color:var(--text-muted); font-size:.9rem;">You have project management permissions.</p>
</div>
@endcan

@cannot('manage_users')
<div class="card" style="border-color: var(--border); opacity: .6;">
    <p style="color:var(--text-muted); font-size:.85rem;">🔒 Admin panel access requires <code>manage_users</code> permission.</p>
</div>
@endcannot
@endsection
