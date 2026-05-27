<div style="margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
    @if (count($posts) > 0)
        @foreach ($posts as $post)
            <div style="padding: 0.8rem 0; border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                <a href="{{ url('/post/' . $post['id']) }}" style="color: var(--accent); text-decoration: none; font-weight: 600; font-size: 0.95rem; display: block; margin-bottom: 0.2rem;">
                    {{ $post['title'] }}
                </a>
                <span style="font-size: 0.85rem; color: var(--text-secondary);">
                    {{ substr($post['body'], 0, 80) }}...
                </span>
            </div>
        @endforeach
    @else
        <p style="color: var(--text-secondary); font-size: 0.9rem;">No matching posts found.</p>
    @endif
</div>
