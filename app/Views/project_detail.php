<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<?php
$db = \Config\Database::connect();
$projStatuses = $db->tableExists('projects') ? $db->table('projects')->select('status')->distinct()->get()->getResultArray() : [];
$statusDefs = $db->tableExists('status_definitions') ? $db->table('status_definitions')->get()->getResultArray() : [];

$statusLabels = [];
$statusBgs = [];

foreach ($statusDefs as $def) {
    $statusLabels[$def['status']] = $def['label'];
    $statusBgs[$def['status']] = 'bg-status-' . $def['status'];
}

foreach ($projStatuses as $ps) {
    $slug = $ps['status'];
    if (!isset($statusLabels[$slug]) && !empty($slug)) {
        $statusLabels[$slug] = strtoupper(str_replace('-', ' ', $slug));
        $statusBgs[$slug] = 'bg-status-' . $slug;
    }
}

// Ensure default fallback items exist
$defaultSlugs = ['on-track', 'at-risk', 'blocked', 'delayed', 'backlog'];
$defaultLabels = ['ON TRACK', 'AT RISK', 'BLOCKED', 'DELAYED', 'BACKLOG'];
foreach ($defaultSlugs as $i => $s) {
    if (!isset($statusLabels[$s])) $statusLabels[$s] = $defaultLabels[$i];
    if (!isset($statusBgs[$s])) $statusBgs[$s] = 'bg-status-' . $s;
}

$roleBgs = [
    'FE' => 'bg-status-ontrack',
    'BE' => 'bg-status-atrisk',
    'QA' => 'bg-status-delayed',
    'BA' => 'bg-status-backlog',
];

$severityBgs = [
    'low'  => 'bg-status-backlog',
    'med'  => 'bg-status-atrisk',
    'high' => 'bg-status-blocked',
];

// PHP calculation for Min and Max dates across phases
$phases = $project['phases'];
$minDate = null;
$maxDate = null;
if (!empty($phases)) {
    foreach ($phases as $ph) {
        $phStart = strtotime($ph['start']);
        $phEnd = strtotime($ph['end']);
        if ($minDate === null || $phStart < $minDate) {
            $minDate = $phStart;
        }
        if ($maxDate === null || $phEnd > $maxDate) {
            $maxDate = $phEnd;
        }
    }
} else {
    $minDate = strtotime($project['startDate']);
    $maxDate = strtotime($project['endDate']);
}
if ($minDate === $maxDate) {
    $maxDate += 86400 * 30; // 30 days buffer
}
$totalDuration = $maxDate - $minDate;

// Generate months list between minDate and maxDate
$startYear = (int)date('Y', $minDate);
$startMonth = (int)date('n', $minDate);
$endYear = (int)date('Y', $maxDate);
$endMonth = (int)date('n', $maxDate);

$timelineMonths = [];
$y = $startYear;
$m = $startMonth;
while ($y < $endYear || ($y === $endYear && $m <= $endMonth)) {
    $timelineMonths[] = [
        'year' => $y,
        'month' => $m,
        'label' => strtoupper(date('M', mktime(0, 0, 0, $m, 1, $y)))
    ];
    $m++;
    if ($m > 12) {
        $m = 1;
        $y++;
    }
}
$numMonths = count($timelineMonths);

$yearSpans = [];
foreach ($timelineMonths as $tm) {
    $yr = $tm['year'];
    if (!isset($yearSpans[$yr])) {
        $yearSpans[$yr] = 0;
    }
    $yearSpans[$yr]++;
}

$timelineStartMs = strtotime("{$startYear}-" . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . "-01");
$timelineEndMs = strtotime("last day of " . date('F Y', mktime(0, 0, 0, $endMonth, 1, $endYear))) + 86399;
$totalTimelineMs = $timelineEndMs - $timelineStartMs;
if ($totalTimelineMs <= 0) {
    $totalTimelineMs = 1;
}

$nowMs = time();
$todayPct = null;
if ($nowMs >= $timelineStartMs && $nowMs <= $timelineEndMs) {
    $todayPct = (($nowMs - $timelineStartMs) / $totalTimelineMs) * 100;
}
?>

