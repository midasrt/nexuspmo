<?= $this->extend('layout') ?>

<?= $this->section('content') ?>

<div class="min-h-screen pb-24">
    <!-- Header -->
    <header class="border-b border-ink/15 py-6 no-print mb-8 cascade-in" style="animation-delay: 0ms;">
        <div class="w-full px-8 lg:px-14 flex items-center justify-between gap-4 flex-wrap">
            <div>
                <span class="eyebrow">PMO // Audit</span>
                <h1 class="font-display text-2xl md:text-3xl font-black uppercase mt-1 tracking-tight">Activity Log</h1>
            </div>
            <div class="mono text-xs text-muted-foreground uppercase tracking-widest">
                <?= $logs ? count($logs) . ' entries shown' : '0 entries' ?>
            </div>
        </div>
    </header>

    <div class="w-full px-8 lg:px-14 space-y-6">

        <!-- Filters -->
        <form method="GET" action="<?= base_url('activity-log') ?>" class="cascade-in rounded-2xl border border-ink/15 bg-card/70 backdrop-blur p-5 shadow-sm flex flex-wrap gap-4 items-end" style="animation-delay: 50ms;">
            <!-- Module -->
            <div class="flex flex-col gap-1">
                <label class="text-[10px] mono uppercase tracking-widest text-muted-foreground">Module</label>
                <select name="module" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-xs font-mono uppercase focus:outline-none min-w-[130px]">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= esc($m) ?>" <?= $filterModule === $m ? 'selected' : '' ?>><?= strtoupper(esc($m)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Action -->
            <div class="flex flex-col gap-1">
                <label class="text-[10px] mono uppercase tracking-widest text-muted-foreground">Action</label>
                <select name="action" class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-xs font-mono uppercase focus:outline-none min-w-[120px]">
                    <option value="">All Actions</option>
                    <option value="create" <?= strtolower($filterAction) === 'create' ? 'selected' : '' ?>>Create</option>
                    <option value="update" <?= strtolower($filterAction) === 'update' ? 'selected' : '' ?>>Update</option>
                    <option value="delete" <?= strtolower($filterAction) === 'delete' ? 'selected' : '' ?>>Delete</option>
                </select>
            </div>
            <!-- User search -->
            <div class="flex flex-col gap-1">
                <label class="text-[10px] mono uppercase tracking-widest text-muted-foreground">User</label>
                <input type="text" name="user" value="<?= esc($filterUser) ?>" placeholder="Search user..." class="rounded-xl border border-ink/20 bg-background px-3 py-2 text-xs font-mono focus:outline-none min-w-[150px]">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-full bg-ink text-paper px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold hover:bg-ink/90 flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i> Filter
                </button>
                <?php if ($filterModule || $filterAction || $filterUser): ?>
                    <a href="<?= base_url('activity-log') ?>" class="rounded-full border border-ink/20 bg-background px-4 py-2 text-xs font-mono uppercase tracking-widest hover:bg-secondary flex items-center gap-1.5">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Log Table -->
        <div class="cascade-in rounded-2xl border border-ink/15 bg-card/70 backdrop-blur overflow-hidden shadow-sm" style="animation-delay: 100ms;">
            <?php if (empty($logs)): ?>
                <div class="px-6 py-12 text-center text-xs text-muted-foreground uppercase tracking-widest font-mono">
                    No activity log entries found.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-ink/15 bg-background/50">
                                <th class="px-4 py-3 font-mono uppercase tracking-wider text-muted-foreground font-semibold whitespace-nowrap">Timestamp</th>
                                <th class="px-4 py-3 font-mono uppercase tracking-wider text-muted-foreground font-semibold">User</th>
                                <th class="px-4 py-3 font-mono uppercase tracking-wider text-muted-foreground font-semibold">Role</th>
                                <th class="px-4 py-3 font-mono uppercase tracking-wider text-muted-foreground font-semibold">Action</th>
                                <th class="px-4 py-3 font-mono uppercase tracking-wider text-muted-foreground font-semibold">Module</th>
                                <th class="px-4 py-3 font-mono uppercase tracking-wider text-muted-foreground font-semibold">Target</th>
                                <th class="px-4 py-3 font-mono uppercase tracking-wider text-muted-foreground font-semibold">Description</th>
                                <th class="px-4 py-3 font-mono uppercase tracking-wider text-muted-foreground font-semibold">IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <?php
                                $actionColors = [
                                    'CREATE' => 'bg-status-ontrack/20 text-status-ontrack border border-status-ontrack/30',
                                    'UPDATE' => 'bg-status-atrisk/20 text-status-atrisk border border-status-atrisk/30',
                                    'DELETE' => 'bg-status-blocked/20 text-status-blocked border border-status-blocked/30',
                                ];
                                $badgeClass = $actionColors[$log['action']] ?? 'bg-secondary text-ink border border-ink/10';
                                ?>
                                <tr class="border-b border-ink/10 hover:bg-background/20 transition-colors">
                                    <td class="px-4 py-3 font-mono text-muted-foreground whitespace-nowrap">
                                        <?= date('d-m-Y H:i:s', strtotime($log['created_at'])) ?>
                                    </td>
                                    <td class="px-4 py-3 font-bold uppercase text-ink">
                                        <?= esc($log['user_name']) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-secondary text-ink border border-ink/10">
                                            <?= esc($log['user_role']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $badgeClass ?>">
                                            <?= esc($log['action']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono uppercase text-ink/70">
                                        <?= esc($log['module']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-ink/80 max-w-[140px] truncate">
                                        <?= esc($log['target_name'] ?? ($log['target_id'] ? '#' . $log['target_id'] : '—')) ?>
                                    </td>
                                    <td class="px-4 py-3 text-ink/60 max-w-[260px]">
                                        <span title="<?= esc($log['description']) ?>" class="block truncate">
                                            <?= esc($log['description'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-muted-foreground whitespace-nowrap">
                                        <?= esc($log['ip_address'] ?? '—') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($pager): ?>
                    <div class="px-5 py-4 border-t border-ink/10 flex justify-center">
                        <?= $pager->links('default', 'pager') ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<?= $this->endSection() ?>
