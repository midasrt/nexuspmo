<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<style>
.timeline-grid {
    display: grid !important;
    grid-template-columns: 200px repeat(var(--num-months, 12), minmax(64px, 1fr)) !important;
}
.timeline-grid.hidden {
    display: none !important;
}
</style>
<?php
if (!function_exists('getUtilColor')) {
    function getUtilColor($u) {
        if ($u >= 85) return 'bg-status-blocked';
        if ($u >= 65) return 'bg-status-ontrack';
        if ($u >= 40) return 'bg-status-atrisk';
        return 'bg-status-backlog';
    }
}
$db = \Config\Database::connect();
$projStatuses = $db->tableExists('projects') ? $db->table('projects')->select('status')->distinct()->get()->getResultArray() : [];
$statusDefs = $db->tableExists('status_definitions') ? $db->table('status_definitions')->get()->getResultArray() : [];

$statusLabels = [];
$statusBgs = [];
$statusColors = [
    'on-track' => 'oklch(0.62 0.14 155)',
    'at-risk'  => 'oklch(0.78 0.16 80)',
    'blocked'  => 'oklch(0.58 0.22 27)',
    'delayed'  => 'oklch(0.65 0.18 35)',
    'backlog'  => 'oklch(0.6 0.02 270)',
];

foreach ($statusDefs as $def) {
    $statusLabels[$def['status']] = $def['label'];
    $statusBgs[$def['status']] = 'bg-status-' . $def['status'];
    if (!empty($def['color'])) {
        $statusColors[$def['status']] = $def['color'];
    }
}

foreach ($projStatuses as $ps) {
    $slug = $ps['status'];
    if (!isset($statusLabels[$slug]) && !empty($slug)) {
        $statusLabels[$slug] = strtoupper(str_replace('-', ' ', $slug));
        $statusBgs[$slug] = 'bg-status-' . $slug;
    }
}

// Ensure default fallback items exist in case table is empty or missing keys
$defaultSlugs = ['on-track', 'at-risk', 'blocked', 'delayed', 'backlog'];
$defaultLabels = ['ON TRACK', 'AT RISK', 'BLOCKED', 'DELAYED', 'BACKLOG'];
foreach ($defaultSlugs as $i => $s) {
    if (!isset($statusLabels[$s])) $statusLabels[$s] = $defaultLabels[$i];
    if (!isset($statusBgs[$s])) $statusBgs[$s] = 'bg-status-' . $s;
}

// Timeline calculations helpers
$currentYear = (int)date('Y');
$startDate = $project['startDate'];
$endDate = $project['endDate'];

$startYear = (int)date('Y', strtotime($startDate));
$endYear = (int)date('Y', strtotime($endDate));
$startMonth = (int)date('n', strtotime($startDate));
$endMonth = (int)date('n', strtotime($endDate));

$numMonths = (($endYear - $startYear) * 12) + ($endMonth - $startMonth) + 1;
if ($numMonths < 1) $numMonths = 1;

$timelineMonths = [];
for ($i = 0; $i < $numMonths; $i++) {
    $m = ($startMonth - 1 + $i) % 12 + 1;
    $y = $startYear + (int)(($startMonth - 1 + $i) / 12);
    $monthsList = ["JAN","FEB","MAR","APR","MAY","JUN","JUL","AUG","SEP","OCT","NOV","DEC"];
    $timelineMonths[] = [
        'label' => $monthsList[$m - 1],
        'month' => $m,
        'year'  => $y
    ];
}

$yearSpans = [];
foreach ($timelineMonths as $m) {
    if (!isset($yearSpans[$m['year']])) $yearSpans[$m['year']] = 0;
    $yearSpans[$m['year']]++;
}

$timelineStartStr = "{$startYear}-" . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . "-01";
$timelineEndStr = date('Y-m-t', strtotime("{$endYear}-" . str_pad($endMonth, 2, '0', STR_PAD_LEFT) . "-01"));

$timelineStartMs = strtotime($timelineStartStr);
$timelineEndMs = strtotime($timelineEndStr);
$totalTimelineMs = $timelineEndMs - $timelineStartMs;
if ($totalTimelineMs < 1) $totalTimelineMs = 1;

$todayPct = null;
$now = time();
if ($now >= $timelineStartMs && $now <= $timelineEndMs) {
    $todayPct = (($now - $timelineStartMs) / $totalTimelineMs) * 100;
}
?>

