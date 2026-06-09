<?php
$roleBgs = [
    'FE' => 'bg-status-ontrack',
    'BE' => 'bg-status-atrisk',
    'QA' => 'bg-status-delayed',
    'BA' => 'bg-status-backlog',
];

if (!function_exists('getUtilColor')) {
    function getUtilColor($u) {
        if ($u >= 85) return 'bg-status-blocked';
        if ($u >= 65) return 'bg-status-ontrack';
        if ($u >= 40) return 'bg-status-atrisk';
        return 'bg-status-backlog';
    }
}
?>

<?php foreach ($resources as $r): ?>
    <?php
    $projCount = count($r['currentProjects']);
    $split = $projCount ? round($r['utilization'] / $projCount) : 0;
    
    $jsData = htmlspecialchars(json_encode([
        'id' => str_pad($r['id'], 3, '0', STR_PAD_LEFT),
        'name' => $r['name'],
        'department' => $r['department'],
        'role' => $r['role'],
        'utilization' => $r['utilization'] . '%',
        'status' => strtoupper($r['status']),
        'email' => $r['email'],
        'manager' => $r['manager'],
        'skills' => $r['skills'],
        'projects' => array_column($r['currentProjects'], 'name')
    ]), ENT_QUOTES, 'UTF-8');
    ?>
    <tr class="border-t border-foreground hover:bg-secondary">
        <td class="px-3 py-2 align-middle text-muted-foreground"><?= str_pad($r['id'], 3, '0', STR_PAD_LEFT) ?></td>
        <td class="px-3 py-2 align-middle font-bold uppercase"><?= esc($r['name']) ?></td>
        <td class="px-3 py-2 align-middle uppercase"><?= esc($r['department']) ?></td>
        <td class="px-3 py-2 align-middle">
            <span class="inline-block px-2 py-0.5 border border-foreground text-foreground <?= $roleBgs[$r['role']] ?? 'bg-secondary' ?>">
                <?= esc($r['role']) ?>
            </span>
        </td>
        <td class="px-3 py-2 align-middle">
            <div class="flex items-center gap-2">
                <div class="flex-1 h-3 border border-foreground bg-background relative overflow-hidden">
                    <div class="h-full <?= getUtilColor($r['utilization']) ?>" style="width: <?= $r['utilization'] ?>%"></div>
                </div>
                <span class="w-10 text-right tabular-nums"><?= $r['utilization'] ?>%</span>
            </div>
        </td>
        <td class="px-3 py-2 align-middle">
            <div class="flex flex-wrap gap-1">
                <?php if (empty($r['currentProjects'])): ?>
                    <span class="text-muted-foreground">—</span>
                <?php else: ?>
                    <?php foreach ($r['currentProjects'] as $p): ?>
                        <span class="inline-flex items-center gap-1 border border-foreground bg-background px-1.5 py-0.5 text-[10px]">
                            <span class="font-black"><?= esc($p['code']) ?></span>
                            <span class="text-muted-foreground"><?= $split ?>%</span>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </td>
        <td class="px-3 py-2 align-middle">
            <span class="inline-block px-2 py-0.5 border border-foreground uppercase text-[10px] tracking-widest <?= $r['status'] === 'employee' ? 'bg-status-ontrack text-foreground' : 'bg-status-backlog text-background' ?>">
                <?= esc($r['status']) ?>
            </span>
        </td>
        <td class="px-3 py-2 align-middle text-right">
            <div class="flex items-center justify-end gap-1">
                <!-- View/Detail Button -->
                <button title="View Detail" onclick="openDetailModal(this)" data-resource="<?= $jsData ?>" class="brutal-border p-1.5 brutal-hover bg-card">
                    <i data-lucide="eye" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                </button>
                <!-- Edit Button -->
                <button title="Edit" onclick="openEditModal(this)" data-resource="<?= $jsData ?>" class="brutal-border p-1.5 brutal-hover bg-card">
                    <i data-lucide="pencil" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                </button>
                <!-- Delete Button -->
                <button title="Delete" onclick="openDeleteModal(this)" data-resource="<?= $jsData ?>" class="brutal-border p-1.5 brutal-hover bg-card hover:bg-destructive hover:text-destructive-foreground">
                    <i data-lucide="trash-2" class="h-3.5 w-3.5" stroke-width="2.5"></i>
                </button>
            </div>
        </td>
    </tr>
<?php endforeach; ?>

<?php if (empty($resources)): ?>
    <tr>
        <td colSpan="8" class="text-center py-10 text-muted-foreground uppercase tracking-widest">
            No resources match filter
        </td>
    </tr>
<?php endif; ?>
