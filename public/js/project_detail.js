const expandedPhases = new Set();
const expandedTimelinePhases = new Set();
let globalTimelineSubtasksVisible = false;
let globalTableSubtasksVisible = false;
let activeHistoryTab = 'chronicle';

function switchHistoryTab(tabName) {
    activeHistoryTab = tabName;
    // Hide all tabs
    document.getElementById('history-tab-chronicle').classList.add('hidden');
    document.getElementById('history-tab-compare').classList.add('hidden');
    document.getElementById('history-tab-raw').classList.add('hidden');

    // Show selected tab
    document.getElementById('history-tab-' + tabName).classList.remove('hidden');

    // Reset button styles
    const btnChronicle = document.getElementById('tab-btn-chronicle');
    const btnCompare = document.getElementById('tab-btn-compare');
    const btnRaw = document.getElementById('tab-btn-raw');

    if (btnChronicle && btnCompare && btnRaw) {
        [btnChronicle, btnCompare, btnRaw].forEach(btn => {
            btn.classList.remove('bg-ink', 'text-paper');
            btn.classList.add('bg-secondary/35', 'text-ink');
        });

        // Highlight active button
        const activeBtn = document.getElementById('tab-btn-' + tabName);
        if (activeBtn) {
            activeBtn.classList.remove('bg-secondary/35', 'text-ink');
            activeBtn.classList.add('bg-ink', 'text-paper');
        }
    }
}

function refreshBlocks() {
    fetch(window.location.href)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const sections = [
                '#stats-container',
                '#timeline-container',
                '#health-container',
                '#phases-container',
                '#resources-container',
                '#dependencies-container',
                '#escalations-container',
                '#risks-container',
                '#actions-container',
                '#status-history-container'
            ];
            sections.forEach(selector => {
                const oldEl = document.querySelector(selector);
                const newEl = doc.querySelector(selector);
                if (oldEl && newEl) {
                    oldEl.innerHTML = newEl.innerHTML;
                }
            });

            // Re-run drag & drop and dynamic chevrons/icons initialization
            initDragAndDrop();
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Re-apply chevrons for expanded states
            expandedPhases.forEach(id => {
                const box = document.getElementById(`subtask-list-box-${id}`);
                if (box) box.classList.remove('hidden');
                const chev = document.getElementById(`chevron-${id}`);
                if (chev) {
                    chev.classList.add('rotate-90');
                    chev.style.transform = 'rotate(90deg)';
                }
            });

            expandedTimelinePhases.forEach(id => {
                const rows = document.querySelectorAll(`.timeline-subtask-row-${id}`);
                rows.forEach(r => r.classList.remove('hidden'));
                const chev = document.getElementById(`timeline-chevron-${id}`);
                if (chev) chev.style.transform = 'rotate(90deg)';
            });

            if (globalTimelineSubtasksVisible) {
                const subRows = document.querySelectorAll('[class*="timeline-subtask-row-"]');
                subRows.forEach(row => row.classList.remove('hidden'));
                const chevrons = document.querySelectorAll('[id^="timeline-chevron-"]');
                chevrons.forEach(ch => ch.style.transform = 'rotate(90deg)');
            }

            if (globalTableSubtasksVisible) {
                const tableSubBoxes = document.querySelectorAll('[class*="subtask-list-box-"]');
                tableSubBoxes.forEach(box => box.classList.remove('hidden'));
                const tableChevrons = document.querySelectorAll('[id^="chevron-"]');
                tableChevrons.forEach(ch => {
                    ch.classList.add('rotate-90');
                    ch.style.transform = 'rotate(90deg)';
                });
            }
        })
        .catch(err => {
            console.error('Error refreshing project detail page:', err);
        });
}

function toggleSubtasks(phaseId, button) {
    console.log('toggleSubtasks called:', phaseId, button);
    const box = document.getElementById(`subtask-list-box-${phaseId}`);
    const chevron = (button && (button.querySelector('svg') || button.querySelector('[data-lucide]'))) || document.getElementById(`chevron-${phaseId}`);
    console.log('Found elements:', { box, chevron });

    let shouldHide = false;
    if (expandedPhases.has(phaseId)) {
        expandedPhases.delete(phaseId);
        shouldHide = true;
    } else {
        expandedPhases.add(phaseId);
    }

    if (box) {
        if (shouldHide) {
            box.classList.add('hidden');
        } else {
            box.classList.remove('hidden');
        }
    }
    if (chevron) {
        if (shouldHide) {
            chevron.classList.remove('rotate-90');
            chevron.style.transform = 'rotate(0deg)';
        } else {
            chevron.classList.add('rotate-90');
            chevron.style.transform = 'rotate(90deg)';
        }
    }
}

function toggleSingleTimelineSubtask(phaseId, button) {
    console.log('toggleSingleTimelineSubtask called:', phaseId, button);
    const rows = document.querySelectorAll(`.timeline-subtask-row-${phaseId}`);
    const chevron = (button && (button.querySelector('svg') || button.querySelector('[data-lucide]'))) || document.getElementById(`timeline-chevron-${phaseId}`);
    console.log('Found timeline elements:', { rowsCount: rows.length, chevron });

    let shouldHide = false;
    if (expandedTimelinePhases.has(phaseId)) {
        expandedTimelinePhases.delete(phaseId);
        shouldHide = true;
    } else {
        expandedTimelinePhases.add(phaseId);
    }

    rows.forEach(row => {
        if (shouldHide) {
            row.classList.add('hidden');
        } else {
            row.classList.remove('hidden');
        }
    });
    if (chevron) {
        if (shouldHide) {
            chevron.style.transform = 'rotate(0deg)';
        } else {
            chevron.style.transform = 'rotate(90deg)';
        }
    }
}

