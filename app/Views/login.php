<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login // NEXUS</title>
    
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
</head>
<body class="min-h-screen flex items-center justify-center p-6 bg-background text-foreground relative overflow-hidden">

    <!-- Depth-of-Field Blur Background -->
    <div class="dof-bg"></div>

    <div class="w-full max-w-md bg-card/70 backdrop-blur rounded-2xl border border-ink/15 p-8 flex flex-col gap-6 shadow-sm z-10">
        <!-- Logo Header -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-ink text-paper rounded-xl flex items-center justify-center font-display font-black text-xl shadow-sm">
                ▣
            </div>
            <div class="leading-tight">
                <div class="mono text-[10px] uppercase tracking-[0.25em] text-muted-foreground">Gtech PMO</div>
                <div class="font-display text-lg font-black uppercase text-ink">NEXUS</div>
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
