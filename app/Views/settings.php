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

<div class="min-h-screen pb-24">
    <!-- Header -->
    <header class="border-b border-ink/15 py-6 no-print mb-8 cascade-in" style="animation-delay: 0ms;">
        <div class="w-full px-8 lg:px-14 flex items-center justify-between gap-4">
            <div>
                <span class="eyebrow">PMO // Module</span>
                <h1 class="font-display text-2xl md:text-3xl font-black uppercase mt-1 tracking-tight">Settings</h1>
            </div>
        </div>
    </header>

    <div class="w-full px-8 lg:px-14 cascade-in" style="animation-delay: 50ms;">
        <!-- Collapsible Content Sections -->
        <style>
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(8px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .animate-fade-in {
                animation: fadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
        </style>

        <!-- Tab Navigation -->
        <div class="flex border-b border-ink/15 mb-8 overflow-x-auto no-scrollbar">
            <button id="tile-status" onclick="toggleSection('status')" class="px-6 py-4 text-xs font-mono uppercase tracking-widest border-b-2 border-transparent hover:text-ink text-muted-foreground font-bold transition-all focus:outline-none flex items-center gap-2 whitespace-nowrap">
                <i data-lucide="pencil-ruler" class="w-4 h-4"></i>
                Status Definitions
                <span class="text-[9px] rounded-full border border-ink/10 bg-secondary px-2 py-0.5 ml-1"><?= count($statusDefinitions) ?></span>
            </button>
            <button id="tile-org" onclick="toggleSection('org')" class="px-6 py-4 text-xs font-mono uppercase tracking-widest border-b-2 border-transparent hover:text-ink text-muted-foreground font-bold transition-all focus:outline-none flex items-center gap-2 whitespace-nowrap">
                <i data-lucide="network" class="w-4 h-4"></i>
                Org. Structure
                <span class="text-[9px] rounded-full border border-ink/10 bg-secondary px-2 py-0.5 ml-1"><?= count($orgStructures) ?></span>
            </button>
            <button id="tile-resources" onclick="toggleSection('resources')" class="px-6 py-4 text-xs font-mono uppercase tracking-widest border-b-2 border-transparent hover:text-ink text-muted-foreground font-bold transition-all focus:outline-none flex items-center gap-2 whitespace-nowrap">
                <i data-lucide="sliders" class="w-4 h-4"></i>
                Resource Management
            </button>
        </div>

        <div class="mt-4">
            <!-- Project Status Definitions Section -->
            <div id="section-status" class="section-content hidden">
                <section>
                    <div class="flex items-center justify-between gap-4 mb-6">
                        <div>
                            <h2 class="font-display text-xl tracking-tight text-ink font-bold">Project Status Definitions</h2>
                            <p class="text-xs text-muted-foreground mt-0.5">Define classifications, criteria, and colors for tracking statuses.</p>
                        </div>
                        <button onclick="openCreateModal()" class="rounded-full border border-ink/20 bg-ink text-paper hover:bg-ink/90 px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold flex items-center gap-1.5 shadow-sm">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            New Definition
                        </button>
                    </div>
                    
                    <div class="rounded-2xl border border-ink/15 bg-card/70 backdrop-blur overflow-hidden shadow-sm">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-ink/15 bg-background/50">
                                    <th class="px-4 py-3 text-[10px] font-mono uppercase tracking-wider text-muted-foreground font-semibold w-48">Status</th>
                                    <th class="px-4 py-3 text-[10px] font-mono uppercase tracking-wider text-muted-foreground font-semibold">Definition / Criteria</th>
                                    <th class="px-4 py-3 text-[10px] font-mono uppercase tracking-wider text-muted-foreground font-semibold text-right w-24">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statusDefinitions as $d): ?>
                                    <tr class="border-b border-ink/10 hover:bg-background/20 transition-colors">
                                        <td class="px-4 py-4.5 align-middle">
                                            <span class="inline-block mono text-[10px] font-bold uppercase tracking-wider rounded-full px-3 py-1 text-white shadow-sm" style="background-color: <?= esc($d['color'] ?? '#6B7280') ?>;">
                                                <?= esc($d['label']) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4.5 align-middle text-sm text-ink leading-relaxed uppercase font-semibold"><?= esc($d['criteria']) ?></td>
                                        <td class="px-4 py-4.5 align-middle text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button title="Edit" onclick="openEditModal(this)" data-definition="<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>" class="text-ink/60 hover:text-ink">
                                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                                </button>
                                                <button title="Delete" onclick="openDeleteModal(this)" data-definition="<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>" class="text-destructive hover:opacity-80">
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
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
                <section>
                    <div class="flex items-center justify-between gap-4 mb-6">
                        <div>
                            <h2 class="font-display text-xl tracking-tight text-ink font-bold">Organizational Structure</h2>
                            <p class="text-xs text-muted-foreground mt-0.5">Departments and business unit groupings in the organization.</p>
                        </div>
                        <button onclick="openCreateOrgModal()" class="rounded-full border border-ink/20 bg-ink text-paper hover:bg-ink/90 px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold flex items-center gap-1.5 shadow-sm">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            New Department
                        </button>
                    </div>
                    
                    <div class="rounded-2xl border border-ink/15 bg-card/70 backdrop-blur overflow-hidden shadow-sm">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-ink/15 bg-background/50">
                                    <th class="px-4 py-3 text-[10px] font-mono uppercase tracking-wider text-muted-foreground font-semibold w-64">Department</th>
                                    <th class="px-4 py-3 text-[10px] font-mono uppercase tracking-wider text-muted-foreground font-semibold">Description</th>
                                    <th class="px-4 py-3 text-[10px] font-mono uppercase tracking-wider text-muted-foreground font-semibold text-right w-24">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orgStructures as $org): ?>
                                    <tr class="border-b border-ink/10 hover:bg-background/20 transition-colors">
                                        <td class="px-4 py-4.5 align-middle text-sm font-bold uppercase text-ink"><?= esc($org['name']) ?></td>
                                        <td class="px-4 py-4.5 align-middle text-sm text-ink/85 leading-relaxed uppercase font-semibold"><?= esc($org['description']) ?></td>
                                        <td class="px-4 py-4.5 align-middle text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button title="Edit" onclick="openEditOrgModal(this)" data-org="<?= htmlspecialchars(json_encode($org), ENT_QUOTES, 'UTF-8') ?>" class="text-ink/60 hover:text-ink">
                                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                                </button>
                                                <button title="Delete" onclick="openDeleteOrgModal(this)" data-org="<?= htmlspecialchars(json_encode($org), ENT_QUOTES, 'UTF-8') ?>" class="text-destructive hover:opacity-80">
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($orgStructures)): ?>
                                    <tr>
                                        <td colspan="3" class="px-4 py-6 text-center text-xs text-muted-foreground uppercase tracking-widest font-mono">No departments defined.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <!-- Resource Management Section -->
            <div id="section-resources" class="section-content hidden">
                <section>
                    <div class="mb-6">
                        <h2 class="font-display text-xl tracking-tight text-ink font-bold">Resource Allocation Settings</h2>
                        <p class="text-xs text-muted-foreground mt-0.5">Parameters that configure team utilization and capacity charts.</p>
                    </div>

                    <div class="rounded-2xl border border-ink/15 bg-card/70 backdrop-blur p-6 shadow-sm max-w-xl">
                        <form action="<?= base_url('settings/resources/update') ?>" method="POST" class="space-y-4">
                            <div class="flex flex-col gap-1 font-mono">
                                <label for="daily_work_hours" class="text-[10px] uppercase text-muted-foreground">Daily Work Hours (Hours)</label>
                                <input type="number" id="daily_work_hours" name="daily_work_hours" value="<?= esc($resourceSettings['daily_work_hours'] ?? 8) ?>" required min="1" max="24" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" oninput="updateWorkWeekHours()" />
                            </div>
                            <div class="flex flex-col gap-1 font-mono">
                                <label for="work_days_per_week" class="text-[10px] uppercase text-muted-foreground">Work Days in a Week (Days)</label>
                                <input type="number" id="work_days_per_week" name="work_days_per_week" value="<?= esc($resourceSettings['work_days_per_week'] ?? 5) ?>" required min="1" max="7" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" oninput="updateWorkWeekHours()" />
                            </div>
                            <div class="p-3.5 bg-background/50 rounded-xl border border-ink/10 font-mono text-xs uppercase flex items-center justify-between shadow-inner">
                                <span class="text-muted-foreground">Calculated Work Week:</span>
                                <span class="font-bold text-sm text-ink" id="work_week_hours_label">0 Hours</span>
                            </div>
                            <div class="flex justify-end pt-2">
                                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">
                                    Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<!-- ==================== CREATE STATUS DEFINITION MODAL ==================== -->
