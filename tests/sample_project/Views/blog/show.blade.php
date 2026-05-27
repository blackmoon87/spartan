@extends('layouts.main_blade')

@section('content')
    <div style="margin-bottom: 2rem;">
        <a href="{{ url('/') }}" style="color: var(--accent); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; transition: var(--transition);">
            &larr; Back to Directory
        </a>
    </div>

    <div class="layout-grid">
        <!-- Left Side: Post Detail & Comments list -->
        <div>
            <div class="card" style="padding: 2.5rem;">
                <h1 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 1rem; line-height: 1.2;">{{ $post->title }}</h1>
                <p style="font-size: 1.1rem; color: var(--text-primary); white-space: pre-line;">{{ $post->body }}</p>
            </div>

            <div class="card">
                <h2>Comments ({{ count($comments) }})</h2>
                <div id="comments-section">
                    @if (count($comments) > 0)
                        @foreach ($comments as $comment)
                            <div class="post-item" style="border-bottom: 1px solid var(--border-color); padding: 1.2rem 0;">
                                <p style="color: var(--text-primary); margin-bottom: 0.4rem;">{{ $comment['content'] }}</p>
                                <small style="color: var(--text-secondary); font-weight: 500;">By: <strong style="color: var(--text-primary);">{{ $comment['author']['name'] ?? 'Guest' }}</strong></small>
                            </div>
                        @endforeach
                    @else
                        <p style="color: var(--text-secondary); font-size: 0.95rem;">No comments yet. Be the first to leave one below!</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Side: Comment Form -->
        <div>
            <div class="card">
                <h2>Leave a Comment</h2>
                <form method="POST" action="{{ url('/post/' . $post->id . '/comment') }}">
                    @csrf
                    <label>Select Your Author Profile</label>
                    <select name="user_id" required>
                        <option value="">Choose registered user...</option>
                        @foreach ($users as $u)
                            <option value="{{ $u['id'] }}">{{ $u['name'] }} (ID: {{ $u['id'] }})</option>
                        @endforeach
                    </select>

                    <label>Your Comment</label>
                    <textarea name="content" required rows="5" placeholder="Share your thoughts..."></textarea>
                    <button type="submit">Submit Comment</button>
                </form>
            </div>
        </div>
    </div>
@endsection
