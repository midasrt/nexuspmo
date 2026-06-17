<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<?php
$roleBgs = [
    'FE' => 'bg-status-ontrack',
    'BE' => 'bg-status-atrisk',
    'QA' => 'bg-status-delayed',
    'BA' => 'bg-status-backlog',
];

function getUtilColor($u) {
    if ($u >= 85) return 'bg-status-blocked';
    if ($u >= 65) return 'bg-status-ontrack';
    if ($u >= 40) return 'bg-status-atrisk';
    return 'bg-status-backlog';
}

// SVG Team Mesh Nodes removed
?>

<div class="min-h-screen pb-24">
    <!-- Header Component -->
    <header class="border-b border-ink/15 py-6 no-print mb-6 cascade-in" style="animation-delay: 0ms;">
        <div class="w-full px-8 lg:px-14 flex items-center justify-between gap-4">
            <div>
                <span class="eyebrow">Capacity Module</span>
                <h1 class="font-display text-2xl md:text-3xl font-black uppercase mt-1 tracking-tight">Team / Resources</h1>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="openCreateModal()" class="rounded-full border border-ink/20 bg-ink text-paper hover:bg-ink/90 px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    New Resource
                </button>
            </div>
        </div>
    </header>

    <section class="w-full px-8 lg:px-14 flex flex-col gap-10">
        <!-- Lead stats strip -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 py-6 border-b border-ink/15 cascade-in" style="animation-delay: 50ms;">
            <div>
                <div class="eyebrow">Total Team</div>
                <div class="font-display text-3xl font-bold mt-1 text-ink"><?= $totalCount ?></div>
            </div>
            <div>
                <div class="eyebrow">Employees</div>
                <div class="font-display text-3xl font-bold mt-1 text-ink"><?= $empCount ?></div>
            </div>
            <div>
                <div class="eyebrow">Outsource</div>
                <div class="font-display text-3xl font-bold mt-1 text-ink"><?= $outCount ?></div>
            </div>
            <div>
                <div class="eyebrow">Avg Utilization</div>
                <div class="font-display text-3xl font-bold mt-1 text-ink"><?= $avgUtil ?>%</div>
            </div>
        </div>

        <!-- Filter Row -->
        <div class="flex flex-wrap items-center justify-between gap-4 py-2 border-b border-ink/15 cascade-in" style="animation-delay: 100ms;">
            <div class="flex flex-wrap gap-4 items-center">
                <!-- Role filters -->
                <div class="flex items-center gap-2">
                    <span class="eyebrow">Role:</span>
                    <div class="flex flex-wrap gap-1">
                        <?php foreach (['ALL', 'FE', 'BE', 'QA', 'BA'] as $opt): ?>
                            <a href="?role=<?= $opt ?>&status=<?= $statusFilter ?>&search=<?= urlencode($search) ?>" data-role-btn="<?= $opt ?>" class="role-filter-btn group flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-mono uppercase tracking-widest transition-all <?= $roleFilter === $opt ? 'bg-ink text-paper border-ink' : 'bg-transparent text-ink border-ink/25 hover:border-ink/60' ?>">
                                <?= $opt ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Status filters -->
                <div class="flex items-center gap-2">
                    <span class="eyebrow">Status:</span>
                    <div class="flex flex-wrap gap-1">
                        <?php foreach (['ALL', 'employee', 'outsource'] as $opt): ?>
                            <a href="?role=<?= $roleFilter ?>&status=<?= $opt ?>&search=<?= urlencode($search) ?>" data-status-btn="<?= $opt ?>" class="status-filter-btn group flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-mono uppercase tracking-widest transition-all <?= $statusFilter === $opt ? 'bg-ink text-paper border-ink' : 'bg-transparent text-ink border-ink/25 hover:border-ink/60' ?>">
                                <?= $opt ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <form id="search-form" method="GET" action="" class="flex items-center gap-2">
                <input type="hidden" name="role" id="active-role" value="<?= esc($roleFilter) ?>">
                <input type="hidden" name="status" id="active-status" value="<?= esc($statusFilter) ?>">
                <input type="text" id="search-input" name="search" value="<?= esc($search) ?>" placeholder="SEARCH NAME..." class="rounded-xl border border-ink/20 bg-background px-3 py-1.5 mono text-xs uppercase focus:outline-none w-48">
                <button type="submit" class="rounded-full bg-ink text-paper px-3 py-1.5 text-xs font-mono uppercase font-bold tracking-widest hover:bg-ink/90 transition-colors">Search</button>
                <a href="?role=<?= $roleFilter ?>&status=<?= $statusFilter ?>&search=" id="search-clear-link" class="<?= $search !== '' ? '' : 'hidden' ?> text-xs underline text-muted-foreground ml-1">Clear</a>
            </form>
        </div>

        <!-- Workload List -->
        <div>
            <h2 class="font-display text-2xl tracking-tight text-ink mb-6">Workload / Individual</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" id="resources-grid">
                <?php foreach ($resources as $r): 
                    $roleMatch = ($roleFilter === 'ALL' || $r['role'] === $roleFilter);
                    $statusMatch = ($statusFilter === 'ALL' || $r['status'] === $statusFilter);
                    $searchMatch = true;
                    if ($search !== '') {
                        $searchLower = strtolower($search);
                        $skillsStr = implode(',', $r['skills']);
                        $searchMatch = (
                            strpos(strtolower($r['name']), $searchLower) !== false ||
                            strpos(strtolower($r['department']), $searchLower) !== false ||
                            strpos(strtolower($r['role']), $searchLower) !== false ||
                            strpos(strtolower($r['email']), $searchLower) !== false ||
                            strpos(strtolower($r['manager']), $searchLower) !== false ||
                            strpos(strtolower($skillsStr), $searchLower) !== false
                        );
                    }
                    $isMatch = $roleMatch && $statusMatch && $searchMatch;
                    
                    $skillsBlob = implode(' ', $r['skills']);
                    $searchBlob = strtolower($r['name'] . ' ' . $r['department'] . ' ' . $r['role'] . ' ' . $r['status'] . ' ' . $r['email'] . ' ' . $r['manager'] . ' ' . $skillsBlob);
                ?>
                    <div class="resource-card group/card cursor-pointer opacity-0 translate-y-4 rounded-2xl border border-ink/15 bg-paper p-5 shadow-sm hover:border-ink/60 transition-all flex flex-col justify-between <?= $isMatch ? '' : 'hidden' ?>"
                         data-role="<?= esc($r['role']) ?>"
                         data-status="<?= esc($r['status']) ?>"
                         data-search="<?= esc($searchBlob) ?>"
                         onclick="if (!event.target.closest('button') && !event.target.closest('a')) window.location.href = '<?= base_url('member/' . $r['id']) ?>'">
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <a href="<?= base_url('member/' . $r['id']) ?>" class="group/link flex items-center gap-3 hover:opacity-85">
                                    <?php
                                    $initials = '';
                                    $names = explode(' ', $r['name']);
                                    foreach ($names as $n) { $initials .= strtoupper(substr($n,0,1)); }
                                    $initials = substr($initials, 0, 2);
                                    ?>
                                    <span class="grid h-10 w-10 place-items-center rounded-full bg-ink text-paper font-display text-xs font-bold transition-transform group-hover/card:scale-105"><?= $initials ?></span>
                                    <div>
                                        <h4 class="font-display text-sm font-bold text-ink leading-tight uppercase group-hover/card:underline"><?= esc($r['name']) ?></h4>
                                        <div class="mono text-[9px] text-muted-foreground uppercase tracking-widest mt-0.5"><?= esc($r['department']) ?></div>
                                    </div>
                                </a>
                                <div class="flex items-center gap-1.5 relative z-10">
                                    <button onclick="openEditModal(this)" data-resource="<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>" class="text-ink/60 hover:text-ink">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <button onclick="openDeleteModal(this)" data-resource="<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>" class="text-destructive hover:opacity-80">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-4 flex items-end justify-between border-t border-ink/10 pt-3">
                                <span class="font-display text-2xl font-bold"><?= $r['utilization'] ?><span class="text-xs text-muted-foreground">%</span></span>
                                <span class="mono text-[9px] uppercase tracking-widest text-muted-foreground">utilization</span>
                            </div>
                            <div class="mt-2 h-1.5 rounded-full bg-ink/10 overflow-hidden">
                                <div class="h-full <?= getUtilColor($r['utilization']) ?>" style="width: <?= min(100, $r['utilization']) ?>%"></div>
                            </div>
                        </div>
                        <div class="mt-3 flex justify-between items-center text-[10px] font-mono text-muted-foreground uppercase">
                            <span>Role: <?= esc($r['role']) ?></span>
                            <span>Status: <?= esc($r['status']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </section>
</div>

<!-- ==================== DIALOG MODALS ==================== -->

<!-- Create Resource -->
<div id="create-resource-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-xl w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Add New Resource</h3>
            <button onclick="closeModal('create-resource-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form action="<?= base_url('resource/create') ?>" method="POST" class="overflow-y-auto flex-1 p-6 space-y-4 text-left font-mono">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Full Name</span>
                <input name="name" required placeholder="Name" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Department</span>
                    <select name="department" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <option value="">-- SELECT DEPT --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= esc($dept['name']) ?>"><?= esc($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Role</span>
                    <select name="role" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <option value="FE">FE (Frontend)</option>
                        <option value="BE">BE (Backend)</option>
                        <option value="QA">QA (Quality Assurance)</option>
                        <option value="BA">BA (Business Analyst)</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Status</span>
                    <select name="status" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <option value="employee">employee</option>
                        <option value="outsource">outsource</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Location</span>
                    <input name="location" placeholder="e.g. Jakarta, Remote" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Email Address</span>
                <input type="email" name="email" required placeholder="email@gtech.com" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Manager</span>
                <input name="manager" placeholder="Manager Name" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Skills (Comma-separated)</span>
                <input name="skills" placeholder="e.g. React, PHP, QA testing" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-resource-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Resource -->
<div id="edit-resource-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-xl w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Edit Resource</h3>
            <button onclick="closeModal('edit-resource-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="edit-resource-form" action="" method="POST" class="overflow-y-auto flex-1 p-6 space-y-4 text-left font-mono">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Full Name</span>
                <input id="edit-res-name" name="name" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Department</span>
                    <select id="edit-res-dept" name="department" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= esc($dept['name']) ?>"><?= esc($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Role</span>
                    <select id="edit-res-role" name="role" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <option value="FE">FE</option>
                        <option value="BE">BE</option>
                        <option value="QA">QA</option>
                        <option value="BA">BA</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Status</span>
                    <select id="edit-res-status" name="status" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                        <option value="employee">employee</option>
                        <option value="outsource">outsource</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Location</span>
                    <input id="edit-res-location" name="location" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Email Address</span>
                <input type="email" id="edit-res-email" name="email" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Manager</span>
                <input id="edit-res-manager" name="manager" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Skills (Comma-separated)</span>
                <input id="edit-res-skills" name="skills" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-resource-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Resource -->
<div id="delete-resource-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <form id="delete-resource-form" action="" method="POST" class="flex flex-col h-full">
            <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
                <h3 class="font-display text-sm font-bold uppercase tracking-wide">Delete Resource?</h3>
                <button type="button" onclick="closeModal('delete-resource-modal')" class="text-paper hover:opacity-75">✕</button>
            </div>
            <div class="p-6 text-left overflow-y-auto flex-1">
                <p class="mono text-xs text-ink/80 leading-normal">
                    Are you sure you want to delete <span id="delete-res-name" class="font-black text-ink"></span>?<br /><br />
                    This action is permanent and will remove their allocations from projects and squads.
                </p>
            </div>
            <div class="border-t border-ink/15 p-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('delete-resource-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-destructive text-destructive-foreground px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Delete Resource</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= base_url('js/resource.js') ?>"></script>
<?= $this->endSection() ?>
