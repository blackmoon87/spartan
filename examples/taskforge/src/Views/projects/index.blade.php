@extends('layouts/app')

@section('title', 'Projects — TaskForge')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
    <h1>📁 Projects</h1>
    @role('admin', 'manager')
    <a href="#new-project" class="btn" onclick="document.getElementById('new-project-form').style.display='block'">+ New Project</a>
    @endrole
</div>

<div id="new-project-form" style="display:none; margin-bottom:24px;">
    <div class="card" style="border-color: var(--primary);">
        <h2>Create Project</h2>
        <form method="POST" action="/projects/store">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label for="proj-name">Project Name</label>
                    <input type="text" id="proj-name" name="name" required minlength="3">
                </div>
                <div class="form-group">
                    <label for="proj-priority">Priority</label>
                    <select id="proj-priority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="proj-desc">Description</label>
                <textarea id="proj-desc" name="description" rows="3" required minlength="10"></textarea>
            </div>
            <div class="form-group">
                <label for="proj-deadline">Deadline (optional)</label>
                <input type="date" id="proj-deadline" name="deadline">
            </div>
            <button type="submit">Create Project</button>
        </form>
    </div>
</div>

@if(empty($projects))
<div class="card"><p style="color:var(--text-muted);">No projects yet.</p></div>
@else
<div class="grid">
    @foreach($projects as $project)
    <div class="card">
        <h2><a href="/project/{{ $project['slug'] }}">{{ $project['name'] }}</a></h2>
        <p style="color:var(--text-muted); font-size:.85rem; margin-bottom:12px;">{{ $project['description'] }}</p>
        <div style="display:flex; gap:8px; align-items:center;">
            <span class="badge badge-{{ $project['priority'] }}">{{ ucfirst($project['priority']) }}</span>
            <span class="badge" style="background:var(--border);">{{ $project['status'] }}</span>
            @if($project['deadline'])
            <span style="font-size:.8rem; color:var(--text-muted);">📅 {{ $project['deadline'] }}</span>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
