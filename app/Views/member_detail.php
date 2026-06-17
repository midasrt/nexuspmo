<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<?php
$roleBgs = [
    'FE' => 'bg-status-ontrack',
    'BE' => 'bg-status-atrisk',
    'QA' => 'bg-status-delayed',
    'BA' => 'bg-status-backlog',
];

function getUtilColorLocal($u) {
    if ($u >= 85) return 'bg-status-blocked';
    if ($u >= 65) return 'bg-status-ontrack';
    if ($u >= 40) return 'bg-status-atrisk';
    return 'bg-status-backlog';
}
?>

<div class="min-h-screen pb-24">
    <!-- Header -->
    <header class="border-b border-ink/15 py-6 no-print mb-8 cascade-in" style="animation-delay: 0ms;">
        <div class="w-full px-8 lg:px-14 flex items-center justify-between gap-4">
            <div>
                <a href="<?= base_url('resource') ?>" class="group inline-flex items-center gap-1.5 mono text-[10px] uppercase tracking-widest text-ink/60 hover:text-ink transition-colors mb-2">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-0.5"></i>
                    Back to Resources
                </a>
                <h1 class="font-display text-2xl md:text-3xl font-black uppercase mt-1 tracking-tight">Resource Profile</h1>
            </div>
            <div class="mono text-xs text-muted-foreground uppercase tracking-widest">
                Nexus ID: #<?= sprintf("%03d", $member['id']) ?>
            </div>
        </div>
    </header>

    <div class="w-full px-8 lg:px-14">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <!-- Left Column: Profile Card -->
            <div class="lg:col-span-1 space-y-6">
                <div class="rounded-2xl border border-ink/15 bg-card p-6 shadow-sm flex flex-col justify-between cascade-in" style="animation-delay: 50ms;">
                    <div>
                        <!-- Profile Header -->
                        <div class="flex items-center gap-4 border-b border-ink/10 pb-5">
                            <span class="grid h-16 w-16 place-items-center rounded-full bg-ink text-paper font-display text-xl font-bold"><?= $member['initials'] ?></span>
                            <div>
                                <h2 class="font-display text-lg font-bold text-ink uppercase leading-snug"><?= esc($member['name']) ?></h2>
                                <div class="mono text-[10px] text-muted-foreground uppercase tracking-widest mt-1"><?= esc($member['department']) ?></div>
                            </div>
                        </div>

                        <!-- Capacity Metrics -->
                        <div class="py-5 border-b border-ink/10 space-y-3">
                            <div class="flex items-end justify-between">
                                <span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">Workload / Utilization</span>
                                <span class="font-display text-2xl font-bold"><?= $member['utilization'] ?><span class="text-xs text-muted-foreground">%</span></span>
                            </div>
                            <div class="h-2 rounded-full bg-ink/10 overflow-hidden">
                                <div class="h-full" style="width: <?= $member['utilization'] ?>%; background: <?= getUtilColorLocal($member['utilization']) ?>"></div>
                            </div>
                        </div>

                        <!-- Metadata Grid -->
                        <div class="py-5 space-y-4 font-mono text-xs">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground uppercase">Role</span>
                                <span class="font-bold text-ink uppercase"><?= esc($member['role']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground uppercase">Status</span>
                                <span class="font-bold text-ink uppercase"><?= esc($member['status']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground uppercase">Location</span>
                                <span class="font-bold text-ink"><?= esc($member['location'] ?: 'Local') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground uppercase">Manager</span>
                                <span class="font-bold text-ink"><?= esc($member['manager'] ?: 'N/A') ?></span>
                            </div>
                            <div class="flex flex-col gap-1.5 pt-2">
                                <span class="text-muted-foreground uppercase">Email</span>
                                <a href="mailto:<?= esc($member['email']) ?>" class="font-bold text-ink hover:underline break-all"><?= esc($member['email']) ?></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills Card -->
                <div class="rounded-2xl border border-ink/15 bg-card p-6 shadow-sm cascade-in" style="animation-delay: 100ms;">
                    <span class="eyebrow block mb-3">Skills Logged</span>
                    <?php if (empty($member['skills'])): ?>
                        <p class="mono text-[10px] text-muted-foreground uppercase">No skills registered</p>
                    <?php else: ?>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach ($member['skills'] as $s): ?>
                                <span class="mono text-[9px] uppercase tracking-wider rounded-md border border-ink/15 bg-secondary px-2.5 py-1 text-ink"><?= esc(trim($s)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Assigned Tasks -->
            <div class="lg:col-span-2 space-y-6">
                <div class="space-y-6 cascade-in" style="animation-delay: 150ms;">
                    <div class="flex items-center justify-between border-b border-ink/10 pb-4">
                        <div>
                            <span class="eyebrow">Assigned Workload</span>
                            <h3 class="font-display text-xl font-bold uppercase tracking-tight text-ink mt-0.5">Tasks & Deliverables</h3>
                        </div>
                        <span class="mono text-xs text-muted-foreground uppercase tracking-widest bg-secondary px-3 py-1 rounded-full border border-ink/10">
                            Total Tasks: <?= count($subtasks) ?>
                        </span>
                    </div>

                    <?php if (empty($subtasks)): ?>
                        <div class="rounded-2xl border border-ink/10 border-dashed p-10 text-center bg-card">
                            <p class="mono text-xs text-muted-foreground uppercase">No active tasks assigned to this resource profile</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($subtasks as $idx => $task):
                                $taskStatus = strtolower($task['status']);
                                $statusBadgeClass = 'border-ink/20 text-ink/75 bg-ink/5';
                                if (in_array($taskStatus, ['complete', 'completed', 'done'])) {
                                    $statusBadgeClass = 'border-status-ontrack text-status-ontrack bg-status-ontrack/5';
                                } elseif (in_array($taskStatus, ['in-progress', 'active', 'started'])) {
                                    $statusBadgeClass = 'border-status-atrisk text-status-atrisk bg-status-atrisk/5';
                                } elseif (in_array($taskStatus, ['blocked'])) {
                                    $statusBadgeClass = 'border-status-blocked text-status-blocked bg-status-blocked/5';
                                } elseif (in_array($taskStatus, ['delayed'])) {
                                    $statusBadgeClass = 'border-status-delayed text-status-delayed bg-status-delayed/5';
                                } else {
                                    $statusBadgeClass = 'border-status-backlog text-status-backlog bg-status-backlog/5';
                                }
                            ?>
                                <div class="rounded-2xl border border-ink/15 bg-card p-5 hover:border-ink/30 hover:shadow-md transition-all duration-300 flex flex-col justify-between h-full group">
                                    <!-- Card Header -->
                                    <div class="space-y-2.5">
                                        <div class="flex items-center justify-between gap-2">
                                            <a href="<?= base_url('project/' . esc($task['project_id'])) ?>" class="mono text-[10px] font-bold uppercase tracking-widest text-ink/60 hover:text-ink bg-secondary px-2.5 py-0.5 rounded border border-ink/10 transition-colors truncate max-w-[70%]" title="View Project: <?= esc($task['project_name']) ?>">
                                                <?= esc($task['project_code']) ?>
                                            </a>
                                            <span class="mono text-[9px] uppercase tracking-wider px-2 py-0.5 rounded border <?= $statusBadgeClass ?>">
                                                <?= esc($task['status']) ?>
                                            </span>
                                        </div>
                                        
                                        <h4 class="font-display text-base font-bold text-ink uppercase tracking-tight group-hover:text-ink/80 transition-colors pt-1">
                                            <a href="<?= base_url('project/' . esc($task['project_id'])) ?>" class="hover:underline" title="View Project: <?= esc($task['project_name']) ?>">
                                                <?= esc($task['name']) ?>
                                            </a>
                                        </h4>
                                        
                                        <div class="mono text-[9px] text-muted-foreground uppercase tracking-widest flex items-center gap-1">
                                            <i data-lucide="layers" class="w-3.5 h-3.5 text-ink/50"></i>
                                            <?= esc($task['phase_name']) ?>
                                        </div>

                                        <?php if (!empty($task['description'])): ?>
                                            <p class="text-xs text-muted-foreground line-clamp-2 pt-1 font-sans">
                                                <?= esc($task['description']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Card Footer Metrics -->
                                    <div class="border-t border-ink/10 pt-4 mt-4 grid grid-cols-2 gap-4">
                                        <div class="space-y-0.5">
                                            <span class="mono text-[9px] uppercase tracking-wider text-muted-foreground block">Task Hours</span>
                                            <div class="flex items-baseline gap-1">
                                                <span class="font-display text-lg font-bold text-ink"><?= number_format($task['task_hours'], 1) ?></span>
                                                <span class="mono text-[9px] text-muted-foreground lowercase">hrs</span>
                                            </div>
                                        </div>
                                        <div class="space-y-0.5 border-l border-ink/10 pl-4">
                                            <span class="mono text-[9px] uppercase tracking-wider text-muted-foreground block">Man Days</span>
                                            <div class="flex items-baseline gap-1">
                                                <span class="font-display text-lg font-bold text-ink"><?= number_format($task['man_days'], 1) ?></span>
                                                <span class="mono text-[9px] text-muted-foreground lowercase">days</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bottom: Radial Collaboration Mesh -->
        <div class="mt-10 space-y-6 cascade-in" style="animation-delay: 200ms;">
            <div class="rounded-2xl border border-ink/15 bg-card p-6 shadow-sm overflow-hidden flex flex-col justify-between">
                <div class="flex items-center justify-between border-b border-ink/10 pb-4 mb-4">
                    <div>
                        <span class="eyebrow">Interactive Visualizer</span>
                        <h3 class="font-display text-xl font-bold uppercase tracking-tight text-ink mt-0.5">Focused Collaboration Mesh</h3>
                    </div>
                    <span class="eyebrow text-right hidden sm:inline">Hover nodes to isolate project paths</span>
                </div>

                <?php
                $projectWorkloads = [];
                foreach ($projects as $p) {
                    $projectWorkloads[$p['id']] = 0;
                }
                foreach ($subtasks as $task) {
                    if (isset($projectWorkloads[$task['project_id']])) {
                        $projectWorkloads[$task['project_id']] += (float)$task['task_hours'];
                    }
                }
                $maxWorkload = 0;
                foreach ($projectWorkloads as $w) {
                    if ($w > $maxWorkload) {
                        $maxWorkload = $w;
                    }
                }
                ?>
                <div class="relative w-full aspect-[3/2] max-h-[500px]">
                    <svg viewBox="0 0 900 600" class="w-full h-full">
                        <!-- Link Lines -->
                        <?php foreach ($links as $link): ?>
                            <?php if ($link['class'] === 'link-member-proj'): 
                                $pid = $link['data_project'];
                                $wVal = $projectWorkloads[$pid] ?? 0;
                                $weightPct = $maxWorkload > 0 ? ($wVal / $maxWorkload) : 0;
                                $strokeWidth = 0.8 + ($weightPct * 4.2);
                            ?>
                                <line x1="<?= $link['from_x'] ?>" y1="<?= $link['from_y'] ?>" 
                                      x2="<?= $link['to_x'] ?>" y2="<?= $link['to_y'] ?>"
                                      stroke="var(--ink)" stroke-width="<?= number_format($strokeWidth, 1) ?>" opacity="0.15" 
                                      class="mesh-link transition-all duration-300 <?= $link['class'] ?>"
                                      data-project="<?= $link['data_project'] ?>"
                                      data-resource="<?= $link['data_resource'] ?>"
                                      data-weight-width="<?= number_format($strokeWidth, 1) ?>" />
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <!-- Target Member (Center Node) -->
                        <g class="mesh-node cursor-pointer" data-kind="center" onmouseenter="hoverCenterNode()" onmouseleave="clearMeshHover()">
                            <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="14" fill="var(--ink)"></circle>
                            <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="20" fill="none" stroke="var(--ink)" stroke-width="0.8" opacity="0.4" class="animate-pulse"></circle>
                            <text x="<?= $cx ?>" y="<?= $cy + 4 ?>" text-anchor="middle" class="font-display font-bold text-[9px] fill-paper uppercase" pointer-events="none"><?= $member['initials'] ?></text>
                            <text x="<?= $cx ?>" y="<?= $cy - 26 ?>" text-anchor="middle" class="font-display font-bold text-xs fill-ink uppercase"><?= esc($member['name']) ?></text>
                        </g>

                        <!-- Projects (Inner Ring) -->
                        <?php foreach ($projects as $p): 
                            $coord = $projCoords[$p['id']];
                            $angle = atan2($coord['y'] - $cy, $coord['x'] - $cx);
                            $textAnchor = (cos($angle) >= 0) ? 'start' : 'end';
                            $textOffsetX = (cos($angle) >= 0) ? 14 : -14;
                            $textOffsetY = (sin($angle) >= 0) ? 10 : -2;

                            $wVal = $projectWorkloads[$p['id']] ?? 0;
                            $weightPct = $maxWorkload > 0 ? ($wVal / $maxWorkload) : 0;
                            $radius = 6.0 + ($weightPct * 8.0);
                        ?>
                            <g class="mesh-node cursor-pointer transition-all duration-300" data-kind="project" data-id="<?= $p['id'] ?>" data-weight-radius="<?= number_format($radius, 1) ?>" onmouseenter="hoverProjectNode('<?= $p['id'] ?>')" onmouseleave="clearMeshHover()">
                                <circle cx="<?= $coord['x'] ?>" cy="<?= $coord['y'] ?>" r="<?= number_format($radius, 1) ?>" fill="var(--ink)"></circle>
                                <text x="<?= $coord['x'] + $textOffsetX ?>" y="<?= $coord['y'] + $textOffsetY ?>" text-anchor="<?= $textAnchor ?>" class="font-display font-bold text-[10px] fill-ink uppercase">
                                    <?= esc($p['code']) ?>
                                </text>
                            </g>
                        <?php endforeach; ?>
                    </svg>
                </div>

                <!-- Footnotes -->
                <div class="border-t border-ink/10 pt-4 mt-4 flex justify-between items-center text-[10px] font-mono text-muted-foreground uppercase">
                    <span>Projects Active: <?= count($projects) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function hoverCenterNode() {
        const links = document.querySelectorAll('.mesh-link');
        const nodes = document.querySelectorAll('.mesh-node');

        links.forEach(l => {
            l.setAttribute('opacity', '0.9');
            const baseWidth = parseFloat(l.getAttribute('data-weight-width')) || 0.8;
            l.setAttribute('stroke-width', (baseWidth * 1.5).toFixed(1));
        });

        nodes.forEach(n => {
            const circle = n.querySelector('circle');
            const text = n.querySelector('text');
            circle.setAttribute('opacity', '1');
            if (text) text.setAttribute('opacity', '1');
        });
    }

    function hoverProjectNode(projectId) {
        const links = document.querySelectorAll('.mesh-link');
        const nodes = document.querySelectorAll('.mesh-node');

        links.forEach(l => {
            const pId = l.getAttribute('data-project');
            if (pId === projectId) {
                l.setAttribute('opacity', '0.95');
                const baseWidth = parseFloat(l.getAttribute('data-weight-width')) || 0.8;
                l.setAttribute('stroke-width', (baseWidth * 2.0).toFixed(1));
            } else {
                l.setAttribute('opacity', '0.04');
                l.setAttribute('stroke-width', '0.6');
            }
        });

        nodes.forEach(n => {
            const kind = n.getAttribute('data-kind');
            const id = n.getAttribute('data-id');

            let lit = false;
            if (kind === 'center') lit = true;
            if (kind === 'project' && id === projectId) lit = true;

            const circle = n.querySelector('circle');
            const text = n.querySelector('text');
            if (lit) {
                circle.setAttribute('opacity', '1');
                const baseRadius = parseFloat(n.getAttribute('data-weight-radius')) || 7.5;
                circle.setAttribute('r', kind === 'project' ? (baseRadius * 1.3).toFixed(1) : '14');
                if (text) text.setAttribute('opacity', '1');
            } else {
                circle.setAttribute('opacity', '0.15');
                if (text) text.setAttribute('opacity', '0.25');
            }
        });
    }

    function clearMeshHover() {
        const links = document.querySelectorAll('.mesh-link');
        const nodes = document.querySelectorAll('.mesh-node');

        links.forEach(l => {
            l.setAttribute('opacity', '0.15');
            l.setAttribute('stroke-width', l.getAttribute('data-weight-width') || '0.8');
        });

        nodes.forEach(n => {
            const kind = n.getAttribute('data-kind');
            const circle = n.querySelector('circle');
            const text = n.querySelector('text');
            circle.setAttribute('opacity', '1');
            circle.setAttribute('r', kind === 'project' ? (n.getAttribute('data-weight-radius') || '7.5') : '14');
            if (text) text.setAttribute('opacity', '1');
        });
    }
</script>
<?= $this->endSection() ?>
