@extends('layouts.blog')

@section('content')
<article class="glass-card" style="margin-bottom: 2.5rem; padding: 3rem 2.5rem;">
    <h1 style="font-size: 2.4rem; font-weight: 800; margin-bottom: 1rem; line-height: 1.2;">{{ $post->title }}</h1>
    <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
        <span>Published {{ $post->created_at }}</span> &bull; <span>👁️ {{ $post->views }} views</span>
    </div>

    <div style="font-size: 1.1rem; line-height: 1.8; color: #e2e8f0; margin-bottom: 2rem;">
        <p style="font-size: 1.2rem; color: #94a3b8; margin-bottom: 1.5rem; font-weight: 500;">{{ $post->excerpt }}</p>
        <div style="white-space: pre-line;">{{ $post->content }}</div>
    </div>
</article>

<!-- Comments Section -->
<section class="glass-card">
    <h3 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Discussion & Comments ({{ count($comments) }})</h3>

    <div id="comments-container">
        @foreach($comments as $comment)
            @include('blog.partials.comment_item', ['comment' => $comment])
        @endforeach
    </div>

    <!-- Post Comment Form with HTMX dynamic append -->
    <div style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
        <h4 style="margin-bottom: 1rem;">Leave a Comment</h4>
        <form action="/comment/store" 
              method="POST" 
              hx-post="/comment/store" 
              hx-target="#comments-container" 
              hx-swap="beforeend">
            @csrf
            <input type="hidden" name="post_id" value="{{ $post->id }}">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <input type="text" name="author_name" placeholder="Your Name" required style="padding: 0.75rem; background: rgba(9,13,22,0.8); border: 1px solid var(--border-color); border-radius: 8px; color: #fff; outline: none;">
                <input type="email" name="author_email" placeholder="Your Email Address" required style="padding: 0.75rem; background: rgba(9,13,22,0.8); border: 1px solid var(--border-color); border-radius: 8px; color: #fff; outline: none;">
            </div>

            <div style="margin-bottom: 1rem;">
                <textarea name="content" rows="3" placeholder="Share your thoughts..." required style="width: 100%; padding: 0.75rem; background: rgba(9,13,22,0.8); border: 1px solid var(--border-color); border-radius: 8px; color: #fff; outline: none;"></textarea>
            </div>

            <button type="submit" class="btn">Post Comment</button>
        </form>
    </div>
</section>
@endsection
