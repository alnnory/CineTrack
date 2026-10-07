<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up — CineTrack</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Cinzel:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- CineTrack Design System -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Ambient Cosmic Glows -->
    <div class="ambient-glows">
        <div class="orb-1"></div>
        <div class="orb-2"></div>
    </div>

    <!-- Particle Canvas Layer -->
    <canvas id="particleCanvas"></canvas>

    <!-- Global Header / Navbar -->
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
            <li><a class="nav-link" href="welcome.php#features">Features</a></li>
        </ul>

        <div class="nav-actions">
            <a href="login.php" class="btn-secondary">Log In</a>
        </div>
    </nav>

    <!-- Signup Main Container -->
    <main class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-header">
                <h1 class="auth-brand-title">CINETRACK</h1>
                <p class="auth-brand-tagline">Your Movies. Your Story.</p>
                <h2 class="auth-main-heading">Start Your Movie Journey</h2>
                <p class="auth-sub-heading">Create your CineTrack account and keep your movies in one place.</p>
            </div>

            <div class="card-border-glow">
                <form class="auth-card" action="#" method="POST" onsubmit="return false;">
                    <div class="field-group">
                        <label class="field-label">Full Name</label>
                        <input type="text" name="full_name" class="input-box" placeholder="Enter your full name">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Username</label>
                        <input type="text" name="username" class="input-box" placeholder="Choose a username">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Email</label>
                        <input type="email" name="email" class="input-box" placeholder="Enter your email">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Password</label>
                        <input type="password" name="password" class="input-box" placeholder="Create a password">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="input-box" placeholder="Confirm your password">
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top: 0.5rem; width: 100%;">CREATE ACCOUNT</button>
                </form>
            </div>

            <p class="auth-prompt">
                Already have an account? <a href="login.php" class="auth-link">Log in</a>
            </p>
            <p class="footer-copyright">&copy; <?php echo date('Y'); ?> CineTrack. All rights reserved.</p>
        </div>
    </main>

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
