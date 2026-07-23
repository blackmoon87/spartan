<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->yieldContent('title'); ?></title>
    <!-- Tailwind CSS CDN Engine -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full text-slate-100 flex flex-col font-sans antialiased">
    <nav class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">⚡</span>
                <span class="font-bold text-amber-400 text-lg">Spartan + Tailwind CSS</span>
            </div>
            <div class="flex space-x-4 text-sm font-medium">
                <a href="/" class="hover:text-amber-400">Hub</a>
                <a href="/tailwind" class="text-amber-400">Tailwind</a>
                <a href="/openprops" class="hover:text-amber-400">Open Props</a>
                <a href="/vanilla" class="hover:text-amber-400">Vanilla</a>
            </div>
        </div>
    </nav>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php echo $this->yieldContent('content'); ?>
    </main>

    <footer class="border-t border-slate-800 bg-slate-900 py-4 text-center text-xs text-slate-400">
        Spartan Framework Multi-CSS Engine Support — Zero Dependencies Kernel
    </footer>
</body>
</html>
