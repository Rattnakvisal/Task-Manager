/**
 * Task Manager - State-of-the-Art Interactive Application Script
 * Features:
 * - Full Bilingual Khmer & English i18n
 * - Kanban Drag-and-Drop with HTML5 Drag Events & Realtime Status Sync
 * - 1-Click Fast Checkbox Completion with Canvas Confetti & Synthesized Audio Chime
 * - Interactive Subtasks / Checklist Manager
 * - Quick Inline Task Creation
 * - View Switcher (Kanban, List, Grid)
 * - Pin/Favorite Toggle
 * - Command Palette (Ctrl+K / Cmd+K)
 * - Dark / Light Theme & Responsive Sidebar
 */

// ============================================================================
// 1. LOCALIZATION DICTIONARY (ភាសាខ្មែរ & ENGLISH)
// ============================================================================
const i18n = {
    km: {
        workspace: 'កន្លែងធ្វើការ',
        my_tasks: 'កិច្ចការរបស់ខ្ញុំ',
        my_tasks_sub: 'រៀបចំ តាមដាន និងគ្រប់គ្រងកិច្ចការងារយ៉ាងរលូនលើក្ដារ Kanban និងបញ្ជី។',
        dashboard: 'ផ្ទាំងគ្រប់គ្រង',
        dashboard_sub: 'ផ្ដោតលើការងារសំខាន់បំផុត តាមដានកាលបរិច្ឆេទ អាទិភាព និងលទ្ធផលការងារ។',
        welcome_dashboard: 'សូមស្វាគមន៍មកកាន់ផ្ទាំងគ្រប់គ្រង',
        calendar: 'ប្រតិទិន',
        priority: 'កម្រិតអាទិភាព',
        all_tasks: 'កិច្ចការទាំងអស់',
        completed: 'បានបញ្ចប់',
        pending: 'មិនទាន់ធ្វើ',
        in_progress: 'កំពុងដំណើរការ',
        all: 'ទាំងអស់',
        new_task: 'បង្កើត Task ថ្មី',
        create_task: 'បង្កើតកិច្ចការថ្មី',
        create_task_sub: 'បន្ថែមព័ត៌មានលម្អិត កិច្ចការរង អាទិភាព និងថ្ងៃកំណត់។',
        edit_task: 'កែសម្រួលកិច្ចការ',
        edit_task_sub: 'កែប្រែព័ត៌មានលម្អិត កិច្ចការរង អាទិភាព និងស្ថានភាព។',
        task_title: 'ចំណងជើងកិច្ចការ',
        description: 'ការពិពណ៌នា',
        category: 'ប្រភេទការងារ',
        no_category: '-- គ្មានប្រភេទ --',
        priority_label: 'កម្រិតអាទិភាព',
        status: 'ស្ថានភាព',
        due_date: 'ថ្ងៃផុតកំណត់',
        optional: 'មិនទាមទារ',
        checklist: 'កិច្ចការរង (Checklist)',
        step_by_step: 'ជំហាននីមួយៗ',
        pin_to_top: '📌 ខ្ទាស់នៅខាងលើបង្អស់',
        cancel: 'បោះបង់',
        add: 'បន្ថែម',
        save_changes: 'រក្សាទុកការកែប្រែ',
        view_kanban: 'ក្ដារ Kanban',
        view_list: 'បញ្ជី',
        view_grid: 'ក្រឡា',
        export: 'ទាញយកទិន្នន័យ',
        export_csv: 'ទាញយកជា CSV',
        export_json: 'ទាញយកជា JSON',
        commands: 'ពាក្យបញ្ជា',
        quick_actions: 'សកម្មភាពរហ័ស',
        tools_preferences: 'ឧបករណ៍ & ចំណូលចិត្ត',
        switch_lang: 'ប្តូរភាសា (ខ្មែរ / English)',
        toggle_dark: 'បិទ/បើក មុខងារងងឹត (Dark Mode)',
        weekly_activity: 'សកម្មភាព & ការរីកចម្រើនក្នុង ៧ ថ្ងៃ',
        weekly_activity_sub: 'ចំនួនកិច្ចការដែលបានបញ្ចប់ និងបានបង្កើតប្រចាំថ្ងៃ',
        completion_rate: 'អត្រាជោគជ័យ',
        productivity_score: 'ផលិតភាពការងារសរុបរបស់អ្នក',
        day_streak: 'ថ្ងៃបន្តបន្ទាប់គ្នា!',
        keep_streak: 'បន្តបំពេញកិច្ចការប្រចាំថ្ងៃដើម្បីរក្សាភាពជោគជ័យ។',
        upcoming_schedule: 'កាលវិភាគបន្ទាប់',
        next_deadlines: 'កាលបរិច្ឆេទ និងការងារដែលជិតមកដល់។',
        view_calendar: 'មើលប្រតិទិន',
        no_upcoming: 'មិនទាន់មានការងារកំណត់ថ្ងៃ',
        add_due_dates: 'បន្ថែមកាលបរិច្ឆេទលើកិច្ចការដើម្បីបង្ហាញនៅទីនេះ។',
        high_priority: 'អាទិភាពខ្ពស់',
        critical_work: 'ការងារបន្ទាន់ដែលត្រូវការយកចិត្តទុកដាក់។',
        view_all: 'មើលទាំងអស់',
        no_high_priority: 'មិនមានការងារអាទិភាពខ្ពស់ទេ',
        overdue_alert: 'ការជូនដំណឹង៖ អ្នកមានកិច្ចការហួសកំណត់!',
        overdue_desc: 'កិច្ចការមួយចំនួនបានហួសកាលបរិច្ឆេទ។ សូមពិនិត្យ ឬពន្យារពេលឥឡូវនេះ។',
        view_overdue: 'ពិនិត្យមើល',
        all_priority: 'អាទិភាពទាំងអស់',
        all_categories: 'ប្រភេទទាំងអស់',
        high: '🔴 ខ្ពស់',
        medium: '🟡 មធ្យម',
        low: '🟢 ទាប',
        task: 'កិច្ចការ',
        actions: 'សកម្មភាព',
        no_tasks_found: 'រកមិនឃើញកិច្ចការទេ',
        no_pending_tasks: 'មិនមានកិច្ចការមិនទាន់ធ្វើទេ។ ទាញកាតមកទីនេះ!',
        no_in_progress_tasks: 'មិនមានកិច្ចការកំពុងដំណើរការទេ។',
        no_completed_tasks: 'ទម្លាក់កាតដែលបានបញ្ចប់នៅទីនេះ!',
        total_tasks: 'កិច្ចការសរុប',
        task_overview: 'ទិដ្ឋភាពទូទៅនៃកិច្ចការ',
    },
    en: {
        workspace: 'Workspace',
        my_tasks: 'My Tasks',
        my_tasks_sub: 'Plan, prioritize, and track work from one interactive Kanban and list board.',
        dashboard: 'Dashboard',
        dashboard_sub: 'Stay focused on what matters most. Track deadlines, priority tasks, and progress.',
        welcome_dashboard: 'Welcome to your Dashboard',
        calendar: 'Calendar',
        priority: 'Priority',
        all_tasks: 'All Tasks',
        completed: 'Completed',
        pending: 'Pending',
        in_progress: 'In Progress',
        all: 'All',
        new_task: 'New Task',
        create_task: 'Create New Task',
        create_task_sub: 'Add a new task with checklist, priority, and deadline.',
        edit_task: 'Edit Task',
        edit_task_sub: 'Update task details, checklist, priority, and status.',
        task_title: 'Task Title',
        description: 'Description',
        category: 'Category',
        no_category: '-- None --',
        priority_label: 'Priority',
        status: 'Status',
        due_date: 'Due Date',
        optional: 'Optional',
        checklist: 'Checklist / Subtasks',
        step_by_step: 'Add steps',
        pin_to_top: '📌 Pin to top of list',
        cancel: 'Cancel',
        add: 'Add',
        save_changes: 'Save Changes',
        view_kanban: 'Kanban',
        view_list: 'List',
        view_grid: 'Grid',
        export: 'Export',
        export_csv: 'Export as CSV',
        export_json: 'Export as JSON',
        commands: 'Commands',
        quick_actions: 'Quick Actions',
        tools_preferences: 'Tools & Preferences',
        switch_lang: 'Switch Language (ភាសាខ្មែរ / English)',
        toggle_dark: 'Toggle Dark / Light Mode',
        weekly_activity: '7-Day Activity & Progress',
        weekly_activity_sub: 'Daily completed vs created tasks',
        completion_rate: 'Completion Rate',
        productivity_score: 'Your overall workspace productivity',
        day_streak: 'Day Productivity Streak!',
        keep_streak: 'Keep finishing tasks daily to maintain momentum.',
        upcoming_schedule: 'Upcoming Schedule',
        next_deadlines: 'Your next deadlines and scheduled tasks.',
        view_calendar: 'View Calendar',
        no_upcoming: 'No upcoming tasks scheduled',
        add_due_dates: 'Add due dates to your tasks to see them here.',
        high_priority: 'High Priority',
        critical_work: 'Critical items needing attention.',
        view_all: 'View All',
        no_high_priority: 'No high priority tasks',
        overdue_alert: 'Attention: You have overdue tasks!',
        overdue_desc: 'Some tasks missed their due dates. Review or reschedule them now.',
        view_overdue: 'View Tasks',
        all_priority: 'All Priority',
        all_categories: 'All Categories',
        high: '🔴 High',
        medium: '🟡 Medium',
        low: '🟢 Low',
        task: 'Task',
        actions: 'Actions',
        no_tasks_found: 'No tasks found',
        no_pending_tasks: 'No pending tasks. Drag cards here!',
        no_in_progress_tasks: 'No active tasks in progress.',
        no_completed_tasks: 'Drop completed cards here!',
        total_tasks: 'Tasks',
        task_overview: 'Task Overview',
    }
};

