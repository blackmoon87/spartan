<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'PHP MVC' ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(20, 26, 46, 0.6);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --accent: #10b981;
            --accent-glow: rgba(16, 185, 129, 0.4);
            --gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.15) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(16, 185, 129, 0.05) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--text-primary);
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Glassmorphism Header */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(11, 15, 25, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo::before {
            content: '';
            display: inline-block;
            width: 12px;
            height: 12px;
            background: var(--gradient);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--primary-glow);
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease, text-shadow 0.3s ease;
            font-size: 0.95rem;
        }

        nav a:hover, nav a.active {
            color: var(--text-primary);
            text-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
        }

        /* Main Content wrapper */
        main {
            flex: 1;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 3rem 2rem;
            animation: fadeIn 0.8s ease-out;
        }

        /* Glassmorphism Card style */
        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-2px);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 12px 40px 0 rgba(99, 102, 241, 0.1);
        }

        /* Form styling */
        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 0.85rem 1rem;
            background: rgba(11, 15, 25, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        button {
            background: var(--gradient);
            color: #fff;
            border: none;
            padding: 0.85rem 1.75rem;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px var(--primary-glow);
        }

        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.6);
        }

        button:active {
            transform: translateY(1px);
        }

        /* Status Pills */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.03);
        }

        .status-pill.success {
            border-color: rgba(16, 185, 129, 0.2);
            color: var(--accent);
            background: rgba(16, 185, 129, 0.05);
        }

        .status-pill.success::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--accent-glow);
        }

        /* Footer */
        footer {
            border-top: 1px solid var(--border-color);
            padding: 2rem 0;
            background: rgba(7, 10, 17, 0.8);
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-top: auto;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #fff 0%, #9ca3af 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .lead {
            font-size: 1.15rem;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        /* Code Block Styling */
        pre {
            background: #060913;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.25rem;
            overflow-x: auto;
            color: #38bdf8;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.875rem;
            margin: 1.5rem 0;
        }

        code {
            font-family: 'Courier New', Courier, monospace;
            color: #ec4899;
            background: rgba(236, 72, 153, 0.1);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <header>
        <div class="nav-container">
            <a href="/" class="logo">PHP.mvc</a>
            <nav>
                <ul>
                    <li><a href="/" class="<?= ($title ?? '') === 'Home Page' ? 'active' : '' ?>">Home</a></li>
                    <li><a href="/contact" class="<?= ($title ?? '') === 'Contact Us' ? 'active' : '' ?>">Contact</a></li>
                    <li><a href="/profile/demo-user" class="<?= ($title ?? '') === 'User Profile' ? 'active' : '' ?>">Sample Profile</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        {{content}}
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> PHP MVC. Handcrafted for AI IDE Integration.</p>
        </div>
    </footer>
</body>
</html>
