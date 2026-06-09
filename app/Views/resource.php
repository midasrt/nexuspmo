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
?>

<div class="min-h-screen p-6 md:p-10">
    <div class="max-w-7xl mx-auto">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="mono text-[10px] uppercase tracking-[0.3em] text-muted-foreground">
                    PMO // Module
                </div>
                <h1 class="mono text-4xl font-black uppercase tracking-tight mt-2">Resource</h1>
                <p class="mt-2 text-sm text-muted-foreground max-w-prose">
                    Capacity, utilization, and assignments across employees and outsourced contributors.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="openCreateModal()" class="brutal-border bg-foreground text-background px-4 py-3 mono text-xs uppercase tracking-widest font-black brutal-shadow brutal-hover">
                    + New Resource
                </button>
                <a href="<?= base_url('resource-map') ?>" class="brutal-border bg-card text-foreground px-4 py-3 mono text-xs uppercase tracking-widest font-black brutal-shadow brutal-hover">
                    ◇ Resource Map
                </a>
            </div>
        </div>

        <!-- Stat Tiles -->
        <div class="grid grid-cols-4 gap-2 mt-6 max-w-xl">
            <div class="brutal-border bg-card p-2 brutal-shadow-sm">
                <div class="mono text-[9px] uppercase tracking-[0.2em] text-muted-foreground">Total</div>
                <div class="mono text-xl font-black mt-0.5"><?= $totalCount ?></div>
            </div>
            <div class="brutal-border bg-card p-2 brutal-shadow-sm">
                <div class="mono text-[9px] uppercase tracking-[0.2em] text-muted-foreground">Employees</div>
                <div class="mono text-xl font-black mt-0.5"><?= $empCount ?></div>
            </div>
            <div class="brutal-border bg-card p-2 brutal-shadow-sm">
                <div class="mono text-[9px] uppercase tracking-[0.2em] text-muted-foreground">Outsource</div>
                <div class="mono text-xl font-black mt-0.5"><?= $outCount ?></div>
            </div>
            <div class="brutal-border bg-card p-2 brutal-shadow-sm">
                <div class="mono text-[9px] uppercase tracking-[0.2em] text-muted-foreground">Avg Util</div>
                <div class="mono text-xl font-black mt-0.5"><?= $avgUtil ?>%</div>
            </div>
        </div>

        <!-- Filter Rows -->
        <div class="flex flex-wrap items-center justify-between gap-6 mt-8">
            <div class="flex flex-wrap gap-6 items-center">
                <!-- Role filter -->
                <div class="flex items-center gap-2">
                    <span class="mono text-[10px] uppercase tracking-[0.25em] text-muted-foreground">Role</span>
                    <div class="flex flex-wrap gap-1" id="role-filters-container">
                        <?php foreach (['ALL', 'FE', 'BE', 'QA', 'BA'] as $opt): ?>
                            <a href="?role=<?= $opt ?>&status=<?= $statusFilter ?>&search=<?= urlencode($search) ?>" data-role="<?= $opt ?>" class="filter-link mono text-[10px] uppercase tracking-widest border border-foreground px-2 py-1 <?= $roleFilter === $opt ? 'bg-foreground text-background' : 'bg-card hover:bg-secondary' ?>">
                                <?= $opt ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Status filter -->
                <div class="flex items-center gap-2">
                    <span class="mono text-[10px] uppercase tracking-[0.25em] text-muted-foreground">Status</span>
                    <div class="flex flex-wrap gap-1" id="status-filters-container">
                        <?php foreach (['ALL', 'employee', 'outsource'] as $opt): ?>
                            <a href="?role=<?= $roleFilter ?>&status=<?= $opt ?>&search=<?= urlencode($search) ?>" data-status="<?= $opt ?>" class="filter-link mono text-[10px] uppercase tracking-widest border border-foreground px-2 py-1 <?= $statusFilter === $opt ? 'bg-foreground text-background' : 'bg-card hover:bg-secondary' ?>">
                                <?= $opt ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Search Form -->
            <form id="search-form" method="GET" action="" class="flex items-center gap-2">
                <input type="hidden" name="role" id="active-role" value="<?= esc($roleFilter) ?>">
                <input type="hidden" name="status" id="active-status" value="<?= esc($statusFilter) ?>">
                <input type="text" id="search-input" name="search" value="<?= esc($search) ?>" placeholder="SEARCH RESOURCES..." autocomplete="off" class="border border-foreground bg-background px-3 py-1.5 mono text-xs uppercase focus:outline-none focus:ring-2 focus:ring-primary w-64 brutal-shadow-sm">
                <button type="submit" class="brutal-border bg-foreground text-background px-3 py-1.5 mono text-xs uppercase font-black brutal-hover">
                    Search
                </button>
                <?php if ($search !== ''): ?>
                    <a href="?role=<?= esc($roleFilter) ?>&status=<?= esc($statusFilter) ?>" id="clear-search-btn" class="brutal-border bg-card text-foreground px-3 py-1.5 mono text-xs uppercase brutal-hover">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Resources Table -->
        <div class="mt-6 brutal-border bg-card brutal-shadow overflow-x-auto">
            <table class="w-full mono text-xs">
                <thead class="bg-foreground text-background uppercase tracking-widest">
                    <tr>
                        <th class="text-left px-3 py-2 text-[10px] font-bold w-12">ID</th>
                        <th class="text-left px-3 py-2 text-[10px] font-bold">Name</th>
                        <th class="text-left px-3 py-2 text-[10px] font-bold">Department</th>
                        <th class="text-left px-3 py-2 text-[10px] font-bold w-20">Role</th>
                        <th class="text-left px-3 py-2 text-[10px] font-bold w-56">Utilization</th>
                        <th class="text-left px-3 py-2 text-[10px] font-bold">Projects</th>
                        <th class="text-left px-3 py-2 text-[10px] font-bold w-28">Status</th>
                        <th class="text-left px-3 py-2 text-[10px] font-bold w-24 text-right">Action</th>
                    </tr>
                </thead>
                <tbody id="resource-table-body">
                    <?= view('partials/resource_rows', ['resources' => $resources]) ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <div id="resource-pagination-container">
            <?= view('partials/resource_pagination', [
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'roleFilter' => $roleFilter,
                'statusFilter' => $statusFilter,
                'search' => $search,
                'totalFiltered' => $totalFiltered,
                'offset' => $offset,
                'perPage' => $perPage
            ]) ?>
        </div>
    </div>
