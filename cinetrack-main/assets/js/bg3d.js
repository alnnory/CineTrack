// CineTrack animated 3D background: perspective starfield + floating wireframe solids.
// Reacts to mouse (camera orbit) and scroll (parallax + warp speed). Vanilla canvas, no libraries.
(() => {
    'use strict';

    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const small  = matchMedia('(max-width: 720px)').matches;

    // ---- Canvas setup ----
    const cv = document.createElement('canvas');
    cv.id = 'bg3d';
    cv.setAttribute('aria-hidden', 'true');
    cv.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;z-index:-1;pointer-events:none';
    document.body.prepend(cv);

    const ctx = cv.getContext('2d', { alpha: true });
    if (!ctx) return;

    // ---- Config ----
    const COLORS = ['139,92,246', '224,64,155', '79,140,255', '251,191,36'];
    const D = 900;        // camera distance
    const F = 900;        // focal length
    const SPAN = 3000;    // world wrap size
    const ZMIN = -800;
    const ZLEN = 2800;
    const MAX_DPR = 1.5;
    const MAX_DT = 0.05;

    let W = 0, H = 0, dpr = 1;
    let running = true;

    const rnd = (a, b) => a + Math.random() * (b - a);

    // ---- Stars ----
    const starCount = small ? 90 : 220;
    const stars = Array.from({ length: starCount }, () => ({
        x: rnd(-1500, 1500),
        y: rnd(-1500, 1500),
        z: rnd(ZMIN, ZMIN + ZLEN),
        c: COLORS[(Math.random() * 3) | 0],
        r: rnd(0.6, 1.8),
    }));

    // ---- Wireframe solids ----
    const CUBE = [
        [[-1,-1,-1],[1,-1,-1],[1,1,-1],[-1,1,-1],[-1,-1,1],[1,-1,1],[1,1,1],[-1,1,1]],
        [[0,1],[1,2],[2,3],[3,0],[4,5],[5,6],[6,7],[7,4],[0,4],[1,5],[2,6],[3,7]]
    ];
    const OCTA = [
        [[1,0,0],[-1,0,0],[0,1,0],[0,-1,0],[0,0,1],[0,0,-1]],
        [[0,2],[0,3],[0,4],[0,5],[1,2],[1,3],[1,4],[1,5],[2,4],[4,3],[3,5],[5,2]]
    ];
    const TETRA = [
        [[1,1,1],[-1,-1,1],[-1,1,-1],[1,-1,-1]],
        [[0,1],[0,2],[0,3],[1,2],[2,3],[3,1]]
    ];
    const SOLIDS = [CUBE, OCTA, TETRA];

    const shapeCount = small ? 5 : 9;
    const shapes = Array.from({ length: shapeCount }, (_, i) => ({
        s: SOLIDS[i % 3],
        size: rnd(50, 130),
        x: rnd(-900, 900),
        y: rnd(-1000, 1000),
        z: rnd(-200, 700),
        rx: rnd(0, 6.28),
        ry: rnd(0, 6.28),
        vx: rnd(-0.35, 0.35),
        vy: rnd(-0.45, 0.45),
        c: COLORS[i % 4],
        drift: rnd(0.5, 1.5),
        ph: rnd(0, 6.28),
    }));

    // ---- Interaction state ----
    let mx = 0, my = 0;              // mouse offset (-0.5 .. 0.5)
    let yaw = 0, pitch = 0;           // camera angle (smoothly follows mouse)
    let scrollY = window.scrollY || 0;
    let lastScroll = scrollY;
    let boost = 0;                    // warp speed when scrolling fast

    // ---- Resize ----
    function resize() {
        dpr = Math.min(window.devicePixelRatio || 1, MAX_DPR);
        W = window.innerWidth;
        H = window.innerHeight;
        cv.width = Math.floor(W * dpr);
        cv.height = Math.floor(H * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    window.addEventListener('resize', resize, { passive: true });
    resize();

    // ---- Input ----
    window.addEventListener('pointermove', e => {
        mx = e.clientX / W - 0.5;
        my = e.clientY / H - 0.5;
    }, { passive: true });

    window.addEventListener('scroll', () => {
        scrollY = window.scrollY || 0;
    }, { passive: true });

    // ---- Helpers ----
    const wrap = (v, range) => ((v + range / 2) % range + range) % range - range / 2;

    function project(x, y, z, cy, sy) {
        // Orbit around Y
        let X = x * cy.c - z * cy.s;
        let Z = x * cy.s + z * cy.c;
        let Y = y;
        // Orbit around X
        const Y2 = Y * sy.c - Z * sy.s;
        Z = Y * sy.s + Z * sy.c;
        const zc = Z + D;
        if (zc < 60) return null;
        const k = F / zc;
        return { x: W / 2 + X * k, y: H / 2 + Y2 * k, k, z: zc };
    }

    // ---- Frame ----
    function frame(t, dt) {
        ctx.clearRect(0, 0, W, H);
        ctx.globalCompositeOperation = 'lighter';

        // Smooth camera
        yaw   += (mx * 0.55 - yaw) * 0.04;
        pitch += (my * 0.35 - pitch) * 0.04;

        const cy = { c: Math.cos(yaw),  s: Math.sin(yaw)  };
        const sy = { c: Math.cos(pitch), s: Math.sin(pitch) };

        const camY = scrollY * 0.45;
        const speed = 55 + boost * 900;
        boost *= 0.92;

        // ---- Stars ----
        for (const s of stars) {
            s.z -= speed * dt;
            if (s.z < ZMIN) {
                s.z += ZLEN;
                s.x = rnd(-1500, 1500);
                s.y = rnd(-1500, 1500);
            }
            const p = project(s.x, wrap(s.y - camY, SPAN), s.z, cy, sy);
            if (!p) continue;

            const a = Math.min(1, (1 - (p.z - 60) / 3000) * 1.4) * 0.9;
            if (a <= 0) continue;

            ctx.fillStyle = `rgba(${s.c},${a})`;

            // Streak when scrolling fast
            if (boost > 0.05) {
                const q = project(s.x, wrap(s.y - camY, SPAN), s.z + speed * 0.03, cy, sy);
                if (q) {
                    ctx.strokeStyle = ctx.fillStyle;
                    ctx.lineWidth = Math.max(0.6, s.r * p.k * 1.6);
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                    ctx.lineTo(q.x, q.y);
                    ctx.stroke();
                    continue;
                }
            }

            ctx.beginPath();
            ctx.arc(p.x, p.y, Math.max(0.4, s.r * p.k * 1.5), 0, 6.283);
            ctx.fill();
        }

        // ---- Wireframe solids ----
        for (const o of shapes) {
            o.rx += o.vx * dt;
            o.ry += o.vy * dt;

            const bob = Math.sin(t * 0.0004 * o.drift + o.ph) * 60;
            const cx = Math.cos(o.rx), sx = Math.sin(o.rx);
            const cyy = Math.cos(o.ry), syy = Math.sin(o.ry);

            const pts = o.s[0].map(([vx, vy, vz]) => {
                let x = vx * o.size, y = vy * o.size, z = vz * o.size;
                // spin X
                const y1 = y * cx - z * sx;
                const z1 = y * sx + z * cx;
                // spin Y
                const x1 = x * cyy + z1 * syy;
                const z2 = -x * syy + z1 * cyy;
                return project(
                    o.x + x1,
                    wrap(o.y + y1 + bob - camY * 0.8, 2400),
                    o.z + z2,
                    cy, sy
                );
            });

            const near = pts.find(Boolean);
            if (!near) continue;

            const alpha = Math.min(0.55, Math.max(0.12, near.k * 0.5));
            ctx.lineWidth = Math.max(0.8, near.k * 1.6);

            for (const [i, j] of o.s[1]) {
                const a = pts[i], b = pts[j];
                if (!a || !b) continue;

                // Soft glow pass
                ctx.strokeStyle = `rgba(${o.c},${alpha * 0.35})`;
                ctx.lineWidth = Math.max(2.5, near.k * 5);
                ctx.beginPath();
                ctx.moveTo(a.x, a.y);
                ctx.lineTo(b.x, b.y);
                ctx.stroke();

                // Crisp pass
                ctx.strokeStyle = `rgba(${o.c},${alpha})`;
                ctx.lineWidth = Math.max(0.8, near.k * 1.4);
                ctx.beginPath();
                ctx.moveTo(a.x, a.y);
                ctx.lineTo(b.x, b.y);
                ctx.stroke();
            }
        }

        ctx.globalCompositeOperation = 'source-over';
    }

    // ---- Reduced motion: draw one frame and stop ----
    if (reduce) {
        frame(0, 0);
        return;
    }

    // ---- Loop with visibility pause ----
    let last = performance.now();
    let raf = 0;

    function loop(now) {
        if (!running) return;
        const dt = Math.min(MAX_DT, (now - last) / 1000);
        last = now;

        // Warp boost based on scroll speed
        const scrollDelta = Math.abs(scrollY - lastScroll);
        boost = Math.min(1, boost + Math.min(scrollDelta / 400, 0.6));
        lastScroll = scrollY;

        frame(now, dt);
        raf = requestAnimationFrame(loop);
    }

    raf = requestAnimationFrame(loop);

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            running = false;
            cancelAnimationFrame(raf);
        } else {
            running = true;
            last = performance.now();
            raf = requestAnimationFrame(loop);
        }
    });
})();