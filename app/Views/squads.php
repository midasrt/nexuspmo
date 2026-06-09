<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<?php
$roleBgs = [
    'FE' => 'bg-status-ontrack',
    'BE' => 'bg-status-atrisk',
    'QA' => 'bg-status-delayed',
    'BA' => 'bg-status-backlog',
];

$requiredRoles = ['BA', 'BE', 'FE', 'QA'];
?>

<div class="min-h-screen p-6 md:p-10">
    <div class="max-w-7xl mx-auto">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="mono text-[10px] uppercase tracking-[0.3em] text-muted-foreground">
                    PMO // Module
                </div>
                <h1 class="mono text-4xl font-black uppercase tracking-tight mt-2">Squads</h1>
                <p class="mt-2 text-sm text-muted-foreground max-w-prose">
                    Cross-functional squad composition, leads, and assigned projects. Every squad has at minimum 1 BA, 1 BE, 1 FE, and 1 QA.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="openCreateModal()" class="inline-flex items-center gap-2 brutal-border bg-foreground text-background px-4 py-2.5 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    <i data-lucide="plus" class="h-4 w-4" stroke-width="3"></i>
                    New Squad
                </button>
                <a href="<?= base_url('squad-map') ?>" class="inline-flex items-center gap-2 brutal-border bg-card text-foreground px-4 py-2.5 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    <i data-lucide="network" class="h-4 w-4" stroke-width="3"></i>
                    Squad Map
                </a>
            </div>
        </div>

        <!-- Squad Cards Grid -->
        <div class="grid md:grid-cols-2 gap-4 mt-8">
            <?php foreach ($squads as $s): ?>
                <?php
                // Count roles coverage
                $coverage = [];
                foreach ($requiredRoles as $role) {
                    $roleCount = 0;
                    foreach ($s['members'] as $m) {
                        if ($m['role'] === $role) $roleCount++;
                    }
                    $coverage[$role] = $roleCount;
                }
                ?>
                <div class="brutal-border-thick bg-card p-5 brutal-shadow">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">SQUAD</span>
                                <div class="flex items-center gap-1">
                                    <!-- Edit Squad Button -->
                                    <button title="Edit Squad" onclick="openEditModal(this)" data-squad="<?= htmlspecialchars(json_encode(['id' => $s['id'], 'name' => $s['name'], 'lead' => $s['lead'], 'mission' => $s['mission']]), ENT_QUOTES, 'UTF-8') ?>" class="brutal-border p-1 bg-background brutal-hover">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                                    </button>
                                    <!-- Delete Squad Button -->
                                    <button title="Delete Squad" onclick="openDeleteModal(this)" data-squad="<?= htmlspecialchars(json_encode(['id' => $s['id'], 'name' => $s['name'], 'lead' => $s['lead'], 'mission' => $s['mission']]), ENT_QUOTES, 'UTF-8') ?>" class="brutal-border p-1 bg-background brutal-hover hover:bg-destructive hover:text-destructive-foreground">
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                                    </button>
                                </div>
                            </div>
                            <h2 class="mono text-2xl font-black uppercase tracking-tight truncate"><?= esc($s['name']) ?></h2>
                            <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground mt-1">
                                Lead — <span class="text-foreground font-black"><?= esc($s['lead']) ?></span>
                            </div>
                        </div>
                        
                        <!-- Role coverage badges -->
                        <div class="flex gap-1 flex-wrap justify-end">
                            <?php foreach ($coverage as $role => $count): ?>
                                <span class="mono text-[10px] uppercase tracking-widest border border-foreground px-2 py-1 <?= $roleBgs[$role] ?>" title="<?= $count ?> <?= $role ?>">
                                    <?= $role ?> ×<?= $count ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <p class="text-sm mt-3"><?= esc($s['mission']) ?></p>

                    <!-- Members List -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">
                                Members (<?= count($s['members']) ?>)
                            </span>
                            <button type="button" onclick="openAddMemberModal(<?= $s['id'] ?>, '<?= esc($s['name'], 'js') ?>')" class="inline-flex items-center gap-1 brutal-border bg-foreground text-background px-1.5 py-0.5 mono text-[9px] uppercase font-black brutal-hover">
                                <i data-lucide="plus" class="h-2.5 w-2.5" stroke-width="3"></i> Add
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                            <?php foreach ($s['members'] as $m): ?>
                                <div class="brutal-border bg-background px-2 py-1.5 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <!-- Remove Squad Member Button -->
                                        <form action="<?= base_url('squads/remove-member/' . $s['id'] . '/' . $m['id']) ?>" method="POST" class="inline m-0">
                                            <button type="submit" title="Remove Member" class="text-destructive hover:opacity-85 focus:outline-none flex items-center">
                                                <i data-lucide="trash-2" class="h-3 w-3" stroke-width="2.5"></i>
                                            </button>
                                        </form>
                                        <span class="text-xs font-bold uppercase truncate"><?= esc($m['name']) ?></span>
                                    </div>
                                    <span class="mono text-[9px] font-black uppercase border border-foreground px-1.5 py-0.5 <?= $roleBgs[$m['role']] ?? 'bg-secondary' ?>">
                                        <?= esc($m['role']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Assigned Projects -->
                    <div class="mt-4">
                        <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground mb-2">
                            Assigned Projects (<?= count($s['projects']) ?>)
                        </div>
                        <?php if (empty($s['projects'])): ?>
                            <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground border border-foreground bg-background px-2 py-2 text-center">
                                No projects assigned
                            </div>
                        <?php else: ?>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($s['projects'] as $p): ?>
                                    <a href="<?= base_url('project/' . $p['id']) ?>" class="mono text-[10px] uppercase tracking-widest border border-foreground bg-secondary px-2 py-1 hover:bg-accent hover:text-accent-foreground transition-colors">
                                        <span class="font-black"><?= esc($p['code']) ?></span> · 
                                        <?php
                                        $nameParts = explode('//', $p['name']);
                                        echo esc(trim($nameParts[0]));
                                        ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ==================== CREATE SQUAD MODAL ==================== -->
<div id="create-squad-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-2xl w-full p-0 brutal-shadow">
        <form action="<?= base_url('squads/create') ?>" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">New Squad</h3>
                <button type="button" onclick="closeModal('create-squad-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4 text-left">
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Name</span>
                    <input name="name" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Lead</span>
                    <input name="lead" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Mission</span>
                    <textarea name="mission" rows="3" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('create-squad-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== EDIT SQUAD MODAL ==================== -->
<div id="edit-squad-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-2xl w-full p-0 brutal-shadow">
        <form id="edit-squad-form" action="" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">Edit Squad</h3>
                <button type="button" onclick="closeModal('edit-squad-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4 text-left">
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Name</span>
                    <input id="edit-sq-name" name="name" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Lead</span>
                    <input id="edit-sq-lead" name="lead" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Mission</span>
                    <textarea id="edit-sq-mission" name="mission" rows="3" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('edit-squad-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== DELETE SQUAD MODAL ==================== -->
<div id="delete-squad-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow">
        <form id="delete-squad-form" action="" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">Delete Squad?</h3>
                <button type="button" onclick="closeModal('delete-squad-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 text-left">
                <p class="mono text-xs text-foreground">
                    Are you sure you want to delete <span id="delete-sq-name" class="font-black"></span>?<br />
                    This action is permanent, will remove all squad members, and unassign any projects mapped to this squad.
                </p>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('delete-squad-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-destructive text-destructive-foreground px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Delete
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== ADD SQUAD MEMBER MODAL ==================== -->
<div id="add-member-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow">
        <form action="<?= base_url('squads/add-member') ?>" method="POST">
            <input type="hidden" id="add-member-squad-id" name="squad_id" value="" />
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">Add Squad Member</h3>
                <button type="button" onclick="closeModal('add-member-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 flex flex-col gap-3 text-left">
                <div class="mono text-xs mb-2">
                    Add member to squad: <span id="add-member-squad-display" class="font-black"></span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Select Resource</span>
                    <select name="resource_id" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">-- Choose Resource --</option>
                        <?php foreach ($resources as $res): ?>
                            <option value="<?= $res['id'] ?>"><?= esc($res['name']) ?> (<?= esc($res['role']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('add-member-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Add Member
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('create-squad-modal').classList.remove('hidden');
    }

    function openEditModal(button) {
        const sq = JSON.parse(button.getAttribute('data-squad'));
        document.getElementById('edit-squad-form').action = '<?= base_url('squads/update') ?>/' + sq.id;
        document.getElementById('edit-sq-name').value = sq.name;
        document.getElementById('edit-sq-lead').value = sq.lead;
        document.getElementById('edit-sq-mission').value = sq.mission || '';
        document.getElementById('edit-squad-modal').classList.remove('hidden');
    }

    function openDeleteModal(button) {
        const sq = JSON.parse(button.getAttribute('data-squad'));
        document.getElementById('delete-squad-form').action = '<?= base_url('squads/delete') ?>/' + sq.id;
        document.getElementById('delete-sq-name').innerText = sq.name;
        document.getElementById('delete-squad-modal').classList.remove('hidden');
    }

    function openAddMemberModal(squadId, squadName) {
        document.getElementById('add-member-squad-id').value = squadId;
        document.getElementById('add-member-squad-display').innerText = squadName;
        document.getElementById('add-member-modal').classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    window.onclick = function(event) {
        const modals = ['create-squad-modal', 'edit-squad-modal', 'delete-squad-modal', 'add-member-modal'];
        modals.forEach(id => {
            const modal = document.getElementById(id);
            if (event.target == modal) {
                modal.classList.add('hidden');
            }
        });
    }
</script>
<?= $this->endSection() ?>