<div id="create-status-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-xl w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">New Status Definition</h3>
            <button type="button" onclick="closeModal('create-status-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form action="<?= base_url('settings/status/create') ?>" method="POST" class="overflow-y-auto flex-1 p-6 space-y-4 text-left font-mono">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Label (e.g. ON TRACK)</span>
                <input id="create-status-label" name="label" oninput="generateSlug('create-status-label', 'create-status-slug')" required placeholder="ON TRACK" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Status Slug (Auto-generated)</span>
                <input id="create-status-slug" name="status" readonly required placeholder="on-track" class="rounded-xl border border-ink/20 bg-secondary px-3 py-2 text-sm focus:outline-none cursor-not-allowed opacity-75" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Label Color</span>
                <div class="flex items-center gap-3">
                    <input type="color" name="color" value="#6b7280" class="w-12 h-10 rounded-xl border border-ink/20 bg-background cursor-pointer p-1" />
                    <span class="mono text-xs text-muted-foreground">Choose a badge background color</span>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Criteria / Definition</span>
                <textarea name="criteria" required rows="3" placeholder="Define when this status should be used..." class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-status-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== EDIT STATUS DEFINITION MODAL ==================== -->
<div id="edit-status-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-xl w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Edit Status Definition</h3>
            <button type="button" onclick="closeModal('edit-status-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="edit-status-form" action="" method="POST" class="overflow-y-auto flex-1 p-6 space-y-4 text-left font-mono">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Label</span>
                <input id="edit-status-label" name="label" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Status Slug (Non-editable)</span>
                <input id="edit-status-slug" name="status" readonly required class="rounded-xl border border-ink/20 bg-secondary px-3 py-2 text-sm focus:outline-none cursor-not-allowed opacity-75" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Label Color</span>
                <div class="flex items-center gap-3">
                    <input id="edit-status-color" type="color" name="color" value="#6b7280" class="w-12 h-10 rounded-xl border border-ink/20 bg-background cursor-pointer p-1" />
                    <span class="mono text-xs text-muted-foreground">Choose a badge background color</span>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Criteria</span>
                <textarea id="edit-status-criteria" name="criteria" required rows="3" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-status-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== DELETE STATUS DEFINITION MODAL ==================== -->
