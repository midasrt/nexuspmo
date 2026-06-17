<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<?php
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

// Timeline calculations helper functions
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
?>

<div class="min-h-screen pb-24">
    <!-- Header component -->
    <header class="border-b border-ink/15 py-6 no-print cascade-in" style="animation-delay: 0ms;">
        <div class="w-full px-8 lg:px-14 flex items-center justify-between gap-4">
            <div>
                <span class="eyebrow">Portfolio Register</span>
                <h1 class="font-display text-2xl md:text-3xl font-black uppercase mt-1 tracking-tight">Project Register</h1>
            </div>
            <button onclick="openCreateModal()" class="rounded-full border border-ink/20 bg-ink text-paper hover:bg-ink/90 px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold flex items-center gap-1.5 shadow-sm">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                New Project
            </button>
        </div>
    </header>

    <section class="w-full px-8 lg:px-14">
        <!-- Filter chips in pill shape (2x larger) -->
        <div class="flex flex-wrap items-center gap-3 py-6 border-b border-ink/15 mb-6 cascade-in" style="animation-delay: 50ms;">
            <span class="eyebrow mr-2 text-sm">Status filter</span>
            <a href="?filter=all" class="group flex items-center gap-3 rounded-full border px-6 py-3 text-sm font-mono uppercase tracking-widest transition-all whitespace-nowrap <?= $filter === 'all' ? 'bg-ink text-paper border-ink' : 'bg-transparent text-ink border-ink/25 hover:border-ink/60' ?>">
                ALL · <?= count($projects) ?>
            </a>
            <?php foreach ($statusLabels as $s => $label): ?>
                <?php
                $count = 0;
                foreach ($projects as $p) {
                    if ($p['status'] === $s) $count++;
                }
                ?>
                <a href="?filter=<?= $s ?>" class="group flex items-center gap-3 rounded-full border px-6 py-3 text-sm font-mono uppercase tracking-widest transition-all whitespace-nowrap <?= $filter === $s ? 'bg-ink text-paper border-ink' : 'bg-transparent text-ink border-ink/25 hover:border-ink/60' ?>">
                    <span class="h-3 w-3 rounded-full shrink-0" style="background: <?= $statusColors[$s] ?? '#6B7280' ?>"></span>
                    <span class="truncate max-w-[150px]" title="<?= esc($label) ?>"><?= $label ?></span>
                    <span class="shrink-0">· <?= $count ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Clean Projects Register Timeline view inside glassmorphic card container -->
        <div class="rounded-2xl border border-ink/15 bg-white dark:bg-card/70 overflow-hidden shadow-sm cascade-in" style="animation-delay: 100ms;">
            <!-- Month scale header -->
            <div class="grid grid-cols-[300px_1fr] border-b border-ink/15 bg-white dark:bg-card/70">
                <div class="px-5 py-3 eyebrow border-r border-ink/10 flex items-center">Project Details & Actions</div>
                <div class="relative grid grid-cols-12">
                    <?php foreach ($months as $i => $m): ?>
                        <div class="px-3 py-3 text-[11px] font-mono uppercase tracking-widest border-r border-ink/10 last:border-r-0">
                            <span class="text-ink/80"><?= $m ?></span>
                            <span class="ml-1 text-ink/35"><?= substr($currentYear, -2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Gantt timelines list -->
            <ul>
                <?php if (empty($filtered)): ?>
                    <li class="p-8 text-center text-muted-foreground uppercase font-mono text-xs">No projects match the active filter.</li>
                <?php else: ?>
                    <?php foreach ($filtered as $rowIdx => $p): 
                        $span = getSpanPct($p['startDate'], $p['endDate']);
                        $statusColorVal = $statusColors[$p['status']] ?? 'var(--muted)';
                        $jsData = htmlspecialchars(json_encode([
                            'id' => $p['id'],
                            'code' => $p['code'],
                            'name' => $p['name'],
                            'owner' => $p['owner'],
                            'squad' => $p['squad'],
                            'status' => $p['status'],
                            'startDate' => $p['startDate'],
                            'endDate' => $p['endDate'],
                            'description' => $p['description'],
                            'phases' => $p['phases']
                        ]), ENT_QUOTES, 'UTF-8');
                    ?>
                        <li class="grid grid-cols-[300px_1fr] items-stretch border-b border-ink/10 last:border-b-0 group hover:bg-ink/[0.01]">
                            <div class="px-5 py-4 border-r border-ink/10 flex flex-col justify-between gap-3 transition-colors bg-white dark:bg-card/70">
                                <a href="<?= base_url('project/' . $p['id']) ?>" class="flex flex-col gap-1">
                                    <span class="font-mono text-[10px] tracking-widest text-muted-foreground"><?= $p['code'] ?></span>
                                    <span class="font-display text-[15px] leading-tight font-bold text-ink uppercase group-hover:text-primary transition-colors"><?= esc($p['name']) ?></span>
                                    <span class="text-xs text-muted-foreground truncate"><?= esc($p['owner']) ?> · <span class="font-semibold text-ink/80"><?= esc($p['squad']) ?></span></span>
                                </a>
                                <div class="flex items-center gap-1.5">
                                    <a href="<?= base_url('project/' . $p['id']) ?>" title="View Detail" class="rounded-lg border border-ink/10 bg-background hover:bg-secondary p-1.5 text-ink transition-colors">
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                    </a>
                                    <button title="Edit" onclick='openEditModal(this)' data-project="<?= $jsData ?>" class="rounded-lg border border-ink/10 bg-background hover:bg-secondary p-1.5 text-ink transition-colors">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                    </button>
                                    <button title="Delete" onclick='openDeleteModal(this)' data-project="<?= $jsData ?>" class="rounded-lg border border-ink/10 bg-background hover:bg-destructive hover:text-paper p-1.5 text-destructive transition-colors">
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="relative h-[90px] flex items-center bg-white dark:bg-card/70">
                                <!-- Month grid columns -->
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
                                
                                <div class="absolute h-7 rounded-full border border-ink/20 overflow-hidden shadow-sm flex items-center"
                                     style="left: <?= $span['left'] ?>%; width: <?= $span['width'] ?>%; background: var(--card);"
                                     title="<?= esc($p['name']) ?> (<?= $p['startDate'] ?> to <?= $p['endDate'] ?>)">
                                    <div class="h-full absolute left-0 top-0" style="width: <?= $p['progress'] ?>%; background: color-mix(in oklab, <?= $statusColorVal ?> 70%, transparent)"></div>
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
    </section>
</div>

<!-- ==================== DIALOG MODALS (SCROLLABLE CONTENT) ==================== -->

<!-- 1. CREATE DIALOG MODAL -->
<div id="create-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-lg w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Add New Project</h3>
            <button onclick="closeModal('create-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form action="<?= base_url('projects/create') ?>" method="POST" class="overflow-y-auto flex-1 p-6 space-y-4 text-left">
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Code / Tag</span>
                    <input name="code" required placeholder="e.g. PRJ-001" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Initial Status</span>
                    <select name="status" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <?php foreach ($statusLabels as $status => $label): ?>
                            <option value="<?= $status ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Project Name</span>
                <input name="name" required placeholder="Project Name" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Owner / Lead</span>
                    <select name="owner" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <?php foreach ($resources as $res): ?>
                            <option value="<?= esc($res['name']) ?>"><?= esc($res['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Squad</span>
                    <select name="squad" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <?php foreach ($squads as $sq): ?>
                            <option value="<?= esc($sq['name']) ?>"><?= esc($sq['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Start Date</span>
                    <input type="date" name="startDate" value="<?= date('Y-m-d') ?>" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">End Date (Target)</span>
                    <input type="date" name="endDate" value="<?= date('Y-m-d', strtotime('+6 months')) ?>" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Description</span>
                <textarea name="description" rows="3" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>

            <!-- Dynamic phases rows -->
            <div class="border-t border-ink/15 pt-4 mt-2">
                <div class="flex items-center justify-between mb-2">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Phases Setup</span>
                    <button type="button" onclick="addPhaseRow('create-phases-container')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-3 py-1 text-xs font-mono uppercase tracking-widest">+ Add Phase</button>
                </div>
                <div id="create-phases-container" class="space-y-2 max-h-48 overflow-y-auto no-scrollbar">
                    <!-- Loaded dynamically -->
                </div>
            </div>

            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Create Project</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. EDIT DIALOG MODAL -->
<div id="edit-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-lg w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Edit Project</h3>
            <button onclick="closeModal('edit-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="edit-form" action="" method="POST" class="overflow-y-auto flex-1 p-6 space-y-4 text-left">
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Code</span>
                    <input id="edit-code" name="code" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Status</span>
                    <select id="edit-status" name="status" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <?php foreach ($statusLabels as $status => $label): ?>
                            <option value="<?= $status ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Project Name</span>
                <input id="edit-name" name="name" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Owner</span>
                    <select id="edit-owner" name="owner" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <?php foreach ($resources as $res): ?>
                            <option value="<?= esc($res['name']) ?>"><?= esc($res['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Squad</span>
                    <select id="edit-squad" name="squad" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <?php foreach ($squads as $sq): ?>
                            <option value="<?= esc($sq['name']) ?>"><?= esc($sq['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Start Date</span>
                    <input type="date" id="edit-start-date" name="startDate" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">End Date</span>
                    <input type="date" id="edit-end-date" name="endDate" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Description</span>
                <textarea id="edit-description" name="description" rows="3" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>

            <!-- Dynamic phases setup -->
            <div class="border-t border-ink/15 pt-4 mt-2">
                <div class="flex items-center justify-between mb-2">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Project Phases</span>
                    <button type="button" onclick="addPhaseRow('edit-phases-container')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-3 py-1 text-xs font-mono uppercase tracking-widest">+ Add Phase</button>
                </div>
                <div id="edit-phases-container" class="space-y-2 max-h-48 overflow-y-auto no-scrollbar">
                    <!-- Dynamic rows loaded -->
                </div>
            </div>

            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. DELETE CONFIRM DIALOG MODAL -->
<div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <form id="delete-form" action="" method="POST" class="flex flex-col h-full">
            <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
                <h3 class="font-display text-sm font-bold uppercase tracking-wide">Delete Project?</h3>
                <button type="button" onclick="closeModal('delete-modal')" class="text-paper hover:opacity-75">✕</button>
            </div>
            <div class="p-6 text-left overflow-y-auto flex-1">
                <p class="mono text-xs text-ink/80 leading-normal">
                    Are you sure you want to delete <span id="delete-project-name" class="font-black text-ink"></span>?<br /><br />
                    This action is permanent and will cascade-delete all phases, action items, risks, and documents.
                </p>
                <div class="mt-4 flex flex-col gap-2 border-t border-dashed border-ink/15 pt-4">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Verification Required</span>
                    <p class="mono text-xs text-ink/80 leading-normal mb-1">
                        Please type the word <span id="delete-confirm-target" class="font-bold text-destructive underline select-all"></span> below to enable deletion.
                    </p>
                    <input type="text" id="delete-confirm-input" placeholder="Type word here..." autocomplete="off" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none font-mono" />
                </div>
            </div>
            <div class="border-t border-ink/15 p-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('delete-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" id="delete-submit-btn" disabled class="rounded-full bg-destructive text-destructive-foreground px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold disabled:opacity-50 disabled:cursor-not-allowed">Delete Project</button>
            </div>
        </form>
    </div>
</div>

<script>
    window.ProjectsConfig = {
        statusLabelMap: <?= json_encode($statusLabels) ?>,
        statusDefinitions: <?= json_encode($statusDefinitions) ?>,
        updateUrl: '<?= base_url('projects/update') ?>',
        deleteUrl: '<?= base_url('projects/delete') ?>'
    };
</script>
<script src="<?= base_url('js/projects.js') ?>?v=<?= time() ?>"></script>
<?= $this->endSection() ?>
