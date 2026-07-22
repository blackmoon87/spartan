<div style="background: rgba(15, 21, 37, 0.6); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.25rem; margin-bottom: 1rem;">
    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
        <span style="font-weight: 700; color: var(--primary);">{{ $comment['author_name'] }}</span>
        <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ $comment['created_at'] ?? 'Just now' }}</span>
    </div>
    <p style="color: var(--text-primary); font-size: 0.95rem; line-height: 1.5;">{{ $comment['content'] }}</p>
</div>