let currentLang = window.localStorage.getItem('task-manager-lang') || 'km';

function applyLanguage(lang) {
    currentLang = lang;
    window.localStorage.setItem('task-manager-lang', lang);

    const flagSpan = document.querySelector('[data-lang-flag]');
    const textSpan = document.querySelector('[data-lang-text]');
    if (flagSpan && textSpan) {
        if (lang === 'km') {
            flagSpan.textContent = '🇰🇭';
            textSpan.textContent = 'ខ្មែរ';
        } else {
            flagSpan.textContent = '🇬🇧';
            textSpan.textContent = 'EN';
        }
    }

    const dict = i18n[lang] || i18n.en;
    document.querySelectorAll('[data-i18n]').forEach((el) => {
        const key = el.dataset.i18n;
        if (dict[key]) {
            el.textContent = dict[key];
        }
    });
}

const langToggleBtn = document.querySelector('[data-language-toggle]');
langToggleBtn?.addEventListener('click', () => {
    const nextLang = currentLang === 'km' ? 'en' : 'km';
    applyLanguage(nextLang);
});

// Initialize language on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    applyLanguage(currentLang);
});


// ============================================================================
// 2. THEME & SIDEBAR
// ============================================================================
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
    const sunIcon = themeToggle?.querySelector('[data-theme-sun]');
    const moonIcon = themeToggle?.querySelector('[data-theme-moon]');
    if (sunIcon && moonIcon) {
        sunIcon.classList.toggle('hidden', dark);
        moonIcon.classList.toggle('hidden', !dark);
    }
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


