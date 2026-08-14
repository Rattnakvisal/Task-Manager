@props(['name', 'class' => 'h-5 w-5'])

<svg {{ $attributes->merge(['class' => $class]) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
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

        @case('x')
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
            @break
    @endswitch
</svg>
