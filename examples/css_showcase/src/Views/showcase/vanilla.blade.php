@extends('layouts/vanilla')

@section('title', 'Vanilla Glassmorphism — Spartan')

@section('content')
<div class="glass-card">
    <h1 style="color: var(--primary);">💎 Vanilla Glassmorphic Engine</h1>
    <p style="color: var(--text-muted); margin-top: 0.5rem;">Pure CSS3 backdrop-filter glass cards with custom CSS variables.</p>
</div>

<div class="glass-card">
    <h2>Performance Engine Metrics</h2>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1rem;">
        @foreach($metrics as $label => $val)
        <div style="background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 8px; text-align: center;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent);">{{ $val }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $label }}</div>
        </div>
        @endforeach
    </div>
</div>
@endsection
