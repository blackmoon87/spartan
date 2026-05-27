<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spartan Framework - Blade & HTMX Demo</title>
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- HTMX CDN -->
    <script src="https://unpkg.com/htmx.org@1.9.12" integrity="sha384-ujb1lZYygJmzgSwoxRggbCHcjc0rB2XoQrxeTUQyRjrOnlCoYta87iKBWq3EsdM2" crossorigin="anonymous"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://unpkg.com/alpinejs@3.13.10/dist/cdn.min.js"></script>

    <style>
        :root {
            --bg-base: #0f172a;
            --bg-surface: rgba(30, 41, 59, 0.7);
            --bg-card: rgba(51, 65, 85, 0.5);
            --border-color: rgba(71, 85, 105, 0.4);
            --border-hover: rgba(148, 163, 184, 0.6);
            --color-text: #f8fafc;
            --color-muted: #94a3b8;
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.2);
            --primary-hover: #4f46e5;
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 8px;
            --transition-smooth: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-base);
            color: var(--color-text);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            line-height: 1.6;
            padding: 2rem 1rem;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            top: -150px;
            left: -100px;
            background: radial-gradient(circle, var(--primary-glow) 0%, transparent 70%);
            z-index: -1;
            pointer-events: none;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.05em;
            background: linear-gradient(135deg, #818cf8 0%, var(--primary) 50%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: var(--color-muted);
            font-size: 1rem;
        }

        main {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 0.85rem 1rem;
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--color-text);
            font-size: 1rem;
            font-family: inherit;
            transition: var(--transition-smooth);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        /* Results table/list */
        .results-list {
            margin-top: 1.5rem;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .result-item {
            padding: 1rem;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition-smooth);
        }

        .result-item:hover {
            border-color: var(--border-hover);
            transform: translateY(-2px);
        }

        .badge-active {
            background-color: rgba(16, 185, 129, 0.15);
            color: #34d399;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Alpine Demo Widget */
        .alpine-widget {
            margin-top: 2rem;
            padding: 1.25rem;
            background: rgba(99, 102, 241, 0.05);
            border: 1px dashed rgba(99, 102, 241, 0.3);
            border-radius: var(--radius-md);
            text-align: center;
        }

        .btn-action {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            font-family: inherit;
            transition: var(--transition-smooth);
            margin-top: 0.5rem;
        }

        .btn-action:hover {
            background-color: var(--primary-hover);
        }

        .btn-back {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: var(--color-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition-smooth);
        }

        .btn-back:hover {
            color: var(--color-text);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1 class="logo">SPARTAN + BLADE + HTMX</h1>
            <p class="subtitle">Interactive Real-time Reactive Dashboard</p>
        </header>

        <a href="/" class="btn-back">← Back to Classic Dashboard</a>

        <main>
            @yield('content')
        </main>
    </div>
</body>
</html>
