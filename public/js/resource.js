function openDetailModal(button) {
    const r = JSON.parse(button.getAttribute('data-resource'));
    
    document.getElementById('modal-sub').innerText = 'Resource // ' + r.id;
    document.getElementById('modal-title').innerText = r.name;
    document.getElementById('modal-dept').innerText = r.department;
    document.getElementById('modal-role').innerText = r.role;
    document.getElementById('modal-status').innerText = r.status;
    document.getElementById('modal-util').innerText = r.utilization;
    document.getElementById('modal-email').innerText = r.email;
    document.getElementById('modal-manager').innerText = r.manager;

    // Render projects
    const projList = document.getElementById('modal-projects');
    projList.innerHTML = '';
    if (!r.projects || r.projects.length === 0) {
        projList.innerHTML = '<li class="mono text-xs border border-foreground px-3 py-2 bg-background text-muted-foreground uppercase">No current projects</li>';
    } else {
        r.projects.forEach(p => {
            projList.innerHTML += '<li class="mono text-xs border border-foreground px-3 py-2 bg-background font-bold uppercase">' + p + '</li>';
        });
    }

    // Render skills
    const skillsDiv = document.getElementById('modal-skills');
    skillsDiv.innerHTML = '';
    if (!r.skills || r.skills.length === 0) {
        skillsDiv.innerHTML = '<span class="mono text-[10px] uppercase tracking-widest text-muted-foreground">No skills logged</span>';
    } else {
        r.skills.forEach(s => {
            if (s.trim()) {
                skillsDiv.innerHTML += '<span class="mono text-[10px] uppercase tracking-widest border border-foreground bg-secondary px-2 py-1">' + s.trim() + '</span>';
            }
        });
    }

    document.getElementById('resource-modal').classList.remove('hidden');
}

function openCreateModal() {
    document.getElementById('create-resource-modal').classList.remove('hidden');
}

function openEditModal(button) {
    const r = JSON.parse(button.getAttribute('data-resource'));
    
    // Find DB ID from raw ID column in object (r.id is padded string e.g. "003", so convert back to integer)
    const intId = parseInt(r.id, 10);
    
    document.getElementById('edit-resource-form').action = window.Nexus.baseUrl + '/resource/update/' + intId;
    document.getElementById('edit-res-name').value = r.name;
    document.getElementById('edit-res-dept').value = r.department;
    document.getElementById('edit-res-role').value = r.role;
    document.getElementById('edit-res-status').value = r.status.toLowerCase();
    
    document.getElementById('edit-res-email').value = r.email;
    document.getElementById('edit-res-location').value = r.location || 'Local';
    
    document.getElementById('edit-res-manager').value = r.manager || '';
    document.getElementById('edit-res-skills').value = r.skills ? r.skills.join(', ') : '';

    document.getElementById('edit-resource-modal').classList.remove('hidden');
}

