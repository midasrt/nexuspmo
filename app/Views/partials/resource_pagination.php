<?php if ($totalPages > 1): ?>
    <div class="flex flex-wrap items-center justify-between gap-4 mt-6 mono text-xs">
        <span class="text-muted-foreground uppercase tracking-wider">
            Showing <?= min($offset + 1, $totalFiltered) ?>-<?= min($offset + $perPage, $totalFiltered) ?> of <?= $totalFiltered ?> resources
        </span>
        <div class="flex items-center gap-1">
            <!-- Previous Page -->
            <?php if ($currentPage > 1): ?>
                <a href="?role=<?= esc($roleFilter) ?>&status=<?= esc($statusFilter) ?>&search=<?= urlencode($search) ?>&page=<?= $currentPage - 1 ?>" class="pagination-link brutal-border bg-card px-3 py-1.5 brutal-hover font-bold" data-page="<?= $currentPage - 1 ?>">
                    &lt; PREV
                </a>
            <?php else: ?>
                <span class="brutal-border bg-card px-3 py-1.5 text-muted-foreground opacity-50 cursor-not-allowed font-bold">
                    &lt; PREV
                </span>
            <?php endif; ?>

            <!-- Page Numbers -->
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?role=<?= esc($roleFilter) ?>&status=<?= esc($statusFilter) ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>" class="pagination-link brutal-border px-3 py-1.5 brutal-hover <?= $currentPage === $i ? 'bg-foreground text-background font-black' : 'bg-card font-bold' ?>" data-page="<?= $i ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <!-- Next Page -->
            <?php if ($currentPage < $totalPages): ?>
                <a href="?role=<?= esc($roleFilter) ?>&status=<?= esc($statusFilter) ?>&search=<?= urlencode($search) ?>&page=<?= $currentPage + 1 ?>" class="pagination-link brutal-border bg-card px-3 py-1.5 brutal-hover font-bold" data-page="<?= $currentPage + 1 ?>">
                    NEXT &gt;
                </a>
            <?php else: ?>
                <span class="brutal-border bg-card px-3 py-1.5 text-muted-foreground opacity-50 cursor-not-allowed font-bold">
                    NEXT &gt;
                </span>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