function toggleAllTimelineSubtasks(button) {
    const btn = button || document.getElementById('toggle-all-timeline-subtasks-btn');
    const subRows = document.querySelectorAll('[class*="timeline-subtask-row-"]');
    const allChevrons = document.querySelectorAll('[id^="timeline-chevron-"]');

    if (globalTimelineSubtasksVisible) {
        // Hide all
        subRows.forEach(row => row.classList.add('hidden'));
        allChevrons.forEach(chevron => chevron.style.transform = 'rotate(0deg)');
        if (btn) {
            btn.innerHTML = 'Show Subtasks';
        }
        globalTimelineSubtasksVisible = false;
        expandedTimelinePhases.clear();
    } else {
        // Show all
        subRows.forEach(row => row.classList.remove('hidden'));
        allChevrons.forEach(chevron => chevron.style.transform = 'rotate(90deg)');
        if (btn) {
            btn.innerHTML = 'Hide Subtasks';
        }
        globalTimelineSubtasksVisible = true;

        // Add all to expanded set
        allChevrons.forEach(chevron => {
            const id = chevron.id.replace('timeline-chevron-', '');
            expandedTimelinePhases.add(parseInt(id, 10));
        });
    }
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function toggleAllTableSubtasks(button) {
    const btn = button || document.getElementById('toggle-all-table-subtasks-btn-2') || document.getElementById('toggle-all-table-subtasks-btn');
    const subBoxes = document.querySelectorAll('[class*="subtask-list-box-"]');
    const allChevrons = document.querySelectorAll('[id^="chevron-"]');

    if (globalTableSubtasksVisible) {
        // Hide all
        subBoxes.forEach(box => box.classList.add('hidden'));
        allChevrons.forEach(chevron => {
            chevron.classList.remove('rotate-90');
            chevron.style.transform = 'rotate(0deg)';
        });
        if (btn) {
            btn.innerHTML = 'Show Subtasks';
        }
        globalTableSubtasksVisible = false;
        expandedPhases.clear();
    } else {
        // Show all
        subBoxes.forEach(box => box.classList.remove('hidden'));
        allChevrons.forEach(chevron => {
            chevron.classList.add('rotate-90');
            chevron.style.transform = 'rotate(90deg)';
        });
        if (btn) {
            btn.innerHTML = 'Hide Subtasks';
        }
        globalTableSubtasksVisible = true;

        // Add all to expanded set
        allChevrons.forEach(chevron => {
            const id = chevron.id.replace('chevron-', '');
            expandedPhases.add(parseInt(id, 10));
        });
    }
}

function openCreatePhaseModal() {
    document.getElementById('create-phase-modal').classList.remove('hidden');
}

function openEditPhaseModal(ph) {
    document.getElementById('edit-phase-form').action = window.Nexus.baseUrl + '/project/' + window.NexusDetailConfig.projectId + '/phase/update/' + ph.id;
    document.getElementById('edit-phase-name').value = ph.name;
    document.getElementById('edit-phase-description').value = ph.description || '';
    document.getElementById('edit-phase-start').value = ph.start;
    document.getElementById('edit-phase-end').value = ph.end;
    document.getElementById('edit-phase-status').value = ph.status;
    document.getElementById('edit-phase-modal').classList.remove('hidden');
}

function openCreateSubtaskModal(phaseId) {
    document.getElementById('create-subtask-form').action = window.Nexus.baseUrl + '/project/' + window.NexusDetailConfig.projectId + '/phase/' + phaseId + '/subtask/create';
    document.getElementById('create-subtask-form').reset();
    const md = document.getElementById('create-subtask-mandays');
    const th = document.getElementById('create-subtask-hours');
    if (md) md.value = "0.00";
    if (th) th.value = "0.00";
    
    const startEl = document.getElementById('create-subtask-start');
    if (startEl) {
        startEl.value = new Date().toISOString().split('T')[0];
        // Attach listener if not already done
        if (!startEl.dataset.listenerAttached) {
            startEl.addEventListener('input', () => updateCalculatedEndDate('create'));
            startEl.dataset.listenerAttached = "true";
        }
    }
    updateCalculatedEndDate('create');
    
    document.getElementById('create-subtask-modal').classList.remove('hidden');
}

function openEditSubtaskModal(sub, phaseId) {
    document.getElementById('edit-subtask-form').action = window.Nexus.baseUrl + '/project/' + window.NexusDetailConfig.projectId + '/phase/' + phaseId + '/subtask/update/' + sub.id;
    document.getElementById('edit-subtask-name').value = sub.name;
    document.getElementById('edit-subtask-description').value = sub.description || '';
    
    const startEl = document.getElementById('edit-subtask-start');
    if (startEl) {
        startEl.value = sub.start;
        // Attach listener if not already done
        if (!startEl.dataset.listenerAttached) {
            startEl.addEventListener('input', () => updateCalculatedEndDate('edit'));
            startEl.dataset.listenerAttached = "true";
        }
    }
    
    const endEl = document.getElementById('edit-subtask-end');
    if (endEl) endEl.value = sub.end || '';
    
    document.getElementById('edit-subtask-resource-id').value = sub.resource_id || '';
    document.getElementById('edit-subtask-status').value = sub.status;
    
    const md = document.getElementById('edit-subtask-mandays');
    const th = document.getElementById('edit-subtask-hours');
    if (md) {
        const manDays = parseFloat(sub.man_days) || 0.00;
        md.value = manDays.toFixed(2);
    }
    if (th) {
        const taskHours = parseFloat(sub.task_hours) || 0.00;
        th.value = taskHours.toFixed(2);
    }
    
    updateCalculatedEndDate('edit');
    
    document.getElementById('edit-subtask-modal').classList.remove('hidden');
}

function addWorkDaysJS(startDateStr, daysToAdd, workDaysPerWeek) {
    if (!startDateStr) return '';
    const current = new Date(startDateStr);
    if (isNaN(current.getTime())) return '';
    
    let days = parseFloat(daysToAdd);
    if (isNaN(days) || days <= 0) {
        return startDateStr;
    }
    
    const duration = Math.ceil(days);
    let added = 0;
    const target = duration - 1;
    
    while (added < target) {
        current.setDate(current.getDate() + 1);
        const w = current.getDay(); // 0 = Sunday, 6 = Saturday
        if (workDaysPerWeek === 5) {
            if (w === 0 || w === 6) {
                continue;
            }
        } else if (workDaysPerWeek === 6) {
            if (w === 0) {
                continue;
            }
        }
        added++;
    }
    
    const yyyy = current.getFullYear();
    const mm = String(current.getMonth() + 1).padStart(2, '0');
    const dd = String(current.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

function updateCalculatedEndDate(prefix) {
    const startInput = document.getElementById(`${prefix}-subtask-start`);
    const manDaysInput = document.getElementById(`${prefix}-subtask-mandays`);
    const endInput = document.getElementById(`${prefix}-subtask-end`);
    
    if (!startInput || !manDaysInput || !endInput) return;
    
    const startDateVal = startInput.value;
    const manDaysVal = parseFloat(manDaysInput.value) || 0;
    const workDaysPerWeek = (window.NexusDetailConfig && window.NexusDetailConfig.workDaysPerWeek) || 5;
    
    endInput.value = addWorkDaysJS(startDateVal, manDaysVal, workDaysPerWeek);
}

function syncSubtaskHours(manDaysId, taskHoursId) {
    const dailyHours = window.dailyWorkHours || 8;
    const manDaysInput = document.getElementById(manDaysId);
    const taskHoursInput = document.getElementById(taskHoursId);
    if (manDaysInput && taskHoursInput) {
        const manDays = parseFloat(manDaysInput.value);
        if (!isNaN(manDays)) {
            taskHoursInput.value = (manDays * dailyHours).toFixed(2);
        } else {
            taskHoursInput.value = '';
        }
        const prefix = manDaysId.split('-')[0];
        updateCalculatedEndDate(prefix);
    }
}

function syncSubtaskManDays(taskHoursId, manDaysId) {
    const dailyHours = window.dailyWorkHours || 8;
    const taskHoursInput = document.getElementById(taskHoursId);
    const manDaysInput = document.getElementById(manDaysId);
    if (taskHoursInput && manDaysInput) {
        const taskHours = parseFloat(taskHoursInput.value);
        if (!isNaN(taskHours)) {
            manDaysInput.value = (taskHours / dailyHours).toFixed(2);
        } else {
            manDaysInput.value = '';
        }
        const prefix = manDaysId.split('-')[0];
        updateCalculatedEndDate(prefix);
    }
}

function openCreateDependencyModal() {
    document.getElementById('create-dependency-modal').classList.remove('hidden');
}

function openCreateEscalationModal() {
    document.getElementById('create-escalation-modal').classList.remove('hidden');
}

function openCreateRiskModal() {
    document.getElementById('create-risk-modal').classList.remove('hidden');
}

function openCreateActionModal() {
    document.getElementById('create-action-modal').classList.remove('hidden');
}

function openEditHealthModal() {
    const healthEl = document.getElementById('health-display-text');
    const descEl = document.getElementById('description-display-text');
    const healthInput = document.getElementById('edit-health-input');
    const descInput = document.getElementById('edit-description-input');

    if (healthEl && healthInput) {
        healthInput.value = healthEl.getAttribute('data-value') || '';
    }
    if (descEl && descInput) {
        descInput.value = descEl.innerText || '';
    }
    const modal = document.getElementById('edit-health-modal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('hidden');
    if (id === 'create-dependency-modal') {
        const container = document.getElementById('custom-dependency-type-container');
        const input = document.getElementById('custom-dependency-type-input');
        const select = document.getElementById('dependency-type-select');
        const projSelect = document.getElementById('dependency-project-select');
        const nameContainer = document.getElementById('custom-dep-name-container');
        const nameInput = document.getElementById('custom-dep-name-input');

        if (container) container.classList.add('hidden');
        if (input) {
            input.value = '';
            input.removeAttribute('required');
        }
        if (select) select.value = 'depends-on';
        if (projSelect) {
            projSelect.value = '';
            projSelect.setAttribute('required', 'required');
        }
        if (nameContainer) nameContainer.classList.add('hidden');
        if (nameInput) {
            nameInput.value = '';
            nameInput.removeAttribute('required');
        }
    }
}

function openEditEscalationModal(esc) {
    document.getElementById('edit-escalation-form').action = window.Nexus.baseUrl + '/project/' + window.NexusDetailConfig.projectId + '/escalation/update/' + esc.id;
    document.getElementById('edit-esc-level').value = esc.level || '1';
    document.getElementById('edit-esc-to').value = esc.to_recipient || '';
    document.getElementById('edit-esc-note').value = esc.note || '';
    document.getElementById('edit-esc-status').value = esc.status || 'active';
    document.getElementById('edit-esc-reason').value = esc.reason || '';
    document.getElementById('edit-escalation-modal').classList.remove('hidden');
}

function openEditRiskModal(risk) {
    document.getElementById('edit-risk-form').action = window.Nexus.baseUrl + '/project/' + window.NexusDetailConfig.projectId + '/risk/update/' + risk.id;
    document.getElementById('edit-risk-title').value = risk.title || '';
    document.getElementById('edit-risk-severity').value = risk.severity || 'low';
    document.getElementById('edit-risk-type').value = risk.type || 'risk';
    document.getElementById('edit-risk-owner').value = risk.owner || '';
    document.getElementById('edit-risk-mitigation').value = risk.mitigation || '';
    document.getElementById('edit-risk-status').value = risk.status || 'active';
    document.getElementById('edit-risk-reason').value = risk.reason || '';
    document.getElementById('edit-risk-modal').classList.remove('hidden');
}

// Click outside to close modals
window.onclick = function (event) {
    const modals = ['create-phase-modal', 'edit-phase-modal', 'create-dependency-modal', 'create-escalation-modal', 'create-risk-modal', 'create-action-modal', 'edit-health-modal', 'edit-subtask-modal', 'edit-escalation-modal', 'edit-risk-modal', 'confirm-action-modal'];
    modals.forEach(id => {
        const modal = document.getElementById(id);
        if (modal && event.target === modal) {
            modal.classList.add('hidden');
        }
    });
}

function addPhaseSubtaskRow() {
    const container = document.getElementById('phase-subtasks-container');
    if (!container) return;
    const rowId = 'subtask-row-' + Date.now();

    let statusesOptionsHtml = '';
    if (window.NexusDetailConfig && window.NexusDetailConfig.statusLabels) {
        Object.entries(window.NexusDetailConfig.statusLabels).forEach(([slug, label]) => {
            statusesOptionsHtml += `<option value="${slug}">${label}</option>`;
        });
    }

    const html = `
        <div id="${rowId}" class="border border-dashed border-foreground/60 p-2 relative flex flex-col gap-2 bg-background/50">
            <button type="button" onclick="this.parentElement.remove()" class="absolute right-1 top-1 text-[10px] font-black text-destructive hover:opacity-85">✕</button>
            <div class="flex flex-col gap-1">
                <span class="mono text-[8px] uppercase tracking-wider text-muted-foreground">Subtask Name</span>
                <input name="subtask_names[]" required class="w-full border border-foreground bg-background px-1.5 py-0.5 mono text-xs focus:outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-1.5">
                <div class="flex flex-col gap-1">
                    <span class="mono text-[8px] uppercase tracking-wider text-muted-foreground">Start</span>
                    <input type="date" name="subtask_starts[]" value="${new Date().toISOString().split('T')[0]}" required class="w-full border border-foreground bg-background px-1 py-0.5 mono text-xs focus:outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="mono text-[8px] uppercase tracking-wider text-muted-foreground">End</span>
                    <input type="date" name="subtask_ends[]" value="${new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]}" required class="w-full border border-foreground bg-background px-1 py-0.5 mono text-xs focus:outline-none" />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[8px] uppercase tracking-wider text-muted-foreground">Status</span>
                <select name="subtask_statuses[]" class="w-full border border-foreground bg-background px-1 py-0.5 mono text-xs focus:outline-none uppercase">
                    ${statusesOptionsHtml}
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <span class="mono text-[8px] uppercase tracking-wider text-muted-foreground">Description</span>
                <textarea name="subtask_descriptions[]" rows="1" class="w-full border border-foreground bg-background px-1.5 py-0.5 mono text-[10px] focus:outline-none"></textarea>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
}

// AJAX Form submission
function submitAjaxForm(formId, url) {
    const form = document.getElementById(formId);
    if (!form) return;
    const formData = new FormData(form);

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                form.reset();
                const subtasksContainer = form.querySelector('#phase-subtasks-container');
                if (subtasksContainer) subtasksContainer.innerHTML = '';
                const modal = form.closest('[id$="-modal"]');
                if (modal) modal.classList.add('hidden');
                refreshBlocks();
            } else {
                alert(data.message || 'An error occurred.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred.');
        });
}

function assignPhaseResource(phaseId, resourceId) {
    const formData = new FormData();
    formData.append('resource_id', resourceId);
    fetch(window.Nexus.baseUrl + '/project/' + window.NexusDetailConfig.projectId + '/phase/assign-resource/' + phaseId, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                refreshBlocks();
            } else {
                alert(data.message || 'An error occurred.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred.');
        });
}

function assignSubtaskResource(subtaskId, resourceId) {
    const formData = new FormData();
    formData.append('resource_id', resourceId);
    fetch(window.Nexus.baseUrl + '/project/' + window.NexusDetailConfig.projectId + '/phase/subtask/assign-resource/' + subtaskId, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                refreshBlocks();
            } else {
                alert(data.message || 'An error occurred.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred.');
        });
}

// AJAX Delete helpers
function deleteAjaxItem(url) {
    if (!confirm('Are you sure you want to delete this item?')) return;
    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                refreshBlocks();
            } else {
                alert(data.message || 'An error occurred.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred.');
        });
}

// Confirm Modal Callback Helpers
let confirmActionCallback = null;

function showConfirmModal(title, bodyText, onConfirm) {
    const modal = document.getElementById('confirm-action-modal');
    const titleEl = document.getElementById('confirm-modal-title');
    const bodyEl = document.getElementById('confirm-modal-body');
    const yesBtn = document.getElementById('confirm-modal-yes-btn');
    
    if (modal && titleEl && bodyEl && yesBtn) {
        titleEl.innerText = title;
        bodyEl.innerText = bodyText;
        confirmActionCallback = onConfirm;
        modal.classList.remove('hidden');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const yesBtn = document.getElementById('confirm-modal-yes-btn');
    if (yesBtn) {
        yesBtn.addEventListener('click', () => {
            if (confirmActionCallback) {
                confirmActionCallback();
                confirmActionCallback = null;
            }
            closeModal('confirm-action-modal');
        });
    }
});

// AJAX Resource unassignment
function unassignResource(projectId, resourceId) {
    showConfirmModal(
        'Confirm Resource Removal',
        'Are you sure you want to unassign this resource from this project?',
        () => {
            const formData = new FormData();
            formData.append('resource_id', resourceId);
            fetch(window.Nexus.baseUrl + '/project/' + projectId + '/resources/unassign', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        refreshBlocks();
                    } else {
                        alert(data.message || 'An error occurred.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred.');
                });
        }
    );
}

// AJAX Squad Submit with Confirm Modal
function handleSquadSubmit(form) {
    const squadSelect = form.querySelector('select[name="squad_id"]');
    if (!squadSelect) return;
    const originalSquad = squadSelect.getAttribute('data-original') || '';
    const selectedSquad = squadSelect.value;
    
    if (selectedSquad === '') {
        showConfirmModal(
            'Confirm Squad Removal',
            'Are you sure you want to remove the assigned squad from this project?',
            () => {
                submitAjaxForm(form.id, window.Nexus.baseUrl + '/project/' + window.NexusDetailConfig.projectId + '/squad/assign');
            }
        );
    } else if (originalSquad !== '' && selectedSquad !== originalSquad) {
        showConfirmModal(
            'Confirm Squad Change',
            'Changing the squad will automatically assign all members of the new squad. Are you sure you want to change the squad?',
            () => {
                submitAjaxForm(form.id, window.Nexus.baseUrl + '/project/' + window.NexusDetailConfig.projectId + '/squad/assign');
            }
        );
    } else {
        submitAjaxForm(form.id, window.Nexus.baseUrl + '/project/' + window.NexusDetailConfig.projectId + '/squad/assign');
    }
}

function toggleSubtaskStatus(projectId, phaseId, subtaskId) {
    fetch(window.Nexus.baseUrl + '/project/' + projectId + '/phase/' + phaseId + '/subtask/toggle/' + subtaskId, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            refreshBlocks();
        } else {
            alert(data.message || 'An error occurred.');
        }
    })
    .catch(err => {
        console.error('Error toggling subtask status:', err);
        alert('An error occurred.');
    });
}

// AJAX Snappy toggle action item
function toggleActionItem(projectId, actionId, button) {
    fetch(window.Nexus.baseUrl + '/project/' + projectId + '/toggle-action/' + actionId, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const titleSpan = button.nextElementSibling;
                if (data.done) {
                    button.classList.add('bg-primary');
                    button.classList.remove('bg-background');
                    button.innerHTML = '<span class="mono text-[10px] font-black text-primary-foreground">X</span>';
                    titleSpan.classList.add('line-through', 'text-muted-foreground');
                } else {
                    button.classList.remove('bg-primary');
                    button.classList.add('bg-background');
                    button.innerHTML = '';
                    titleSpan.classList.remove('line-through', 'text-muted-foreground');
                }
                refreshBlocks(); // keep everything synced (e.g. timeline etc)
            }
        })
        .catch(err => {
            console.error('Error toggling action item:', err);
        });
}

function syncResourceId(input) {
    const val = input.value.trim();
    const list = document.getElementById('resource-options');
    const hiddenInput = document.getElementById('resource-id-hidden');
    let foundId = '';
    if (list) {
        const options = list.options;
        for (let i = 0; i < options.length; i++) {
            if (options[i].value.trim() === val) {
                foundId = options[i].getAttribute('data-id');
                break;
            }
        }
    }
    hiddenInput.value = foundId;

    if (val !== '' && foundId === '') {
        input.setCustomValidity('Please select a valid resource from the list.');
    } else {
        input.setCustomValidity('');
    }
}

function toggleCustomDependencyType(select) {
    const container = document.getElementById('custom-dependency-type-container');
    const input = document.getElementById('custom-dependency-type-input');
    const projSelect = document.getElementById('dependency-project-select');
    const nameContainer = document.getElementById('custom-dep-name-container');
    const nameInput = document.getElementById('custom-dep-name-input');

    if (select.value === 'others') {
        container.classList.remove('hidden');
        input.setAttribute('required', 'required');

        if (projSelect) projSelect.removeAttribute('required');
        if (nameContainer) nameContainer.classList.remove('hidden');
        if (nameInput) {
            nameInput.setAttribute('required', 'required');
            nameInput.focus();
        }
    } else {
        container.classList.add('hidden');
        input.removeAttribute('required');
        input.value = '';

        if (projSelect) projSelect.setAttribute('required', 'required');
        if (nameContainer) nameContainer.classList.add('hidden');
        if (nameInput) {
            nameInput.removeAttribute('required');
            nameInput.value = '';
        }
    }
}

// HTML5 Drag & Drop Reordering logic
let dragSrcEl = null;

function initDragAndDrop() {
    if (document.body.classList.contains('role-viewer')) {
        const rows = document.querySelectorAll('.phase-drag-row');
        rows.forEach(row => {
            row.setAttribute('draggable', 'false');
        });
        return;
    }
    const rows = document.querySelectorAll('.phase-drag-row');
    rows.forEach(row => {
        row.addEventListener('dragstart', handleDragStart, false);
        row.addEventListener('dragenter', handleDragEnter, false);
        row.addEventListener('dragover', handleDragOver, false);
        row.addEventListener('dragleave', handleDragLeave, false);
        row.addEventListener('drop', handleDrop, false);
        row.addEventListener('dragend', handleDragEnd, false);
    });
}

function handleDragStart(e) {
    this.classList.add('opacity-50');
    dragSrcEl = this;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', this.getAttribute('data-id'));
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    this.classList.add('bg-secondary/40');
    return false;
}

function handleDragEnter(e) { }

function handleDragLeave(e) {
    this.classList.remove('bg-secondary/40');
}

function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    this.classList.remove('bg-secondary/40');

    if (dragSrcEl !== this) {
        const dragId = e.dataTransfer.getData('text/plain');
        const dropId = this.getAttribute('data-id');

        const rows = Array.from(document.querySelectorAll('.phase-drag-row'));
        const order = rows.map(r => r.getAttribute('data-id'));

        const dragIndex = order.indexOf(dragId);
        const dropIndex = order.indexOf(dropId);

        // Re-arrange array
        order.splice(dragIndex, 1);
        order.splice(dropIndex, 0, dragId);

        fetch(window.Nexus.baseUrl + '/project/' + window.NexusDetailConfig.projectId + '/phases/reorder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(order)
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    refreshBlocks();
                } else {
                    alert(data.message || 'Failed to reorder phases.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred.');
            });
    }
    return false;
}

function handleDragEnd(e) {
    this.classList.remove('opacity-50');
    const rows = document.querySelectorAll('.phase-drag-row');
    rows.forEach(row => {
        row.classList.remove('bg-secondary/40');
    });
}

// Initialize drag & drop on load
document.addEventListener('DOMContentLoaded', () => {
    initDragAndDrop();
});


// ==================== ECHO TERMINAL LOGIC ====================
let echoOpen = false;
let snakeGameActive = false;
let snakeGameInterval = null;
const echoTemplates = [
    "Create a phase called [name] for start date on [start date] to end date on [end date] with status [status]",
    "Create a subtask on [phase] called [name] for start date on [start date] with [man days] mandays assigned to [resource] with status [status]",
    "Update [select phase/subtask] start date on [start date] with [man days] mandays assigned to [resource] with status [status]",
    "Update [select phase/subtask] with status [status]",
    "Assign resource [resource] to subtask [#id]",
    "Unassign resource from subtask [#id]"
];

let currentSuggestionIndex = -1;
let currentSuggestions = [];

function toggleEchoTerminal() {
    const win = document.getElementById('echo-terminal-window');
    if (!win) return;
    echoOpen = !echoOpen;
    if (echoOpen) {
        win.classList.remove('hidden');
        const input = document.getElementById('echo-terminal-input');
        if (input) {
            input.focus();
            updateSuggestions();
        }
    } else {
        win.classList.add('hidden');
    }
}

function logToEcho(msg, type = 'info') {
    const out = document.getElementById('echo-terminal-output');
    if (!out) return;
    const div = document.createElement('div');
    if (type === 'success') {
        div.className = 'text-green-300 font-bold';
    } else if (type === 'error') {
        div.className = 'text-red-500 font-bold';
    } else if (type === 'command') {
        div.className = 'text-green-200';
    } else {
        div.className = 'text-green-400';
    }
    div.innerText = (type === 'command' ? '> ' : '') + msg;
    out.appendChild(div);
    out.scrollTop = out.scrollHeight;
}

function handleEchoSubmit(e) {
    e.preventDefault();
    if (snakeGameActive) return;
    const input = document.getElementById('echo-terminal-input');
    if (!input) return;
    const cmd = input.value.trim();
    if (cmd === '') return;

    logToEcho(cmd, 'command');
    input.value = '';
    document.getElementById('echo-autocomplete-list').classList.add('hidden');

    if (cmd.toLowerCase() === 'snake') {
        startSnakeGame();
        return;
    }

    if (cmd.toLowerCase() === 'help') {
        logToEcho("Valid templates:", 'info');
        echoTemplates.forEach(t => logToEcho("  " + t, 'info'));
        logToEcho("Chaining Multiple Commands:", 'info');
        logToEcho("  Separate commands using a semicolon ';' to run them in sequence.", 'info');
        logToEcho("  Example: Command1 ; Command2 ; Command3", 'info');
        logToEcho("  Note: Commands execute inside a transaction. If any command fails, all will roll back.", 'info');
        return;
    }

    if (cmd.toLowerCase() === 'clear') {
        const out = document.getElementById('echo-terminal-output');
        if (out) out.innerHTML = '<div class="text-green-600">// terminal cleared</div>';
        return;
    }

    // Send command to backend
    const formData = new FormData();
    formData.append('command', cmd);

    fetch(window.Nexus.baseUrl + '/project/' + window.NexusDetailConfig.projectId + '/echo', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                logToEcho(data.message || 'Command executed successfully.', 'success');
                refreshBlocks();
            } else {
                logToEcho('[Error] ' + (data.message || 'Execution failed.'), 'error');
            }
        })
        .catch(err => {
            console.error(err);
            logToEcho('[Error] Connection failed.', 'error');
        });
}

function getActiveToken(inputVal, cursorPosition) {
    const leftText = inputVal.substring(0, cursorPosition);
    const lastOpenBracket = leftText.lastIndexOf('[');
    if (lastOpenBracket !== -1) {
        const rightText = leftText.substring(lastOpenBracket);
        if (rightText.indexOf(']') === -1) {
            const bracketContent = rightText.substring(1);
            return {
                start: lastOpenBracket,
                end: cursorPosition,
                content: bracketContent
            };
        }
    }
    return null;
}

function updateSuggestions() {
    const input = document.getElementById('echo-terminal-input');
    const listContainer = document.getElementById('echo-autocomplete-list');
    if (!input || !listContainer) return;

    const inputVal = input.value;
    const pos = input.selectionStart;

    const token = getActiveToken(inputVal, pos);
    let suggestions = [];
    let isBracket = false;

    if (token) {
        isBracket = true;
        const query = token.content.toLowerCase();
        if (token.content.startsWith('status')) {
            const search = query.replace('status', '').trim();
            suggestions = window.NexusDetailConfig.validStatuses.filter(s => s.toLowerCase().includes(search));
        } else if (token.content.startsWith('phase')) {
            const search = query.replace('phase', '').trim();
            suggestions = window.NexusDetailConfig.existingPhases.filter(p => p.toLowerCase().includes(search));
        } else if (token.content.startsWith('select phase/subtask')) {
            const search = query.replace('select phase/subtask', '').trim();
            const combined = [...window.NexusDetailConfig.existingPhases, ...window.NexusDetailConfig.existingSubtasks];
            suggestions = combined.filter(item => item.toLowerCase().includes(search));
        } else if (token.content.startsWith('start date') || token.content.startsWith('end date')) {
            const search = token.content.includes('start date') ? query.replace('start date', '').trim() : query.replace('end date', '').trim();

            const getDateString = (daysOffset, monthsOffset = 0) => {
                const d = new Date();
                if (daysOffset) d.setDate(d.getDate() + daysOffset);
                if (monthsOffset) d.setMonth(d.getMonth() + monthsOffset);
                const dd = String(d.getDate()).padStart(2, '0');
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const yyyy = d.getFullYear();
                return `${dd}-${mm}-${yyyy}`;
            };

            const dateOpts = [
                { label: `Today (${getDateString(0)})`, value: getDateString(0) },
                { label: `Tomorrow (${getDateString(1)})`, value: getDateString(1) },
                { label: `Next Week (${getDateString(7)})`, value: getDateString(7) },
                { label: `In 2 Weeks (${getDateString(14)})`, value: getDateString(14) },
                { label: `In 1 Month (${getDateString(0, 1)})`, value: getDateString(0, 1) },
                { label: `In 2 Months (${getDateString(0, 2)})`, value: getDateString(0, 2) },
                { label: `In 3 Months (${getDateString(0, 3)})`, value: getDateString(0, 3) }
            ];

            suggestions = dateOpts.filter(opt => opt.label.toLowerCase().includes(search));
        } else if (token.content.startsWith('resource')) {
            const search = query.replace('resource', '').trim();
            const resources = [...(window.NexusDetailConfig.existingResources || []), 'unassigned'];
            suggestions = resources.filter(r => r.toLowerCase().includes(search));
        } else if (token.content.startsWith('#') || token.content.startsWith('id')) {
            const search = query.replace('#', '').replace('id', '').trim();
            suggestions = (window.NexusDetailConfig.existingSubtaskIds || []).filter(item => item.label.toLowerCase().includes(search));
        } else {
            suggestions = [];
        }
    } else {
        if (inputVal.trim() !== '') {
            suggestions = echoTemplates.filter(t => t.toLowerCase().includes(inputVal.toLowerCase()));
        } else {
            suggestions = [...echoTemplates];
        }
    }

    currentSuggestions = suggestions;

    if (suggestions.length > 0) {
        listContainer.innerHTML = '';
        listContainer.classList.remove('hidden');
        suggestions.forEach((s, idx) => {
            const label = typeof s === 'object' ? s.label : s;
            const div = document.createElement('div');
            div.className = 'px-3 py-1.5 cursor-pointer hover:bg-green-950 flex items-center justify-between text-[11px] ' +
                (idx === currentSuggestionIndex ? 'bg-green-900 text-green-300 font-bold' : '');
            div.innerHTML = `<span>${label}</span> <span class="text-[8px] text-green-600 uppercase font-mono">${isBracket ? 'Value' : 'Template'}</span>`;
            div.onclick = () => selectSuggestion(s, token);
            listContainer.appendChild(div);
        });
    } else {
        listContainer.classList.add('hidden');
        currentSuggestionIndex = -1;
    }
}

function selectSuggestion(s, token) {
    const input = document.getElementById('echo-terminal-input');
    if (!input) return;
    const inputVal = input.value;
    const valToInsert = typeof s === 'object' ? s.value : s;

    if (token) {
        const before = inputVal.substring(0, token.start);
        const nextCloseBracket = inputVal.indexOf(']', token.start);
        const endPos = (nextCloseBracket !== -1 && nextCloseBracket >= input.selectionStart) ? nextCloseBracket + 1 : input.selectionStart;
        const afterBracket = inputVal.substring(endPos);

        input.value = before + valToInsert + afterBracket;
        input.selectionStart = input.selectionEnd = before.length + valToInsert.length;
    } else {
        input.value = valToInsert;
        const firstBracket = valToInsert.indexOf('[');
        if (firstBracket !== -1) {
            input.selectionStart = firstBracket + 1;
            input.selectionEnd = valToInsert.indexOf(']', firstBracket);
        }
    }
    input.focus();
    currentSuggestionIndex = -1;
    updateSuggestions();
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('echo-terminal-input');
    if (input) {
        input.addEventListener('input', () => {
            currentSuggestionIndex = -1;
            updateSuggestions();
        });
        input.addEventListener('keyup', () => {
            updateSuggestions();
        });
        input.addEventListener('click', () => {
            updateSuggestions();
        });

        input.addEventListener('keydown', (e) => {
            if (snakeGameActive) {
                e.preventDefault();
                return;
            }
            const listContainer = document.getElementById('echo-autocomplete-list');
            const listVisible = listContainer && !listContainer.classList.contains('hidden');

            if (listVisible && currentSuggestions.length > 0) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    currentSuggestionIndex = (currentSuggestionIndex + 1) % currentSuggestions.length;
                    updateSuggestions();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    currentSuggestionIndex = (currentSuggestionIndex - 1 + currentSuggestions.length) % currentSuggestions.length;
                    updateSuggestions();
                } else if (e.key === 'Enter' && currentSuggestionIndex !== -1) {
                    e.preventDefault();
                    const token = getActiveToken(input.value, input.selectionStart);
                    selectSuggestion(currentSuggestions[currentSuggestionIndex], token);
                }
            }

            if (e.key === 'Tab') {
                if (listVisible && currentSuggestions.length > 0 && currentSuggestionIndex !== -1) {
                    e.preventDefault();
                    const token = getActiveToken(input.value, input.selectionStart);
                    selectSuggestion(currentSuggestions[currentSuggestionIndex], token);
                } else {
                    // Jump to next bracket
                    const val = input.value;
                    const pos = input.selectionStart;
                    const nextOpen = val.indexOf('[', pos);
                    if (nextOpen !== -1) {
                        e.preventDefault();
                        const nextClose = val.indexOf(']', nextOpen);
                        input.selectionStart = nextOpen + 1;
                        input.selectionEnd = nextClose !== -1 ? nextClose : val.length;
                        updateSuggestions();
                    }
                }
            }
        });
    }
});

// Zoom functions removed

function toggleLegend() {
    const content = document.getElementById('legend-content');
    const icon = document.getElementById('legend-toggle-icon');
    if (content && icon) {
        const isHidden = content.classList.contains('hidden');
        if (isHidden) {
            content.classList.remove('hidden');
            icon.innerText = '[-] Collapse';
        } else {
            content.classList.add('hidden');
            icon.innerText = '[+] Expand';
        }
    }
}

function startSnakeGame() {
    const output = document.getElementById('echo-terminal-output');
    if (!output) return;

    // Save current output content to restore later
    const originalContent = output.innerHTML;
    
    // Clear and insert canvas
    output.innerHTML = `
        <div class="text-center font-mono space-y-2 select-none h-full flex flex-col items-center justify-center">
            <div class="text-green-300 font-bold uppercase tracking-wider text-base">▣ NOKIA SNAKE ▣</div>
            <div class="text-green-500 text-[11px]">// Use Arrow keys to navigate. Press ESC to quit.</div>
            <canvas id="snake-canvas" width="400" height="280" class="border-2 border-green-500 bg-black mx-auto mt-2"></canvas>
            <div id="snake-score" class="text-green-400 font-bold text-sm">SCORE: 0</div>
            <div id="snake-status" class="text-green-500 text-xs mt-1 min-h-[32px]"></div>
        </div>
    `;

    const canvas = document.getElementById('snake-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const scoreEl = document.getElementById('snake-score');
    const statusEl = document.getElementById('snake-status');

    const gridSize = 10;
    const cols = canvas.width / gridSize;
    const rows = canvas.height / gridSize;

    let snake = [
        { x: 5, y: 10 },
        { x: 4, y: 10 },
        { x: 3, y: 10 }
    ];
    let dx = 1;
    let dy = 0;
    let apple = getRandomApplePosition();
    let score = 0;
    let gameOver = false;

    function getRandomApplePosition() {
        let rx, ry;
        let onSnake;
        do {
            rx = Math.floor(Math.random() * cols);
            ry = Math.floor(Math.random() * rows);
            onSnake = snake.some(part => part.x === rx && part.y === ry);
        } while (onSnake);
        return { x: rx, y: ry };
    }

    function handleKeyDown(e) {
        if (!snakeGameActive) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            exitSnakeGame();
            return;
        }

        if (gameOver) {
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                restartGame();
            }
            return;
        }

        if (e.key === 'ArrowUp' && dy === 0) {
            e.preventDefault();
            dx = 0; dy = -1;
        } else if (e.key === 'ArrowDown' && dy === 0) {
            e.preventDefault();
            dx = 0; dy = 1;
        } else if (e.key === 'ArrowLeft' && dx === 0) {
            e.preventDefault();
            dx = -1; dy = 0;
        } else if (e.key === 'ArrowRight' && dx === 0) {
            e.preventDefault();
            dx = 1; dy = 0;
        }
    }

    function restartGame() {
        snake = [
            { x: 5, y: 10 },
            { x: 4, y: 10 },
            { x: 3, y: 10 }
        ];
        dx = 1;
        dy = 0;
        apple = getRandomApplePosition();
        score = 0;
        gameOver = false;
        if (scoreEl) scoreEl.innerText = "SCORE: 0";
        if (statusEl) statusEl.innerText = "";
    }

    function exitSnakeGame() {
        snakeGameActive = false;
        clearInterval(snakeGameInterval);
        window.removeEventListener('keydown', handleKeyDown);
        output.innerHTML = originalContent;
        logToEcho("Exited Nokia Snake. Welcome back.", 'info');
        const input = document.getElementById('echo-terminal-input');
        if (input) input.focus();
    }

    // Bind controls
    window.addEventListener('keydown', handleKeyDown);
    snakeGameActive = true;

    // Game loop
    snakeGameInterval = setInterval(() => {
        if (gameOver) return;

        // Move head
        const head = { x: snake[0].x + dx, y: snake[0].y + dy };

        // Wall collision
        if (head.x < 0 || head.x >= cols || head.y < 0 || head.y >= rows) {
            triggerGameOver();
            return;
        }

        // Self collision
        if (snake.some(part => part.x === head.x && part.y === head.y)) {
            triggerGameOver();
            return;
        }

        snake.unshift(head);

        // Apple collection
        if (head.x === apple.x && head.y === apple.y) {
            score += 10;
            if (scoreEl) scoreEl.innerText = `SCORE: ${score}`;
            apple = getRandomApplePosition();
        } else {
            snake.pop();
        }

        // Draw background
        ctx.fillStyle = '#000000';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Draw apple
        ctx.fillStyle = '#ff3333';
        ctx.fillRect(apple.x * gridSize + 1, apple.y * gridSize + 1, gridSize - 2, gridSize - 2);

        // Draw snake
        snake.forEach((part, index) => {
            ctx.fillStyle = index === 0 ? '#39ff14' : '#22aa0f'; // neon green head, darker green body
            ctx.fillRect(part.x * gridSize + 1, part.y * gridSize + 1, gridSize - 2, gridSize - 2);
        });
    }, 100);

    function triggerGameOver() {
        gameOver = true;
        if (statusEl) {
            statusEl.innerHTML = `<span class="text-red-500 font-bold">GAME OVER</span><br/>Press SPACE to Restart or ESC to Exit`;
        }
    }
}
