<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<?php
$roleHex = [
    'FE' => '#34d399',
    'BE' => '#fbbf24',
    'QA' => '#f472b6',
    'BA' => '#a78bfa',
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

<div>
    <!-- Sub-header / Back button -->
    <div class="border-b border-foreground bg-background">
        <div class="max-w-[1400px] mx-auto px-6 py-3">
            <a href="<?= base_url('resource') ?>" class="inline-flex items-center gap-2 mono text-[10px] uppercase tracking-widest brutal-border bg-card px-3 py-1.5 brutal-hover">
                <i data-lucide="arrow-left" class="h-3 w-3" stroke-width="3"></i>
                Back to Resource
            </a>
        </div>
    </div>

    <!-- Map Container -->
    <div class="max-w-[1400px] mx-auto p-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
            <div>
                <div class="mono text-[10px] uppercase tracking-[0.3em] text-muted-foreground">
                    Resource // Map
                </div>
                <h1 class="mono text-3xl md:text-4xl font-black uppercase tracking-tight mt-1">
                    Project ↔ Resource Mesh
                </h1>
            </div>
            
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Legend -->
                <div class="flex flex-wrap gap-2">
                    <?php foreach (['FE', 'BE', 'QA', 'BA'] as $role): ?>
                        <div class="flex items-center gap-1.5 border border-foreground px-2 py-1 bg-card">
                            <span class="inline-block w-3 h-3 border border-foreground <?= $roleBgs[$role] ?>"></span>
                            <span class="mono text-[10px] uppercase tracking-widest"><?= $role ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button onclick="exportMapPdf()" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover flex items-center gap-2">
                    <i data-lucide="download" class="h-3.5 w-3.5" stroke-width="3"></i>
                    Export PDF
                </button>
            </div>
        </div>

        <!-- SVG Map Card -->
        <div id="map-capture" class="brutal-border bg-card p-4 brutal-shadow">
            <svg viewBox="0 0 <?= $W ?> <?= $H ?>" class="w-full h-auto bg-card" style="min-width: 700px;">
                <?php foreach ($projectNodes as $p): ?>
                    <g>
                        <!-- Lines connecting project center to resource nodes -->
                        <?php foreach ($p['nodes'] as $n): ?>
                            <line x1="<?= $p['cx'] ?>" y1="<?= $p['cy'] ?>" x2="<?= $n['x'] ?>" y2="<?= $n['y'] ?>" stroke="currentColor" stroke-width="1" class="text-foreground/40" />
                        <?php endforeach; ?>

                        <!-- Central Project Box -->
                        <g>
                            <rect x="<?= $p['cx'] - 130 ?>" y="<?= $p['cy'] - 30 ?>" width="260" height="60" class="fill-foreground" />
                            <text x="<?= $p['cx'] ?>" y="<?= $p['cy'] - 10 ?>" text-anchor="middle" class="fill-background mono" style="font-size: 9px; letter-spacing: 1px;">
                                <?= esc($p['code']) ?>
                            </text>
                            <text x="<?= $p['cx'] ?>" y="<?= $p['cy'] + 6 ?>" text-anchor="middle" class="fill-background mono" style="font-size: 11px; font-weight: 900; letter-spacing: 1px;">
                                <?php
                                $nameParts = explode('//', $p['name']);
                                echo esc(trim($nameParts[0]));
                                ?>
                            </text>
                            <text x="<?= $p['cx'] ?>" y="<?= $p['cy'] + 22 ?>" text-anchor="middle" class="fill-background/70 mono" style="font-size: 9px; letter-spacing: 1px;">
                                <?= count($p['nodes']) ?> RESOURCES
                            </text>
                        </g>

                        <!-- Surrounding Resource Nodes -->
                        <?php foreach ($p['nodes'] as $n): ?>
                            <g>
                                <circle cx="<?= $n['x'] ?>" cy="<?= $n['y'] ?>" r="22" fill="<?= $roleHex[$n['resource']['role']] ?>" stroke="currentColor" stroke-width="1.2" class="text-foreground" />
                                <text x="<?= $n['x'] ?>" y="<?= $n['y'] + 3 ?>" text-anchor="middle" class="mono" style="font-size: 9px; font-weight: 900;">
                                    <?= esc($n['resource']['role']) ?>
                                </text>
                                <text x="<?= $n['x'] ?>" y="<?= $n['y'] + 36 ?>" text-anchor="middle" class="fill-foreground mono" style="font-size: 9px; font-weight: 700;">
                                    <?= esc($n['resource']['name']) ?>
                                </text>
                                <text x="<?= $n['x'] ?>" y="<?= $n['y'] + 48 ?>" text-anchor="middle" class="fill-muted-foreground mono" style="font-size: 8px;">
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

<script>
    async function exportMapPdf() {
        const { jsPDF } = window.jspdf;
        const element = document.getElementById('map-capture');
        
        // Temporarily add class for print colors if needed, html2canvas handles OKLCH colors, 
        // but setting scale 2 ensures high res rendering
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
        pdf.save("resource-map.pdf");
    }
</script>
<?= $this->endSection() ?>
