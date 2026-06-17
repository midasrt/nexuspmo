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

// Prepare nodes for Squad mesh
$squadNodes = [];
$projectNodes = [];

$squadToProjectLinks = [];

foreach ($squads as $s) {
    $squadNodes[$s['id']] = [
        'id' => $s['id'],
        'name' => $s['name'],
        'lead' => $s['lead']
    ];
    foreach ($s['projects'] as $p) {
        $projectNodes[$p['id']] = [
            'id' => $p['id'],
            'code' => $p['code'],
            'name' => $p['name']
        ];
        $squadToProjectLinks[] = [
            'squad_id' => $s['id'],
            'project_id' => $p['id']
        ];
    }
}

$numSquads = count($squadNodes);
$numProjects = count($projectNodes);
$maxNodes = max($numSquads, $numProjects);

$W = 900;
$H = max(320, $maxNodes * 60);
$padY = 40;

// Coordinates for Squads (Left: X = 180)
$squadList = array_values($squadNodes);
$sCoord = [];
foreach ($squadList as $i => $sq) {
    $y = $padY + ($i * ($H - $padY * 2)) / max(1, $numSquads - 1);
    $sCoord[$sq['id']] = ['x' => 180, 'y' => $y];
}

// Coordinates for Projects (Right: X = 720)
$projectList = array_values($projectNodes);
$pCoord = [];
foreach ($projectList as $i => $pj) {
    $y = $padY + ($i * ($H - $padY * 2)) / max(1, $numProjects - 1);
    $pCoord[$pj['id']] = ['x' => 720, 'y' => $y];
}
?>

