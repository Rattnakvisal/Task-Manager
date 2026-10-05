import lottie from 'lottie-web';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;

/**
 * WorkMind - State-of-the-Art Interactive Application Script
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
        account: 'គណនី',
        my_profile: 'ប្រវត្តិរូបរបស់ខ្ញុំ',
        my_tasks: 'កិច្ចការរបស់ខ្ញុំ',
        my_tasks_sub: 'រៀបចំ តាមដាន និងគ្រប់គ្រងកិច្ចការងារយ៉ាងរលូនលើក្ដារ Kanban និងបញ្ជី។',
        dashboard: 'ផ្ទាំងគ្រប់គ្រង',
        dashboard_sub: 'ផ្ដោតលើការងារសំខាន់បំផុត តាមដានកាលបរិច្ឆេទ អាទិភាព និងលទ្ធផលការងារ។',
        welcome_dashboard: 'សូមស្វាគមន៍មកកាន់ផ្ទាំងគ្រប់គ្រង',
        projects: 'គម្រោងការងារ',
        projects_sub: 'គ្រប់គ្រង និងតាមដានកិច្ចការតាមប្រភេទគម្រោងនីមួយៗប្រកបដោយរបៀបរៀបរយ។',
        calendar: 'ប្រតិទិន',
        priority: 'កម្រិតអាទិភាព',
        analytics: 'ស្ថិតិ & ផលិតភាព',
        analytics_sub: 'តាមដានលទ្ធផល ស្ថិតិនៃការបំពេញកិច្ចការ និងការវាយតម្លៃផលិតភាពការងារ។',
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
        end_date: 'ថ្ងៃបញ្ចប់',
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
        ai_magic_breakdown: '✨ AI Magic Breakdown (បំបែកកិច្ចការស្វ័យប្រវត្តិ)',
        task_title_or_topic: 'ចំណងជើងកិច្ចការ ឬប្រធានបទ',
        task_topic_hint: 'ឧទាហរណ៍៖ រៀន Laravel, រៀបចំបទបង្ហាញ ឬបង្កើត Portfolio',
        ai_plan_type: 'ប្រភេទផែនការ',
        ai_suggest: '✨ AI Suggest (វិភាគស្វ័យប្រវត្តិ)',
        ai_copilot_detected: 'Nova បានរកឃើញ៖',
        ai_briefing_title: 'AI Daily Standup Briefing (សេចក្តីសង្ខេប AI)',
        ai_refresh_brief: 'ធ្វើបច្ចុប្បន្នភាព AI',
        ai_generating: 'កំពុងវិភាគ...',
        ai_subtasks_ready: 'បានបង្កើតកិច្ចការរងជោគជ័យ!',
        please_enter_title: 'សូមបញ្ចូលចំណងជើងកិច្ចការ ឬប្រធានបទជាមុនសិន!',
        ai_chatbot: 'ជំនួយការ Nova AI',
        open_ai_chat: 'ជជែកជាមួយ AI Chatbot',
        ai_chatbot_status: 'ជំនួយការ AI កំពុងដំណើរការ',
        ai_assistant_sub: 'IT • ការងារ • ការសិក្សា • ជីវិតប្រចាំថ្ងៃ',
        ai_welcome_title: 'តើខ្ញុំអាចជួយអ្វីដល់អ្នកនៅថ្ងៃនេះ?',
        ai_welcome_desc: 'សួរខ្ញុំអំពី IT ការសិក្សា ការងារ ផែនការផ្ទាល់ខ្លួន ឬឱ្យខ្ញុំជួយរៀបចំកិច្ចការរបស់អ្នក។',
        ai_try_asking: 'សំណួររហ័សដែលអ្នកអាចសួរ៖',
        ai_learn_it: 'រៀន IT',
        ai_work_help: 'ជំនួយការងារ',
        ai_personal_plan: 'ផែនការផ្ទាល់ខ្លួន',
        ai_new_task: 'កិច្ចការថ្មី',
        ai_standup: 'សង្ខេបថ្ងៃនេះ',
        ai_overdue: 'ហួសកំណត់',
        ai_breakdown: 'បំបែកជំហាន',
        ai_ask_placeholder: 'សួរ Nova អំពីការងារ ការសិក្សា ជីវិត ឬអ្វីផ្សេងទៀត...',
        ai_profile_title: 'កំណត់ Nova សម្រាប់អ្នក',
        ai_profile_desc: 'ប្រាប់ Nova អំពីអ្វីដែលអ្នកចង់រៀន ការងាររបស់អ្នក និងជំនួយដែលអ្នកត្រូវការ។',
        ai_profile_occupation: 'ការងារ / តួនាទីរបស់អ្នក',
        ai_profile_level: 'កម្រិតបទពិសោធន៍',
        ai_profile_learning: 'តើអ្នកចង់រៀនជំនាញអ្វី?',
        ai_profile_work_skills: 'ជំនាញដែលប្រើ ឬត្រូវការសម្រាប់ការងារ',
        ai_profile_help: 'តើអ្នកចង់ឱ្យ Nova ជួយអ្វីខ្លះ?',
        ai_profile_other: 'គោលដៅ ឬតម្រូវការផ្សេងៗ (មិនបង្ខំ)',
        ai_profile_other_placeholder: 'ឧទាហរណ៍៖ ជួយខ្ញុំអភិវឌ្ឍភាសាអង់គ្លេសសម្រាប់ប្រជុំជាមួយអតិថិជន...',
        ai_profile_save: 'រក្សាទុកការកំណត់',
        ai_profile_button: 'កំណត់ Nova សម្រាប់ខ្ញុំ',
        ai_profile_saved: 'បានរក្សាទុកការកំណត់ AI ដោយជោគជ័យ!',
        ai_profile_required: 'សូមជ្រើសរើសការងារ ជំនាញចង់រៀន និងប្រភេទជំនួយយ៉ាងហោចណាស់មួយ។',
        ai_thinking: 'Nova កំពុងគិត...',
        ai_task_created_alert_title: 'Nova បានបង្កើតកិច្ចការ!',
        ai_task_created_alert_text: 'កិច្ចការថ្មីត្រូវបានបន្ថែមភ្លាមៗទៅ WorkMind។',
        ai_task_created_view: 'មើលកិច្ចការ',
        ai_task_created_now: 'ទើបតែបង្កើតដោយ Nova',
        ai_clear_confirm: 'តើអ្នកប្រាកដជាចង់លុបប្រវត្តិជជែកទាំងអស់មែនទេ?',
        swal_confirm_title: 'តើអ្នកប្រាកដទេ?',
        swal_delete_task_title: 'តើអ្នកប្រាកដជាចង់លុបកិច្ចការនេះទេ?',
        swal_delete_task_text: 'កិច្ចការនេះនឹងត្រូវលុបចេញពីប្រព័ន្ធ។ អ្នកមិនអាចទាញយកមកវិញបានទេ។',
        swal_delete_btn: 'បាទ/ចាស លុបចេញ',
        swal_cancel_btn: 'បោះបង់',
        swal_success_title: 'ជោគជ័យ!',
        swal_error_title: 'មានបញ្ហា!',
        swal_warning_title: 'សូមយកចិត្តទុកដាក់!',
        swal_task_created: 'កិច្ចការត្រូវបានបង្កើតដោយជោគជ័យ!',
        swal_task_updated: 'កិច្ចការត្រូវបានកែសម្រួលដោយជោគជ័យ!',
        swal_task_deleted: 'កិច្ចការត្រូវបានលុបដោយជោគជ័យ!',
        swal_enter_title: 'សូមបញ្ចូលចំណងជើងកិច្ចការជាមុនសិន!',
        swal_logout_title: 'តើអ្នកពិតជាចង់ចាកចេញមែនទេ?',
        swal_logout_text: 'អ្នកនឹងត្រូវចូលគណនីម្តងទៀតដើម្បីប្រើប្រាស់ WorkMind។',
        swal_logout_btn: 'ចាកចេញ',
        swal_clear_chat_title: 'លុបប្រវត្តិជជែក',
        swal_clear_chat_text: 'តើអ្នកប្រាកដជាចង់លុបប្រវត្តិជជែកទាំងអស់ជាមួយ AI មែនទេ?',
        swal_clear_chat_btn: 'បាទ/ចាស សម្អាត',
        swal_chat_cleared: 'ប្រវត្តិជជែកត្រូវបានសម្អាតជោគជ័យ!',
        swal_submitting: 'កំពុងដំណើរការ...',
        swal_deleting: 'កំពុងលុប...',
        swal_validation_title: 'សូមពិនិត្យមើលកំហុសខាងក្រោម',
    },
    en: {
        workspace: 'Workspace',
        account: 'Account',
        my_profile: 'My Profile',
        my_tasks: 'My Tasks',
        my_tasks_sub: 'Plan, prioritize, and track work from one interactive Kanban and list board.',
        dashboard: 'Dashboard',
        dashboard_sub: 'Stay focused on what matters most. Track deadlines, priority tasks, and progress.',
        welcome_dashboard: 'Welcome to your Dashboard',
        projects: 'Projects',
        projects_sub: 'Organize and manage tasks by project and category with live progress.',
        calendar: 'Calendar',
        priority: 'Priority',
        analytics: 'Analytics',
        analytics_sub: 'Track performance, completion rates, and workspace productivity trends.',
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
        end_date: 'End Date',
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
        ai_magic_breakdown: '✨ AI Magic Breakdown',
        task_title_or_topic: 'Task title or topic',
        task_topic_hint: 'Examples: Learn Laravel, prepare a presentation, or build a portfolio',
        ai_plan_type: 'Plan type',
        ai_suggest: '✨ AI Suggest',
        ai_copilot_detected: 'Nova detected:',
        ai_briefing_title: 'AI Daily Standup Briefing',
        ai_refresh_brief: 'Refresh Brief',
        ai_generating: 'Generating...',
        ai_subtasks_ready: 'AI Subtasks generated!',
        please_enter_title: 'Please enter a task title or topic first!',
        ai_chatbot: 'Nova AI Assistant',
        open_ai_chat: 'Open AI Chatbot',
        ai_chatbot_status: 'Online Assistant',
        ai_assistant_sub: 'IT • Work • Learning • Personal',
        ai_welcome_title: 'How can I help you today?',
        ai_welcome_desc: 'Ask me about IT, learning, work, personal planning, or let me organize your tasks and goals.',
        ai_try_asking: 'Quick suggestions:',
        ai_learn_it: 'Learn IT',
        ai_work_help: 'Work Help',
        ai_personal_plan: 'Personal Plan',
        ai_new_task: 'New Task',
        ai_standup: 'Standup',
        ai_overdue: 'Overdue',
        ai_breakdown: 'Breakdown',
        ai_ask_placeholder: 'Ask Nova about work, learning, life, or anything else...',
        ai_profile_title: 'Personalize your AI',
        ai_profile_desc: 'Tell Nova what you want to learn, what you do, and what kind of help you need.',
        ai_profile_occupation: 'Your work / role',
        ai_profile_level: 'Experience level',
        ai_profile_learning: 'What do you want to learn?',
        ai_profile_work_skills: 'Skills used or needed at work',
        ai_profile_help: 'What should Nova help you with?',
        ai_profile_other: 'Other goals or needs (optional)',
        ai_profile_other_placeholder: 'Example: Help me improve English for customer meetings...',
        ai_profile_save: 'Save personalization',
        ai_profile_button: 'Personalize Nova',
        ai_profile_saved: 'AI personalization saved!',
        ai_profile_required: 'Choose your role, at least one learning interest, and at least one help area.',
        ai_thinking: 'Nova is thinking...',
        ai_task_created_alert_title: 'Nova created a task!',
        ai_task_created_alert_text: 'The new task was added to WorkMind immediately.',
        ai_task_created_view: 'View task',
        ai_task_created_now: 'Created by Nova just now',
        ai_clear_confirm: 'Are you sure you want to clear your chat history?',
        swal_confirm_title: 'Are you sure?',
        swal_delete_task_title: 'Delete this task?',
        swal_delete_task_text: 'This task will be permanently deleted. You cannot undo this action.',
        swal_delete_btn: 'Yes, delete it',
        swal_cancel_btn: 'Cancel',
        swal_success_title: 'Success!',
        swal_error_title: 'Something went wrong!',
        swal_warning_title: 'Attention!',
        swal_task_created: 'Task created successfully!',
        swal_task_updated: 'Task updated successfully!',
        swal_task_deleted: 'Task deleted successfully!',
        swal_enter_title: 'Please enter a task title first!',
        swal_logout_title: 'Sign out of your account?',
        swal_logout_text: 'You will need to sign in again to access your tasks.',
        swal_logout_btn: 'Sign Out',
        swal_clear_chat_title: 'Clear Chat History',
        swal_clear_chat_text: 'Are you sure you want to clear your conversation history?',
        swal_clear_chat_btn: 'Yes, clear it',
        swal_chat_cleared: 'Chat history cleared successfully!',
        swal_submitting: 'Processing...',
        swal_deleting: 'Deleting...',
        swal_validation_title: 'Please check the following errors',
    }
};

let currentLang = window.localStorage.getItem('task-manager-lang') || 'en';

function applyLanguage(lang) {
    currentLang = lang;
    window.localStorage.setItem('task-manager-lang', lang);

    const flagSpan = document.querySelector('[data-lang-flag]');
    const textSpan = document.querySelector('[data-lang-text]');
    if (textSpan) {
        if (lang === 'km') {
            if (flagSpan) flagSpan.textContent = '🇰🇭';
            textSpan.textContent = 'ខ្មែរ';
        } else {
            if (flagSpan) flagSpan.textContent = '🇬🇧';
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

    document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => {
        const key = el.dataset.i18nPlaceholder;
        if (dict[key]) {
            el.setAttribute('placeholder', dict[key]);
        }
    });
}

const langToggleBtn = document.querySelector('[data-language-toggle]');
langToggleBtn?.addEventListener('click', () => {
    const nextLang = currentLang === 'km' ? 'en' : 'km';
    applyLanguage(nextLang);
});

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ============================================================================
// SWEETALERT2 ADVANCED SYSTEM & HELPERS
// ============================================================================
function isDarkMode() {
    return document.documentElement.classList.contains('dark');
}

function showToast(icon, message, title = null) {
    const isDark = isDarkMode();
    const iconColors = {
        success: '#10b981',
        error: '#f43f5e',
        warning: '#f59e0b',
        info: '#3b82f6',
    };
    return Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon || 'info',
        title: title || message,
        text: title ? message : undefined,
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
        background: isDark ? '#0f172a' : '#ffffff',
        color: isDark ? '#f8fafc' : '#0f172a',
        iconColor: iconColors[icon] || '#3b82f6',
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });
}

function showConfirmDialog(opts = {}) {
    const isDark = isDarkMode();
    const dict = i18n[currentLang] || i18n.en;

    return Swal.fire({
        title: opts.title || dict.swal_confirm_title || 'Are you sure?',
        html: opts.html || opts.text || '',
        icon: opts.icon || 'warning',
        showCancelButton: true,
        confirmButtonText: opts.confirmButtonText || (opts.isDanger !== false ? dict.swal_delete_btn : (dict.swal_confirm_title || 'Confirm')),
        cancelButtonText: opts.cancelButtonText || dict.swal_cancel_btn || 'Cancel',
        confirmButtonColor: opts.confirmButtonColor || (opts.isDanger !== false ? '#e11d48' : '#2563eb'),
        cancelButtonColor: isDark ? '#334155' : '#94a3b8',
        background: isDark ? '#0f172a' : '#ffffff',
        color: isDark ? '#f8fafc' : '#0f172a',
        reverseButtons: true,
        focusCancel: true,
    });
}

function showSuccessAlert(title, text = '') {
    const isDark = isDarkMode();
    return Swal.fire({
        icon: 'success',
        title: title,
        text: text,
        background: isDark ? '#0f172a' : '#ffffff',
        color: isDark ? '#f8fafc' : '#0f172a',
        confirmButtonColor: '#2563eb',
        confirmButtonText: currentLang === 'km' ? 'យល់ព្រម' : 'OK',
    });
}

function showErrorAlert(title, htmlOrText = '') {
    const isDark = isDarkMode();
    return Swal.fire({
        icon: 'error',
        title: title,
        html: htmlOrText,
        background: isDark ? '#0f172a' : '#ffffff',
        color: isDark ? '#f8fafc' : '#0f172a',
        confirmButtonColor: '#e11d48',
        confirmButtonText: currentLang === 'km' ? 'យល់ព្រម' : 'OK',
    });
}

window.showToast = showToast;
window.showConfirmDialog = showConfirmDialog;
window.showSuccessAlert = showSuccessAlert;
window.showErrorAlert = showErrorAlert;

// Initialize language and SweetAlert alerts on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    applyLanguage(currentLang);

    const pageSkeleton = document.getElementById('page-loading-skeleton');
    if (pageSkeleton) {
        window.requestAnimationFrame(() => {
            window.setTimeout(() => {
                pageSkeleton.classList.add('is-loaded');
                pageSkeleton.setAttribute('aria-hidden', 'true');
                window.setTimeout(() => pageSkeleton.remove(), 250);
            }, 100);
        });
    }

    const dict = i18n[currentLang] || i18n.en;

    // 1. Check for stored sessionStorage toast (e.g. from quick-add or reload)
    const storedToast = window.sessionStorage.getItem('swal_toast_success');
    if (storedToast) {
        window.sessionStorage.removeItem('swal_toast_success');
        showToast('success', storedToast);
    }

    // 2. Check for Laravel flash session success
    const flashSuccess = document.getElementById('flash-session-success');
    if (flashSuccess && flashSuccess.dataset.message) {
        showToast('success', flashSuccess.dataset.message, dict.swal_success_title);
    }

    // 3. Check for Laravel flash session error
    const flashError = document.getElementById('flash-session-error');
    if (flashError && flashError.dataset.message) {
        showErrorAlert(dict.swal_error_title, flashError.dataset.message);
    }

    // 4. Check for Laravel validation errors
    const flashErrors = document.getElementById('flash-session-errors');
    if (flashErrors && flashErrors.dataset.errors) {
        try {
            const errorList = JSON.parse(flashErrors.dataset.errors);
            if (Array.isArray(errorList) && errorList.length > 0) {
                const formattedHtml = `
                    <div class="mt-2 text-left">
                        <ul class="list-disc pl-5 space-y-1 text-xs text-rose-600 dark:text-rose-400">
                            ${errorList.map(e => `<li>${escapeHtml(e)}</li>`).join('')}
                        </ul>
                    </div>
                `;
                showErrorAlert(dict.swal_validation_title, formattedHtml);
            }
        } catch (_) {}
    }

    // 5. Auto dismiss in-page success alert smoothly
    const successAlert = document.getElementById('success-alert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            successAlert.style.opacity = '0';
            successAlert.style.transform = 'translateY(-8px)';
            setTimeout(() => successAlert.remove(), 600);
        }, 4000);
    }
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
const preferredTheme = 'light';
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
// AUTHENTICATION & CSP-SAFE FORM CONTROLS
// ============================================================================
const authSwitcher = document.querySelector('[data-auth-switcher]');

function setAuthMode(mode, updateUrl = true) {
    if (!authSwitcher) return;

    const showRegister = mode === 'register';
    const registerPane = authSwitcher.querySelector('.auth-register-pane');
    const loginPane = authSwitcher.querySelector('.auth-login-pane');

    authSwitcher.classList.toggle('show-register', showRegister);
    registerPane?.toggleAttribute('inert', !showRegister);
    loginPane?.toggleAttribute('inert', showRegister);
    registerPane?.setAttribute('aria-hidden', String(!showRegister));
    loginPane?.setAttribute('aria-hidden', String(showRegister));

    if (updateUrl) {
        const url = showRegister ? authSwitcher.dataset.registerUrl : authSwitcher.dataset.loginUrl;
        if (url) {
            const targetUrl = new URL(url, window.location.href);
            window.history.replaceState({}, '', `${targetUrl.pathname}${targetUrl.search}${targetUrl.hash}`);
        }
    }

    const activePane = showRegister ? registerPane : loginPane;
    window.setTimeout(() => activePane?.querySelector('input')?.focus({ preventScroll: true }), 350);
}

if (authSwitcher) {
    const initialMode = document.body.dataset.authMode === 'register' ? 'register' : 'login';
    setAuthMode(initialMode, false);

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
        if (!input) return;

        const reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(reveal));
        button.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
    });
});

document.querySelectorAll('[data-animated-form]').forEach((form) => {
    form.querySelectorAll('.auth-field, .auth-hint, .auth-remember, .auth-submit').forEach((element) => {
        element.classList.add('form-motion-item');
    });
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form');
    if (!form) return;

    // If form has already been confirmed by SweetAlert2, proceed normally
    if (form.dataset.swalBypassed === 'true') {
        delete form.dataset.swalBypassed;
        return;
    }

    const dict = i18n[currentLang] || i18n.en;
    const formAction = form.getAttribute('action') || '';
    const methodInput = form.querySelector('input[name="_method"]')?.value?.toUpperCase();
    const isDelete = methodInput === 'DELETE' || formAction.includes('/destroy') || (form.dataset.confirm && form.dataset.confirm.toLowerCase().includes('delete'));
    const isLogout = form.matches('[data-confirm-logout]') || formAction.endsWith('/logout');

    // 1. Intercept Task Deletion with SweetAlert2
    if (isDelete) {
        event.preventDefault();
        event.stopPropagation();

        const result = await showConfirmDialog({
            title: dict.swal_delete_task_title,
            text: dict.swal_delete_task_text,
            icon: 'warning',
            confirmButtonText: dict.swal_delete_btn,
            cancelButtonText: dict.swal_cancel_btn,
            confirmButtonColor: '#e11d48',
            isDanger: true,
        });

        if (result.isConfirmed) {
            form.dataset.swalBypassed = 'true';
            Swal.fire({
                title: dict.swal_deleting,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
                background: isDarkMode() ? '#0f172a' : '#ffffff',
                color: isDarkMode() ? '#f8fafc' : '#0f172a',
            });
            form.requestSubmit ? form.requestSubmit() : form.submit();
        }
        return;
    }

    // 2. Intercept Logout with SweetAlert2
    if (isLogout) {
        event.preventDefault();
        event.stopPropagation();

        const result = await showConfirmDialog({
            title: dict.swal_logout_title,
            text: dict.swal_logout_text,
            icon: 'question',
            confirmButtonText: dict.swal_logout_btn,
            cancelButtonText: dict.swal_cancel_btn,
            confirmButtonColor: '#2563eb',
            isDanger: false,
        });

        if (result.isConfirmed) {
            form.dataset.swalBypassed = 'true';
            form.requestSubmit ? form.requestSubmit() : form.submit();
        }
        return;
    }

    // 3. Intercept any other generic confirmation
    const confirmation = form.dataset.confirm;
    if (confirmation) {
        event.preventDefault();
        event.stopPropagation();

        const result = await showConfirmDialog({
            title: dict.swal_confirm_title,
            text: confirmation,
            icon: 'warning',
            confirmButtonText: dict.swal_confirm_title,
            cancelButtonText: dict.swal_cancel_btn,
            isDanger: false,
        });

        if (result.isConfirmed) {
            form.dataset.swalBypassed = 'true';
            form.requestSubmit ? form.requestSubmit() : form.submit();
        }
        return;
    }

    // 4. Validate Create/Edit task forms
    if (form.matches('[data-task-form], [data-edit-task-form]')) {
        const titleInput = form.querySelector('input[name="title"]');
        if (titleInput && !titleInput.value.trim()) {
            event.preventDefault();
            event.stopPropagation();

            Swal.fire({
                icon: 'warning',
                title: dict.swal_warning_title || 'Attention!',
                text: dict.swal_enter_title || 'Please enter a task title first!',
                confirmButtonColor: '#2563eb',
                confirmButtonText: currentLang === 'km' ? 'យល់ព្រម' : 'OK',
                background: isDarkMode() ? '#0f172a' : '#ffffff',
                color: isDarkMode() ? '#f8fafc' : '#0f172a',
            });
            showToast('warning', dict.swal_enter_title);

            titleInput.focus();
            titleInput.classList.add('border-rose-400', 'ring-2', 'ring-rose-200');
            setTimeout(() => titleInput.classList.remove('border-rose-400', 'ring-2', 'ring-rose-200'), 1500);
            return;
        }

        // Show SweetAlert loading dialog on valid submit
        if (!event.defaultPrevented) {
            const isEditing = form.matches('[data-edit-task-form]');
            const loadingTitle = isEditing
                ? (currentLang === 'km' ? 'កំពុងកែសម្រួលកិច្ចការ...' : 'Updating task...')
                : (currentLang === 'km' ? 'កំពុងបង្កើតកិច្ចការ...' : 'Creating task...');

            Swal.fire({
                title: loadingTitle,
                text: currentLang === 'km' ? 'សូមរង់ចាំបន្តិច...' : 'Please wait a moment...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                background: isDarkMode() ? '#0f172a' : '#ffffff',
                color: isDarkMode() ? '#f8fafc' : '#0f172a',
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
    }

    // Default button submitting animation
    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton && !event.defaultPrevented) {
        submitButton.classList.add('is-submitting');
        submitButton.disabled = true;
    }
});

document.addEventListener('change', (event) => {
    const control = event.target.closest('[data-submit-on-change]');
    control?.form?.requestSubmit();
});

document.addEventListener('click', (event) => {
    const dismissButton = event.target.closest('[data-dismiss-alert]');
    dismissButton?.closest('#success-alert')?.remove();
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
// 7. INLINE QUICK-ADD TASK WITH LIVE AI NLP DETECTION
// ============================================================================
function parseClientNlp(text) {
    let t = text.trim();
    let priority = null;
    let category = null;
    let dueDate = null;
    let dateLabel = null;

    // 1. Hashtags -> Category
    const hashMatch = t.match(/#([a-zA-Z0-9_\-]+)/i);
    if (hashMatch) {
        const catMap = {
            work: 'Work', personal: 'Personal', urgent: 'Urgent',
            design: 'Design', dev: 'Dev', study: 'Study', finance: 'Finance'
        };
        const lowerCat = hashMatch[1].toLowerCase();
        if (catMap[lowerCat]) {
            category = catMap[lowerCat];
            t = t.replace(hashMatch[0], '');
        }
    }

    // 2. Priority expressions
    if (/\b(p1|priority\s*:\s*high|priority\s+high|!high|urgent|critical|asap)\b/i.test(t)) {
        priority = 'high';
        t = t.replace(/\b(p1|priority\s*:\s*high|priority\s+high|!high|urgent|critical|asap)\b/i, '');
    } else if (/\b(p2|priority\s*:\s*medium|priority\s+medium|!medium|normal)\b/i.test(t)) {
        priority = 'medium';
        t = t.replace(/\b(p2|priority\s*:\s*medium|priority\s+medium|!medium|normal)\b/i, '');
    } else if (/\b(p3|priority\s*:\s*low|priority\s+low|!low)\b/i.test(t)) {
        priority = 'low';
        t = t.replace(/\b(p3|priority\s*:\s*low|priority\s+low|!low)\b/i, '');
    }

    // 3. Due Date expressions
    const now = new Date();
    const formatDate = (d) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };

    if (/\b(today|tonight)\b/i.test(t)) {
        dueDate = formatDate(now);
        dateLabel = 'Today';
        t = t.replace(/\b(today|tonight)\b/i, '');
    } else if (/\btomorrow\b/i.test(t)) {
        const d = new Date(now);
        d.setDate(d.getDate() + 1);
        dueDate = formatDate(d);
        dateLabel = 'Tomorrow';
        t = t.replace(/\btomorrow\b/i, '');
    } else {
        const inDaysMatch = t.match(/\bin\s+(\d+)\s+days?\b/i);
        if (inDaysMatch) {
            const count = parseInt(inDaysMatch[1], 10);
            const d = new Date(now);
            d.setDate(d.getDate() + count);
            dueDate = formatDate(d);
            dateLabel = `In ${count}d`;
            t = t.replace(inDaysMatch[0], '');
        } else {
            const daysOfWeek = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            const dayMatch = t.match(/\b(?:this\s+|by\s+|next\s+)?(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i);
            if (dayMatch) {
                const targetDayIndex = daysOfWeek.indexOf(dayMatch[1].toLowerCase());
                if (targetDayIndex !== -1) {
                    const currentDayIndex = now.getDay();
                    let diff = targetDayIndex - currentDayIndex;
                    if (diff <= 0) diff += 7;
                    const d = new Date(now);
                    d.setDate(d.getDate() + diff);
                    dueDate = formatDate(d);
                    dateLabel = dayMatch[1].charAt(0).toUpperCase() + dayMatch[1].slice(1).toLowerCase();
                    t = t.replace(dayMatch[0], '');
                }
            }
        }
    }

    const cleanTitle = t.replace(/\s+/g, ' ').replace(/\b(by|due|at|on|for)\s*$/i, '').trim();

    return {
        cleanTitle: cleanTitle || text.trim(),
        priority,
        category,
        dueDate,
        dateLabel,
    };
}

const quickAddForm = document.getElementById('quick-add-form');
const quickAddTitleInput = document.getElementById('quick-add-title');
const quickAddCategoryInput = document.getElementById('quick-add-category');
const quickAddPriorityInput = document.getElementById('quick-add-priority');
const quickAddDueDateInput = document.getElementById('quick-add-due-date');
const quickNlpPreview = document.getElementById('quick-add-nlp-preview');
const nlpChipTitle = document.getElementById('nlp-chip-title');
const nlpChipDate = document.getElementById('nlp-chip-date');
const nlpChipPriority = document.getElementById('nlp-chip-priority');
const nlpChipCategory = document.getElementById('nlp-chip-category');

// Real-time live AI preview on typing
quickAddTitleInput?.addEventListener('input', () => {
    const raw = quickAddTitleInput.value;
    if (!raw.trim() || !quickNlpPreview) {
        quickNlpPreview?.classList.add('hidden');
        return;
    }

    const parsed = parseClientNlp(raw);
    const hasMetadata = parsed.priority || parsed.category || parsed.dueDate;

    if (hasMetadata) {
        quickNlpPreview.classList.remove('hidden');
        if (nlpChipTitle) nlpChipTitle.textContent = parsed.cleanTitle;

        if (parsed.dateLabel && nlpChipDate) {
            nlpChipDate.textContent = `📅 ${parsed.dateLabel}`;
            nlpChipDate.classList.remove('hidden');
            if (quickAddDueDateInput) quickAddDueDateInput.value = parsed.dueDate;
        } else {
            nlpChipDate?.classList.add('hidden');
        }

        if (parsed.priority && nlpChipPriority) {
            nlpChipPriority.textContent = `⚡ ${parsed.priority.toUpperCase()}`;
            nlpChipPriority.classList.remove('hidden');
            if (quickAddPriorityInput) quickAddPriorityInput.value = parsed.priority;
        } else {
            nlpChipPriority?.classList.add('hidden');
        }

        if (parsed.category && nlpChipCategory) {
            nlpChipCategory.textContent = `📁 ${parsed.category}`;
            nlpChipCategory.classList.remove('hidden');
            if (quickAddCategoryInput) quickAddCategoryInput.value = parsed.category;
        } else {
            nlpChipCategory?.classList.add('hidden');
        }
    } else {
        quickNlpPreview.classList.add('hidden');
    }
});

quickAddForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const rawTitle = quickAddTitleInput?.value.trim();
    const dict = i18n[currentLang] || i18n.en;

    if (!rawTitle) {
        showToast('warning', dict.swal_enter_title);
        quickAddTitleInput?.focus();
        return;
    }

    const parsed = parseClientNlp(rawTitle);
    const submitBtn = document.getElementById('quick-add-submit');

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = '...';
    }

    const finalTitle = parsed.cleanTitle || rawTitle;
    const finalCategory = quickAddCategoryInput?.value || parsed.category || null;
    const finalPriority = quickAddPriorityInput?.value || parsed.priority || 'medium';
    const finalDueDate = quickAddDueDateInput?.value || parsed.dueDate || null;

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
                title: finalTitle,
                category: finalCategory,
                priority: finalPriority,
                due_date: finalDueDate,
            }),
        });

        if (res.ok) {
            quickAddTitleInput.value = '';
            quickNlpPreview?.classList.add('hidden');
            window.sessionStorage.setItem('swal_toast_success', dict.swal_task_created);
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
            <button type="button" class="text-slate-400 hover:text-rose-500" data-remove-create-subtask="${i}">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    `).join('');
}

function removeCreateSubtask(index) {
    createSubtasks.splice(index, 1);
    renderCreateSubtasks();
}

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
                <input type="checkbox" ${st.completed ? 'checked' : ''} data-toggle-edit-subtask="${i}" class="rounded border-slate-300 text-blue-600">
                <span class="${st.completed ? 'line-through text-slate-400' : ''}">${escapeHtml(st.title)}</span>
            </label>
            <button type="button" class="text-slate-400 hover:text-rose-500" data-remove-edit-subtask="${i}">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    `).join('');
}

function toggleEditSubtaskCompleted(index, checked) {
    if (editSubtasks[index]) {
        editSubtasks[index].completed = checked;
        renderEditSubtasks();
    }
}

function removeEditSubtask(index) {
    editSubtasks.splice(index, 1);
    renderEditSubtasks();
}

document.addEventListener('click', (event) => {
    const createRemoveButton = event.target.closest('[data-remove-create-subtask]');
    if (createRemoveButton) {
        removeCreateSubtask(Number.parseInt(createRemoveButton.dataset.removeCreateSubtask, 10));
        return;
    }

    const editRemoveButton = event.target.closest('[data-remove-edit-subtask]');
    if (editRemoveButton) {
        removeEditSubtask(Number.parseInt(editRemoveButton.dataset.removeEditSubtask, 10));
    }
});

document.addEventListener('change', (event) => {
    const checkbox = event.target.closest('[data-toggle-edit-subtask]');
    if (checkbox) {
        toggleEditSubtaskCompleted(Number.parseInt(checkbox.dataset.toggleEditSubtask, 10), checkbox.checked);
    }
});

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

function openTaskModal(prefilledCategory = null) {
    createSubtasks = [];
    renderCreateSubtasks();
    const breakdownInsight = document.getElementById('create-ai-breakdown-insight');
    breakdownInsight?.classList.add('hidden');
    if (breakdownInsight) breakdownInsight.innerHTML = '';
    const breakdownType = document.getElementById('create_breakdown_type');
    if (breakdownType) breakdownType.value = 'auto';
    if (prefilledCategory) {
        const catSelect = modal?.querySelector('select[name="category"]');
        if (catSelect) catSelect.value = prefilledCategory;
    }
    modal?.classList.remove('hidden');
    modal?.classList.add('flex');
    modal?.querySelector('input[name="title"]')?.focus();
}

function closeTaskModal() {
    modal?.classList.add('hidden');
    modal?.classList.remove('flex');
}

function openEditModal() {
    const breakdownInsight = document.getElementById('edit-ai-breakdown-insight');
    breakdownInsight?.classList.add('hidden');
    if (breakdownInsight) breakdownInsight.innerHTML = '';
    const breakdownType = document.getElementById('edit_breakdown_type');
    if (breakdownType) breakdownType.value = 'auto';
    editModal?.classList.remove('hidden');
    editModal?.classList.add('flex');
    editModal?.querySelector('[name="title"]')?.focus();
}

function closeEditModal() {
    editModal?.classList.add('hidden');
    editModal?.classList.remove('flex');
}

window.openTaskModal = openTaskModal;
window.closeTaskModal = closeTaskModal;
window.openEditModal = openEditModal;
window.closeEditModal = closeEditModal;

document.addEventListener('click', (e) => {
    const openBtn = e.target.closest('[data-open-task-modal], a[href$="/tasks/create"], a[href*="/tasks/create?"]');
    if (openBtn && modal) {
        e.preventDefault();
        let cat = openBtn.dataset.category;
        if (!cat && openBtn.tagName === 'A' && openBtn.href) {
            try {
                cat = new URL(openBtn.href, window.location.origin).searchParams.get('category');
            } catch (_) {}
        }
        openTaskModal(cat);
        return;
    }

    if (e.target.closest('[data-close-task-modal]')) {
        closeTaskModal();
        return;
    }

    if (e.target.closest('[data-close-edit-modal]')) {
        closeEditModal();
        return;
    }
});

modal?.addEventListener('click', (e) => {
    if (e.target === modal) closeTaskModal();
});

editModal?.addEventListener('click', (e) => {
    if (e.target === editModal) closeEditModal();
});

// Auto-open modal if URL query param ?create=1 or ?open_create=1 or ?edit=ID is present
try {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('create') || urlParams.has('open_create')) {
        const cat = urlParams.get('category');
        setTimeout(() => openTaskModal(cat), 150);
    }
    if (urlParams.has('edit') || urlParams.has('open_edit')) {
        const editId = urlParams.get('edit') || urlParams.get('open_edit');
        if (editId) {
            setTimeout(() => openEditById(editId), 150);
        }
    }
} catch (_) {}

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

        const setVal = (selector, val) => {
            const el = editForm.querySelector(selector);
            if (el) el.value = val ?? '';
        };

        setVal('[name="title"]', task.title);
        setVal('[name="description"]', task.description);
        setVal('[name="category"]', task.category);
        setVal('[name="priority"]', task.priority ?? 'medium');
        setVal('[name="status"]', task.status ?? 'pending');
        setVal('[name="due_date"]', task.due_date);
        setVal('[name="end_date"]', task.end_date);

        const pinCheck = editForm.querySelector('[name="is_pinned"]');
        if (pinCheck) pinCheck.checked = Boolean(task.is_pinned);

        editSubtasks = Array.isArray(task.subtasks) ? [...task.subtasks] : [];
        renderEditSubtasks();

        openEditModal();
    } catch (_err) {
        console.error('Failed to open edit modal, falling back to page edit:', _err);
        window.location.href = `/tasks/${taskId}/edit`;
    }
}

document.addEventListener('click', (e) => {
    const editBtn = e.target.closest('[data-edit-task-id], a[href*="/tasks/"][href$="/edit"]');
    if (editBtn && editModal) {
        let taskId = editBtn.dataset.editTaskId;
        if (!taskId && editBtn.tagName === 'A' && editBtn.href) {
            const match = editBtn.href.match(/\/tasks\/(\d+)\/edit/);
            if (match) taskId = match[1];
        }
        if (taskId) {
            e.preventDefault();
            openEditById(taskId);
        }
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
        } else if (cmd === 'open-ai-chat') {
            openAiChat();
        } else if (cmd === 'ai-standup') {
            openAiChat('standup');
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
        closeAiChat();
        document.querySelector('[data-notifications-panel]')?.classList.add('hidden');
    }
});


// ============================================================================
// 11. DASHBOARD ANIMATED COUNTERS
// ============================================================================
const dashboard = document.querySelector('[data-dashboard]');
const miniCalendar = document.querySelector('[data-mini-calendar]');
if (miniCalendar) {
    const events = JSON.parse(miniCalendar.dataset.events || '{}');
    const [year, month] = miniCalendar.dataset.month.split('-').map(Number);
    let visibleMonth = new Date(year, month - 1, 1);
    const dateKey = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    const renderCalendar = () => {
        miniCalendar.querySelector('[data-calendar-title]').textContent = visibleMonth.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        const firstDay = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth(), 1);
        const daysInMonth = new Date(firstDay.getFullYear(), firstDay.getMonth() + 1, 0).getDate();
        const cellCount = Math.ceil((firstDay.getDay() + daysInMonth) / 7) * 7;
        const grid = miniCalendar.querySelector('[data-calendar-dates]');
        grid.replaceChildren();
        for (let index = 0; index < cellCount; index++) {
            const date = new Date(firstDay.getFullYear(), firstDay.getMonth(), 1 - firstDay.getDay() + index);
            const key = dateKey(date);
            const cell = document.createElement('a');
            cell.className = 'calendar-date';
            cell.classList.toggle('outside-month', date.getMonth() !== visibleMonth.getMonth());
            cell.classList.toggle('is-today', key === miniCalendar.dataset.today);
            cell.href = `${miniCalendar.dataset.calendarUrl}#date-${key}`;
            cell.setAttribute('aria-label', date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }));
            if (key === miniCalendar.dataset.today) cell.setAttribute('aria-current', 'date');
            const number = document.createElement('span');
            number.textContent = date.getDate();
            const dots = document.createElement('span');
            dots.className = 'calendar-event-dots';
            (events[key] || []).slice(0, 3).forEach((priority) => {
                const dot = document.createElement('i');
                dot.className = 'priority-dot';
                if (['low', 'medium', 'high'].includes(priority)) dot.classList.add(priority);
                dots.append(dot);
            });
            cell.append(number, dots);
            grid.append(cell);
        }
    };
    miniCalendar.querySelectorAll('[data-calendar-step]').forEach((button) => {
        button.addEventListener('click', () => {
            visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + Number(button.dataset.calendarStep), 1);
            renderCalendar();
        });
    });
    miniCalendar.querySelector('[data-calendar-today]').addEventListener('click', () => {
        const [todayYear, todayMonth] = miniCalendar.dataset.today.split('-').map(Number);
        visibleMonth = new Date(todayYear, todayMonth - 1, 1);
        renderCalendar();
    });
}

const profileTabButtons = document.querySelectorAll('[data-profile-tab]');
const profileTabPanels = document.querySelectorAll('[data-profile-panel]');
const profileForm = document.querySelector('[data-profile-form]');
const profileSectionInput = profileForm?.querySelector('[data-profile-section]');
const profileSaveText = profileForm?.querySelector('[data-profile-save-text]');
const profileSectionChoices = profileForm?.querySelectorAll('[data-profile-section-choice]') ?? [];

function syncProfileSectionTabs() {
    const selectedSections = new Set(
        [...profileSectionChoices]
            .filter((choice) => choice.checked)
            .map((choice) => choice.value),
    );

    profileTabButtons.forEach((button) => {
        if (!button.hasAttribute('data-profile-optional-tab')) return;

        const isVisible = selectedSections.has(button.dataset.profileTab);
        button.classList.toggle('hidden', !isVisible);
        button.classList.toggle('inline-flex', isVisible);
    });

    const activeTab = [...profileTabButtons].find((button) => button.getAttribute('aria-selected') === 'true');
    if (activeTab?.classList.contains('hidden')) activateProfileTab('personal');
}

function activateProfileTab(tabName) {
    profileTabButtons.forEach((button) => {
        const isActive = button.dataset.profileTab === tabName;
        button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        button.classList.toggle('border-blue-600', isActive);
        button.classList.toggle('text-blue-600', isActive);
        button.classList.toggle('dark:text-blue-400', isActive);
        button.classList.toggle('border-transparent', !isActive);
        button.classList.toggle('text-slate-400', !isActive);
    });

    profileTabPanels.forEach((panel) => {
        const isActive = panel.dataset.profilePanel === tabName;
        panel.classList.toggle('hidden', !isActive);
        panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');

        panel.querySelectorAll('input, select, textarea').forEach((control) => {
            if (!Object.hasOwn(control.dataset, 'profileOriginallyDisabled')) {
                control.dataset.profileOriginallyDisabled = control.disabled ? 'true' : 'false';
            }

            control.disabled = !isActive || control.dataset.profileOriginallyDisabled === 'true';
        });
    });

    if (profileSectionInput) profileSectionInput.value = tabName;
    if (profileSaveText) profileSaveText.textContent = `Save ${tabName} information`;

    window.history.replaceState(null, '', tabName === 'personal' ? window.location.pathname : `#${tabName}`);
}

profileTabButtons.forEach((button) => {
    button.addEventListener('click', () => activateProfileTab(button.dataset.profileTab));
});

if (profileTabButtons.length) {
    syncProfileSectionTabs();
    const requestedTab = window.location.hash.replace('#', '');
    const validTab = [...profileTabButtons].some((button) => (
        button.dataset.profileTab === requestedTab && !button.classList.contains('hidden')
    ));
    activateProfileTab(validTab ? requestedTab : 'personal');
}

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
function closeClickDropdowns(except = null) {
    document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
        if (dropdown === except) return;

        dropdown.querySelector('[data-dropdown-menu]')?.classList.add('hidden');
        const toggle = dropdown.querySelector('[data-dropdown-toggle]');
        toggle?.setAttribute('aria-expanded', 'false');
        dropdown.querySelector('[data-dropdown-chevron]')?.classList.remove('rotate-180');
    });
}

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-dropdown-toggle]');
    if (toggle) {
        event.preventDefault();
        event.stopPropagation();

        const dropdown = toggle.closest('[data-dropdown]');
        const menu = dropdown?.querySelector('[data-dropdown-menu]');
        const shouldOpen = menu?.classList.contains('hidden');

        closeClickDropdowns(dropdown);
        notificationPanel?.classList.add('hidden');
        menu?.classList.toggle('hidden', !shouldOpen);
        toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        dropdown?.querySelector('[data-dropdown-chevron]')?.classList.toggle('rotate-180', Boolean(shouldOpen));

        if (shouldOpen) {
            menu?.querySelector('[role="menuitem"]')?.focus({ preventScroll: true });
        }
        return;
    }

    if (!event.target.closest('[data-dropdown]')) {
        closeClickDropdowns();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    const openToggle = document.querySelector('[data-dropdown-toggle][aria-expanded="true"]');
    if (openToggle) {
        closeClickDropdowns();
        openToggle.focus();
    }
});

const notificationBtn = document.querySelector('[data-toggle-notifications]');
const notificationPanel = document.querySelector('[data-notifications-panel]');

notificationBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    closeClickDropdowns();
    notificationPanel?.classList.toggle('hidden');
});

notificationPanel?.addEventListener('click', (e) => e.stopPropagation());

document.addEventListener('click', () => {
    notificationPanel?.classList.add('hidden');
});



// ============================================================================
// 13. AI COPILOT INTERACTIVE CONTROLLER
// ============================================================================

// 13.1. 1-Click AI Breakdown directly on any task card
document.addEventListener('click', async (e) => {
    const aiCardBtn = e.target.closest('[data-ai-card-breakdown]');
    if (!aiCardBtn) return;
    e.preventDefault();
    e.stopPropagation();

    const taskId = aiCardBtn.dataset.aiCardBreakdown;
    if (!taskId || aiCardBtn.dataset.loading === 'true') return;

    aiCardBtn.dataset.loading = 'true';
    const originalHtml = aiCardBtn.innerHTML;
    aiCardBtn.innerHTML = `
        <svg class="h-3.5 w-3.5 animate-spin text-purple-600 dark:text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
        </svg>
        <span class="text-[10px] font-bold text-purple-700 dark:text-purple-300">${currentLang === 'km' ? 'កំពុងបង្កើត...' : 'AI thinking...'}</span>
    `;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    try {
        const res = await fetch(`/tasks/${taskId}/ai-breakdown`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            body: JSON.stringify({ lang: currentLang }),
        });

        if (res.ok) {
            const data = await res.json();
            if (data.success && Array.isArray(data.subtasks)) {
                playTaskChime();
                fireConfetti();

                // Hide the trigger button
                const slot = document.getElementById(`ai-breakdown-slot-${taskId}`);
                if (slot) slot.classList.add('hidden');

                // Render or update subtasks list container
                const container = document.getElementById(`card-subtasks-container-${taskId}`);
                if (container) {
                    container.innerHTML = `
                        <div class="mt-3 rounded-xl border border-purple-200/80 bg-purple-50/40 p-2.5 dark:border-purple-900/50 dark:bg-purple-950/20">
                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-700 dark:text-slate-200">
                                <span class="flex items-center gap-1.5">
                                    <svg class="h-3 w-3 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                                    <span>Checklist (<span id="card-completed-count-${taskId}">0</span>/<span id="card-total-count-${taskId}">${data.subtasks_count}</span>)</span>
                                </span>
                                <span class="text-purple-600 dark:text-purple-400 font-bold" id="card-progress-text-${taskId}">${data.subtasks_progress}%</span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div id="card-progress-bar-${taskId}" class="h-full bg-emerald-500 transition-all duration-300" style="width: ${data.subtasks_progress}%"></div>
                            </div>
                            <div class="mt-2 space-y-1.5" id="card-subtasks-list-${taskId}">
                                ${data.subtasks.map((st) => `
                                    <label class="flex items-center gap-2 cursor-pointer text-[11px] text-slate-700 dark:text-slate-300">
                                        <input
                                            type="checkbox"
                                            data-task-subtask-toggle="${taskId}"
                                            data-subtask-id="${st.id ?? ''}"
                                            ${st.completed ? 'checked' : ''}
                                            class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-700"
                                        >
                                        <span class="${st.completed ? 'line-through text-slate-400' : ''}">
                                            ${escapeHtml(st.title ?? '')}
                                        </span>
                                    </label>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }
            }
        }
    } catch (_err) {
        // silent fail
    } finally {
        aiCardBtn.dataset.loading = 'false';
        aiCardBtn.innerHTML = originalHtml;
    }
});

// 13.2. Modal AI Magic Breakdown Buttons
function renderAiBreakdownInsight(isEdit, data) {
    const insight = document.getElementById(isEdit ? 'edit-ai-breakdown-insight' : 'create-ai-breakdown-insight');
    if (!insight) return;

    const planLabels = {
        task: currentLang === 'km' ? 'កិច្ចការអនុវត្ត' : 'Action task',
        learning: currentLang === 'km' ? 'ផែនការសិក្សា' : 'Learning roadmap',
        project: currentLang === 'km' ? 'ផែនការគម្រោង' : 'Project plan',
        personal: currentLang === 'km' ? 'គោលដៅផ្ទាល់ខ្លួន' : 'Personal goal',
    };
    const totalMinutes = Number.parseInt(data.estimated_minutes, 10) || 0;
    const duration = totalMinutes >= 60
        ? `${Math.floor(totalMinutes / 60)}h ${totalMinutes % 60 ? `${totalMinutes % 60}m` : ''}`.trim()
        : `${totalMinutes}m`;
    const tags = Array.isArray(data.suggested_tags) ? data.suggested_tags.slice(0, 4) : [];

    insight.innerHTML = `
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full bg-purple-600 px-2.5 py-1 text-[10px] font-bold text-white">${escapeHtml(planLabels[data.plan_type] || 'AI plan')}</span>
            <span class="text-[11px] font-semibold text-purple-700 dark:text-purple-300">${data.subtasks?.length || 0} ${currentLang === 'km' ? 'ជំហាន' : 'steps'} · ${escapeHtml(duration)}</span>
            ${tags.map((tag) => `<span class="rounded-full bg-white/80 px-2 py-1 text-[10px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">#${escapeHtml(tag)}</span>`).join('')}
        </div>
        ${data.outcome ? `<p class="mt-2 text-xs font-bold leading-5 text-slate-800 dark:text-slate-100">${escapeHtml(data.outcome)}</p>` : ''}
        ${data.summary ? `<p class="mt-1 text-[11px] leading-5 text-slate-600 dark:text-slate-400">${escapeHtml(data.summary)}</p>` : ''}
    `;
    insight.classList.remove('hidden');
}

async function handleModalAiBreakdown(isEdit = false) {
    const titleInput = isEdit ? document.getElementById('edit_title') : document.getElementById('create_title');
    const descInput = isEdit ? document.getElementById('edit_description') : document.getElementById('create_description');
    const catInput = isEdit ? document.getElementById('edit_category') : document.getElementById('create_category');
    const prioInput = isEdit ? document.getElementById('edit_priority') : document.getElementById('create_priority');
    const planTypeInput = isEdit ? document.getElementById('edit_breakdown_type') : document.getElementById('create_breakdown_type');
    const breakdownBtn = isEdit ? document.getElementById('edit-ai-breakdown-btn') : document.getElementById('create-ai-breakdown-btn');

    const title = titleInput?.value.trim();
    if (!title) {
        titleInput?.focus();
        titleInput?.classList.add('border-rose-400', 'ring-2', 'ring-rose-200');
        setTimeout(() => titleInput?.classList.remove('border-rose-400', 'ring-2', 'ring-rose-200'), 1500);
        showToast('warning', (i18n[currentLang] || i18n.en).please_enter_title);
        return;
    }

    if (breakdownBtn) {
        breakdownBtn.disabled = true;
        breakdownBtn.dataset.origHtml = breakdownBtn.innerHTML;
        breakdownBtn.innerHTML = `
            <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
                <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
            </svg>
            <span>${currentLang === 'km' ? 'កំពុងបង្កើត...' : 'AI thinking...'}</span>
        `;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    try {
        const res = await fetch('/tasks/ai/breakdown', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            body: JSON.stringify({
                title,
                description: descInput?.value || null,
                category: catInput?.value || null,
                plan_type: planTypeInput?.value || 'auto',
                lang: currentLang,
            }),
        });

        if (!res.ok) {
            throw new Error('Unable to generate an AI plan.');
        }

        const data = await res.json();
        if (!data.success || !Array.isArray(data.subtasks)) {
            throw new Error('The AI plan response was incomplete.');
        }

        const currentSubtasks = isEdit ? editSubtasks : createSubtasks;
        const existingTitles = new Set(currentSubtasks.map((item) => String(item.title || '').trim().toLocaleLowerCase()));
        const uniqueSubtasks = data.subtasks.filter((item) => {
            const normalizedTitle = String(item.title || '').trim().toLocaleLowerCase();
            if (!normalizedTitle || existingTitles.has(normalizedTitle)) return false;
            existingTitles.add(normalizedTitle);
            return true;
        });

        if (isEdit) {
            editSubtasks = [...editSubtasks, ...uniqueSubtasks];
            renderEditSubtasks();
        } else {
            createSubtasks = [...createSubtasks, ...uniqueSubtasks];
            renderCreateSubtasks();
        }

        if (catInput && !catInput.value && data.suggested_category) {
            catInput.value = data.suggested_category;
        }

        if (prioInput && data.suggested_priority) {
            prioInput.value = data.suggested_priority;
        }

        renderAiBreakdownInsight(isEdit, data);
        playTaskChime();
        showToast('success', currentLang === 'km'
            ? `Nova បានបង្កើតផែនការ ${uniqueSubtasks.length} ជំហាន។`
            : `Nova created a ${uniqueSubtasks.length}-step plan.`);
    } catch (_err) {
        showErrorAlert(
            currentLang === 'km' ? 'មិនអាចបង្កើតផែនការបានទេ' : 'Could not generate the plan',
            currentLang === 'km' ? 'សូមព្យាយាមម្តងទៀតក្នុងពេលបន្តិច។' : 'Please check your connection and try again.',
        );
    } finally {
        if (breakdownBtn) {
            breakdownBtn.disabled = false;
            breakdownBtn.innerHTML = breakdownBtn.dataset.origHtml || '✨ AI Magic Breakdown';
        }
    }
}

document.getElementById('create-ai-breakdown-btn')?.addEventListener('click', () => handleModalAiBreakdown(false));
document.getElementById('edit-ai-breakdown-btn')?.addEventListener('click', () => handleModalAiBreakdown(true));

// 13.3. Modal AI Suggest / Enhance Buttons
async function handleModalAiSuggest(isEdit = false) {
    const titleInput = isEdit ? document.getElementById('edit_title') : document.getElementById('create_title');
    const descInput = isEdit ? document.getElementById('edit_description') : document.getElementById('create_description');
    const catInput = isEdit ? document.getElementById('edit_category') : document.getElementById('create_category');
    const prioInput = isEdit ? document.getElementById('edit_priority') : document.getElementById('create_priority');
    const suggestBtn = isEdit ? document.getElementById('edit-ai-suggest-btn') : document.getElementById('create-ai-suggest-btn');

    const title = titleInput?.value.trim();
    if (!title) {
        titleInput?.focus();
        titleInput?.classList.add('border-rose-400', 'ring-2', 'ring-rose-200');
        setTimeout(() => titleInput?.classList.remove('border-rose-400', 'ring-2', 'ring-rose-200'), 1500);
        return;
    }

    if (suggestBtn) {
        suggestBtn.disabled = true;
        suggestBtn.dataset.origHtml = suggestBtn.innerHTML;
        suggestBtn.innerHTML = `
            <svg class="h-3 w-3 animate-spin text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
                <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
            </svg>
            <span class="text-[11px] font-bold text-purple-700">${currentLang === 'km' ? 'កំពុងវិភាគ...' : 'Analyzing...'}</span>
        `;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    try {
        const res = await fetch('/tasks/ai/enhance', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            body: JSON.stringify({
                title,
                description: descInput?.value || null,
                lang: currentLang,
            }),
        });

        if (res.ok) {
            const data = await res.json();
            if (data.success) {
                if (data.title && titleInput) titleInput.value = data.title;
                if (data.description && descInput && (!descInput.value || descInput.value.length < 10)) {
                    descInput.value = data.description;
                }
                if (catInput && data.suggested_category) {
                    catInput.value = data.suggested_category;
                }
                if (prioInput && data.suggested_priority) {
                    prioInput.value = data.suggested_priority;
                }
                playTaskChime();
            }
        }
    } catch (_err) {
        // silent fail
    } finally {
        if (suggestBtn) {
            suggestBtn.disabled = false;
            suggestBtn.innerHTML = suggestBtn.dataset.origHtml || '✨ AI Suggest';
        }
    }
}

document.getElementById('create-ai-suggest-btn')?.addEventListener('click', () => handleModalAiSuggest(false));
document.getElementById('edit-ai-suggest-btn')?.addEventListener('click', () => handleModalAiSuggest(true));


// ============================================================================
// 14. INTERACTIVE AI COPILOT CHATBOT SYSTEM & NIU LOTTIE ANIMATIONS
// ============================================================================
let niuJsonData = null;
let niuJsonPromise = null;

function fetchNiuJson() {
    if (niuJsonData) return Promise.resolve(niuJsonData);
    if (!niuJsonPromise) {
        niuJsonPromise = fetch('/images/niu.json')
            .then(res => {
                if (!res.ok) throw new Error('Failed to load niu.json');
                return res.json();
            })
            .then(data => {
                niuJsonData = data;
                return data;
            })
            .catch(err => {
                console.error('Error fetching niu.json:', err);
                return null;
            });
    }
    return niuJsonPromise;
}

function renderNiuLottie(container, opts = {}) {
    if (!container) return null;
    if (container.dataset.lottieLoaded === 'true') return null;

    container.innerHTML = '';

    const startAnim = (data) => {
        if (!data || !container.isConnected) return null;
        try {
            container.dataset.lottieLoaded = 'true';
            const clone = typeof structuredClone === 'function' ? structuredClone(data) : JSON.parse(JSON.stringify(data));
            const anim = lottie.loadAnimation({
                container,
                renderer: 'svg',
                loop: opts.loop !== false,
                autoplay: opts.autoplay !== false,
                animationData: clone,
                rendererSettings: {
                    preserveAspectRatio: opts.preserveAspectRatio || 'xMidYMid meet',
                    clearCanvas: true,
                },
            });
            container._lottieAnim = anim;
            return anim;
        } catch (err) {
            console.error('Error initializing Lottie niu:', err);
            container.dataset.lottieLoaded = 'false';
            return null;
        }
    };

    if (niuJsonData) {
        return startAnim(niuJsonData);
    } else {
        fetchNiuJson().then(data => startAnim(data));
    }
}

function initAllNiuLotties(root = document) {
    if (!root) return;
    root.querySelectorAll('[data-lottie="niu"]').forEach(el => {
        if (el.dataset.lottieLoaded !== 'true') {
            renderNiuLottie(el);
        } else if (el._lottieAnim) {
            el._lottieAnim.resize();
            el._lottieAnim.play();
        }
    });
}

window.renderNiuLottie = renderNiuLottie;
window.initAllNiuLotties = initAllNiuLotties;

const aiChatDrawer = document.getElementById('ai-chatbot-drawer');
const aiChatLauncher = document.getElementById('ai-chatbot-launcher');
const aiChatLauncherBtn = document.getElementById('ai-chatbot-launcher-btn');
const aiChatCloseBtn = document.getElementById('ai-chat-close-btn');
const aiChatClearBtn = document.getElementById('ai-chat-clear-btn');
const aiChatMessagesCont = document.getElementById('ai-chat-messages');
const aiChatThread = document.getElementById('ai-chat-thread');
const aiChatWelcome = document.getElementById('ai-chat-welcome');
const aiChatTyping = document.getElementById('ai-chat-typing');
const aiChatHistorySkeleton = document.getElementById('ai-chat-history-skeleton');
const aiChatForm = document.getElementById('ai-chat-form');
const aiChatInput = document.getElementById('ai-chat-input');
const aiChatSendBtn = document.getElementById('ai-chat-send-btn');
const aiChatTools = document.getElementById('ai-chat-tools');
const aiChatComposer = document.getElementById('ai-chat-composer');
const aiProfilePanel = document.getElementById('ai-profile-panel');
const aiProfileForm = document.getElementById('ai-profile-form');
const aiProfileOpenBtn = document.getElementById('ai-profile-open-btn');
const aiProfileWelcomeBtn = document.getElementById('ai-profile-welcome-btn');
const aiProfileCancelBtn = document.getElementById('ai-profile-cancel-btn');
const aiProfileSaveBtn = document.getElementById('ai-profile-save-btn');
const aiProfileError = document.getElementById('ai-profile-error');

let aiChatHistoryLoaded = false;
let isAiResponding = false;
let aiPreferencesLoaded = false;
let aiPreferencesConfigured = false;
const deliveredAiTaskAlerts = new Set();
let aiTaskAlertChannel = null;

try {
    if ('BroadcastChannel' in window) {
        aiTaskAlertChannel = new BroadcastChannel('workmind-task-alerts');
    }
} catch (_error) {
    aiTaskAlertChannel = null;
}

function getAiTaskUrl(task) {
    const fallback = task?.task_id ? `/tasks/${encodeURIComponent(task.task_id)}` : '/tasks';
    try {
        const url = new URL(task?.task_url || fallback, window.location.origin);
        return url.origin === window.location.origin ? `${url.pathname}${url.search}${url.hash}` : fallback;
    } catch (_error) {
        return fallback;
    }
}

function addAiTaskToNotificationPanel(task) {
    const notificationList = document.querySelector('[data-notification-list]');
    const notificationCount = document.querySelector('[data-notification-count]');
    if (!notificationList || !notificationCount) return;

    notificationList.querySelector('[data-notifications-empty]')?.remove();

    const alertId = `ai-task-${task.task_id ?? Date.now()}`;
    if (notificationList.querySelector(`[data-ai-task-alert-id="${alertId}"]`)) return;

    const dict = i18n[currentLang] || i18n.en;
    const item = document.createElement('a');
    item.href = getAiTaskUrl(task);
    item.dataset.aiTaskAlertId = alertId;
    if (task.notification_id) item.dataset.notificationId = task.notification_id;
    item.className = `flex gap-3 border-b border-slate-100 px-4 py-3 transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/50 last:border-b-0 ${task.read ? 'opacity-65' : ''}`;
    item.innerHTML = `
        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-bold text-slate-950 dark:text-white">${escapeHtml(task.title || 'New task')}</span>
            <span class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">${escapeHtml(task.time_ago ? `Created by Nova · ${task.time_ago}` : dict.ai_task_created_now)}</span>
        </span>
        ${task.read
            ? '<span class="mt-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-bold uppercase text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200">AI</span>'
            : '<span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-blue-500" aria-label="Unread"></span>'}
    `;
    notificationList.prepend(item);

    const currentCount = Number.parseInt(notificationCount.textContent, 10) || 0;
    const nextCount = currentCount + 1;
    notificationCount.textContent = nextCount > 99 ? '99+' : String(nextCount);
    notificationCount.classList.remove('hidden');
    notificationCount.classList.add('flex');
}

function setPersistentTaskAlertCount(unreadAiCount) {
    const notificationCount = document.querySelector('[data-notification-count]');
    if (!notificationCount) return;

    const reminderCount = Number.parseInt(notificationCount.dataset.reminderCount, 10) || 0;
    const total = reminderCount + (Number.parseInt(unreadAiCount, 10) || 0);
    notificationCount.textContent = total > 99 ? '99+' : String(total);
    notificationCount.classList.toggle('hidden', total === 0);
    notificationCount.classList.toggle('flex', total > 0);
}

let taskAlertSyncInFlight = false;

async function syncPersistentAiTaskAlerts() {
    if (!document.querySelector('[data-notifications-root]') || document.hidden || taskAlertSyncInFlight) return;

    taskAlertSyncInFlight = true;

    try {
        const response = await fetch('/api/task-alerts', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!response.ok) return;

        const data = await response.json();
        if (!data.success) return;

        setPersistentTaskAlertCount(data.unread_count);
        (data.notifications || []).slice().reverse().forEach((task) => {
            const taskKey = String(task.task_id ?? '');
            const createdAt = task.created_at ? Date.parse(task.created_at) : 0;
            const isRecentUnread = !task.read && createdAt > 0 && (Date.now() - createdAt) < 30000;

            if (isRecentUnread && taskKey && !deliveredAiTaskAlerts.has(taskKey)) {
                showAiTaskCreatedAlert(task, { broadcast: false, celebrate: false });
            } else {
                addAiTaskToNotificationPanel(task);
                if (taskKey) deliveredAiTaskAlerts.add(taskKey);
            }
        });
    } catch (_error) {
        // Keep the rest of the application usable while temporarily offline.
    } finally {
        taskAlertSyncInFlight = false;
    }
}

function showAiTaskCreatedAlert(task, { broadcast = true, celebrate = true } = {}) {
    if (!task?.task_id || deliveredAiTaskAlerts.has(String(task.task_id))) return;
    deliveredAiTaskAlerts.add(String(task.task_id));

    const dict = i18n[currentLang] || i18n.en;
    const taskUrl = getAiTaskUrl(task);
    addAiTaskToNotificationPanel(task);
    window.dispatchEvent(new CustomEvent('workmind:task-created', { detail: task }));

    if (celebrate) {
        playTaskChime();
        triggerConfetti();
    }

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: dict.ai_task_created_alert_title,
        html: `<strong>${escapeHtml(task.title || 'New task')}</strong><br><span style="font-size:11px;opacity:.72">${escapeHtml(dict.ai_task_created_alert_text)}</span>`,
        showConfirmButton: true,
        confirmButtonText: dict.ai_task_created_view,
        confirmButtonColor: '#2563eb',
        showCloseButton: true,
        timer: 7000,
        timerProgressBar: true,
        background: isDarkMode() ? '#0f172a' : '#ffffff',
        color: isDarkMode() ? '#f8fafc' : '#0f172a',
    }).then((result) => {
        if (result.isConfirmed) window.location.assign(taskUrl);
    });

    if (broadcast && aiTaskAlertChannel) {
        aiTaskAlertChannel.postMessage({ type: 'task_created', task });
    }
}

if (aiTaskAlertChannel) {
    aiTaskAlertChannel.addEventListener('message', (event) => {
        if (event.data?.type === 'task_created') {
            showAiTaskCreatedAlert(event.data.task, { broadcast: false, celebrate: false });
        }
    });
}

document.addEventListener('click', async (event) => {
    const notificationLink = event.target.closest('[data-notification-id]');
    if (notificationLink) {
        event.preventDefault();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        try {
            await fetch(`/api/task-alerts/${encodeURIComponent(notificationLink.dataset.notificationId)}/read`, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                credentials: 'same-origin',
            });
        } finally {
            window.location.assign(notificationLink.href);
        }
        return;
    }

    const markAllButton = event.target.closest('[data-mark-all-task-alerts-read]');
    if (markAllButton) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch('/api/task-alerts/read-all', {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            credentials: 'same-origin',
        });
        if (response.ok) {
            document.querySelectorAll('[data-notification-id]').forEach((item) => {
                item.classList.add('opacity-65');
                item.querySelector('[aria-label="Unread"]')?.remove();
            });
            markAllButton.remove();
            setPersistentTaskAlertCount(0);
        }
    }
});

if (document.querySelector('[data-notifications-root]')) {
    window.setTimeout(syncPersistentAiTaskAlerts, 1200);
    window.setInterval(syncPersistentAiTaskAlerts, 30000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) syncPersistentAiTaskAlerts();
    });
}

function openAiChat(promptToRun = null) {
    if (!aiChatDrawer) return;

    aiChatDrawer.classList.remove('hidden');
    aiChatDrawer.classList.add('flex');
    initAllNiuLotties(aiChatDrawer);

    if (promptToRun) {
        hideAiProfile();
    }

    if (!aiPreferencesLoaded) {
        loadAiPreferences().then(() => {
            if (!aiPreferencesConfigured && !promptToRun) {
                showAiProfile();
            }
        });
    }

    if (!aiChatHistoryLoaded) {
        loadAiChatHistory().then(() => {
            if (promptToRun) {
                executeOrFillPrompt(promptToRun);
            }
        });
    } else {
        if (promptToRun) {
            executeOrFillPrompt(promptToRun);
        }
    }

    setTimeout(() => {
        if (aiProfilePanel?.classList.contains('hidden')) {
            aiChatInput?.focus();
        }
        scrollChatToBottom();
    }, 100);
}

function closeAiChat() {
    if (!aiChatDrawer) return;
    aiChatDrawer.classList.add('hidden');
    aiChatDrawer.classList.remove('flex');
}

function toggleAiChat() {
    if (aiChatDrawer?.classList.contains('flex')) {
        closeAiChat();
    } else {
        openAiChat();
    }
}

function scrollChatToBottom(smooth = true) {
    if (!aiChatMessagesCont) return;
    aiChatMessagesCont.scrollTo({
        top: aiChatMessagesCont.scrollHeight,
        behavior: smooth ? 'smooth' : 'auto',
    });
}

function executeOrFillPrompt(promptText) {
    if (!aiChatInput) return;
    const clean = promptText.trim();
    if (clean.endsWith(':') || clean.endsWith('៖')) {
        aiChatInput.value = clean + ' ';
        aiChatInput.focus();
    } else {
        aiChatInput.value = '';
        sendAiChatMessage(clean);
    }
}

function showAiProfile() {
    if (!aiProfilePanel) return;

    aiProfilePanel.classList.remove('hidden');
    aiChatMessagesCont?.classList.add('hidden');
    aiChatTools?.classList.add('hidden');
    aiChatComposer?.classList.add('hidden');
    aiProfileError?.classList.add('hidden');
}

function hideAiProfile() {
    if (!aiProfilePanel) return;

    aiProfilePanel.classList.add('hidden');
    aiChatMessagesCont?.classList.remove('hidden');
    aiChatTools?.classList.remove('hidden');
    aiChatComposer?.classList.remove('hidden');
    aiChatInput?.focus();
    scrollChatToBottom(false);
}

function fillAiPreferencesForm(preferences) {
    if (!aiProfileForm || !preferences) return;

    aiProfileForm.reset();
    aiProfileForm.elements.occupation.value = preferences.occupation || '';
    aiProfileForm.elements.experience_level.value = preferences.experience_level || 'beginner';

    ['learning_interests', 'work_skills', 'assistance_areas'].forEach((field) => {
        const selected = new Set(preferences[field] || []);
        aiProfileForm.querySelectorAll(`[name="${field}[]"]`).forEach((input) => {
            input.checked = selected.has(input.value);
        });
    });

    aiProfileForm.elements.other_needs.value = preferences.other_needs || '';
}

async function loadAiPreferences() {
    try {
        const response = await fetch('/tasks/ai/preferences', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) return;

        const data = await response.json();
        aiPreferencesLoaded = true;
        aiPreferencesConfigured = Boolean(data.configured);
        if (data.preferences) {
            fillAiPreferencesForm(data.preferences);
        }
    } catch (_error) {
        // The chatbot remains usable when preferences cannot be loaded.
    }
}

async function saveAiPreferences() {
    if (!aiProfileForm || !aiProfileSaveBtn) return;

    const learningInterests = [...aiProfileForm.querySelectorAll('[name="learning_interests[]"]:checked')].map((input) => input.value);
    const workSkills = [...aiProfileForm.querySelectorAll('[name="work_skills[]"]:checked')].map((input) => input.value);
    const assistanceAreas = [...aiProfileForm.querySelectorAll('[name="assistance_areas[]"]:checked')].map((input) => input.value);
    const dict = i18n[currentLang] || i18n.en;

    if (!aiProfileForm.elements.occupation.value || learningInterests.length === 0 || assistanceAreas.length === 0) {
        if (aiProfileError) {
            aiProfileError.textContent = dict.ai_profile_required;
            aiProfileError.classList.remove('hidden');
        }
        return;
    }

    aiProfileSaveBtn.disabled = true;
    aiProfileError?.classList.add('hidden');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    try {
        const response = await fetch('/tasks/ai/preferences', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            body: JSON.stringify({
                occupation: aiProfileForm.elements.occupation.value,
                experience_level: aiProfileForm.elements.experience_level.value,
                learning_interests: learningInterests,
                work_skills: workSkills,
                assistance_areas: assistanceAreas,
                other_needs: aiProfileForm.elements.other_needs.value.trim() || null,
            }),
        });

        const data = await response.json();
        if (!response.ok) {
            const firstError = Object.values(data.errors || {})[0]?.[0] || data.message || 'Unable to save your preferences.';
            if (aiProfileError) {
                aiProfileError.textContent = firstError;
                aiProfileError.classList.remove('hidden');
            }
            return;
        }

        aiPreferencesLoaded = true;
        aiPreferencesConfigured = true;
        fillAiPreferencesForm(data.preferences);
        hideAiProfile();
        showToast('success', dict.ai_profile_saved);
    } catch (_error) {
        if (aiProfileError) {
            aiProfileError.textContent = currentLang === 'km'
                ? 'មិនអាចរក្សាទុកបានទេ។ សូមព្យាយាមម្តងទៀត។'
                : 'Unable to save your preferences. Please try again.';
            aiProfileError.classList.remove('hidden');
        }
    } finally {
        aiProfileSaveBtn.disabled = false;
    }
}

function inlineFormat(text) {
    if (!text) return '';
    let s = escapeHtml(text);
    s = s.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900 dark:text-white">$1</strong>');
    s = s.replace(/\*(.*?)\*/g, '<em class="italic text-slate-700 dark:text-slate-300">$1</em>');
    s = s.replace(/`([^`]+)`/g, '<code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10.5px] text-indigo-600 dark:bg-slate-800 dark:text-indigo-400">$1</code>');
    return s;
}

function formatMarkdownText(rawText) {
    if (!rawText) return '';

    const lines = rawText.split(/\r?\n/);
    const output = [];
    let inList = false;
    let listItems = [];

    for (let i = 0; i < lines.length; i++) {
        let line = lines[i].trim();
        if (!line) {
            if (inList) {
                output.push(`<ul class="my-2 space-y-1.5">${listItems.join('')}</ul>`);
                inList = false;
                listItems = [];
            }
            continue;
        }

        // Check if line is a metric bullet (e.g. • ហួសកាលកំណត់: 0 or • Overdue: 0)
        // Group consecutive metric bullets into a 3-column visual card
        if (/^[•\-\*]\s*(?:ហួសកាលកំណត់|Overdue|អាទិភាពខ្ពស់|High Priority|ដល់កំណត់ថ្ងៃនេះ|Due Today)[៖:]*/i.test(line)) {
            const metricLines = [line];
            while (i + 1 < lines.length && /^[•\-\*]\s*(?:ហួសកាលកំណត់|Overdue|អាទិភាពខ្ពស់|High Priority|ដល់កំណត់ថ្ងៃនេះ|Due Today)[៖:]*/i.test(lines[i + 1].trim())) {
                i++;
                metricLines.push(lines[i].trim());
            }

            let overdueVal = '0';
            let urgentVal = '0';
            let todayVal = '0';
            let isKm = false;

            metricLines.forEach((mLine) => {
                const numMatch = mLine.match(/\b(\d+)\b/);
                const val = numMatch ? numMatch[1] : '0';
                if (/ហួសកាលកំណត់|Overdue/i.test(mLine)) {
                    overdueVal = val;
                    if (/ហួសកាលកំណត់/.test(mLine)) isKm = true;
                } else if (/អាទិភាពខ្ពស់|High Priority/i.test(mLine)) {
                    urgentVal = val;
                    if (/អាទិភាពខ្ពស់/.test(mLine)) isKm = true;
                } else if (/ដល់កំណត់ថ្ងៃនេះ|Due Today/i.test(mLine)) {
                    todayVal = val;
                    if (/ដល់កំណត់ថ្ងៃនេះ/.test(mLine)) isKm = true;
                }
            });

            const overdueBadgeClass = parseInt(overdueVal, 10) > 0
                ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/50'
                : 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900/50';

            output.push(`
                <div class="my-3 grid grid-cols-3 gap-2">
                    <div class="flex flex-col items-center justify-center rounded-xl border p-2 text-center transition hover:shadow-2xs ${overdueBadgeClass}">
                        <span class="text-[9.5px] font-semibold opacity-85">${isKm ? 'ហួសកំណត់' : 'Overdue'}</span>
                        <span class="text-base font-extrabold mt-0.5">${overdueVal}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center rounded-xl border border-amber-200 bg-amber-50 p-2 text-center text-amber-700 transition hover:shadow-2xs dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-300">
                        <span class="text-[9.5px] font-semibold opacity-85">${isKm ? 'អាទិភាពខ្ពស់' : 'High Priority'}</span>
                        <span class="text-base font-extrabold mt-0.5">${urgentVal}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center rounded-xl border border-blue-200 bg-blue-50 p-2 text-center text-blue-700 transition hover:shadow-2xs dark:border-blue-900/50 dark:bg-blue-950/40 dark:text-blue-300">
                        <span class="text-[9.5px] font-semibold opacity-85">${isKm ? 'ថ្ងៃនេះ' : 'Due Today'}</span>
                        <span class="text-base font-extrabold mt-0.5">${todayVal}</span>
                    </div>
                </div>
            `);
            continue;
        }

        // Recommendation Callout (🎯 ...)
        if (/^🎯\s*/.test(line)) {
            const cleanContent = inlineFormat(line.replace(/^🎯\s*/, ''));
            output.push(`
                <div class="my-2.5 flex items-start gap-2.5 rounded-xl border border-indigo-200/80 bg-gradient-to-r from-indigo-50/80 to-purple-50/60 p-3 text-[11.5px] leading-relaxed text-indigo-950 shadow-2xs dark:border-indigo-900/50 dark:from-indigo-950/40 dark:to-purple-950/30 dark:text-indigo-200">
                    <span class="text-base shrink-0 mt-0.5">🎯</span>
                    <div class="flex-1">${cleanContent}</div>
                </div>
            `);
            continue;
        }

        // Celebratory Callout (🎉 ...)
        if (/^🎉\s*/.test(line)) {
            const cleanContent = inlineFormat(line.replace(/^🎉\s*/, ''));
            output.push(`
                <div class="my-2.5 flex items-start gap-2.5 rounded-xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50/80 to-teal-50/60 p-3 text-[11.5px] leading-relaxed text-emerald-950 shadow-2xs dark:border-emerald-900/50 dark:from-emerald-950/40 dark:to-teal-950/30 dark:text-emerald-200">
                    <span class="text-base shrink-0 mt-0.5">🎉</span>
                    <div class="flex-1">${cleanContent}</div>
                </div>
            `);
            continue;
        }

        // Warning Callout (⚠️ ...)
        if (/^⚠️\s*/.test(line)) {
            const cleanContent = inlineFormat(line.replace(/^⚠️\s*/, ''));
            output.push(`
                <div class="my-2.5 flex items-start gap-2.5 rounded-xl border border-rose-200/80 bg-rose-50/70 p-3 text-[11.5px] leading-relaxed text-rose-950 shadow-2xs dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200">
                    <span class="text-base shrink-0 mt-0.5">⚠️</span>
                    <div class="flex-1">${cleanContent}</div>
                </div>
            `);
            continue;
        }

        // Success Callout (✅ ...)
        if (/^✅\s*/.test(line)) {
            const cleanContent = inlineFormat(line.replace(/^✅\s*/, ''));
            output.push(`
                <div class="my-2.5 flex items-start gap-2.5 rounded-xl border border-emerald-200/80 bg-emerald-50/70 p-3 text-[11.5px] leading-relaxed text-emerald-950 shadow-2xs dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-200">
                    <span class="text-base shrink-0 mt-0.5">✅</span>
                    <div class="flex-1">${cleanContent}</div>
                </div>
            `);
            continue;
        }

        // Bullet points
        if (/^[•\-\*]\s+(.+)$/.test(line)) {
            const content = line.replace(/^[•\-\*]\s+/, '');
            inList = true;
            listItems.push(`
                <li class="flex items-start gap-2 text-[11.5px] leading-relaxed text-slate-700 dark:text-slate-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 shrink-0 mt-1.5"></span>
                    <span class="flex-1">${inlineFormat(content)}</span>
                </li>
            `);
            continue;
        }

        // Numbered list items
        const numMatch = line.match(/^(\d+)\.\s+(.+)$/);
        if (numMatch) {
            inList = true;
            listItems.push(`
                <li class="flex items-start gap-2 text-[11.5px] leading-relaxed text-slate-700 dark:text-slate-300">
                    <span class="font-bold text-indigo-600 dark:text-indigo-400 shrink-0 text-[11px]">${numMatch[1]}.</span>
                    <span class="flex-1">${inlineFormat(numMatch[2])}</span>
                </li>
            `);
            continue;
        }

        // Close list if non-list line encountered
        if (inList) {
            output.push(`<ul class="my-2 space-y-1.5">${listItems.join('')}</ul>`);
            inList = false;
            listItems = [];
        }

        // Headings
        if (line.startsWith('### ')) {
            output.push(`<h5 class="mt-2.5 mb-1 font-bold text-xs text-slate-900 dark:text-white">${inlineFormat(line.slice(4))}</h5>`);
            continue;
        }
        if (line.startsWith('## ')) {
            output.push(`<h4 class="mt-3 mb-1.5 font-bold text-sm text-slate-900 dark:text-white">${inlineFormat(line.slice(3))}</h4>`);
            continue;
        }
        if (line.startsWith('# ')) {
            output.push(`<h3 class="mt-3.5 mb-2 font-extrabold text-sm text-slate-900 dark:text-white">${inlineFormat(line.slice(2))}</h3>`);
            continue;
        }

        // Regular paragraph
        output.push(`<p class="leading-relaxed text-[12px] text-slate-800 dark:text-slate-200">${inlineFormat(line)}</p>`);
    }

    if (inList) {
        output.push(`<ul class="my-2 space-y-1.5">${listItems.join('')}</ul>`);
    }

    return output.join('');
}

function renderChatMessage(msg, autoScroll = true) {
    if (!aiChatThread) return;

    if (aiChatWelcome) {
        aiChatWelcome.classList.add('hidden');
    }

    const isUser = (msg.role === 'user');
    const msgDiv = document.createElement('div');
    msgDiv.className = `flex flex-col ${isUser ? 'items-end' : 'items-start'} space-y-1 w-full`;

    const time = msg.time || msg.created_at || '';

    if (isUser) {
        msgDiv.innerHTML = `
            <div class="flex flex-col items-end space-y-1 max-w-[85%] self-end">
                <div class="ai-chat-bubble-user rounded-2xl rounded-tr-xs px-4 py-2.5 text-[12px] text-white shadow-sm leading-relaxed font-medium">
                    ${escapeHtml(msg.message)}
                </div>
                ${time ? `<span class="text-[9px] text-slate-400 px-1 font-mono">${escapeHtml(time)}</span>` : ''}
            </div>
        `;
    } else {
        let actionCardHtml = '';

        // 1. Task Created Card
        if (msg.action_type === 'task_created' && msg.action_data) {
            const d = msg.action_data;
            actionCardHtml = `
                <div class="mt-2.5 w-full rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/80 via-white to-teal-50/50 p-3.5 text-slate-800 shadow-xs dark:border-emerald-900/60 dark:from-slate-850 dark:to-emerald-950/40 dark:text-slate-100">
                    <div class="flex items-center justify-between pb-2 border-b border-emerald-100 dark:border-emerald-900/50">
                        <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Task Created Successfully
                        </span>
                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-bold text-emerald-800 uppercase dark:bg-emerald-900 dark:text-emerald-200">
                            ${escapeHtml(d.priority ?? 'medium')}
                        </span>
                    </div>
                    <p class="mt-2 font-bold text-xs text-slate-900 dark:text-white">${escapeHtml(d.title)}</p>
                    <div class="mt-1.5 flex flex-wrap items-center gap-3 text-[10.5px] text-slate-500 dark:text-slate-400">
                        <span>📁 ${escapeHtml(d.category ?? 'Work')}</span>
                        ${d.due_date ? `<span>📅 ${escapeHtml(d.due_date)}</span>` : ''}
                    </div>
                    <div class="mt-2.5 pt-2 border-t border-emerald-100 dark:border-emerald-900/50 flex items-center justify-end">
                        <a href="/tasks" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:text-emerald-900 dark:text-emerald-400 transition hover:underline">
                            View on Kanban Board →
                        </a>
                    </div>
                </div>
            `;
        }

        // 2. Task Breakdown Card
        if (msg.action_type === 'task_breakdown' && msg.action_data?.subtasks?.length) {
            actionCardHtml = `
                <div class="mt-2.5 w-full rounded-2xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50/70 via-white to-purple-50/50 p-3.5 text-slate-800 shadow-xs dark:border-indigo-900/60 dark:from-slate-850 dark:to-indigo-950/40 dark:text-slate-100">
                    <div class="flex items-center justify-between pb-2 border-b border-indigo-100 dark:border-indigo-900/50">
                        <span class="text-[10.5px] font-extrabold uppercase tracking-wide text-indigo-700 dark:text-indigo-400 flex items-center gap-1.5">
                            <span>📋</span> Action Checklist (${msg.action_data.subtasks.length} steps)
                        </span>
                    </div>
                    <div class="mt-2.5 space-y-1.5">
                        ${msg.action_data.subtasks.map((st, i) => `
                            <div class="flex items-start gap-2 text-[11px] p-1.5 rounded-lg bg-white/80 border border-indigo-100/60 dark:bg-slate-800/80 dark:border-slate-700/60">
                                <span class="font-extrabold text-indigo-600 dark:text-indigo-400 shrink-0 w-4">${i + 1}.</span>
                                <span class="flex-1 font-medium text-slate-800 dark:text-slate-200">${escapeHtml(st.title)}</span>
                                <span class="text-[9.5px] font-semibold text-slate-400 shrink-0 bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">${st.estimated_minutes ?? 20}m</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        // 3. Standup Summary Card
        if (msg.action_type === 'standup_summary' && msg.action_data?.brief) {
            const brief = msg.action_data.brief;
            const isKm = (currentLang === 'km');
            actionCardHtml = `
                <div class="mt-2.5 w-full rounded-2xl border border-indigo-150 bg-gradient-to-br from-indigo-50/70 via-white to-blue-50/50 p-3.5 text-slate-800 shadow-xs dark:border-indigo-900/60 dark:from-slate-850 dark:to-indigo-950/40 dark:text-slate-100">
                    <div class="flex items-center justify-between pb-2 border-b border-indigo-100/70 dark:border-slate-700/60">
                        <span class="inline-flex items-center gap-1.5 text-[10.5px] font-extrabold uppercase tracking-wide text-indigo-700 dark:text-indigo-400">
                            <span>☀️</span> ${isKm ? 'របាយការណ៍សង្ខេបប្រចាំថ្ងៃ' : 'Daily Standup Dashboard'}
                        </span>
                        <span class="rounded-full bg-indigo-100/80 px-2 py-0.5 text-[9px] font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                            ${brief.completion_rate ? `${brief.completion_rate}% Done` : (isKm ? 'ថ្ងៃនេះ' : 'Today')}
                        </span>
                    </div>
                    <div class="mt-2.5 grid grid-cols-3 gap-2">
                        <div class="flex flex-col items-center justify-center rounded-xl border p-2 text-center ${parseInt(brief.overdue_count, 10) > 0 ? 'bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-950/40 dark:border-rose-900/50 dark:text-rose-300' : 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-950/40 dark:border-emerald-900/50 dark:text-emerald-300'}">
                            <span class="text-[9.5px] font-semibold opacity-85">${isKm ? 'ហួសកំណត់' : 'Overdue'}</span>
                            <span class="text-base font-extrabold mt-0.5">${brief.overdue_count ?? 0}</span>
                        </div>
                        <div class="flex flex-col items-center justify-center rounded-xl border border-amber-200 bg-amber-50 p-2 text-center text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-300">
                            <span class="text-[9.5px] font-semibold opacity-85">${isKm ? 'អាទិភាពខ្ពស់' : 'High Priority'}</span>
                            <span class="text-base font-extrabold mt-0.5">${brief.urgent_count ?? 0}</span>
                        </div>
                        <div class="flex flex-col items-center justify-center rounded-xl border border-blue-200 bg-blue-50 p-2 text-center text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/40 dark:text-blue-300">
                            <span class="text-[9.5px] font-semibold opacity-85">${isKm ? 'ថ្ងៃនេះ' : 'Due Today'}</span>
                            <span class="text-base font-extrabold mt-0.5">${brief.due_today_count ?? 0}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        // 4. Overdue Summary Card
        if (msg.action_type === 'overdue_summary') {
            const count = msg.action_data?.count ?? 0;
            const isKm = (currentLang === 'km');
            if (count === 0) {
                actionCardHtml = `
                    <div class="mt-2.5 w-full rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50/90 to-teal-50/70 p-3 text-emerald-950 dark:border-emerald-900/60 dark:from-emerald-950/40 dark:to-teal-950/30 dark:text-emerald-200">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200 text-sm">🎉</span>
                            <div>
                                <h5 class="font-extrabold text-xs text-emerald-900 dark:text-emerald-200">${isKm ? 'គ្មានកិច្ចការហួសកំណត់ទេ!' : 'All Caught Up!'}</h5>
                                <p class="text-[10.5px] text-emerald-800/80 dark:text-emerald-300 mt-0.5">${isKm ? 'កិច្ចការរបស់អ្នកទាំងអស់ស្ថិតក្នុងលំហូរយ៉ាងល្អ។' : 'You have 0 overdue tasks right now. Great job!'}</p>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        msgDiv.innerHTML = `
            <div class="flex items-start gap-2.5 max-w-[94%]">
                <div class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-2xl overflow-hidden bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-100 dark:border-indigo-900/50 mt-0.5 shadow-2xs p-0.5 pointer-events-none" data-lottie="niu"></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 mb-1 px-0.5">
                        <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200">Nova</span>
                        ${time ? `<span class="text-[9.5px] text-slate-400 font-mono ml-auto">${escapeHtml(time)}</span>` : ''}
                    </div>
                    <div class="ai-chat-bubble-bot rounded-2xl rounded-tl-xs p-3.5 text-xs text-slate-800 dark:text-slate-200 leading-relaxed shadow-xs">
                        ${formatMarkdownText(msg.message)}
                        ${actionCardHtml}
                    </div>
                </div>
            </div>
        `;
    }

    aiChatThread.appendChild(msgDiv);
    const botLottie = msgDiv.querySelector('[data-lottie="niu"]');
    if (botLottie) {
        renderNiuLottie(botLottie);
    }
    if (autoScroll) {
        scrollChatToBottom();
    }
}

async function loadAiChatHistory() {
    let hasHistory = false;
    aiChatHistorySkeleton?.classList.remove('hidden');
    aiChatWelcome?.classList.add('hidden');

    try {
        const res = await fetch('/tasks/ai/chat/history', {
            headers: { Accept: 'application/json' },
        });

        if (res.ok) {
            const data = await res.json();
            aiChatHistoryLoaded = true;
            if (data.success && data.history?.length) {
                hasHistory = true;
                if (aiChatThread) aiChatThread.innerHTML = '';
                data.history.forEach((m) => renderChatMessage(m, false));
                scrollChatToBottom(false);
            }
        }
    } catch (_err) {
        // silent fail
    } finally {
        aiChatHistorySkeleton?.classList.add('hidden');
        if (!hasHistory) {
            aiChatWelcome?.classList.remove('hidden');
        }
    }
}

async function sendAiChatMessage(messageText) {
    const text = messageText?.trim();
    if (!text || isAiResponding) return;

    isAiResponding = true;

    // Render user message immediately
    const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    renderChatMessage({
        role: 'user',
        message: text,
        time: nowTime,
    });

    if (aiChatInput) aiChatInput.value = '';
    if (aiChatSendBtn) aiChatSendBtn.disabled = true;

    // Show typing
    if (aiChatTyping) {
        aiChatTyping.classList.remove('hidden');
        aiChatTyping.classList.add('flex');
        initAllNiuLotties(aiChatTyping);
    }
    scrollChatToBottom();

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    try {
        const res = await fetch('/tasks/ai/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            body: JSON.stringify({
                message: text,
                lang: currentLang,
            }),
        });

        if (res.ok) {
            const data = await res.json();
            if (data.success) {
                renderChatMessage({
                    role: 'assistant',
                    message: data.message,
                    action_type: data.action_type,
                    action_data: data.action_data,
                    time: data.created_at || nowTime,
                });

                if (data.action_type === 'task_created') {
                    showAiTaskCreatedAlert(data.action_data);
                }
            } else {
                renderChatMessage({
                    role: 'assistant',
                    message: 'Sorry, I could not complete your request. Please try again.',
                    time: nowTime,
                });
            }
        } else {
            renderChatMessage({
                role: 'assistant',
                message: 'An error occurred while connecting to Nova. Please check your connection and try again.',
                time: nowTime,
            });
        }
    } catch (_err) {
        renderChatMessage({
            role: 'assistant',
            message: 'Network issue encountered. Please try again in a moment.',
            time: nowTime,
        });
    } finally {
        isAiResponding = false;
        if (aiChatTyping) {
            aiChatTyping.classList.add('hidden');
            aiChatTyping.classList.remove('flex');
        }
        if (aiChatSendBtn) aiChatSendBtn.disabled = false;
        aiChatInput?.focus();
        scrollChatToBottom();
    }
}

async function clearAiChatHistory() {
    const dict = i18n[currentLang] || i18n.en;
    const result = await showConfirmDialog({
        title: dict.swal_clear_chat_title,
        text: dict.swal_clear_chat_text,
        icon: 'warning',
        confirmButtonText: dict.swal_clear_chat_btn,
        cancelButtonText: dict.swal_cancel_btn,
        confirmButtonColor: '#e11d48',
        isDanger: true,
    });
    if (!result.isConfirmed) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    try {
        const res = await fetch('/tasks/ai/chat/clear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
        });

        if (res.ok) {
            if (aiChatThread) aiChatThread.innerHTML = '';
            if (aiChatWelcome) aiChatWelcome.classList.remove('hidden');
            aiChatHistoryLoaded = true;
            showToast('success', dict.swal_chat_cleared);
        }
    } catch (_err) {
        // silent fail
    }
}

// Attach AI Chatbot Event Listeners
aiChatLauncherBtn?.addEventListener('click', () => toggleAiChat());
aiChatCloseBtn?.addEventListener('click', () => closeAiChat());
aiChatClearBtn?.addEventListener('click', () => clearAiChatHistory());
aiProfileOpenBtn?.addEventListener('click', () => showAiProfile());
aiProfileWelcomeBtn?.addEventListener('click', () => showAiProfile());
aiProfileCancelBtn?.addEventListener('click', () => hideAiProfile());

aiProfileForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    saveAiPreferences();
});

aiChatForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    const val = aiChatInput?.value;
    if (val) {
        sendAiChatMessage(val);
    }
});

// All elements with [data-open-ai-chat]
document.addEventListener('click', (e) => {
    const openBtn = e.target.closest('[data-open-ai-chat]');
    if (openBtn) {
        e.preventDefault();
        openAiChat();
        return;
    }

    const promptBtn = e.target.closest('.ai-suggestion-chip, .ai-quick-btn, .dashboard-ai-chip, [data-chat-prompt]');
    if (promptBtn) {
        e.preventDefault();
        const prompt = currentLang === 'km' && promptBtn.dataset.promptKm
            ? promptBtn.dataset.promptKm
            : (promptBtn.dataset.prompt || promptBtn.dataset.chatPrompt);
        if (prompt) {
            openAiChat(prompt);
        }
    }
});

// Global keyboard shortcut: Shift + A
document.addEventListener('keydown', (e) => {
    if (e.shiftKey && (e.key === 'A' || e.key === 'a') && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
        e.preventDefault();
        toggleAiChat();
    }
});

// Initialize all Lottie niu mascot animations
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initAllNiuLotties();
    });
} else {
    initAllNiuLotties();
}
