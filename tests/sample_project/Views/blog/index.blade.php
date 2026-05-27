@extends('layouts.main_blade')

@section('content')
    <div class="layout-grid">
        <!-- Left Side: Blog Directory & Search -->
        <div>
            <!-- HTMX Live Search -->
            <div class="card">
                <h2>Search Posts</h2>
                <input type="text" name="query" hx-post="{{ url('/search/posts') }}" hx-trigger="keyup changed delay:200ms" hx-target="#search-results" placeholder="Search title or body...">
                <div id="search-results"></div>
            </div>

            <!-- Blog Posts List -->
            <div class="card">
                <h2>Blog Posts Directory</h2>
                <div id="posts-list">
                    @if (count($posts) > 0)
                        @foreach ($posts as $post)
                            <div class="post-item">
                                <h3><a href="{{ url('/post/' . $post['id']) }}">{{ $post['title'] }}</a></h3>
                                <p>{{ $post['body'] }}</p>
                                <div class="post-meta">
                                    <span>By: <strong>{{ $post['author']['name'] ?? 'Anonymous' }}</strong></span>
                                    <span>•</span>
                                    <span>Comments: {{ count($post['comments'] ?? []) }}</span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p style="color: var(--text-secondary);">No blog posts found. Create one on the right!</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Side: User System & Creation -->
        <div>
            <!-- User Registry -->
            <div class="card">
                <h2>User System (Authors)</h2>
                <div style="margin-bottom: 1.5rem; max-height: 250px; overflow-y: auto; padding-right: 0.5rem;">
                    @if (count($users) > 0)
                        @foreach ($users as $u)
                            <div class="author-badge">
                                <div>
                                    <div class="author-name">{{ $u['name'] }}</div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">{{ $u['email'] }}</div>
                                </div>
                                <span class="author-id">ID: {{ $u['id'] }}</span>
                            </div>
                        @endforeach
                    @else
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">No authors registered yet.</p>
                    @endif
                </div>

                <!-- Register Author Form -->
                <h3 style="border-top: 1px solid var(--border-color); padding-top: 1.2rem; font-size: 1.1rem;">Register New Author</h3>
                <form method="POST" action="{{ url('/user') }}" style="margin-top: 0.8rem;">
                    @csrf
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="e.g. John Doe">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="e.g. john@example.com">
                    <button type="submit">Add User</button>
                </form>
            </div>

            <!-- Create Post Form -->
            <div class="card">
                <h2>Create New Post</h2>
                <form method="POST" action="{{ url('/post') }}">
                    @csrf
                    <label>Select Author</label>
                    <select name="user_id" required>
                        <option value="">Choose registered user...</option>
                        @foreach ($users as $u)
                            <option value="{{ $u['id'] }}">{{ $u['name'] }} (ID: {{ $u['id'] }})</option>
                        @endforeach
                    </select>
                    <label>Post Title</label>
                    <input type="text" name="title" required placeholder="Catchy title...">
                    <label>Post Content</label>
                    <textarea name="body" required rows="5" placeholder="Write your post here..."></textarea>
                    <button type="submit">Publish Post</button>
                </form>
            </div>
        </div>
    </div>
@endsection
