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

<div class="min-h-screen p-6 md:p-8">
    <div class="max-w-6xl mx-auto">
        
        <!-- Header -->
        <div class="no-print">
            <div class="mono text-[10px] uppercase tracking-[0.3em] text-muted-foreground">
                PMO // Module
            </div>
            <h1 class="mono text-4xl font-black uppercase tracking-tight mt-2">Reports</h1>
            <p class="mt-2 text-sm text-muted-foreground max-w-prose">
                Generate weekly or monthly status reports for any project. Each report compares
                the current period vs. the previous one.
            </p>
        </div>

        <!-- Report Controls Box -->
        <form action="" method="GET" class="no-print mt-8 grid gap-4 brutal-border bg-card p-5 brutal-shadow">
            <input type="hidden" name="generate" value="true" />
            
            <!-- Report Type Select -->
            <div>
                <div class="mono text-[10px] uppercase tracking-widest font-black">Report Type</div>
                <div class="grid grid-cols-2 gap-3 mt-2 max-w-md">
                    <!-- Weekly Button option -->
                    <label class="brutal-border p-3 flex items-center gap-2 font-black uppercase tracking-tight text-sm cursor-pointer transition-all hover:translate-x-[-2px] hover:translate-y-[-2px] hover:brutal-shadow <?= $period === 'weekly' ? 'bg-primary text-primary-foreground' : 'bg-background' ?>">
                        <input type="radio" name="period" value="weekly" <?= $period === 'weekly' ? 'checked' : '' ?> class="hidden" onchange="this.form.submit()" />
                        <i data-lucide="calendar" class="h-4 w-4 shrink-0"></i>
                        Weekly
                    </label>
                    
                    <!-- Monthly Button option -->
                    <label class="brutal-border p-3 flex items-center gap-2 font-black uppercase tracking-tight text-sm cursor-pointer transition-all hover:translate-x-[-2px] hover:translate-y-[-2px] hover:brutal-shadow <?= $period === 'monthly' ? 'bg-primary text-primary-foreground' : 'bg-background' ?>">
                        <input type="radio" name="period" value="monthly" <?= $period === 'monthly' ? 'checked' : '' ?> class="hidden" onchange="this.form.submit()" />
                        <i data-lucide="calendar-days" class="h-4 w-4 shrink-0"></i>
                        Monthly
                    </label>
                </div>
            </div>

            <!-- Project Selector -->
            <div>
                <div class="mono text-[10px] uppercase tracking-widest font-black">Project</div>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2 mt-2">
                    <?php foreach ($projects as $p): ?>
                        <label class="brutal-border p-3 text-left transition-all hover:translate-x-[-2px] hover:translate-y-[-2px] hover:brutal-shadow cursor-pointer <?= $selectedId === $p['id'] ? 'bg-primary text-primary-foreground' : 'bg-background' ?>">
                            <input type="radio" name="project" value="<?= $p['id'] ?>" <?= $selectedId === $p['id'] ? 'checked' : '' ?> class="hidden" />
                            <div class="mono text-[10px] uppercase tracking-widest opacity-70">
                                <?= esc($p['code']) ?>
                            </div>
                            <div class="font-bold uppercase tracking-tight text-sm mt-0.5 truncate">
                                <?= esc($p['name']) ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center justify-between gap-3 flex-wrap pt-2">
                <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground">
                    <?= $period === 'weekly' ? 'Last 7 days vs. previous 7 days' : 'This month vs. last month' ?>
                </div>
                <button type="submit" class="brutal-border bg-foreground text-background px-5 py-2.5 font-black uppercase tracking-tight text-sm flex items-center gap-2 hover:translate-x-[-2px] hover:translate-y-[-2px] hover:brutal-shadow transition-all">
                    <i data-lucide="file-text" class="h-4 w-4"></i> Generate Report
                </button>
            </div>
        </form>

        <!-- Generated Report Render -->
        <?php if ($report): ?>
            <?php
            $p = $report['project'];
            ?>
            <div class="mt-8 brutal-border bg-card brutal-shadow print-card">
                
                <!-- Report Header -->
                <div class="border-b border-foreground p-5 bg-foreground text-background flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <div class="mono text-[10px] uppercase tracking-[0.3em] opacity-70">
                            <?= $period === 'weekly' ? 'Weekly Status Report' : 'Monthly Status Report' ?>
                        </div>
                        <h2 class="mono text-2xl md:text-3xl font-black uppercase tracking-tight mt-1">
                            <?= esc($p['name']) ?>
                        </h2>
                        <div class="mono text-xs mt-1 opacity-80">
                            <?= esc($p['code']) ?> // Owner: <?= esc($p['owner']) ?> // Generated <?= $report['at'] ?>
                        </div>
                    </div>
                    <button onclick="window.print()" class="no-print brutal-border border-background bg-background text-foreground px-3 py-2 mono text-[10px] font-black uppercase tracking-widest flex items-center gap-2 hover:translate-x-[-2px] hover:translate-y-[-2px] transition-transform">
                        <i data-lucide="download" class="h-3.5 w-3.5" stroke-width="3"></i> Export
                    </button>
                </div>

                <div class="p-6 grid gap-6">
                    
                    <!-- Executive Summary -->
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-primary brutal-border"></div>
                            <h3 class="mono text-xs uppercase tracking-widest font-black">Executive Summary</h3>
                            <div class="flex-1 h-[2px] bg-border"></div>
                        </div>
                        <p class="text-sm leading-relaxed uppercase font-bold"><?= esc($report['summary']) ?></p>
                    </div>

                    <!-- Current vs Previous Comparisons -->
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-primary brutal-border"></div>
                            <h3 class="mono text-xs uppercase tracking-widest font-black">
                                <?= $period === 'weekly' ? 'This Week' : 'This Month' ?> vs. <?= $period === 'weekly' ? 'Last Week' : 'Last Month' ?>
                            </h3>
                            <div class="flex-1 h-[2px] bg-border"></div>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <!-- Previous Snapshot -->
                            <div class="brutal-border p-4 bg-background opacity-80">
                                <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground mb-3">
                                    <?= $period === 'weekly' ? 'Previous Week' : 'Previous Month' ?>
                                </div>
                                <div class="grid gap-2">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Status</span>
                                        <span class="font-bold uppercase"><?= $statusLabels[$report['previous']['status']] ?></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Progress</span>
                                        <span class="font-bold"><?= $report['previous']['progress'] ?>%</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Open Actions</span>
                                        <span class="font-bold"><?= $report['previous']['openActions'] ?></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Escalations</span>
                                        <span class="font-bold"><?= $report['previous']['escalations'] ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Current Snapshot -->
                            <div class="brutal-border p-4 bg-background">
                                <div class="mono text-[10px] uppercase tracking-widest text-muted-foreground mb-3">
                                    <?= $period === 'weekly' ? 'Current Week' : 'Current Month' ?>
                                </div>
                                <div class="grid gap-2">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Status</span>
                                        <span class="font-bold uppercase <?= $report['current']['status'] !== $report['previous']['status'] ? 'text-primary' : '' ?>">
                                            <?= $statusLabels[$report['current']['status']] ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Progress</span>
                                        <span class="font-bold text-primary">
                                            <?= $report['current']['progress'] ?>% (<?= ($report['current']['progress'] >= $report['previous']['progress']) ? '+' : '' ?><?= $report['current']['progress'] - $report['previous']['progress'] ?>)
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Open Actions</span>
                                        <span class="font-bold">
                                            <?= $report['current']['openActions'] ?> (<?= ($report['current']['openActions'] - $report['previous']['openActions'] >= 0) ? '+' : '' ?><?= $report['current']['openActions'] - $report['previous']['openActions'] ?>)
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Escalations</span>
                                        <span class="font-bold">
                                            <?= $report['current']['escalations'] ?> (<?= ($report['current']['escalations'] - $report['previous']['escalations'] >= 0) ? '+' : '' ?><?= $report['current']['escalations'] - $report['previous']['escalations'] ?>)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Key Updates -->
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-primary brutal-border"></div>
                            <h3 class="mono text-xs uppercase tracking-widest font-black">Key Updates</h3>
                            <div class="flex-1 h-[2px] bg-border"></div>
                        </div>
                        <ul class="grid gap-2">
                            <?php foreach ($report['updates'] as $index => $u): ?>
                                <li class="brutal-border p-3 bg-background flex gap-3">
                                    <span class="mono text-[10px] font-black uppercase tracking-widest text-muted-foreground">
                                        <?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?>
                                    </span>
                                    <span class="text-sm font-bold uppercase"><?= esc($u) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Achievements & Risks -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Achievements -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 bg-primary brutal-border"></div>
                                <h3 class="mono text-xs uppercase tracking-widest font-black">Achievements</h3>
                                <div class="flex-1 h-[2px] bg-border"></div>
                            </div>
                            <?php if (empty($report['achievements'])): ?>
                                <div class="brutal-border p-3 bg-background mono text-xs text-muted-foreground uppercase tracking-widest text-center">
                                    No major achievements logged.
                                </div>
                            <?php else: ?>
                                <ul class="grid gap-2">
                                    <?php foreach ($report['achievements'] as $item): ?>
                                        <li class="brutal-border p-3 bg-background text-sm flex gap-2 font-bold uppercase">
                                            <span class="text-primary font-black">▸</span>
                                            <span><?= esc($item) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <!-- Risks -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 bg-primary brutal-border"></div>
                                <h3 class="mono text-xs uppercase tracking-widest font-black">Risks & Blockers</h3>
                                <div class="flex-1 h-[2px] bg-border"></div>
                            </div>
                            <?php if (empty($report['risks'])): ?>
                                <div class="brutal-border p-3 bg-background mono text-xs text-muted-foreground uppercase tracking-widest text-center">
                                    No new risks identified.
                                </div>
                            <?php else: ?>
                                <ul class="grid gap-2">
                                    <?php foreach ($report['risks'] as $item): ?>
                                        <li class="brutal-border p-3 bg-background text-sm flex gap-2 font-bold uppercase text-destructive">
                                            <span class="font-black">▸</span>
                                            <span><?= esc($item) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Planned items -->
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-primary brutal-border"></div>
                            <h3 class="mono text-xs uppercase tracking-widest font-black">
                                Next <?= $period === 'weekly' ? 'Week' : 'Month' ?> — Planned
                            </h3>
                            <div class="flex-1 h-[2px] bg-border"></div>
                        </div>
                        <?php if (empty($report['next'])): ?>
                            <div class="brutal-border p-3 bg-background mono text-xs text-muted-foreground uppercase tracking-widest text-center">
                                No planned items.
                            </div>
                        <?php else: ?>
                            <ul class="grid gap-2">
                                <?php foreach ($report['next'] as $item): ?>
                                    <li class="brutal-border p-3 bg-background text-sm flex gap-2 font-bold uppercase">
                                        <span class="text-primary font-black">▸</span>
                                        <span><?= esc($item) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
<?= $this->endSection() ?>
