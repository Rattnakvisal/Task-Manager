@extends('layouts.app')

@section('title', 'My Profile - WorkMind')

@section('content')
    @php
        $initials = collect(preg_split('/\s+/', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
        $skillsValue = old('skills', implode(', ', $profile->skills ?? []));
        $interestsValue = old('interests', implode(', ', $profile->interests ?? []));
        $visibleSections = old('profile_sections', $selectedSections);
        $visibleSections = is_array($visibleSections) ? $visibleSections : [];
    @endphp

    <div class="mx-auto max-w-[1450px] space-y-0" data-profile-page>
        {{-- Cover --}}
        <section class="profile-cover relative min-h-56 overflow-hidden rounded-t-3xl bg-gradient-to-r from-blue-700 via-indigo-600 to-violet-600 px-6 pb-20 pt-8 text-white sm:px-10">
            <div class="pointer-events-none absolute -left-16 -top-24 h-72 w-72 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute left-1/3 top-0 h-full w-40 -skew-x-12 bg-white/5"></div>
            <div class="pointer-events-none absolute right-12 -top-32 h-80 w-80 rounded-full border-[55px] border-white/5"></div>
            <div class="relative flex items-start justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-bold backdrop-blur-sm">
                        <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
                        WorkMind Profile
                    </span>
                    <h1 class="mt-4 text-2xl font-extrabold sm:text-3xl">Your personal workspace identity</h1>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-blue-100">Connect your personal, work, study, and other information so WorkMind and Nova can support you better.</p>
                </div>
                <span class="hidden rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold backdrop-blur-sm sm:inline-flex">Private to your account</span>
            </div>
        </section>

        <div class="relative -mt-12 grid gap-6 px-4 pb-8 sm:px-7 xl:grid-cols-[270px_minmax(0,1fr)]">
            {{-- Profile summary --}}
            <aside class="self-start overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-950/5 dark:border-slate-800 dark:bg-slate-900">
                <div class="p-5 text-center">
                    <div class="relative mx-auto flex h-24 w-24 items-center justify-center rounded-full border-4 border-white bg-gradient-to-br from-blue-100 to-indigo-200 text-2xl font-extrabold text-blue-700 shadow-lg dark:border-slate-900 dark:from-blue-950 dark:to-indigo-900 dark:text-blue-300">
                        {{ $initials ?: 'WM' }}
                        <span class="absolute bottom-0 right-0 flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-blue-600 text-white dark:border-slate-900" title="WorkMind member">
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6" /></svg>
                        </span>
                    </div>
                    <h2 class="mt-3 text-base font-extrabold text-slate-950 dark:text-white">{{ $user->name }}</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $profile->headline ?: 'Add a professional headline' }}</p>
                    <p class="mt-1 truncate text-[11px] text-slate-400">{{ $user->email }}</p>
                </div>

                <div class="grid grid-cols-2 border-y border-slate-100 dark:border-slate-800">
                    <div class="border-r border-slate-100 px-3 py-3 text-center dark:border-slate-800">
                        <p class="text-lg font-extrabold text-slate-900 dark:text-white">{{ $totalTasks }}</p>
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Tasks</p>
                    </div>
                    <div class="px-3 py-3 text-center">
                        <p class="text-lg font-extrabold text-emerald-600">{{ $completedTasks }}</p>
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Completed</p>
                    </div>
                </div>

                <div class="space-y-3 p-5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-600 dark:text-slate-300">Profile completion</span>
                        <span class="font-extrabold text-blue-600 dark:text-blue-400">{{ $profileCompletion }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 transition-all" style="width: {{ $profileCompletion }}%"></div>
                    </div>
                    <p class="text-[10.5px] leading-5 text-slate-400">A complete profile gives Nova better context for work, learning, and personal guidance.</p>
                </div>
            </aside>

            {{-- Settings card --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-950/5 dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-4 pt-3 dark:border-slate-800 sm:px-6">
                    <div class="no-scrollbar flex gap-1 overflow-x-auto" role="tablist" aria-label="Profile sections">
                        @foreach ([
                            'personal' => ['user', 'Personal'],
                            'work' => ['project', 'Work'],
                            'study' => ['clipboard', 'Study'],
                            'other' => ['sparkles', 'Other'],
                        ] as $tab => [$icon, $label])
                            <button type="button" role="tab" data-profile-tab="{{ $tab }}" @if ($tab !== 'personal') data-profile-optional-tab @endif aria-controls="profile-panel-{{ $tab }}" aria-selected="{{ $tab === 'personal' ? 'true' : 'false' }}"
                                class="profile-tab {{ $tab !== 'personal' && !in_array($tab, $visibleSections, true) ? 'hidden' : 'inline-flex' }} shrink-0 items-center gap-2 border-b-2 px-4 py-3 text-xs font-bold transition {{ $tab === 'personal' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                                <x-icon :name="$icon" class="h-3.5 w-3.5" />
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="p-5 sm:p-7" data-profile-form>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="personal" data-profile-section>

                    <div id="profile-panel-personal" data-profile-panel="personal" role="tabpanel" class="space-y-5">
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-950 dark:text-white">Personal information</h3>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Add your basic information, then choose one or more profile areas. Only your selected areas will appear above.</p>
                        </div>
                        <fieldset>
                            <legend class="profile-label">What will you use WorkMind for? <b>*</b></legend>
                            <p class="mb-3 text-[11px] leading-5 text-slate-400">Select one, two, or all three, then save Personal information to update the tabs above.</p>
                            <div class="grid gap-3 sm:grid-cols-3">
                                @foreach ([
                                    'work' => ['project', 'Work', 'Career, company, and professional goals'],
                                    'study' => ['clipboard', 'Study', 'Education, courses, and learning goals'],
                                    'other' => ['sparkles', 'Other', 'Skills, interests, and portfolio'],
                                ] as $area => [$icon, $label, $description])
                                    <label class="profile-area-option cursor-pointer">
                                        <input type="checkbox" name="profile_sections[]" value="{{ $area }}" data-profile-section-choice class="peer sr-only" @checked(in_array($area, $visibleSections, true))>
                                        <span class="flex h-full gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3.5 transition hover:border-blue-300 hover:bg-blue-50/60 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:ring-2 peer-checked:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800/60 dark:hover:border-blue-700 dark:hover:bg-blue-950/30 dark:peer-checked:border-blue-500 dark:peer-checked:bg-blue-950/40">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 shadow-sm peer-checked:text-blue-600 dark:bg-slate-900 dark:text-slate-400">
                                                <x-icon :name="$icon" class="h-4 w-4" />
                                            </span>
                                            <span>
                                                <span class="block text-xs font-extrabold text-slate-800 dark:text-white">{{ $label }}</span>
                                                <span class="mt-1 block text-[10px] leading-4 text-slate-400">{{ $description }}</span>
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('profile_sections')
                                <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
                            @enderror
                        </fieldset>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block md:col-span-2">
                                <span class="profile-label">Full name <b>*</b></span>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="100" required class="profile-input">
                            </label>
                            <label class="block">
                                <span class="profile-label">Email address</span>
                                <input type="email" value="{{ $user->email }}" disabled class="profile-input opacity-70">
                                <span class="mt-1 block text-[10px] text-slate-400">Your sign-in email cannot be changed here.</span>
                            </label>
                            <label class="block">
                                <span class="profile-label">Professional headline</span>
                                <input type="text" name="headline" value="{{ old('headline', $profile->headline) }}" maxlength="160" placeholder="e.g. Designer, student, or business owner" class="profile-input">
                            </label>
                            <label class="block md:col-span-2">
                                <span class="profile-label">About you</span>
                                <textarea name="bio" rows="4" maxlength="1500" placeholder="Share a short introduction, your goals, and what matters to you..." class="profile-input resize-none">{{ old('bio', $profile->bio) }}</textarea>
                            </label>
                            <label class="block">
                                <span class="profile-label">City</span>
                                <input type="text" name="city" value="{{ old('city', $profile->city) }}" maxlength="100" placeholder="Phnom Penh" class="profile-input">
                            </label>
                            <label class="block">
                                <span class="profile-label">Country</span>
                                <input type="text" name="country" value="{{ old('country', $profile->country) }}" maxlength="100" placeholder="Cambodia" class="profile-input">
                            </label>
                        </div>
                    </div>

                    <div id="profile-panel-work" data-profile-panel="work" role="tabpanel" class="hidden space-y-5">
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-950 dark:text-white">Work information</h3>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Connect your current role and professional direction with Nova’s work guidance.</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block">
                                <span class="profile-label">Work status</span>
                                <select name="work_status" class="profile-input profile-select">
                                    <option value="">Select status</option>
                                    <option value="employed" @selected(old('work_status', $profile->work_status) === 'employed')>Employed</option>
                                    <option value="self_employed" @selected(old('work_status', $profile->work_status) === 'self_employed')>Self-employed</option>
                                    <option value="freelancer" @selected(old('work_status', $profile->work_status) === 'freelancer')>Freelancer</option>
                                    <option value="looking_for_work" @selected(old('work_status', $profile->work_status) === 'looking_for_work')>Looking for work</option>
                                    <option value="not_working" @selected(old('work_status', $profile->work_status) === 'not_working')>Not currently working</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="profile-label">Job title or role</span>
                                <input type="text" name="job_title" value="{{ old('job_title', $profile->job_title) }}" maxlength="120" placeholder="e.g. Project Manager" class="profile-input">
                            </label>
                            <label class="block">
                                <span class="profile-label">Company or organization</span>
                                <input type="text" name="company" value="{{ old('company', $profile->company) }}" maxlength="120" placeholder="Organization name" class="profile-input">
                            </label>
                            <label class="block">
                                <span class="profile-label">Industry</span>
                                <input type="text" name="industry" value="{{ old('industry', $profile->industry) }}" maxlength="120" placeholder="e.g. Education, Finance, Technology" class="profile-input">
                            </label>
                        </div>
                    </div>

                    <div id="profile-panel-study" data-profile-panel="study" role="tabpanel" class="hidden space-y-5">
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-950 dark:text-white">Study information</h3>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Help Nova tailor learning plans, examples, and explanations to your education.</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block">
                                <span class="profile-label">Study status</span>
                                <select name="study_status" class="profile-input profile-select">
                                    <option value="">Select status</option>
                                    <option value="student" @selected(old('study_status', $profile->study_status) === 'student')>Currently studying</option>
                                    <option value="self_learning" @selected(old('study_status', $profile->study_status) === 'self_learning')>Self-learning</option>
                                    <option value="graduated" @selected(old('study_status', $profile->study_status) === 'graduated')>Graduated</option>
                                    <option value="not_studying" @selected(old('study_status', $profile->study_status) === 'not_studying')>Not currently studying</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="profile-label">Education level</span>
                                <select name="education_level" class="profile-input profile-select">
                                    <option value="">Select level</option>
                                    @foreach (['high_school' => 'High school', 'associate' => 'Associate', 'bachelor' => 'Bachelor', 'master' => 'Master', 'doctorate' => 'Doctorate', 'professional' => 'Professional training', 'other' => 'Other'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('education_level', $profile->education_level) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block">
                                <span class="profile-label">School or institution</span>
                                <input type="text" name="institution" value="{{ old('institution', $profile->institution) }}" maxlength="160" placeholder="Institution name" class="profile-input">
                            </label>
                            <label class="block">
                                <span class="profile-label">Field of study</span>
                                <input type="text" name="field_of_study" value="{{ old('field_of_study', $profile->field_of_study) }}" maxlength="160" placeholder="e.g. Business Administration" class="profile-input">
                            </label>
                        </div>
                    </div>

                    <div id="profile-panel-other" data-profile-panel="other" role="tabpanel" class="hidden space-y-5">
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-950 dark:text-white">Skills and other information</h3>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Add skills and interests separated by commas. These help connect your work, study, and personal goals.</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block md:col-span-2">
                                <span class="profile-label">Skills</span>
                                <textarea name="skills" rows="3" maxlength="1000" placeholder="Communication, Laravel, English, Project Management" class="profile-input resize-none">{{ $skillsValue }}</textarea>
                            </label>
                            <label class="block md:col-span-2">
                                <span class="profile-label">Interests</span>
                                <textarea name="interests" rows="3" maxlength="1000" placeholder="Technology, Business, Design, Personal Growth" class="profile-input resize-none">{{ $interestsValue }}</textarea>
                            </label>
                            <label class="block md:col-span-2">
                                <span class="profile-label">Website or portfolio</span>
                                <input type="url" name="website" value="{{ old('website', $profile->website) }}" maxlength="255" placeholder="https://your-website.com" class="profile-input">
                            </label>
                        </div>
                    </div>

                    <div class="mt-7 flex flex-col-reverse items-stretch justify-between gap-3 border-t border-slate-100 pt-5 dark:border-slate-800 sm:flex-row sm:items-center">
                        <p class="text-[10.5px] text-slate-400">Only the section currently selected above will be saved.</p>
                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 text-xs font-extrabold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                            <span data-profile-save-text>Save personal information</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
