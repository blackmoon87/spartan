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
                @if (!empty($post->cover_image))
                    <div style="margin-bottom: 1.5rem; border-radius: 12px; overflow: hidden; max-height: 350px; border: 1px solid var(--border-color);">
                        <img src="{{ asset($post->cover_image) }}" alt="{{ $post->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                @endif
                <h1 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 1rem; line-height: 1.2; color: #f3f4f6;">{{ $post->title }}</h1>
                <p style="font-size: 1.1rem; color: #d1d5db; white-space: pre-line; line-height: 1.7;">{{ $post->body }}</p>
            </div>

            @can('update', $post)
                <div class="card" style="border: 1px solid rgba(251, 191, 36, 0.4); background: rgba(251, 191, 36, 0.02); margin-top: 1rem;">
                    <h2 style="font-size: 1.3rem; margin-bottom: 1rem; color: #fbbf24;">Edit Post Content</h2>
                    <form method="POST" action="{{ url('/post/' . $post->id) }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="_method" value="PUT">
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.4rem; font-size: 0.9rem; font-weight: 500;">Post Title</label>
                            <input type="text" name="title" value="{{ $post->title }}" required style="width: 100%; box-sizing: border-box;">
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.4rem; font-size: 0.9rem; font-weight: 500;">Post Cover Image</label>
                            <input type="file" name="cover_image" accept="image/*" style="width: 100%; box-sizing: border-box; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-color); padding: 0.5rem; border-radius: 6px; color: var(--text-color);">
                            @if (!empty($post->cover_image))
                                <small style="color: var(--text-secondary); display: block; margin-top: 0.3rem;">Current cover image: {{ $post->cover_image }}</small>
                            @endif
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.4rem; font-size: 0.9rem; font-weight: 500;">Post Content</label>
                            <textarea name="body" required rows="5" style="width: 100%; box-sizing: border-box; resize: vertical;">{{ $post->body }}</textarea>
                        </div>
                        <button type="submit" style="width: 100%; padding: 0.75rem; font-weight: 600; background: #fbbf24; color: #0b0f19; border: none; cursor: pointer; transition: all 0.2s;">
                            Save Changes
                        </button>
                    </form>
                </div>
            @endcan

            <div class="card">
                <h2 style="font-size: 1.4rem; margin-bottom: 1rem; color: #f3f4f6;">Comments ({{ count($comments) }})</h2>
                <div id="comments-section">
                    @if (count($comments) > 0)
                        @foreach ($comments as $comment)
                            <div class="post-item" style="border-bottom: 1px solid var(--border-color); padding: 1.2rem 0; margin-bottom: 0;">
                                <p style="color: #e5e7eb; margin-bottom: 0.5rem; line-height: 1.5;">{{ $comment['content'] }}</p>
                                <small style="color: var(--text-secondary); font-weight: 500;">By: <strong style="color: #f3f4f6;">{{ $comment['author']['name'] ?? 'Guest' }}</strong></small>
                            </div>
                        @endforeach
                    @else
                        <p style="color: var(--text-secondary); font-size: 0.95rem;">No comments yet. Be the first to leave one!</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Side: Comment Action -->
        <div>
            @if (\App\Core\Application::$app->session->get('user_id'))
                <div class="card">
                    <h2 style="font-size: 1.4rem; margin-bottom: 0.5rem; color: #f3f4f6;">Leave a Comment</h2>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Commenting as <strong>{{ \App\Core\Application::$app->session->get('user_name') }}</strong>.</p>
                    
                    <form method="POST" action="{{ url('/post/' . $post->id . '/comment') }}">
                        @csrf
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.4rem; font-size: 0.9rem; font-weight: 500;">Your Comment</label>
                            <textarea name="content" required rows="5" placeholder="Share your thoughts..." style="width: 100%; box-sizing: border-box; resize: vertical;"></textarea>
                        </div>
                        <button type="submit" style="width: 100%; padding: 0.75rem; font-weight: 600; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border: none; cursor: pointer; transition: all 0.2s;">
                            Submit Comment
                        </button>
                    </form>
                </div>
            @else
                <div class="card" style="text-align: center; border: 1px dashed rgba(255, 255, 255, 0.15); background: rgba(255, 255, 255, 0.02);">
                    <h2 style="font-size: 1.4rem; margin-bottom: 0.75rem; color: #f3f4f6;">Join Discussion</h2>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.5rem;">Please log in or register to join the conversation and leave a comment on this post.</p>
                    <div style="display: flex; gap: 0.75rem; justify-content: center;">
                        <a href="{{ url('/login') }}" style="display: inline-block; padding: 0.6rem 1.2rem; font-weight: 600; background: #3b82f6; color: #ffffff; border-radius: 6px; text-decoration: none; font-size: 0.9rem;">
                            Log In
                        </a>
                        <a href="{{ url('/register') }}" style="display: inline-block; padding: 0.6rem 1.2rem; font-weight: 600; background: rgba(255, 255, 255, 0.08); color: #f3f4f6; border-radius: 6px; text-decoration: none; font-size: 0.9rem;">
                            Sign Up
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
