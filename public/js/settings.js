// Toggle collapsible settings section
function toggleSection(name, force = false) {
    const sections = ['status', 'org', 'resources'];
    const currentlyActive = localStorage.getItem('activeSettingsSection');
    
    // For tabs, we keep one active instead of toggling all closed
    const targetSection = (currentlyActive === name && !force) ? 'status' : name;
    
    sections.forEach(s => {
        const sectionEl = document.getElementById(`section-${s}`);
        const tileEl = document.getElementById(`tile-${s}`);
        
        if (sectionEl && tileEl) {
            if (s === targetSection) {
                // Open section
                sectionEl.classList.remove('hidden');
                sectionEl.classList.add('animate-fade-in');
                
                // Active tab styling
                tileEl.classList.remove('text-muted-foreground', 'border-transparent');
                tileEl.classList.add('text-ink', 'border-ink');
            } else {
                // Close section
                sectionEl.classList.add('hidden');
                sectionEl.classList.remove('animate-fade-in');
                
                // Inactive tab styling
                tileEl.classList.remove('text-ink', 'border-ink');
                tileEl.classList.add('text-muted-foreground', 'border-transparent');
            }
        }
    });
    
    localStorage.setItem('activeSettingsSection', targetSection);
}

function updateWorkWeekHours() {
    const hoursInput = document.getElementById('daily_work_hours');
    const daysInput = document.getElementById('work_days_per_week');
    const label = document.getElementById('work_week_hours_label');
    if (hoursInput && daysInput && label) {
        const hours = parseFloat(hoursInput.value) || 0;
        const days = parseFloat(daysInput.value) || 0;
        label.innerText = (hours * days) + ' Hours';
    }
}

// Auto-open last active section on load (default to status)
document.addEventListener('DOMContentLoaded', function() {
    const savedSection = localStorage.getItem('activeSettingsSection');
    if (savedSection && ['status', 'org', 'resources'].includes(savedSection)) {
        toggleSection(savedSection, true);
    } else {
        toggleSection('status', true);
    }
    updateWorkWeekHours();
});

function openCreateModal() {
    document.getElementById('create-status-modal').classList.remove('hidden');
}

function openEditModal(button) {
    const d = JSON.parse(button.getAttribute('data-definition'));
    document.getElementById('edit-status-form').action = window.Nexus.baseUrl + '/settings/status/update/' + d.id;
    document.getElementById('edit-status-slug').value = d.status;
    document.getElementById('edit-status-label').value = d.label;
    document.getElementById('edit-status-criteria').value = d.criteria;
    document.getElementById('edit-status-color').value = d.color || '#6b7280';
    document.getElementById('edit-status-modal').classList.remove('hidden');
}

function openDeleteModal(button) {
    const d = JSON.parse(button.getAttribute('data-definition'));
    document.getElementById('delete-status-form').action = window.Nexus.baseUrl + '/settings/status/delete/' + d.id;
    document.getElementById('delete-status-name').innerText = d.label;
    document.getElementById('delete-status-modal').classList.remove('hidden');
}

function openCreateOrgModal() {
    document.getElementById('create-org-modal').classList.remove('hidden');
}

function openEditOrgModal(button) {
    const org = JSON.parse(button.getAttribute('data-org'));
    document.getElementById('edit-org-form').action = window.Nexus.baseUrl + '/settings/org/update/' + org.id;
    document.getElementById('edit-org-name').value = org.name;
    document.getElementById('edit-org-description').value = org.description || '';
    document.getElementById('edit-org-modal').classList.remove('hidden');
}

function openDeleteOrgModal(button) {
    const org = JSON.parse(button.getAttribute('data-org'));
    document.getElementById('delete-org-form').action = window.Nexus.baseUrl + '/settings/org/delete/' + org.id;
    document.getElementById('delete-org-display-name').innerText = org.name;
    document.getElementById('delete-org-modal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function generateSlug(sourceId, targetId) {
    const val = document.getElementById(sourceId).value;
    const slug = val.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .trim();
    document.getElementById(targetId).value = slug;
}

window.onclick = function(event) {
    const modals = ['create-status-modal', 'edit-status-modal', 'delete-status-modal', 'create-org-modal', 'edit-org-modal', 'delete-org-modal'];
    modals.forEach(id => {
        const modal = document.getElementById(id);
        if (modal && event.target == modal) {
            modal.classList.add('hidden');
        }
    });
}