<div class="min-h-screen pb-24">
    <!-- Header -->
    <header class="border-b border-ink/15 py-6 no-print mb-8 cascade-in" style="animation-delay: 0ms;">
        <div class="w-full px-8 lg:px-14 flex items-center justify-between gap-4">
            <div>
                <span class="eyebrow">PMO // Module</span>
                <h1 class="font-display text-2xl md:text-3xl font-black uppercase mt-1 tracking-tight">Squads</h1>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="openCreateModal()" class="rounded-full border border-ink/20 bg-ink text-paper hover:bg-ink/90 px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    New Squad
                </button>
                <a href="<?= base_url('squad-map') ?>" class="rounded-full border border-ink/20 bg-card text-ink hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="network" class="w-3.5 h-3.5"></i>
                    Squad Map
                </a>
            </div>
        </div>
    </header>

    <div class="w-full px-8 lg:px-14 flex flex-col gap-10">
        <!-- Squad Cards Grid -->
        <div>
            <h2 class="font-display text-2xl tracking-tight text-ink mb-6">Squad Structure</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <?php foreach ($squads as $idx => $s): ?>
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
                    <div class="rounded-2xl border border-ink/15 bg-card/70 backdrop-blur p-6 shadow-sm hover:border-ink/60 transition-all flex flex-col justify-between cascade-in" style="animation-delay: <?= 50 + $idx * 50 ?>ms;">
                        <div>
                            <div class="flex items-start justify-between gap-3 flex-wrap">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="eyebrow">SQUAD</span>
                                        <div class="flex items-center gap-1.5">
                                            <!-- Edit Squad Button -->
                                            <button title="Edit Squad" onclick="openEditModal(this)" data-squad="<?= htmlspecialchars(json_encode(['id' => $s['id'], 'name' => $s['name'], 'lead' => $s['lead'], 'mission' => $s['mission']]), ENT_QUOTES, 'UTF-8') ?>" class="text-ink/60 hover:text-ink">
                                                <i data-lucide="pencil" class="w-3.5 h-3.5" stroke-width="2"></i>
                                            </button>
                                            <!-- Delete Squad Button -->
                                            <button title="Delete Squad" onclick="openDeleteModal(this)" data-squad="<?= htmlspecialchars(json_encode(['id' => $s['id'], 'name' => $s['name'], 'lead' => $s['lead'], 'mission' => $s['mission']]), ENT_QUOTES, 'UTF-8') ?>" class="text-destructive hover:opacity-85">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5" stroke-width="2"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <h3 class="font-display text-xl font-bold uppercase tracking-tight truncate mt-1 text-ink"><?= esc($s['name']) ?></h3>
                                    <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground mt-1">
                                        Lead — <span class="text-ink font-bold"><?= esc($s['lead']) ?></span>
                                    </div>
                                </div>
                                
                                <!-- Role coverage badges -->
                                <div class="flex gap-1 flex-wrap justify-end">
                                    <?php foreach ($coverage as $role => $count): ?>
                                        <span class="mono text-[9px] uppercase tracking-wider rounded-full border border-ink/15 px-2 py-0.5 <?= $roleBgs[$role] ?> text-ink" title="<?= $count ?> <?= $role ?>">
                                            <?= $role ?> ×<?= $count ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <p class="text-xs text-muted-foreground mt-4 leading-relaxed max-w-prose"><?= esc($s['mission']) ?></p>

                            <!-- Members List -->
                            <div class="mt-6">
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <span class="eyebrow">
                                        Members (<?= count($s['members']) ?>)
                                    </span>
                                    <button type="button" onclick="openAddMemberModal(<?= $s['id'] ?>, '<?= esc($s['name'], 'js') ?>')" class="rounded-full bg-ink text-paper px-2 py-0.5 text-[10px] font-mono uppercase font-bold hover:bg-ink/90 transition-colors">
                                        + Add
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <?php foreach ($s['members'] as $m): ?>
                                        <div class="rounded-xl border border-ink/10 bg-background/50 px-3 py-2 flex items-center justify-between gap-2 shadow-sm hover:border-ink/35 transition-colors">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <form action="<?= base_url('squads/remove-member/' . $s['id'] . '/' . $m['id']) ?>" method="POST" class="inline m-0">
                                                    <button type="submit" title="Remove Member" class="text-destructive hover:opacity-85 focus:outline-none flex items-center">
                                                        <i data-lucide="x" class="w-3.5 h-3.5" stroke-width="2.5"></i>
                                                    </button>
                                                </form>
                                                <span class="text-xs font-semibold uppercase truncate text-ink"><?= esc($m['name']) ?></span>
                                            </div>
                                            <span class="mono text-[8px] font-bold uppercase rounded-full px-1.5 py-0.5 <?= $roleBgs[$m['role']] ?? 'bg-secondary' ?> text-ink">
                                                <?= esc($m['role']) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Assigned Projects -->
                        <div class="mt-6 border-t border-ink/10 pt-4">
                            <div class="eyebrow mb-2">
                                Assigned Projects (<?= count($s['projects']) ?>)
                            </div>
                            <?php if (empty($s['projects'])): ?>
                                <div class="mono text-[10px] uppercase text-muted-foreground border border-dashed border-ink/20 rounded-xl bg-background/30 px-3 py-3 text-center">
                                    No projects assigned
                                </div>
                            <?php else: ?>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($s['projects'] as $p): ?>
                                        <a href="<?= base_url('project/' . $p['id']) ?>" class="rounded-full border border-ink/15 bg-secondary px-3 py-1 text-[10px] font-mono uppercase tracking-wide hover:bg-ink hover:text-paper transition-all">
                                            <span class="font-bold"><?= esc($p['code']) ?></span> · 
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

        <!-- Squad Mesh Graph -->
        <?php if (!empty($squadNodes)): ?>
        <div class="mt-4 cascade-in" style="animation-delay: 150ms;">
            <div class="flex items-end justify-between mb-4">
                <h2 class="font-display text-2xl tracking-tight text-ink">Squad Mesh / Allocation</h2>
                <span class="eyebrow">Hover node to isolate connections</span>
            </div>
            
            <div class="rounded-2xl border border-ink/15 bg-card/70 backdrop-blur p-6 overflow-x-auto no-scrollbar shadow-sm">
                <svg viewBox="0 0 <?= $W ?> <?= $H ?>" class="w-full h-auto max-h-[600px]">
                    <!-- Link curves: Squad to Project -->
                    <?php foreach ($squadToProjectLinks as $link): 
                        if (!isset($sCoord[$link['squad_id']]) || !isset($pCoord[$link['project_id']])) continue;
                        $from = $sCoord[$link['squad_id']];
                        $to = $pCoord[$link['project_id']];
                        $cx1 = $from['x'] + 180;
                        $cx2 = $to['x'] - 180;
                    ?>
                        <path d="M <?= $from['x'] ?> <?= $from['y'] ?> C <?= $cx1 ?> <?= $from['y'] ?>, <?= $cx2 ?> <?= $to['y'] ?>, <?= $to['x'] ?> <?= $to['y'] ?>"
                               fill="none" stroke="var(--ink)" stroke-width="0.6" opacity="0.18" class="mesh-path transition-all duration-300" 
                               data-squad="<?= $link['squad_id'] ?>" data-project="<?= $link['project_id'] ?>"></path>
                    <?php endforeach; ?>

                    <!-- Squad nodes (Left: X = 180) -->
                    <?php foreach ($squadList as $sq): 
                        $coord = $sCoord[$sq['id']];
                    ?>
                        <g class="mesh-node cursor-pointer transition-all duration-300" data-kind="s" data-id="<?= $sq['id'] ?>" onmouseenter="hoverNode('s', '<?= $sq['id'] ?>')" onmouseleave="clearHover()">
                            <circle cx="<?= $coord['x'] ?>" cy="<?= $coord['y'] ?>" r="6" fill="var(--ink)"></circle>
                            <text x="<?= $coord['x'] - 14 ?>" y="<?= $coord['y'] - 2 ?>" text-anchor="end" class="font-display font-bold uppercase tracking-wider" fontSize="12" fill="var(--ink)"><?= esc($sq['name']) ?></text>
                            <text x="<?= $coord['x'] - 14 ?>" y="<?= $coord['y'] + 10 ?>" text-anchor="end" class="font-mono text-[9px] uppercase tracking-widest text-muted-foreground" fontSize="8" fill="var(--muted-foreground)">Lead: <?= esc($sq['lead']) ?></text>
                        </g>
                    <?php endforeach; ?>

                    <!-- Project nodes (Right: X = 720) -->
                    <?php foreach ($projectList as $p): 
                        $coord = $pCoord[$p['id']];
                    ?>
                        <g class="mesh-node cursor-pointer transition-all duration-300" data-kind="p" data-id="<?= $p['id'] ?>" onmouseenter="hoverNode('p', '<?= $p['id'] ?>')" onmouseleave="clearHover()">
                            <circle cx="<?= $coord['x'] ?>" cy="<?= $coord['y'] ?>" r="6" fill="var(--ink)"></circle>
                            <text x="<?= $coord['x'] + 14 ?>" y="<?= $coord['y'] + 4 ?>" class="font-display font-bold" fontSize="11" fill="var(--ink)">
                                <?php
                                $nameParts = explode('//', $p['name']);
                                echo esc(trim($nameParts[0])) . ' (' . esc($p['code']) . ')';
                                ?>
                            </text>
                        </g>
                    <?php endforeach; ?>
                </svg>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ==================== DIALOG MODALS ==================== -->

