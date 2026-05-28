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
    <!-- Custom Testing Assets -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
    <style>
        :root {
            --bg-primary: #0b0f19;
            --bg-secondary: #131a2b;
            --bg-tertiary: #1e2942;
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --accent: #3b82f6;
            --accent-gradient: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
            --card-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
            --border-color: rgba(255, 255, 255, 0.08);
            --transition: all 0.2s ease-in-out;
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
            max-width: 1100px;
            margin: 0 auto;
            padding: 1.5rem 1.5rem 3rem 1.5rem;
        }

        /* Top Navigation Bar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 2.5rem;
        }

        .nav-logo {
            font-weight: 800;
            font-size: 1.4rem;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        /* Success/Error Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: 500;
            animation: fadeIn 0.25s ease;
        }
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        /* Grid Layout */
        .layout-grid {
            display: grid;
            grid-template-columns: 7fr 5fr;
            gap: 2rem;
        }

        @media (max-width: 900px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card styles */
        .card {
            background-color: var(--bg-secondary);
            border-radius: 12px;
            padding: 1.75rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }
        .card:hover {
            border-color: rgba(255, 255, 255, 0.15);
        }

        /* Inputs & Buttons */
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select,
        textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.95rem;
            margin-bottom: 1.2rem;
            transition: var(--transition);
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.15);
        }

        button, .btn {
            display: inline-block;
            width: 100%;
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            text-decoration: none;
        }
        button:hover, .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        /* Post Items */
        .post-item {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 0;
            margin-bottom: 0.5rem;
        }
        .post-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .post-item:first-child {
            padding-top: 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Main Top Bar -->
        <nav class="navbar">
            <a href="{{ url('/') }}" class="nav-logo">Spartan Blogger <span style="font-size: 0.75rem; font-weight:400; color: var(--text-secondary); margin-left: 0.5rem; text-transform: uppercase; letter-spacing: 1px;">SPARTAN + BLADE + HTMX</span></a>
            <div style="display: flex; align-items: center; gap: 1.25rem;">
                @if (\App\Core\Application::$app->session->get('user_id'))
                    <span style="font-size: 0.9rem; color: var(--text-secondary);">
                        Logged in as <strong style="color: var(--text-primary);">{{ \App\Core\Application::$app->session->get('user_name') }}</strong>
                    </span>
                    <form action="{{ url('/logout') }}" method="POST" style="margin: 0; display: inline-flex;">
                        @csrf
                        <button type="submit" style="width: auto; padding: 0.4rem 0.85rem; font-size: 0.8rem; background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 6px; margin: 0; cursor: pointer; transition: var(--transition);">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ url('/login') }}" style="color: #60a5fa; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: var(--transition);">Log In</a>
                    <a href="{{ url('/register') }}" style="color: var(--text-primary); text-decoration: none; font-size: 0.9rem; font-weight: 600; padding: 0.4rem 0.85rem; background: rgba(255, 255, 255, 0.08); border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.12); transition: var(--transition);">Sign Up</a>
                @endif
            </div>
        </nav>

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
