function openCreateModal() {
    document.getElementById('create-squad-modal').classList.remove('hidden');
}

function openEditModal(button) {
    const sq = JSON.parse(button.getAttribute('data-squad'));
    document.getElementById('edit-squad-form').action = window.Nexus.baseUrl + '/squads/update/' + sq.id;
    document.getElementById('edit-sq-name').value = sq.name;
    document.getElementById('edit-sq-lead').value = sq.lead;
    document.getElementById('edit-sq-mission').value = sq.mission || '';
    document.getElementById('edit-squad-modal').classList.remove('hidden');
}

function openDeleteModal(button) {
    const sq = JSON.parse(button.getAttribute('data-squad'));
    document.getElementById('delete-squad-form').action = window.Nexus.baseUrl + '/squads/delete/' + sq.id;
    document.getElementById('delete-sq-name').innerText = sq.name;
    document.getElementById('delete-squad-modal').classList.remove('hidden');
}

function openAddMemberModal(squadId, squadName) {
    document.getElementById('add-member-squad-id').value = squadId;
    document.getElementById('add-member-squad-display').innerText = squadName;

    const searchInput = document.getElementById('member-resource-search');
    if (searchInput) {
        searchInput.value = '';
        searchInput.setCustomValidity('');
    }
    const hiddenInput = document.getElementById('member-resource-id-hidden');
    if (hiddenInput) {
        hiddenInput.value = '';
    }

    document.getElementById('add-member-modal').classList.remove('hidden');
}

function syncMemberResourceId(input) {
    const val = input.value.trim().toUpperCase();
    const list = document.getElementById('member-resource-options');
    const hiddenInput = document.getElementById('member-resource-id-hidden');
    let foundId = '';
    if (list) {
        const options = list.options;
        for (let i = 0; i < options.length; i++) {
            const optVal = options[i].value.trim().toUpperCase();
            if (optVal === val) {
                foundId = options[i].getAttribute('data-id');
                break;
            }
        }
    }
    if (hiddenInput) {
        hiddenInput.value = foundId;
    }

    if (input.value !== '' && foundId === '') {
        input.setCustomValidity('Please select a valid resource from the list.');
    } else {
        input.setCustomValidity('');
    }
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

window.onclick = function(event) {
    const modals = ['create-squad-modal', 'edit-squad-modal', 'delete-squad-modal', 'add-member-modal'];
    modals.forEach(id => {
        const modal = document.getElementById(id);
        if (modal && event.target == modal) {
            modal.classList.add('hidden');
        }
    });
}
