@extends('layouts/tailwind')

@section('title', 'Tailwind CSS High-End UI Component Suite — Spartan')

@section('content')
<!-- Header Banner -->
<div class="relative overflow-hidden bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl mb-8">
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>
    
    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium bg-amber-400/10 text-amber-400 border border-amber-400/20 mb-3">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                Tailwind CSS v3 + Spartan Blade Engine
            </span>
            <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight">
                Enterprise Dashboard Component Suite
            </h1>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
                High-performance markup rendered server-side by Spartan Blade with sub-millisecond execution times.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button class="px-4 py-2.5 rounded-lg text-sm font-semibold bg-amber-400 text-slate-950 hover:bg-amber-300 transition shadow-lg shadow-amber-400/20">
                Deploy Dashboard
            </button>
            <button class="px-4 py-2.5 rounded-lg text-sm font-semibold bg-slate-800 text-slate-200 hover:bg-slate-700 border border-slate-700 transition">
                Export Reports
            </button>
        </div>
    </div>
</div>

<!-- Metrics Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    @foreach($metrics as $metric)
    <div class="bg-slate-900/80 backdrop-blur border border-slate-800 rounded-xl p-5 hover:border-slate-700 transition">
        <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
            <span>{{ $metric['label'] }}</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $metric['isUp'] ? 'bg-emerald-400/10 text-emerald-400 border border-emerald-400/20' : 'bg-rose-400/10 text-rose-400 border border-rose-400/20' }}">
                {{ $metric['change'] }}
            </span>
        </div>
        <div class="text-2xl font-bold text-white tracking-tight mb-2">{{ $metric['value'] }}</div>
        <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
            <div class="bg-gradient-to-r from-amber-400 to-cyan-400 h-full rounded-full" style="width: 78%"></div>
        </div>
    </div>
    @endforeach
</div>

<!-- Interactive Data Table Card -->
<div class="bg-slate-900/80 backdrop-blur border border-slate-800 rounded-xl overflow-hidden shadow-xl mb-8">
    <div class="p-6 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-white">Active System Projects</h2>
            <p class="text-xs text-slate-400">Real-time status tracking powered by Spartan ORM & QueryBuilder.</p>
        </div>
        <div class="flex items-center gap-2">
            <input type="text" placeholder="Search projects..." class="bg-slate-950 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-amber-400/50 w-48">
            <button class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700 hover:bg-slate-700">Filter</button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="px-6 py-3.5">Project</th>
                    <th class="px-6 py-3.5">Category</th>
                    <th class="px-6 py-3.5">Status</th>
                    <th class="px-6 py-3.5">Progress</th>
                    <th class="px-6 py-3.5">Budget</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @foreach($projects as $proj)
                <tr class="hover:bg-slate-800/40 transition">
                    <td class="px-6 py-4 font-semibold text-white">
                        {{ $proj['name'] }}
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-400">
                        {{ $proj['category'] }}
                    </td>
                    <td class="px-6 py-4">
                        @if($proj['status'] === 'Production')
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-400/10 text-emerald-400 border border-emerald-400/20">
                            ✓ {{ $proj['status'] }}
                        </span>
                        @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-400/10 text-amber-400 border border-amber-400/20">
                            ⏳ {{ $proj['status'] }}
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-32 bg-slate-800 h-2 rounded-full overflow-hidden">
                                <div class="bg-gradient-to-r from-amber-400 to-cyan-400 h-full rounded-full" style="width: {{ $proj['progress'] }}%"></div>
                            </div>
                            <span class="text-xs font-mono font-medium text-slate-400">{{ $proj['progress'] }}%</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 font-mono text-sm text-slate-200">
                        {{ $proj['budget'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Pricing Component Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    @foreach($pricing as $plan)
    <div class="relative bg-slate-900/80 backdrop-blur border {{ $plan['popular'] ? 'border-amber-400/50 shadow-amber-500/10' : 'border-slate-800' }} rounded-2xl p-6 flex flex-col justify-between shadow-xl">
        @if($plan['popular'])
        <div class="absolute -top-3 right-6 bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 text-xs font-extrabold px-3 py-1 rounded-full uppercase tracking-wider shadow">
            Most Popular
        </div>
        @endif

        <div>
            <h3 class="text-xl font-bold text-white mb-1">{{ $plan['name'] }}</h3>
            <p class="text-xs text-slate-400 mb-4">{{ $plan['desc'] }}</p>
            <div class="flex items-baseline gap-1 mb-6">
                <span class="text-4xl font-extrabold text-white tracking-tight">{{ $plan['price'] }}</span>
                <span class="text-xs text-slate-400">{{ $plan['period'] }}</span>
            </div>
            <ul class="space-y-3 text-sm text-slate-300 mb-6">
                @foreach($plan['features'] as $feat)
                <li class="flex items-center gap-2">
                    <span class="text-emerald-400 font-bold">✓</span>
                    <span>{{ $feat }}</span>
                </li>
                @endforeach
            </ul>
        </div>

        <button class="w-full py-3 rounded-xl font-bold text-sm transition {{ $plan['popular'] ? 'bg-amber-400 text-slate-950 hover:bg-amber-300 shadow-lg shadow-amber-400/20' : 'bg-slate-800 text-slate-200 hover:bg-slate-700 border border-slate-700' }}">
            Get Started with {{ $plan['name'] }}
        </button>
    </div>
    @endforeach
</div>
@endsection
