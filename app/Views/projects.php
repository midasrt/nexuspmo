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

// Ensure default fallback items exist in case table is empty or missing keys
$defaultSlugs = ['on-track', 'at-risk', 'blocked', 'delayed', 'backlog'];
$defaultLabels = ['ON TRACK', 'AT RISK', 'BLOCKED', 'DELAYED', 'BACKLOG'];
foreach ($defaultSlugs as $i => $s) {
    if (!isset($statusLabels[$s])) $statusLabels[$s] = $defaultLabels[$i];
    if (!isset($statusBgs[$s])) $statusBgs[$s] = 'bg-status-' . $s;
}
?>

<div class="min-h-screen p-6 md:p-10">
    <div class="max-w-7xl mx-auto">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="mono text-[10px] uppercase tracking-[0.3em] text-muted-foreground">
                    PMO // Module
                </div>
                <h1 class="mono text-4xl font-black uppercase tracking-tight mt-2">Projects</h1>
                <p class="mt-2 text-sm text-muted-foreground max-w-prose">
                    Manage the full project register — create, view, edit, and remove projects.
                </p>
            </div>
            <button onclick="openCreateModal()" class="inline-flex items-center gap-2 brutal-border bg-foreground text-background px-4 py-2.5 mono text-xs uppercase tracking-widest font-black brutal-hover">
                <i data-lucide="plus" class="h-4 w-4" stroke-width="3"></i>
                New Project
            </button>
        </div>

        <!-- Filter Chips -->
        <div class="flex flex-wrap gap-2 mt-6">
            <a href="?filter=all" class="mono text-[10px] uppercase tracking-widest px-3 py-2 border border-foreground brutal-hover <?= $filter === 'all' ? 'bg-foreground text-background' : 'bg-card' ?>">
                ALL · <?= count($projects) ?>
            </a>
            <?php foreach ($statusLabels as $s => $label): ?>
                <?php
                $count = 0;
                foreach ($projects as $p) {
                    if ($p['status'] === $s) $count++;
                }
                ?>
                <a href="?filter=<?= $s ?>" class="mono text-[10px] uppercase tracking-widest px-3 py-2 border border-foreground brutal-hover <?= $filter === $s ? "{$statusBgs[$s]} text-background" : 'bg-card' ?>">
                    <?= $label ?> · <?= $count ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Projects Table -->
        <div class="brutal-border-thick bg-card mt-6 overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-secondary border-b border-foreground">
                    <tr class="mono text-[10px] uppercase tracking-widest">
                        <th class="px-3 py-3">Code</th>
                        <th class="px-3 py-3">Project</th>
                        <th class="px-3 py-3">Owner</th>
                        <th class="px-3 py-3">Squad</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Progress</th>
                        <th class="px-3 py-3">Window</th>
                        <th class="px-3 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filtered as $p): ?>
                        <?php
                        $openActions = 0;
                        foreach ($p['actionItems'] as $a) {
                            if ($a['done'] == 0) $openActions++;
                        }
                        
                        // Prep JSON representation to pass into Javascript modals
                        $jsData = htmlspecialchars(json_encode([
                            'id' => $p['id'],
                            'code' => $p['code'],
                            'name' => $p['name'],
                            'owner' => $p['owner'],
                            'squad' => $p['squad'],
                            'status' => $p['status'],
                            'startDate' => $p['startDate'],
                            'endDate' => $p['endDate'],
                            'progress' => (int)$p['progress'],
                            'description' => $p['description'],
                            'phases' => $p['phases'],
                            'phasesCount' => count($p['phases']),
                            'depsCount' => count($p['dependencies']),
                            'actionsCount' => $openActions,
                            'risksCount' => count($p['risks']),
                        ]), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr class="border-b border-border hover:bg-secondary/40">
                            <td class="px-3 py-3 mono text-[11px]"><?= esc($p['code']) ?></td>
                            <td class="px-3 py-3 font-bold uppercase tracking-tight text-sm"><?= esc($p['name']) ?></td>
                            <td class="px-3 py-3 mono text-[11px]"><?= esc($p['owner']) ?></td>
                            <td class="px-3 py-3 mono text-[11px]"><?= esc($p['squad']) ?></td>
                            <td class="px-3 py-3">
                                <span class="mono text-[10px] font-black uppercase tracking-widest px-2 py-1 border border-foreground text-background <?= $statusBgs[$p['status']] ?? 'bg-status-' . $p['status'] ?> whitespace-nowrap">
                                    <?= $statusLabels[$p['status']] ?? strtoupper($p['status']) ?>
                                </span>
                            </td>
                            <td class="px-3 py-3 w-40">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 brutal-border bg-background">
                                        <div class="h-full bg-primary" style="width: <?= $p['progress'] ?>%"></div>
                                    </div>
                                    <span class="mono text-[10px] font-bold w-8 text-right"><?= $p['progress'] ?>%</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 mono text-[10px] text-muted-foreground whitespace-nowrap">
                                <?= sys_date($p['startDate']) ?> → <?= sys_date($p['endDate']) ?>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- View Action Button -->
                                    <a href="<?= base_url('project/' . $p['id']) ?>" title="View" class="brutal-border p-1.5 brutal-hover bg-card inline-flex items-center">
                                        <i data-lucide="eye" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                                    </a>
                                    <!-- Edit Action Button -->
                                    <button title="Edit" onclick="openEditModal(this)" data-project="<?= $jsData ?>" class="brutal-border p-1.5 brutal-hover bg-card">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                                    </button>
                                    <!-- Delete Action Button -->
                                    <button title="Delete" onclick="openDeleteModal(this)" data-project="<?= $jsData ?>" class="brutal-border p-1.5 brutal-hover bg-card hover:bg-destructive hover:text-destructive-foreground">
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($filtered)): ?>
                        <tr>
                            <td colSpan="8" class="px-3 py-8 text-center mono text-[11px] text-muted-foreground uppercase tracking-widest">
                                No projects.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==================== MODALS ==================== -->

