<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<?php
$statusBgs = [
    'on-track' => 'bg-status-ontrack',
    'at-risk'  => 'bg-status-atrisk',
    'blocked'  => 'bg-status-blocked',
    'delayed'  => 'bg-status-delayed',
    'backlog'  => 'bg-status-backlog',
];
?>

<div class="min-h-screen p-6 md:p-10">
    <div class="max-w-5xl mx-auto">
        <div class="mono text-[10px] uppercase tracking-[0.3em] text-muted-foreground">
            PMO // Module
        </div>
        <h1 class="mono text-4xl font-black uppercase tracking-tight mt-2">Settings</h1>
        <p class="mt-2 text-sm text-muted-foreground max-w-prose">
            Definitions used throughout the portfolio. These drive how the project office classifies and reports work.
        </p>

    <!-- Windows 8 Style Metro Tiles Grid -->
        <style>
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .animate-fade-in {
                animation: fadeIn 0.2s ease-out forwards;
            }
        </style>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <!-- Tile 1: Status Definitions -->
            <button id="tile-status" onclick="toggleSection('status')" class="relative text-left brutal-border-thick brutal-shadow p-6 bg-[#0078d7] text-white transition-all duration-150 select-none overflow-hidden h-40 hover:-translate-y-1 hover:shadow-[6px_6px_0_0_var(--foreground)] active:translate-y-0.5 active:shadow-[2px_2px_0_0_var(--foreground)] focus:outline-none">
                <!-- Checkmark badge in corner -->
                <div id="badge-status" class="hidden absolute top-3 right-3 w-6 h-6 border-2 border-white bg-white text-[#0078d7] flex items-center justify-center font-bold text-xs shadow-md">✓</div>
                
                <div class="h-full flex flex-col justify-between">
                    <div>
                        <div class="mono text-[10px] uppercase tracking-widest opacity-80 mb-1">Configuration</div>
                        <h3 class="mono text-lg font-black uppercase tracking-tight leading-tight">Project Status Definitions</h3>
                    </div>
                    <div class="flex items-end justify-between mt-4">
                        <span class="mono text-[10px] uppercase tracking-wider font-bold bg-white/20 px-2 py-0.5"><?= count($statusDefinitions) ?> DEFINED</span>
                        <i data-lucide="pencil-ruler" class="w-10 h-10 opacity-30 shrink-0"></i>
                    </div>
                </div>
            </button>

            <!-- Tile 2: Org Structure -->
            <button id="tile-org" onclick="toggleSection('org')" class="relative text-left brutal-border-thick brutal-shadow p-6 bg-[#783ecf] text-white transition-all duration-150 select-none overflow-hidden h-40 hover:-translate-y-1 hover:shadow-[6px_6px_0_0_var(--foreground)] active:translate-y-0.5 active:shadow-[2px_2px_0_0_var(--foreground)] focus:outline-none">
                <!-- Checkmark badge in corner -->
                <div id="badge-org" class="hidden absolute top-3 right-3 w-6 h-6 border-2 border-white bg-white text-[#783ecf] flex items-center justify-center font-bold text-xs shadow-md">✓</div>

                <div class="h-full flex flex-col justify-between">
                    <div>
                        <div class="mono text-[10px] uppercase tracking-widest opacity-80 mb-1">Structure</div>
                        <h3 class="mono text-lg font-black uppercase tracking-tight leading-tight">Org. Structure</h3>
                    </div>
                    <div class="flex items-end justify-between mt-4">
                        <span class="mono text-[10px] uppercase tracking-wider font-bold bg-white/20 px-2 py-0.5"><?= count($orgStructures) ?> DEPARTMENTS</span>
                        <i data-lucide="network" class="w-10 h-10 opacity-30 shrink-0"></i>
                    </div>
                </div>
            </button>

            <!-- Tile 3: Squad Definitions -->
            <button id="tile-squads" onclick="toggleSection('squads')" class="relative text-left brutal-border-thick brutal-shadow p-6 bg-[#008272] text-white transition-all duration-150 select-none overflow-hidden h-40 hover:-translate-y-1 hover:shadow-[6px_6px_0_0_var(--foreground)] active:translate-y-0.5 active:shadow-[2px_2px_0_0_var(--foreground)] focus:outline-none">
                <!-- Checkmark badge in corner -->
                <div id="badge-squads" class="hidden absolute top-3 right-3 w-6 h-6 border-2 border-white bg-white text-[#008272] flex items-center justify-center font-bold text-xs shadow-md">✓</div>

                <div class="h-full flex flex-col justify-between">
                    <div>
                        <div class="mono text-[10px] uppercase tracking-widest opacity-80 mb-1">Squads</div>
                        <h3 class="mono text-lg font-black uppercase tracking-tight leading-tight">Squad Definitions</h3>
                    </div>
                    <div class="flex items-end justify-between mt-4">
                        <span class="mono text-[10px] uppercase tracking-wider font-bold bg-white/20 px-2 py-0.5"><?= count($squads) ?> ACTIVE SQUADS</span>
                        <i data-lucide="boxes" class="w-10 h-10 opacity-30 shrink-0"></i>
                    </div>
                </div>
            </button>
        </div>

        <!-- Collapsible Content Sections -->
        <div class="mt-8">
            <!-- Project Status Definitions Section -->
            <div id="section-status" class="section-content hidden">
                <section class="mt-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-4 h-4 bg-primary brutal-border"></div>
                        <h2 class="mono text-xl font-black uppercase tracking-tight">Project Status Definitions</h2>
                        <div class="flex-1 h-[2px] bg-border"></div>
                        <button onclick="openCreateModal()" class="brutal-border bg-foreground text-background px-3 py-1.5 mono text-xs uppercase tracking-widest font-black brutal-hover">
                            + New Definition
                        </button>
                    </div>
                    
                    <div class="brutal-border bg-card brutal-shadow overflow-hidden">
                        <table class="w-full mono text-xs">
                            <thead class="bg-foreground text-background uppercase tracking-widest">
                                <tr>
                                    <th class="text-left px-3 py-2 text-[10px] w-40 font-black">Status</th>
                                    <th class="text-left px-3 py-2 text-[10px] font-black">Definition / Criteria</th>
                                    <th class="text-right px-3 py-2 text-[10px] w-24 font-black">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statusDefinitions as $d): ?>
                                    <tr class="border-t border-foreground">
                                        <td class="px-3 py-3 align-top">
                                            <span class="inline-block mono text-[10px] font-black uppercase tracking-widest border border-foreground text-background px-2 py-1" style="background-color: <?= esc($d['color'] ?? '#6B7280') ?>; color: #ffffff;">
                                                <?= esc($d['label']) ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 align-top text-foreground font-bold uppercase"><?= esc($d['criteria']) ?></td>
                                        <td class="px-3 py-3 align-top text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <!-- Edit status definition -->
                                                <button title="Edit" onclick="openEditModal(this)" data-definition="<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>" class="brutal-border p-1.5 bg-background brutal-hover">
                                                    <i data-lucide="pencil" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                                                </button>
                                                <!-- Delete status definition -->
                                                <button title="Delete" onclick="openDeleteModal(this)" data-definition="<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>" class="brutal-border p-1.5 bg-background brutal-hover hover:bg-destructive hover:text-destructive-foreground">
                                                    <i data-lucide="trash-2" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <!-- Org. Structure Section -->
            <div id="section-org" class="section-content hidden">
                <section class="mt-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-4 h-4 bg-primary brutal-border"></div>
                        <h2 class="mono text-xl font-black uppercase tracking-tight">Org. Structure</h2>
                        <div class="flex-1 h-[2px] bg-border"></div>
                        <button onclick="openCreateOrgModal()" class="brutal-border bg-foreground text-background px-3 py-1.5 mono text-xs uppercase tracking-widest font-black brutal-hover">
                            + New Department
                        </button>
                    </div>
                    
                    <div class="brutal-border bg-card brutal-shadow overflow-hidden">
                        <table class="w-full mono text-xs">
                            <thead class="bg-foreground text-background uppercase tracking-widest">
                                <tr>
                                    <th class="text-left px-3 py-2 text-[10px] w-56 font-black">Department</th>
                                    <th class="text-left px-3 py-2 text-[10px] font-black">Description</th>
                                    <th class="text-right px-3 py-2 text-[10px] w-24 font-black">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orgStructures as $org): ?>
                                    <tr class="border-t border-foreground">
                                        <td class="px-3 py-3 align-top font-black uppercase"><?= esc($org['name']) ?></td>
                                        <td class="px-3 py-3 align-top text-foreground font-bold uppercase"><?= esc($org['description']) ?></td>
                                        <td class="px-3 py-3 align-top text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <!-- Edit department -->
                                                <button title="Edit" onclick="openEditOrgModal(this)" data-org="<?= htmlspecialchars(json_encode($org), ENT_QUOTES, 'UTF-8') ?>" class="brutal-border p-1.5 bg-background brutal-hover">
                                                    <i data-lucide="pencil" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                                                </button>
                                                <!-- Delete department -->
                                                <button title="Delete" onclick="openDeleteOrgModal(this)" data-org="<?= htmlspecialchars(json_encode($org), ENT_QUOTES, 'UTF-8') ?>" class="brutal-border p-1.5 bg-background brutal-hover hover:bg-destructive hover:text-destructive-foreground">
                                                    <i data-lucide="trash-2" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($orgStructures)): ?>
                                    <tr>
                                        <td colspan="3" class="px-3 py-4 text-center text-muted-foreground uppercase">No departments defined.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <!-- Squad Definitions Section -->
            <div id="section-squads" class="section-content hidden">
                <section class="mt-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-4 h-4 bg-primary brutal-border"></div>
                        <h2 class="mono text-xl font-black uppercase tracking-tight">Squad Definitions</h2>
                        <div class="flex-1 h-[2px] bg-border"></div>
                    </div>

                    <div class="brutal-border bg-card brutal-shadow overflow-hidden">
                        <table class="w-full mono text-xs">
                            <thead class="bg-foreground text-background uppercase tracking-widest">
                                <tr>
                                    <th class="text-left px-3 py-2 text-[10px] w-48 font-black">Squad</th>
                                    <th class="text-left px-3 py-2 text-[10px] w-40 font-black">Lead</th>
                                    <th class="text-left px-3 py-2 text-[10px] font-black">Mission</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($squads as $s): ?>
                                    <tr class="border-t border-foreground">
                                        <td class="px-3 py-3 font-black uppercase"><?= esc($s['name']) ?></td>
                                        <td class="px-3 py-3 uppercase"><?= esc($s['lead']) ?></td>
                                        <td class="px-3 py-3 text-foreground font-bold uppercase"><?= esc($s['mission']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<!-- ==================== CREATE STATUS DEFINITION MODAL ==================== -->
<div id="create-status-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-2xl w-full p-0 brutal-shadow">
        <form action="<?= base_url('settings/status/create') ?>" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">New Status Definition</h3>
                <button type="button" onclick="closeModal('create-status-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4 text-left">
                <div class="flex flex-col gap-1 col-span-2">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Label (e.g. ON TRACK)</span>
                    <input id="create-status-label" name="label" oninput="generateSlug('create-status-label', 'create-status-slug')" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="flex flex-col gap-1 col-span-2">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Status Slug (Auto-generated)</span>
                    <input id="create-status-slug" name="status" readonly required class="w-full border border-foreground bg-secondary px-2 py-1.5 mono text-xs focus:outline-none cursor-not-allowed opacity-75" />
                </div>
                <div class="flex flex-col gap-1 col-span-2">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Label Color</span>
                    <div class="flex items-center gap-2">
                        <input type="color" name="color" value="#6b7280" class="w-10 h-8 brutal-border bg-background cursor-pointer" />
                        <span class="mono text-xs text-muted-foreground">Pick a color for the label badge background</span>
                    </div>
                </div>
                <div class="flex flex-col gap-1 col-span-2">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Criteria / Definition</span>
                    <textarea name="criteria" required rows="3" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('create-status-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== EDIT STATUS DEFINITION MODAL ==================== -->
<div id="edit-status-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-2xl w-full p-0 brutal-shadow">
        <form id="edit-status-form" action="" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">Edit Status Definition</h3>
                <button type="button" onclick="closeModal('edit-status-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4 text-left font-mono text-xs">
                <div class="flex flex-col gap-1 col-span-2">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Label</span>
                    <input id="edit-status-label" name="label" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="flex flex-col gap-1 col-span-2">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Status Slug (Non-editable)</span>
                    <input id="edit-status-slug" name="status" readonly required class="w-full border border-foreground bg-secondary px-2 py-1.5 mono text-xs focus:outline-none cursor-not-allowed opacity-75" />
                </div>
                <div class="flex flex-col gap-1 col-span-2">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Label Color</span>
                    <div class="flex items-center gap-2">
                        <input id="edit-status-color" type="color" name="color" value="#6b7280" class="w-10 h-8 brutal-border bg-background cursor-pointer" />
                        <span class="mono text-xs text-muted-foreground">Pick a color for the label badge background</span>
                    </div>
                </div>
                <div class="flex flex-col gap-1 col-span-2">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Criteria</span>
                    <textarea id="edit-status-criteria" name="criteria" required rows="3" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('edit-status-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== DELETE STATUS DEFINITION MODAL ==================== -->
<div id="delete-status-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow">
        <form id="delete-status-form" action="" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">Delete Status?</h3>
                <button type="button" onclick="closeModal('delete-status-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 text-left">
                <p class="mono text-xs text-foreground">
                    Are you sure you want to delete status <span id="delete-status-name" class="font-black"></span>?<br />
                    This status option will no longer be available when adding or editing projects/phases.
                </p>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('delete-status-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-destructive text-destructive-foreground px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Delete
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== CREATE ORG STRUCTURE MODAL ==================== -->
<div id="create-org-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-2xl w-full p-0 brutal-shadow">
        <form action="<?= base_url('settings/org/create') ?>" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">New Department</h3>
                <button type="button" onclick="closeModal('create-org-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4 text-left font-mono text-xs">
                <div class="flex flex-col gap-1 col-span-2">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Department Name</span>
                    <input name="name" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="flex flex-col gap-1 col-span-2">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Description</span>
                    <textarea name="description" rows="3" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('create-org-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== EDIT ORG STRUCTURE MODAL ==================== -->
<div id="edit-org-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-2xl w-full p-0 brutal-shadow">
        <form id="edit-org-form" action="" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">Edit Department</h3>
                <button type="button" onclick="closeModal('edit-org-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4 text-left font-mono text-xs">
                <div class="flex flex-col gap-1 col-span-2">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Department Name</span>
                    <input id="edit-org-name" name="name" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="flex flex-col gap-1 col-span-2">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Description</span>
                    <textarea id="edit-org-description" name="description" rows="3" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('edit-org-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== DELETE ORG STRUCTURE MODAL ==================== -->
<div id="delete-org-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow">
        <form id="delete-org-form" action="" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">Delete Department?</h3>
                <button type="button" onclick="closeModal('delete-org-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 text-left">
                <p class="mono text-xs text-foreground">
                    Are you sure you want to delete department <span id="delete-org-display-name" class="font-black"></span>?<br />
                    This action will fail if the department is currently assigned to any active resources.
                </p>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('delete-org-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
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
    // Toggle collapsible settings section
    function toggleSection(name, force = false) {
        const sections = ['status', 'org', 'squads'];
        const currentlyActive = localStorage.getItem('activeSettingsSection');
        
        // If clicking the currently active section without force, we toggle it off (hide all)
        const targetSection = (currentlyActive === name && !force) ? '' : name;
        
        sections.forEach(s => {
            const sectionEl = document.getElementById(`section-${s}`);
            const tileEl = document.getElementById(`tile-${s}`);
            const badgeEl = document.getElementById(`badge-${s}`);
            
            if (s === targetSection) {
                // Open section
                sectionEl.classList.remove('hidden');
                sectionEl.classList.add('animate-fade-in');
                
                // Add active outline and offset styling
                tileEl.classList.add('ring-4', 'ring-foreground', 'scale-[0.98]', 'shadow-[2px_2px_0_0_var(--foreground)]');
                badgeEl.classList.remove('hidden');
            } else {
                // Close section
                sectionEl.classList.add('hidden');
                sectionEl.classList.remove('animate-fade-in');
                
                // Remove active styling
                tileEl.classList.remove('ring-4', 'ring-foreground', 'scale-[0.98]', 'shadow-[2px_2px_0_0_var(--foreground)]');
                badgeEl.classList.add('hidden');
            }
        });
        
        localStorage.setItem('activeSettingsSection', targetSection);
    }

    // Auto-open last active section on load (default to status)
    document.addEventListener('DOMContentLoaded', function() {
        const savedSection = localStorage.getItem('activeSettingsSection');
        if (savedSection && ['status', 'org', 'squads'].includes(savedSection)) {
            toggleSection(savedSection, true);
        } else {
            toggleSection('status', true);
        }
    });

    function openCreateModal() {
        document.getElementById('create-status-modal').classList.remove('hidden');
    }

    function openEditModal(button) {
        const d = JSON.parse(button.getAttribute('data-definition'));
        document.getElementById('edit-status-form').action = '<?= base_url('settings/status/update') ?>/' + d.id;
        document.getElementById('edit-status-slug').value = d.status;
        document.getElementById('edit-status-label').value = d.label;
        document.getElementById('edit-status-criteria').value = d.criteria;
        document.getElementById('edit-status-color').value = d.color || '#6b7280';
        document.getElementById('edit-status-modal').classList.remove('hidden');
    }

    function openDeleteModal(button) {
        const d = JSON.parse(button.getAttribute('data-definition'));
        document.getElementById('delete-status-form').action = '<?= base_url('settings/status/delete') ?>/' + d.id;
        document.getElementById('delete-status-name').innerText = d.label;
        document.getElementById('delete-status-modal').classList.remove('hidden');
    }

    function openCreateOrgModal() {
        document.getElementById('create-org-modal').classList.remove('hidden');
    }

    function openEditOrgModal(button) {
        const org = JSON.parse(button.getAttribute('data-org'));
        document.getElementById('edit-org-form').action = '<?= base_url('settings/org/update') ?>/' + org.id;
        document.getElementById('edit-org-name').value = org.name;
        document.getElementById('edit-org-description').value = org.description || '';
        document.getElementById('edit-org-modal').classList.remove('hidden');
    }

    function openDeleteOrgModal(button) {
        const org = JSON.parse(button.getAttribute('data-org'));
        document.getElementById('delete-org-form').action = '<?= base_url('settings/org/delete') ?>/' + org.id;
        document.getElementById('delete-org-display-name').innerText = org.name;
        document.getElementById('delete-org-modal').classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function generateSlug(sourceId, targetId) {
        const val = document.getElementById(sourceId).value;
        const slug = val.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .trim();
        document.getElementById(targetId).value = slug;
    }

    window.onclick = function(event) {
        const modals = ['create-status-modal', 'edit-status-modal', 'delete-status-modal', 'create-org-modal', 'edit-org-modal', 'delete-org-modal'];
        modals.forEach(id => {
            const modal = document.getElementById(id);
            if (event.target == modal) {
                modal.classList.add('hidden');
            }
        });
    }
</script>
<?= $this->endSection() ?>