// ============================================================================
// 3. CONFETTI CELEBRATION & PLEASANT AUDIO CHIME
// ============================================================================
function playTaskChime() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const ctx = new AudioContext();

        const notes = [523.25, 659.25, 783.99, 1046.50]; // C5, E5, G5, C6
        notes.forEach((freq, index) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, ctx.currentTime + index * 0.08);

            gain.gain.setValueAtTime(0.001, ctx.currentTime + index * 0.08);
            gain.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + index * 0.08 + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + index * 0.08 + 0.35);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(ctx.currentTime + index * 0.08);
            osc.stop(ctx.currentTime + index * 0.08 + 0.38);
        });
    } catch (_err) {
        // AudioContext not allowed or unsupported
    }
}

function fireConfetti() {
    const canvas = document.getElementById('confetti-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#06b6d4'];
    const particles = [];
    const particleCount = 65;

    for (let i = 0; i < particleCount; i++) {
        particles.push({
            x: canvas.width / 2 + (Math.random() - 0.5) * 200,
            y: canvas.height * 0.6 + (Math.random() - 0.5) * 100,
            vx: (Math.random() - 0.5) * 12,
            vy: -Math.random() * 14 - 6,
            size: Math.random() * 8 + 4,
            color: colors[Math.floor(Math.random() * colors.length)],
            rotation: Math.random() * 360,
            rotationSpeed: (Math.random() - 0.5) * 10,
            alpha: 1,
            gravity: 0.35,
        });
    }

    let animationId;
    function render() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        let alive = 0;

        for (const p of particles) {
            p.x += p.vx;
            p.y += p.vy;
            p.vy += p.gravity;
            p.rotation += p.rotationSpeed;
            p.alpha -= 0.012;

            if (p.alpha > 0) {
                alive++;
                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate((p.rotation * Math.PI) / 180);
                ctx.globalAlpha = Math.max(0, p.alpha);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 1.6);
                ctx.restore();
            }
        }

        if (alive > 0) {
            animationId = requestAnimationFrame(render);
        } else {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            cancelAnimationFrame(animationId);
        }
    }

    render();
}


