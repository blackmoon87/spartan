@extends('layouts/vanilla')

@section('title', 'Awwwards / Godly Style Glassmorphic Showcase — Spartan')

@section('content')
<div class="glass-card" style="border-color: rgba(99, 102, 241, 0.4); position: relative; overflow: hidden;">
    <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(34,211,238,0.2), transparent); pointer-events: none;"></div>
    
    <span style="background: rgba(99,102,241,0.2); color: var(--accent); font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.75rem; border-radius: 999px; border: 1px solid rgba(34,211,238,0.3);">
        Awwwards & Godly Aesthetic Standard
    </span>
    
    <h1 style="font-size: 2.2rem; font-weight: 800; margin: 1rem 0 0.5rem 0; background: linear-gradient(135deg, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
        Ultra-High Precision Glassmorphic Design System
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 700px;">
        Zero-dependency PHP 8.1+ MVC rendering hardware-accelerated CSS3 backdrop filters and smooth gradient micro-animations.
    </p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    @foreach($highlights as $item)
    <div class="glass-card" style="transition: transform 0.2s, border-color 0.2s; cursor: pointer;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #fff;">{{ $item['title'] }}</h3>
            <span style="background: rgba(34,211,238,0.1); color: var(--accent); font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 4px; border: 1px solid rgba(34,211,238,0.2);">
                {{ $item['badge'] }}
            </span>
        </div>
        <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5;">
            {{ $item['desc'] }}
        </p>
    </div>
    @endforeach
</div>

<div class="glass-card">
    <h2 style="font-size: 1.2rem; margin-bottom: 1rem; color: #fff;">⚡ Direct Blade Compiled Core Architecture</h2>
    <pre style="background: rgba(0,0,0,0.5); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border); font-family: monospace; font-size: 0.85rem; color: #22d3ee; overflow-x: auto;">
// Controllers/CssShowcaseController.php
public function vanilla(): string 
{
    return $this->render('showcase/vanilla', [
        'title' => 'Awwwards / Godly Glassmorphism',
        'metrics' => ['TTFB' => '1.8ms', 'Memory' => '4.5MB']
    ]);
}
</pre>
</div>
@endsection
