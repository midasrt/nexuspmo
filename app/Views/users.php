<?= $this->extend('layout') ?>

<?= $this->section('content') ?>

<div class="min-h-screen p-6 md:p-10">
    <div class="max-w-7xl mx-auto">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="mono text-[10px] uppercase tracking-[0.3em] text-muted-foreground">
                    PMO // Admin Module
                </div>
                <h1 class="mono text-4xl font-black uppercase tracking-tight mt-2">User Management</h1>
                <p class="mt-2 text-sm text-muted-foreground max-w-prose">
                    CRUD system to manage user accounts, define roles (Admin vs. Viewer), and control workspace mutation rights.
                </p>
            </div>
            <div>
                <button onclick="openCreateModal()" class="brutal-border bg-foreground text-background px-4 py-3 mono text-xs uppercase tracking-widest font-black brutal-shadow brutal-hover">
                    + New User
                </button>
            </div>
        </div>

        <!-- Users Table -->
        <div class="mt-8 brutal-border bg-card brutal-shadow overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-foreground bg-secondary/50 mono text-xs uppercase tracking-widest font-black">
                        <th class="p-4 border-r border-foreground">Name</th>
                        <th class="p-4 border-r border-foreground">Email</th>
                        <th class="p-4 border-r border-foreground">Role</th>
                        <th class="p-4 border-r border-foreground text-center">Created At</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="mono text-xs">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="p-8 text-center text-muted-foreground uppercase tracking-widest">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr class="border-b border-foreground hover:bg-muted/30">
                                <td class="p-4 border-r border-foreground font-bold"><?= esc($u['name']) ?></td>
                                <td class="p-4 border-r border-foreground"><?= esc($u['email']) ?></td>
                                <td class="p-4 border-r border-foreground">
                                    <span class="inline-block px-2 py-1 border border-foreground font-black uppercase tracking-widest <?= $u['role'] === 'admin' ? 'bg-status-ontrack text-foreground' : 'bg-secondary text-foreground' ?>">
                                        <?= esc($u['role']) ?>
                                    </span>
                                </td>
                                <td class="p-4 border-r border-foreground text-center">
                                    <?= date('d-m-Y H:i', strtotime($u['created_at'])) ?>
                                </td>
                                <td class="p-4 text-right flex justify-end gap-2">
                                    <button 
                                        onclick='openEditModal(<?= json_encode($u) ?>)'
                                        class="brutal-border bg-card hover:bg-secondary px-2.5 py-1.5 mono text-[10px] uppercase font-black tracking-widest brutal-hover"
                                    >
                                        Edit
                                    </button>
                                    <?php if (session()->get('user_id') != $u['id']): ?>
                                        <form action="<?= base_url('users/delete/' . $u['id']) ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="brutal-border bg-destructive text-destructive-foreground hover:opacity-95 px-2.5 py-1.5 mono text-[10px] uppercase font-black tracking-widest brutal-hover">
                                                Delete
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="mono text-[10px] text-muted-foreground uppercase tracking-widest py-1.5 px-2.5 select-none">(You)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="create-modal" class="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="bg-card brutal-border brutal-shadow w-full max-w-md p-6 flex flex-col gap-4">
        <div class="flex items-center justify-between border-b border-foreground pb-2">
            <h3 class="mono text-sm font-black uppercase">Create New User</h3>
            <button onclick="closeCreateModal()" class="font-black hover:opacity-75">✕</button>
        </div>
        <form action="<?= base_url('users/create') ?>" method="POST" class="flex flex-col gap-4">
            <?= csrf_field() ?>
            <div class="flex flex-col gap-1.5">
                <label for="create_name" class="mono text-[10px] uppercase tracking-widest font-black text-foreground">Name</label>
                <input type="text" name="name" id="create_name" required class="w-full bg-background border border-foreground px-3 py-2 mono text-xs brutal-border">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="create_email" class="mono text-[10px] uppercase tracking-widest font-black text-foreground">Email Address</label>
                <input type="email" name="email" id="create_email" required class="w-full bg-background border border-foreground px-3 py-2 mono text-xs brutal-border">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="create_password" class="mono text-[10px] uppercase tracking-widest font-black text-foreground">Password</label>
                <input type="password" name="password" id="create_password" required class="w-full bg-background border border-foreground px-3 py-2 mono text-xs brutal-border">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="create_role" class="mono text-[10px] uppercase tracking-widest font-black text-foreground">Role</label>
                <select name="role" id="create_role" class="w-full bg-background border border-foreground px-3 py-2 mono text-xs brutal-border">
                    <option value="viewer">Viewer (Read-Only)</option>
                    <option value="admin">Admin (Full Control)</option>
                </select>
            </div>

            <button type="submit" class="brutal-border bg-foreground text-background py-2.5 mono text-xs uppercase tracking-widest font-black brutal-hover mt-2">
                Create User
            </button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="bg-card brutal-border brutal-shadow w-full max-w-md p-6 flex flex-col gap-4">
        <div class="flex items-center justify-between border-b border-foreground pb-2">
            <h3 class="mono text-sm font-black uppercase">Edit User</h3>
            <button onclick="closeEditModal()" class="font-black hover:opacity-75">✕</button>
        </div>
        <form id="edit-form" action="" method="POST" class="flex flex-col gap-4">
            <?= csrf_field() ?>
            <div class="flex flex-col gap-1.5">
                <label for="edit_name" class="mono text-[10px] uppercase tracking-widest font-black text-foreground">Name</label>
                <input type="text" name="name" id="edit_name" required class="w-full bg-background border border-foreground px-3 py-2 mono text-xs brutal-border">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="edit_email" class="mono text-[10px] uppercase tracking-widest font-black text-foreground">Email Address</label>
                <input type="email" name="email" id="edit_email" required class="w-full bg-background border border-foreground px-3 py-2 mono text-xs brutal-border">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="edit_password" class="mono text-[10px] uppercase tracking-widest font-black text-foreground">New Password (Optional)</label>
                <input type="password" name="password" id="edit_password" placeholder="Leave blank to keep current password" class="w-full bg-background border border-foreground px-3 py-2 mono text-xs brutal-border">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="edit_role" class="mono text-[10px] uppercase tracking-widest font-black text-foreground">Role</label>
                <select name="role" id="edit_role" class="w-full bg-background border border-foreground px-3 py-2 mono text-xs brutal-border">
                    <option value="viewer">Viewer (Read-Only)</option>
                    <option value="admin">Admin (Full Control)</option>
                </select>
            </div>

            <button type="submit" class="brutal-border bg-foreground text-background py-2.5 mono text-xs uppercase tracking-widest font-black brutal-hover mt-2">
                Save Changes
            </button>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('create-modal').classList.remove('hidden');
    }

    function closeCreateModal() {
        document.getElementById('create-modal').classList.add('hidden');
    }

    function openEditModal(user) {
        document.getElementById('edit_name').value = user.name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_password').value = '';
        
        document.getElementById('edit-form').action = '<?= base_url('users/update') ?>/' + user.id;
        document.getElementById('edit-modal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('edit-modal').classList.add('hidden');
    }
</script>

<?= $this->endSection() ?>