// ============================================================================
// 4. KANBAN DRAG AND DROP
// ============================================================================
let draggedCard = null;

function setupDragAndDrop() {
    const cards = document.querySelectorAll('.task-card[draggable="true"]');
    const cols = document.querySelectorAll('.kanban-col');

    cards.forEach((card) => {
        card.removeEventListener('dragstart', handleDragStart);
        card.removeEventListener('dragend', handleDragEnd);

        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });

    cols.forEach((col) => {
        col.removeEventListener('dragover', handleDragOver);
        col.removeEventListener('dragleave', handleDragLeave);
        col.removeEventListener('drop', handleDrop);

        col.addEventListener('dragover', handleDragOver);
        col.addEventListener('dragleave', handleDragLeave);
        col.addEventListener('drop', handleDrop);
    });
}

function handleDragStart(e) {
    draggedCard = this;
    this.classList.add('is-dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', this.dataset.taskId);
}

function handleDragEnd() {
    this.classList.remove('is-dragging');
    draggedCard = null;
    document.querySelectorAll('.kanban-col').forEach((col) => {
        col.classList.remove('drag-over');
    });
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    this.classList.add('drag-over');
}

function handleDragLeave(e) {
    if (!this.contains(e.relatedTarget)) {
        this.classList.remove('drag-over');
    }
}

