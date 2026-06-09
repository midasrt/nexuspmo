<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'PMO // Portfolio Tracker') ?></title>
    
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
                        secondary: 'var(--secondary)',
                        'secondary-foreground': 'var(--secondary-foreground)',
                        muted: 'var(--muted)',
                        'muted-foreground': 'var(--muted-foreground)',
                        accent: 'var(--accent)',
                        'accent-foreground': 'var(--accent-foreground)',
                        destructive: 'var(--destructive)',
                        'destructive-foreground': 'var(--destructive-foreground)',
                        border: 'var(--border)',
                        input: 'var(--input)',
                        ring: 'var(--ring)',
                        'status-ontrack': 'var(--status-ontrack)',
                        'status-atrisk': 'var(--status-atrisk)',
                        'status-blocked': 'var(--status-blocked)',
                        'status-delayed': 'var(--status-delayed)',
                        'status-backlog': 'var(--status-backlog)',
                    },
                    fontFamily: {
                        sans: ['"Space Grotesk"', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>

    <!-- Custom Brutalist Stylesheet -->
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
    
    <!-- jsPDF and html2canvas CDNs for PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas-pro@2.0.3/dist/html2canvas-pro.min.js"></script>

    <!-- Dynamic Status Background Colors -->
    <?php
    $db = \Config\Database::connect();
    if ($db->tableExists('status_definitions')) {
        $defs = $db->table('status_definitions')->get()->getResultArray();
        echo '<style>';
        foreach ($defs as $def) {
            $slug = esc($def['status']);
            $color = esc($def['color'] ?? '#6b7280');
            echo ".bg-status-{$slug} { background-color: {$color} !important; color: #ffffff !important; }\n";
        }
        echo '</style>';
    }
    ?>
</head>
<body class="min-h-screen flex">

    <!-- Sidebar Navigation -->
    <aside class="no-print w-64 bg-card border-r border-foreground flex flex-col shrink-0 sticky top-0 h-screen">
        <!-- Sidebar Header -->
        <div class="border-b border-foreground p-5 flex items-center gap-3">
            <div class="w-8 h-8 bg-primary brutal-border brutal-shadow-sm flex items-center justify-center mono font-black text-primary-foreground shrink-0">
                ▣
            </div>
            <div class="leading-tight">
                <div class="mono text-[9px] uppercase tracking-[0.25em] text-muted-foreground">Gtech PMO</div>
                <div class="mono text-sm font-black uppercase">NEXUS</div>
            </div>
        </div>

        <!-- User Info Widget -->
        <?php if (session()->get('isLoggedIn')): ?>
        <div class="border-b border-foreground p-4 bg-muted/20">
            <div class="mono text-[9px] uppercase tracking-widest text-muted-foreground">Logged in as</div>
            <div class="font-bold text-xs truncate mt-0.5"><?= esc(session()->get('name')) ?></div>
            <div class="inline-block mt-1 px-1.5 py-0.5 border border-foreground text-[8px] mono uppercase tracking-widest font-black <?= session()->get('role') === 'admin' ? 'bg-status-ontrack' : 'bg-secondary' ?>">
                <?= esc(session()->get('role')) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Navigation Menu -->
        <nav class="flex-1 p-4 space-y-2">
            <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground px-2 mb-3">Navigation</div>
            
            <?php
            $menuItems = [
                ['title' => 'Portfolio', 'url' => base_url(), 'icon' => 'layout-grid', 'path' => '/'],
                ['title' => 'Projects', 'url' => base_url('projects'), 'icon' => 'folder-kanban', 'path' => '/projects'],
                ['title' => 'Reports', 'url' => base_url('reports'), 'icon' => 'file-bar-chart', 'path' => '/reports'],
                ['title' => 'Budget Control', 'url' => base_url('budget'), 'icon' => 'wallet', 'path' => '/budget'],
                ['title' => 'Resource', 'url' => base_url('resource'), 'icon' => 'users', 'path' => '/resource'],
                ['title' => 'Squads', 'url' => base_url('squads'), 'icon' => 'boxes', 'path' => '/squads'],
                ['title' => 'Settings', 'url' => base_url('settings'), 'icon' => 'settings', 'path' => '/settings'],
            ];

            if (session()->get('role') === 'admin') {
                $menuItems[] = ['title' => 'Users', 'url' => base_url('users'), 'icon' => 'user-cog', 'path' => '/users'];
            }

            foreach ($menuItems as $item):
                $active = ($item['path'] === '/') 
                    ? ($currentPath === '/') 
                    : (strpos($currentPath, $item['path']) === 0);
            ?>
                <a href="<?= $item['url'] ?>" class="flex items-center gap-3 px-3 py-2.5 mono text-xs uppercase tracking-widest border transition-all duration-100 <?= $active ? 'border-foreground bg-primary text-primary-foreground brutal-shadow-sm' : 'border-transparent hover:border-foreground hover:bg-background' ?>">
                    <i data-lucide="<?= $item['icon'] ?>" class="w-4 h-4 shrink-0"></i>
                    <span><?= $item['title'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-foreground">
            <a href="<?= base_url('logout') ?>" class="flex items-center gap-3 px-3 py-2.5 mono text-xs uppercase tracking-widest border border-transparent hover:border-foreground hover:bg-destructive hover:text-destructive-foreground transition-all duration-100 w-full text-left">
                <i data-lucide="log-out" class="w-4 h-4 shrink-0"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        
        <!-- Flash messages -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="no-print bg-status-ontrack text-foreground border-b-2 border-foreground px-6 py-3 mono text-xs uppercase tracking-widest font-black flex items-center justify-between">
                <span>[SUCCESS] <?= session()->getFlashdata('success') ?></span>
                <button onclick="this.parentElement.remove()" class="font-black hover:opacity-75">✕</button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="no-print bg-status-blocked text-primary-foreground border-b-2 border-foreground px-6 py-3 mono text-xs uppercase tracking-widest font-black flex items-center justify-between">
                <span>[ERROR] <?= session()->getFlashdata('error') ?></span>
                <button onclick="this.parentElement.remove()" class="font-black hover:opacity-75">✕</button>
            </div>
        <?php endif; ?>

        <!-- Render View Section -->
        <main class="flex-1">
            <?= $this->renderSection('content') ?>
        </main>
    </div>

    <!-- Lucide Icons initialization -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
