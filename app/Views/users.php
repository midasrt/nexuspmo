<?= $this->extend('layout') ?>

<?= $this->section('content') ?>

<div class="min-h-screen pb-24">
    <!-- Header -->
    <header class="border-b border-ink/15 py-6 no-print mb-8 cascade-in" style="animation-delay: 0ms;">
        <div class="w-full px-8 lg:px-14 flex items-center justify-between gap-4">
            <div>
                <span class="eyebrow">PMO // Admin Module</span>
                <h1 class="font-display text-2xl md:text-3xl font-black uppercase mt-1 tracking-tight">User Management</h1>
            </div>
            <div>
                <button onclick="openCreateModal()" class="rounded-full border border-ink/20 bg-ink text-paper hover:bg-ink/90 px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    New User
                </button>
            </div>
        </div>
    </header>

    <div class="w-full px-8 lg:px-14 cascade-in" style="animation-delay: 50ms;">
        <!-- Users Table -->
        <div class="rounded-2xl border border-ink/15 bg-card/70 backdrop-blur overflow-hidden shadow-sm">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-ink/15 bg-background/50">
                        <th class="px-4 py-3 text-[10px] font-mono uppercase tracking-wider text-muted-foreground font-semibold">Name</th>
                        <th class="px-4 py-3 text-[10px] font-mono uppercase tracking-wider text-muted-foreground font-semibold">Email</th>
                        <th class="px-4 py-3 text-[10px] font-mono uppercase tracking-wider text-muted-foreground font-semibold">Role</th>
                        <th class="px-4 py-3 text-[10px] font-mono uppercase tracking-wider text-muted-foreground font-semibold text-center">Created At</th>
                        <th class="px-4 py-3 text-[10px] font-mono uppercase tracking-wider text-muted-foreground font-semibold text-right w-28">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-xs text-muted-foreground uppercase tracking-widest font-mono">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr class="border-b border-ink/10 hover:bg-background/20 transition-colors">
                                <td class="px-4 py-4.5 align-middle text-sm font-bold uppercase text-ink"><?= esc($u['name']) ?></td>
                                <td class="px-4 py-4.5 align-middle text-sm text-ink/80"><?= esc($u['email']) ?></td>
                                <td class="px-4 py-4.5 align-middle">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $u['role'] === 'admin' ? 'bg-status-ontrack text-white shadow-sm' : 'bg-secondary text-ink border border-ink/10' ?>">
                                        <?= esc($u['role']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4.5 align-middle text-center text-xs font-mono text-muted-foreground">
                                    <?= date('d-m-Y H:i', strtotime($u['created_at'])) ?>
                                </td>
                                <td class="px-4 py-4.5 align-middle text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <button 
                                            onclick='openEditModal(<?= json_encode($u) ?>)'
                                            class="text-ink/60 hover:text-ink"
                                            title="Edit"
                                        >
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </button>
                                        <?php if (session()->get('user_id') != $u['id']): ?>
                                            <form action="<?= base_url('users/delete/' . $u['id']) ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');" class="inline m-0">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="text-destructive hover:opacity-80 flex items-center" title="Delete">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-[10px] font-mono text-muted-foreground uppercase tracking-widest select-none">(You)</span>
                                        <?php endif; ?>
                                    </div>
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
<div id="create-modal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Create New User</h3>
            <button onclick="closeCreateModal()" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form action="<?= base_url('users/create') ?>" method="POST" class="overflow-y-auto flex-1 p-6 space-y-4 text-left font-mono">
            <?= csrf_field() ?>
            <div class="flex flex-col gap-1">
                <label for="create_name" class="text-[10px] uppercase text-muted-foreground">Name</label>
                <input type="text" name="name" id="create_name" required placeholder="User's Name" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none">
            </div>

            <div class="flex flex-col gap-1">
                <label for="create_email" class="text-[10px] uppercase text-muted-foreground">Email Address</label>
                <input type="email" name="email" id="create_email" required placeholder="email@gtech.com" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none">
            </div>

            <div class="flex flex-col gap-1">
                <label for="create_password" class="text-[10px] uppercase text-muted-foreground">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="create_password" required placeholder="Password" class="w-full rounded-xl border border-ink/20 bg-background px-3 py-2 pr-10 text-sm focus:outline-none">
                    <button type="button" onclick="togglePassword('create_password', this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-ink transition-colors" title="Toggle password visibility">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label for="create_role" class="text-[10px] uppercase text-muted-foreground">Role</label>
                <select name="role" id="create_role" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <option value="viewer">Viewer (Read-Only)</option>
                    <option value="manager">Manager (Full CRUD, No Settings)</option>
                    <option value="admin">Admin (Full Control)</option>
                </select>
            </div>

            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeCreateModal()" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-card rounded-2xl border border-ink/15 max-w-md w-full p-0 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="border-b border-ink/15 bg-ink text-paper px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide">Edit User</h3>
            <button onclick="closeEditModal()" class="text-paper hover:opacity-75">✕</button>
        </div>
        <form id="edit-form" action="" method="POST" class="overflow-y-auto flex-1 p-6 space-y-4 text-left font-mono">
            <?= csrf_field() ?>
            <div class="flex flex-col gap-1">
                <label for="edit_name" class="text-[10px] uppercase text-muted-foreground">Name</label>
                <input type="text" name="name" id="edit_name" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none">
            </div>

            <div class="flex flex-col gap-1">
                <label for="edit_email" class="text-[10px] uppercase text-muted-foreground">Email Address</label>
                <input type="email" name="email" id="edit_email" required class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none">
            </div>

            <div class="flex flex-col gap-1">
                <label for="edit_password" class="text-[10px] uppercase text-muted-foreground">New Password (Optional)</label>
                <div class="relative">
                    <input type="password" name="password" id="edit_password" placeholder="Leave blank to keep current" class="w-full rounded-xl border border-ink/20 bg-background px-3 py-2 pr-10 text-sm focus:outline-none">
                    <button type="button" onclick="togglePassword('edit_password', this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-ink transition-colors" title="Toggle password visibility">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label for="edit_role" class="text-[10px] uppercase text-muted-foreground">Role</label>
                <select name="role" id="edit_role" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-sm focus:outline-none uppercase">
                    <option value="viewer">Viewer (Read-Only)</option>
                    <option value="manager">Manager (Full CRUD, No Settings)</option>
                    <option value="admin">Admin (Full Control)</option>
                </select>
            </div>

            <div class="border-t border-ink/15 pt-4 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="closeEditModal()" class="rounded-full border border-ink/20 bg-background hover:bg-secondary px-4 py-2 text-xs font-mono uppercase tracking-widest">Cancel</button>
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= base_url('js/users.js') ?>"></script>

<?= $this->endSection() ?>
