<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<?php
// PHP functions to help with timeline layout
// PHP functions to help with timeline layout
function getMonthIndex($dateStr) {
    $time = strtotime($dateStr);
    $year = (int)date('Y', $time);
    $month = (int)date('n', $time) - 1; // 0-based
    $currentYear = (int)date('Y');
    if ($year < $currentYear) return 0;
    if ($year > $currentYear) return 11;
    return $month;
}

$currentYear = (int)date('Y');
$startMs = strtotime("{$currentYear}-01-01");
$endMs = strtotime("{$currentYear}-12-31");
$totalMs = $endMs - $startMs;
$nowMs = time();
$todayPct = null;
if ($nowMs >= $startMs && $nowMs <= $endMs) {
    $todayPct = (($nowMs - $startMs) / $totalMs) * 100;
}

$months = ["JAN","FEB","MAR","APR","MAY","JUN","JUL","AUG","SEP","OCT","NOV","DEC"];
$timelineMonths = [];
foreach ($months as $m) {
    $timelineMonths[] = ['label' => $m, 'year' => $currentYear, 'key' => "{$currentYear}-{$m}"];
}

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

// Default fallbacks in case table is empty or status field has no keys
$defaultSlugs = ['on-track', 'at-risk', 'blocked', 'delayed', 'backlog'];
$defaultLabels = ['ON TRACK', 'AT RISK', 'BLOCKED', 'DELAYED', 'BACKLOG'];
foreach ($defaultSlugs as $i => $s) {
    if (!isset($statusLabels[$s])) $statusLabels[$s] = $defaultLabels[$i];
    if (!isset($statusBgs[$s])) $statusBgs[$s] = 'bg-status-' . $s;
}
?>

