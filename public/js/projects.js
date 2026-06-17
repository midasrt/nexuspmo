let phaseIndex = 0;

// Build status dropdown options dynamically
let statusOptionsHtml = '';
if (window.ProjectsConfig && window.ProjectsConfig.statusDefinitions) {
    window.ProjectsConfig.statusDefinitions.forEach(def => {
        statusOptionsHtml += `<option value="${def.status}">${def.label}</option>`;
    });
}

function addPhaseRow(containerId, data = null) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const index = phaseIndex++;
    const row = document.createElement('div');
    row.className = 'brutal-border p-3 bg-background grid grid-cols-2 gap-3 relative';
    
    row.innerHTML = `
        <input type="hidden" name="phases[${index}][id]" value="${data && data.id ? data.id : ''}" />
        <div class="col-span-2 flex items-center justify-between">
            <span class="mono text-[9px] uppercase tracking-widest text-muted-foreground">Phase Item</span>
            <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-destructive font-black text-[10px] uppercase hover:opacity-75">✕ Remove</button>
        </div>
        <div class="col-span-2 flex flex-col gap-1">
            <span class="mono text-[9px] uppercase tracking-widest text-muted-foreground">Name</span>
            <input name="phases[${index}][name]" required value="${data ? escapeHtml(data.name) : ''}" class="w-full border border-foreground bg-background px-2 py-1 mono text-xs focus:outline-none" />
        </div>
        <div class="col-span-2 flex flex-col gap-1">
            <span class="mono text-[9px] uppercase tracking-widest text-muted-foreground">Description</span>
            <textarea name="phases[${index}][description]" rows="1" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none">${data && data.description ? escapeHtml(data.description) : ''}</textarea>
        </div>
        <div class="flex flex-col gap-1">
            <span class="mono text-[9px] uppercase tracking-widest text-muted-foreground">Status</span>
            <select name="phases[${index}][status]" class="w-full border border-foreground bg-background px-2 py-1.5 mono text-xs focus:outline-none">
                ${statusOptionsHtml}
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <span class="mono text-[9px] uppercase tracking-widest text-muted-foreground">Start Date</span>
            <input type="date" name="phases[${index}][start]" value="${data ? data.start : ''}" class="w-full border border-foreground bg-background px-2 py-1 mono text-xs focus:outline-none" />
        </div>
        <div class="flex flex-col gap-1 col-span-2">
            <span class="mono text-[9px] uppercase tracking-widest text-muted-foreground">End Date</span>
            <input type="date" name="phases[${index}][end]" value="${data ? data.end : ''}" class="w-full border border-foreground bg-background px-2 py-1 mono text-xs focus:outline-none" />
        </div>
    `;
    
    container.appendChild(row);

    // Select correct status if data exists
    if (data) {
        row.querySelector(`select[name="phases[${index}][status]"]`).value = data.status;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function openCreateModal() {
    const container = document.getElementById('create-phases-container');
    if (container) {
        container.innerHTML = '';
        // Add two default phase rows
        addPhaseRow('create-phases-container', {name: 'Discovery', description: 'Discovery Phase', start: '', end: '', status: 'on-track'});
        addPhaseRow('create-phases-container', {name: 'Execution', description: 'Execution Phase', start: '', end: '', status: 'backlog'});
    }
    document.getElementById('create-modal').classList.remove('hidden');
}

function openEditModal(button) {
    const p = JSON.parse(button.getAttribute('data-project'));
    document.getElementById('edit-form').action = window.ProjectsConfig.updateUrl + '/' + p.id;
    document.getElementById('edit-code').value = p.code;
    document.getElementById('edit-status').value = p.status;
    document.getElementById('edit-name').value = p.name;
    document.getElementById('edit-owner').value = p.owner;
    document.getElementById('edit-squad').value = p.squad;
    document.getElementById('edit-start-date').value = p.startDate;
    document.getElementById('edit-end-date').value = p.endDate;
    document.getElementById('edit-description').value = p.description || '';

    // Populate phases
    const editContainer = document.getElementById('edit-phases-container');
    if (editContainer) {
        editContainer.innerHTML = '';
        if (p.phases && p.phases.length > 0) {
            p.phases.forEach(ph => {
                addPhaseRow('edit-phases-container', ph);
            });
        }
    }

    document.getElementById('edit-modal').classList.remove('hidden');
}

const CONFIRMATION_WORDS = ['NEXUS', 'PMO', 'SEGMENT', 'TIMELINE', 'PHASE', 'AGILE', 'SCRUM', 'VELOCITY', 'SPRINT', 'DELIVERABLE', 'METRIC', 'RESOURCE', 'UTILIZATION', 'ESCORT', 'ANTIGRAVITY', 'SYSTEM', 'CASCADE'];
let currentConfirmWord = '';

function openDeleteModal(button) {
    const p = JSON.parse(button.getAttribute('data-project'));
    document.getElementById('delete-form').action = window.ProjectsConfig.deleteUrl + '/' + p.id;
    document.getElementById('delete-project-name').innerText = p.code + ' — ' + p.name;

    const randIdx = Math.floor(Math.random() * CONFIRMATION_WORDS.length);
    currentConfirmWord = CONFIRMATION_WORDS[randIdx];

    const targetEl = document.getElementById('delete-confirm-target');
    if (targetEl) {
        targetEl.innerText = currentConfirmWord;
    }

    const confirmInput = document.getElementById('delete-confirm-input');
    if (confirmInput) {
        confirmInput.value = '';
    }

    const submitBtn = document.getElementById('delete-submit-btn');
    if (submitBtn) {
        submitBtn.disabled = true;
    }

    document.getElementById('delete-modal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

// Close modals on clicking backdrop
window.onclick = function(event) {
    const modals = ['view-modal', 'create-modal', 'edit-modal', 'delete-modal'];
    modals.forEach(function(id) {
        const modal = document.getElementById(id);
        if (modal && event.target == modal) {
            modal.classList.add('hidden');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const confirmInput = document.getElementById('delete-confirm-input');
    const submitBtn = document.getElementById('delete-submit-btn');
    if (confirmInput && submitBtn) {
        confirmInput.addEventListener('input', () => {
            const inputVal = confirmInput.value.trim().toUpperCase();
            submitBtn.disabled = (inputVal !== currentConfirmWord);
        });
    }
});
