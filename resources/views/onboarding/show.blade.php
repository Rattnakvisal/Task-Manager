<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Set up your workspace · WorkMind</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Logo/Glossy Blue Checklist App Icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="onboarding-page min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-white">
    @php
        $selectedSections = old('profile_sections', $profile->profile_sections ?? ['work', 'study', 'other']);
        $selectedSections = is_array($selectedSections) ? $selectedSections : [];
        $learningInterests = old('learning_interests', $preferences?->learning_interests ?? []);
        $assistanceAreas = old('assistance_areas', $preferences?->assistance_areas ?? []);
        $skills = old('skills', implode(', ', $profile->skills ?? []));
        $interests = old('interests', implode(', ', $profile->interests ?? []));
        $initialStep = 1;
        if ($errors->hasAny(['profile_sections', 'work_status', 'job_title', 'company', 'industry', 'study_status', 'education_level', 'institution', 'field_of_study', 'skills', 'interests', 'website'])) {
            $initialStep = 2;
        } elseif ($errors->hasAny(['occupation', 'experience_level', 'learning_interests', 'assistance_areas', 'other_needs'])) {
            $initialStep = 3;
        }
    @endphp

    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="onboarding-orb onboarding-orb--one absolute -left-28 -top-28 h-96 w-96 rounded-full bg-blue-200/50 blur-3xl dark:bg-blue-900/20"></div>
        <div class="onboarding-orb onboarding-orb--two absolute -bottom-32 -right-20 h-[28rem] w-[28rem] rounded-full bg-violet-200/40 blur-3xl dark:bg-violet-900/20"></div>
        <div class="onboarding-grid absolute inset-0 opacity-40"></div>
    </div>

    <header class="onboarding-topbar relative z-10 mx-auto flex max-w-6xl items-center justify-between px-5 py-5 sm:px-8">
        <a href="{{ route('onboarding.show') }}" class="flex items-center gap-3" aria-label="WorkMind onboarding">
            <img src="{{ asset('images/Logo/Glossy Blue Checklist App Icon.png') }}" alt="" class="h-10 w-10 rounded-xl shadow-lg shadow-blue-500/20">
            <span class="text-base font-extrabold tracking-tight">WorkMind</span>
        </a>
        <div class="flex items-center gap-2">
            <button type="button" data-theme-toggle class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Toggle theme">
                <svg data-theme-sun viewBox="0 0 24 24" class="h-4.5 w-4.5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg>
                <svg data-theme-moon viewBox="0 0 24 24" class="hidden h-4.5 w-4.5 text-blue-400" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
            </button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="h-10 rounded-xl px-3 text-xs font-bold text-slate-500 transition hover:bg-white hover:text-slate-800 dark:hover:bg-slate-900 dark:hover:text-white">Sign out</button>
            </form>
        </div>
    </header>

    <main class="relative z-10 mx-auto max-w-6xl px-4 pb-12 sm:px-8" data-onboarding-root data-initial-step="{{ $initialStep }}">
        <div class="onboarding-intro mb-7 text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-widest text-blue-700 dark:border-blue-900 dark:bg-blue-950/60 dark:text-blue-300">
                <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                Welcome, {{ $user->name }}
            </span>
            <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Let’s make WorkMind yours</h1>
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">Tell us a little about yourself. Your information stays connected to your private profile and helps Nova give more relevant guidance.</p>
        </div>

        <div class="onboarding-shell grid overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/5 dark:border-slate-800 dark:bg-slate-900 lg:grid-cols-[250px_minmax(0,1fr)]">
            <aside class="onboarding-aside relative overflow-hidden bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-700 p-6 text-white lg:p-8">
                <div class="onboarding-aside-glow pointer-events-none absolute -right-16 -top-12 h-44 w-44 rounded-full border border-white/20"></div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-200">Quick setup</p>
                <div class="mt-7 space-y-5">
                    @foreach ([1 => ['About you', 'Basic profile details'], 2 => ['Your world', 'Work, study, and interests'], 3 => ['Meet Nova', 'Personalize your AI']] as $number => [$title, $subtitle])
                        <button type="button" data-onboarding-step-link="{{ $number }}" class="onboarding-step-link flex w-full items-center gap-3 text-left opacity-60 transition" aria-current="{{ $number === 1 ? 'step' : 'false' }}">
                            <span data-onboarding-step-indicator class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/40 bg-white/10 text-xs font-black">{{ $number }}</span>
                            <span><span class="block text-xs font-extrabold">{{ $title }}</span><span class="mt-0.5 block text-[10px] text-blue-200">{{ $subtitle }}</span></span>
                        </button>
                    @endforeach
                </div>
                <div class="mt-9 rounded-2xl border border-white/15 bg-white/10 p-4">
                    <div class="flex items-center gap-2 text-xs font-bold"><span>✨</span><span>Why Nova asks</span></div>
                    <p class="mt-2 text-[11px] leading-5 text-blue-100">Nova uses these details only as context for better plans, explanations, and task suggestions. Your data is never treated as an instruction.</p>
                </div>
                <div class="onboarding-nova-visual pointer-events-none relative mx-auto mt-9 hidden h-32 w-32 items-center justify-center lg:flex" aria-hidden="true">
                    <span class="onboarding-nova-ring absolute inset-2 rounded-full border border-white/20"></span>
                    <span class="onboarding-nova-ring onboarding-nova-ring--reverse absolute inset-7 rounded-full border border-blue-200/30"></span>
                    <span class="onboarding-nova-core relative flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 text-xl shadow-2xl backdrop-blur">✨</span>
                    <span class="onboarding-nova-dot absolute left-1 top-1/2 h-2 w-2 rounded-full bg-cyan-300"></span>
                    <span class="onboarding-nova-dot onboarding-nova-dot--two absolute right-2 top-5 h-2.5 w-2.5 rounded-full bg-violet-200"></span>
                </div>
            </aside>

            <form method="POST" action="{{ route('onboarding.store') }}" class="onboarding-form p-5 sm:p-8 lg:p-10" data-onboarding-form>
                @csrf
                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300" role="alert">
                        <p class="font-extrabold">Please review the highlighted information.</p>
                        <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <section data-onboarding-step="1" class="onboarding-step-panel">
                    <p class="text-xs font-extrabold uppercase tracking-widest text-blue-600 dark:text-blue-400">Step 1 of 3</p>
                    <h2 class="mt-2 text-2xl font-black">About you</h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Start with the identity you want to use across your workspace.</p>
                    <div class="mt-7 grid gap-5 sm:grid-cols-2">
                        <label class="block sm:col-span-2"><span class="profile-label">Full name <b>*</b></span><input name="name" value="{{ old('name', $user->name) }}" required maxlength="100" autocomplete="name" class="profile-input"></label>
                        <label class="block sm:col-span-2"><span class="profile-label">Headline</span><input name="headline" value="{{ old('headline', $profile->headline) }}" maxlength="160" placeholder="e.g. Developer, student, or business owner" class="profile-input"></label>
                        <label class="block sm:col-span-2"><span class="profile-label">A little about you</span><textarea name="bio" rows="4" maxlength="1500" placeholder="Your goals, background, and what matters to you..." class="profile-input resize-none">{{ old('bio', $profile->bio) }}</textarea></label>
                        <label class="block"><span class="profile-label">City</span><input name="city" value="{{ old('city', $profile->city) }}" maxlength="100" placeholder="Phnom Penh" class="profile-input"></label>
                        <label class="block"><span class="profile-label">Country</span><input name="country" value="{{ old('country', $profile->country) }}" maxlength="100" placeholder="Cambodia" class="profile-input"></label>
                    </div>
                </section>

                <section data-onboarding-step="2" class="onboarding-step-panel hidden">
                    <p class="text-xs font-extrabold uppercase tracking-widest text-blue-600 dark:text-blue-400">Step 2 of 3</p>
                    <h2 class="mt-2 text-2xl font-black">What describes your world?</h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Choose at least one area. You can change these details later from My Profile.</p>
                    <fieldset class="mt-7">
                        <legend class="profile-label">I use WorkMind for <b>*</b></legend>
                        <div class="grid gap-3 sm:grid-cols-3">
                            @foreach (['work' => ['💼', 'Work'], 'study' => ['📚', 'Study'], 'other' => ['✨', 'Personal growth']] as $value => [$emoji, $label])
                                <label class="cursor-pointer"><input type="checkbox" name="profile_sections[]" value="{{ $value }}" class="peer sr-only" data-onboarding-area @checked(in_array($value, $selectedSections, true))><span class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs font-extrabold transition peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 peer-checked:ring-2 peer-checked:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800 dark:peer-checked:border-blue-500 dark:peer-checked:bg-blue-950/40 dark:peer-checked:text-blue-300"><span class="text-xl">{{ $emoji }}</span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </fieldset>
                    <div class="mt-6 grid gap-5 sm:grid-cols-2" data-onboarding-area-panel="work">
                        <h3 class="sm:col-span-2 text-sm font-extrabold">Work information</h3>
                        <label class="block"><span class="profile-label">Work status</span><select name="work_status" class="profile-input profile-select"><option value="">Select status</option>@foreach (['employed' => 'Employed', 'self_employed' => 'Self-employed', 'freelancer' => 'Freelancer', 'looking_for_work' => 'Looking for work', 'not_working' => 'Not currently working'] as $value => $label)<option value="{{ $value }}" @selected(old('work_status', $profile->work_status) === $value)>{{ $label }}</option>@endforeach</select></label>
                        <label class="block"><span class="profile-label">Job title</span><input name="job_title" value="{{ old('job_title', $profile->job_title) }}" maxlength="120" placeholder="e.g. Software Developer" class="profile-input"></label>
                        <label class="block"><span class="profile-label">Company</span><input name="company" value="{{ old('company', $profile->company) }}" maxlength="120" class="profile-input"></label>
                        <label class="block"><span class="profile-label">Industry</span><input name="industry" value="{{ old('industry', $profile->industry) }}" maxlength="120" placeholder="e.g. Technology" class="profile-input"></label>
                    </div>
                    <div class="mt-6 grid gap-5 border-t border-slate-100 pt-6 dark:border-slate-800 sm:grid-cols-2" data-onboarding-area-panel="study">
                        <h3 class="sm:col-span-2 text-sm font-extrabold">Study information</h3>
                        <label class="block"><span class="profile-label">Study status</span><select name="study_status" class="profile-input profile-select"><option value="">Select status</option>@foreach (['student' => 'Student', 'self_learning' => 'Self-learning', 'graduated' => 'Graduated', 'not_studying' => 'Not currently studying'] as $value => $label)<option value="{{ $value }}" @selected(old('study_status', $profile->study_status) === $value)>{{ $label }}</option>@endforeach</select></label>
                        <label class="block"><span class="profile-label">Education level</span><select name="education_level" class="profile-input profile-select"><option value="">Select level</option>@foreach (['high_school' => 'High school', 'associate' => 'Associate', 'bachelor' => 'Bachelor', 'master' => 'Master', 'doctorate' => 'Doctorate', 'professional' => 'Professional training', 'other' => 'Other'] as $value => $label)<option value="{{ $value }}" @selected(old('education_level', $profile->education_level) === $value)>{{ $label }}</option>@endforeach</select></label>
                        <label class="block"><span class="profile-label">Institution</span><input name="institution" value="{{ old('institution', $profile->institution) }}" maxlength="160" class="profile-input"></label>
                        <label class="block"><span class="profile-label">Field of study</span><input name="field_of_study" value="{{ old('field_of_study', $profile->field_of_study) }}" maxlength="160" class="profile-input"></label>
                    </div>
                    <div class="mt-6 grid gap-5 border-t border-slate-100 pt-6 dark:border-slate-800 sm:grid-cols-2" data-onboarding-area-panel="other">
                        <h3 class="sm:col-span-2 text-sm font-extrabold">Skills and interests</h3>
                        <label class="block"><span class="profile-label">Skills</span><textarea name="skills" rows="3" maxlength="1000" placeholder="Laravel, Communication, English" class="profile-input resize-none">{{ $skills }}</textarea></label>
                        <label class="block"><span class="profile-label">Interests</span><textarea name="interests" rows="3" maxlength="1000" placeholder="AI, Business, Design" class="profile-input resize-none">{{ $interests }}</textarea></label>
                        <label class="block sm:col-span-2"><span class="profile-label">Website or portfolio</span><input type="url" name="website" value="{{ old('website', $profile->website) }}" maxlength="255" placeholder="https://example.com" class="profile-input"></label>
                    </div>
                </section>

                <section data-onboarding-step="3" class="onboarding-step-panel hidden">
                    <p class="text-xs font-extrabold uppercase tracking-widest text-violet-600 dark:text-violet-400">Step 3 of 3</p>
                    <h2 class="mt-2 text-2xl font-black">Personalize Nova</h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">This creates Nova’s personalization profile alongside your WorkMind profile.</p>
                    <div class="mt-7 grid gap-5 sm:grid-cols-2">
                        <label class="block"><span class="profile-label">Your main role <b>*</b></span><select name="occupation" required class="profile-input profile-select"><option value="">Select your role</option>@foreach (['student' => 'Student', 'software_it' => 'Software / IT', 'education' => 'Education', 'business' => 'Business', 'design_creative' => 'Design / Creative', 'marketing_sales' => 'Marketing / Sales', 'finance' => 'Finance', 'healthcare' => 'Healthcare', 'engineering' => 'Engineering', 'government_ngo' => 'Government / NGO', 'freelancer' => 'Freelancer', 'other' => 'Other'] as $value => $label)<option value="{{ $value }}" @selected(old('occupation', $preferences?->occupation) === $value)>{{ $label }}</option>@endforeach</select></label>
                        <label class="block"><span class="profile-label">Experience level <b>*</b></span><select name="experience_level" required class="profile-input profile-select">@foreach (['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'] as $value => $label)<option value="{{ $value }}" @selected(old('experience_level', $preferences?->experience_level ?? 'beginner') === $value)>{{ $label }}</option>@endforeach</select></label>
                    </div>
                    <fieldset class="mt-6"><legend class="profile-label">What do you want to learn? <b>*</b></legend><div class="mt-2 flex flex-wrap gap-2">@foreach (['technology' => 'Technology', 'languages' => 'Languages', 'business' => 'Business', 'design_creative' => 'Design', 'finance' => 'Finance', 'marketing_sales' => 'Marketing', 'leadership' => 'Leadership', 'health_wellness' => 'Wellness', 'academic' => 'Academic', 'life_skills' => 'Life skills', 'other' => 'Other'] as $value => $label)<label class="cursor-pointer"><input type="checkbox" name="learning_interests[]" value="{{ $value }}" class="peer sr-only" @checked(in_array($value, $learningInterests, true))><span class="inline-flex rounded-full border border-slate-200 px-3 py-2 text-[11px] font-bold text-slate-600 transition peer-checked:border-violet-500 peer-checked:bg-violet-50 peer-checked:text-violet-700 dark:border-slate-700 dark:text-slate-300 dark:peer-checked:border-violet-500 dark:peer-checked:bg-violet-950/40 dark:peer-checked:text-violet-300">{{ $label }}</span></label>@endforeach</div></fieldset>
                    <fieldset class="mt-6"><legend class="profile-label">How should Nova help? <b>*</b></legend><div class="mt-2 flex flex-wrap gap-2">@foreach (['learning' => 'Learning', 'work' => 'Work', 'personal' => 'Personal', 'career' => 'Career', 'technical' => 'Technical help', 'writing_translation' => 'Writing & translation', 'planning' => 'Planning', 'creativity' => 'Creativity'] as $value => $label)<label class="cursor-pointer"><input type="checkbox" name="assistance_areas[]" value="{{ $value }}" class="peer sr-only" @checked(in_array($value, $assistanceAreas, true))><span class="inline-flex rounded-full border border-slate-200 px-3 py-2 text-[11px] font-bold text-slate-600 transition peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 dark:border-slate-700 dark:text-slate-300 dark:peer-checked:border-blue-500 dark:peer-checked:bg-blue-950/40 dark:peer-checked:text-blue-300">{{ $label }}</span></label>@endforeach</div></fieldset>
                    <label class="mt-6 block"><span class="profile-label">Anything else Nova should know?</span><textarea name="other_needs" rows="4" maxlength="1000" placeholder="For example: Help me improve English for client meetings and plan my learning each week." class="profile-input resize-none">{{ old('other_needs', $preferences?->other_needs) }}</textarea></label>
                    <div class="mt-6 flex gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300"><span>🔒</span><p class="text-[11px] leading-5"><strong class="block">Private by design</strong>Your information is saved to your account profile and used by Nova only to personalize assistance.</p></div>
                </section>

                <div class="mt-8 border-t border-slate-100 pt-5 dark:border-slate-800">
                    <div class="mb-5 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" aria-hidden="true"><span data-onboarding-progress-bar class="onboarding-progress-bar block h-full w-1/3 rounded-full bg-gradient-to-r from-blue-500 via-indigo-500 to-violet-500"></span></div>
                    <div class="flex items-center justify-between">
                    <button type="button" data-onboarding-back class="onboarding-back invisible rounded-xl px-4 py-2.5 text-xs font-extrabold text-slate-500 transition hover:bg-slate-100 dark:hover:bg-slate-800">Back</button>
                    <div class="flex items-center gap-3">
                        <span class="text-[11px] font-bold text-slate-400" data-onboarding-progress>1 / 3</span>
                        <button type="button" data-onboarding-next class="onboarding-primary-button rounded-xl bg-blue-600 px-6 py-3 text-xs font-extrabold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">Continue <span aria-hidden="true">→</span></button>
                        <button type="submit" data-onboarding-submit class="onboarding-primary-button onboarding-finish-button hidden overflow-hidden rounded-xl bg-gradient-to-r from-blue-600 to-violet-600 px-6 py-3 text-xs font-extrabold text-white shadow-lg shadow-violet-600/20 transition hover:from-blue-700 hover:to-violet-700"><span class="relative z-10">Finish setup ✨</span></button>
                    </div>
                    </div>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
