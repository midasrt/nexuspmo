<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<?php
function getSpanPct($startDateStr, $endDateStr) {
    $currentYear = (int)date('Y');
    $yearStart = strtotime("{$currentYear}-01-01");
    $yearEnd = strtotime("{$currentYear}-12-31");
    $total = $yearEnd - $yearStart;
    
    $start = strtotime($startDateStr);
    $end = strtotime($endDateStr);
    
    $start = max($yearStart, min($yearEnd, $start));
    $end = max($yearStart, min($yearEnd, $end));
    
    $left = (($start - $yearStart) / $total) * 100;
    $width = (($end - $start) / $total) * 100;
    
    if ($width < 1) $width = 1;
    return ['left' => $left, 'width' => $width];
}

$currentYear = (int)date('Y');
$todayLeft = null;
$now = time();
$yearStart = strtotime("{$currentYear}-01-01");
$yearEnd = strtotime("{$currentYear}-12-31");
if ($now >= $yearStart && $now <= $yearEnd) {
    $todayLeft = (($now - $yearStart) / ($yearEnd - $yearStart)) * 100;
}

$months = ["JAN","FEB","MAR","APR","MAY","JUN","JUL","AUG","SEP","OCT","NOV","DEC"];

// Gather action item reminders
$overdueReminders = [];
$dueTodayReminders = [];
$dueSoonReminders = [];
$todayStr = date('Y-m-d');
$threeDaysFromNow = date('Y-m-d', strtotime('+7 days'));

foreach ($projects as $p) {
    if (isset($p['actionItems'])) {
        foreach ($p['actionItems'] as $a) {
            if ($a['done'] == 0) {
                $reminder = [
                    'project_name' => $p['name'],
                    'project_id'   => $p['id'],
                    'project_code' => $p['code'],
                    'title'        => $a['title'],
                    'owner'        => $a['owner'],
                    'due'          => $a['due'],
                ];
                if ($a['due'] < $todayStr) {
                    $overdueReminders[] = $reminder;
                } elseif ($a['due'] === $todayStr) {
                    $dueTodayReminders[] = $reminder;
                } elseif ($a['due'] <= $threeDaysFromNow) {
                    $dueSoonReminders[] = $reminder;
                }
            }
        }
    }
}
usort($overdueReminders, function($a, $b) { return strcmp($a['due'], $b['due']); });
usort($dueSoonReminders, function($a, $b) { return strcmp($a['due'], $b['due']); });

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

// Default fallbacks in case table is empty or status field has no keys
$defaultSlugs = ['on-track', 'at-risk', 'blocked', 'delayed', 'backlog'];
$defaultLabels = ['ON TRACK', 'AT RISK', 'BLOCKED', 'DELAYED', 'BACKLOG'];
foreach ($defaultSlugs as $i => $s) {
    if (!isset($statusLabels[$s])) $statusLabels[$s] = $defaultLabels[$i];
    if (!isset($statusBgs[$s])) $statusBgs[$s] = 'bg-status-' . $s;
}
?>