async function handleDrop(e) {
    e.preventDefault();
    this.classList.remove('drag-over');

    if (!draggedCard) return;

    const taskId = draggedCard.dataset.taskId;
    const targetStatus = this.dataset.statusCol;
    const previousStatus = draggedCard.dataset.taskStatus;

    if (targetStatus === previousStatus) return;

    // Move DOM element to new column
    const cardsContainer = this.querySelector('[data-col-cards]');
    const emptyPlaceholder = cardsContainer?.querySelector('.empty-drop-placeholder');
    if (emptyPlaceholder) {
        emptyPlaceholder.remove();
    }
    cardsContainer?.prepend(draggedCard);

    // Update dataset and styling
    draggedCard.dataset.taskStatus = targetStatus;
    const titleEl = draggedCard.querySelector(`#card-title-${taskId}`);
    if (targetStatus === 'completed') {
        titleEl?.classList.add('task-completed-text');
        playTaskChime();
        fireConfetti();
    } else {
        titleEl?.classList.remove('task-completed-text');
    }

    // Update column counters
    updateColumnCounters();

    // Send API update
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        await fetch(`/tasks/${taskId}/toggle-status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            body: JSON.stringify({ status: targetStatus }),
        });
    } catch (_err) {
        // silent fail
    }
}

function updateColumnCounters() {
    document.querySelectorAll('.kanban-col').forEach((col) => {
        const status = col.dataset.statusCol;
        const count = col.querySelectorAll(`.task-card[data-task-status="${status}"]`).length;
        const counterEl = col.querySelector('.col-count');
        if (counterEl) {
            counterEl.textContent = count;
        }
    });
}


// ============================================================================
// 5. VIEW SWITCHER (KANBAN, LIST, GRID)
// ============================================================================
const viewButtons = document.querySelectorAll('[data-view-btn]');
const viewPanels = document.querySelectorAll('[data-view-panel]');
const currentViewInput = document.getElementById('current-view-input');

function setViewMode(view) {
    viewButtons.forEach((btn) => {
        const isMatch = btn.dataset.viewBtn === view;
        btn.classList.toggle('bg-white', isMatch);
        btn.classList.toggle('text-blue-600', isMatch);
        btn.classList.toggle('shadow-sm', isMatch);
        btn.classList.toggle('dark:bg-slate-900', isMatch);
        btn.classList.toggle('dark:text-blue-400', isMatch);
    });

    viewPanels.forEach((panel) => {
        panel.classList.toggle('hidden', panel.dataset.viewPanel !== view);
    });

    if (currentViewInput) {
        currentViewInput.value = view;
    }

    window.localStorage.setItem('task-manager-view', view);
}

viewButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
        setViewMode(btn.dataset.viewBtn);
    });
});

const storedView = window.localStorage.getItem('task-manager-view');
if (storedView) {
    setViewMode(storedView);
}


// ============================================================================
// 6. 1-CLICK COMPLETE TOGGLE & PIN TOGGLE
// ============================================================================
document.addEventListener('click', async (e) => {
    // 1-Click Complete Toggle
    const completeBtn = e.target.closest('[data-toggle-complete-id]');
    if (completeBtn) {
        e.preventDefault();
        e.stopPropagation();
        const taskId = completeBtn.dataset.toggleCompleteId;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const res = await fetch(`/tasks/${taskId}/toggle-status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
            });
            const data = await res.json();
            if (data.success) {
                const isCompleted = data.status === 'completed';

                // Update cards
                const card = document.getElementById(`task-card-${taskId}`);
                if (card) {
                    card.dataset.taskStatus = data.status;
                    const cardTitle = card.querySelector(`#card-title-${taskId}`);
                    cardTitle?.classList.toggle('task-completed-text', isCompleted);

                    // Move to completed/pending column if in Kanban
                    const targetCol = document.querySelector(`[data-status-col="${data.status}"] [data-col-cards]`);
                    if (targetCol && card.closest('.kanban-board')) {
                        targetCol.prepend(card);
                        updateColumnCounters();
                    }
                }

                // Update list view row
                const listRow = document.getElementById(`list-row-${taskId}`);
                if (listRow) {
                    const rowTitle = listRow.querySelector(`#task-title-${taskId}`);
                    rowTitle?.classList.toggle('task-completed-text', isCompleted);
                    const btn = listRow.querySelector('[data-toggle-complete-id]');
                    if (btn) {
                        btn.className = `flex h-5 w-5 items-center justify-center rounded-md border ${isCompleted ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 bg-white hover:border-slate-400 dark:border-slate-700 dark:bg-slate-800'}`;
                        btn.innerHTML = isCompleted ? `<svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>` : '';
                    }
                }

                if (isCompleted) {
                    playTaskChime();
                    fireConfetti();
                }
            }
        } catch (_err) {
            // silent fail
        }
        return;
    }

    // Pin Toggle
    const pinBtn = e.target.closest('[data-toggle-pin-id]');
    if (pinBtn) {
        e.preventDefault();
        e.stopPropagation();
        const taskId = pinBtn.dataset.togglePinId;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const res = await fetch(`/tasks/${taskId}/toggle-pin`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
            });
            const data = await res.json();
            if (data.success) {
                pinBtn.classList.toggle('text-amber-500', data.is_pinned);
                pinBtn.classList.toggle('text-slate-300', !data.is_pinned);
            }
        } catch (_err) {
            // silent fail
        }
        return;
    }
});


