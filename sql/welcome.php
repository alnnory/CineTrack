<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineTrack — Your Movies. Your Story.</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Cinzel:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- CineTrack Design System -->
    <link rel="stylesheet" href="css/style.css">

    <style>
        .hero-section {
            position: relative;
            z-index: 2;
            padding: 170px 4rem 80px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .hero-tagline {
            font-size: 0.95rem;
            color: var(--gold);
            letter-spacing: 3px;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 0 10px rgba(245, 197, 66, 0.3);
        }

        .hero-title {
            font-size: 3.75rem;
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 1.25rem;
            max-width: 850px;
            background: linear-gradient(180deg, #FFFFFF 30%, var(--text-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-sub {
            font-size: 1.15rem;
            color: var(--text-secondary);
            max-width: 620px;
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }

        .hero-ctas {
            display: flex;
            gap: 1.25rem;
        }

        .highlights-grid {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            padding: 0 4rem 80px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .highlight-card {
            background: rgba(33, 20, 50, 0.6);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 1.75rem;
            text-align: center;
            backdrop-filter: blur(12px);
        }

        .highlight-title {
            color: var(--gold);
            font-size: 0.9rem;
            letter-spacing: 2px;
            font-weight: 700;
            margin-bottom: 0.4rem;
        }

        .highlight-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .hero-section { padding: 140px 1.5rem 60px; }
            .hero-title { font-size: 2.5rem; }
            .highlights-grid { grid-template-columns: repeat(2, 1fr); padding: 0 1.5rem 60px; }
        }
    </style>
</head>
<body>

    <!-- Ambient Cosmic Glows -->
    <div class="ambient-glows">
        <div class="orb-1"></div>
        <div class="orb-2"></div>
    </div>

    <!-- Background Canvas Layer -->
    <canvas id="particleCanvas"></canvas>

    <!-- Global Header -->
    <nav class="navbar">
        <a href="welcome.php" class="logo-group">
            <svg class="logo-svg" viewBox="0 0 64 64" fill="none">
                <circle cx="32" cy="32" r="30" fill="url(#nav-bg)" stroke="url(#nav-border)" stroke-width="2"/>
                <circle cx="32" cy="32" r="22" stroke="#A855F7" stroke-width="2" stroke-dasharray="4 3" opacity="0.6"/>
                <circle cx="32" cy="16" r="2.5" fill="#F5C542"/>
                <circle cx="32" cy="48" r="2.5" fill="#F5C542"/>
                <path d="M26 21L43 32L26 43V21Z" fill="url(#nav-play)"/>
                <defs>
                    <linearGradient id="nav-bg" x1="0" y1="0" x2="64" y2="64"><stop offset="0%" stop-color="#211432"/><stop offset="100%" stop-color="#0B0614"/></linearGradient>
                    <linearGradient id="nav-border" x1="0" y1="0" x2="64" y2="64"><stop offset="0%" stop-color="#7B2CDB"/><stop offset="100%" stop-color="#EC168C"/></linearGradient>
                    <linearGradient id="nav-play" x1="26" y1="21" x2="43" y2="43"><stop offset="0%" stop-color="#FFF"/><stop offset="100%" stop-color="#7B2CDB"/></linearGradient>
                </defs>
            </svg>
            <span class="logo-text">CINETRACK</span>
        </a>

        <ul class="nav-links">
            <li><a class="nav-link" href="welcome.php">Home</a></li>
            <li><a class="nav-link" href="#features">Features</a></li>
        </ul>

        <div class="nav-actions">
            <a href="login.php" class="btn-secondary">Log In</a>
            <a href="signup.php" class="btn-primary">Sign Up</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <main class="hero-section">
        <p class="hero-tagline">Your Personal Movie Universe</p>
        <h1 class="hero-title">Your Movies. Your Story.</h1>
        <p class="hero-sub">CineTrack is a personal movie watchlist and tracking platform that helps you organize, discover, rate, review, and keep track of the movies you love.</p>
        <div class="hero-ctas">
            <a href="signup.php" class="btn-primary" style="padding: 0.9rem 2.25rem;">GET STARTED</a>
            <a href="login.php" class="btn-secondary" style="padding: 0.9rem 2rem;">EXPLORE MOVIES</a>
        </div>
    </main>

    <!-- Features Section -->
    <div id="features" class="highlights-grid">
        <div class="highlight-card">
            <p class="highlight-title">WATCH</p>
            <p class="highlight-desc">Track what you want to watch.</p>
        </div>
        <div class="highlight-card">
            <p class="highlight-title">RATE</p>
            <p class="highlight-desc">Give movies your personal rating.</p>
        </div>
        <div class="highlight-card">
            <p class="highlight-title">REVIEW</p>
            <p class="highlight-desc">Write and save your thoughts.</p>
        </div>
        <div class="highlight-card">
            <p class="highlight-title">REMEMBER</p>
            <p class="highlight-desc">Keep your history organized.</p>
        </div>
    </div>

    <!-- Particle Background Script -->
    <script>
        const canvas = document.getElementById('particleCanvas');
        const ctx = canvas.getContext('2d');
        let width, height, particles = [];

        function resize() {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resize);
        resize();

        class Particle {
            constructor() { this.reset(); }
            reset() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.size = Math.random() * 2 + 0.5;
                this.speedX = (Math.random() - 0.5) * 0.25;
                this.speedY = (Math.random() - 0.5) * 0.25 - 0.15;
                this.opacity = Math.random() * 0.5 + 0.1;
                this.color = Math.random() > 0.4 ? '#A855F7' : (Math.random() > 0.5 ? '#EC168C' : '#F5C542');
            }
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.x < 0 || this.x > width || this.y < 0 || this.y > height) this.reset();
            }
            draw() {
                ctx.save();
                ctx.globalAlpha = this.opacity;
                ctx.fillStyle = this.color;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
                ctx.restore();
            }
        }

        for (let i = 0; i < 45; i++) particles.push(new Particle());

        function animate() {
            ctx.clearRect(0, 0, width, height);
            particles.forEach(p => { p.update(); p.draw(); });
            requestAnimationFrame(animate);
        }
        animate();
    </script>
</body>
</html>
