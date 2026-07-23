<?php $this->extend('layouts/openprops'); ?>

<?php $this->sections[trim('title', "'\"")] = 'Open Props Integration — Spartan'; ?>
<?php $this->startSection('content'); ?>
<div class="card">
    <h1 style="color: var(--indigo-4);">Open Props CSS Custom Properties System</h1>
    <p>Using CSS variables directly compiled with Spartan Blade layout inheritance.</p>
</div>

<div class="card">
    <h3>Standard CSS Variables Evaluated</h3>
    <ul style="padding-left: var(--size-4);">
        <?php foreach($props as $name => $value): ?>
            <li style="margin-bottom: var(--size-2);">
                <code style="color: var(--pink-4);"><?php echo htmlspecialchars(($name) ?? '', ENT_QUOTES, 'UTF-8'); ?></code> : <?php echo htmlspecialchars(($value) ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php $this->endSection(); ?>
