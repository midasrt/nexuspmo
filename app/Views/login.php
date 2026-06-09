<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login // NEXUS</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    
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
                    },
                    fontFamily: {
                        sans: ['"Space Grotesk"', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
</head>
<body class="min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-md bg-card brutal-border brutal-shadow p-8 flex flex-col gap-6">
        <!-- Logo Header -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary brutal-border brutal-shadow-sm flex items-center justify-center mono font-black text-primary-foreground text-xl">
                ▣
            </div>
            <div class="leading-tight">
                <div class="mono text-[10px] uppercase tracking-[0.25em] text-muted-foreground">Gtech PMO</div>
                <div class="mono text-lg font-black uppercase">NEXUS</div>
            </div>
        </div>

        <div>
            <h1 class="mono text-2xl font-black uppercase tracking-tight">Login</h1>
            <p class="text-xs text-muted-foreground uppercase mono tracking-widest mt-1">Access PMO Portfolio Tracker</p>
        </div>

        <!-- Alerts -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="bg-primary/20 border border-primary text-foreground px-4 py-2.5 mono text-[10px] uppercase tracking-widest font-black flex items-center justify-between">
                <span>[SUCCESS] <?= session()->getFlashdata('success') ?></span>
                <button onclick="this.parentElement.remove()" class="font-black hover:opacity-75">✕</button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="bg-destructive/10 border border-destructive text-destructive px-4 py-2.5 mono text-[10px] uppercase tracking-widest font-black flex items-center justify-between">
                <span>[ERROR] <?= session()->getFlashdata('error') ?></span>
                <button onclick="this.parentElement.remove()" class="font-black hover:opacity-75">✕</button>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="<?= base_url('login') ?>" method="POST" class="flex flex-col gap-4">
            <?= csrf_field() ?>
            <div class="flex flex-col gap-1.5">
                <label for="email" class="mono text-[10px] uppercase tracking-widest font-black text-foreground">Email Address</label>
                <input type="email" name="email" id="email" required value="<?= old('email') ?>" class="w-full bg-background border border-foreground px-3 py-2.5 mono text-xs brutal-border focus:outline-none focus:ring-1 focus:ring-primary">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="password" class="mono text-[10px] uppercase tracking-widest font-black text-foreground">Password</label>
                <input type="password" name="password" id="password" required class="w-full bg-background border border-foreground px-3 py-2.5 mono text-xs brutal-border focus:outline-none focus:ring-1 focus:ring-primary">
            </div>

            <button type="submit" class="w-full brutal-border bg-foreground text-background py-3 mono text-xs uppercase tracking-widest font-black brutal-hover mt-2">
                Sign In
            </button>
        </form>

        <div class="border-t border-foreground pt-4 flex justify-between items-center text-[10px] mono uppercase tracking-widest">
            <span class="text-muted-foreground">Forgot Password?</span>
            <a href="<?= base_url('reset-password') ?>" class="font-black underline hover:text-primary">Reset Here</a>
        </div>
    </div>

</body>
</html>