// ============================================================================
// 7. INLINE QUICK-ADD TASK
// ============================================================================
const quickAddForm = document.getElementById('quick-add-form');
quickAddForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const titleInput = document.getElementById('quick-add-title');
    const categoryInput = document.getElementById('quick-add-category');
    const priorityInput = document.getElementById('quick-add-priority');
    const dueDateInput = document.getElementById('quick-add-due-date');
    const submitBtn = document.getElementById('quick-add-submit');

    const title = titleInput?.value.trim();
    if (!title) return;

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = '...';
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch('/tasks/quick', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            body: JSON.stringify({
                title,
                category: categoryInput?.value || null,
                priority: priorityInput?.value || 'medium',
                due_date: dueDateInput?.value || null,
            }),
        });

        if (res.ok) {
            titleInput.value = '';
            // Fast reload to render proper card with backend relations
            window.location.reload();
        }
    } catch (_err) {
        window.location.reload();
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Add';
        }
    }
});


// ============================================================================
// 8. SUBTASKS CHECKLIST MANAGER (MODALS & CARDS)
// ============================================================================
// Subtask toggle inside task cards
document.addEventListener('change', async (e) => {
    const subtaskCheckbox = e.target.closest('[data-task-subtask-toggle]');
    if (subtaskCheckbox) {
        const taskId = subtaskCheckbox.dataset.taskSubtaskToggle;
        const subtaskId = subtaskCheckbox.dataset.subtaskId;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            await fetch(`/tasks/${taskId}/subtasks/${subtaskId}/toggle`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
            });
            const textSpan = subtaskCheckbox.nextElementSibling;
            if (textSpan) {
                textSpan.classList.toggle('line-through', subtaskCheckbox.checked);
                textSpan.classList.toggle('text-slate-400', subtaskCheckbox.checked);
            }
        } catch (_err) {
            // silent fail
        }
    }
});

// Create Modal Subtasks State
let createSubtasks = [];
function renderCreateSubtasks() {
    const container = document.getElementById('create-subtask-list');
    const payloadInput = document.getElementById('create-subtasks-payload');
    if (!container || !payloadInput) return;

    payloadInput.value = JSON.stringify(createSubtasks);
    container.innerHTML = createSubtasks.map((st, i) => `
        <div class="flex items-center justify-between gap-2 rounded-lg bg-white px-3 py-2 border border-slate-200 dark:border-slate-700 dark:bg-slate-800">
            <span class="text-xs text-slate-800 dark:text-slate-200">${escapeHtml(st.title)}</span>
            <button type="button" class="text-slate-400 hover:text-rose-500" onclick="removeCreateSubtask(${i})">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    `).join('');
}

window.removeCreateSubtask = function(index) {
    createSubtasks.splice(index, 1);
    renderCreateSubtasks();
};

document.getElementById('create-add-subtask-btn')?.addEventListener('click', () => {
    const input = document.getElementById('create-new-subtask-input');
    const val = input?.value.trim();
    if (!val) return;
    createSubtasks.push({ id: Date.now().toString(), title: val, completed: false });
    input.value = '';
    renderCreateSubtasks();
});