<div id="delete-status-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <form id="delete-status-form" action="" method="POST" class="flex flex-col h-full">
            <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
                <h3 class="font-display text-sm font-bold uppercase tracking-wide">Delete Status?</h3>
                <button type="button" onclick="closeModal('delete-status-modal')" class="text-paper hover:opacity-75">✕</button>
            </div>
            <div class="p-6 text-left overflow-y-auto flex-1 font-mono text-xs">
                <p class="text-ink/80 leading-normal">
                    Are you sure you want to delete status <span id="delete-status-name" class="font-black text-ink"></span>?<br /><br />
                    This status option will no longer be available when adding or editing projects/phases.
                </p>
            </div>
            <div class="border-t border-ink/15 p-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('delete-status-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-destructive text-destructive-foreground px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== CREATE ORG STRUCTURE MODAL ==================== -->
<div id="create-org-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-xl w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">New Department</h3>
            <button type="button" onclick="closeModal('create-org-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form action="<?= base_url('settings/org/create') ?>" method="POST" class="overflow-y-auto flex-1 p-6 space-y-4 text-left font-mono">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Department Name</span>
                <input name="name" required placeholder="e.g. Technology, Finance" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Description</span>
                <textarea name="description" rows="3" placeholder="Provide a brief description of the department's role..." class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('create-org-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== EDIT ORG STRUCTURE MODAL ==================== -->
<div id="edit-org-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-xl w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Edit Department</h3>
            <button type="button" onclick="closeModal('edit-org-modal')" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="edit-org-form" action="" method="POST" class="overflow-y-auto flex-1 p-6 space-y-4 text-left font-mono">
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Department Name</span>
                <input id="edit-org-name" name="name" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none" />
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[10px] uppercase text-muted-foreground">Description</span>
                <textarea id="edit-org-description" name="description" rows="3" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none"></textarea>
            </div>
            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('edit-org-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== DELETE ORG STRUCTURE MODAL ==================== -->
<div id="delete-org-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <form id="delete-org-form" action="" method="POST" class="flex flex-col h-full">
            <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
                <h3 class="font-display text-sm font-bold uppercase tracking-wide">Delete Department?</h3>
                <button type="button" onclick="closeModal('delete-org-modal')" class="text-paper hover:opacity-75">✕</button>
            </div>
            <div class="p-6 text-left overflow-y-auto flex-1 font-mono text-xs">
                <p class="text-ink/80 leading-normal">
                    Are you sure you want to delete department <span id="delete-org-display-name" class="font-black text-ink"></span>?<br /><br />
                    This action will fail if the department is currently assigned to any active resources.
                </p>
            </div>
            <div class="border-t border-ink/15 p-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeModal('delete-org-modal')" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-destructive text-destructive-foreground px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Delete</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= base_url('js/settings.js') ?>"></script>
<?= $this->endSection() ?>
