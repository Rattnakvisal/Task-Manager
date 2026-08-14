const modal = document.getElementById('task-modal');
const openButtons = document.querySelectorAll('[data-open-task-modal]');
const closeButtons = document.querySelectorAll('[data-close-task-modal]');
const taskForm = document.querySelector('[data-task-form]');
const editModal = document.getElementById('edit-task-modal');
const editForm = document.querySelector('[data-edit-task-form]');
const closeEditButtons = document.querySelectorAll('[data-close-edit-modal]');
const successAlert = document.getElementById('success-alert');
const dismissAlertButton = document.querySelector('[data-dismiss-alert]');
const notificationButton = document.querySelector('[data-toggle-notifications]');
const notificationPanel = document.querySelector('[data-notifications-panel]');
const globalSearch = document.querySelector('[data-global-search]');
const globalSearchForm = document.querySelector('[data-global-search-form]');
const searchResultsPanel = document.querySelector('[data-search-results]');
const shouldPreserveForm = modal?.dataset.hasErrors === 'true';
let searchTimeout;
let activeSearchController;

const authSwitcher = document.querySelector('[data-auth-switcher]');
const dashboard = document.querySelector('[data-dashboard]');
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const themeToggle = document.querySelector('[data-theme-toggle]');

function applySidebarState(collapsed) {
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    sidebarToggle?.setAttribute('aria-expanded', String(!collapsed));
    sidebarToggle?.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
}

function applyTheme(theme) {
    const dark = theme === 'dark';
    document.documentElement.classList.toggle('dark', dark);
    themeToggle?.setAttribute('aria-pressed', String(dark));
    themeToggle?.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
}

const storedSidebarState = window.localStorage.getItem('task-manager-sidebar');
applySidebarState(storedSidebarState === 'collapsed');

const storedTheme = window.localStorage.getItem('task-manager-theme');
const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
applyTheme(storedTheme ?? preferredTheme);

sidebarToggle?.addEventListener('click', () => {
    const collapsed = !document.body.classList.contains('sidebar-collapsed');
    applySidebarState(collapsed);
    window.localStorage.setItem('task-manager-sidebar', collapsed ? 'collapsed' : 'expanded');
});

themeToggle?.addEventListener('click', () => {
    const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
    applyTheme(nextTheme);
    window.localStorage.setItem('task-manager-theme', nextTheme);
});

if (dashboard && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    dashboard.querySelectorAll('[data-dashboard-count]').forEach((counter) => {
        const target = Number.parseInt(counter.dataset.dashboardCount, 10);

        if (!Number.isFinite(target) || target <= 0) {
            return;
        }

        const duration = 700;
        const startTime = performance.now();
        counter.textContent = '0';

        const updateCounter = (currentTime) => {
            const progress = Math.min((currentTime - startTime) / duration, 1);
            const easedProgress = 1 - Math.pow(1 - progress, 3);
            counter.textContent = Math.round(target * easedProgress).toLocaleString();

            if (progress < 1) {
                window.requestAnimationFrame(updateCounter);
            }
        };

        window.requestAnimationFrame(updateCounter);
    });
}

function setAuthMode(mode) {
    if (!authSwitcher) {
        return;
    }

    const showRegister = mode === 'register';
    authSwitcher.classList.toggle('show-register', showRegister);

    const loginPane = authSwitcher.querySelector('.auth-login-pane');
    const registerPane = authSwitcher.querySelector('.auth-register-pane');
    loginPane?.toggleAttribute('inert', showRegister);
    registerPane?.toggleAttribute('inert', !showRegister);

    const nextUrl = showRegister ? '/sign-up' : '/sign-in';
    window.history.replaceState({}, '', nextUrl);

    window.setTimeout(() => {
        authSwitcher.querySelector(showRegister ? '#register-name' : '#login-email')?.focus();
    }, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 380);
}

if (authSwitcher) {
    const startsWithRegister = authSwitcher.classList.contains('show-register');
    authSwitcher.querySelector('.auth-login-pane')?.toggleAttribute('inert', startsWithRegister);
    authSwitcher.querySelector('.auth-register-pane')?.toggleAttribute('inert', !startsWithRegister);

    document.querySelectorAll('[data-show-register]').forEach((button) => {
        button.addEventListener('click', () => setAuthMode('register'));
    });

    document.querySelectorAll('[data-show-login]').forEach((button) => {
        button.addEventListener('click', () => setAuthMode('login'));
    });
}

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const isVisible = input.type === 'text';
        input.type = isVisible ? 'password' : 'text';
        button.setAttribute('aria-pressed', String(!isVisible));
        button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
        input.focus({ preventScroll: true });
        input.setSelectionRange(input.value.length, input.value.length);
    });
});