// Edit Modal Subtasks State
let editSubtasks = [];
function renderEditSubtasks() {
    const container = document.getElementById('edit-subtask-list');
    const payloadInput = document.getElementById('edit-subtasks-payload');
    if (!container || !payloadInput) return;

    payloadInput.value = JSON.stringify(editSubtasks);
    container.innerHTML = editSubtasks.map((st, i) => `
        <div class="flex items-center justify-between gap-2 rounded-lg bg-white px-3 py-2 border border-slate-200 dark:border-slate-700 dark:bg-slate-800">
            <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-800 dark:text-slate-200">
                <input type="checkbox" ${st.completed ? 'checked' : ''} onchange="toggleEditSubtaskCompleted(${i}, this.checked)" class="rounded border-slate-300 text-blue-600">
                <span class="${st.completed ? 'line-through text-slate-400' : ''}">${escapeHtml(st.title)}</span>
            </label>
            <button type="button" class="text-slate-400 hover:text-rose-500" onclick="removeEditSubtask(${i})">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    `).join('');
}

window.toggleEditSubtaskCompleted = function(index, checked) {
    if (editSubtasks[index]) {
        editSubtasks[index].completed = checked;
        renderEditSubtasks();
    }
};

window.removeEditSubtask = function(index) {
    editSubtasks.splice(index, 1);
    renderEditSubtasks();
};

document.getElementById('edit-add-subtask-btn')?.addEventListener('click', () => {
    const input = document.getElementById('edit-new-subtask-input');
    const val = input?.value.trim();
    if (!val) return;
    editSubtasks.push({ id: Date.now().toString(), title: val, completed: false });
    input.value = '';
    renderEditSubtasks();
});


// ============================================================================
// 9. MODALS: CREATE & EDIT
// ============================================================================
const modal = document.getElementById('task-modal');
const editModal = document.getElementById('edit-task-modal');
const editForm = document.querySelector('[data-edit-task-form]');

function openTaskModal() {
    createSubtasks = [];
    renderCreateSubtasks();
    modal?.classList.remove('hidden');
    modal?.classList.add('flex');
    modal?.querySelector('input[name="title"]')?.focus();
}

function closeTaskModal() {
    modal?.classList.add('hidden');
    modal?.classList.remove('flex');
}

function openEditModal() {
    editModal?.classList.remove('hidden');
    editModal?.classList.add('flex');
    editModal?.querySelector('[name="title"]')?.focus();
}

function closeEditModal() {
    editModal?.classList.add('hidden');
    editModal?.classList.remove('flex');
}

document.querySelectorAll('[data-open-task-modal]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        openTaskModal();
    });
});

document.querySelectorAll('[data-close-task-modal]').forEach((btn) => {
    btn.addEventListener('click', closeTaskModal);
});

document.querySelectorAll('[data-close-edit-modal]').forEach((btn) => {
    btn.addEventListener('click', closeEditModal);
});

modal?.addEventListener('click', (e) => {
    if (e.target === modal) closeTaskModal();
});

editModal?.addEventListener('click', (e) => {
    if (e.target === editModal) closeEditModal();
});