<div class="min-h-screen pb-12">
    <!-- Header -->
    <div class="border-b border-foreground bg-primary text-primary-foreground">
        <div class="max-w-[1400px] mx-auto px-6 py-6 flex items-start justify-between gap-4 flex-wrap">
            <div class="flex items-start gap-4">
                <a href="<?= base_url('projects') ?>" class="brutal-border-thick border-primary-foreground bg-primary-foreground text-primary p-2 brutal-hover" aria-label="Back to portfolio">
                    <i data-lucide="arrow-left" class="h-5 w-5" stroke-width="3"></i>
                </a>
                <div>
                    <div class="mono text-xs uppercase tracking-widest opacity-80">
                        <?= esc($project['code']) ?> // <?= esc($project['owner']) ?> // SQUAD: <?= esc($project['squad']) ?>
                    </div>
                    <h1 class="text-2xl md:text-4xl font-black uppercase tracking-tight mt-1">
                        <?= esc($project['name']) ?>
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-[1400px] mx-auto p-6 flex flex-col gap-6">
        
        <!-- Quick Stats -->
        <div id="stats-container" class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="brutal-border p-3 bg-background">
                <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground mb-2">Status</div>
                <span class="mono text-[10px] font-bold uppercase tracking-widest px-2 py-1 border border-foreground text-background <?= $statusBgs[$project['status']] ?? 'bg-status-' . $project['status'] ?>">
                    <?= $statusLabels[$project['status']] ?? strtoupper($project['status']) ?>
                </span>
            </div>
            <div class="brutal-border p-3 bg-background">
                <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground mb-2">Squad</div>
                <span class="mono text-sm font-black uppercase"><?= esc($project['squad']) ?></span>
            </div>
            <div class="brutal-border p-3 bg-background">
                <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground mb-2">Start</div>
                <span class="mono text-sm"><?= sys_date($project['startDate']) ?></span>
            </div>
            <div class="brutal-border p-3 bg-background">
                <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground mb-2">End (Target)</div>
                <span class="mono text-sm font-bold"><?= sys_date($project['endDate']) ?></span>
            </div>
            <div class="brutal-border p-3 bg-background">
                <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground mb-2">Progress</div>
                <div class="flex items-center gap-2 w-full">
                    <div class="flex-1 h-3 brutal-border bg-background">
                        <div class="h-full bg-primary" style="width: <?= $project['progress'] ?>%"></div>
                    </div>
                    <span class="mono text-xs font-bold"><?= $project['progress'] ?>%</span>
                </div>
            </div>
        </div>

        <!-- Sequential Phase Timeline (Stacked) -->
        <div id="timeline-container" class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 bg-primary brutal-border"></div>
                <h3 class="mono text-xs uppercase tracking-widest font-black">Phase Sequential Timeline</h3>
                <div class="flex-1 h-[2px] bg-border"></div>
                <button onclick="toggleAllTimelineSubtasks()" id="toggle-all-timeline-subtasks-btn" class="brutal-border bg-card px-3 py-1 mono text-[10px] uppercase tracking-widest font-black brutal-hover">
                    Show Subtasks
                </button>
            </div>
            
            <div class="brutal-border-thick bg-card overflow-x-auto relative">
                <div class="min-w-[800px] relative">
                    
                    <!-- Year Headers -->
                    <div class="grid grid-cols-[220px_repeat(<?= $numMonths ?>,minmax(48px,1fr))] border-b border-foreground relative">
                        <div class="brutal-border-thick border-0 border-r p-3 bg-primary text-primary-foreground mono text-xs uppercase tracking-widest font-bold">
                            Phase / Timeline
                        </div>
                        <?php foreach ($yearSpans as $yr => $spanCount): ?>
                            <div class="col-span-<?= $spanCount ?> border-r border-foreground last:border-r-0 p-2 mono text-xs uppercase tracking-widest text-center bg-secondary">// <?= $yr ?></div>
                        <?php endforeach; ?>
                        
                        <?php if ($todayPct !== null): ?>
                            <div class="absolute top-1/2 -translate-y-1/2 pointer-events-none z-30" style="left: calc(220px + (100% - 220px) * <?= $todayPct / 100 ?>); transform: translate(-50%, -50%)">
                                <span class="mono text-[9px] font-black uppercase tracking-widest bg-red-600 text-white px-1.5 py-0.5 border border-foreground whitespace-nowrap">
                                    ▼ TODAY
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Month Headers -->
                    <div class="grid grid-cols-[220px_repeat(<?= $numMonths ?>,minmax(48px,1fr))] border-b border-foreground">
                        <div class="border-r border-foreground p-2 mono text-[10px] uppercase tracking-widest text-muted-foreground">Bar = phase span</div>
                        <?php foreach ($timelineMonths as $index => $m): ?>
                            <div class="p-2 mono text-[10px] uppercase tracking-widest text-center text-muted-foreground border-r border-foreground/40 <?= ($index === $numMonths - 1) ? 'border-r border-foreground' : '' ?>">
                                <?= $m['label'] ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Timeline Rows -->
                    <div class="relative">
                        <!-- Today Line (Red) -->
                        <?php if ($todayPct !== null): ?>
                            <div class="absolute top-0 bottom-0 w-[2px] bg-red-600 z-20 pointer-events-none" style="left: calc(220px + (100% - 220px) * <?= $todayPct / 100 ?>); transform: translateX(-1px)"></div>
                        <?php endif; ?>

                        <?php if (empty($phases)): ?>
                            <div class="p-8 bg-background mono text-xs text-muted-foreground uppercase tracking-widest text-center">
                                Add phases to render timeline view.
                            </div>
                        <?php else: ?>
                            <?php foreach ($phases as $ph): ?>
                                <?php
                                $phComp = 0;
                                $phTotal = count($ph['subtasks'] ?? []);
                                if ($phTotal > 0) {
                                    foreach ($ph['subtasks'] as $sub) {
                                        if (in_array(strtolower($sub['status']), ['complete', 'completed', 'done'])) {
                                            $phComp++;
                                        }
                                    }
                                    $phProgress = (int)(($phComp / $phTotal) * 100);
                                } else {
                                    $phProgress = in_array(strtolower($ph['status']), ['complete', 'completed', 'done']) ? 100 : 0;
                                }
                                ?>
                                <div class="grid grid-cols-[220px_repeat(<?= $numMonths ?>,minmax(48px,1fr))] border-b border-foreground w-full text-left hover:bg-secondary/40 transition-colors">
                                    <!-- Phase Details (Left Column) -->
                                    <div class="border-r border-foreground p-3 flex flex-col gap-1 bg-background z-10">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="mono text-[9px] font-black uppercase tracking-widest px-1.5 py-0.5 border border-foreground text-background <?= $statusBgs[$ph['status']] ?? 'bg-status-' . $ph['status'] ?> whitespace-nowrap">
                                                <?= $statusLabels[$ph['status']] ?? strtoupper($ph['status']) ?>
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <?php if (!empty($ph['subtasks'])): ?>
                                                <button onclick="toggleSingleTimelineSubtask(<?= $ph['id'] ?>, this)" class="p-0.5 border border-foreground bg-secondary/50 hover:bg-secondary transition-colors" title="Toggle Subtasks">
                                                    <i data-lucide="chevron-right" class="w-3 h-3 transition-transform duration-200" id="timeline-chevron-<?= $ph['id'] ?>"></i>
                                                </button>
                                            <?php endif; ?>
                                            <span class="font-bold text-xs uppercase tracking-tight truncate" title="<?= esc($ph['name']) ?>"><?= esc($ph['name']) ?></span>
                                            <span class="mono text-[10px] font-bold text-muted-foreground ml-1">(<?= $phProgress ?>%)</span>
                                        </div>
                                        <?php if (!empty($ph['subtasks'])): ?>
                                            <span class="mono text-[9px] text-muted-foreground uppercase font-bold tracking-tight">
                                                (<?= count($ph['subtasks']) ?> <?= count($ph['subtasks']) === 1 ? 'subtask' : 'subtasks' ?>)
                                            </span>
                                        <?php endif; ?>
                                        <span class="mono text-[9px] text-muted-foreground"><?= sys_date($ph['start']) ?> → <?= sys_date($ph['end']) ?></span>
                                    </div>

                                    <!-- Phase Bar Track (Right columns) -->
                                    <div class="[grid-column:span_<?= $numMonths ?>] relative grid h-[64px]" style="grid-template-columns: repeat(<?= $numMonths ?>, minmax(0, 1fr))">
                                        <?php for ($i = 0; $i < $numMonths; $i++): ?>
                                            <div class="border-r border-foreground/30 <?= ($i === $numMonths - 1) ? 'border-r border-foreground' : '' ?>"></div>
                                        <?php endfor; ?>

                                        <!-- Left highlight bar -->
                                        <div class="absolute left-0 top-0 bottom-0 w-1 <?= $statusBgs[$ph['status']] ?? 'bg-status-' . $ph['status'] ?>"></div>

                                        <!-- Phase span -->
                                        <?php
                                        $s = strtotime($ph['start']);
                                        $e = strtotime($ph['end']);
                                        
                                        $left = (($s - $timelineStartMs) / $totalTimelineMs) * 100;
                                        $width = (($e - $s) / $totalTimelineMs) * 100;
                                        
                                        if ($left < 0) {
                                            $width += $left;
                                            $left = 0;
                                        }
                                        if ($left + $width > 100) {
                                            $width = 100 - $left;
                                        }
                                        if ($width < 2) $width = 2;
                                        ?>
                                        <div class="absolute top-1/2 -translate-y-1/2 h-7 <?= $statusBgs[$ph['status']] ?? 'bg-status-' . $ph['status'] ?> border border-foreground flex items-center px-2 z-10" 
                                             style="left: <?= $left ?>%; width: <?= $width ?>%" 
                                             title="<?= esc($ph['name']) ?> • <?= sys_date($ph['start']) ?> → <?= sys_date($ph['end']) ?> • <?= $phProgress ?>% • <?= $statusLabels[$ph['status']] ?? strtoupper($ph['status']) ?>">
                                            <span class="mono text-[10px] font-bold text-background uppercase tracking-tight truncate">
                                                <?= esc($ph['name']) ?> (<?= $phProgress ?>%)
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Timeline Subtasks Nested Rows -->
                                <?php if (!empty($ph['subtasks'])): ?>
                                    <?php foreach ($ph['subtasks'] as $sub): ?>
                                        <?php
                                        $subStart = strtotime($sub['start']);
                                        $subEnd = strtotime($sub['end']);
                                        
                                        $subLeft = (($subStart - $timelineStartMs) / $totalTimelineMs) * 100;
                                        $subWidth = (($subEnd - $subStart) / $totalTimelineMs) * 100;
                                        
                                        if ($subLeft < 0) {
                                            $subWidth += $subLeft;
                                            $subLeft = 0;
                                        }
                                        if ($subLeft + $subWidth > 100) {
                                            $subWidth = 100 - $subLeft;
                                        }
                                        if ($subWidth < 2) $subWidth = 2;
                                        ?>
                                        <div class="timeline-subtask-row-<?= $ph['id'] ?> hidden grid grid-cols-[220px_repeat(<?= $numMonths ?>,minmax(48px,1fr))] border-b border-foreground/20 w-full text-left bg-background/40 hover:bg-secondary/20 transition-colors">
                                            <!-- Subtask Details (Left Column) -->
                                            <div class="border-r border-foreground p-2 pl-8 flex flex-col gap-0.5 bg-background/10 z-10">
                                                <div class="flex items-center justify-between gap-1">
                                                    <span class="font-bold text-[10px] uppercase tracking-tight truncate" title="<?= esc($sub['name']) ?>"><?= esc($sub['name']) ?></span>
                                                    <span class="mono text-[8px] font-bold uppercase tracking-widest px-1 border border-foreground text-background <?= $statusBgs[$sub['status']] ?? 'bg-status-' . $sub['status'] ?> whitespace-nowrap">
                                                        <?= $statusLabels[$sub['status']] ?? strtoupper($sub['status']) ?>
                                                    </span>
                                                </div>
                                                <span class="mono text-[8px] text-muted-foreground"><?= sys_date($sub['start']) ?> → <?= sys_date($sub['end']) ?></span>
                                            </div>

                                            <!-- Subtask Bar Track (Right columns) -->
                                            <div class="[grid-column:span_<?= $numMonths ?>] relative grid h-[40px]" style="grid-template-columns: repeat(<?= $numMonths ?>, minmax(0, 1fr))">
                                                <?php for ($i = 0; $i < $numMonths; $i++): ?>
                                                    <div class="border-r border-foreground/15 <?= ($i === $numMonths - 1) ? 'border-r border-foreground' : '' ?>"></div>
                                                <?php endfor; ?>

                                                <!-- Subtask span bar -->
                                                <div class="absolute top-1/2 -translate-y-1/2 h-4 <?= $statusBgs[$sub['status']] ?? 'bg-status-' . $sub['status'] ?> border border-foreground/80 flex items-center px-1.5 z-10" 
                                                     style="left: <?= $subLeft ?>%; width: <?= $subWidth ?>%" 
                                                     title="<?= esc($sub['name']) ?> • <?= sys_date($sub['start']) ?> → <?= sys_date($sub['end']) ?> • <?= $statusLabels[$sub['status']] ?? strtoupper($sub['status']) ?>">
                                                    <span class="mono text-[8px] font-black text-background uppercase tracking-tight truncate animate-fade-in">
                                                        <?= esc($sub['name']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
        </div>

        <!-- Health Summary -->
        <div id="health-container" class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 bg-primary brutal-border"></div>
                <h3 class="mono text-xs uppercase tracking-widest font-black">Health Summary</h3>
                <div class="flex-1 h-[2px] bg-border"></div>
                <button onclick="openEditHealthModal()" class="brutal-border bg-foreground text-background px-3 py-1 mono text-[10px] uppercase tracking-widest font-black brutal-hover">
                    Edit Health
                </button>
            </div>
            <div class="brutal-border bg-card p-4 brutal-shadow space-y-2">
                <p id="health-display-text" data-value="<?= esc($project['health']) ?>" class="text-sm font-bold">
                    <?php
                    $hVal = $project['health'];
                    if (isset($statusLabels[$hVal])) {
                        echo esc($statusLabels[$hVal]);
                    } else {
                        echo esc($hVal);
                    }
                    ?>
                </p>
                <p id="description-display-text" class="text-sm text-muted-foreground"><?= esc($project['description']) ?></p>
            </div>
        </div>

        <!-- Phases & Expected Completion (Drag & Drop, CRUD) -->
        <div id="phases-container" class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 bg-primary brutal-border"></div>
                <h3 class="mono text-xs uppercase tracking-widest font-black">Phases & Expected Completion</h3>
                <div class="flex-1 h-[2px] bg-border"></div>
                <button onclick="openCreatePhaseModal()" class="brutal-border bg-foreground text-background px-3 py-1 mono text-[10px] uppercase tracking-widest font-black brutal-hover">
                    + Add Phase
                </button>
            </div>
            <div class="brutal-border bg-card brutal-shadow overflow-hidden">
                <table class="w-full mono text-xs">
                    <thead class="bg-foreground text-background uppercase tracking-widest">
                        <tr>
                            <th class="text-left px-3 py-2 text-[10px] w-12 font-black">Drag</th>
                            <th class="text-left px-3 py-2 text-[10px] font-black">Phase Name</th>
                            <th class="text-left px-3 py-2 text-[10px] hidden md:table-cell font-black">Description</th>
                            <th class="text-left px-3 py-2 text-[10px] w-48 hidden md:table-cell font-black">Timeline</th>
                            <th class="text-left px-3 py-2 text-[10px] w-32 font-black">Status</th>
                            <th class="text-right px-3 py-2 text-[10px] w-24 font-black">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="phase-drag-body">
                        <?php if (empty($phases)): ?>
                            <tr>
                                <td colspan="6" class="p-4 text-center text-muted-foreground uppercase tracking-widest">No phases configured.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($phases as $ph): ?>
                                <?php
                                $phComp = 0;
                                $phTotal = count($ph['subtasks'] ?? []);
                                if ($phTotal > 0) {
                                    foreach ($ph['subtasks'] as $sub) {
                                        if (in_array(strtolower($sub['status']), ['complete', 'completed', 'done'])) {
                                            $phComp++;
                                        }
                                    }
                                    $phProgress = (int)(($phComp / $phTotal) * 100);
                                } else {
                                    $phProgress = in_array(strtolower($ph['status']), ['complete', 'completed', 'done']) ? 100 : 0;
                                }
                                ?>
                                <tr class="phase-drag-row border-t border-foreground bg-background cursor-grab active:cursor-grabbing" draggable="true" data-id="<?= $ph['id'] ?>">
                                    <td class="px-3 py-3 align-middle text-center text-muted-foreground font-black">
                                        <i data-lucide="grip-vertical" class="w-4 h-4 mx-auto select-none pointer-events-none"></i>
                                    </td>
                                    <td class="px-3 py-3 align-middle font-bold uppercase tracking-tight text-sm">
                                        <div class="flex items-center gap-2">
                                            <button onclick="toggleSubtasks(<?= $ph['id'] ?>, this)" class="p-1 border border-foreground bg-secondary/50 hover:bg-secondary transition-colors" title="Toggle Subtasks">
                                                <i data-lucide="chevron-right" class="w-3.5 h-3.5 transition-transform duration-200" id="chevron-<?= $ph['id'] ?>"></i>
                                            </button>
                                            <span><?= esc($ph['name']) ?></span>
                                            <span class="mono text-xs font-bold text-muted-foreground ml-1">(<?= $phProgress ?>%)</span>
                                            <?php if (!empty($ph['subtasks'])): ?>
                                                <span class="mono text-[10px] text-muted-foreground">(<?= count($ph['subtasks']) ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 align-middle text-muted-foreground hidden md:table-cell"><?= esc($ph['description']) ?></td>
                                    <td class="px-3 py-3 align-middle mono text-xs text-muted-foreground hidden md:table-cell">
                                        <?= sys_date($ph['start']) ?> → <?= sys_date($ph['end']) ?>
                                    </td>
                                    <td class="px-3 py-3 align-middle">
                                        <span class="mono text-[10px] font-bold uppercase tracking-widest px-2 py-1 border border-foreground text-background <?= $statusBgs[$ph['status']] ?? 'bg-status-' . $ph['status'] ?>">
                                            <?= $statusLabels[$ph['status']] ?? strtoupper($ph['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 align-middle text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button title="Add Subtask" onclick="openCreateSubtaskModal(<?= $ph['id'] ?>)" class="brutal-border p-1 bg-background text-primary brutal-hover">
                                                <i data-lucide="plus-square" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                                            </button>
                                            <button title="Edit" onclick='openEditPhaseModal(<?= json_encode($ph) ?>)' class="brutal-border p-1 bg-background brutal-hover">
                                                <i data-lucide="pencil" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                                            </button>
                                            <button title="Delete" onclick="deleteAjaxItem('<?= base_url('project/phase/delete') ?>/<?= $ph['id'] ?>')" class="brutal-border p-1 bg-background text-destructive brutal-hover">
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                
                                <?php if (!empty($ph['subtasks'])): ?>
                                    <?php foreach ($ph['subtasks'] as $sub): ?>
                                        <tr class="subtask-row-<?= $ph['id'] ?> hidden bg-card/50 border-t border-dashed border-foreground/30 hover:bg-secondary/20 transition-colors">
                                            <td></td>
                                            <td class="pl-8 px-3 py-2 align-middle font-medium uppercase tracking-tight text-xs text-muted-foreground">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="corner-down-right" class="w-3.5 h-3.5 text-muted-foreground/60 select-none"></i>
                                                    <span><?= esc($sub['name']) ?></span>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 align-middle text-xs text-muted-foreground/80 hidden md:table-cell"><?= esc($sub['description']) ?></td>
                                            <td class="px-3 py-2 align-middle mono text-[11px] text-muted-foreground/80 hidden md:table-cell">
                                                <?= sys_date($sub['start']) ?> → <?= sys_date($sub['end']) ?>
                                            </td>
                                            <td class="px-3 py-2 align-middle">
                                                <span class="mono text-[9px] font-bold uppercase tracking-widest px-1.5 py-0.5 border border-foreground/50 text-background <?= $statusBgs[$sub['status']] ?? 'bg-status-' . $sub['status'] ?>">
                                                    <?= $statusLabels[$sub['status']] ?? strtoupper($sub['status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 align-middle text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <button title="Edit Subtask" onclick='openEditSubtaskModal(<?= json_encode($sub) ?>, <?= $ph['id'] ?>)' class="brutal-border p-1 bg-background brutal-hover scale-90">
                                                        <i data-lucide="pencil" class="h-3 w-3" stroke-width="2.5"></i>
                                                    </button>
                                                    <button title="Delete Subtask" onclick="deleteAjaxItem('<?= base_url('project/phase/subtask/delete') ?>/<?= $sub['id'] ?>')" class="brutal-border p-1 bg-background text-destructive brutal-hover scale-90">
                                                        <i data-lucide="trash-2" class="h-3 w-3" stroke-width="2.5"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr class="subtask-row-<?= $ph['id'] ?> hidden bg-card/20 border-t border-dashed border-foreground/30">
                                        <td></td>
                                        <td colspan="5" class="pl-8 px-3 py-2 align-middle text-xs text-muted-foreground/60 italic uppercase tracking-wider">
                                            No subtasks for this phase.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Assigned Resources (Inline Assign) -->
        <div id="resources-container" class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 bg-primary brutal-border"></div>
                <h3 class="mono text-xs uppercase tracking-widest font-black">Assigned Resources</h3>
                <div class="flex-1 h-[2px] bg-border"></div>
            </div>
            
            <div class="brutal-border bg-card p-4 brutal-shadow flex items-center justify-between gap-4 flex-wrap">
                <form id="assign-resource-form" class="flex items-center gap-2 flex-wrap" onsubmit="event.preventDefault(); submitAjaxForm('assign-resource-form', '<?= base_url("project/{$project['id']}/resources/assign") ?>');">
                    <span class="mono text-xs font-bold uppercase">Assign Resource:</span>
                    <div class="relative inline-block">
                        <input type="text" id="resource-search-input" list="resource-options" placeholder="TYPE OR SELECT RESOURCE..." class="border border-foreground bg-background px-2 py-1 mono text-xs uppercase focus:outline-none w-64" required autocomplete="off" oninput="syncResourceId(this)" />
                        <datalist id="resource-options">
                            <?php foreach ($allResources as $res): ?>
                                <option data-id="<?= $res['id'] ?>" value="<?= esc($res['name']) ?> [<?= esc($res['role']) ?>]"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <input type="hidden" name="resource_id" id="resource-id-hidden" required />
                    </div>
                    <button type="submit" class="brutal-border bg-foreground text-background px-3 py-1 mono text-[10px] uppercase tracking-widest font-black brutal-hover">
                        Assign
                    </button>
                </form>
            </div>

            <?php if (empty($assignedResources)): ?>
                <div class="brutal-border p-4 bg-background mono text-xs text-muted-foreground uppercase tracking-widest text-center">
                    No resources mapped to this project.
                </div>
            <?php else: ?>
                <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-2">
                    <?php foreach ($assignedResources as $r): ?>
                        <div class="brutal-border bg-background p-3 flex items-center justify-between gap-3">
                            <div>
                                <div class="font-bold uppercase tracking-tight text-sm"><?= esc($r['name']) ?></div>
                                <div class="mono text-[10px] text-muted-foreground uppercase tracking-widest">
                                    <?= esc($r['department']) ?> · <?= esc($r['status']) ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="mono text-[10px] font-black uppercase border border-foreground px-2 py-0.5 <?= $roleBgs[$r['role']] ?? 'bg-secondary' ?>">
                                    <?= esc($r['role']) ?>
                                </span>
                                <button onclick="unassignResource('<?= $project['id'] ?>', '<?= $r['id'] ?>')" class="brutal-border p-1 bg-background text-destructive brutal-hover" title="Unassign Resource">
                                    <i data-lucide="x" class="w-3.5 h-3.5" stroke-width="2.5"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Dependencies and Escalations -->
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Dependencies -->
            <div id="dependencies-container" class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 bg-primary brutal-border"></div>
                    <h3 class="mono text-xs uppercase tracking-widest font-black">Dependencies</h3>
                    <div class="flex-1 h-[2px] bg-border"></div>
                    <button onclick="openCreateDependencyModal()" class="brutal-border bg-foreground text-background px-3 py-1 mono text-[10px] uppercase tracking-widest font-black brutal-hover">
                        + Add
                    </button>
                </div>
                <?php if (empty($project['dependencies'])): ?>
                    <div class="brutal-border p-4 bg-background mono text-xs text-muted-foreground uppercase tracking-widest text-center">
                        No dependencies declared.
                    </div>
                <?php else: ?>
                    <div class="grid gap-2">
                        <?php foreach ($project['dependencies'] as $d): ?>
                            <div class="brutal-border p-3 bg-card flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-bold uppercase tracking-tight text-sm"><?= esc($d['dep_project_name']) ?></div>
                                    <div class="mono text-[10px] text-muted-foreground uppercase tracking-widest">
                                        <?php if ($d['type'] === 'depends-on'): ?>
                                            → depends on
                                        <?php elseif ($d['type'] === 'blocks'): ?>
                                            ← blocks
                                        <?php else: ?>
                                            ◈ <?= esc($d['type']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="mono text-[10px] font-bold uppercase tracking-widest px-2 py-1 border border-foreground text-background <?= $statusBgs[$d['status']] ?? 'bg-status-' . $d['status'] ?>">
                                        <?= $statusLabels[$d['status']] ?? strtoupper($d['status']) ?>
                                    </span>
                                    <button onclick="deleteAjaxItem('<?= base_url('project/dependency/delete') ?>/<?= $d['id'] ?>')" class="brutal-border p-1 bg-background text-destructive brutal-hover" title="Delete Dependency">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5" stroke-width="2.5"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Escalations -->
            <div id="escalations-container" class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 bg-primary brutal-border"></div>
                    <h3 class="mono text-xs uppercase tracking-widest font-black">Escalations Needed</h3>
                    <div class="flex-1 h-[2px] bg-border"></div>
                    <button onclick="openCreateEscalationModal()" class="brutal-border bg-foreground text-background px-3 py-1 mono text-[10px] uppercase tracking-widest font-black brutal-hover">
                        + Add
                    </button>
                </div>
                <?php if (empty($project['escalations'])): ?>
                    <div class="brutal-border p-4 bg-background mono text-xs text-muted-foreground uppercase tracking-widest text-center">
                        No active escalations.
                    </div>
                <?php else: ?>
                    <div class="grid gap-2">
                        <?php foreach ($project['escalations'] as $e): ?>
                            <div class="brutal-border p-3 bg-card relative">
                                <div class="flex items-center justify-between">
                                    <span class="mono text-xs font-black bg-destructive text-destructive-foreground px-2 py-0.5 border border-foreground">
                                        LEVEL <?= esc($e['level']) ?>
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <span class="mono text-[10px] text-muted-foreground"><?= sys_date($e['date']) ?></span>
                                        <button onclick='openEditEscalationModal(<?= json_encode($e) ?>)' class="brutal-border p-1 bg-background brutal-hover" title="Edit Escalation">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                                        </button>
                                        <button onclick="deleteAjaxItem('<?= base_url('project/escalation/delete') ?>/<?= $e['id'] ?>')" class="brutal-border p-1 bg-background text-destructive brutal-hover" title="Delete Escalation">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="text-sm mt-2"><?= esc($e['note']) ?></div>
                                <div class="mono text-[10px] text-muted-foreground uppercase tracking-widest mt-1">
                                    → <?= esc($e['to_recipient']) ?>
                                </div>
                                <?php if (!empty($e['status']) && $e['status'] !== 'active'): ?>
                                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                                        <span class="mono text-[9px] font-black uppercase tracking-tight px-1.5 py-0.5 border border-foreground bg-secondary text-foreground">
                                            STATUS: <?= esc($e['status']) ?>
                                        </span>
                                        <?php if (!empty($e['reason'])): ?>
                                            <span class="text-xs text-muted-foreground italic">Reason: <?= esc($e['reason']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Risks & Issues -->
        <div id="risks-container" class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 bg-primary brutal-border"></div>
                <h3 class="mono text-xs uppercase tracking-widest font-black">Risks & Issues</h3>
                <div class="flex-1 h-[2px] bg-border"></div>
                <button onclick="openCreateRiskModal()" class="brutal-border bg-foreground text-background px-3 py-1 mono text-[10px] uppercase tracking-widest font-black brutal-hover">
                    + Add Risk/Issue
                </button>
            </div>
            <?php if (empty($project['risks'])): ?>
                <div class="brutal-border p-4 bg-background mono text-xs text-muted-foreground uppercase tracking-widest text-center">
                    No risks or issues logged.
                </div>
            <?php else: ?>
                <div class="grid gap-2">
                    <?php foreach ($project['risks'] as $r): ?>
                        <div class="brutal-border p-3 bg-card grid gap-2">
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="mono text-[10px] font-black uppercase tracking-widest border border-foreground text-background px-2 py-0.5 <?= $r['type'] === 'issue' ? 'bg-destructive' : 'bg-foreground' ?>">
                                        <?= esc($r['type']) ?>
                                    </span>
                                    <span class="mono text-[10px] font-black uppercase tracking-widest border border-foreground text-background px-2 py-0.5 <?= $severityBgs[$r['severity']] ?? 'bg-secondary' ?>">
                                        <?= esc($r['severity']) ?>
                                    </span>
                                    <?php if (!empty($r['status']) && $r['status'] !== 'active'): ?>
                                        <span class="mono text-[9px] font-black uppercase tracking-tight px-1.5 py-0.5 border border-foreground
                                            <?= $r['status'] === 'resolved' ? 'bg-primary text-primary-foreground' : ($r['status'] === 'deferred' ? 'bg-secondary text-foreground' : 'bg-card text-foreground') ?>">
                                            <?= esc($r['status']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="font-bold text-sm uppercase"><?= esc($r['title']) ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="mono text-[10px] text-muted-foreground uppercase tracking-widest hidden md:inline">→ <?= esc($r['owner']) ?></span>
                                    <button onclick='openEditRiskModal(<?= json_encode($r) ?>)' class="brutal-border p-1 bg-background brutal-hover" title="View / Edit Risk">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5" stroke-width="2.5"></i>
                                    </button>
                                    <button onclick="deleteAjaxItem('<?= base_url('project/risk/delete') ?>/<?= $r['id'] ?>')" class="brutal-border p-1 bg-background text-destructive brutal-hover" title="Delete Risk/Issue">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5" stroke-width="2.5"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mono text-xs text-muted-foreground">Mitigation: <?= esc($r['mitigation']) ?></div>
                            <?php if (!empty($r['reason'])): ?>
                                <div class="mono text-xs text-muted-foreground italic">Reason: <?= esc($r['reason']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action Items (Checklist) -->
        <div id="actions-container" class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 bg-primary brutal-border"></div>
                <h3 class="mono text-xs uppercase tracking-widest font-black">Action Items</h3>
                <div class="flex-1 h-[2px] bg-border"></div>
                <button onclick="openCreateActionModal()" class="brutal-border bg-foreground text-background px-3 py-1 mono text-[10px] uppercase tracking-widest font-black brutal-hover">
                    + Add Action Item
                </button>
            </div>
            <?php if (empty($project['actionItems'])): ?>
                <div class="brutal-border p-4 bg-background mono text-xs text-muted-foreground uppercase tracking-widest text-center">
                    No action items.
                </div>
            <?php else: ?>
                <div class="grid gap-2">
                    <?php foreach ($project['actionItems'] as $a): ?>
                        <div class="brutal-border p-3 bg-card grid gap-2">
                            <div class="grid grid-cols-[24px_1fr_auto_auto_auto] items-center gap-3">
                                <button onclick="toggleActionItem('<?= $project['id'] ?>', '<?= $a['id'] ?>', this)" class="w-5 h-5 brutal-border <?= $a['done'] == 1 ? 'bg-primary' : 'bg-background' ?> flex items-center justify-center cursor-pointer transition-colors duration-100">
                                    <?php if ($a['done'] == 1): ?>
                                        <span class="mono text-[10px] font-black text-primary-foreground">X</span>
                                    <?php endif; ?>
                                </button>
                                <span class="text-sm font-bold uppercase <?= $a['done'] == 1 ? 'line-through text-muted-foreground' : '' ?>"><?= esc($a['title']) ?></span>
                                <span class="mono text-[10px] text-muted-foreground uppercase tracking-widest hidden md:inline"><?= esc($a['owner']) ?></span>
                                <span class="mono text-xs"><?= sys_date($a['due']) ?></span>
                                <button onclick="deleteAjaxItem('<?= base_url('project/action/delete') ?>/<?= $a['id'] ?>')" class="brutal-border p-1 bg-background text-destructive brutal-hover" title="Delete Action Item">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5" stroke-width="2.5"></i>
                                </button>
                            </div>
                            <?php if ($a['done'] == 1 && !empty($a['resolved_date'])): ?>
                                <div class="mono text-[10px] text-primary font-black uppercase tracking-widest pl-8">
                                    ✓ Resolved on <?= sys_date($a['resolved_date']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Documents -->
        <div class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 bg-primary brutal-border"></div>
                <h3 class="mono text-xs uppercase tracking-widest font-black">Documents</h3>
                <div class="flex-1 h-[2px] bg-border"></div>
            </div>

            <div class="brutal-border bg-card p-4 brutal-shadow space-y-4">
                <form action="<?= base_url("project/{$project['id']}/upload") ?>" method="POST" enctype="multipart/form-data" class="flex items-center justify-between gap-3 flex-wrap">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">
                        <?= count($project['documents']) ?> file<?= count($project['documents']) === 1 ? '' : 's' ?>
                    </span>
                    <div class="flex items-center gap-2">
                        <label class="brutal-border bg-card px-3 py-2 mono text-[10px] uppercase tracking-widest font-black cursor-pointer brutal-hover flex items-center gap-2">
                            <i data-lucide="file-plus" class="w-3.5 h-3.5"></i>
                            Select Files
                            <input type="file" name="documents[]" multiple class="hidden" onchange="document.getElementById('upload-submit-btn').classList.remove('hidden'); document.getElementById('selected-files-summary').innerText = this.files.length + ' file(s) selected';" />
                        </label>
                        <span id="selected-files-summary" class="mono text-[10px] uppercase text-muted-foreground"></span>
                        <button type="submit" id="upload-submit-btn" class="hidden brutal-border bg-foreground text-background mono text-[10px] uppercase tracking-widest font-black px-3 py-2 brutal-hover flex items-center gap-2">
                            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                            Upload
                        </button>
                    </div>
                </form>

                <?php if (empty($project['documents'])): ?>
                    <div class="brutal-border p-4 bg-background mono text-xs text-muted-foreground uppercase tracking-widest text-center">
                        No documents uploaded.
                    </div>
                <?php else: ?>
                    <div class="grid gap-2">
                        <?php foreach ($project['documents'] as $d): ?>
                            <div class="brutal-border p-3 bg-background flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <i data-lucide="file-text" class="w-4 h-4 shrink-0 text-muted-foreground"></i>
                                    <div class="min-w-0">
                                        <div class="font-bold text-sm truncate uppercase"><?= esc($d['name']) ?></div>
                                        <div class="mono text-[10px] text-muted-foreground uppercase tracking-widest">
                                            <?= number_format($d['size'] / 1024, 1) ?> KB · <?= sys_date($d['uploaded_at']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <a href="<?= base_url(esc($d['file_path'])) ?>" download="<?= esc($d['name']) ?>" class="mono text-[10px] uppercase tracking-widest border border-foreground p-1.5 brutal-hover bg-background text-foreground" title="Download">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    </a>
                                    <form action="<?= base_url("project/{$project['id']}/delete-doc/{$d['id']}") ?>" method="POST" class="inline">
                                        <button type="submit" class="mono text-[10px] uppercase tracking-widest border border-foreground p-1.5 brutal-hover bg-background text-destructive" aria-label="Remove">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- History & Chronicles Panel -->
        <div id="status-history-container" class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 bg-primary brutal-border"></div>
                <h3 class="mono text-xs uppercase tracking-widest font-black">Project Chronicles & History</h3>
                <div class="flex-1 h-[2px] bg-border"></div>
            </div>

            <!-- Tab Buttons -->
            <div class="flex border-b border-foreground mb-4">
                <button onclick="switchHistoryTab('chronicle')" id="tab-btn-chronicle" class="px-4 py-2 border-t border-l border-r border-foreground bg-primary text-primary-foreground mono text-xs uppercase tracking-widest font-black">
                    The Chronicle
                </button>
                <button onclick="switchHistoryTab('compare')" id="tab-btn-compare" class="px-4 py-2 border-t border-l border-r border-foreground bg-card text-foreground mono text-xs uppercase tracking-widest font-black border-transparent">
                    Comparative Log
                </button>
                <button onclick="switchHistoryTab('raw')" id="tab-btn-raw" class="px-4 py-2 border-t border-l border-r border-foreground bg-card text-foreground mono text-xs uppercase tracking-widest font-black border-transparent">
                    Raw Logs
                </button>
            </div>

            <!-- Tab Contents -->
            <!-- 1. The Chronicle (Autobiographical Story) -->
            <div id="history-tab-chronicle" class="brutal-border bg-card p-6 max-h-[400px] overflow-y-auto relative brutal-shadow">
                <div class="space-y-6 max-w-3xl mx-auto">
                    <?php if (empty($project['narratives'])): ?>
                        <p class="mono text-xs text-muted-foreground uppercase text-center py-6">The archives are silent. Add or update project details to begin the chronicle.</p>
                    <?php else: ?>
                        <?php foreach ($project['narratives'] as $n): ?>
                            <div class="border-l-2 border-primary pl-4 py-1">
                                <span class="mono text-[9px] text-muted-foreground uppercase tracking-wider block mb-1">// <?= sys_date($n['date']) ?></span>
                                <p class="italic text-sm font-serif text-foreground/90 leading-relaxed font-medium">
                                    "<?= esc($n['sentence']) ?>"
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Comparative Log -->
            <div id="history-tab-compare" class="hidden brutal-border bg-card p-4 max-h-[400px] overflow-y-auto relative brutal-shadow">
                <?php if (empty($project['detailedHistory'])): ?>
                    <p class="mono text-xs text-muted-foreground uppercase text-center py-6">No snapshot history recorded for comparison.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left mono text-xs brutal-border-thin">
                            <thead>
                                <tr class="bg-foreground text-background">
                                    <th class="p-2 brutal-border-thin">Date</th>
                                    <th class="p-2 brutal-border-thin">Action / Event</th>
                                    <th class="p-2 brutal-border-thin">Metrics Change (Before → After)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($project['detailedHistory'] as $h): 
                                    $old = json_decode($h['old_state'], true) ?: [];
                                    $new = json_decode($h['new_state'], true) ?: [];
                                    
                                    // Collect changes
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
                                            if ($k === 'progress') {
                                                $changes[] = "<strong>{$label}</strong>: {$oldVal}% → {$newVal}%";
                                            } elseif ($k === 'status') {
                                                $changes[] = "<strong>{$label}</strong>: " . strtoupper(str_replace('-', ' ', $oldVal)) . " → " . strtoupper(str_replace('-', ' ', $newVal));
                                            } else {
                                                $changes[] = "<strong>{$label}</strong>: {$oldVal} → {$newVal}";
                                            }
                                        }
                                    }
                                ?>
                                    <tr class="hover:bg-secondary/20">
                                        <td class="p-2 brutal-border-thin whitespace-nowrap align-top"><?= sys_date($h['date']) ?></td>
                                        <td class="p-2 brutal-border-thin font-bold align-top"><?= esc($h['activity']) ?></td>
                                        <td class="p-2 brutal-border-thin align-top space-y-1">
                                            <?php if (empty($changes)): ?>
                                                <span class="text-muted-foreground uppercase text-[10px]">No tracked metric changes</span>
                                            <?php else: ?>
                                                <ul class="list-disc pl-4 space-y-0.5">
                                                    <?php foreach ($changes as $c): ?>
                                                        <li><?= $c ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 3. Raw Logs -->
            <div id="history-tab-raw" class="hidden brutal-border bg-card p-4 max-h-[400px] overflow-y-auto relative brutal-shadow">
                <div class="relative pl-6 py-2">
                    <div class="absolute left-2 top-2 bottom-2 w-[2px] bg-border"></div>
                    <?php foreach ($project['statusHistory'] as $index => $h): ?>
                        <div class="relative mb-6 last:mb-0">
                            <div class="absolute -left-[22px] top-1 w-3.5 h-3.5 brutal-border bg-primary"></div>
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="mono text-xs text-muted-foreground"><?= sys_date($h['date']) ?></span>
                                <span class="mono text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 border border-foreground text-background <?= $statusBgs[$h['status']] ?? 'bg-status-' . $h['status'] ?>">
                                    <?= $statusLabels[$h['status']] ?? strtoupper($h['status']) ?>
                                </span>
                            </div>
                            <p class="text-sm mt-1.5 font-bold uppercase"><?= esc($h['note']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ==================== CREATE PHASE MODAL ==================== -->
<div id="create-phase-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow max-h-[90vh] flex flex-col">
        <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="mono font-black uppercase tracking-tight">Add Project Phase</h3>
            <button type="button" onclick="closeModal('create-phase-modal')" class="text-background font-black hover:opacity-75">✕</button>
        </div>
        <form id="create-phase-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('create-phase-form', '<?= base_url("project/{$project['id']}/phase/create") ?>');">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Phase Name</span>
                <input name="name" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Description</span>
                <textarea name="description" rows="2" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Start Date</span>
                    <input type="date" name="start" required value="<?= date('Y-m-d') ?>" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">End Date</span>
                    <input type="date" name="end" required value="<?= date('Y-m-d', strtotime('+1 month')) ?>" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Status</span>
                <select name="status" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                    <?php foreach ($statusLabels as $slug => $label): ?>
                        <option value="<?= $slug ?>"><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="border-t border-foreground pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-phase-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== EDIT PHASE MODAL ==================== -->
<div id="edit-phase-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow max-h-[90vh] flex flex-col">
        <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="mono font-black uppercase tracking-tight">Edit Project Phase</h3>
            <button type="button" onclick="closeModal('edit-phase-modal')" class="text-background font-black hover:opacity-75">✕</button>
        </div>
        <form id="edit-phase-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('edit-phase-form', this.action);">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Phase Name</span>
                <input id="edit-phase-name" name="name" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Description</span>
                <textarea id="edit-phase-description" name="description" rows="2" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none"></textarea>
            </div>
            <div id="phase-dates-warning" class="hidden text-[10px] text-red-600 font-bold uppercase tracking-tight my-1">
                * Dates are determined by subtasks and cannot be edited directly.
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Start Date</span>
                    <input id="edit-phase-start" type="date" name="start" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">End Date</span>
                    <input id="edit-phase-end" type="date" name="end" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Status</span>
                <select id="edit-phase-status" name="status" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                    <?php foreach ($statusLabels as $slug => $label): ?>
                        <option value="<?= $slug ?>"><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="border-t border-foreground pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-phase-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== CREATE DEPENDENCY MODAL ==================== -->
<div id="create-dependency-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow max-h-[90vh] flex flex-col">
        <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="mono font-black uppercase tracking-tight">Add Dependency</h3>
            <button type="button" onclick="closeModal('create-dependency-modal')" class="text-background font-black hover:opacity-75">✕</button>
        </div>
        <form id="create-dependency-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('create-dependency-form', '<?= base_url("project/{$project['id']}/dependency/create") ?>');">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Dependent Project</span>
                <select id="dependency-project-select" name="dep_project_id" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                    <option value="">-- SELECT PROJECT --</option>
                    <?php foreach ($allProjects as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= esc($p['name']) ?> [<?= esc($p['status']) ?>]</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="custom-dep-name-container" class="flex flex-col gap-1 hidden">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Dependency Name (Free Type)</span>
                <input id="custom-dep-name-input" name="dep_project_name" placeholder="e.g. External API / Vendor Task" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Relationship Type</span>
                <select id="dependency-type-select" name="type" onchange="toggleCustomDependencyType(this)" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                    <option value="depends-on">Depends On</option>
                    <option value="blocks">Blocks</option>
                    <option value="others">Others</option>
                </select>
            </div>
            <div id="custom-dependency-type-container" class="flex flex-col gap-1 hidden">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Custom Relationship Type</span>
                <input id="custom-dependency-type-input" name="custom_type" maxlength="20" placeholder="e.g. Integrates With" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="border-t border-foreground pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-dependency-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== CREATE ESCALATION MODAL ==================== -->
<div id="create-escalation-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow max-h-[90vh] flex flex-col">
        <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="mono font-black uppercase tracking-tight">Add Escalation</h3>
            <button type="button" onclick="closeModal('create-escalation-modal')" class="text-background font-black hover:opacity-75">✕</button>
        </div>
        <form id="create-escalation-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('create-escalation-form', '<?= base_url("project/{$project['id']}/escalation/create") ?>');">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Escalation Level</span>
                <select name="level" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                    <option value="1">Level 1 - Internal Alert</option>
                    <option value="2">Level 2 - Stakeholder Attention</option>
                    <option value="3">Level 3 - Executive Action Required</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Escalate To</span>
                <input name="to_recipient" placeholder="Recipient Name or Role" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Recipient Email</span>
                <input type="email" name="recipient_email" placeholder="recipient@example.com" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="escalation-send-email" name="send_email" value="1" class="border border-foreground bg-background focus:ring-0 focus:outline-none cursor-pointer" />
                <label for="escalation-send-email" class="mono text-[10px] uppercase tracking-widest text-muted-foreground select-none cursor-pointer">Send email notification</label>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Escalation Note / Details</span>
                <textarea name="note" rows="3" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none"></textarea>
            </div>
            <div class="border-t border-foreground pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-escalation-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== CREATE RISK MODAL ==================== -->
<div id="create-risk-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow max-h-[90vh] flex flex-col">
        <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="mono font-black uppercase tracking-tight">Add Risk or Issue</h3>
            <button type="button" onclick="closeModal('create-risk-modal')" class="text-background font-black hover:opacity-75">✕</button>
        </div>
        <form id="create-risk-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('create-risk-form', '<?= base_url("project/{$project['id']}/risk/create") ?>');">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Title / Concern</span>
                <input name="title" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Severity</span>
                    <select name="severity" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                        <option value="low">Low</option>
                        <option value="med">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Classification</span>
                    <select name="type" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                        <option value="risk">Risk (Potential)</option>
                        <option value="issue">Issue (Active)</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Owner</span>
                <input name="owner" placeholder="Resource Name" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Notification Email</span>
                <input type="email" name="notification_email" placeholder="owner@example.com" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="risk-send-email" name="send_email" value="1" class="border border-foreground bg-background focus:ring-0 focus:outline-none cursor-pointer" />
                <label for="risk-send-email" class="mono text-[10px] uppercase tracking-widest text-muted-foreground select-none cursor-pointer">Send email notification</label>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Mitigation Strategy</span>
                <textarea name="mitigation" rows="2" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none"></textarea>
            </div>
            <div class="border-t border-foreground pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-risk-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== CREATE ACTION ITEM MODAL ==================== -->
<div id="create-action-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow max-h-[90vh] flex flex-col">
        <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="mono font-black uppercase tracking-tight">Add Action Item</h3>
            <button type="button" onclick="closeModal('create-action-modal')" class="text-background font-black hover:opacity-75">✕</button>
        </div>
        <form id="create-action-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('create-action-form', '<?= base_url("project/{$project['id']}/action/create") ?>');">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Task Title</span>
                <input name="title" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Owner</span>
                    <input name="owner" placeholder="Resource Name" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Due Date</span>
                    <input type="date" name="due" required value="<?= date('Y-m-d') ?>" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
                </div>
            </div>
            <div class="border-t border-foreground pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-action-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>
<!-- ==================== EDIT HEALTH MODAL ==================== -->
<div id="edit-health-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow max-h-[90vh] flex flex-col">
        <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="mono font-black uppercase tracking-tight">Edit Health Summary</h3>
            <button type="button" onclick="closeModal('edit-health-modal')" class="text-background font-black hover:opacity-75">✕</button>
        </div>
        <form id="edit-health-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('edit-health-form', '<?= base_url("project/{$project['id']}/health/update") ?>');">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Health Status / Summary</span>
                <select id="edit-health-input" name="health" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                    <option value="">-- SELECT HEALTH STATUS --</option>
                    <?php foreach ($statusLabels as $slug => $label): ?>
                        <option value="<?= $slug ?>"><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Project Description</span>
                <textarea id="edit-description-input" name="description" rows="4" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none"><?= esc($project['description']) ?></textarea>
            </div>
            <div class="border-t border-foreground pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-health-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>
<!-- ==================== CREATE SUBTASK MODAL ==================== -->
<div id="create-subtask-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow max-h-[90vh] flex flex-col">
        <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="mono font-black uppercase tracking-tight">Add Phase Subtask</h3>
            <button type="button" onclick="closeModal('create-subtask-modal')" class="text-background font-black hover:opacity-75">✕</button>
        </div>
        <form id="create-subtask-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('create-subtask-form', this.action);">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Subtask Name</span>
                <input name="name" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Description</span>
                <textarea name="description" rows="2" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Start Date</span>
                    <input type="date" name="start" required value="<?= date('Y-m-d') ?>" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">End Date</span>
                    <input type="date" name="end" required value="<?= date('Y-m-d', strtotime('+1 week')) ?>" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Status</span>
                <select name="status" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                    <?php foreach ($statusLabels as $slug => $label): ?>
                        <option value="<?= $slug ?>"><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="border-t border-foreground pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-subtask-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== EDIT SUBTASK MODAL ==================== -->
<div id="edit-subtask-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow max-h-[90vh] flex flex-col">
        <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="mono font-black uppercase tracking-tight">Edit Subtask</h3>
            <button type="button" onclick="closeModal('edit-subtask-modal')" class="text-background font-black hover:opacity-75">✕</button>
        </div>
        <form id="edit-subtask-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('edit-subtask-form', this.action);">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Subtask Name</span>
                <input id="edit-subtask-name" name="name" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Description</span>
                <textarea id="edit-subtask-description" name="description" rows="2" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Start Date</span>
                    <input id="edit-subtask-start" type="date" name="start" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">End Date</span>
                    <input id="edit-subtask-end" type="date" name="end" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Status</span>
                <select id="edit-subtask-status" name="status" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                    <?php foreach ($statusLabels as $slug => $label): ?>
                        <option value="<?= $slug ?>"><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="border-t border-foreground pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-subtask-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== EDIT ESCALATION MODAL ==================== -->
<div id="edit-escalation-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow max-h-[90vh] flex flex-col">
        <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="mono font-black uppercase tracking-tight">View / Edit Escalation</h3>
            <button type="button" onclick="closeModal('edit-escalation-modal')" class="text-background font-black hover:opacity-75">✕</button>
        </div>
        <form id="edit-escalation-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('edit-escalation-form', this.action);">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Escalation Level</span>
                <select id="edit-esc-level" name="level" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                    <option value="1">Level 1 - Internal Alert</option>
                    <option value="2">Level 2 - Stakeholder Attention</option>
                    <option value="3">Level 3 - Executive Action Required</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Escalate To</span>
                <input id="edit-esc-to" name="to_recipient" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Escalation Note / Details</span>
                <textarea id="edit-esc-note" name="note" rows="3" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none"></textarea>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Status</span>
                <select id="edit-esc-status" name="status" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                    <option value="active">Active</option>
                    <option value="resolved">Resolved</option>
                    <option value="deferred">Deferred</option>
                    <option value="others">Others</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Reason (optional)</span>
                <input id="edit-esc-reason" name="reason" placeholder="e.g. Awaiting vendor response" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="border-t border-foreground pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-escalation-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== EDIT RISK MODAL ==================== -->
<div id="edit-risk-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow max-h-[90vh] flex flex-col">
        <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="mono font-black uppercase tracking-tight">View / Edit Risk or Issue</h3>
            <button type="button" onclick="closeModal('edit-risk-modal')" class="text-background font-black hover:opacity-75">✕</button>
        </div>
        <form id="edit-risk-form" class="overflow-y-auto flex-1 p-6 space-y-4 text-left" onsubmit="event.preventDefault(); submitAjaxForm('edit-risk-form', this.action);">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Title / Concern</span>
                <input id="edit-risk-title" name="title" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Severity</span>
                    <select id="edit-risk-severity" name="severity" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                        <option value="low">Low</option>
                        <option value="med">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Classification</span>
                    <select id="edit-risk-type" name="type" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                        <option value="risk">Risk (Potential)</option>
                        <option value="issue">Issue (Active)</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Owner</span>
                <input id="edit-risk-owner" name="owner" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Mitigation Strategy</span>
                <textarea id="edit-risk-mitigation" name="mitigation" rows="2" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none"></textarea>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Status</span>
                <select id="edit-risk-status" name="status" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none uppercase">
                    <option value="active">Active</option>
                    <option value="resolved">Resolved</option>
                    <option value="deferred">Deferred</option>
                    <option value="others">Others</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Reason (optional)</span>
                <input id="edit-risk-reason" name="reason" placeholder="e.g. Risk accepted by PM" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none" />
            </div>
            <div class="border-t border-foreground pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-risk-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const expandedPhases = new Set();
    const expandedTimelinePhases = new Set();
    let globalTimelineSubtasksVisible = false;
    let activeHistoryTab = 'chronicle';

    function switchHistoryTab(tabName) {
        activeHistoryTab = tabName;
        // Hide all tabs
        document.getElementById('history-tab-chronicle').classList.add('hidden');
        document.getElementById('history-tab-compare').classList.add('hidden');
        document.getElementById('history-tab-raw').classList.add('hidden');
        
        // Show selected tab
        document.getElementById('history-tab-' + tabName).classList.remove('hidden');
        
        // Reset button styles
        const btnChronicle = document.getElementById('tab-btn-chronicle');
        const btnCompare = document.getElementById('tab-btn-compare');
        const btnRaw = document.getElementById('tab-btn-raw');
        
        if (btnChronicle && btnCompare && btnRaw) {
            [btnChronicle, btnCompare, btnRaw].forEach(btn => {
                btn.classList.remove('bg-primary', 'text-primary-foreground');
                btn.classList.add('bg-card', 'text-foreground');
                btn.classList.add('border-transparent');
            });
            
            // Highlight active button
            const activeBtn = document.getElementById('tab-btn-' + tabName);
            activeBtn.classList.remove('bg-card', 'text-foreground', 'border-transparent');
            activeBtn.classList.add('bg-primary', 'text-primary-foreground');
        }
    }

    // Refresh DOM blocks from fresh server fetch response
    function refreshBlocks() {
        fetch(window.location.href)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            const sections = [
                '#stats-container',
                '#timeline-container',
                '#health-container',
                '#phases-container',
                '#resources-container',
                '#dependencies-container',
                '#escalations-container',
                '#risks-container',
                '#actions-container',
                '#status-history-container'
            ];
            sections.forEach(selector => {
                const oldEl = document.querySelector(selector);
                const newEl = doc.querySelector(selector);
                if (oldEl && newEl) {
                    oldEl.innerHTML = newEl.innerHTML;
                }
            });
            
            // Re-apply expanded states
            expandedPhases.forEach(phaseId => {
                const rows = document.querySelectorAll('.subtask-row-' + phaseId);
                const chevron = document.getElementById('chevron-' + phaseId);
                rows.forEach(row => row.classList.remove('hidden'));
                if (chevron) {
                    chevron.classList.add('rotate-90');
                }
            });

            // Re-apply timeline subtasks expanded states
            if (globalTimelineSubtasksVisible) {
                const allRows = document.querySelectorAll('[class*="timeline-subtask-row-"]');
                const allChevrons = document.querySelectorAll('[id*="timeline-chevron-"]');
                allRows.forEach(row => row.classList.remove('hidden'));
                allChevrons.forEach(chevron => chevron.style.transform = 'rotate(90deg)');
                const btn = document.getElementById('toggle-all-timeline-subtasks-btn');
                if (btn) btn.innerText = 'Hide Subtasks';
            } else {
                expandedTimelinePhases.forEach(phaseId => {
                    const rows = document.querySelectorAll('.timeline-subtask-row-' + phaseId);
                    const chevron = document.getElementById('timeline-chevron-' + phaseId);
                    rows.forEach(row => row.classList.remove('hidden'));
                    if (chevron) {
                        chevron.style.transform = 'rotate(90deg)';
                    }
                });
                updateGlobalTimelineSubtaskButtonState();
            }

            if (window.lucide) {
                window.lucide.createIcons();
            }
            initDragAndDrop();
            switchHistoryTab(activeHistoryTab);
        })
        .catch(err => console.error('Error refreshing blocks:', err));
    }

    // Timeline Subtasks Toggling
    function toggleSingleTimelineSubtask(phaseId, button) {
        const rows = document.querySelectorAll('.timeline-subtask-row-' + phaseId);
        const chevron = document.getElementById('timeline-chevron-' + phaseId);
        
        let shouldHide = false;
        if (expandedTimelinePhases.has(phaseId)) {
            expandedTimelinePhases.delete(phaseId);
            shouldHide = true;
        } else {
            expandedTimelinePhases.add(phaseId);
        }

        rows.forEach(row => {
            if (shouldHide) {
                row.classList.add('hidden');
            } else {
                row.classList.remove('hidden');
            }
        });
        
        if (chevron) {
            if (shouldHide) {
                chevron.style.transform = 'rotate(0deg)';
            } else {
                chevron.style.transform = 'rotate(90deg)';
            }
        }
        
        updateGlobalTimelineSubtaskButtonState();
    }

    function toggleAllTimelineSubtasks() {
        const btn = document.getElementById('toggle-all-timeline-subtasks-btn');
        const allRows = document.querySelectorAll('[class*="timeline-subtask-row-"]');
        const allChevrons = document.querySelectorAll('[id*="timeline-chevron-"]');
        
        // Check if any subtask row is hidden
        let hasHidden = false;
        allRows.forEach(row => {
            if (row.classList.contains('hidden')) {
                hasHidden = true;
            }
        });
        
        globalTimelineSubtasksVisible = hasHidden;
        expandedTimelinePhases.clear();

        allRows.forEach(row => {
            if (globalTimelineSubtasksVisible) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
        
        allChevrons.forEach(chevron => {
            const phaseId = parseInt(chevron.id.replace('timeline-chevron-', ''), 10);
            if (globalTimelineSubtasksVisible) {
                chevron.style.transform = 'rotate(90deg)';
                expandedTimelinePhases.add(phaseId);
            } else {
                chevron.style.transform = 'rotate(0deg)';
            }
        });
        
        btn.innerText = globalTimelineSubtasksVisible ? 'Hide Subtasks' : 'Show Subtasks';
    }

    function updateGlobalTimelineSubtaskButtonState() {
        const btn = document.getElementById('toggle-all-timeline-subtasks-btn');
        if (!btn) return;
        const allRows = document.querySelectorAll('[class*="timeline-subtask-row-"]');
        if (allRows.length === 0) return;
        
        let hasHidden = false;
        allRows.forEach(row => {
            if (row.classList.contains('hidden')) {
                hasHidden = true;
            }
        });
        
        globalTimelineSubtasksVisible = !hasHidden;
        btn.innerText = hasHidden ? 'Show Subtasks' : 'Hide Subtasks';
    }

    // Modal helpers
    function openCreatePhaseModal() {
        document.getElementById('create-phase-modal').classList.remove('hidden');
    }

    function openEditPhaseModal(ph) {
        document.getElementById('edit-phase-form').action = '<?= base_url("project/{$project['id']}/phase/update") ?>/' + ph.id;
        document.getElementById('edit-phase-name').value = ph.name;
        document.getElementById('edit-phase-description').value = ph.description || '';
        
        const startInput = document.getElementById('edit-phase-start');
        const endInput = document.getElementById('edit-phase-end');
        startInput.value = ph.start;
        endInput.value = ph.end;
        
        const hasSubtasks = ph.subtasks && ph.subtasks.length > 0;
        if (hasSubtasks) {
            startInput.setAttribute('readonly', 'readonly');
            endInput.setAttribute('readonly', 'readonly');
            startInput.classList.add('bg-secondary', 'cursor-not-allowed');
            endInput.classList.add('bg-secondary', 'cursor-not-allowed');
            document.getElementById('phase-dates-warning').classList.remove('hidden');
        } else {
            startInput.removeAttribute('readonly');
            endInput.removeAttribute('readonly');
            startInput.classList.remove('bg-secondary', 'cursor-not-allowed');
            endInput.classList.remove('bg-secondary', 'cursor-not-allowed');
            document.getElementById('phase-dates-warning').classList.add('hidden');
        }
        
        document.getElementById('edit-phase-status').value = ph.status;
        document.getElementById('edit-phase-modal').classList.remove('hidden');
    }

    function toggleSubtasks(phaseId, button) {
        const rows = document.querySelectorAll('.subtask-row-' + phaseId);
        const chevron = document.getElementById('chevron-' + phaseId);
        
        let shouldHide = false;
        if (expandedPhases.has(phaseId)) {
            expandedPhases.delete(phaseId);
            shouldHide = true;
        } else {
            expandedPhases.add(phaseId);
        }

        rows.forEach(row => {
            if (shouldHide) {
                row.classList.add('hidden');
            } else {
                row.classList.remove('hidden');
            }
        });
        if (chevron) {
            if (shouldHide) {
                chevron.classList.remove('rotate-90');
            } else {
                chevron.classList.add('rotate-90');
            }
        }
    }

    function openCreateSubtaskModal(phaseId) {
        document.getElementById('create-subtask-form').action = '<?= base_url("project/{$project['id']}/phase") ?>/' + phaseId + '/subtask/create';
        document.getElementById('create-subtask-modal').classList.remove('hidden');
    }

    function openEditSubtaskModal(sub, phaseId) {
        document.getElementById('edit-subtask-form').action = '<?= base_url("project/{$project['id']}/phase") ?>/' + phaseId + '/subtask/update/' + sub.id;
        document.getElementById('edit-subtask-name').value = sub.name;
        document.getElementById('edit-subtask-description').value = sub.description || '';
        document.getElementById('edit-subtask-start').value = sub.start;
        document.getElementById('edit-subtask-end').value = sub.end;
        document.getElementById('edit-subtask-status').value = sub.status;
        document.getElementById('edit-subtask-modal').classList.remove('hidden');
    }

    function openCreateDependencyModal() {
        document.getElementById('create-dependency-modal').classList.remove('hidden');
    }

    function openCreateEscalationModal() {
        document.getElementById('create-escalation-modal').classList.remove('hidden');
    }

    function openCreateRiskModal() {
        document.getElementById('create-risk-modal').classList.remove('hidden');
    }

    function openCreateActionModal() {
        document.getElementById('create-action-modal').classList.remove('hidden');
    }

    function openEditHealthModal() {
        const healthVal = document.getElementById('health-display-text').getAttribute('data-value');
        const descText = document.getElementById('description-display-text').innerText;
        document.getElementById('edit-health-input').value = healthVal;
        document.getElementById('edit-description-input').value = descText;
        document.getElementById('edit-health-modal').classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        if (id === 'create-dependency-modal') {
            const container = document.getElementById('custom-dependency-type-container');
            const input = document.getElementById('custom-dependency-type-input');
            const select = document.getElementById('dependency-type-select');
            const projSelect = document.getElementById('dependency-project-select');
            const nameContainer = document.getElementById('custom-dep-name-container');
            const nameInput = document.getElementById('custom-dep-name-input');
            
            if (container) container.classList.add('hidden');
            if (input) {
                input.value = '';
                input.removeAttribute('required');
            }
            if (select) select.value = 'depends-on';
            if (projSelect) {
                projSelect.value = '';
                projSelect.setAttribute('required', 'required');
            }
            if (nameContainer) nameContainer.classList.add('hidden');
            if (nameInput) {
                nameInput.value = '';
                nameInput.removeAttribute('required');
            }
        }
    }

    function openEditEscalationModal(esc) {
        document.getElementById('edit-escalation-form').action = '<?= base_url("project/{$project['id']}/escalation/update") ?>/' + esc.id;
        document.getElementById('edit-esc-level').value = esc.level || '1';
        document.getElementById('edit-esc-to').value = esc.to_recipient || '';
        document.getElementById('edit-esc-note').value = esc.note || '';
        document.getElementById('edit-esc-status').value = esc.status || 'active';
        document.getElementById('edit-esc-reason').value = esc.reason || '';
        document.getElementById('edit-escalation-modal').classList.remove('hidden');
    }

    function openEditRiskModal(risk) {
        document.getElementById('edit-risk-form').action = '<?= base_url("project/{$project['id']}/risk/update") ?>/' + risk.id;
        document.getElementById('edit-risk-title').value = risk.title || '';
        document.getElementById('edit-risk-severity').value = risk.severity || 'low';
        document.getElementById('edit-risk-type').value = risk.type || 'risk';
        document.getElementById('edit-risk-owner').value = risk.owner || '';
        document.getElementById('edit-risk-mitigation').value = risk.mitigation || '';
        document.getElementById('edit-risk-status').value = risk.status || 'active';
        document.getElementById('edit-risk-reason').value = risk.reason || '';
        document.getElementById('edit-risk-modal').classList.remove('hidden');
    }

    // Click outside to close modals
    window.onclick = function(event) {
        const modals = ['create-phase-modal', 'edit-phase-modal', 'create-dependency-modal', 'create-escalation-modal', 'create-risk-modal', 'create-action-modal', 'edit-health-modal', 'create-subtask-modal', 'edit-subtask-modal', 'edit-escalation-modal', 'edit-risk-modal'];
        modals.forEach(id => {
            const modal = document.getElementById(id);
            if (event.target === modal) {
                modal.classList.add('hidden');
            }
        });
    }

    // AJAX Form submission
    function submitAjaxForm(formId, url) {
        const form = document.getElementById(formId);
        if (!form) return;
        const formData = new FormData(form);
        
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                form.reset();
                const modal = form.closest('[id$="-modal"]');
                if (modal) modal.classList.add('hidden');
                refreshBlocks();
            } else {
                alert(data.message || 'An error occurred.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred.');
        });
    }

    // AJAX Delete helpers
    function deleteAjaxItem(url) {
        if (!confirm('Are you sure you want to delete this item?')) return;
        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                refreshBlocks();
            } else {
                alert(data.message || 'An error occurred.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred.');
        });
    }

    // AJAX Resource unassignment
    function unassignResource(projectId, resourceId) {
        if (!confirm('Are you sure you want to unassign this resource?')) return;
        const formData = new FormData();
        formData.append('resource_id', resourceId);
        fetch('<?= base_url('project') ?>/' + projectId + '/resources/unassign', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                refreshBlocks();
            } else {
                alert(data.message || 'An error occurred.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred.');
        });
    }

    // AJAX Snappy toggle action item
    function toggleActionItem(projectId, actionId, button) {
        fetch('<?= base_url('project') ?>/' + projectId + '/toggle-action/' + actionId, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const titleSpan = button.nextElementSibling;
                if (data.done) {
                    button.classList.add('bg-primary');
                    button.classList.remove('bg-background');
                    button.innerHTML = '<span class="mono text-[10px] font-black text-primary-foreground">X</span>';
                    titleSpan.classList.add('line-through', 'text-muted-foreground');
                } else {
                    button.classList.remove('bg-primary');
                    button.classList.add('bg-background');
                    button.innerHTML = '';
                    titleSpan.classList.remove('line-through', 'text-muted-foreground');
                }
                refreshBlocks(); // keep everything synced (e.g. timeline etc)
            }
        })
        .catch(err => {
            console.error('Error toggling action item:', err);
        });
    }

    function syncResourceId(input) {
        const val = input.value.trim();
        const list = document.getElementById('resource-options');
        const hiddenInput = document.getElementById('resource-id-hidden');
        let foundId = '';
        if (list) {
            const options = list.options;
            for (let i = 0; i < options.length; i++) {
                if (options[i].value.trim() === val) {
                    foundId = options[i].getAttribute('data-id');
                    break;
                }
            }
        }
        hiddenInput.value = foundId;
        
        if (val !== '' && foundId === '') {
            input.setCustomValidity('Please select a valid resource from the list.');
        } else {
            input.setCustomValidity('');
        }
    }

    function toggleCustomDependencyType(select) {
        const container = document.getElementById('custom-dependency-type-container');
        const input = document.getElementById('custom-dependency-type-input');
        const projSelect = document.getElementById('dependency-project-select');
        const nameContainer = document.getElementById('custom-dep-name-container');
        const nameInput = document.getElementById('custom-dep-name-input');
        
        if (select.value === 'others') {
            container.classList.remove('hidden');
            input.setAttribute('required', 'required');
            
            if (projSelect) projSelect.removeAttribute('required');
            if (nameContainer) nameContainer.classList.remove('hidden');
            if (nameInput) {
                nameInput.setAttribute('required', 'required');
                nameInput.focus();
            }
        } else {
            container.classList.add('hidden');
            input.removeAttribute('required');
            input.value = '';
            
            if (projSelect) projSelect.setAttribute('required', 'required');
            if (nameContainer) nameContainer.classList.add('hidden');
            if (nameInput) {
                nameInput.removeAttribute('required');
                nameInput.value = '';
            }
        }
    }

    // HTML5 Drag & Drop Reordering logic
    let dragSrcEl = null;

    function initDragAndDrop() {
        const rows = document.querySelectorAll('.phase-drag-row');
        rows.forEach(row => {
            row.addEventListener('dragstart', handleDragStart, false);
            row.addEventListener('dragenter', handleDragEnter, false);
            row.addEventListener('dragover', handleDragOver, false);
            row.addEventListener('dragleave', handleDragLeave, false);
            row.addEventListener('drop', handleDrop, false);
            row.addEventListener('dragend', handleDragEnd, false);
        });
    }

    function handleDragStart(e) {
        this.classList.add('opacity-50');
        dragSrcEl = this;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', this.getAttribute('data-id'));
    }

    function handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.dataTransfer.dropEffect = 'move';
        this.classList.add('bg-secondary/40');
        return false;
    }

    function handleDragEnter(e) {}

    function handleDragLeave(e) {
        this.classList.remove('bg-secondary/40');
    }

    function handleDrop(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        this.classList.remove('bg-secondary/40');
        
        if (dragSrcEl !== this) {
            const dragId = e.dataTransfer.getData('text/plain');
            const dropId = this.getAttribute('data-id');
            
            const rows = Array.from(document.querySelectorAll('.phase-drag-row'));
            const order = rows.map(r => r.getAttribute('data-id'));
            
            const dragIndex = order.indexOf(dragId);
            const dropIndex = order.indexOf(dropId);
            
            // Re-arrange array
            order.splice(dragIndex, 1);
            order.splice(dropIndex, 0, dragId);
            
            fetch('<?= base_url("project/{$project['id']}/phases/reorder") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(order)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    refreshBlocks();
                } else {
                    alert(data.message || 'Failed to reorder phases.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred.');
            });
        }
        return false;
    }

    function handleDragEnd(e) {
        this.classList.remove('opacity-50');
        const rows = document.querySelectorAll('.phase-drag-row');
        rows.forEach(row => {
            row.classList.remove('bg-secondary/40');
        });
    }

    // Initialize drag & drop on load
    document.addEventListener('DOMContentLoaded', () => {
        initDragAndDrop();
    });
</script>
<?= $this->endSection() ?>
