<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Blogger Showcase - Spartan Framework' }}</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <style>
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent: #38bdf8;
            --accent-gradient: linear-gradient(135deg, #38bdf8 0%, #818cf8 100%);
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            --border-color: #334155;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        header {
            text-align: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
        }

        header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Success/Error Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-weight: 500;
            animation: fadeIn 0.3s ease;
        }
        .alert-success {
            background-color: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
        }
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        /* Grid Layout */
        .layout-grid {
            display: grid;
            grid-template-columns: 7fr 5fr;
            gap: 2.5rem;
        }

        @media (max-width: 968px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card styles */
        .card {
            background-color: var(--bg-secondary);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }
        .card:hover {
            transform: translateY(-2px);
            border-color: #475569;
        }

        .card h2, .card h3 {
            font-weight: 700;
            margin-bottom: 1.2rem;
            color: var(--text-primary);
        }

        /* Inputs & Buttons */
        input[type="text"],
        input[type="email"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            background-color: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 1rem;
            margin-bottom: 1.2rem;
            transition: var(--transition);
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
        }

        label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        button, .btn {
            display: inline-block;
            width: 100%;
            padding: 0.8rem 1.5rem;
            background: var(--accent-gradient);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            text-decoration: none;
        }
        button:hover, .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Post Items */
        .post-item {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 0;
        }
        .post-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .post-item:first-child {
            padding-top: 0;
        }
        .post-item h3 a {
            color: var(--text-primary);
            text-decoration: none;
            font-size: 1.3rem;
            font-weight: 700;
            transition: var(--transition);
        }
        .post-item h3 a:hover {
            color: var(--accent);
        }
        .post-item p {
            color: var(--text-secondary);
            margin: 0.5rem 0 1rem 0;
        }
        .post-meta {
            display: flex;
            gap: 1rem;
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Author list sidebar */
        .author-badge {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--bg-primary);
            border: 1px solid var(--border-color);
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.8rem;
        }
        .author-badge .author-name {
            font-weight: 600;
        }
        .author-badge .author-id {
            background-color: var(--bg-tertiary);
            color: var(--accent);
            font-size: 0.8rem;
            font-weight: 700;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Blogger Showcase - Spartan</h1>
            <p>SPARTAN + BLADE + HTMX</p>
        </header>

        <!-- Flash messages -->
        @if ($success = \App\Core\Application::$app->session->getFlash('success_message'))
            <div class="alert alert-success">
                {{ $success }}
            </div>
        @endif

        <main>
            @yield('content')
        </main>
    </div>
</body>
</html>
