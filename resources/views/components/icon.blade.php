@props(['name', 'class' => 'h-5 w-5'])

<svg {{ $attributes->merge(['class' => $class]) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('clock')
            <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
            @break
        @case('circle')
            <circle cx="12" cy="12" r="9" />
            @break
        @case('more-vertical')
            <circle cx="12" cy="5" r="1" /><circle cx="12" cy="12" r="1" /><circle cx="12" cy="19" r="1" />
            @break
        @case('project')
            <path d="m12 3 8 4v10l-8 4-8-4V7Z" /><path d="m4 7 8 4 8-4M12 11v10M8 5l8 4" />
            @break
        @case('analytics')
            <path d="M3 3v18h18M7 16v-4M12 16V9M17 16V6M6 9l5-4 5 2 5-5" />
            @break
        @case('settings')
            <path d="m9 3-1 3-3 1-2 3 2 2-1 3 3 2 1 3h4l1-3 3-1 2-3-2-2 1-3-3-2-1-3Z" /><circle cx="10" cy="11.5" r="3" />
            @break
        @case('activity')
            <path d="M22 12h-4l-3 8-6-16-3 8H2" />
            @break

        @case('calendar')
            <path d="M8 2v4" />
            <path d="M16 2v4" />
            <rect width="18" height="18" x="3" y="4" rx="2" />
            <path d="M3 10h18" />
            @break

        @case('bell')
            <path d="M10.27 21a2 2 0 0 0 3.46 0" />
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
            @break

        @case('check')
            <path d="m20 6-11 11-5-5" />
            @break

        @case('check-circle')
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
            <path d="m9 11 3 3L22 4" />
            @break

        @case('task-logo')
            <rect width="18" height="18" x="3" y="3" rx="4" />
            <path d="m8 12 2.5 2.5L16 9" />
            <path d="M7 7h.01" />
            @break

        @case('clipboard')
            <rect width="8" height="4" x="8" y="2" rx="1" />
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
            <path d="M8 12h8" />
            <path d="M8 16h5" />
            @break

        @case('edit')
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
            @break

        @case('filter')
            <path d="M22 3H2l8 9.46V19l4 2v-8.54Z" />
            @break

        @case('flag')
            <path d="M4 22V4" />
            <path d="M4 4h12l-1 5 1 5H4" />
            @break

        @case('grid')
            <rect width="7" height="7" x="3" y="3" rx="1" />
            <rect width="7" height="7" x="14" y="3" rx="1" />
            <rect width="7" height="7" x="14" y="14" rx="1" />
            <rect width="7" height="7" x="3" y="14" rx="1" />
            @break

        @case('inbox')
            <path d="M22 12h-6l-2 3h-4l-2-3H2" />
            <path d="m5.45 5.11-3.24 9.72A2 2 0 0 0 4.11 17h15.78a2 2 0 0 0 1.9-2.17l-3.24-9.72A2 2 0 0 0 16.66 4H7.34a2 2 0 0 0-1.89 1.11Z" />
            @break

        @case('list')
            <path d="M8 6h13" />
            <path d="M8 12h13" />
            <path d="M8 18h13" />
            <path d="M3 6h.01" />
            <path d="M3 12h.01" />
            <path d="M3 18h.01" />
            @break

        @case('layers')
            <path d="m12 2 9 5-9 5-9-5Z" />
            <path d="m3 12 9 5 9-5" />
            <path d="m3 17 9 5 9-5" />
            @break

        @case('plus')
            <path d="M5 12h14" />
            <path d="M12 5v14" />
            @break

        @case('search')
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
            @break

        @case('user')
            <path d="M19 21a7 7 0 0 0-14 0" />
            <circle cx="12" cy="7" r="4" />
            @break

        @case('users')
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            @break

        @case('trash')
            <path d="M3 6h18" />
            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
            <path d="M10 11v6" />
            <path d="M14 11v6" />
            @break

        @case('logout')
            <path d="M10 17l5-5-5-5" />
            <path d="M15 12H3" />
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
            @break

        @case('x')
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
            @break

        @case('kanban')
            <path d="M6 5v11" />
            <path d="M12 5v6" />
            <path d="M18 5v14" />
            <rect width="18" height="18" x="3" y="3" rx="2" />
            @break

        @case('pin')
            <line x1="12" y1="17" x2="12" y2="22" />
            <path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a1 1 0 0 0 0-2H8a1 1 0 0 0 0 2h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24Z" />
            @break

        @case('sparkles')
            <path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z" />
            @break

        @case('download')
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="7 10 12 15 17 10" />
            <line x1="12" y1="15" x2="12" y2="3" />
            @break

        @case('globe')
            <circle cx="12" cy="12" r="10" />
            <line x1="2" y1="12" x2="22" y2="12" />
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
            @break

        @case('command')
            <path d="M18 3a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3H6a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3V6a3 3 0 0 0-3-3 3 3 0 0 0-3 3 3 3 0 0 0 3 3h12a3 3 0 0 0 3-3 3 3 0 0 0-3-3z" />
            @break

        @case('bot')
            <path d="M12 8V4H8" />
            <rect width="16" height="12" x="4" y="8" rx="2" />
            <path d="M2 14h2" />
            <path d="M20 14h2" />
            <path d="M15 13v2" />
            <path d="M9 13v2" />
            @break

        @case('zap')
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
            @break

        @case('refresh-cw')
            <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
            <path d="M21 3v5h-5" />
            <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
            <path d="M8 16H3v5" />
            @break

        @case('wand')
            <path d="m15 4-2 4 4-2-2-2Z" />
            <path d="m8 9-5 5 1.5 1.5 5-5L8 9Z" />
            <path d="m18 11 1.5 1.5-2.5 2.5-1.5-1.5 2.5-2.5Z" />
            <path d="m19 2 2 2-14 14-2-2L19 2Z" />
            @break

        @case('arrow-left')
            <path d="m12 19-7-7 7-7" />
            <path d="M19 12H5" />
            @break

        @case('copy')
            <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
            @break

        @case('share')
            <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" />
            <polyline points="16 6 12 2 8 6" />
            <line x1="12" y1="2" x2="12" y2="15" />
            @break

        @case('tag')
            <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z" />
            <path d="M7 7h.01" />
            @break
    @endswitch
</svg>
