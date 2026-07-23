@extends('layouts/openprops')

@section('title', 'Open Props CSS System & Tokens Suite — Spartan')

@section('content')
<div class="card" style="background: var(--surface-2); border-left: 4px solid var(--indigo-5);">
    <h1 style="color: var(--indigo-4); margin-bottom: var(--size-2);">🎨 Open Props Tokens Engine</h1>
    <p style="color: var(--text-2);">Custom CSS Properties evaluation with zero runtime JavaScript or build steps.</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--size-4); margin-top: var(--size-4);">
    @foreach($tokens as $category => $propList)
    <div class="card" style="margin-top: 0;">
        <h3 style="color: var(--pink-4); border-bottom: 1px solid var(--surface-3); padding-bottom: var(--size-2); margin-bottom: var(--size-3);">
            {{ $category }} Tokens
        </h3>
        @foreach($propList as $prop => $val)
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--size-3); font-size: var(--font-size-1);">
            <code style="color: var(--cyan-4); font-family: var(--font-mono);">{{ $prop }}</code>
            <span style="color: var(--text-2); font-weight: var(--font-weight-6);">{{ $val }}</span>
        </div>
        @endforeach
    </div>
    @endforeach
</div>

<div class="card" style="margin-top: var(--size-4);">
    <h3 style="color: var(--lime-4);">Dynamic Elevation & Shadow Tokens</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--size-4); margin-top: var(--size-3);">
        <div style="background: var(--surface-3); padding: var(--size-4); border-radius: var(--radius-2); box-shadow: var(--shadow-1); text-align: center;">
            <strong>Shadow 1</strong>
            <p style="font-size: var(--font-size-0); color: var(--text-2);">Low Depth</p>
        </div>
        <div style="background: var(--surface-3); padding: var(--size-4); border-radius: var(--radius-2); box-shadow: var(--shadow-3); text-align: center;">
            <strong>Shadow 3</strong>
            <p style="font-size: var(--font-size-0); color: var(--text-2);">Floating Depth</p>
        </div>
        <div style="background: var(--surface-3); padding: var(--size-4); border-radius: var(--radius-2); box-shadow: var(--shadow-5); text-align: center;">
            <strong>Shadow 5</strong>
            <p style="font-size: var(--font-size-0); color: var(--text-2);">Hero Elevation</p>
        </div>
    </div>
</div>
@endsection