</div>

<!-- ==================== RESOURCE DETAIL MODAL ==================== -->
<div id="resource-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-2xl w-full p-0 brutal-shadow">
        <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
            <div>
                <div id="modal-sub" class="mono text-[10px] uppercase tracking-[0.3em] opacity-70"></div>
                <h3 id="modal-title" class="mono text-2xl font-black uppercase tracking-tight"></h3>
            </div>
            <button onclick="closeModal('resource-modal')" class="text-background font-black hover:opacity-75">✕</button>
        </div>
        <div class="p-6 space-y-6 mono text-xs">
            <div class="grid grid-cols-2 gap-4">
                <div class="border border-foreground bg-background p-3">
                    <div class="text-[9px] uppercase tracking-[0.25em] text-muted-foreground">Department</div>
                    <div id="modal-dept" class="text-sm font-bold mt-1 uppercase"></div>
                </div>
                <div class="border border-foreground bg-background p-3">
                    <div class="text-[9px] uppercase tracking-[0.25em] text-muted-foreground">Role</div>
                    <div id="modal-role" class="text-sm font-bold mt-1 uppercase"></div>
                </div>
                <div class="border border-foreground bg-background p-3">
                    <div class="text-[9px] uppercase tracking-[0.25em] text-muted-foreground">Status</div>
                    <div id="modal-status" class="text-sm font-bold mt-1 uppercase"></div>
                </div>
                <div class="border border-foreground bg-background p-3">
                    <div class="text-[9px] uppercase tracking-[0.25em] text-muted-foreground">Utilization</div>
                    <div id="modal-util" class="text-sm font-bold mt-1 uppercase"></div>
                </div>
                <div class="border border-foreground bg-background p-3 col-span-2">
                    <div class="text-[9px] uppercase tracking-[0.25em] text-muted-foreground">Email</div>
                    <div id="modal-email" class="text-sm font-bold mt-1 break-words"></div>
                </div>
                <div class="border border-foreground bg-background p-3 col-span-2">
                    <div class="text-[9px] uppercase tracking-[0.25em] text-muted-foreground">Manager</div>
                    <div id="modal-manager" class="text-sm font-bold mt-1 uppercase"></div>
                </div>
            </div>

            <div>
                <div class="text-[10px] uppercase tracking-[0.25em] text-muted-foreground mb-2">Current Projects</div>
                <ul id="modal-projects" class="space-y-1"></ul>
            </div>

            <div>
                <div class="text-[10px] uppercase tracking-[0.25em] text-muted-foreground mb-2">Skills</div>
                <div id="modal-skills" class="flex flex-wrap gap-2"></div>
            </div>
        </div>
        <div class="border-t border-foreground p-4 flex justify-end">
            <button onclick="closeModal('resource-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                Close
            </button>
        </div>
    </div>