<div class="min-h-screen pb-24">
    <!-- Header Component -->
    <header class="border-b border-ink/15 py-6 no-print cascade-in" style="animation-delay: 0ms;">
        <div class="w-full px-8 lg:px-14 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('projects') ?>" class="rounded-lg border border-ink/10 bg-background hover:bg-secondary p-2 text-ink transition-colors" title="Back to Registry">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                </a>
                <div>
                    <span class="eyebrow"><?= esc($project['code']) ?></span>
                    <h1 class="font-display text-2xl md:text-3xl font-black uppercase mt-1 tracking-tight"><?= esc($project['name']) ?></h1>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="openEditHealthModal()" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                    Edit Details
                </button>
            </div>
        </div>
    </header>

    <section class="w-full px-8 lg:px-14 space-y-8 mt-8">
        <?php
        $openEscalationsCount = 0;
        if (!empty($project['escalations'])) {
            foreach ($project['escalations'] as $e) {
                if (($e['status'] ?? 'active') === 'active') {
                    $openEscalationsCount++;
                }
            }
        }

        $openRisksCount = 0;
        if (!empty($project['risks'])) {
            foreach ($project['risks'] as $r) {
                if (($r['status'] ?? 'active') === 'active') {
                    $openRisksCount++;
                }
            }
        }
        ?>
        <!-- Snappy KPI Strip -->
        <div id="stats-container" class="grid grid-cols-2 md:grid-cols-6 gap-6 py-6 border-y border-ink/15 cascade-in" style="animation-delay: 50ms;">
            <div>
                <span class="eyebrow text-xs">Project Status</span>
                <span class="flex items-center gap-1.5 mt-2 text-sm font-mono uppercase font-black whitespace-nowrap">
                    <span class="h-2 w-2 rounded-full animate-pulse shrink-0" style="background: <?= $statusColors[$project['status']] ?? '#6B7280' ?>"></span>
                    <?= $statusLabels[$project['status']] ?? strtoupper($project['status']) ?>
                </span>
            </div>
            <div>
                <span class="eyebrow text-xs">Squad Assignee</span>
                <div class="font-display text-lg font-bold mt-1 text-ink">
                    <?php
                    $squadName = '-- NO SQUAD ASSIGNED --';
                    if (!empty($project['squad'])) {
                        foreach ($allSquads as $sq) {
                            if ($project['squad'] == $sq['id'] || $project['squad'] === $sq['name']) {
                                $squadName = $sq['name'];
                                break;
                            }
                        }
                    }
                    echo esc($squadName);
                    ?>
                </div>
            </div>
            <div>
                <span class="eyebrow text-xs">Lead / Owner</span>
                <div class="font-display text-lg font-bold mt-1 text-ink"><?= esc($project['owner']) ?></div>
            </div>
            <div>
                <span class="eyebrow text-xs">Overall Progress</span>
                <div class="font-display text-lg font-bold mt-1 text-ink"><?= $project['progress'] ?>%</div>
            </div>
            <div>
                <span class="eyebrow text-xs">Open Escalations</span>
                <div class="font-display text-lg font-bold mt-1 text-ink">
                    <a href="#escalations-container" class="hover:underline flex items-center gap-1.5">
                        <?= $openEscalationsCount ?>
                        <?php if ($openEscalationsCount > 0): ?>
                            <span class="h-2 w-2 rounded-full bg-status-blocked shrink-0 animate-pulse" style="background: oklch(0.58 0.22 27)"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            <div>
                <span class="eyebrow text-xs">Open Risks</span>
                <div class="font-display text-lg font-bold mt-1 text-ink">
                    <a href="#risks-container" class="hover:underline flex items-center gap-1.5">
                        <?= $openRisksCount ?>
                        <?php if ($openRisksCount > 0): ?>
                            <span class="h-2 w-2 rounded-full bg-status-atrisk shrink-0 animate-pulse" style="background: oklch(0.78 0.16 80)"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Collapsible Legend -->
        <div class="rounded-2xl border border-ink/15 bg-white dark:bg-card/50 p-4 shadow-sm">
            <button onclick="toggleLegend()" class="w-full flex items-center justify-between font-black mono text-xs uppercase tracking-wider focus:outline-none text-ink">
                <span class="flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4"></i>
                    Status Legend & Criteria
                </span>
                <span id="legend-toggle-icon">[+] Expand</span>
            </button>
            <div id="legend-content" class="hidden mt-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 border-t border-dashed border-ink/15 pt-3">
                <?php foreach ($statusDefs as $def): ?>
                    <div class="p-3 border border-ink/10 bg-background/50 rounded-xl flex flex-col gap-1">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 inline-block rounded-full shrink-0" style="background-color: <?= esc($def['color']) ?>"></span>
                            <span class="mono text-[10px] font-black uppercase text-ink"><?= esc($def['label']) ?></span>
                        </div>
                        <p class="mono text-[9px] text-muted-foreground leading-normal mt-1"><?= esc($def['criteria']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Animated Timeline Gantt -->
        <div id="timeline-container" class="space-y-4" style="--num-months: <?= $numMonths ?>;">
            <div class="flex items-end justify-between mb-2">
                <h2 class="font-display text-2xl tracking-tight text-ink">Project Timeline</h2>
                <div class="flex items-center gap-3">
                    <button onclick="toggleAllTimelineSubtasks(this)" id="toggle-all-timeline-subtasks-btn" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-3 py-1.5 text-xs font-mono uppercase tracking-widest">
                        Show Subtasks
                    </button>
                    <span class="eyebrow">Phase spans</span>
                </div>
            </div>

            <div class="rounded-2xl border border-ink/15 bg-white dark:bg-card/70 overflow-hidden shadow-sm">
                <div class="min-w-[800px] relative">
                    <!-- Year Headers -->
                    <div class="timeline-grid border-b border-ink/15 relative bg-white dark:bg-card/70">
                        <div class="px-5 py-3 eyebrow border-r border-ink/10 bg-white dark:bg-card/70">Phase</div>
                        <?php foreach ($yearSpans as $yr => $spanCount): ?>
                            <div class="col-span-<?= $spanCount ?> border-r border-ink/10 last:border-r-0 p-3 eyebrow text-center bg-white dark:bg-secondary/30">// <?= $yr ?></div>
                        <?php endforeach; ?>
                        
                        <?php if ($todayPct !== null): ?>
                            <div class="absolute top-0 bottom-0 w-px bg-ink z-30" style="left: calc(200px + (100% - 200px) * <?= $todayPct / 100 ?>)">
                                <span class="absolute -top-1 -translate-x-1/2 w-2 h-2 rounded-full bg-ink" />
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Month Headers -->
                    <div class="timeline-grid border-b border-ink/15 bg-white dark:bg-card/70">
                        <div class="border-r border-ink/10 p-3 eyebrow bg-white dark:bg-card/70">Bar = phase span</div>
                        <?php foreach ($timelineMonths as $index => $m): ?>
                            <div class="p-3 text-[11px] font-mono uppercase tracking-widest text-center text-muted-foreground border-r border-ink/10 last:border-r-0 bg-white dark:bg-card/70">
                                <span class="text-ink/80"><?= $m['label'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Timeline Content Rows -->
                    <div class="relative">
                        <?php if ($todayPct !== null): ?>
                            <div class="absolute top-0 bottom-0 w-px bg-ink z-20 pointer-events-none" style="left: calc(200px + (100% - 200px) * <?= $todayPct / 100 ?>)"></div>
                        <?php endif; ?>

                        <?php if (empty($project['phases'])): ?>
                            <div class="p-8 text-center text-muted-foreground uppercase font-mono text-xs">No phases configured.</div>
                        <?php else: ?>
                            <?php foreach ($project['phases'] as $ph): ?>
                                <div class="phase-group border-b border-ink/10 last:border-b-0">
                                    <div class="timeline-grid border-b border-ink/5 last:border-b-0 w-full text-left items-stretch hover:bg-ink/[0.02] transition-colors bg-white dark:bg-card/70">
                                        <!-- Phase Left Col -->
                                        <div class="border-r border-ink/10 p-4 flex flex-col gap-1 bg-white dark:bg-card/30 z-10">
                                            <div class="flex items-center gap-1.5">
                                                <?php if (!empty($ph['subtasks'])): ?>
                                                    <button onclick="toggleSingleTimelineSubtask(<?= $ph['id'] ?>, this)" class="p-0.5 border border-ink/20 rounded hover:bg-secondary transition-colors" title="Toggle Subtasks">
                                                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 transition-transform duration-200" id="timeline-chevron-<?= $ph['id'] ?>"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <span class="font-display text-[14px] font-bold uppercase tracking-tight truncate text-ink" title="<?= esc($ph['name']) ?>"><?= esc($ph['name']) ?></span>
                                            </div>
                                            <span class="text-[10px] font-mono text-muted-foreground"><?= sys_date($ph['start']) ?> → <?= sys_date($ph['end']) ?></span>
                                        </div>

                                        <!-- Phase Bar Track -->
                                        <div class="[grid-column:span_<?= $numMonths ?>] relative grid h-[68px] bg-white dark:bg-card/70" style="grid-template-columns: repeat(<?= $numMonths ?>, minmax(0, 1fr))">
                                            <?php for ($i = 0; $i < $numMonths; $i++): ?>
                                                <div class="border-r border-ink/[0.06] last:border-r-0"></div>
                                            <?php endfor; ?>

                                            <?php
                                            $s = strtotime($ph['start']);
                                            $e = strtotime($ph['end']);
                                            $left = (($s - $timelineStartMs) / $totalTimelineMs) * 100;
                                            $width = (($e - $s) / $totalTimelineMs) * 100;
                                            if ($left < 0) { $width += $left; $left = 0; }
                                            if ($left + $width > 100) $width = 100 - $left;
                                            if ($width < 2) $width = 2;
                                            $statusColorVal = $statusColors[$ph['status']] ?? 'var(--muted)';
                                            ?>
                                            <div class="absolute top-1/2 -translate-y-1/2 h-7 rounded-full border border-ink/20 overflow-hidden flex items-center shadow-xs" style="left: <?= $left ?>%; width: <?= $width ?>%; background: lab(99.078% 0.111103 0.751424);">
                                                <div class="h-full absolute left-0 top-0 transition-all duration-300" style="width: <?= $ph['progress'] ?>%; background: <?= $statusColorVal ?>;"></div>
                                                <div class="absolute inset-0 flex items-center justify-between px-3 text-[10px] font-mono uppercase tracking-widest font-bold z-10"
                                                     style="color: color-mix(in oklab, var(--ink) 80%, transparent)">
                                                    <span class="px-2 py-0.5 rounded bg-black/5"><?= $statusLabels[$ph['status']] ?? strtoupper($ph['status']) ?></span>
                                                    <span><?= $ph['progress'] ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Subtasks Nested Gantt Rows -->
                                    <?php if (!empty($ph['subtasks'])): ?>
                                        <?php foreach ($ph['subtasks'] as $sub): 
                                            $subStart = strtotime($sub['start']);
                                            $subEnd = strtotime($sub['end']);
                                            $subLeft = (($subStart - $timelineStartMs) / $totalTimelineMs) * 100;
                                            $subWidth = (($subEnd - $subStart) / $totalTimelineMs) * 100;
                                            if ($subLeft < 0) { $subWidth += $subLeft; $subLeft = 0; }
                                            if ($subLeft + $subWidth > 100) $subWidth = 100 - $subLeft;
                                            if ($subWidth < 2) $subWidth = 2;
                                            $subColorVal = $statusColors[$sub['status']] ?? 'var(--muted)';
                                        ?>
                                            <div class="timeline-subtask-row-<?= $ph['id'] ?> hidden timeline-grid border-b border-ink/5 w-full text-left bg-white dark:bg-ink/[0.01] hover:bg-secondary/20 transition-colors">
                                                <div class="border-r border-ink/10 p-3 pl-12 flex flex-col justify-center bg-white dark:bg-card/10 z-10 relative">
                                                    <div class="absolute left-6 top-0 bottom-0 w-px bg-ink/10"></div>
                                                    <span class="font-display text-[12px] font-bold text-ink uppercase tracking-tight truncate"><span class="mono text-[10px] text-muted-foreground mr-1">#<?= $sub['id'] ?></span><?= esc($sub['name']) ?></span>
                                                    <span class="text-[9px] font-mono text-muted-foreground"><?= sys_date($sub['start']) ?> → <?= sys_date($sub['end']) ?></span>
                                                </div>
                                                <div class="[grid-column:span_<?= $numMonths ?>] relative h-[48px] bg-white dark:bg-card/70" style="grid-template-columns: repeat(<?= $numMonths ?>, minmax(0, 1fr))">
                                                    <?php for ($i = 0; $i < $numMonths; $i++): ?>
                                                        <div class="border-r border-ink/[0.04] last:border-r-0"></div>
                                                    <?php endfor; ?>
                                                    <div class="absolute top-1/2 -translate-y-1/2 h-5 rounded-full border border-ink/10 overflow-hidden flex items-center shadow-xs" style="left: <?= $subLeft ?>%; width: <?= $subWidth ?>%; background: <?= $subColorVal ?>;">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Info Columns -->
        <div class="grid grid-cols-1 lg:grid-cols-[1.4fr_1fr] gap-10">
            <!-- Left Side: Phase & Task List (Sortable) -->
            <div id="phases-container" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-display text-xl text-ink font-bold">Phase order & tasks</h3>
                    <div class="flex items-center gap-2">
                        <button onclick="toggleAllTableSubtasks(this)" id="toggle-all-table-subtasks-btn-2" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-3 py-1.5 text-xs font-mono uppercase tracking-widest">
                            Show Subtasks
                        </button>
                        <button onclick="openCreatePhaseModal()" class="rounded-full border border-ink/20 bg-ink text-paper hover:bg-ink/90 px-3 py-1.5 text-xs font-mono uppercase tracking-widest font-bold">
                            + Add Phase
                        </button>
                    </div>
                </div>

                <ul class="phase-drag-body space-y-3">
                    <?php if (empty($project['phases'])): ?>
                        <li class="rounded-2xl border border-ink/15 bg-white dark:bg-card/70 p-6 text-center text-muted-foreground uppercase font-mono text-xs shadow-sm">No phases mapped.</li>
                    <?php else: ?>
                        <?php foreach ($project['phases'] as $phIndex => $ph): ?>
                            <li class="phase-drag-row rounded-2xl border border-ink/15 bg-white dark:bg-card/70 p-5 flex flex-col gap-3 transition-shadow cursor-grab active:cursor-grabbing shadow-sm" draggable="true" data-id="<?= $ph['id'] ?>">
                                <div class="flex items-start gap-3">
                                    <button class="cursor-grab active:cursor-grabbing text-ink/40 hover:text-ink mt-0.5" aria-label="Drag">
                                        <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                                    </button>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between flex-wrap gap-2">
                                            <div class="flex items-center gap-2">
                                                <button onclick="toggleSubtasks(<?= $ph['id'] ?>, this)" class="p-0.5 border border-ink/20 rounded hover:bg-secondary transition-colors" title="Toggle Tasks">
                                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 transition-transform duration-200" id="chevron-<?= $ph['id'] ?>"></i>
                                                </button>
                                                <div>
                                                    <span class="font-mono text-[10px] tracking-widest text-muted-foreground">PHASE 0<?= $phIndex + 1 ?></span>
                                                    <h4 class="font-display text-lg leading-tight font-black uppercase text-ink mt-0.5"><?= esc($ph['name']) ?></h4>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="mono text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full border border-ink/20 text-ink bg-secondary/50 flex items-center gap-1.5">
                                                    <span class="h-1.5 w-1.5 rounded-full shrink-0 animate-pulse" style="background-color: <?= $statusColors[$ph['status']] ?? '#6B7280' ?>;"></span>
                                                    <?= $statusLabels[$ph['status']] ?? strtoupper($ph['status']) ?>
                                                </span>
                                                <span class="font-display text-lg font-bold"><?= $ph['progress'] ?>%</span>
                                            </div>
                                        </div>

                                        <!-- Phase Progress Bar -->
                                        <div class="mt-3 h-1.5 bg-ink/10 rounded-full overflow-hidden">
                                            <div class="h-full bg-ink" style="width: <?= $ph['progress'] ?>%"></div>
                                        </div>

                                        <!-- Subtasks list -->
                                        <ul class="subtask-list-box-<?= $ph['id'] ?> hidden mt-4 space-y-2.5 border-t border-ink/10 pt-3" id="subtask-list-box-<?= $ph['id'] ?>">
                                            <?php if (!empty($ph['subtasks'])): ?>
                                                <?php foreach ($ph['subtasks'] as $sub): 
                                                    $isDone = in_array(strtolower($sub['status']), ['complete', 'completed', 'done']);
                                                ?>
                                                    <li class="flex items-center justify-between text-sm gap-2">
                                                        <span class="flex items-center gap-2.5">
                                                            <button type="button" onclick="toggleSubtaskStatus('<?= $project['id'] ?>', <?= $ph['id'] ?>, <?= $sub['id'] ?>)" class="h-4 w-4 rounded border flex items-center justify-center shrink-0 <?= $isDone ? 'bg-ink border-ink text-paper' : 'border-ink/30' ?> hover:border-ink transition-colors cursor-pointer" title="Toggle Completion">
                                                                <?php if ($isDone): ?>✓<?php endif; ?>
                                                            </button>
                                                            <span class="text-ink <?= $isDone ? 'line-through text-muted-foreground' : 'font-bold uppercase text-xs' ?>">
                                                                <span class="mono text-[10px] text-muted-foreground normal-case font-normal mr-1">#<?= $sub['id'] ?></span>
                                                                <?= esc($sub['name']) ?>
                                                                <span class="mono text-[9px] text-muted-foreground normal-case font-normal"> (<?= sys_date($sub['start']) ?> - <?= sys_date($sub['end']) ?>)</span>
                                                            </span>
                                                        </span>
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-mono text-[10px] text-muted-foreground"><?= esc($sub['resource_name'] ?: 'unassigned') ?></span>
                                                            <button title="Edit Subtask" onclick='openEditSubtaskModal(<?= json_encode($sub) ?>, <?= $ph['id'] ?>)' class="text-ink/60 hover:text-ink">
                                                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                                            </button>
                                                            <button title="Delete Subtask" onclick="deleteAjaxItem('<?= base_url('project/phase/subtask/delete') ?>/<?= $sub['id'] ?>')" class="text-destructive hover:opacity-85">
                                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                            </button>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <li class="pt-1 flex items-center justify-between gap-2">
                                                <button onclick="openCreateSubtaskModal(<?= $ph['id'] ?>)" class="text-[10px] font-mono uppercase tracking-widest text-ink hover:underline flex items-center gap-1">
                                                    + Add Task / Subtask
                                                </button>
                                                <div class="flex items-center gap-1.5">
                                                    <button onclick='openEditPhaseModal(<?= json_encode($ph) ?>)' class="text-[10px] font-mono uppercase tracking-widest text-ink hover:underline flex items-center gap-1">
                                                        Edit Phase
                                                    </button>
                                                    <button onclick="deleteAjaxItem('<?= base_url('project/phase/delete') ?>/<?= $ph['id'] ?>')" class="text-[10px] font-mono uppercase tracking-widest text-destructive hover:underline flex items-center gap-1">
                                                        Delete Phase
                                                    </button>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Right Side: Clean Panels (Dependencies, Risks, Escalations, Team, Completion) -->
            <aside class="space-y-6">
                <!-- Team Section -->
                <div id="resources-container" class="rounded-2xl border border-ink/15 bg-white dark:bg-card/70 p-5 shadow-sm">
                    <h3 class="font-display text-lg text-ink font-bold mb-4">Team & Resource mapping</h3>
                    
                    <!-- Squad Assignment Form -->
                    <form id="assign-squad-form-right" class="flex gap-2 mb-4 pb-4 border-b border-ink/10" onsubmit="event.preventDefault(); handleSquadSubmit(this);">
                        <select name="squad_id" data-original="<?= esc($project['squad'] ?? '') ?>" class="flex-1 rounded-xl border border-ink/20 bg-background px-3 py-1.5 text-xs focus:outline-none">
                            <option value="">-- NO SQUAD ASSIGNED --</option>
                            <?php foreach ($allSquads as $sq): ?>
                                <option value="<?= $sq['id'] ?>" <?= ($project['squad'] ?? '') == $sq['id'] ? 'selected' : '' ?>><?= esc($sq['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="rounded-xl bg-ink text-paper px-3 py-1.5 text-xs font-mono uppercase tracking-widest font-bold">Set Squad</button>
                    </form>

                    <form id="assign-resource-form-right" class="flex gap-2 mb-4" onsubmit="event.preventDefault(); submitAjaxForm('assign-resource-form-right', '<?= base_url("project/{$project['id']}/resources/assign") ?>');">
                        <select name="resource_id" required class="flex-1 rounded-xl border border-ink/20 bg-background px-3 py-1.5 text-xs focus:outline-none">
                            <option value="">-- ASSIGN TEAM MEMBER --</option>
                            <?php foreach ($allResources as $res): ?>
                                <option value="<?= $res['id'] ?>"><?= esc($res['name']) ?> [<?= esc($res['role']) ?>]</option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="rounded-xl bg-ink text-paper px-3 py-1.5 text-xs font-mono uppercase tracking-widest font-bold">Add</button>
                    </form>

                    <?php if (empty($assignedResources)): ?>
                        <div class="text-xs text-muted-foreground uppercase font-mono text-center py-2">No team mapped.</div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($assignedResources as $r): ?>
                                <?php
                                $initials = '';
                                $names = explode(' ', $r['name']);
                                foreach ($names as $n) { $initials .= strtoupper(substr($n, 0, 1)); }
                                $initials = substr($initials, 0, 2);
                                ?>
                                <div class="flex flex-col gap-2 rounded-xl border border-ink/10 bg-ink/[0.02] p-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="grid h-7 w-7 place-items-center rounded-full bg-ink text-paper font-display text-[10px] font-bold"><?= $initials ?></span>
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold text-ink"><?= esc($r['name']) ?></span>
                                                <span class="text-[9px] uppercase tracking-wider text-muted-foreground font-mono"><?= esc($r['role']) ?></span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-mono font-bold"><?= $r['utilization'] ?>%</span>
                                            <button type="button" onclick="unassignResource('<?= $project['id'] ?>', '<?= $r['id'] ?>')" class="text-ink/40 hover:text-ink ml-1 font-bold">✕</button>
                                        </div>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-ink/10 overflow-hidden">
                                        <div class="h-full <?= getUtilColor($r['utilization']) ?>" style="width: <?= min(100, $r['utilization']) ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Dependencies Section -->
                <div id="dependencies-container" class="rounded-2xl border border-ink/15 bg-white dark:bg-card/70 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-display text-lg text-ink font-bold">Dependencies</h3>
                        <button onclick="openCreateDependencyModal()" class="rounded-xl bg-ink text-paper px-3 py-1.5 text-xs font-mono uppercase tracking-widest font-bold">+ Add</button>
                    </div>
                    <?php if (empty($project['dependencies'])): ?>
                        <div class="text-xs text-muted-foreground uppercase font-mono text-center py-2">No upstream dependencies.</div>
                    <?php else: ?>
                        <ul class="space-y-3">
                            <?php foreach ($project['dependencies'] as $d): ?>
                                <li class="flex items-center justify-between border-b border-ink/10 pb-2 last:border-b-0">
                                    <div>
                                        <span class="font-mono text-[10px] text-muted-foreground"><?= esc($d['dep_project_name']) ?></span>
                                        <div class="text-[10px] font-mono text-muted-foreground uppercase"><?= $d['type'] === 'depends-on' ? '→ depends on' : '← blocks' ?></div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="h-1.5 w-1.5 rounded-full" style="background: <?= $statusColors[$d['status']] ?? '#6B7280' ?>" />
                                        <button onclick="deleteAjaxItem('<?= base_url('project/dependency/delete') ?>/<?= $d['id'] ?>')" class="text-destructive hover:opacity-80 ml-1">✕</button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Risks & Issues Section -->
                <div id="risks-container" class="rounded-2xl border border-ink/15 bg-white dark:bg-card/70 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-display text-lg text-ink font-bold">Risks & Issues</h3>
                        <button onclick="openCreateRiskModal()" class="rounded-xl bg-ink text-paper px-3 py-1.5 text-xs font-mono uppercase tracking-widest font-bold">+ Add</button>
                    </div>
                    <?php if (empty($project['risks'])): ?>
                        <div class="text-xs text-muted-foreground uppercase font-mono text-center py-2">Clear skies.</div>
                    <?php else: ?>
                        <ul class="space-y-3">
                            <?php foreach ($project['risks'] as $r): ?>
                                <li class="border-l-2 pl-4 py-1 flex flex-col justify-between" style="border-color: <?= $r['severity'] === 'high' ? 'var(--status-blocked)' : ($r['severity'] === 'med' ? 'var(--status-atrisk)' : 'var(--status-backlog)') ?>">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-display text-sm font-bold text-ink leading-tight"><?= esc($r['title']) ?></span>
                                        <button onclick="deleteAjaxItem('<?= base_url('project/risk/delete') ?>/<?= $r['id'] ?>')" class="text-destructive text-xs">✕</button>
                                    </div>
                                    <p class="text-xs text-muted-foreground mt-1"><?= esc($r['mitigation']) ?></p>
                                    <span class="eyebrow text-[9px] mt-1.5 flex items-center gap-1"><i data-lucide="alert-triangle" class="w-3 h-3"></i> <?= esc($r['severity']) ?> severity</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Escalations Section -->
                <div id="escalations-container" class="rounded-2xl border border-ink/15 bg-white dark:bg-card/70 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-display text-lg text-ink font-bold">Escalations</h3>
                        <button onclick="openCreateEscalationModal()" class="rounded-xl bg-ink text-paper px-3 py-1.5 text-xs font-mono uppercase tracking-widest font-bold">+ Add</button>
                    </div>
                    <?php if (empty($project['escalations'])): ?>
                        <div class="text-xs text-muted-foreground uppercase font-mono text-center py-2">None required.</div>
                    <?php else: ?>
                        <ul class="space-y-3">
                            <?php foreach ($project['escalations'] as $e): ?>
                                <li class="border-b border-ink/10 pb-3 last:border-b-0 flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-display text-sm font-bold text-ink"><?= esc($e['note']) ?></div>
                                        <div class="eyebrow text-[9px] mt-1">To <?= esc($e['to_recipient']) ?> · Lvl <?= esc($e['level']) ?></div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="text-[9px] font-mono text-muted-foreground"><?= sys_date($e['date']) ?></span>
                                        <button onclick="deleteAjaxItem('<?= base_url('project/escalation/delete') ?>/<?= $e['id'] ?>')" class="text-destructive hover:opacity-85 text-xs">✕</button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Completion Summary -->
                <div class="rounded-2xl border border-ink/15 bg-white dark:bg-card/70 p-5 shadow-sm">
                    <h3 class="font-display text-lg text-ink font-bold mb-4">Expected completion</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <?php if (!empty($project['phases'])): ?>
                            <?php foreach ($project['phases'] as $ph): ?>
                                <div class="border-t border-ink/10 pt-2.5">
                                    <div class="eyebrow text-[9px]"><?= esc($ph['name']) ?></div>
                                    <div class="font-display text-[15px] font-bold text-ink mt-1"><?= sys_date($ph['end']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="border-t-2 border-ink pt-2.5 col-span-2 mt-1">
                            <div class="eyebrow">Project End Target</div>
                            <div class="font-display text-2xl font-black text-ink mt-1"><?= sys_date($project['endDate']) ?></div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Historical Log & Narrative Chronicles -->
        <div id="status-history-container" class="space-y-4">
            <div class="flex items-end justify-between border-b border-ink/15 pb-2">
                <h2 class="font-display text-2xl tracking-tight text-ink flex items-center gap-2"><i data-lucide="history" class="w-5 h-5"></i> Historical Log</h2>
                <span class="eyebrow">Everything that ever happened</span>
            </div>

            <!-- Tab Headers -->
            <div class="flex gap-2">
                <button onclick="switchHistoryTab('chronicle')" id="tab-btn-chronicle" class="px-4 py-2 rounded-t-xl font-mono text-xs uppercase font-bold bg-ink text-paper">Chronicle</button>
                <button onclick="switchHistoryTab('compare')" id="tab-btn-compare" class="px-4 py-2 rounded-t-xl font-mono text-xs uppercase font-bold bg-secondary/35 text-ink">Comparative Log</button>
                <button onclick="switchHistoryTab('raw')" id="tab-btn-raw" class="px-4 py-2 rounded-t-xl font-mono text-xs uppercase font-bold bg-secondary/35 text-ink">Raw Logs</button>
            </div>

            <!-- Tab Content Blocks -->
            <!-- 1. Chronicle narrative -->
            <div id="history-tab-chronicle" class="rounded-2xl border border-ink/15 bg-white dark:bg-card/70 p-6 max-h-[400px] overflow-y-auto no-scrollbar shadow-sm">
                <div class="space-y-6 max-w-3xl mx-auto">
                    <?php if (empty($project['narratives'])): ?>
                        <p class="text-xs text-muted-foreground uppercase text-center py-6 font-mono">The archives are silent.</p>
                    <?php else: ?>
                        <?php foreach ($project['narratives'] as $n): ?>
                            <div class="border-l border-ink/20 pl-4 py-1">
                                <span class="mono text-[9px] text-muted-foreground uppercase block mb-1">// <?= sys_date($n['date']) ?></span>
                                <p class="italic text-sm text-ink leading-relaxed font-medium">"<?= esc($n['sentence']) ?>"</p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Comparative log -->
            <div id="history-tab-compare" class="hidden rounded-2xl border border-ink/15 bg-white dark:bg-card/70 p-5 max-h-[400px] overflow-y-auto no-scrollbar shadow-sm">
                <?php if (empty($project['detailedHistory'])): ?>
                    <p class="text-xs text-muted-foreground uppercase text-center py-6 font-mono">No comparisons found.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left mono text-xs">
                            <thead>
                                <tr class="border-b border-ink/20">
                                    <th class="p-2 font-black uppercase text-[10px] w-24">Date</th>
                                    <th class="p-2 font-black uppercase text-[10px] w-48">Event</th>
                                    <th class="p-2 font-black uppercase text-[10px]">Changes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($project['detailedHistory'] as $h): 
                                    $old = json_decode($h['old_state'], true) ?: [];
                                    $new = json_decode($h['new_state'], true) ?: [];
                                    $changes = [];
                                    $trackedKeys = [
                                        'status'            => 'Status',
                                        'progress'          => 'Progress',
                                        'phases_count'      => 'Phases',
                                        'subtasks_count'    => 'Subtasks',
                                        'action_items_count'=> 'Action Items',
                                        'risks_count'       => 'Risks',
                                        'escalations_count' => 'Escalations',
                                        'resources_count'   => 'Assigned Resources'
                                    ];
                                    foreach ($trackedKeys as $k => $label) {
                                        $oldVal = isset($old[$k]) ? $old[$k] : '—';
                                        $newVal = isset($new[$k]) ? $new[$k] : '—';
                                        if ($oldVal !== $newVal) {
                                            $changes[] = "<strong>{$label}</strong>: {$oldVal} → {$newVal}";
                                        }
                                    }
                                ?>
                                    <tr class="border-b border-ink/10 last:border-b-0 hover:bg-ink/[0.01]">
                                        <td class="p-2 font-mono text-muted-foreground"><?= sys_date($h['date']) ?></td>
                                        <td class="p-2 uppercase font-bold text-ink"><?= esc($h['action'] ?? 'update') ?></td>
                                        <td class="p-2 text-ink/80"><?= implode(', ', $changes) ?: '—' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 3. Raw Log list -->
            <div id="history-tab-raw" class="hidden rounded-2xl border border-ink/15 bg-white dark:bg-card/70 p-5 max-h-[400px] overflow-y-auto no-scrollbar shadow-sm">
                <?php if (empty($project['statusHistory'])): ?>
                    <p class="text-xs text-muted-foreground uppercase text-center py-6 font-mono">No raw logs recorded.</p>
                <?php else: ?>
                    <ul class="space-y-3">
                        <?php foreach ($project['statusHistory'] as $h): ?>
                            <li class="border-b border-ink/10 pb-2 last:border-b-0 flex items-center justify-between gap-3 text-xs">
                                <div>
                                    <span class="font-bold text-ink uppercase"><?= esc($h['note']) ?></span>
                                    <span class="mono text-[10px] text-muted-foreground ml-2">(<?= sys_date($h['date']) ?>)</span>
                                </div>
                                <span class="h-1.5 w-1.5 rounded-full" style="background: <?= $statusColors[$h['status']] ?? '#6B7280' ?>" />
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Documents Upload Component -->
        <div class="rounded-2xl border border-ink/15 bg-white dark:bg-card/70 p-5 space-y-4 shadow-sm">
            <h3 class="font-display text-lg text-ink font-bold">Documents & uploads</h3>
            <form id="upload-documents-form" action="<?= base_url("project/{$project['id']}/upload") ?>" method="POST" enctype="multipart/form-data" class="flex items-center justify-between gap-4 flex-wrap border-b border-ink/10 pb-4">
                <span class="mono text-[10px] uppercase text-muted-foreground"><?= count($project['documents']) ?> uploaded files</span>
                <div class="flex items-center gap-2">
                    <label class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-3 py-1.5 text-xs font-mono uppercase tracking-widest cursor-pointer">
                        Select Files
                        <input type="file" name="documents[]" multiple class="hidden" onchange="document.getElementById('upload-submit-btn').classList.remove('hidden');" />
                    </label>
                    <button type="submit" id="upload-submit-btn" class="hidden rounded-full bg-ink text-paper px-3 py-1.5 text-xs font-mono uppercase tracking-widest font-bold">Upload</button>
                </div>
            </form>

            <?php if (empty($project['documents'])): ?>
                <div class="text-xs text-muted-foreground uppercase font-mono text-center py-2">No documents mapped.</div>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach ($project['documents'] as $d): ?>
                        <li class="flex items-center justify-between gap-3 border-b border-ink/10 pb-2.5 last:border-b-0">
                            <div>
                                <span class="font-display text-sm font-bold text-ink uppercase"><?= esc($d['name']) ?></span>
                                <span class="mono text-[10px] text-muted-foreground ml-2">(<?= number_format($d['size'] / 1024, 1) ?> KB)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="<?= base_url(esc($d['file_path'])) ?>" download class="text-ink hover:opacity-80">
                                    <i data-lucide="download" class="w-4 h-4"></i>
                                </a>
                                <form action="<?= base_url("project/{$project['id']}/delete-doc/{$d['id']}") ?>" method="POST" class="inline">
                                    <button type="submit" class="text-destructive hover:opacity-85">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

    </section>
</div>

<!-- ==================== DIALOG MODALS (SCROLLABLE FORM CONTENT) ==================== -->

<!-- Edit Health -->
<div id="edit-health-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Edit Health / Details</h3>
            <button onclick="closeModal('edit-health-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form action="<?= base_url("projects/update/{$project['id']}") ?>" method="POST" class="overflow-y-auto flex-1 p-6 space-y-4 text-left">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Code</span>
                <input name="code" value="<?= esc($project['code']) ?>" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Name</span>
                <input name="name" value="<?= esc($project['name']) ?>" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Owner</span>
                <input name="owner" value="<?= esc($project['owner']) ?>" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Squad</span>
                <input name="squad" value="<?= esc($project['squad']) ?>" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Status</span>
                <select name="status" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <?php foreach ($statusLabels as $s => $lbl): ?>
                        <option value="<?= $s ?>" <?= ($project['status'] === $s) ? 'selected' : '' ?>><?= esc($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Start</span>
                    <input type="date" name="startDate" value="<?= esc($project['startDate']) ?>" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">End</span>
                    <input type="date" name="endDate" value="<?= esc($project['endDate']) ?>" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Health (Summary)</span>
                <input id="edit-health-input" name="health" value="<?= esc($project['health']) ?>" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Description</span>
                <textarea id="edit-description-input" name="description" rows="3" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"><?= esc($project['description']) ?></textarea>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-health-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Phase -->
<div id="create-phase-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Add Phase</h3>
            <button onclick="closeModal('create-phase-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="create-phase-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('create-phase-form', '<?= base_url("project/{$project['id']}/phase/create") ?>');">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Phase Name</span>
                <input name="name" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Description</span>
                <textarea name="description" rows="2" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Start</span>
                    <input type="date" name="start" required value="<?= date('Y-m-d') ?>" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">End</span>
                    <input type="date" name="end" required value="<?= date('Y-m-d', strtotime('+1 month')) ?>" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Status</span>
                <select name="status" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <?php foreach ($statusLabels as $s => $lbl): ?>
                        <option value="<?= $s ?>"><?= esc($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-phase-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Phase -->
<div id="edit-phase-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Edit Phase</h3>
            <button onclick="closeModal('edit-phase-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="edit-phase-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('edit-phase-form', this.action);">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Phase Name</span>
                <input id="edit-phase-name" name="name" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Description</span>
                <textarea id="edit-phase-description" name="description" rows="2" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Start</span>
                    <input id="edit-phase-start" type="date" name="start" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">End</span>
                    <input id="edit-phase-end" type="date" name="end" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Status</span>
                <select id="edit-phase-status" name="status" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <?php foreach ($statusLabels as $s => $lbl): ?>
                        <option value="<?= $s ?>"><?= esc($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-phase-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Subtask -->
<div id="create-subtask-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Add Subtask</h3>
            <button onclick="closeModal('create-subtask-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="create-subtask-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('create-subtask-form', this.action);">
            <input type="hidden" name="phase_id" id="create-subtask-phase-id" />
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Subtask Name</span>
                <input name="name" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Description</span>
                <textarea name="description" rows="2" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Start Date</span>
                    <input type="date" name="start" id="create-subtask-start" required value="<?= date('Y-m-d') ?>" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">End Date (Calculated)</span>
                    <input type="date" id="create-subtask-end" readonly class="rounded-xl border border-ink/20 bg-muted px-3 py-2 text-sm focus:outline-none cursor-not-allowed opacity-80" />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Man Days</span>
                    <input type="number" step="0.01" name="man_days" id="create-subtask-mandays" oninput="syncSubtaskHours('create-subtask-mandays', 'create-subtask-hours')" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" placeholder="0.00" required />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Task Hours</span>
                    <input type="number" step="0.01" name="task_hours" id="create-subtask-hours" oninput="syncSubtaskManDays('create-subtask-hours', 'create-subtask-mandays')" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" placeholder="0.00" required />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Assignee (Resource)</span>
                <select name="resource_id" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <option value="">-- UNASSIGNED --</option>
                    <?php foreach ($assignedResources as $res): ?>
                        <option value="<?= $res['id'] ?>"><?= esc($res['name']) ?> [<?= esc($res['role']) ?>]</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Status</span>
                <select name="status" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <?php foreach ($statusLabels as $s => $lbl): ?>
                        <option value="<?= $s ?>"><?= esc($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-subtask-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Subtask -->
<div id="edit-subtask-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Edit Subtask</h3>
            <button onclick="closeModal('edit-subtask-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="edit-subtask-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('edit-subtask-form', this.action);">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Subtask Name</span>
                <input id="edit-subtask-name" name="name" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Description</span>
                <textarea id="edit-subtask-description" name="description" rows="2" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Start Date</span>
                    <input type="date" name="start" id="edit-subtask-start" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">End Date (Calculated)</span>
                    <input type="date" id="edit-subtask-end" readonly class="rounded-xl border border-ink/20 bg-muted px-3 py-2 text-sm focus:outline-none cursor-not-allowed opacity-80" />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Man Days</span>
                    <input type="number" step="0.01" name="man_days" id="edit-subtask-mandays" oninput="syncSubtaskHours('edit-subtask-mandays', 'edit-subtask-hours')" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" placeholder="0.00" required />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Task Hours</span>
                    <input type="number" step="0.01" name="task_hours" id="edit-subtask-hours" oninput="syncSubtaskManDays('edit-subtask-hours', 'edit-subtask-mandays')" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" placeholder="0.00" required />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Assignee</span>
                <select id="edit-subtask-resource-id" name="resource_id" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <option value="">-- UNASSIGNED --</option>
                    <?php foreach ($assignedResources as $res): ?>
                        <option value="<?= $res['id'] ?>"><?= esc($res['name']) ?> [<?= esc($res['role']) ?>]</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Status</span>
                <select id="edit-subtask-status" name="status" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <?php foreach ($statusLabels as $s => $lbl): ?>
                        <option value="<?= $s ?>"><?= esc($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-subtask-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Dependency -->
<div id="create-dependency-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Add Dependency</h3>
            <button onclick="closeModal('create-dependency-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="create-dependency-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('create-dependency-form', '<?= base_url("project/{$project['id']}/dependency/create") ?>');">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Dependent Project</span>
                <select name="dep_project_id" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <option value="">-- SELECT PROJECT --</option>
                    <?php foreach ($allProjects as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= esc($p['name']) ?> [<?= esc($p['status']) ?>]</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Type</span>
                <select name="type" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <option value="depends-on">Depends On</option>
                    <option value="blocks">Blocks</option>
                </select>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-dependency-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Escalation -->
<div id="create-escalation-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Add Escalation</h3>
            <button onclick="closeModal('create-escalation-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="create-escalation-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('create-escalation-form', '<?= base_url("project/{$project['id']}/escalation/create") ?>');">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Level</span>
                <select name="level" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <option value="1">Level 1 - Internal Alert</option>
                    <option value="2">Level 2 - Stakeholder Attention</option>
                    <option value="3">Level 3 - Executive Action Required</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Escalate To</span>
                <input name="to_recipient" placeholder="Recipient Name or Role" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Details</span>
                <textarea name="note" rows="3" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-escalation-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Escalation -->
<div id="edit-escalation-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Edit Escalation</h3>
            <button onclick="closeModal('edit-escalation-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="edit-escalation-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('edit-escalation-form', this.action);">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Level</span>
                <select id="edit-esc-level" name="level" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <option value="1">Level 1 - Internal Alert</option>
                    <option value="2">Level 2 - Stakeholder Attention</option>
                    <option value="3">Level 3 - Executive Action Required</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Escalate To</span>
                <input id="edit-esc-to" name="to_recipient" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Details</span>
                <textarea id="edit-esc-note" name="note" rows="3" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Status</span>
                <select id="edit-esc-status" name="status" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <option value="active">Active</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Reason (optional)</span>
                <input id="edit-esc-reason" name="reason" placeholder="Reason details..." class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-escalation-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Risk -->
<div id="create-risk-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Add Risk or Issue</h3>
            <button onclick="closeModal('create-risk-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="create-risk-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('create-risk-form', '<?= base_url("project/{$project['id']}/risk/create") ?>');">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Title / Concern</span>
                <input name="title" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Severity</span>
                    <select name="severity" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <option value="low">Low</option>
                        <option value="med">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Classification</span>
                    <select name="type" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <option value="risk">Risk (Potential)</option>
                        <option value="issue">Issue (Active)</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Owner</span>
                <input name="owner" placeholder="Resource Name" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Mitigation Strategy</span>
                <textarea name="mitigation" rows="2" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-risk-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Risk -->
<div id="edit-risk-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Edit Risk or Issue</h3>
            <button onclick="closeModal('edit-risk-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="edit-risk-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('edit-risk-form', this.action);">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Title / Concern</span>
                <input id="edit-risk-title" name="title" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Severity</span>
                    <select id="edit-risk-severity" name="severity" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <option value="low">Low</option>
                        <option value="med">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Classification</span>
                    <select id="edit-risk-type" name="type" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <option value="risk">Risk (Potential)</option>
                        <option value="issue">Issue (Active)</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Owner</span>
                <input id="edit-risk-owner" name="owner" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Mitigation Strategy</span>
                <textarea id="edit-risk-mitigation" name="mitigation" rows="2" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Status</span>
                <select id="edit-risk-status" name="status" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <option value="active">Active</option>
                    <option value="resolved">Resolved</option>
                    <option value="deferred">Deferred</option>
                    <option value="others">Others</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Reason (optional)</span>
                <input id="edit-risk-reason" name="reason" placeholder="Reason details..." class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-risk-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Action Item -->
<div id="create-action-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Add Action Item</h3>
            <button onclick="closeModal('create-action-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="create-action-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('create-action-form', '<?= base_url("project/{$project['id']}/action/create") ?>');">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Task Title</span>
                <input name="title" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Owner</span>
                    <input name="owner" placeholder="Resource Name" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Due Date</span>
                    <input type="date" name="due" required value="<?= date('Y-m-d') ?>" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-action-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Action Modal -->
<div id="confirm-action-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden no-print">
    <div class="w-[95vw] max-w-[420px] rounded-3xl border border-ink/15 bg-white p-6 shadow-2xl text-left flex flex-col gap-4">
        <div class="flex items-center justify-between border-b border-ink/10 pb-3">
            <h3 class="font-display text-base font-bold text-ink uppercase tracking-wider" id="confirm-modal-title">Confirm Action</h3>
            <button type="button" onclick="closeModal('confirm-action-modal')" class="text-ink/60 hover:text-ink font-bold">✕</button>
        </div>
        <p class="text-xs text-ink/80 leading-relaxed font-mono" id="confirm-modal-body">Are you sure you want to perform this action?</p>
        <div class="flex justify-end gap-2 border-t border-ink/10 pt-4 mt-2">
            <button type="button" onclick="closeModal('confirm-action-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
            <button type="button" id="confirm-modal-yes-btn" class="rounded-full bg-destructive text-destructive-foreground hover:bg-destructive/90 px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Yes, Confirm</button>
        </div>
    </div>
</div>

<?php
$projectPhases = [];
$projectSubtasks = [];
$projectSubtaskIds = [];
if (!empty($project['phases'])) {
    foreach ($project['phases'] as $ph) {
        $projectPhases[] = $ph['name'];
        if (!empty($ph['subtasks'])) {
            foreach ($ph['subtasks'] as $sub) {
                $projectSubtasks[] = $sub['name'];
                $projectSubtaskIds[] = ['label' => "#{$sub['id']} ({$sub['name']})", 'value' => "#{$sub['id']}"];
            }
        }
    }
}
$statusList = array_keys($statusLabels);
?>
<script>
    window.NexusDetailConfig = {
        projectId: '<?= $project['id'] ?>',
        statusLabels: <?= json_encode($statusLabels) ?>,
        validStatuses: <?= json_encode($statusList) ?>,
        existingPhases: <?= json_encode($projectPhases) ?>,
        existingSubtasks: <?= json_encode($projectSubtasks) ?>,
        existingSubtaskIds: <?= json_encode($projectSubtaskIds) ?>,
        existingResources: <?= json_encode(array_values(array_unique(array_column($assignedResources, 'name')))) ?>,
        workDaysPerWeek: <?= (int)$workDaysPerWeek ?>
    };
    window.dailyWorkHours = <?= (float)$dailyWorkHours ?>;
</script>
<script src="<?= base_url('js/project_detail.js') ?>?v=<?= time() ?>"></script>

<!-- Echo Widget -->
<button onclick="toggleEchoTerminal()" class="fixed bottom-6 right-6 z-50 bg-black text-green-400 border border-green-500 font-mono text-[11px] uppercase px-3 py-2 shadow-lg hover:scale-105 transition-transform flex items-center gap-1.5 no-print" id="echo-term-btn">
    <span class="animate-pulse">▣</span> Echo Term
</button>

<div id="echo-terminal-window" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 w-[80vw] h-[80vh] bg-black border-2 border-green-500 shadow-2xl flex flex-col hidden font-mono text-sm text-green-300 select-none no-print">
    <div class="bg-green-500 text-black px-3 py-1.5 flex items-center justify-between shrink-0 cursor-default">
        <span class="font-bold text-[11px] uppercase tracking-wider">▣ ECHO TERMINAL v1.0</span>
        <button onclick="toggleEchoTerminal()" class="font-bold hover:text-red-700 select-none">✕</button>
    </div>
    
    <div id="echo-terminal-output" class="flex-1 p-3 overflow-y-auto space-y-1 select-text">
        <div class="text-green-400">// welcome to echo cli</div>
        <div class="text-green-400">// project: <?= esc($project['name']) ?></div>
        <div class="text-green-400">// type 'help' to list valid command templates.</div>
        <div class="text-green-300 mt-2">&gt; terminal ready.</div>
    </div>
    
    <div id="echo-autocomplete-list" class="border-t border-green-800 bg-black/90 max-h-32 overflow-y-auto shrink-0 select-none hidden divide-y divide-green-950"></div>
    
    <form id="echo-terminal-form" onsubmit="handleEchoSubmit(event)" class="border-t border-green-500 p-2 flex items-center gap-1.5 shrink-0 bg-black">
        <span class="text-green-300 font-bold">&gt;</span>
        <input type="text" id="echo-terminal-input" placeholder="Type command..." autocomplete="off" class="flex-1 bg-black text-green-300 outline-none border-none caret-green-300 text-sm py-0.5" />
    </form>
</div>

<?= $this->endSection() ?>