document.querySelectorAll('form').forEach((form) => {
    const controls = form.querySelectorAll('input:not([type="hidden"]), select, textarea');

    if (controls.length >= 2) {
        form.dataset.animatedForm = '';

        Array.from(form.children).forEach((child, index) => {
            child.classList.add('form-motion-item');
            child.style.setProperty('--form-index', index);
        });
    }

    form.addEventListener('submit', (event) => {
        if (event.defaultPrevented || !form.checkValidity()) {
            return;
        }

        const submitButton = event.submitter;

        if (submitButton instanceof HTMLButtonElement) {
            submitButton.classList.add('is-submitting');
            submitButton.disabled = true;
            submitButton.setAttribute('aria-busy', 'true');
        }
    });
});

function clearTaskForm() {
    if (!taskForm || shouldPreserveForm) {
        return;
    }

    taskForm.reset();
    const title = taskForm.querySelector('[name="title"]');
    const description = taskForm.querySelector('[name="description"]');

    if (title) {
        title.value = '';
        title.defaultValue = '';
    }

    if (description) {
        description.value = '';
        description.defaultValue = '';
    }

    const priority = taskForm.querySelector('[name="priority"]');
    const status = taskForm.querySelector('[name="status"]');
    const dueDate = taskForm.querySelector('[name="due_date"]');
    const endDate = taskForm.querySelector('[name="end_date"]');

    if (priority) {
        priority.value = 'medium';
        priority.querySelectorAll('option').forEach((option) => {
            option.defaultSelected = option.value === 'medium';
        });
    }

    if (status) {
        status.value = 'pending';
        status.querySelectorAll('option').forEach((option) => {
            option.defaultSelected = option.value === 'pending';
        });
    }

    if (dueDate) {
        dueDate.value = '';
        dueDate.defaultValue = '';
    }

    if (endDate) {
        endDate.value = '';
        endDate.defaultValue = '';
    }
}

function openTaskModal(event) {
    if (!modal) {
        return;
    }

    event?.preventDefault();
    clearTaskForm();
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.querySelector('input[name="title"]')?.focus();
}

function closeTaskModal() {
    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    clearTaskForm();

    if (window.location.hash === '#new-task') {
        history.replaceState(null, '', window.location.pathname + window.location.search);
    }
}

function openEditModal() {
    if (!editModal) {
        return;
    }

    editModal.classList.remove('hidden');
    editModal.classList.add('flex');
    editModal.querySelector('[name="title"]')?.focus();
}

function closeEditModal() {
    if (!editModal) {
        return;
    }

    editModal.classList.add('hidden');
    editModal.classList.remove('flex');
}

