<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spartan Framework Showcase</title>
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-base: #09090b;
            --bg-surface: rgba(24, 24, 27, 0.65);
            --bg-card: rgba(39, 39, 42, 0.45);
            --border-color: rgba(63, 63, 70, 0.4);
            --border-hover: rgba(113, 113, 122, 0.6);
            
            --color-text: #fafafa;
            --color-muted: #a1a1aa;
            
            --primary: #8b5cf6;
            --primary-glow: rgba(139, 92, 246, 0.35);
            --primary-hover: #7c3aed;
            
            --success: #10b981;
            --success-glow: rgba(16, 185, 129, 0.2);
            --error: #ef4444;
            --error-glow: rgba(239, 68, 68, 0.2);
            
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 8px;
            
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-base);
            color: var(--color-text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            line-height: 1.6;
            padding: 2rem 1rem;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            overflow-x: hidden;
            position: relative;
        }

        /* Decorative Background Glows */
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

        body::after {
            content: '';
            position: absolute;
            width: 350px;
            height: 350px;
            bottom: -50px;
            right: -50px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, transparent 70%);
            z-index: -1;
            pointer-events: none;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header block */
        header {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInDown 0.6s ease-out;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.05em;
            background: linear-gradient(135deg, #a78bfa 0%, var(--primary) 50%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: var(--color-muted);
            font-size: 1.1rem;
            font-weight: 400;
        }

        .badge {
            background: rgba(139, 92, 246, 0.15);
            color: #c084fc;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid rgba(139, 92, 246, 0.3);
            margin-left: 0.5rem;
            vertical-align: middle;
        }

        /* Content Injection */
        main {
            animation: fadeInUp 0.8s ease-out;
        }

        /* Alert notifications */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
            border: 1px solid transparent;
            backdrop-filter: blur(8px);
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideIn 0.3s ease-out;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.3);
            color: #34d399;
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        .alert .close-btn {
            cursor: pointer;
            background: none;
            border: none;
            color: inherit;
            font-weight: 700;
            font-size: 1.1rem;
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-15px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    <!-- HTMX CDN -->
    <script src="https://unpkg.com/htmx.org@1.9.12" integrity="sha384-ujb1lZYygJmzgSwoxRggbCHcjc0rB2XoQrxeTUQyRjrOnlCoYta87iKBWq3EsdM2" crossorigin="anonymous"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://unpkg.com/alpinejs@3.13.10/dist/cdn.min.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1 class="logo">SPARTAN<span class="badge">v1.1</span></h1>
            <p class="subtitle">Self-Contained Sample Application &amp; Integration Playground</p>
        </header>

        <!-- Dynamic Flash messages -->
        <?php if ($success = \App\Core\Application::$app->session->getFlash('success_message')): ?>
            <div class="alert alert-success">
                <span>✓ <?= htmlspecialchars($success) ?></span>
                <button class="close-btn" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endif; ?>

        <?php if ($error = \App\Core\Application::$app->session->getFlash('error_message')): ?>
            <div class="alert alert-danger">
                <span>✗ <?= htmlspecialchars($error) ?></span>
                <button class="close-btn" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endif; ?>

        <main>
            {{content}}
        </main>
    </div>
</body>
</html>
