<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->yieldContent('title'); ?></title>
    <!-- Open Props CSS System -->
    <link rel="stylesheet" href="https://unpkg.com/open-props">
    <link rel="stylesheet" href="https://unpkg.com/open-props/normalize.min.css">
    <style>
        body {
            background-color: var(--surface-1);
            color: var(--text-1);
            font-family: var(--font-sans);
            padding: var(--size-4);
            margin: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: var(--size-3);
            border-bottom: var(--border-size-1) solid var(--surface-3);
        }
        .card {
            background-color: var(--surface-2);
            padding: var(--size-4);
            border-radius: var(--radius-3);
            box-shadow: var(--shadow-3);
            margin-top: var(--size-4);
        }
        .nav-links a {
            margin-left: var(--size-3);
            color: var(--link);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>🎨 Spartan + Open Props CSS</h2>
        <div class="nav-links">
            <a href="/">Hub</a>
            <a href="/css/tailwind">Tailwind</a>
            <a href="/css/openprops">Open Props</a>
            <a href="/css/vanilla">Vanilla</a>
        </div>
    </div>

    <main>
        <?php echo $this->yieldContent('content'); ?>
    </main>
</body>
</html>