<div class="min-h-screen">
    <!-- Header -->
    <header class="brutal-border-thick border-x-0 border-t-0 bg-background">
        <div class="max-w-[1600px] mx-auto px-6 py-5 flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-primary brutal-border-thick brutal-shadow-sm flex items-center justify-center mono font-black text-primary-foreground">
                    ▣
                </div>
                <div>
                    <div class="mono text-[10px] uppercase tracking-[0.3em] text-muted-foreground">
                        Project Management Office
                    </div>
                    <h1 class="text-2xl md:text-3xl font-black uppercase tracking-tight">
                        PORTFOLIO
                    </h1>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="brutal-border px-3 py-2 bg-card">
                    <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Projects</div>
                    <div class="mono text-lg font-black"><?= str_pad($total, 2, '0', STR_PAD_LEFT) ?></div>
                </div>
                <div class="brutal-border px-3 py-2 bg-card">
                    <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Window</div>
                    <div class="mono text-lg font-black">12 MO</div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-[1600px] mx-auto px-6 py-8 space-y-10">
        
        <!-- Status Overview -->
        <section>
            <div class="flex items-end justify-between gap-4 mb-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 bg-primary brutal-border"></div>
                    <h2 class="text-xl md:text-2xl font-black uppercase tracking-tight">Status Overview</h2>
                </div>
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Click a tile to filter the portfolio.</span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-6 gap-2">
                <!-- ALL -->
                <a href="?filter=all" class="brutal-border-thick p-2 text-left brutal-hover bg-foreground text-background <?= $filter === 'all' ? 'translate-x-[-2px] translate-y-[-2px] shadow-[4px_4px_0_0_var(--color-primary)]' : '' ?>">
                    <div class="mono text-[9px] font-black uppercase tracking-widest opacity-80">ALL</div>
                    <div class="mono text-2xl font-black mt-1 tabular-nums"><?= str_pad($total, 2, '0', STR_PAD_LEFT) ?></div>
                </a>

                <?php foreach (array_keys($statusLabels) as $s): ?>
                    <a href="?filter=<?= $s ?>" class="brutal-border-thick p-2 text-left brutal-hover <?= $statusBgs[$s] ?? 'bg-status-' . $s ?> text-background <?= $filter === $s ? 'translate-x-[-2px] translate-y-[-2px] shadow-[4px_4px_0_0_var(--color-primary)]' : '' ?>">
                        <div class="mono text-[9px] font-black uppercase tracking-widest opacity-80"><?= $statusLabels[$s] ?? strtoupper($s) ?></div>
                        <div class="mono text-2xl font-black mt-1 tabular-nums"><?= str_pad($counts[$s] ?? 0, 2, '0', STR_PAD_LEFT) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Action Item Reminders -->
        <?php if (!empty($overdueReminders) || !empty($dueTodayReminders) || !empty($dueSoonReminders)): ?>
        <section class="animate-fade-in">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-4 h-4 bg-primary brutal-border"></div>
                <h2 class="text-xl md:text-2xl font-black uppercase tracking-tight font-bold">Action Item Reminders</h2>
                <div class="flex-1 h-[2px] bg-border"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Overdue Reminders -->
                <div class="brutal-border-thick bg-card p-4 flex flex-col">
                    <div class="flex items-center justify-between border-b border-foreground pb-2 mb-3">
                        <span class="mono text-xs font-black text-destructive uppercase tracking-widest flex items-center gap-1">
                            <i data-lucide="alert-octagon" class="w-4 h-4 text-destructive"></i> Overdue (<?= count($overdueReminders) ?>)
                        </span>
                    </div>
                    <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                        <?php if (empty($overdueReminders)): ?>
                            <div class="mono text-[10px] text-muted-foreground uppercase py-2">No overdue action items.</div>
                        <?php else: ?>
                            <?php foreach ($overdueReminders as $r): ?>
                                <div class="border border-destructive/40 bg-destructive/5 p-2 mono text-[10px] uppercase">
                                    <div class="flex justify-between font-black text-destructive">
                                        <span class="truncate max-w-[150px]"><?= esc($r['title']) ?></span>
                                        <span><?= sys_date($r['due']) ?></span>
                                    </div>
                                    <div class="text-muted-foreground mt-1 flex justify-between">
                                        <span>Owner: <?= esc($r['owner']) ?></span>
                                        <a href="<?= base_url('project/' . $r['project_id']) ?>" class="underline text-foreground hover:text-primary"><?= esc($r['project_code']) ?></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Due Today Reminders -->
                <div class="brutal-border-thick bg-card p-4 flex flex-col">
                    <div class="flex items-center justify-between border-b border-foreground pb-2 mb-3">
                        <span class="mono text-xs font-black text-status-atrisk uppercase tracking-widest flex items-center gap-1">
                            <i data-lucide="clock" class="w-4 h-4 text-status-atrisk"></i> Due Today (<?= count($dueTodayReminders) ?>)
                        </span>
                    </div>
                    <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                        <?php if (empty($dueTodayReminders)): ?>
                            <div class="mono text-[10px] text-muted-foreground uppercase py-2">No items due today.</div>
                        <?php else: ?>
                            <?php foreach ($dueTodayReminders as $r): ?>
                                <div class="border border-foreground/30 bg-secondary/20 p-2 mono text-[10px] uppercase">
                                    <div class="flex justify-between font-black text-foreground">
                                        <span class="truncate max-w-[150px]"><?= esc($r['title']) ?></span>
                                        <span>TODAY</span>
                                    </div>
                                    <div class="text-muted-foreground mt-1 flex justify-between">
                                        <span>Owner: <?= esc($r['owner']) ?></span>
                                        <a href="<?= base_url('project/' . $r['project_id']) ?>" class="underline text-foreground hover:text-primary"><?= esc($r['project_code']) ?></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Due Soon Reminders -->
                <div class="brutal-border-thick bg-card p-4 flex flex-col">
                    <div class="flex items-center justify-between border-b border-foreground pb-2 mb-3">
                        <span class="mono text-xs font-black text-primary uppercase tracking-widest flex items-center gap-1">
                            <i data-lucide="calendar" class="w-4 h-4 text-primary"></i> Due Soon (<?= count($dueSoonReminders) ?>)
                        </span>
                    </div>
                    <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                        <?php if (empty($dueSoonReminders)): ?>
                            <div class="mono text-[10px] text-muted-foreground uppercase py-2">No items due soon.</div>
                        <?php else: ?>
                            <?php foreach ($dueSoonReminders as $r): ?>
                                <div class="border border-foreground/30 bg-background p-2 mono text-[10px] uppercase">
                                    <div class="flex justify-between font-black text-foreground">
                                        <span class="truncate max-w-[150px]"><?= esc($r['title']) ?></span>
                                        <span><?= sys_date($r['due']) ?></span>
                                    </div>
                                    <div class="text-muted-foreground mt-1 flex justify-between">
                                        <span>Owner: <?= esc($r['owner']) ?></span>
                                        <a href="<?= base_url('project/' . $r['project_id']) ?>" class="underline text-foreground hover:text-primary"><?= esc($r['project_code']) ?></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Graphical Timeline -->
        <section>
            <div class="flex items-end justify-between gap-4 mb-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 bg-primary brutal-border"></div>
                    <h2 class="text-xl md:text-2xl font-black uppercase tracking-tight">Graphical Timeline</h2>
                </div>
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Phase bars spanning the months they are scheduled for. Click any row to open detail.</span>
            </div>

            <!-- SVG Timeline Grid -->
            <div class="brutal-border-thick bg-card overflow-x-auto">
                <div class="min-w-[1400px]">
                    
                    <!-- Year Headers -->
                    <div class="grid grid-cols-[260px_repeat(12,minmax(48px,1fr))] border-b border-foreground relative">
                        <div class="brutal-border-thick border-0 border-r p-3 bg-primary text-primary-foreground mono text-xs uppercase tracking-widest font-bold">
                            Project / Timeline
                        </div>
                        <div class="col-span-12 p-2 mono text-xs uppercase tracking-widest text-center bg-secondary">// <?= $currentYear ?></div>
                        
                        <?php if ($todayPct !== null): ?>
                            <div class="absolute top-1/2 -translate-y-1/2 pointer-events-none z-20" style="left: calc(260px + (100% - 260px) * <?= $todayPct / 100 ?>); transform: translate(-50%, -50%)">
                                <span class="mono text-[9px] font-black uppercase tracking-widest bg-primary text-primary-foreground px-1.5 py-0.5 border border-foreground whitespace-nowrap">
                                    ▼ TODAY
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Month Headers -->
                    <div class="grid grid-cols-[260px_repeat(12,minmax(48px,1fr))] border-b border-foreground">
                        <div class="border-r border-foreground p-2 mono text-[10px] uppercase tracking-widest text-muted-foreground">Bar = phase span</div>
                        <?php foreach ($timelineMonths as $index => $m): ?>
                            <div class="p-2 mono text-[10px] uppercase tracking-widest text-center text-muted-foreground border-r border-foreground/40 last:border-r-0">
                                <?= $m['label'] ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Timeline Content Row -->
                    <div class="relative">
                        <!-- Today Line -->
                        <?php if ($todayPct !== null): ?>
                            <div class="absolute top-0 bottom-0 w-[2px] bg-primary z-10 pointer-events-none" style="left: calc(260px + (100% - 260px) * <?= $todayPct / 100 ?>); transform: translateX(-1px)"></div>
                        <?php endif; ?>

                        <?php foreach ($projects as $p): ?>
                            <?php
                            $startIdx = getMonthIndex($p['startDate']);
                            $endIdx = getMonthIndex($p['endDate']);
                            $span = max(1, $endIdx - $startIdx + 1);
                            ?>
                            <a href="<?= base_url('project/' . $p['id']) ?>" class="grid grid-cols-[260px_repeat(12,minmax(48px,1fr))] border-b border-foreground w-full text-left hover:bg-secondary/40 transition-colors">
                                
                                <!-- Project Details (Left Column) -->
                                <div class="border-r border-foreground p-3 flex flex-col gap-1">
                                    <?php
                                    $hasOpenRisks = false;
                                    foreach ($p['risks'] as $risk) {
                                        if (empty($risk['status']) || $risk['status'] === 'active') {
                                            $hasOpenRisks = true;
                                            break;
                                        }
                                    }

                                    $hasActiveEscalations = false;
                                    foreach ($p['escalations'] as $esc) {
                                        if (empty($esc['status']) || $esc['status'] === 'active') {
                                            $hasActiveEscalations = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="mono text-[10px] text-muted-foreground uppercase tracking-widest"><?= $p['code'] ?></span>
                                        <span class="mono text-[9px] font-black uppercase tracking-widest px-1.5 py-0.5 border border-foreground text-background <?= $statusBgs[$p['status']] ?? 'bg-status-' . $p['status'] ?> whitespace-nowrap">
                                            <?= $statusLabels[$p['status']] ?? strtoupper($p['status']) ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-0.5 min-w-0">
                                        <span class="font-bold text-sm uppercase tracking-tight truncate flex-1" title="<?= esc($p['name']) ?>"><?= esc($p['name']) ?></span>
                                        <?php if ($hasActiveEscalations): ?>
                                            <i data-lucide="shield-alert" class="w-4 h-4 text-destructive animate-pulse shrink-0" title="Active Escalation"></i>
                                        <?php endif; ?>
                                        <?php if ($hasOpenRisks): ?>
                                            <i data-lucide="alert-triangle" class="w-4 h-4 text-status-atrisk shrink-0" title="Open Risks"></i>
                                        <?php endif; ?>
                                    </div>
                                    <span class="mono text-[10px] text-muted-foreground"><?= esc($p['owner']) ?> · <?= esc($p['squad']) ?></span>
                                </div>

                                <!-- Phase Bar Track (Right 12 Columns) -->
                                <div class="[grid-column:span_12] relative grid h-[72px]" style="grid-template-columns: repeat(12, minmax(0, 1fr))">
                                    <?php for ($i = 0; $i < 12; $i++): ?>
                                        <div class="border-r border-foreground/30 last:border-r-0"></div>
                                    <?php endfor; ?>

                                    <!-- Left highlight bar -->
                                    <div class="absolute left-0 top-0 bottom-0 w-1 <?= $statusBgs[$p['status']] ?? 'bg-status-' . $p['status'] ?>"></div>

                                    <!-- Phase spans -->
                                    <?php foreach ($p['phases'] as $phase): ?>
                                        <?php
                                        $s = getMonthIndex($phase['start']);
                                        $e = getMonthIndex($phase['end']);
                                        $left = ($s / 12) * 100;
                                        $width = (($e - $s + 1) / 12) * 100;
                                        ?>
                                        <div class="absolute top-1/2 -translate-y-1/2 h-7 <?= $statusBgs[$phase['status']] ?? 'bg-status-' . $phase['status'] ?> border border-foreground flex items-center px-2 z-10" style="left: <?= $left ?>%; width: <?= $width ?>%" title="<?= esc($phase['name']) ?> • <?= sys_date($phase['start']) ?> → <?= sys_date($phase['end']) ?> • <?= $statusLabels[$phase['status']] ?? strtoupper($phase['status']) ?>">
                                            <span class="mono text-[10px] font-bold text-background uppercase tracking-tight truncate">
                                                <?= esc($phase['name']) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>

                                    <!-- Underline representing total project span -->
                                    <div class="absolute bottom-1 h-[3px] bg-foreground/60" style="left: <?= ($startIdx / 12) * 100 ?>%; width: <?= ($span / 12) * 100 ?>%"></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap gap-3 mt-3 mono text-[10px] uppercase tracking-widest">
                <?php foreach ($statusLabels as $status => $label): ?>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 border border-foreground <?= $statusBgs[$status] ?? 'bg-status-' . $status ?>"></div>
                        <span class="text-muted-foreground"><?= $label ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Project Cards List -->
        <section>
            <div class="flex items-end justify-between gap-4 mb-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 bg-primary brutal-border"></div>
                    <h2 class="text-xl md:text-2xl font-black uppercase tracking-tight font-bold">
                        <?= $filter === 'all' ? 'All Projects' : 'Filtered: ' . ($statusLabels[$filter] ?? strtoupper($filter)) ?>
                    </h2>
                </div>
                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">
                    <?= count($projects) ?> of <?= $total ?> projects
                </span>
            </div>

            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                <?php foreach ($projects as $p): ?>
                    <?php
                    $openActions = 0;
                    foreach ($p['actionItems'] as $a) {
                        if ($a['done'] == 0) $openActions++;
                    }
                    ?>
                    <a href="<?= base_url('project/' . $p['id']) ?>" class="text-left brutal-border-thick bg-card p-5 brutal-hover block">
                        <?php
                        $hasOpenRisks = false;
                        foreach ($p['risks'] as $risk) {
                            if (empty($risk['status']) || $risk['status'] === 'active') {
                                $hasOpenRisks = true;
                                break;
                            }
                        }

                        $hasActiveEscalations = false;
                        foreach ($p['escalations'] as $esc) {
                            if (empty($esc['status']) || $esc['status'] === 'active') {
                                $hasActiveEscalations = true;
                                break;
                            }
                        }
                        ?>
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div>
                                <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground">
                                    <?= $p['code'] ?>
                                </div>
                                <div class="flex items-center gap-1.5 mt-1 min-w-0">
                                    <h3 class="font-black uppercase tracking-tight text-lg leading-tight truncate flex-1" title="<?= esc($p['name']) ?>">
                                        <?= esc($p['name']) ?>
                                    </h3>
                                    <?php if ($hasActiveEscalations): ?>
                                        <i data-lucide="shield-alert" class="w-4.5 h-4.5 text-destructive animate-pulse shrink-0" title="Active Escalation"></i>
                                    <?php endif; ?>
                                    <?php if ($hasOpenRisks): ?>
                                        <i data-lucide="alert-triangle" class="w-4.5 h-4.5 text-status-atrisk shrink-0" title="Open Risks"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="mono text-[10px] font-bold uppercase tracking-widest px-2 py-1 border border-foreground text-background <?= $statusBgs[$p['status']] ?? 'bg-status-' . $p['status'] ?> whitespace-nowrap">
                                <?= $statusLabels[$p['status']] ?? strtoupper($p['status']) ?>
                            </span>
                        </div>
                        <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground mb-1">
                            Owner — <?= esc($p['owner']) ?>
                        </div>
                        <div class="mono text-[10px] uppercase tracking-widest mb-3">
                            Squad — <span class="font-black"><?= esc($p['squad']) ?></span>
                        </div>
                        
                        <div class="flex items-center gap-2 mb-3">
                            <div class="flex-1 h-2 brutal-border bg-background">
                                <div class="h-full bg-primary" style="width: <?= $p['progress'] ?>%"></div>
                            </div>
                            <span class="mono text-[10px] font-bold"><?= $p['progress'] ?>%</span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mono text-[10px] uppercase tracking-widest text-muted-foreground">
                            <div>
                                <div>Start</div>
                                <div class="text-foreground mt-1"><?= sys_date($p['startDate']) ?></div>
                            </div>
                            <div>
                                <div>End</div>
                                <div class="text-foreground mt-1"><?= sys_date($p['endDate']) ?></div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 mt-4">
                            <span class="mono text-[10px] uppercase tracking-widest px-2 py-1 border border-foreground bg-background"><?= count($p['phases']) ?> phases</span>
                            <span class="mono text-[10px] uppercase tracking-widest px-2 py-1 border border-foreground bg-background"><?= count($p['dependencies']) ?> deps</span>
                            <span class="mono text-[10px] uppercase tracking-widest px-2 py-1 border border-foreground bg-background"><?= $openActions ?> open actions</span>
                            <span class="mono text-[10px] uppercase tracking-widest px-2 py-1 border border-foreground bg-background"><?= count($p['risks']) ?> risk/iss</span>
                            <?php if (count($p['escalations']) > 0): ?>
                                <span class="mono text-[10px] uppercase tracking-widest px-2 py-1 border border-foreground bg-destructive text-destructive-foreground"><?= count($p['escalations']) ?> escalation</span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="border-t border-border mt-16 py-6">
        <div class="max-w-[1600px] mx-auto px-6 flex justify-between mono text-[10px] uppercase tracking-widest text-muted-foreground">
            <span>PMO // Build 026.27</span>
            <span>Last sync: live</span>
        </div>
    </footer>
</div>
<?= $this->endSection() ?>
