<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->yieldContent('title'); ?></title>
    <style>
        :root {
            --bg: #090d16;
            --card-bg: rgba(22, 31, 48, 0.7);
            --border: rgba(255, 255, 255, 0.1);
            --primary: #6366f1;
            --accent: #22d3ee;
            --text: #f8fafc;
            --text-muted: #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
        }
        .navbar .logo { font-weight: 700; color: var(--accent); }
        .navbar .nav-links a { color: var(--text-muted); text-decoration: none; margin-left: 1.5rem; font-size: 0.9rem; }
        .navbar .nav-links a:hover { color: var(--text); }
        .container { max-width: 1000px; width: 100%; margin: 2rem auto; padding: 0 1.5rem; flex: 1; }
        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">💎 Spartan Glassmorphism</div>
        <div class="nav-links">
            <a href="/">Hub</a>
            <a href="/tailwind">Tailwind</a>
            <a href="/openprops">Open Props</a>
            <a href="/vanilla">Vanilla</a>
        </div>
    </nav>

    <div class="container">
        <?php echo $this->yieldContent('content'); ?>
    </div>
</body>
</html>