<!-- CREATE SQUAD MODAL -->
<div id="create-squad-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-xl w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">New Squad</h3>
            <button type="button" onclick="closeModal('create-squad-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form action="<?= base_url('squads/create') ?>" method="POST" class="overflow-y-auto flex-1 p-6 space-y-4 text-left font-mono">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Squad Name</span>
                <input name="name" required placeholder="e.g. Phoenix, Alpha" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Lead</span>
                <input name="lead" required placeholder="Lead Name" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Mission Statement</span>
                <textarea name="mission" rows="3" placeholder="Define the squad's target and scope..." class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-squad-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT SQUAD MODAL -->
<div id="edit-squad-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-xl w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Edit Squad</h3>
            <button type="button" onclick="closeModal('edit-squad-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="edit-squad-form" action="" method="POST" class="overflow-y-auto flex-1 p-6 space-y-4 text-left font-mono">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Squad Name</span>
                <input id="edit-sq-name" name="name" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Lead</span>
                <input id="edit-sq-lead" name="lead" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Mission Statement</span>
                <textarea id="edit-sq-mission" name="mission" rows="3" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-squad-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE SQUAD MODAL -->
<div id="delete-squad-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <form id="delete-squad-form" action="" method="POST" class="flex flex-col h-full">
            <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
                <h3 class="font-display text-sm font-bold uppercase tracking-wide">Delete Squad?</h3>
                <button type="button" onclick="closeModal('delete-squad-modal')" class="text-paper hover:opacity-75">✕</button>
            </div>
            <div class="p-6 text-left overflow-y-auto flex-1">
                <p class="mono text-xs text-ink/80 leading-normal">
                    Are you sure you want to delete <span id="delete-sq-name" class="font-black text-ink"></span>?<br /><br />
                    This action is permanent, will remove all squad members, and unassign any projects mapped to this squad.
                </p>
            </div>
            <div class="border-t border-ink/15 p-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('delete-squad-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-destructive text-destructive-foreground px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- ADD SQUAD MEMBER MODAL -->
