<?php $this->extend('layouts/app'); ?>

<?php $this->startSection('title', 'Login — TaskForge'); ?>

<?php $this->startSection('content'); ?>
<div style="max-width: 420px; margin: 60px auto;">
    <div class="card">
        <h2 style="text-align:center; margin-bottom: 24px;">🔐 Sign In</h2>
        <form method="POST" action="/login">
            <?php echo $this->csrfToken(); ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="you@taskforge.dev" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" style="width:100%; margin-top: 8px;">Sign In</button>
        </form>
        <p style="text-align:center; margin-top:16px; font-size:.8rem; color:var(--text-muted);">
            Demo: alexei@taskforge.dev / password
        </p>
    </div>
</div>
<?php $this->endSection(); ?>