<div class="min-h-screen">
    <!-- Header Component -->
    <header class="border-b border-ink/15 py-6 no-print cascade-in" style="animation-delay: 0ms;">
        <div class="w-full px-8 lg:px-14 flex items-center justify-between gap-4">
            <div>
                <span class="eyebrow">Projects</span>
                <h1 class="font-display text-2xl md:text-3xl font-black uppercase mt-1 tracking-tight">Atlas 2026</h1>
            </div>
            <div class="flex items-center gap-3">
                <!-- Logged in status -->
                <?php if (session()->get('isLoggedIn')): ?>
                    <div class="text-right">
                        <span class="mono text-[10px] text-muted-foreground uppercase"><?= esc(session()->get('role')) ?></span>
                        <div class="mono text-xs font-bold uppercase"><?= esc(session()->get('name')) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="w-full px-8 lg:px-14">
        <!-- Editorial lead -->
        <div class="grid grid-cols-12 gap-6 py-8">
            <div class="col-span-12 lg:col-span-7 cascade-in" style="animation-delay: 50ms;">
                <p class="font-display text-[clamp(1.4rem,2vw,2rem)] leading-tight tracking-tight">
                    A working register of every initiative shaping 2026 at a glance.
                </p>
            </div>
            <div class="col-span-12 lg:col-span-5 lg:pl-10 lg:border-l border-ink/15 cascade-in" style="animation-delay: 100ms;">
                <div class="grid grid-cols-3 gap-6">
                    <div>
                        <div class="eyebrow">Projects</div>
                        <div class="font-display text-4xl mt-1"><?= $total ?></div>
                    </div>
                    <div>
                        <div class="eyebrow">In flight</div>
                        <?php 
                        $inFlight = 0;
                        foreach ($projects as $pr) {
                            if ($pr['status'] !== 'backlog') $inFlight++;
                        }
                        ?>
                        <div class="font-display text-4xl mt-1"><?= $inFlight ?></div>
                    </div>
                    <div>
                        <div class="eyebrow">At risk</div>
                        <?php 
                        $atRisk = 0;
                        foreach ($projects as $pr) {
                            if (in_array($pr['status'], ['at-risk', 'blocked', 'delayed'])) $atRisk++;
                        }
                        ?>
                        <div class="font-display text-4xl mt-1"><?= $atRisk ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status filter strip -->
        <div class="flex flex-wrap items-center gap-2 py-4 border-y border-ink/15 cascade-in" style="animation-delay: 150ms;">
            <span class="eyebrow mr-2">Filter</span>
            <a href="?filter=all" class="group flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-mono uppercase tracking-widest transition-all whitespace-nowrap <?= $filter === 'all' ? 'bg-ink text-paper border-ink' : 'bg-transparent text-ink border-ink/25 hover:border-ink/60' ?>">
                <span class="h-2 w-2 rounded-full bg-foreground"></span>
                ALL
                <span class="ml-1 <?= $filter === 'all' ? 'text-paper/70' : 'text-ink/40' ?>"><?= $total ?></span>
            </a>
            <?php foreach (array_keys($statusLabels) as $s): ?>
                <?php if (($counts[$s] ?? 0) > 0 || $filter === $s): ?>
                    <a href="?filter=<?= $s ?>" class="group flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-mono uppercase tracking-widest transition-all whitespace-nowrap <?= $filter === $s ? 'bg-ink text-paper border-ink' : 'bg-transparent text-ink border-ink/25 hover:border-ink/60' ?>">
                        <span class="h-2 w-2 rounded-full shrink-0" style="background: <?= $statusColors[$s] ?? '#6B7280' ?>"></span>
                        <span class="truncate max-w-[120px]" title="<?= esc($statusLabels[$s] ?? strtoupper($s)) ?>">
                            <?= $statusLabels[$s] ?? strtoupper($s) ?>
                        </span>
                        <span class="ml-1 shrink-0 <?= $filter === $s ? 'text-paper/70' : 'text-ink/40' ?>"><?= $counts[$s] ?? 0 ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Action Item Reminders -->
        <?php if (!empty($overdueReminders) || !empty($dueTodayReminders) || !empty($dueSoonReminders)): ?>
        <div class="py-6 border-b border-ink/15 cascade-in" style="animation-delay: 200ms;">
            <h3 class="font-display text-xl mb-4">Action Item Reminders</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Overdue -->
                <div class="rounded-2xl border border-ink/15 bg-white dark:bg-card/70 p-5 shadow-sm">
                    <span class="eyebrow text-destructive flex items-center gap-1.5 mb-3">
                        <i data-lucide="alert-octagon" class="w-4 h-4"></i> Overdue (<?= count($overdueReminders) ?>)
                    </span>
                    <div class="space-y-3 max-h-48 overflow-y-auto no-scrollbar">
                        <?php if (empty($overdueReminders)): ?>
                            <div class="text-xs text-muted-foreground uppercase py-2 font-mono">No overdue items.</div>
                        <?php else: ?>
                            <?php foreach ($overdueReminders as $r): ?>
                                <div class="border-b border-ink/10 pb-2 last:border-b-0">
                                    <div class="flex justify-between items-start">
                                        <span class="font-display text-sm font-bold text-destructive"><?= esc($r['title']) ?></span>
                                        <span class="mono text-[10px] bg-destructive/10 text-destructive px-1.5 py-0.5 rounded"><?= $r['due'] ?></span>
                                    </div>
                                    <div class="text-[10px] font-mono text-muted-foreground mt-1 flex justify-between">
                                        <span>Owner: <?= esc($r['owner']) ?></span>
                                        <a href="<?= base_url('project/' . $r['project_id']) ?>" class="underline text-ink"><?= esc($r['project_code']) ?></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Due Today -->
                <div class="rounded-2xl border border-ink/15 bg-white dark:bg-card/70 p-5 shadow-sm">
                    <span class="eyebrow text-status-atrisk flex items-center gap-1.5 mb-3">
                        <i data-lucide="clock" class="w-4 h-4"></i> Due Today (<?= count($dueTodayReminders) ?>)
                    </span>
                    <div class="space-y-3 max-h-48 overflow-y-auto no-scrollbar">
                        <?php if (empty($dueTodayReminders)): ?>
                            <div class="text-xs text-muted-foreground uppercase py-2 font-mono">No items due today.</div>
                        <?php else: ?>
                            <?php foreach ($dueTodayReminders as $r): ?>
                                <div class="border-b border-ink/10 pb-2 last:border-b-0">
                                    <div class="flex justify-between items-start">
                                        <span class="font-display text-sm font-bold text-ink"><?= esc($r['title']) ?></span>
                                        <span class="mono text-[10px] bg-ink text-paper px-1.5 py-0.5 rounded">TODAY</span>
                                    </div>
                                    <div class="text-[10px] font-mono text-muted-foreground mt-1 flex justify-between">
                                        <span>Owner: <?= esc($r['owner']) ?></span>
                                        <a href="<?= base_url('project/' . $r['project_id']) ?>" class="underline text-ink"><?= esc($r['project_code']) ?></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Due Soon -->
                <div class="rounded-2xl border border-ink/15 bg-white dark:bg-card/70 p-5 shadow-sm">
                    <span class="eyebrow flex items-center gap-1.5 mb-3">
                        <i data-lucide="calendar" class="w-4 h-4"></i> Due Soon (<?= count($dueSoonReminders) ?>)
                    </span>
                    <div class="space-y-3 max-h-48 overflow-y-auto no-scrollbar">
                        <?php if (empty($dueSoonReminders)): ?>
                            <div class="text-xs text-muted-foreground uppercase py-2 font-mono">No items due soon.</div>
                        <?php else: ?>
                            <?php foreach ($dueSoonReminders as $r): ?>
                                <div class="border-b border-ink/10 pb-2 last:border-b-0">
                                    <div class="flex justify-between items-start">
                                        <span class="font-display text-sm font-bold text-ink"><?= esc($r['title']) ?></span>
                                        <span class="mono text-[10px] text-muted-foreground"><?= $r['due'] ?></span>
                                    </div>
                                    <div class="text-[10px] font-mono text-muted-foreground mt-1 flex justify-between">
                                        <span>Owner: <?= esc($r['owner']) ?></span>
                                        <a href="<?= base_url('project/' . $r['project_id']) ?>" class="underline text-ink"><?= esc($r['project_code']) ?></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Year Gantt Timeline -->
        <div class="py-8 cascade-in" style="animation-delay: 250ms;">
            <div class="flex items-end justify-between mb-4">
                <h2 class="font-display text-2xl tracking-tight">Timeline / <?= $currentYear ?></h2>
                <span class="eyebrow">Project spans</span>
            </div>

            <div class="rounded-2xl border border-ink/15 bg-white dark:bg-card/70 overflow-hidden shadow-sm">
                <!-- Month scale -->
                <div class="grid grid-cols-[220px_1fr] border-b border-ink/15 bg-white dark:bg-card/70">
                    <div class="px-5 py-3 eyebrow border-r border-ink/10">Project</div>
                    <div class="relative grid grid-cols-12">
                        <?php foreach ($months as $i => $m): ?>
                            <div class="px-3 py-3 text-[11px] font-mono uppercase tracking-widest border-r border-ink/10 last:border-r-0">
                                <span class="text-ink/80"><?= $m ?></span>
                                <span class="ml-1 text-ink/35">26</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <ul>
                    <?php if (empty($projects)): ?>
                        <li class="p-8 text-center text-muted-foreground uppercase font-mono text-xs">No projects match the active filter.</li>
                    <?php else: ?>
                        <?php foreach ($projects as $idx => $p): 
                            $span = getSpanPct($p['startDate'], $p['endDate']);
                            $statusColorVal = $statusColors[$p['status']] ?? 'var(--muted)';
                        ?>
                            <li class="grid grid-cols-[220px_1fr] items-stretch border-b border-ink/10 last:border-b-0 group">
                                <a href="<?= base_url('project/' . $p['id']) ?>" class="px-5 py-4 border-r border-ink/10 flex flex-col gap-1 hover:bg-ink/[0.03] transition-colors bg-white dark:bg-card/70">
                                    <span class="font-mono text-[10px] tracking-widest text-muted-foreground"><?= $p['code'] ?></span>
                                    <span class="font-display text-[15px] leading-tight font-bold"><?= esc($p['name']) ?></span>
                                    <span class="text-xs text-muted-foreground truncate"><?= esc($p['owner']) ?> · <?= esc($p['squad']) ?></span>
                                </a>
                                <div class="relative h-[68px] bg-white dark:bg-card/70">
                                    <!-- month grid -->
                                    <div class="absolute inset-0 grid grid-cols-12 pointer-events-none">
                                        <?php for ($i = 0; $i < 12; $i++): ?>
                                            <div class="border-r border-ink/[0.06] last:border-r-0"></div>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if ($todayLeft !== null): ?>
                                        <div class="absolute top-0 bottom-0 w-px bg-ink" style="left: <?= $todayLeft ?>%">
                                            <span class="absolute -top-1 -translate-x-1/2 w-2 h-2 rounded-full bg-ink" />
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="absolute top-1/2 -translate-y-1/2 h-7 rounded-full border border-ink/20 overflow-hidden shadow-sm"
                                         style="left: <?= $span['left'] ?>%; width: <?= $span['width'] ?>%; background: var(--card);"
                                         title="<?= esc($p['name']) ?> (<?= $p['startDate'] ?> to <?= $p['endDate'] ?>)">
                                        <div class="h-full" style="width: <?= $p['progress'] ?>%; background: color-mix(in oklab, <?= $statusColorVal ?> 70%, transparent)"></div>
                                        <div class="absolute inset-0 flex items-center justify-between px-3 text-[10px] font-mono uppercase tracking-widest font-bold"
                                             style="color: color-mix(in oklab, var(--ink) 80%, transparent)">
                                            <span><?= $statusLabels[$p['status']] ?? strtoupper($p['status']) ?></span>
                                            <span><?= $p['progress'] ?>%</span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="py-12">
            <h2 class="font-display text-2xl tracking-tight mb-6 cascade-in" style="animation-delay: 280ms;">Index</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($projects as $idx => $p): ?>
                    <a href="<?= base_url('project/' . $p['id']) ?>" 
                       class="bg-white block rounded-2xl border border-ink/15 bg-card/70 backdrop-blur p-5 hover:border-ink/60 transition-all group shadow-sm flex flex-col justify-between min-h-[180px] cascade-in"
                       style="animation-delay: <?= 300 + $idx * 30 ?>ms;">
                        <div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="font-mono text-[10px] tracking-widest text-muted-foreground mt-1"><?= $p['code'] ?></span>
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-ink/10 px-2.5 py-1 text-[10px] font-mono uppercase tracking-widest font-bold bg-card/80 backdrop-blur whitespace-nowrap shadow-sm">
                                    <span class="h-1.5 w-1.5 rounded-full animate-pulse" style="background: <?= $statusColors[$p['status']] ?? '#6B7280' ?>"></span>
                                    <?= $statusLabels[$p['status']] ?? strtoupper($p['status']) ?>
                                </span>
                            </div>
                            <h3 class="font-display text-xl mt-3 leading-tight font-black uppercase text-ink group-hover:text-primary transition-colors"><?= esc($p['name']) ?></h3>
                            <p class="text-xs text-muted-foreground mt-1"><?= esc($p['owner']) ?> · Squad: <span class="font-bold"><?= esc($p['squad']) ?></span></p>
                        </div>
                        <div class="mt-5 flex items-end justify-between border-t border-ink/10 pt-3">
                            <div>
                                <div class="eyebrow">Progress / Health</div>
                                <div class="font-display text-2xl font-bold mt-1">
                                    <?= $p['progress'] ?><span class="text-base text-muted-foreground">%</span>
                                    <span class="text-xs font-mono font-normal block text-muted-foreground truncate max-w-[200px]" title="<?= esc($p['health']) ?>"><?= esc($p['health']) ?></span>
                                </div>
                            </div>
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-secondary text-ink group-hover:bg-ink group-hover:text-paper transition-all">
                                <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer Component -->
    <footer class="w-full px-8 lg:px-14 py-10 border-t border-ink/15 mt-10">
        <div class="flex justify-between items-center text-xs font-mono uppercase tracking-widest text-muted-foreground">
            <span>Atlas / Project Studio</span>
            <span>© 2026</span>
        </div>
    </footer>
</div>

<?= $this->endSection() ?>
