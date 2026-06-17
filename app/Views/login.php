<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login // ATLAS</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:ital,wght@0,100..900;1,100..900&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: 'var(--background)',
                        foreground: 'var(--foreground)',
                        card: 'var(--card)',
                        'card-foreground': 'var(--card-foreground)',
                        primary: 'var(--primary)',
                        'primary-foreground': 'var(--primary-foreground)',
                        border: 'var(--border)',
                        input: 'var(--input)',
                        ring: 'var(--ring)',
                        ink: 'var(--ink)',
                        paper: 'var(--paper)',
                    },
                    fontFamily: {
                        sans: ['"Inter"', 'system-ui', 'sans-serif'],
                        display: ['"Archivo"', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* ── Login-specific: DOF Grid Background ── */
        body.login-page {
            background: var(--background);
        }
        .login-grid-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        /* The SVG grid fills the viewport */
        .login-grid-bg svg {
            width: 100%;
            height: 100%;
            animation: gridBreath 6s ease-in-out infinite;
        }
        /* Radial + linear vignette over the grid — fades edges & top */
        .login-grid-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background:
                /* top fade to hide where VP is above center */
                linear-gradient(to bottom,
                    var(--background) 0%,
                    transparent 28%,
                    transparent 72%,
                    var(--background) 100%
                ),
                /* side vignette */
                radial-gradient(
                    ellipse 90% 80% at 50% 80%,
                    transparent 0%,
                    var(--background) 100%
                );
        }
        /* Soft glow at vanishing point */
        .login-grid-glow {
            position: absolute;
            left: 50%;
            top: 46%;
            transform: translate(-50%, -50%);
            width: 600px;
            height: 220px;
            border-radius: 50%;
            background: radial-gradient(
                ellipse at center,
                color-mix(in srgb, var(--ink) 4%, transparent) 0%,
                transparent 70%
            );
            filter: blur(40px);
            pointer-events: none;
            animation: glowPulse 6s ease-in-out infinite;
        }
        /* The login card sits above the grid */
        .login-card {
            position: relative;
            z-index: 10;
        }


        /* ── Keyframes ── */

        /* Grid breathes: dims slightly then brightens, offset so it leads the wave */
        @keyframes gridBreath {
            0%   { opacity: 0.70; }
            15%  { opacity: 1.00; }
            50%  { opacity: 0.80; }
            85%  { opacity: 1.00; }
            100% { opacity: 0.70; }
        }

        /* Glow expands and contracts in sync with breath */
        @keyframes glowPulse {
            0%   { transform: translate(-50%, -50%) scale(0.85); opacity: 0.6; }
            30%  { transform: translate(-50%, -50%) scale(1.20); opacity: 1.0; }
            60%  { transform: translate(-50%, -50%) scale(0.95); opacity: 0.7; }
            100% { transform: translate(-50%, -50%) scale(0.85); opacity: 0.6; }
        }

    </style>
</head>
<body class="login-page min-h-screen flex items-center justify-center p-6 text-foreground relative overflow-hidden">

    <!-- DOF Grid Background -->
    <div class="login-grid-bg">
        <svg viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <!-- DOF blur filter: blurs lines near vanishing point -->
                <filter id="dofBlur" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="1.2"/>
                </filter>
                <filter id="dofBlurMid" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="0.5"/>
                </filter>

                <!-- Radial fade mask: visible in lower/center, invisible toward edges and top VP -->
                <radialGradient id="gridFade" cx="50%" cy="100%" r="85%" gradientUnits="userSpaceOnUse"
                    gradientTransform="matrix(1,0,0,0.55,0,405)">
                    <stop offset="0%"   stop-color="white" stop-opacity="1"/>
                    <stop offset="50%"  stop-color="white" stop-opacity="0.7"/>
                    <stop offset="80%"  stop-color="white" stop-opacity="0.2"/>
                    <stop offset="100%" stop-color="white" stop-opacity="0"/>
                </radialGradient>
                <mask id="gridMask">
                    <rect width="1440" height="900" fill="url(#gridFade)"/>
                </mask>

                <!-- Horizon fade: lines near VP fade out (DOF blur zone) -->
                <linearGradient id="horizFade" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%"   stop-color="white" stop-opacity="0"/>
                    <stop offset="38%"  stop-color="white" stop-opacity="0.15"/>
                    <stop offset="55%"  stop-color="white" stop-opacity="0.7"/>
                    <stop offset="100%" stop-color="white" stop-opacity="1"/>
                </linearGradient>
                <mask id="horizMask">
                    <rect width="1440" height="900" fill="url(#horizFade)"/>
                </mask>
            </defs>

            <!-- Background fill -->
            <rect width="1440" height="900" fill="var(--background)"/>

            <?php
            $vx = 720; $vy = 418;

            // ── Radial / converging lines from vanishing point ──
            // Far group (near VP): blurred, very faint
            $numRays = 36;
            echo '<g mask="url(#gridMask)" filter="url(#dofBlur)" opacity="0.85">';
            for ($i = 0; $i <= $numRays; $i++):
                $bx = -120 + ($i / $numRays) * 1680;
                $by = 930;
                $dist = abs($i / $numRays - 0.5) * 2;
                $op = round(0.05 + $dist * 0.11, 3);
                echo '<line x1="'.$vx.'" y1="'.$vy.'" x2="'.round($bx).'" y2="'.$by.'"'
                   .' stroke="var(--ink)" stroke-width="0.6" opacity="'.$op.'"/>';
            endfor;
            echo '</g>';

            // ── Horizontal convergence lines ──
            // Near-VP section: apply blur, very faint (DOF effect)
            $closeYStops = [422, 428, 435, 443, 452, 462, 473, 486, 501, 518, 537];
            echo '<g mask="url(#horizMask)" filter="url(#dofBlur)">';
            foreach ($closeYStops as $y):
                $t = ($y - $vy) / (900 - $vy);
                $xL = $vx - ($vx * 1.6 * $t);
                $xR = $vx + ((1440 - $vx) * 1.6 * $t);
                $op = round(0.02 + $t * 0.04, 3);
                echo '<line x1="'.round($xL).'" y1="'.$y.'" x2="'.round($xR).'" y2="'.$y.'"'
                   .' stroke="var(--ink)" stroke-width="0.6" opacity="'.$op.'"/>';
            endforeach;
            echo '</g>';

            // Mid section: slight blur
            $midYStops = [558, 582, 610, 642, 678, 718, 762, 810, 860, 900];
            echo '<g mask="url(#gridMask)" filter="url(#dofBlurMid)">';
            foreach ($midYStops as $y):
                $t = ($y - $vy) / (900 - $vy);
                $xL = $vx - ($vx * 1.6 * $t);
                $xR = $vx + ((1440 - $vx) * 1.6 * $t);
                $op = round(0.04 + $t * 0.09, 3);
                echo '<line x1="'.round($xL).'" y1="'.$y.'" x2="'.round($xR).'" y2="'.$y.'"'
                   .' stroke="var(--ink)" stroke-width="0.7" opacity="'.$op.'"/>';
            endforeach;
            echo '</g>';
            ?>
        </svg>
        <!-- Soft horizon glow at vanishing point -->
        <div class="login-grid-glow"></div>
    </div>

    <div class="login-card w-full max-w-md bg-card/70 backdrop-blur rounded-2xl border border-ink/15 p-8 flex flex-col gap-6 shadow-sm">
        <!-- Logo Header -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-ink text-paper rounded-xl flex items-center justify-center font-display font-black text-xl shadow-sm">
                ▣
            </div>
            <div class="leading-tight">
                <div class="mono text-[10px] uppercase tracking-[0.25em] text-muted-foreground">Gtech PMO</div>
                <div class="font-display text-lg font-black uppercase text-ink">ATLAS</div>
            </div>
        </div>

        <div>
            <h1 class="font-display text-2xl font-bold uppercase tracking-tight text-ink">Login</h1>
            <p class="text-[10px] text-muted-foreground uppercase font-mono tracking-widest mt-1">Access PMO Portfolio Tracker</p>
        </div>

        <!-- Alerts -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="bg-status-ontrack/20 border border-status-ontrack/30 text-ink px-4 py-2.5 rounded-xl font-mono text-[10px] uppercase tracking-widest font-bold flex items-center justify-between shadow-sm">
                <span>[SUCCESS] <?= session()->getFlashdata('success') ?></span>
                <button onclick="this.parentElement.remove()" class="font-black hover:opacity-75">✕</button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="bg-status-blocked/20 border border-status-blocked/30 text-ink px-4 py-2.5 rounded-xl font-mono text-[10px] uppercase tracking-widest font-bold flex items-center justify-between shadow-sm">
                <span>[ERROR] <?= session()->getFlashdata('error') ?></span>
                <button onclick="this.parentElement.remove()" class="font-black hover:opacity-75">✕</button>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="<?= base_url('login') ?>" method="POST" class="flex flex-col gap-4 font-mono">
            <?= csrf_field() ?>
            <div class="flex flex-col gap-1">
                <label for="email" class="text-[10px] uppercase text-muted-foreground">Email Address</label>
                <input type="email" name="email" id="email" required value="<?= old('email') ?>" placeholder="email@gtech.com" class="w-full bg-background/50 border border-ink/20 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ink/20">
            </div>

            <div class="flex flex-col gap-1">
                <label for="password" class="text-[10px] uppercase text-muted-foreground">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required placeholder="Password" class="w-full bg-background/50 border border-ink/20 rounded-xl px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-ink/20">
                    <button type="button" onclick="togglePassword('password', this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-ink transition-colors" title="Toggle password visibility">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full rounded-full bg-ink text-paper py-3 text-xs uppercase tracking-widest font-bold hover:bg-ink/90 mt-2 shadow-sm transition-colors">
                Sign In
            </button>
        </form>

        <div class="border-t border-ink/10 pt-4 flex justify-between items-center text-[10px] font-mono uppercase tracking-widest">
            <span class="text-muted-foreground">Forgot Password?</span>
            <a href="<?= base_url('reset-password') ?>" class="font-bold underline text-ink hover:text-ink/80">Reset Here</a>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
});

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    const icon = btn.querySelector('i');
    if (icon) {
        icon.setAttribute('data-lucide', isHidden ? 'eye-off' : 'eye');
        lucide.createIcons({ nodes: [icon] });
    }
}
</script>
</body>
</html>