async function openEditById(taskId) {
    if (!editModal || !editForm) return;

    try {
        const res = await fetch(`/api/tasks/${taskId}`, {
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) throw new Error('Task could not be loaded');
        const data = await res.json();
        const task = data.task;

        editForm.action = `/tasks/${task.id}`;
        editForm.querySelector('[name="title"]').value = task.title ?? '';
        editForm.querySelector('[name="description"]').value = task.description ?? '';
        editForm.querySelector('[name="category"]').value = task.category ?? '';
        editForm.querySelector('[name="priority"]').value = task.priority ?? 'medium';
        editForm.querySelector('[name="status"]').value = task.status ?? 'pending';
        editForm.querySelector('[name="due_date"]').value = task.due_date ?? '';
        editForm.querySelector('[name="end_date"]').value = task.end_date ?? '';

        const pinCheck = editForm.querySelector('[name="is_pinned"]');
        if (pinCheck) pinCheck.checked = Boolean(task.is_pinned);

        editSubtasks = Array.isArray(task.subtasks) ? [...task.subtasks] : [];
        renderEditSubtasks();

        openEditModal();
    } catch (_err) {
        window.location.href = `/tasks/${taskId}/edit`;
    }
}

document.addEventListener('click', (e) => {
    const editBtn = e.target.closest('[data-edit-task-id]');
    if (editBtn) {
        e.preventDefault();
        openEditById(editBtn.dataset.editTaskId);
    }
});


// ============================================================================
// 10. COMMAND PALETTE (CTRL+K / CMD+K)
// ============================================================================
const cmdPaletteModal = document.getElementById('command-palette-modal');
const cmdInput = document.getElementById('cmd-palette-input');
const cmdList = document.getElementById('cmd-palette-list');

function openCommandPalette() {
    cmdPaletteModal?.classList.remove('hidden');
    cmdPaletteModal?.classList.add('flex');
    if (cmdInput) {
        cmdInput.value = '';
        cmdInput.focus();
    }
}

function closeCommandPalette() {
    cmdPaletteModal?.classList.add('hidden');
    cmdPaletteModal?.classList.remove('flex');
}

document.querySelector('[data-open-command-palette]')?.addEventListener('click', openCommandPalette);

cmdPaletteModal?.addEventListener('click', (e) => {
    if (e.target === cmdPaletteModal) closeCommandPalette();
});

// Command palette search filtering
cmdInput?.addEventListener('input', () => {
    const query = cmdInput.value.toLowerCase().trim();
    const items = cmdList?.querySelectorAll('.cmd-item');
    items?.forEach((item) => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(query) ? 'flex' : 'none';
    });
});

// Command actions
cmdList?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-cmd]');
    if (btn) {
        const cmd = btn.dataset.cmd;
        closeCommandPalette();
        if (cmd === 'new-task') {
            openTaskModal();
        } else if (cmd === 'toggle-lang') {
            const nextLang = currentLang === 'km' ? 'en' : 'km';
            applyLanguage(nextLang);
        } else if (cmd === 'toggle-theme') {
            const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
            applyTheme(nextTheme);
            window.localStorage.setItem('task-manager-theme', nextTheme);
        }
    }
});

// Global keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl+K or Cmd+K
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        if (cmdPaletteModal?.classList.contains('flex')) {
            closeCommandPalette();
        } else {
            openCommandPalette();
        }
    }

    // Escape
    if (e.key === 'Escape') {
        closeCommandPalette();
        closeTaskModal();
        closeEditModal();
        document.querySelector('[data-notifications-panel]')?.classList.add('hidden');
    }
});


// ============================================================================
// 11. DASHBOARD ANIMATED COUNTERS
// ============================================================================
const dashboard = document.querySelector('[data-dashboard]');
if (dashboard && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    dashboard.querySelectorAll('[data-dashboard-count]').forEach((counter) => {
        const target = Number.parseInt(counter.dataset.dashboardCount, 10);
        if (!Number.isFinite(target) || target <= 0) return;

        const duration = 750;
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


// ============================================================================
// 12. NOTIFICATIONS & UTILITIES
// ============================================================================
const notificationBtn = document.querySelector('[data-toggle-notifications]');
const notificationPanel = document.querySelector('[data-notifications-panel]');

notificationBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    notificationPanel?.classList.toggle('hidden');
});

notificationPanel?.addEventListener('click', (e) => e.stopPropagation());

document.addEventListener('click', () => {
    notificationPanel?.classList.add('hidden');
});

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Initialize Drag and Drop on startup
setupDragAndDrop();
