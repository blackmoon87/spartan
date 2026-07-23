@extends('layouts/tailwind')

@section('title', 'Tailwind CSS Integration — Spartan')

@section('content')
<div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-2xl mb-6">
    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-cyan-400 mb-2">
        Tailwind CSS Engine Integration
    </h1>
    <p class="text-slate-400">Utility-first markup seamlessly rendered by Spartan Blade compiler.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    @foreach($components as $comp)
    <div class="bg-slate-900/60 backdrop-blur border border-slate-800 p-5 rounded-lg hover:border-amber-400/50 transition">
        <div class="flex justify-between items-center mb-3">
            <span class="text-xs font-semibold px-2.5 py-1 rounded bg-amber-400/10 text-amber-400 border border-amber-400/20">
                {{ $comp['type'] }}
            </span>
            <span class="text-xs text-emerald-400 font-mono">{{ $comp['status'] }}</span>
        </div>
        <h3 class="font-bold text-lg text-slate-100 mb-1">{{ $comp['name'] }}</h3>
        <p class="text-slate-400 text-sm">Rendered via Blade directive @foreach with Tailwind CSS utility classes.</p>
    </div>
    @endforeach
</div>
@endsection
