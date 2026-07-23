@extends('layouts/app')

@section('title', ($project->name ?? 'Project') . ' — TaskForge')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
    <h1>{{ $project->name ?? 'Project' }}</h1>
    <a href="/projects" style="font-size:.85rem;">← Back to Projects</a>
</div>
<p style="color:var(--text-muted); margin-bottom:24px;">{{ $project->description ?? '' }}</p>

<div class="stats">
    <div class="stat-card">
        <div class="num">{{ $stats['total'] ?? 0 }}</div>
        <div class="label">Total Tasks</div>
    </div>
    <div class="stat-card">
        <div class="num">{{ $stats['done'] ?? 0 }}</div>
        <div class="label">Completed</div>
    </div>
    <div class="stat-card">
        <div class="num">{{ $stats['in_progress'] ?? 0 }}</div>
        <div class="label">In Progress</div>
    </div>
    <div class="stat-card">
        <div class="num">{{ $stats['completion'] ?? 0 }}%</div>
        <div class="label">Progress</div>
    </div>
</div>

<div class="card">
    <h2>📋 Tasks</h2>
    @if(empty($tasks))
    <p style="color:var(--text-muted);">No tasks yet.</p>
    @else
    <table>
        <thead><tr><th>Task</th><th>Status</th><th>Priority</th><th>Due</th><th>Action</th></tr></thead>
        <tbody>
        @foreach($tasks as $task)
        @include('tasks/partials/task_row', ['task' => $task])
        @endforeach
        </tbody>
    </table>
    @endif
</div>

<div class="card" style="border-color: var(--primary); margin-top:24px;">
    <h2>➕ Add Task</h2>
    <form method="POST" action="/tasks/store">
        @csrf
        <input type="hidden" name="project_id" value="{{ $project->id ?? '' }}">
        <input type="hidden" name="project_slug" value="{{ $project->slug ?? '' }}">
        <div class="form-row">
            <div class="form-group">
                <label for="task-title">Title</label>
                <input type="text" id="task-title" name="title" required minlength="3">
            </div>
            <div class="form-group">
                <label for="task-assignee">Assign To (User ID)</label>
                <input type="number" id="task-assignee" name="assigned_to" value="1" min="1">
            </div>
        </div>
        <div class="form-group">
            <label for="task-desc">Description</label>
            <textarea id="task-desc" name="description" rows="2"></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="task-priority">Priority</label>
                <select id="task-priority" name="priority">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div class="form-group">
                <label for="task-due">Due Date</label>
                <input type="date" id="task-due" name="due_date">
            </div>
        </div>
        <button type="submit">Create Task</button>
    </form>
</div>
@endsection