</div>

<!-- ==================== CREATE RESOURCE MODAL ==================== -->
<div id="create-resource-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-2xl w-full p-0 brutal-shadow">
        <form action="<?= base_url('resource/create') ?>" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">New Resource</h3>
                <button type="button" onclick="closeModal('create-resource-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4 text-left">
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Name</span>
                    <input name="name" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Department</span>
                    <select name="department" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= esc($dept['name']) ?>"><?= esc($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Role</span>
                    <select name="role" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="FE">FE</option>
                        <option value="BE">BE</option>
                        <option value="QA">QA</option>
                        <option value="BA">BA</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Status</span>
                    <select name="status" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="employee">employee</option>
                        <option value="outsource">outsource</option>
                    </select>
                </div>
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Email</span>
                    <input type="email" name="email" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Location</span>
                    <input name="location" value="Onshore" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Manager</span>
                    <input name="manager" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Skills (Comma-separated)</span>
                    <input name="skills" placeholder="React, Node.js, SQL" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('create-resource-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== EDIT RESOURCE MODAL ==================== -->
<div id="edit-resource-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-2xl w-full p-0 brutal-shadow">
        <form id="edit-resource-form" action="" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">Edit Resource</h3>
                <button type="button" onclick="closeModal('edit-resource-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4 text-left font-mono">
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Name</span>
                    <input id="edit-res-name" name="name" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Department</span>
                    <select id="edit-res-dept" name="department" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= esc($dept['name']) ?>"><?= esc($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Role</span>
                    <select id="edit-res-role" name="role" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="FE">FE</option>
                        <option value="BE">BE</option>
                        <option value="QA">QA</option>
                        <option value="BA">BA</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Status</span>
                    <select id="edit-res-status" name="status" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="employee">employee</option>
                        <option value="outsource">outsource</option>
                    </select>
                </div>
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Email</span>
                    <input type="email" id="edit-res-email" name="email" required class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Location</span>
                    <input id="edit-res-location" name="location" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Manager</span>
                    <input id="edit-res-manager" name="manager" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div class="col-span-2 flex flex-col gap-1">
                    <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Skills (Comma-separated)</span>
                    <input id="edit-res-skills" name="skills" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('edit-resource-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
                    Cancel
                </button>
                <button type="submit" class="brutal-border bg-foreground text-background px-4 py-2 mono text-xs uppercase tracking-widest font-black brutal-hover">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== DELETE RESOURCE MODAL ==================== -->
<div id="delete-resource-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="bg-card brutal-border-thick max-w-md w-full p-0 brutal-shadow">
        <form id="delete-resource-form" action="" method="POST">
            <div class="border-b border-foreground bg-foreground text-background px-6 py-4 flex items-center justify-between">
                <h3 class="mono font-black uppercase tracking-tight">Delete Resource?</h3>
                <button type="button" onclick="closeModal('delete-resource-modal')" class="text-background font-black hover:opacity-75">✕</button>
            </div>
            <div class="p-6 text-left">
                <p class="mono text-xs text-foreground">
                    Are you sure you want to delete <span id="delete-res-name" class="font-black"></span>?<br />
                    This action is permanent and will remove their allocations from projects and squads.
                </p>
            </div>
            <div class="border-t border-foreground p-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('delete-resource-modal')" class="brutal-border bg-card px-4 py-2 mono text-xs uppercase tracking-widest brutal-hover">
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
    function openDetailModal(button) {
        const r = JSON.parse(button.getAttribute('data-resource'));
        
        document.getElementById('modal-sub').innerText = 'Resource // ' + r.id;
        document.getElementById('modal-title').innerText = r.name;
        document.getElementById('modal-dept').innerText = r.department;
        document.getElementById('modal-role').innerText = r.role;
        document.getElementById('modal-status').innerText = r.status;
        document.getElementById('modal-util').innerText = r.utilization;
        document.getElementById('modal-email').innerText = r.email;
        document.getElementById('modal-manager').innerText = r.manager;

        // Render projects
        const projList = document.getElementById('modal-projects');
        projList.innerHTML = '';
        if (!r.projects || r.projects.length === 0) {
            projList.innerHTML = '<li class="mono text-xs border border-foreground px-3 py-2 bg-background text-muted-foreground uppercase">No current projects</li>';
        } else {
            r.projects.forEach(p => {
                projList.innerHTML += '<li class="mono text-xs border border-foreground px-3 py-2 bg-background font-bold uppercase">' + p + '</li>';
            });
        }

        // Render skills
        const skillsDiv = document.getElementById('modal-skills');
        skillsDiv.innerHTML = '';
        if (!r.skills || r.skills.length === 0) {
            skillsDiv.innerHTML = '<span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">No skills logged</span>';
        } else {
            r.skills.forEach(s => {
                if (s.trim()) {
                    skillsDiv.innerHTML += '<span class="mono text-[10px] uppercase tracking-widest border border-foreground bg-secondary px-2 py-1">' + s.trim() + '</span>';
                }
            });
        }

        document.getElementById('resource-modal').classList.remove('hidden');
    }

    function openCreateModal() {
        document.getElementById('create-resource-modal').classList.remove('hidden');
    }

    function openEditModal(button) {
        const r = JSON.parse(button.getAttribute('data-resource'));
        
        // Find DB ID from raw ID column in object (r.id is padded string e.g. "003", so convert back to integer)
        const intId = parseInt(r.id, 10);
        
        document.getElementById('edit-resource-form').action = '<?= base_url('resource/update') ?>/' + intId;
        document.getElementById('edit-res-name').value = r.name;
        document.getElementById('edit-res-dept').value = r.department;
        document.getElementById('edit-res-role').value = r.role;
        document.getElementById('edit-res-status').value = r.status.toLowerCase();
        
        document.getElementById('edit-res-email').value = r.email;
        document.getElementById('edit-res-location').value = r.location || 'Local';
        
        document.getElementById('edit-res-manager').value = r.manager || '';
        document.getElementById('edit-res-skills').value = r.skills ? r.skills.join(', ') : '';

        document.getElementById('edit-resource-modal').classList.remove('hidden');
    }

    function openDeleteModal(button) {
        const r = JSON.parse(button.getAttribute('data-resource'));
        const intId = parseInt(r.id, 10);
        
        document.getElementById('delete-resource-form').action = '<?= base_url('resource/delete') ?>/' + intId;
        document.getElementById('delete-res-name').innerText = r.name;
        
        document.getElementById('delete-resource-modal').classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    window.onclick = function(event) {
        const modals = ['resource-modal', 'create-resource-modal', 'edit-resource-modal', 'delete-resource-modal'];
        modals.forEach(id => {
            const modal = document.getElementById(id);
            if (event.target == modal) {
                modal.classList.add('hidden');
            }
        });
    }

    // ==================== AJAX DATATABLE SEARCH & PAGINATION ====================
    let searchTimeout = null;

    function fetchResources(page = 1) {
        const searchInput = document.getElementById('search-input');
        const searchVal = searchInput ? searchInput.value : '';
        const role = document.getElementById('active-role').value;
        const status = document.getElementById('active-status').value;
        
        // Build URL
        const url = new URL(window.location.href);
        url.searchParams.set('role', role);
        url.searchParams.set('status', status);
        url.searchParams.set('search', searchVal);
        url.searchParams.set('page', page);
        
        // Push state to browser
        window.history.pushState({}, '', url.toString());

        // Fetch via AJAX
        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('resource-table-body').innerHTML = data.html;
            document.getElementById('resource-pagination-container').innerHTML = data.pagination;
            
            // Re-bind Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Update active filter styling
            document.querySelectorAll('#role-filters-container a').forEach(a => {
                if (a.getAttribute('data-role') === role) {
                    a.className = 'filter-link mono text-[10px] uppercase tracking-widest border border-foreground px-2 py-1 bg-foreground text-background';
                } else {
                    a.className = 'filter-link mono text-[10px] uppercase tracking-widest border border-foreground px-2 py-1 bg-card hover:bg-secondary';
                }
            });

            document.querySelectorAll('#status-filters-container a').forEach(a => {
                if (a.getAttribute('data-status') === status) {
                    a.className = 'filter-link mono text-[10px] uppercase tracking-widest border border-foreground px-2 py-1 bg-foreground text-background';
                } else {
                    a.className = 'filter-link mono text-[10px] uppercase tracking-widest border border-foreground px-2 py-1 bg-card hover:bg-secondary';
                }
            });

            // Update Clear Search Button visibility if needed
            const clearBtn = document.getElementById('clear-search-btn');
            if (clearBtn) {
                if (searchVal === '') {
                    clearBtn.style.display = 'none';
                } else {
                    clearBtn.style.display = 'inline-block';
                    clearBtn.href = `?role=${role}&status=${status}`;
                }
            } else if (searchVal !== '') {
                // If it wasn't rendered originally, we could reload/recreate, but it's handled or we can append it
                const form = document.getElementById('search-form');
                if (form) {
                    const cleanLink = document.createElement('a');
                    cleanLink.id = 'clear-search-btn';
                    cleanLink.href = `?role=${role}&status=${status}`;
                    cleanLink.className = 'brutal-border bg-card text-foreground px-3 py-1.5 mono text-xs uppercase brutal-hover';
                    cleanLink.innerText = 'Clear';
                    form.appendChild(cleanLink);
                }
            }
        })
        .catch(err => console.error('Error fetching resources:', err));
    }

    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchResources(1); // Reset to page 1 on new search
                }, 200); // 200ms debounce for instant result feel
            });
        }

        const searchForm = document.getElementById('search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                fetchResources(1);
            });
        }

        // Intercept Filter Clicks
        document.addEventListener('click', (e) => {
            const filterLink = e.target.closest('.filter-link');
            if (filterLink) {
                e.preventDefault();
                const roleVal = filterLink.getAttribute('data-role');
                const statusVal = filterLink.getAttribute('data-status');
                
                if (roleVal !== null) {
                    document.getElementById('active-role').value = roleVal;
                }
                if (statusVal !== null) {
                    document.getElementById('active-status').value = statusVal;
                }
                
                fetchResources(1); // Reset to page 1 on filter change
            }

            // Intercept Pagination Clicks
            const paginationLink = e.target.closest('.pagination-link');
            if (paginationLink) {
                e.preventDefault();
                const page = parseInt(paginationLink.getAttribute('data-page'), 10) || 1;
                fetchResources(page);
            }
        });
    });
</script>
<?= $this->endSection() ?>
