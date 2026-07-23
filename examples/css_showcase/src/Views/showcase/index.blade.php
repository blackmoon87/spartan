@extends('layouts/vanilla')

@section('title', 'CSS Framework Multi-Support Hub — Spartan')

@section('content')
<div class="glass-card">
    <h1 style="color: var(--accent); margin-bottom: 0.5rem;">🎨 Multi-CSS Framework Engine Hub</h1>
    <p style="color: var(--text-muted);">Demonstrates Spartan's front-end agnostic architecture rendering Tailwind CSS, Open Props, and Vanilla Glassmorphism layouts dynamically.</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
    <div class="glass-card">
        <h3>⚡ Tailwind CSS Integration</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.5rem 0 1rem 0;">Utility-first CSS styling compiled or served via CDN directly in Blade layouts.</p>
        <a href="/css/tailwind" style="color: var(--accent); font-weight: 600; text-decoration: none;">View Tailwind Demo →</a>
    </div>

    <div class="glass-card">
        <h3>🎨 Open Props System</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.5rem 0 1rem 0;">Framework-agnostic CSS custom properties system using native standard variables.</p>
        <a href="/css/openprops" style="color: var(--accent); font-weight: 600; text-decoration: none;">View Open Props Demo →</a>
    </div>

    <div class="glass-card">
        <h3>💎 Vanilla Glassmorphism</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.5rem 0 1rem 0;">Awwwards & Godly style dark-mode glassmorphic cards and micro-animations.</p>
        <a href="/css/vanilla" style="color: var(--accent); font-weight: 600; text-decoration: none;">View Vanilla Demo →</a>
    </div>
</div>
@endsection
