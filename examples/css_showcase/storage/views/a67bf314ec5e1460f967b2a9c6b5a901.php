<?php $this->extend('layouts/vanilla'); ?>

<?php $this->sections[trim('title', "'\"")] = 'Interactive Animations, Sliders & Motion Showcase — Spartan'; ?>
<?php $this->startSection('content'); ?>
<style>
    /* Keyframe Animations */
    @keyframes glowRotate {
        0% { filter: hue-rotate(0deg); }
        100% { filter: hue-rotate(360deg); }
    }

    @keyframes pulseGlow {
        0%, 100% { opacity: 0.4; transform: scale(1); }
        50% { opacity: 0.8; transform: scale(1.03); }
    }

    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }

    /* Slider Component */
    .slider-container {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        border: 1px solid var(--border);
        margin-bottom: 2rem;
    }
    .slider-track {
        display: flex;
        transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        width: 300%;
    }
    .slide-item {
        width: 33.333%;
        padding: 3.5rem 2.5rem;
        min-height: 240px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
    }
    .slider-dots {
        position: absolute;
        bottom: 1.25rem;
        right: 1.5rem;
        display: flex;
        gap: 0.5rem;
        z-index: 10;
    }
    .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: rgba(255,255,255,0.3);
        cursor: pointer;
        transition: all 0.3s;
    }
    .dot.active {
        background: #fff;
        width: 24px;
        border-radius: 99px;
    }
    .slider-nav {
        position: absolute;
        bottom: 1.25rem;
        left: 1.5rem;
        display: flex;
        gap: 0.5rem;
        z-index: 10;
    }
    .nav-btn {
        background: rgba(0,0,0,0.4);
        backdrop-filter: blur(8px);
        border: 1px solid var(--border);
        color: #fff;
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: background 0.2s;
    }
    .nav-btn:hover {
        background: rgba(255,255,255,0.2);
    }

    /* Media Gallery Zoom Cards */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }
    .gallery-card {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        aspect-ratio: 16/9;
        border: 1px solid var(--border);
        cursor: pointer;
    }
    .gallery-card .card-bg {
        width: 100%;
        height: 100%;
        transition: transform 0.5s ease;
    }
    .gallery-card:hover .card-bg {
        transform: scale(1.08);
    }
    .gallery-card .overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(0deg, rgba(9,13,22,0.9), transparent 70%);
        padding: 1rem;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }

    /* Shimmer Skeleton Card */
    .shimmer-box {
        background: linear-gradient(90deg, rgba(255,255,255,0.03) 25%, rgba(255,255,255,0.08) 50%, rgba(255,255,255,0.03) 75%);
        background-size: 200% 100%;
        animation: shimmer 1.8s infinite;
        border-radius: 6px;
    }
</style>

<!-- Page Header -->
<div class="glass-card" style="margin-bottom: 1.5rem;">
    <span style="background: rgba(34,211,238,0.15); color: var(--accent); font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.75rem; border-radius: 999px; border: 1px solid rgba(34,211,238,0.3);">
        Motion Design & Media Suite
    </span>
    <h1 style="font-size: 2rem; font-weight: 800; margin: 0.75rem 0 0.25rem 0;">
        Interactive Sliders, Carousels & CSS Keyframe Motion
    </h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">
        Demonstrates hardware-accelerated CSS animations and interactive Blade UI components rendered with zero external JS frameworks.
    </p>
</div>

<!-- Interactive Image & Content Slider / Carousel -->
<div class="slider-container">
    <div class="slider-track" id="sliderTrack">
        <?php foreach($slides as $index => $slide): ?>
        <div class="slide-item" style="background: <?php echo htmlspecialchars(($slide['gradient']) ?? '', ENT_QUOTES, 'UTF-8'); ?>;">
            <span style="background: rgba(0,0,0,0.3); color: #fff; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 4px; width: max-content; margin-bottom: 0.75rem;">
                <?php echo htmlspecialchars(($slide['tag']) ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <h2 style="font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 0.4rem; text-shadow: 0 2px 10px rgba(0,0,0,0.3);">
                <?php echo htmlspecialchars(($slide['title']) ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </h2>
            <p style="color: rgba(255,255,255,0.9); font-size: 0.95rem;">
                <?php echo htmlspecialchars(($slide['tagline']) ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="slider-nav">
        <button class="nav-btn" onclick="moveSlide(-1)">← Prev</button>
        <button class="nav-btn" onclick="moveSlide(1)">Next →</button>
    </div>

    <div class="slider-dots">
        <span class="dot active" onclick="goToSlide(0)"></span>
        <span class="dot" onclick="goToSlide(1)"></span>
        <span class="dot" onclick="goToSlide(2)"></span>
    </div>
</div>

<!-- Responsive Aspect-Ratio Gallery with Hover Zoom -->
<h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: #fff;">📸 Aspect-Ratio Media Gallery (Hover Zoom)</h2>
<div class="gallery-grid">
    <?php foreach($gallery as $item): ?>
    <div class="gallery-card">
        <div class="card-bg" style="background: linear-gradient(135deg, <?php echo htmlspecialchars(($item['color']) ?? '', ENT_QUOTES, 'UTF-8'); ?>, #090d16);"></div>
        <div class="overlay">
            <span style="font-size: 0.7rem; color: var(--accent); font-weight: 700;"><?php echo htmlspecialchars(($item['category']) ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
            <h4 style="font-size: 0.95rem; font-weight: 700; color: #fff; margin-top: 0.2rem;"><?php echo htmlspecialchars(($item['title']) ?? '', ENT_QUOTES, 'UTF-8'); ?></h4>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Animated Skeleton Shimmer Cards -->
<div class="glass-card">
    <h3 style="font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 1rem;">✨ CSS Shimmer Keyframe Skeleton Loader</h3>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px;">
            <div class="shimmer-box" style="height: 120px; margin-bottom: 0.75rem;"></div>
            <div class="shimmer-box" style="height: 16px; width: 80%; margin-bottom: 0.5rem;"></div>
            <div class="shimmer-box" style="height: 12px; width: 50%;"></div>
        </div>
        <div style="flex: 1; min-width: 200px;">
            <div class="shimmer-box" style="height: 120px; margin-bottom: 0.75rem;"></div>
            <div class="shimmer-box" style="height: 16px; width: 75%; margin-bottom: 0.5rem;"></div>
            <div class="shimmer-box" style="height: 12px; width: 45%;"></div>
        </div>
    </div>
</div>

<script>
    let currentSlide = 0;
    const totalSlides = 3;

    function goToSlide(index) {
        currentSlide = (index + totalSlides) % totalSlides;
        document.getElementById('sliderTrack').style.transform = `translateX(-${currentSlide * 33.333}%)`;
        
        const dots = document.querySelectorAll('.dot');
        dots.forEach((d, i) => {
            if (i === currentSlide) d.classList.add('active');
            else d.classList.remove('active');
        });
    }

    function moveSlide(direction) {
        goToSlide(currentSlide + direction);
    }

    // Auto-advance carousel every 4 seconds
    setInterval(() => {
        moveSlide(1);
    }, 4000);
</script>
<?php $this->endSection(); ?>