<!-- 1. VIEW DIALOG MODAL -->
<div id="view-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-2xl w-full p-0 brutal-shadow">
        <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
            <h3 id="view-title" class="mono font-black uppercase tracking-tight"></h3>
            <button onclick="closeModal('view-modal')" class="text-background font-black hover:opacity-75">✕</button>
        </div>
        <div class="p-6 space-y-4 mono text-xs">
            <div class="flex justify-between border-b border-border pb-1">
                <span class="text-muted-foreground uppercase">Owner</span>
                <span id="view-owner" class="text-foreground"></span>
            </div>
            <div class="flex justify-between border-b border-border pb-1">
                <span class="text-muted-foreground uppercase">Squad</span>
                <span id="view-squad" class="text-foreground"></span>
            </div>
            <div class="flex justify-between border-b border-border pb-1">
                <span class="text-muted-foreground uppercase">Status</span>
                <span id="view-status" class="text-foreground"></span>
            </div>
            <div class="flex justify-between border-b border-border pb-1">
                <span class="text-muted-foreground uppercase">Window</span>
                <span id="view-window" class="text-foreground"></span>
            </div>
            <div class="flex justify-between border-b border-border pb-1">
                <span class="text-muted-foreground uppercase">Progress</span>
                <span id="view-progress" class="text-foreground font-black"></span>
            </div>
            <div>
                <div class="text-[10px] uppercase tracking-widest text-muted-foreground mb-1">Description</div>
                <p id="view-description" class="text-foreground bg-background p-2 brutal-border"></p>
            </div>
            <div class="flex flex-wrap gap-3 text-[10px] uppercase tracking-widest text-muted-foreground pt-2 border-t border-border">
                <span id="view-phases-count"></span>
                <span id="view-deps-count"></span>
                <span id="view-actions-count"></span>
                <span id="view-risks-count"></span>
            </div>
        </div>
        <div class="border-t border-foreground p-4 flex justify-end">
            <a id="view-full-page-btn" href="#" class="inline-flex items-center gap-2 brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                <i data-lucide="external-link" class="h-3.5 w-3.5" stroke-width="3"></i>
                Open Full Page
            </a>
        </div>
    </div>
</div>