function openDeleteModal(button) {
    const r = JSON.parse(button.getAttribute('data-resource'));
    const intId = parseInt(r.id, 10);
    
    document.getElementById('delete-resource-form').action = window.Nexus.baseUrl + '/resource/delete/' + intId;
    document.getElementById('delete-res-name').innerText = r.name;
    
    document.getElementById('delete-resource-modal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

window.onclick = function(event) {
    const modals = ['resource-modal', 'create-resource-modal', 'edit-resource-modal', 'delete-resource-modal'];
    modals.forEach(id => {
        const modal = document.getElementById(id);
        if (modal && event.target == modal) {
            modal.classList.add('hidden');
        }
    });
}

// ==================== INSTANT SEARCH & FILTERS + LAZY LOADING ====================
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.resource-card');
    
    const observer = new IntersectionObserver((entries, obs) => {
        let delay = 0;
        const intersecting = entries.filter(e => e.isIntersecting);
        
        intersecting.forEach(entry => {
            const card = entry.target;
            card.style.transitionDelay = `${delay}ms`;
            card.classList.remove('opacity-0', 'translate-y-4');
            card.classList.add('opacity-100', 'translate-y-0');
            delay += 40;
            obs.unobserve(card);
        });
    }, {
        rootMargin: '0px 0px -40px 0px',
        threshold: 0.05
    });

    cards.forEach(card => {
        card.classList.add('transition-all', 'duration-600', 'ease-out');
        observer.observe(card);
    });

    // Filtering State variables
    const urlParams = new URLSearchParams(window.location.search);
    let activeRole = urlParams.get('role') || 'ALL';
    let activeStatus = urlParams.get('status') || 'ALL';
    let activeSearch = urlParams.get('search') || '';

    const roleBtns = document.querySelectorAll('[data-role-btn]');
    const statusBtns = document.querySelectorAll('[data-status-btn]');
    const searchInput = document.getElementById('search-input');
    const searchForm = document.getElementById('search-form');
    const clearLink = document.getElementById('search-clear-link');
    const activeRoleInput = document.getElementById('active-role');
    const activeStatusInput = document.getElementById('active-status');

    function applyFilters() {
        const query = activeSearch.toLowerCase().trim();

        cards.forEach(card => {
            const role = card.getAttribute('data-role');
            const status = card.getAttribute('data-status');
            const searchBlob = card.getAttribute('data-search') || '';

            const roleMatch = (activeRole === 'ALL' || role === activeRole);
            const statusMatch = (activeStatus === 'ALL' || status === activeStatus);
            const searchMatch = (query === '' || searchBlob.includes(query));

            if (roleMatch && statusMatch && searchMatch) {
                card.classList.remove('hidden');
                // Ensure card is visible and animated if it comes into view
                setTimeout(() => {
                    card.classList.remove('opacity-0', 'translate-y-4');
                    card.classList.add('opacity-100', 'translate-y-0');
                }, 10);
            } else {
                card.classList.add('hidden');
            }
        });

        // Update active classes on Role buttons
        roleBtns.forEach(btn => {
            const val = btn.getAttribute('data-role-btn');
            if (val === activeRole) {
                btn.className = "role-filter-btn group flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-mono uppercase tracking-widest transition-all bg-ink text-paper border-ink";
            } else {
                btn.className = "role-filter-btn group flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-mono uppercase tracking-widest transition-all bg-transparent text-ink border-ink/25 hover:border-ink/60";
            }
            // Update URL parameters in href fallback
            const url = new URL(btn.href);
            url.searchParams.set('status', activeStatus);
            url.searchParams.set('search', activeSearch);
            btn.href = url.pathname + url.search;
        });

        // Update active classes on Status buttons
        statusBtns.forEach(btn => {
            const val = btn.getAttribute('data-status-btn');
            if (val === activeStatus) {
                btn.className = "status-filter-btn group flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-mono uppercase tracking-widest transition-all bg-ink text-paper border-ink";
            } else {
                btn.className = "status-filter-btn group flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-mono uppercase tracking-widest transition-all bg-transparent text-ink border-ink/25 hover:border-ink/60";
            }
            // Update URL parameters in href fallback
            const url = new URL(btn.href);
            url.searchParams.set('role', activeRole);
            url.searchParams.set('search', activeSearch);
            btn.href = url.pathname + url.search;
        });

        // Update inputs
        if (activeRoleInput) activeRoleInput.value = activeRole;
        if (activeStatusInput) activeStatusInput.value = activeStatus;
        if (searchInput && searchInput.value !== activeSearch) searchInput.value = activeSearch;

        // Show/hide clear search link
        if (clearLink) {
            if (activeSearch !== '') {
                clearLink.classList.remove('hidden');
                // Update URL fallback of clear link
                const url = new URL(clearLink.href);
                url.searchParams.set('role', activeRole);
                url.searchParams.set('status', activeStatus);
                url.searchParams.set('search', '');
                clearLink.href = url.pathname + url.search;
            } else {
                clearLink.classList.add('hidden');
            }
        }

        // Update window URL state
        const nextParams = new URLSearchParams();
        if (activeRole !== 'ALL') nextParams.set('role', activeRole);
        if (activeStatus !== 'ALL') nextParams.set('status', activeStatus);
        if (activeSearch !== '') nextParams.set('search', activeSearch);
        const queryStr = nextParams.toString();
        const nextUrl = window.location.pathname + (queryStr ? '?' + queryStr : '');
        window.history.replaceState(null, '', nextUrl);
    }

    // Role Click Listener
    roleBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            activeRole = btn.getAttribute('data-role-btn');
            applyFilters();
        });
    });

    // Status Click Listener
    statusBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            activeStatus = btn.getAttribute('data-status-btn');
            applyFilters();
        });
    });

    // Search Input Listener
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            activeSearch = e.target.value;
            applyFilters();
        });
    }

    // Search Form Submit Listener (prevent reload)
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            applyFilters();
        });
    }

    // Clear Search Listener
    if (clearLink) {
        clearLink.addEventListener('click', (e) => {
            e.preventDefault();
            activeSearch = '';
            if (searchInput) searchInput.value = '';
            applyFilters();
        });
    }
});


