<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<?php
$roleHex = [
    'FE' => '#34d399',
    'BE' => '#fbbf24',
    'QA' => '#f472b6',
    'BA' => '#a78bfa',
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
        // Offset slightly by adding half step
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

<div>
    <!-- Sub-header -->
    <div class="border-b border-foreground bg-background">
        <div class="max-w-[1400px] mx-auto px-6 py-3">
            <a href="<?= base_url('squads') ?>" class="inline-flex items-center gap-2 mono text-[10px] uppercase tracking-widest brutal-border bg-card px-3 py-1.5 brutal-hover">
                <i data-lucide="arrow-left" class="h-3 w-3" stroke-width="3"></i>
                Back to Squads
            </a>
        </div>
    </div>

    <!-- Map Container -->
    <div class="max-w-[1400px] mx-auto p-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
            <div>
                <div class="mono text-[10px] uppercase tracking-[0.3em] text-muted-foreground">
                    Squad // Map
                </div>
                <h1 class="mono text-3xl md:text-4xl font-black uppercase tracking-tight mt-1">
                    Squad ↔ Project Mesh
                </h1>
            </div>
            
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Legend -->
                <div class="flex flex-wrap gap-2">
                    <?php foreach (['FE', 'BE', 'QA', 'BA'] as $role): ?>
                        <div class="flex items-center gap-1.5 border border-foreground px-2 py-1 bg-card">
                            <span class="inline-block w-3 h-3 border border-foreground" style="background: <?= $roleHex[$role] ?>"></span>
                            <span class="mono text-[10px] uppercase tracking-widest"><?= $role ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="flex items-center gap-1.5 border border-foreground px-2 py-1 bg-card">
                        <span class="inline-block w-3 h-[2px] bg-foreground"></span>
                        <span class="mono text-[10px] uppercase tracking-widest">Project</span>
                    </div>
                </div>

                <button onclick="exportSquadMapPdf()" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover flex items-center gap-2">
                    <i data-lucide="download" class="h-3.5 w-3.5" stroke-width="3"></i>
                    Export PDF
                </button>
            </div>
        </div>

        <!-- SVG Map Card -->
        <div id="squad-map-capture" class="brutal-border bg-card p-4 brutal-shadow">
            <svg viewBox="0 0 <?= $W ?> <?= $H ?>" class="w-full h-auto bg-card" style="min-width: 700px;">
                <?php foreach ($squadNodes as $s): ?>
                    <g>
                        <!-- Lines connecting squad center to project boxes -->
                        <?php foreach ($s['projectNodes'] as $n): ?>
                            <line x1="<?= $s['cx'] ?>" y1="<?= $s['cy'] ?>" x2="<?= $n['x'] ?>" y2="<?= $n['y'] ?>" stroke="currentColor" stroke-width="1.5" class="text-foreground/60" />
                        <?php endforeach; ?>

                        <!-- Lines connecting squad center to member circles -->
                        <?php foreach ($s['memberNodes'] as $n): ?>
                            <line x1="<?= $s['cx'] ?>" y1="<?= $s['cy'] ?>" x2="<?= $n['x'] ?>" y2="<?= $n['y'] ?>" stroke="currentColor" stroke-width="1" stroke-dasharray="3 3" class="text-foreground/30" />
                        <?php endforeach; ?>

                        <!-- Central Squad Box -->
                        <g>
                            <rect x="<?= $s['cx'] - 110 ?>" y="<?= $s['cy'] - 32 ?>" width="220" height="64" class="fill-foreground" />
                            <text x="<?= $s['cx'] ?>" y="<?= $s['cy'] - 12 ?>" text-anchor="middle" class="fill-background mono" style="font-size: 9px; letter-spacing: 1px;">
                                SQUAD
                            </text>
                            <text x="<?= $s['cx'] ?>" y="<?= $s['cy'] + 6 ?>" text-anchor="middle" class="fill-background mono" style="font-size: 13px; font-weight: 900; letter-spacing: 1px;">
                                <?= esc(strtoupper($s['squad']['name'])) ?>
                            </text>
                            <text x="<?= $s['cx'] ?>" y="<?= $s['cy'] + 22 ?>" text-anchor="middle" class="fill-background/70 mono" style="font-size: 9px; letter-spacing: 1px;">
                                <?= count($s['projects']) ?> PRJ · <?= count($s['members']) ?> RES
                            </text>
                        </g>

                        <!-- Project Nodes surrounding the center -->
                        <?php foreach ($s['projectNodes'] as $n): ?>
                            <g>
                                <rect x="<?= $n['x'] - 70 ?>" y="<?= $n['y'] - 18 ?>" width="140" height="36" class="fill-card stroke-foreground" stroke-width="1.2" />
                                <text x="<?= $n['x'] ?>" y="<?= $n['y'] - 4 ?>" text-anchor="middle" class="fill-muted-foreground mono" style="font-size: 8px; letter-spacing: 1px;">
                                    <?= esc($n['project']['code']) ?>
                                </text>
                                <text x="<?= $n['x'] ?>" y="<?= $n['y'] + 10 ?>" text-anchor="middle" class="fill-foreground mono" style="font-size: 9px; font-weight: 900;">
                                    <?php
                                    $pName = esc($n['project']['name']);
                                    $pNameParts = explode('//', $pName);
                                    echo esc(substr(trim($pNameParts[0]), 0, 18));
                                    ?>
                                </text>
                            </g>
                        <?php endforeach; ?>

                        <!-- Member Nodes surrounding the center -->
                        <?php foreach ($s['memberNodes'] as $n): ?>
                            <g>
                                <circle cx="<?= $n['x'] ?>" cy="<?= $n['y'] ?>" r="20" fill="<?= $roleHex[$n['member']['role']] ?>" stroke="currentColor" stroke-width="1.2" class="text-foreground" />
                                <text x="<?= $n['x'] ?>" y="<?= $n['y'] + 3 ?>" text-anchor="middle" class="mono" style="font-size: 9px; font-weight: 900;">
                                    <?= esc($n['member']['role']) ?>
                                </text>
                                <text x="<?= $n['x'] ?>" y="<?= $n['y'] + 34 ?>" text-anchor="middle" class="fill-foreground mono" style="font-size: 9px; font-weight: 700;">
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

<script>
    async function exportSquadMapPdf() {
        const { jsPDF } = window.jspdf;
        const element = document.getElementById('squad-map-capture');
        
        const canvas = await html2canvas(element, {
            backgroundColor: "#f5efe1",
            scale: 2
        });
        
        const img = canvas.toDataURL("image/png");
        const pdf = new jsPDF({ orientation: "landscape", unit: "pt", format: "a3" });
        const pageW = pdf.internal.pageSize.getWidth();
        const pageH = pdf.internal.pageSize.getHeight();
        const ratio = Math.min(pageW / canvas.width, pageH / canvas.height);
        const w = canvas.width * ratio;
        const h = canvas.height * ratio;
        pdf.addImage(img, "PNG", (pageW - w) / 2, (pageH - h) / 2, w, h);
        pdf.save("squad-map.pdf");
    }
</script>
<?= $this->endSection() ?>
