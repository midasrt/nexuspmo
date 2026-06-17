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
$W = 1200;
$cols = 2;
$colW = $W / $cols;
$rowH = 640;

$squadNodes = [];
foreach ($groups as $i => $g) {
    $col = $i % $cols;
    $row = floor($i / $cols);
    $cx = $col * $colW + $colW / 2;
    $cy = $row * $rowH + 320;
    
    $projects = $g['projects'];
    $projectCount = count($projects);
    $projRadius = 160;
    $projStep = ($projectCount > 0) ? (pi() * 2) / $projectCount : 0;
    
    $projectNodes = [];
    foreach ($projects as $idx => $p) {
        $angle = $idx * $projStep - pi() / 2;
        $projectNodes[] = [
            'project' => $p,
            'x' => $cx + cos($angle) * $projRadius,
            'y' => $cy + sin($angle) * $projRadius
        ];
    }
    
    $members = $g['members'];
    $memberCount = count($members);
    $memRadius = 260;
    $memStep = ($memberCount > 0) ? (pi() * 2) / $memberCount : 0;
    
    $memberNodes = [];
    foreach ($members as $idx => $m) {
        $angle = $idx * $memStep - pi() / 2 + ($memStep / 2);
        $memberNodes[] = [
            'member' => $m,
            'x' => $cx + cos($angle) * $memRadius,
            'y' => $cy + sin($angle) * $memRadius
        ];
    }
    
    $squadNodes[] = [
        'squad'        => $g['squad'],
        'cx'           => $cx,
        'cy'           => $cy,
        'projectNodes' => $projectNodes,
        'memberNodes'  => $memberNodes,
        'projects'     => $projects,
        'members'      => $members
    ];
}

$H = ceil(count($groups) / $cols) * $rowH + 40;
if ($H < 450) $H = 450;
?>

<div class="min-h-screen pb-24">
    <!-- Sub-header / Back button -->
    <div class="border-b border-ink/15 no-print">
        <div class="w-full px-8 lg:px-14 py-4 flex items-center justify-between">
            <a href="<?= base_url('squads') ?>" class="rounded-full border border-ink/20 bg-card text-ink hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold flex items-center gap-1.5 shadow-sm">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                Back to Squads
            </a>
        </div>
    </div>

    <!-- Map Container -->
    <div class="w-full px-8 lg:px-14 mt-8">
        
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-4 mb-8">
            <div>
                <span class="eyebrow">Squad // Map</span>
                <h1 class="font-display text-2xl md:text-3xl font-black uppercase mt-1 tracking-tight">
                    Squad ↔ Project Mesh
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

                <button onclick="exportSquadMapPdf()" class="rounded-full border border-ink/20 bg-ink text-paper hover:bg-ink/90 px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    Export PDF
                </button>
            </div>
        </div>

        <!-- SVG Map Card -->
        <div id="squad-map-capture" class="rounded-2xl border border-ink/15 bg-card p-6 overflow-x-auto no-scrollbar shadow-sm">
            <svg viewBox="0 0 <?= $W ?> <?= $H ?>" class="w-full h-auto bg-card" style="min-width: 700px;">
                <?php foreach ($squadNodes as $s): ?>
                    <g>
                        <!-- Lines connecting squad center to project boxes -->
                        <?php foreach ($s['projectNodes'] as $n): ?>
                            <line x1="<?= $s['cx'] ?>" y1="<?= $s['cy'] ?>" x2="<?= $n['x'] ?>" y2="<?= $n['y'] ?>" stroke="var(--ink)" stroke-width="0.8" opacity="0.35" />
                        <?php endforeach; ?>

                        <!-- Lines connecting squad center to member circles -->
                        <?php foreach ($s['memberNodes'] as $n): ?>
                            <line x1="<?= $s['cx'] ?>" y1="<?= $s['cy'] ?>" x2="<?= $n['x'] ?>" y2="<?= $n['y'] ?>" stroke="var(--ink)" stroke-width="0.6" stroke-dasharray="3 3" opacity="0.25" />
                        <?php endforeach; ?>

                        <!-- Central Squad Box -->
                        <g>
                            <rect x="<?= $s['cx'] - 110 ?>" y="<?= $s['cy'] - 32 ?>" width="220" height="64" rx="12" fill="var(--ink)" />
                            <text x="<?= $s['cx'] ?>" y="<?= $s['cy'] - 12 ?>" text-anchor="middle" fill="var(--paper)" class="font-mono text-[8px] uppercase tracking-widest opacity-80">
                                SQUAD
                            </text>
                            <text x="<?= $s['cx'] ?>" y="<?= $s['cy'] + 6 ?>" text-anchor="middle" fill="var(--paper)" class="font-display text-sm font-bold uppercase tracking-tight">
                                <?= esc(strtoupper($s['squad']['name'])) ?>
                            </text>
                            <text x="<?= $s['cx'] ?>" y="<?= $s['cy'] + 21 ?>" text-anchor="middle" fill="var(--paper)" class="font-mono text-[8px] uppercase tracking-widest opacity-70">
                                <?= count($s['projects']) ?> PRJ · <?= count($s['members']) ?> RES
                            </text>
                        </g>

                        <!-- Project Nodes surrounding the center -->
                        <?php foreach ($s['projectNodes'] as $n): ?>
                            <g>
                                <rect x="<?= $n['x'] - 70 ?>" y="<?= $n['y'] - 18 ?>" width="140" height="36" rx="8" fill="var(--card)" stroke="var(--ink)" stroke-width="0.8" />
                                <text x="<?= $n['x'] ?>" y="<?= $n['y'] - 4 ?>" text-anchor="middle" fill="var(--muted-foreground)" class="font-mono text-[8px] uppercase tracking-widest">
                                    <?= esc($n['project']['code']) ?>
                                </text>
                                <text x="<?= $n['x'] ?>" y="<?= $n['y'] + 10 ?>" text-anchor="middle" fill="var(--ink)" class="font-display text-[9px] font-bold uppercase">
                                    <?php
                                    $pName = esc($n['project']['name']);
                                    $pNameParts = explode('//', $pName);
                                    echo esc(substr(trim($pNameParts[0]), 0, 16));
                                    ?>
                                </text>
                            </g>
                        <?php endforeach; ?>

                        <!-- Member Nodes surrounding the center -->
                        <?php foreach ($s['memberNodes'] as $n): ?>
                            <g>
                                <circle cx="<?= $n['x'] ?>" cy="<?= $n['y'] ?>" r="20" fill="<?= $roleHex[$n['member']['role']] ?>" stroke="var(--ink)" stroke-width="0.8" />
                                <text x="<?= $n['x'] ?>" y="<?= $n['y'] + 3 ?>" text-anchor="middle" fill="var(--paper)" class="font-mono text-[9px] font-bold">
                                    <?= esc($n['member']['role']) ?>
                                </text>
                                <text x="<?= $n['x'] ?>" y="<?= $n['y'] + 34 ?>" text-anchor="middle" fill="var(--ink)" class="font-display text-[9px] font-semibold uppercase">
                                    <?= esc($n['member']['name']) ?>
                                </text>
                            </g>
                        <?php endforeach; ?>
                    </g>
                <?php endforeach; ?>
            </svg>
        </div>
    </div>
</div>

<script src="<?= base_url('js/squad_map.js') ?>"></script>
<?= $this->endSection() ?>
