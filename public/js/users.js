function openCreateModal() {
    document.getElementById('create-modal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('create-modal').classList.add('hidden');
    // Reset password field and eye icon
    const pwInput = document.getElementById('create_password');
    if (pwInput) {
        pwInput.type = 'password';
        pwInput.value = '';
    }
    resetEyeIcon('create_password');
}

function openEditModal(user) {
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_password').value = '';
    // Reset eye icon when modal opens
    const pwInput = document.getElementById('edit_password');
    if (pwInput) pwInput.type = 'password';
    resetEyeIcon('edit_password');
    
    document.getElementById('edit-form').action = window.Nexus.baseUrl + '/users/update/' + user.id;
    document.getElementById('edit-modal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
    const pwInput = document.getElementById('edit_password');
    if (pwInput) pwInput.type = 'password';
    resetEyeIcon('edit_password');
}

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    const icon = btn.querySelector('i');
    if (icon) {
        icon.setAttribute('data-lucide', isHidden ? 'eye-off' : 'eye');
        if (window.lucide) lucide.createIcons({ nodes: [icon] });
    }
}

function resetEyeIcon(inputId) {
    const wrapper = document.getElementById(inputId)?.closest('.relative');
    if (!wrapper) return;
    const icon = wrapper.querySelector('i[data-lucide]');
    if (icon) {
        icon.setAttribute('data-lucide', 'eye');
        if (window.lucide) lucide.createIcons({ nodes: [icon] });
    }
}
