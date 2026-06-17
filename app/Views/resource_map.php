<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<?php
$roleHex = [
    'FE' => 'var(--status-ontrack)',
    'BE' => 'var(--status-atrisk)',
    'QA' => 'var(--status-delayed)',
    'BA' => 'var(--status-backlog)',
];

$roleBgs = [
    'FE' => 'bg-status-ontrack',
    'BE' => 'bg-status-atrisk',
    'QA' => 'bg-status-delayed',
    'BA' => 'bg-status-backlog',
];

// Layout Constants
$W = 1100;
$cols = 2;
$colW = $W / $cols;
$rowH = 520;

$projectNodes = [];
foreach ($groups as $i => $g) {
    $col = $i % $cols;
    $row = floor($i / $cols);
    $cx = $col * $colW + $colW / 2;
    $cy = $row * $rowH + 260;
    
    $members = $g['members'];
    $memberCount = count($members);
    $radius = 130 + min($memberCount, 8) * 8;
    $angleStep = ($memberCount > 0) ? (pi() * 2) / $memberCount : 0;
    
    $nodes = [];
    foreach ($members as $idx => $m) {
        $angle = $idx * $angleStep - pi() / 2;
        $nodes[] = [
            'resource' => $m,
            'x' => $cx + cos($angle) * $radius,
            'y' => $cy + sin($angle) * $radius
        ];
    }
    
    $projectNodes[] = [
        'name'  => $g['name'],
        'code'  => $g['code'],
        'cx'    => $cx,
        'cy'    => $cy,
        'nodes' => $nodes
    ];
}

$H = ceil(count($groups) / $cols) * $rowH + 40;
if ($H < 400) $H = 400; // Minimum height
?>

<div class="min-h-screen pb-24">
    <!-- Sub-header / Back button -->
    <div class="border-b border-ink/15 no-print">
        <div class="w-full px-8 lg:px-14 py-4 flex items-center justify-between">
            <a href="<?= base_url('resource') ?>" class="rounded-full border border-ink/20 bg-card text-ink hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold flex items-center gap-1.5 shadow-sm">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                Back to Resource
            </a>
        </div>
    </div>

    <!-- Map Container -->
    <div class="w-full px-8 lg:px-14 mt-8">
        
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-4 mb-8">
            <div>
                <span class="eyebrow">Resource // Map</span>
                <h1 class="font-display text-2xl md:text-3xl font-black uppercase mt-1 tracking-tight">
                    Project ↔ Resource Mesh
                </h1>
            </div>
            
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Legend -->
                <div class="flex flex-wrap gap-1.5">
                    <?php foreach (['FE', 'BE', 'QA', 'BA'] as $role): ?>
                        <div class="flex items-center gap-1.5 rounded-full border border-ink/15 px-3 py-1 bg-background text-[10px] font-mono uppercase tracking-wider font-bold">
                            <span class="inline-block w-2.5 h-2.5 rounded-full border border-ink/10 <?= $roleBgs[$role] ?>"></span>
                            <span><?= $role ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button onclick="exportMapPdf()" class="rounded-full border border-ink/20 bg-ink text-paper hover:bg-ink/90 px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    Export PDF
                </button>
            </div>
        </div>

        <!-- SVG Map Card -->
        <div id="map-capture" class="rounded-2xl border border-ink/15 bg-card p-6 overflow-x-auto no-scrollbar shadow-sm">
            <svg viewBox="0 0 <?= $W ?> <?= $H ?>" class="w-full h-auto bg-card" style="min-width: 700px;">
                <?php foreach ($projectNodes as $p): ?>
                    <g>
                        <!-- Lines connecting project center to resource nodes -->
                        <?php foreach ($p['nodes'] as $n): ?>
                            <line x1="<?= $p['cx'] ?>" y1="<?= $p['cy'] ?>" x2="<?= $n['x'] ?>" y2="<?= $n['y'] ?>" stroke="var(--ink)" stroke-width="0.6" opacity="0.25" />
                        <?php endforeach; ?>

                        <!-- Central Project Box -->
                        <g>
                            <rect x="<?= $p['cx'] - 130 ?>" y="<?= $p['cy'] - 30 ?>" width="260" height="60" rx="12" fill="var(--ink)" />
                            <text x="<?= $p['cx'] ?>" y="<?= $p['cy'] - 10 ?>" text-anchor="middle" fill="var(--paper)" class="font-mono text-[9px] uppercase tracking-widest opacity-80">
                                <?= esc($p['code']) ?>
                            </text>
                            <text x="<?= $p['cx'] ?>" y="<?= $p['cy'] + 6 ?>" text-anchor="middle" fill="var(--paper)" class="font-display text-xs font-bold uppercase tracking-tight">
                                <?php
                                $nameParts = explode('//', $p['name']);
                                echo esc(substr(trim($nameParts[0]), 0, 32));
                                ?>
                            </text>
                            <text x="<?= $p['cx'] ?>" y="<?= $p['cy'] + 21 ?>" text-anchor="middle" fill="var(--paper)" class="font-mono text-[8px] uppercase tracking-widest opacity-70">
                                <?= count($p['nodes']) ?> RESOURCES
                            </text>
                        </g>

                        <!-- Surrounding Resource Nodes -->
                        <?php foreach ($p['nodes'] as $n): ?>
                            <g>
                                <circle cx="<?= $n['x'] ?>" cy="<?= $n['y'] ?>" r="20" fill="<?= $roleHex[$n['resource']['role']] ?>" stroke="var(--ink)" stroke-width="0.8" />
                                <text x="<?= $n['x'] ?>" y="<?= $n['y'] + 3 ?>" text-anchor="middle" fill="var(--paper)" class="font-mono text-[9px] font-bold">
                                    <?= esc($n['resource']['role']) ?>
                                </text>
                                <text x="<?= $n['x'] ?>" y="<?= $n['y'] + 34 ?>" text-anchor="middle" fill="var(--ink)" class="font-display text-[10px] font-semibold uppercase">
                                    <?= esc($n['resource']['name']) ?>
                                </text>
                                <text x="<?= $n['x'] ?>" y="<?= $n['y'] + 45 ?>" text-anchor="middle" fill="var(--muted-foreground)" class="font-mono text-[8px]">
                                    <?= $n['resource']['utilization'] ?>% · <?= $n['resource']['status'] === 'employee' ? 'EMP' : 'OUT' ?>
                                </text>
                            </g>
                        <?php endforeach; ?>
                    </g>
                <?php endforeach; ?>
            </svg>
        </div>
    </div>
</div>

<script src="<?= base_url('js/resource_map.js') ?>"></script>
<?= $this->endSection() ?>
