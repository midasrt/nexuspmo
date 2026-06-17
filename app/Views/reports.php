<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<?php
$statusLabels = [
    'on-track' => 'ON TRACK',
    'at-risk'  => 'AT RISK',
    'blocked'  => 'BLOCKED',
    'delayed'  => 'DELAYED',
    'backlog'  => 'BACKLOG',
];

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
                <h1 class="font-display text-2xl md:text-3xl font-black uppercase mt-1 tracking-tight">Reports</h1>
            </div>
        </div>
    </header>

    <div class="w-full px-8 lg:px-14">
        <!-- Report Controls Box -->
        <form action="" method="GET" class="no-print rounded-2xl border border-ink/15 bg-card/70 backdrop-blur p-6 shadow-sm mb-8 space-y-6 cascade-in" style="animation-delay: 50ms;">
            <input type="hidden" name="generate" value="true" />
            
            <!-- Report Type Select -->
            <div>
                <span class="eyebrow">Report Type</span>
                <div class="flex rounded-full bg-ink/5 p-1 max-w-xs mt-2 border border-ink/10">
                    <label class="flex-1 text-center py-2 text-xs font-mono uppercase tracking-widest font-bold rounded-full cursor-pointer transition-all <?= $period === 'weekly' ? 'bg-ink text-paper shadow-sm' : 'text-muted-foreground hover:text-ink' ?>">
                        <input type="radio" name="period" value="weekly" <?= $period === 'weekly' ? 'checked' : '' ?> class="hidden" onchange="this.form.submit()" />
                        Weekly
                    </label>
                    <label class="flex-1 text-center py-2 text-xs font-mono uppercase tracking-widest font-bold rounded-full cursor-pointer transition-all <?= $period === 'monthly' ? 'bg-ink text-paper shadow-sm' : 'text-muted-foreground hover:text-ink' ?>">
                        <input type="radio" name="period" value="monthly" <?= $period === 'monthly' ? 'checked' : '' ?> class="hidden" onchange="this.form.submit()" />
                        Monthly
                    </label>
                </div>
            </div>

            <!-- Project Selector -->
            <div>
                <span class="eyebrow">Select Project</span>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-2">
                    <?php foreach ($projects as $p): ?>
                        <label class="rounded-xl border p-4 text-left transition-all cursor-pointer flex flex-col justify-between <?= $selectedId === $p['id'] ? 'bg-ink text-paper border-ink shadow-md' : 'bg-background text-ink border-ink/15 hover:border-ink/50 shadow-sm' ?>">
                            <input type="radio" name="project" value="<?= $p['id'] ?>" <?= $selectedId === $p['id'] ? 'checked' : '' ?> class="hidden" />
                            <div class="mono text-[10px] uppercase tracking-widest <?= $selectedId === $p['id'] ? 'text-paper/70' : 'text-muted-foreground' ?>">
                                <?= esc($p['code']) ?>
                            </div>
                            <div class="font-display font-bold uppercase tracking-tight text-sm mt-1.5 truncate">
                                <?= esc($p['name']) ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center justify-between gap-4 flex-wrap pt-4 border-t border-ink/10">
                <div class="mono text-xs text-muted-foreground font-semibold">
                    Comparing: <?= $period === 'weekly' ? 'Last 7 days vs. previous 7 days' : 'This month vs. last month' ?>
                </div>
                <button type="submit" class="rounded-full bg-ink text-paper hover:bg-ink/90 px-5 py-2.5 text-xs font-mono uppercase tracking-widest font-bold flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Generate Report
                </button>
            </div>
        </form>

        <!-- Generated Report Render -->
        <?php if ($report): ?>
            <?php
            $p = $report['project'];
            ?>
            <div class="rounded-2xl border border-ink/15 bg-card p-8 shadow-md relative overflow-hidden print-card">
                
                <!-- Report Header -->
                <div class="border-b border-ink/15 pb-6 mb-8 flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <span class="eyebrow"><?= $period === 'weekly' ? 'Weekly Status Report' : 'Monthly Status Report' ?></span>
                        <h2 class="font-display text-2xl md:text-3xl font-black uppercase mt-1 tracking-tight text-ink">
                            <?= esc($p['name']) ?>
                        </h2>
                        <div class="mono text-xs mt-2 text-muted-foreground">
                            Code: <?= esc($p['code']) ?> · Owner: <?= esc($p['owner']) ?> · Generated: <?= $report['at'] ?>
                        </div>
                    </div>
                    <button onclick="window.print()" class="no-print rounded-full border border-ink/20 bg-ink text-paper hover:bg-ink/90 px-4 py-2 text-xs font-mono uppercase tracking-widest font-bold flex items-center gap-1.5 shadow-sm">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i> Print / Export
                    </button>
                </div>

                <div class="space-y-8">
                    
                    <!-- Executive Summary -->
                    <div class="space-y-2">
                        <h3 class="eyebrow">Executive Summary</h3>
                        <p class="text-sm font-semibold uppercase leading-relaxed text-ink"><?= esc($report['summary']) ?></p>
                    </div>

                    <!-- Current vs Previous Comparisons -->
                    <div class="space-y-4">
                        <h3 class="eyebrow">
                            <?= $period === 'weekly' ? 'This Week' : 'This Month' ?> vs. <?= $period === 'weekly' ? 'Last Week' : 'Last Month' ?>
                        </h3>
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Previous Snapshot -->
                            <div class="rounded-xl border border-ink/10 bg-background/30 p-4 opacity-75">
                                <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground mb-3 border-b border-ink/5 pb-2">
                                    <?= $period === 'weekly' ? 'Previous Week' : 'Previous Month' ?>
                                </div>
                                <div class="grid gap-2 text-xs font-mono">
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">Status</span>
                                        <span class="font-bold uppercase text-ink"><?= $statusLabels[$report['previous']['status']] ?></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">Progress</span>
                                        <span class="font-bold text-ink"><?= $report['previous']['progress'] ?>%</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">Open Actions</span>
                                        <span class="font-bold text-ink"><?= $report['previous']['openActions'] ?></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">Escalations</span>
                                        <span class="font-bold text-ink"><?= $report['previous']['escalations'] ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Current Snapshot -->
                            <div class="rounded-xl border border-ink/15 bg-background/50 p-4 shadow-sm">
                                <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground mb-3 border-b border-ink/10 pb-2">
                                    <?= $period === 'weekly' ? 'Current Week' : 'Current Month' ?>
                                </div>
                                <div class="grid gap-2 text-xs font-mono">
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">Status</span>
                                        <span class="font-bold uppercase text-ink"><?= $statusLabels[$report['current']['status']] ?></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">Progress</span>
                                        <span class="font-bold text-ink">
                                            <?= $report['current']['progress'] ?>% (<?= ($report['current']['progress'] >= $report['previous']['progress']) ? '+' : '' ?><?= $report['current']['progress'] - $report['previous']['progress'] ?>%)
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">Open Actions</span>
                                        <span class="font-bold text-ink">
                                            <?= $report['current']['openActions'] ?> (<?= ($report['current']['openActions'] - $report['previous']['openActions'] >= 0) ? '+' : '' ?><?= $report['current']['openActions'] - $report['previous']['openActions'] ?>)
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">Escalations</span>
                                        <span class="font-bold text-ink">
                                            <?= $report['current']['escalations'] ?> (<?= ($report['current']['escalations'] - $report['previous']['escalations'] >= 0) ? '+' : '' ?><?= $report['current']['escalations'] - $report['previous']['escalations'] ?>)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Key Updates -->
                    <div class="space-y-4">
                        <h3 class="eyebrow">Key Updates</h3>
                        <div class="grid gap-2">
                            <?php foreach ($report['updates'] as $index => $u): ?>
                                <div class="rounded-xl border border-ink/10 bg-background/50 px-4 py-3.5 flex gap-4 items-center">
                                    <span class="font-mono text-xs text-muted-foreground"><?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></span>
                                    <span class="text-sm font-semibold uppercase text-ink"><?= esc($u) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Achievements & Risks -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Achievements -->
                        <div class="space-y-4">
                            <h3 class="eyebrow">Achievements</h3>
                            <?php if (empty($report['achievements'])): ?>
                                <div class="rounded-xl border border-dashed border-ink/20 px-4 py-4 text-center text-xs text-muted-foreground uppercase font-mono bg-background/20">
                                    No major achievements logged.
                                </div>
                            <?php else: ?>
                                <div class="grid gap-2">
                                    <?php foreach ($report['achievements'] as $item): ?>
                                        <div class="rounded-xl border border-ink/10 bg-background/50 px-4 py-3 flex gap-2 text-sm font-semibold uppercase text-ink">
                                            <span class="text-status-ontrack font-bold">✓</span>
                                            <span><?= esc($item) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Risks -->
                        <div class="space-y-4">
                            <h3 class="eyebrow">Risks & Blockers</h3>
                            <?php if (empty($report['risks'])): ?>
                                <div class="rounded-xl border border-dashed border-ink/20 px-4 py-4 text-center text-xs text-muted-foreground uppercase font-mono bg-background/20">
                                    No new risks identified.
                                </div>
                            <?php else: ?>
                                <div class="grid gap-2">
                                    <?php foreach ($report['risks'] as $item): ?>
                                        <div class="rounded-xl border border-ink/10 bg-background/50 px-4 py-3 flex gap-2 text-sm font-semibold uppercase text-status-blocked">
                                            <span class="font-bold">⚠</span>
                                            <span><?= esc($item) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Planned items -->
                    <div class="space-y-4">
                        <h3 class="eyebrow">
                            Next <?= $period === 'weekly' ? 'Week' : 'Month' ?> — Planned
                        </h3>
                        <?php if (empty($report['next'])): ?>
                            <div class="rounded-xl border border-dashed border-ink/20 px-4 py-4 text-center text-xs text-muted-foreground uppercase font-mono bg-background/20">
                                No planned items.
                            </div>
                        <?php else: ?>
                            <div class="grid gap-2">
                                <?php foreach ($report['next'] as $item): ?>
                                    <div class="rounded-xl border border-ink/10 bg-background/50 px-4 py-3 flex gap-2 text-sm font-semibold uppercase text-ink">
                                        <span class="text-ink/40 font-bold">▸</span>
                                        <span><?= esc($item) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
<?= $this->endSection() ?>
