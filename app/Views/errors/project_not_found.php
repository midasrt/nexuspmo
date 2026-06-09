<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<div class="min-h-screen flex items-center justify-center p-8">
    <div class="brutal-border-thick bg-card p-10 text-center max-w-md brutal-shadow">
        <h1 class="mono text-2xl font-black uppercase tracking-tight">Project not found</h1>
        <p class="mt-4 mono text-xs uppercase tracking-widest text-muted-foreground">
            No project matches this ID.
        </p>
        <a href="<?= base_url() ?>" class="inline-block mt-6 brutal-border bg-foreground text-background px-5 py-2.5 mono text-xs uppercase tracking-widest font-black brutal-hover">
            ← Portfolio
        </a>
    </div>
</div>
<?= $this->endSection() ?>
