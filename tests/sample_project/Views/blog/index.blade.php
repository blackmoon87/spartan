@extends('layouts.main_blade')

@section('content')
    <div class="layout-grid">
        <!-- Left Side: Blog Directory & Search -->
        <div>
            <!-- HTMX Live Search -->
            <div class="card">
                <h2 style="font-size: 1.4rem; margin-bottom: 1rem; color: #f3f4f6;">Search Posts</h2>
                <input type="text" name="query" hx-post="{{ url('/search/posts') }}" hx-trigger="keyup changed delay:200ms" hx-target="#search-results" placeholder="Search title or body..." style="width: 100%; box-sizing: border-box;">
                <div id="search-results" style="margin-top: 1rem;"></div>
            </div>

            <!-- Blog Posts List -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                    <h2 style="font-size: 1.4rem; margin: 0; color: #f3f4f6;">Blog Posts Directory</h2>
                    @if (isset($stats['total_posts']))
                        <span style="font-size: 0.85rem; padding: 0.25rem 0.75rem; border-radius: 50px; background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">
                            Total Posts: {{ $stats['total_posts'] }}
                        </span>
                    @endif
                </div>

                <div id="posts-list">
                    @if (count($posts) > 0)
                        @foreach ($posts as $post)
                            <div class="post-item">
                                @if (!empty($post['cover_image']))
                                    <div style="margin-bottom: 1rem; border-radius: 8px; overflow: hidden; max-height: 200px; border: 1px solid var(--border-color);">
                                        <img src="{{ asset($post['cover_image']) }}" alt="{{ $post['title'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                @endif
                                <h3 style="margin-top: 0; margin-bottom: 0.5rem; font-size: 1.25rem;">
                                    <a href="{{ url('/blog/' . $post['slug']) }}" style="color: #60a5fa; text-decoration: none; transition: color 0.2s;">
                                        {{ $post['title'] }}
                                    </a>
                                </h3>
                                <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 1rem;">{{ $post['body'] }}</p>
                                <div class="post-meta" style="display: flex; gap: 0.75rem; align-items: center; font-size: 0.85rem; color: #9ca3af; justify-content: space-between;">
                                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                                        <span>By: <strong style="color: #f3f4f6;">{{ $post['author_name'] ?? 'Anonymous' }}</strong></span>
                                        <span>•</span>
                                        <span>Comments: {{ count($post['comments'] ?? []) }}</span>
                                    </div>
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        @can('update', $post)
                                            <a href="{{ url('/post/' . $post['id']) }}" style="color: #fbbf24; text-decoration: none; font-size: 0.8rem; border: 1px solid rgba(251, 191, 36, 0.3); padding: 0.15rem 0.5rem; border-radius: 4px;">Edit</a>
                                        @endcan
                                        @can('delete', $post)
                                            <form action="{{ url('/post/' . $post['id']) }}" method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                                @csrf
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="submit" style="color: #ef4444; background: none; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.8rem; cursor: pointer; line-height: 1.2;">Delete</button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p style="color: var(--text-secondary);">No blog posts found. Be the first to publish!</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Side: Sidebar (Auth Control & Metadata) -->
        <div>
            @role('admin')
                <div class="card" style="border: 1px solid rgba(139, 92, 246, 0.4); background: rgba(139, 92, 246, 0.05); margin-bottom: 1rem;">
                    <h2 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: #a78bfa; display: flex; align-items: center; gap: 0.5rem;">Admin Status</h2>
                    <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Logged in as <strong>Administrator</strong>. Override policy rules active.</p>
                </div>
            @endrole

            @if (\App\Core\Application::$app->session->get('user_id'))
                <!-- Create Post Form (Logged In) -->
                <div class="card">
                    <h2 style="font-size: 1.4rem; margin-bottom: 0.5rem; color: #f3f4f6;">Publish Post</h2>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Share a new story with the world as <strong>{{ \App\Core\Application::$app->session->get('user_name') }}</strong>.</p>
                    
                    <form method="POST" action="{{ url('/post') }}" enctype="multipart/form-data">
                        @csrf
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.4rem; font-size: 0.9rem; font-weight: 500;">Post Title</label>
                            <input type="text" name="title" required placeholder="Catchy title..." style="width: 100%; box-sizing: border-box;">
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.4rem; font-size: 0.9rem; font-weight: 500;">Post Cover Image</label>
                            <input type="file" name="cover_image" accept="image/*" style="width: 100%; box-sizing: border-box; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-color); padding: 0.5rem; border-radius: 6px; color: var(--text-color);">
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.4rem; font-size: 0.9rem; font-weight: 500;">Post Content</label>
                            <textarea name="body" required rows="5" placeholder="Write your post content here..." style="width: 100%; box-sizing: border-box; resize: vertical;"></textarea>
                        </div>
                        <button type="submit" style="width: 100%; padding: 0.75rem; font-weight: 600; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border: none; cursor: pointer; transition: all 0.2s;">
                            Publish Post
                        </button>
                    </form>
                </div>
            @else
                <!-- Guest Call to Action Card -->
                <div class="card" style="text-align: center; border: 1px dashed rgba(255, 255, 255, 0.15); background: rgba(255, 255, 255, 0.02);">
                    <h2 style="font-size: 1.4rem; margin-bottom: 0.75rem; color: #f3f4f6;">Write on Spartan</h2>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.75rem;">Join a community of writers and developers. Log in or create an account to start publishing posts and leaving feedback.</p>
                    
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="{{ url('/login') }}" class="btn" style="display: block; text-decoration: none; padding: 0.75rem; font-weight: 600; background: #3b82f6; color: #ffffff; border-radius: 6px; text-align: center; transition: background 0.2s;">
                            Log In
                        </a>
                        <a href="{{ url('/register') }}" class="btn" style="display: block; text-decoration: none; padding: 0.75rem; font-weight: 600; background: rgba(255, 255, 255, 0.08); color: #f3f4f6; border-radius: 6px; text-align: center; transition: background 0.2s;">
                            Create Account
                        </a>
                    </div>
                </div>
            @endif

            <!-- Authors Directory -->
            <div class="card">
                <h2 style="font-size: 1.4rem; margin-bottom: 1rem; color: #f3f4f6;">Registered Authors</h2>
                <div style="max-height: 250px; overflow-y: auto; padding-right: 0.5rem;">
                    @if (count($users) > 0)
                        @foreach ($users as $u)
                            <div class="author-badge" style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: rgba(255, 255, 255, 0.04); border-radius: 6px; margin-bottom: 0.5rem; border: 1px solid var(--border-color);">
                                <div>
                                    <div class="author-name" style="font-weight: 600; font-size: 0.9rem; color: #f3f4f6;">{{ $u['name'] }}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);">{{ $u['email'] }}</div>
                                </div>
                                <span class="author-id" style="font-size: 0.75rem; padding: 0.2rem 0.5rem; background: rgba(255,255,255,0.08); border-radius: 4px; color: var(--text-secondary);">ID: {{ $u['id'] }}</span>
                            </div>
                        @endforeach
                    @else
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">No authors registered yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
