<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'PMO // Portfolio Tracker') ?></title>
    
    <!-- Google Fonts: Archivo, Inter, JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:ital,wght@0,100..900;1,100..900&family=Inter:wght@300..800&family=JetBrains+Mono:wght@100..800&display=swap" rel="stylesheet">
    
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
                        ink: 'var(--ink)',
                        paper: 'var(--paper)',
                    },
                    fontFamily: {
                        sans: ['"Inter"', 'system-ui', 'sans-serif'],
                        display: ['"Archivo"', '"Helvetica Neue"', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>

    <!-- Custom CSS -->
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
    <script>
        window.Nexus = {
            baseUrl: '<?= rtrim(base_url(), '/') ?>'
        };
    </script>
    <style>
        /* Viewer role read-only rules */
        .role-viewer button[onclick*="openCreate"],
        .role-viewer button[onclick*="openEdit"],
        .role-viewer button[onclick*="openDelete"],
        .role-viewer button[onclick*="unassignResource"],
        .role-viewer button[onclick*="deleteAjaxItem"],
        .role-viewer button[onclick*="toggleEcho"],
        .role-viewer button[id="echo-term-btn"],
        .role-viewer button[onclick*="addPhase"],
        .role-viewer button[onclick*="openAdd"],
        .role-viewer button[onclick*="edit"],
        .role-viewer button[onclick*="delete"],
        .role-viewer button[onclick*="Health"],
        .role-viewer button[onclick*="openEditHealthModal"],
        .role-viewer button[onclick*="openCreatePhaseModal"],
        .role-viewer button[onclick*="openCreateSubtaskModal"],
        .role-viewer button[onclick*="openCreateDependencyModal"],
        .role-viewer button[onclick*="openCreateEscalationModal"],
        .role-viewer button[onclick*="openCreateRiskModal"],
        .role-viewer button[onclick*="openCreateActionModal"],
        .role-viewer form:not(#search-form) button,
        .role-viewer form:not(#search-form) input:not([type="hidden"]):not([type="text"]):not(#search-input),
        .role-viewer form[id="assign-resource-form"],
        .role-viewer form[id="upload-documents-form"] {
            display: none !important;
        }
        .role-viewer button[onclick*="toggleActionItem"] {
            pointer-events: none !important;
            cursor: default !important;
            opacity: 0.7 !important;
        }
        .role-viewer .phase-drag-row {
            cursor: default !important;
        }
        .role-viewer .phase-drag-row [data-lucide="grip-vertical"] {
            display: none !important;
        }
    </style>
</head>
<body class="min-h-screen text-foreground <?= session()->get('role') === 'viewer' ? 'role-viewer' : '' ?>">

    <!-- Depth-of-Field Blur Background -->
    <div class="dof-bg">
        <canvas id="dof-canvas"></canvas>
    </div>

    <!-- Floating Navigation Menu (Bottom-Left) -->
    <?php
    $menuItems = [
        ['title' => 'Dashboard', 'url' => base_url(), 'icon' => 'layout-grid', 'path' => '/'],
        ['title' => 'Projects', 'url' => base_url('projects'), 'icon' => 'folder-kanban', 'path' => '/projects'],
        ['title' => 'Reports', 'url' => base_url('reports'), 'icon' => 'file-bar-chart', 'path' => '/reports'],
    ];

    if (session()->get('role') !== 'viewer') {
        $menuItems[] = ['title' => 'Budget Control', 'url' => base_url('budget'), 'icon' => 'wallet', 'path' => '/budget'];
    }

    $menuItems[] = ['title' => 'Resource', 'url' => base_url('resource'), 'icon' => 'users', 'path' => '/resource'];
    $menuItems[] = ['title' => 'Squads', 'url' => base_url('squads'), 'icon' => 'boxes', 'path' => '/squads'];

    if (session()->get('role') !== 'viewer') {
        $menuItems[] = ['title' => 'Settings', 'url' => base_url('settings'), 'icon' => 'settings', 'path' => '/settings'];
    }

    if (session()->get('role') === 'admin') {
        $menuItems[] = ['title' => 'Users', 'url' => base_url('users'), 'icon' => 'user-cog', 'path' => '/users'];
    }

    foreach ($menuItems as &$item) {
        $item['active'] = ($item['path'] === '/') 
            ? ($currentPath === '/') 
            : (strpos($currentPath, $item['path']) === 0);
    }
    unset($item);
    ?>
    <div class="fixed bottom-6 left-6 z-50 flex flex-col items-start gap-3 no-print">
        <!-- Floating Menu Links -->
        <ul id="floating-menu-items" class="hidden flex flex-col gap-2 transition-all duration-300 transform translate-y-4 opacity-0">
            <?php foreach ($menuItems as $item): ?>
                <li>
                    <a href="<?= $item['url'] ?>" 
                       class="group flex items-center gap-3 rounded-full bg-ink pl-2 pr-5 py-2 text-paper shadow-lg hover:scale-105 transition-transform"
                       style="background: <?= $item['active'] ? 'var(--ink)' : 'color-mix(in oklab, var(--ink) 92%, transparent)' ?>">
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-paper text-ink group-hover:rotate-[8deg] transition-transform">
                            <i data-lucide="<?= $item['icon'] ?>" class="w-4 h-4"></i>
                        </span>
                        <span class="font-display text-xs tracking-wide uppercase font-bold"><?= $item['title'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
            

            
            <?php if (session()->get('isLoggedIn')): ?>
            <!-- Sign Out -->
            <li>
                <a href="<?= base_url('logout') ?>" class="group flex items-center gap-3 rounded-full bg-ink/80 pl-2 pr-5 py-2 text-paper shadow-lg hover:scale-105 transition-transform">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-paper text-ink">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </span>
                    <span class="font-display text-xs tracking-wide uppercase font-bold">Sign Out</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Floating Button Trigger -->
        <button onclick="toggleFloatingMenu()" class="flex items-center gap-3 rounded-full bg-ink pl-2 pr-5 py-2 text-paper shadow-xl hover:scale-105 transition-transform">
            <span class="grid h-10 w-10 place-items-center rounded-full bg-paper text-ink">
                <span id="menu-trigger-icon-span">
                    <i data-lucide="menu" class="w-[18px] h-[18px]"></i>
                </span>
            </span>
            <span class="font-display text-xs tracking-[0.18em] uppercase font-black">
                Atlas<?php if (session()->get('isLoggedIn')) echo " · " . esc(session()->get('role')); ?>
            </span>
        </button>
    </div>

    <!-- Main Content Wrapper -->
    <div class="min-h-screen w-full flex flex-col">
        <!-- Flash messages -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="no-print bg-status-ontrack text-white border-b border-foreground px-6 py-3 mono text-xs uppercase tracking-widest font-black flex items-center justify-between z-10">
                <span>[SUCCESS] <?= session()->getFlashdata('success') ?></span>
                <button onclick="this.parentElement.remove()" class="font-black hover:opacity-75">✕</button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="no-print bg-status-blocked text-white border-b border-foreground px-6 py-3 mono text-xs uppercase tracking-widest font-black flex items-center justify-between z-10">
                <span>[ERROR] <?= session()->getFlashdata('error') ?></span>
                <button onclick="this.parentElement.remove()" class="font-black hover:opacity-75">✕</button>
            </div>
        <?php endif; ?>

        <!-- Render Content -->
        <main class="flex-1">
            <?= $this->renderSection('content') ?>
        </main>
    </div>

    <!-- Lucide Icons initialization -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        function toggleFloatingMenu() {
            const items = document.getElementById('floating-menu-items');
            const iconSpan = document.getElementById('menu-trigger-icon-span');
            
            if (items.classList.contains('hidden')) {
                items.classList.remove('hidden');
                void items.offsetWidth; // Force reflow
                items.classList.remove('translate-y-4', 'opacity-0');
                iconSpan.innerHTML = '<i data-lucide="x" class="w-[18px] h-[18px]"></i>';
            } else {
                items.classList.add('translate-y-4', 'opacity-0');
                iconSpan.innerHTML = '<i data-lucide="menu" class="w-[18px] h-[18px]"></i>';
                setTimeout(() => {
                    if (items.classList.contains('opacity-0')) {
                        items.classList.add('hidden');
                    }
                }, 300);
            }
            if (window.lucide) {
                window.lucide.createIcons();
            }
        }



        // Dynamic Depth of Field Background Canvas Animation
        (function() {
            const canvas = document.getElementById('dof-canvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            
            let width = canvas.width = window.innerWidth;
            let height = canvas.height = window.innerHeight;
            
            window.addEventListener('resize', () => {
                width = canvas.width = window.innerWidth;
                height = canvas.height = window.innerHeight;
            });
            
            // Vibrant colors with moderate opacity that will blend beautifully under the CSS blur
            const getPalette = () => {
                return [
                    'rgba(251, 146, 60, 0.35)',   // Orange/Peach (#fb923c)
                    'rgba(96, 165, 250, 0.35)',   // Soft Blue (#60a5fa)
                    'rgba(167, 139, 250, 0.35)',  // Soft Purple (#a78bfa)
                    'rgba(244, 114, 182, 0.28)'   // Soft Pink (#f472b6)
                ];
            };
            
            let palette = getPalette();
            
            // Define floating blobs
            const blobs = [];
            const blobCount = 6;
            
            for (let i = 0; i < blobCount; i++) {
                blobs.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    radius: Math.random() * (width * 0.15) + (width * 0.15),
                    vx: (Math.random() - 0.5) * 0.5,
                    vy: (Math.random() - 0.5) * 0.5,
                    colorIndex: i % 4,
                    phaseX: Math.random() * Math.PI * 2,
                    phaseY: Math.random() * Math.PI * 2,
                    speed: 0.0005 + Math.random() * 0.0005
                });
            }
            
            let mouseX = width / 2;
            let mouseY = height / 2;
            let targetMouseX = width / 2;
            let targetMouseY = height / 2;
            
            window.addEventListener('mousemove', (e) => {
                targetMouseX = e.clientX;
                targetMouseY = e.clientY;
            });
            
            function animate(time) {
                mouseX += (targetMouseX - mouseX) * 0.03;
                mouseY += (targetMouseY - mouseY) * 0.03;
                
                ctx.clearRect(0, 0, width, height);
                
                blobs.forEach((blob, index) => {
                    blob.phaseX += blob.speed;
                    blob.phaseY += blob.speed;
                    
                    const driftX = Math.sin(blob.phaseX) * 1.5;
                    const driftY = Math.cos(blob.phaseY) * 1.5;
                    
                    blob.x += blob.vx + driftX;
                    blob.y += blob.vy + driftY;
                    
                    const pad = blob.radius;
                    if (blob.x < -pad) { blob.x = width + pad; }
                    if (blob.x > width + pad) { blob.x = -pad; }
                    if (blob.y < -pad) { blob.y = height + pad; }
                    if (blob.y > height + pad) { blob.y = -pad; }
                    
                    const depthFactor = (index % 3 + 1) * 0.04;
                    const parallaxX = (mouseX - width / 2) * depthFactor;
                    const parallaxY = (mouseY - height / 2) * depthFactor;
                    
                    const drawX = blob.x - parallaxX;
                    const drawY = blob.y - parallaxY;
                    
                    ctx.beginPath();
                    ctx.arc(drawX, drawY, blob.radius, 0, Math.PI * 2);
                    ctx.fillStyle = palette[blob.colorIndex] || palette[0];
                    ctx.fill();
                });
                
                requestAnimationFrame(animate);
            }
            
            requestAnimationFrame(animate);
        })();
    </script>
</body>
</html>