<div id="add-member-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <form action="<?= base_url('squads/add-member') ?>" method="POST" class="flex flex-col h-full">
            <input type="hidden" id="add-member-squad-id" name="squad_id" value="" />
            <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
                <h3 class="font-display text-sm font-bold uppercase tracking-wide">Add Squad Member</h3>
                <button type="button" onclick="closeModal('add-member-modal')" class="text-paper hover:opacity-75">✕</button>
            </div>
            <div class="p-6 flex flex-col gap-4 text-left font-mono overflow-y-auto flex-1">
                <div class="mono text-xs mb-2">
                    Add member to squad: <span id="add-member-squad-display" class="font-black text-ink"></span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase text-muted-foreground">Select Resource</span>
                    <input type="text" list="member-resource-options" id="member-resource-search" placeholder="Type name to search..." required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase" oninput="syncMemberResourceId(this)" />
                    <datalist id="member-resource-options">
                        <?php foreach ($resources as $res): ?>
                            <option data-id="<?= $res['id'] ?>" value="<?= esc($res['name']) ?> (<?= esc($res['role']) ?>)"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <input type="hidden" name="resource_id" id="member-resource-id-hidden" value="" />
                </div>
            </div>
            <div class="border-t border-ink/15 p-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('add-member-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Add Member</button>
            </div>
        </form>
    </div>
</div>

<script>
    function hoverNode(kind, id) {
        const paths = document.querySelectorAll('.mesh-path');
        const nodes = document.querySelectorAll('.mesh-node');
        
        paths.forEach(p => {
            const squadId = p.getAttribute('data-squad');
            const projectId = p.getAttribute('data-project');
            
            let lit = false;
            if (kind === 's' && squadId === id) lit = true;
            if (kind === 'p' && projectId === id) lit = true;
            
            if (lit) {
                p.setAttribute('opacity', '0.9');
                p.setAttribute('stroke-width', '1.2');
            } else {
                p.setAttribute('opacity', '0.06');
                p.setAttribute('stroke-width', '0.6');
            }
        });

        nodes.forEach(n => {
            const nKind = n.getAttribute('data-kind');
            const nId = n.getAttribute('data-id');
            
            let lit = false;
            if (nKind === kind && nId === id) lit = true;
            
            if (!lit) {
                paths.forEach(p => {
                    const squadId = p.getAttribute('data-squad');
                    const projectId = p.getAttribute('data-project');
                    
                    if (kind === 's' && nKind === 'p' && projectId === nId && squadId === id) lit = true;
                    if (kind === 'p' && nKind === 's' && squadId === nId && projectId === id) lit = true;
                });
            }

            const circle = n.querySelector('circle');
            const texts = n.querySelectorAll('text');
            if (lit) {
                circle.setAttribute('opacity', '1');
                circle.setAttribute('r', '9');
                texts.forEach(t => t.setAttribute('opacity', '1'));
            } else {
                circle.setAttribute('opacity', '0.25');
                circle.setAttribute('r', '6');
                texts.forEach(t => t.setAttribute('opacity', '0.35'));
            }
        });
    }

    function clearHover() {
        const paths = document.querySelectorAll('.mesh-path');
        const nodes = document.querySelectorAll('.mesh-node');
        
        paths.forEach(p => {
            p.setAttribute('opacity', '0.18');
            p.setAttribute('stroke-width', '0.6');
        });

        nodes.forEach(n => {
            n.querySelector('circle').setAttribute('opacity', '1');
            n.querySelector('circle').setAttribute('r', '6');
            n.querySelectorAll('text').forEach(t => t.setAttribute('opacity', '1'));
        });
    }
</script>
<script src="<?= base_url('js/squads.js') ?>?v=<?= time() ?>"></script>
<?= $this->endSection() ?>
