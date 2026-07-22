<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Spartan Shop' }}</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- HTMX & Alpine.js -->
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        :root {
            --bg-color: #090d16;
            --card-bg: rgba(19, 25, 43, 0.65);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --accent: #10b981;
            --gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.15) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(16, 185, 129, 0.08) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--text-primary);
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(9, 13, 22, 0.8);
            backdrop-filter: blur(16px);
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
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        nav ul { display: flex; list-style: none; gap: 2rem; align-items: center; }
        nav a { color: var(--text-secondary); text-decoration: none; font-weight: 500; transition: color 0.3s; }
        nav a:hover { color: var(--text-primary); }

        .cart-badge {
            background: var(--primary);
            color: #fff;
            padding: 0.2rem 0.6rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        main { flex: 1; max-width: 1200px; width: 100%; margin: 0 auto; padding: 2.5rem 2rem; }

        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.37);
        }

        .grid-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .product-card {
            background: rgba(15, 21, 37, 0.7);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.3s, border-color 0.3s;
        }
        .product-card:hover { transform: translateY(-3px); border-color: rgba(99, 102, 241, 0.4); }

        .price { font-size: 1.3rem; font-weight: 700; color: var(--accent); margin: 0.5rem 0; }
        .btn {
            background: var(--gradient);
            color: #fff;
            border: none;
            padding: 0.7rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn:hover { opacity: 0.9; }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; }
        .alert-warning { background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); color: #fbbf24; }

        footer { border-top: 1px solid var(--border-color); padding: 2rem 0; text-align: center; color: var(--text-secondary); font-size: 0.85rem; margin-top: auto; }
    </style>
</head>
<body>
    <header>
        <div class="nav-container">
            <a href="/" class="logo">⚡ Spartan Shop</a>
            <nav>
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/catalog">Catalog</a></li>
                    <li><a href="/cart">Cart <span class="cart-badge">{{ count($_SESSION['cart'] ?? []) }}</span></a></li>
                    @role('admin')
                        <li><a href="/admin/products" style="color: #a855f7; font-weight: 700;">Admin Panel</a></li>
                    @endrole
                </ul>
            </nav>
        </div>
    </header>

    <main>
        @flash('success')
            <div class="alert alert-success">{{ $flashMsg }}</div>
        @endflash
        @flash('warning')
            <div class="alert alert-warning">{{ $flashMsg }}</div>
        @endflash

        @yield('content')
    </main>

    <footer>
        <p>&copy; {{ date('Y') }} Spartan Shop. Built on zero-dependency Spartan PHP Core.</p>
    </footer>
</body>
</html>
