@extends('layouts/openprops')

@section('title', 'Open Props Integration — Spartan')

@section('content')
<div class="card">
    <h1 style="color: var(--indigo-4);">Open Props CSS Custom Properties System</h1>
    <p>Using CSS variables directly compiled with Spartan Blade layout inheritance.</p>
</div>

<div class="card">
    <h3>Standard CSS Variables Evaluated</h3>
    <ul style="padding-left: var(--size-4);">
        @foreach($props as $name => $value)
            <li style="margin-bottom: var(--size-2);">
                <code style="color: var(--pink-4);">{{ $name }}</code> : {{ $value }}
            </li>
        @endforeach
    </ul>
</div>
@endsection
