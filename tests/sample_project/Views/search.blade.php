@extends('layouts.main_blade')

@section('content')
    <h2 style="margin-bottom: 1.5rem; font-weight: 700;">Customer Directory Search</h2>

    <!-- HTMX Real-time search form -->
    <div class="form-group">
        <label style="display: block; margin-bottom: 0.5rem; color: var(--color-muted); font-size: 0.9rem;">
            Type a name or email to filter customers (HTMX Live Search)
        </label>
        
        <!-- CSRF Token hidden field -->
        @csrf

        <input type="text" 
               name="query" 
               class="form-control" 
               placeholder="Start typing (e.g. John, Charlie, admin)..." 
               hx-post="/search/query" 
               hx-trigger="keyup delay:200ms, search" 
               hx-target="#search-results" 
               hx-include="[name='_csrf']"
               autocomplete="off">
    </div>

    <!-- Target for HTMX search results -->
    <div id="search-results">
        <div style="text-align: center; padding: 2rem; color: var(--color-muted);">
            Type in the search field above to load customers dynamically.
        </div>
    </div>

    <!-- Alpine.js Showcase Widget -->
    <div class="alpine-widget" x-data="{ count: 0 }">
        <h4 style="font-weight: 600; color: #a78bfa; margin-bottom: 0.25rem;">Alpine.js Client-Side Interactivity</h4>
        <p style="font-size: 0.85rem; color: var(--color-muted); margin-bottom: 0.75rem;">
            This counter runs 100% in the browser using Alpine.js without reloading the server.
        </p>
        <div style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem;" x-text="count">0</div>
        <button class="btn-action" @click="count++">Increment Count</button>
        <button class="btn-action" @click="count = 0" style="background-color: transparent; border: 1px solid var(--border-color); color: var(--color-text);">Reset</button>
    </div>
@endsection