function fillEditForm(task) {
    if (!editForm) {
        return;
    }

    editForm.action = `/tasks/${task.id}`;
    editForm.querySelector('[name="title"]').value = task.title ?? '';
    editForm.querySelector('[name="description"]').value = task.description ?? '';
    editForm.querySelector('[name="priority"]').value = task.priority ?? 'medium';
    editForm.querySelector('[name="status"]').value = task.status ?? 'pending';
    editForm.querySelector('[name="due_date"]').value = task.due_date ?? '';
    editForm.querySelector('[name="end_date"]').value = task.end_date ?? '';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function readableDate(value) {
    if (!value) {
        return '';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatLabel(value) {
    return String(value ?? '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function hideSearchResults() {
    searchResultsPanel?.classList.add('hidden');
}

function showSearchResults(content) {
    if (!searchResultsPanel) {
        return;
    }

    searchResultsPanel.innerHTML = content;
    searchResultsPanel.classList.remove('hidden');
}

function renderSearchResults(tasks, query) {
    if (!tasks.length) {
        showSearchResults(`
            <div class="px-4 py-5 text-center">
                <p class="text-sm font-semibold text-slate-800">No tasks found</p>
                <p class="mt-1 text-xs text-slate-400">Try a different title or description.</p>
            </div>
        `);
        return;
    }

    const items = tasks.slice(0, 6).map((task) => {
        const description = task.description
            ? `<p class="mt-1 line-clamp-1 text-xs text-slate-400">${escapeHtml(task.description)}</p>`
            : '';
        const dueDate = task.due_date ? `<span>Due ${escapeHtml(readableDate(task.due_date))}</span>` : '';
        const endDate = task.end_date ? `<span>End ${escapeHtml(readableDate(task.end_date))}</span>` : '';
        const dates = [dueDate, endDate].filter(Boolean).join('<span class="text-slate-300">|</span>');

        return `
            <button type="button" data-edit-task-id="${escapeHtml(task.id)}" class="block w-full border-b border-slate-100 px-4 py-3 text-left transition hover:bg-blue-50/70 last:border-b-0">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-950">${escapeHtml(task.title)}</p>
                        ${description}
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] font-semibold text-slate-400">
                            <span class="rounded-full bg-slate-100 px-2 py-1 text-slate-600">${escapeHtml(formatLabel(task.status))}</span>
                            ${dates}
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full px-2 py-1 text-[11px] font-bold ${task.priority === 'high' ? 'bg-rose-50 text-rose-600' : task.priority === 'medium' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600'}">
                        ${escapeHtml(formatLabel(task.priority))}
                    </span>
                </div>
            </button>
        `;
    }).join('');

    showSearchResults(`
        <div class="border-b border-slate-100 px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Search Results</p>
        </div>
        <div class="max-h-96 overflow-y-auto">${items}</div>
        <a href="/all-tasks?q=${encodeURIComponent(query)}" class="block bg-slate-50 px-4 py-3 text-center text-xs font-bold text-blue-600 hover:bg-blue-100">
            View all matching tasks
        </a>
    `);
}

async function openEditById(taskId, fallbackUrl = null) {
    if (!editModal || !editForm) {
        return;
    }

    if (!taskId) {
        return;
    }

    try {
        const response = await fetch(`/api/tasks/${taskId}`, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Task could not be loaded.');
        }

        const data = await response.json();
        fillEditForm(data.task);
        hideSearchResults();
        openEditModal();
    } catch (_error) {
        if (fallbackUrl) {
            window.location.href = fallbackUrl;
        }
    }
}

function openEditFromLink(link, event) {
    const url = new URL(link.href);
    const match = url.pathname.match(/\/tasks\/(\d+)\/edit$/);

    if (!match) {
        return;
    }

    event.preventDefault();
    openEditById(match[1], link.href);
}

async function searchTasks(query) {
    if (!query) {
        showSearchResults(`
            <div class="px-4 py-3 text-sm text-slate-500">
                Type to search tasks.
            </div>
        `);
        return;
    }

    activeSearchController?.abort();
    activeSearchController = new AbortController();

    showSearchResults(`
        <div class="px-4 py-3 text-sm text-slate-500">
            Searching...
        </div>
    `);

    try {
        const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`, {
            headers: {
                Accept: 'application/json',
            },
            signal: activeSearchController.signal,
        });

        if (!response.ok) {
            throw new Error('Search failed.');
        }

        const data = await response.json();
        renderSearchResults(data.tasks ?? [], query);
    } catch (error) {
        if (error.name === 'AbortError') {
            return;
        }

        showSearchResults(`
            <div class="px-4 py-5 text-center">
                <p class="text-sm font-semibold text-slate-800">Search is unavailable</p>
                <p class="mt-1 text-xs text-slate-400">Please try again in a moment.</p>
            </div>
        `);
    }
}

openButtons.forEach((button) => {
    button.addEventListener('click', openTaskModal);
});

closeButtons.forEach((button) => {
    button.addEventListener('click', closeTaskModal);
});

closeEditButtons.forEach((button) => {
    button.addEventListener('click', closeEditModal);
});

modal?.addEventListener('click', (event) => {
    if (event.target === modal) {
        closeTaskModal();
    }
});

editModal?.addEventListener('click', (event) => {
    if (event.target === editModal) {
        closeEditModal();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeTaskModal();
        closeEditModal();
        notificationPanel?.classList.add('hidden');
        hideSearchResults();
    }

    if (event.key === '/' && document.activeElement?.tagName !== 'INPUT' && document.activeElement?.tagName !== 'TEXTAREA') {
        event.preventDefault();
        globalSearch?.focus();
    }
});

modal?.querySelector('form')?.addEventListener('submit', () => {
    if (window.location.hash === '#new-task') {
        history.replaceState(null, '', window.location.pathname + window.location.search);
    }
});

if (window.location.hash === '#new-task') {
    openTaskModal();
}

document.querySelectorAll('a[href*="/tasks/"][href$="/edit"]').forEach((link) => {
    link.addEventListener('click', (event) => openEditFromLink(link, event));
});

dismissAlertButton?.addEventListener('click', () => {
    successAlert?.remove();
});

notificationButton?.addEventListener('click', (event) => {
    event.stopPropagation();
    notificationPanel?.classList.toggle('hidden');
});

notificationPanel?.addEventListener('click', (event) => {
    event.stopPropagation();
});

globalSearchForm?.addEventListener('click', (event) => {
    event.stopPropagation();
});

searchResultsPanel?.addEventListener('click', (event) => {
    const editButton = event.target.closest('[data-edit-task-id]');

    if (editButton) {
        event.preventDefault();
        event.stopPropagation();
        openEditById(editButton.dataset.editTaskId);
    }
});

globalSearch?.addEventListener('focus', () => {
    searchTasks(globalSearch.value.trim());
});

globalSearch?.addEventListener('input', () => {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        searchTasks(globalSearch.value.trim());
    }, 200);
});

document.addEventListener('click', () => {
    notificationPanel?.classList.add('hidden');
    hideSearchResults();
});

if (successAlert) {
    clearTaskForm();

    setTimeout(() => {
        successAlert.remove();
    }, 3500);
}
