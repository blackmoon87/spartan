<div class="glass-card">
    <h1 class="hero-title">Profile View</h1>
    <p class="lead">This page is loaded dynamically using a regex match on the URL. Notice how the user identifier changes as you modify the path in the URL browser bar.</p>
    
    <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); padding: 1.5rem; border-radius: 12px; margin-top: 1.5rem;">
        <p style="color: var(--text-secondary); margin-bottom: 0.5rem; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">Url Route Variable Capture</p>
        <p style="font-size: 1.5rem; font-weight: 600; color: var(--primary);">User ID: <span style="color: #fff;"><?= htmlspecialchars($userId) ?></span></p>
    </div>

    <div style="margin-top: 2.5rem; display: flex; gap: 1rem;">
        <a href="/" style="text-decoration: none;"><button type="button" style="background: rgba(255, 255, 255, 0.05); box-shadow: none; border: 1px solid var(--border-color);">&larr; Back Home</button></a>
        <a href="/profile/some-other-uuid" style="text-decoration: none;"><button type="button">Load Another User Profile</button></a>
    </div>
</div>