<!-- 2. CREATE DIALOG MODAL -->
<div id="create-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-2xl w-full p-0 brutal-shadow max-h-[90vh] overflow-y-auto">
        <form action="<?= base_url('projects/create') ?>" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">New Project</h3>
                <button type="button" onclick="closeModal('create-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Code</span>
                    <input name="code" value="PRJ-<?= str_pad(count($projects) + 1, 3, '0', STR_PAD_LEFT) ?>" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Status</span>
                    <select name="status" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary">
                        <?php foreach ($statusLabels as $status => $label): ?>
                            <option value="<?= $status ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Name</span>
                    <input name="name" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Owner</span>
                    <select name="owner" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary">
                        <?php foreach ($resources as $res): ?>
                            <option value="<?= esc($res['name']) ?>"><?= esc($res['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Squad</span>
                    <select name="squad" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary">
                        <?php foreach ($squads as $sq): ?>
                            <option value="<?= esc($sq['name']) ?>"><?= esc($sq['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Start Date</span>
                    <input type="date" name="startDate" value="<?= date('Y-m-d') ?>" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">End Date</span>
                    <input type="date" name="endDate" value="<?= date('Y-m-d', strtotime('+6 months')) ?>" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>

                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Description</span>
                    <textarea name="description" rows="3" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
                
                <!-- Create Phase Editor Section -->
                <div class="col-span-2 border-t border-foreground pt-4 mt-2 text-left">
                    <div class="flex items-center justify-between mb-2">
                        <span class="mono text-xs font-black uppercase tracking-widest">Project Phases</span>
                        <button type="button" onclick="addPhaseRow('create-phases-container')" class="brutal-border bg-background px-2 py-1 mono text-[10px] uppercase tracking-widest font-black brutal-hover">
                            + Add Phase
                        </button>
                    </div>
                    <div id="create-phases-container" class="space-y-2">
                        <!-- Dynamic rows loaded here -->
                    </div>
                </div>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('create-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 3. EDIT DIALOG MODAL -->
<div id="edit-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-2xl w-full p-0 brutal-shadow max-h-[90vh] overflow-y-auto">
        <form id="edit-form" action="" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">Edit Project</h3>
                <button type="button" onclick="closeModal('edit-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Code</span>
                    <input id="edit-code" name="code" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Status</span>
                    <select id="edit-status" name="status" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary">
                        <?php foreach ($statusLabels as $status => $label): ?>
                            <option value="<?= $status ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Name</span>
                    <input id="edit-name" name="name" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Owner</span>
                    <select id="edit-owner" name="owner" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary">
                        <?php foreach ($resources as $res): ?>
                            <option value="<?= esc($res['name']) ?>"><?= esc($res['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Squad</span>
                    <select id="edit-squad" name="squad" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary">
                        <?php foreach ($squads as $sq): ?>
                            <option value="<?= esc($sq['name']) ?>"><?= esc($sq['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Start Date</span>
                    <input type="date" id="edit-start-date" name="startDate" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">End Date</span>
                    <input type="date" id="edit-end-date" name="endDate" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>

                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Description</span>
                    <textarea id="edit-description" name="description" rows="3" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
                
                <!-- Edit Phase Editor Section -->
                <div class="col-span-2 border-t border-foreground pt-4 mt-2 text-left">
                    <div class="flex items-center justify-between mb-2">
                        <span class="mono text-xs font-black uppercase tracking-widest">Project Phases</span>
                        <button type="button" onclick="addPhaseRow('edit-phases-container')" class="brutal-border bg-background px-2 py-1 mono text-[10px] uppercase tracking-widest font-black brutal-hover">
                            + Add Phase
                        </button>
                    </div>
                    <div id="edit-phases-container" class="space-y-2">
                        <!-- Dynamic rows loaded here -->
                    </div>
                </div>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('edit-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 4. DELETE DIALOG CONFIRM MODAL -->
<div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow">
        <form id="delete-form" action="" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">Delete project?</h3>
                <button type="button" onclick="closeModal('delete-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6">
                <p class="mono text-xs text-foreground">
                    Are you sure you want to delete <span id="delete-project-name" class="font-black"></span>?<br />
                    This action is permanent and will cascade-delete all phases, action items, risks, and documents.
                </p>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('delete-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-destructive text-destructive-foreground px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const statusLabelMap = <?= json_encode($statusLabels) ?>;
    const statusDefinitions = <?= json_encode($statusDefinitions) ?>;
    let phaseIndex = 0;

    // Build status dropdown options dynamically
    let statusOptionsHtml = '';
    statusDefinitions.forEach(def => {
        statusOptionsHtml += `<option value="${def.status}">${def.label}</option>`;
    });

    function addPhaseRow(containerId, data = null) {
        const container = document.getElementById(containerId);
        const index = phaseIndex++;
        const row = document.createElement('div');
        row.className = 'brutal-border p-3 bg-background grid grid-cols-2 gap-3 relative';
        
        row.innerHTML = `
            <input type="hidden" name="phases[${index}][id]" value="${data && data.id ? data.id : ''}" />
            <div class="col-span-2 flex items-center justify-between">
                <span class="mono text-[9px] uppercase tracking-widest text-muted-foreground">Phase Item</span>
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-destructive font-black text-[10px] uppercase hover:opacity-75">✕ Remove</button>
            </div>
            <div class="col-span-2 flex flex-col gap-1">
                <span class="mono text-[9px] uppercase tracking-widest text-muted-foreground">Name</span>
                <input name="phases[${index}][name]" required value="${data ? escapeHtml(data.name) : ''}" class="w-full border border-foreground bg-background px-2 py-1 mono text-xs focus:outline-none" />
            </div>
            <div class="col-span-2 flex flex-col gap-1">
                <span class="mono text-[9px] uppercase tracking-widest text-muted-foreground">Description</span>
                <textarea name="phases[${index}][description]" rows="1" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none">${data && data.description ? escapeHtml(data.description) : ''}</textarea>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[9px] uppercase tracking-widest text-muted-foreground">Status</span>
                <select name="phases[${index}][status]" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none">
                    ${statusOptionsHtml}
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[9px] uppercase tracking-widest text-muted-foreground">Start Date</span>
                <input type="date" name="phases[${index}][start]" value="${data ? data.start : ''}" class="w-full border border-foreground bg-background px-2 py-1 mono text-xs focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1 col-span-2">
                <span class="mono text-[9px] uppercase tracking-widest text-muted-foreground">End Date</span>
                <input type="date" name="phases[${index}][end]" value="${data ? data.end : ''}" class="w-full border border-foreground bg-background px-2 py-1 mono text-xs focus:outline-none" />
            </div>
        `;
        
        container.appendChild(row);

        // Select correct status if data exists
        if (data) {
            row.querySelector(`select[name="phases[${index}][status]"]`).value = data.status;
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function openCreateModal() {
        document.getElementById('create-phases-container').innerHTML = '';
        // Add two default phase rows
        addPhaseRow('create-phases-container', {name: 'Discovery', description: 'Discovery Phase', start: '', end: '', status: 'on-track'});
        addPhaseRow('create-phases-container', {name: 'Execution', description: 'Execution Phase', start: '', end: '', status: 'backlog'});
        document.getElementById('create-modal').classList.remove('hidden');
    }

    function openEditModal(button) {
        const p = JSON.parse(button.getAttribute('data-project'));
        document.getElementById('edit-form').action = '<?= base_url('projects/update') ?>/' + p.id;
        document.getElementById('edit-code').value = p.code;
        document.getElementById('edit-status').value = p.status;
        document.getElementById('edit-name').value = p.name;
        document.getElementById('edit-owner').value = p.owner;
        document.getElementById('edit-squad').value = p.squad;
        document.getElementById('edit-start-date').value = p.startDate;
        document.getElementById('edit-end-date').value = p.endDate;
        document.getElementById('edit-description').value = p.description || '';

        // Populate phases
        const editContainer = document.getElementById('edit-phases-container');
        editContainer.innerHTML = '';
        if (p.phases && p.phases.length > 0) {
            p.phases.forEach(ph => {
                addPhaseRow('edit-phases-container', ph);
            });
        }

        document.getElementById('edit-modal').classList.remove('hidden');
    }

    function openDeleteModal(button) {
        const p = JSON.parse(button.getAttribute('data-project'));
        document.getElementById('delete-form').action = '<?= base_url('projects/delete') ?>/' + p.id;
        document.getElementById('delete-project-name').innerText = p.code + ' — ' + p.name;
        document.getElementById('delete-modal').classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    // Close modals on clicking backdrop
    window.onclick = function(event) {
        const modals = ['view-modal', 'create-modal', 'edit-modal', 'delete-modal'];
        modals.forEach(function(id) {
            const modal = document.getElementById(id);
            if (event.target == modal) {
                modal.classList.add('hidden');
            }
        });
    }
</script>
<?= $this->endSection() ?>
