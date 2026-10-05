<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $mode === 'register' ? 'Create account' : 'Sign in' }} · WorkMind</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Logo/Glossy Blue Checklist App Icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page relative" data-auth-mode="{{ $mode }}">
    <div id="page-loading-skeleton" class="page-loading-skeleton fixed inset-0 z-[100] flex items-center justify-center bg-[#f6f8fc] p-4 dark:bg-slate-950" role="status" aria-live="polite" aria-label="Loading authentication page">
        <span class="sr-only">Loading authentication page...</span>
        <div class="grid w-full max-w-5xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900 md:grid-cols-2">
            <div class="space-y-5 p-8 sm:p-12">
                <span class="skeleton-block block h-3 w-24 rounded-full"></span>
                <span class="skeleton-block block h-8 w-44 rounded-lg"></span>
                <span class="skeleton-block block h-3 w-72 max-w-full rounded-full"></span>
                <div class="space-y-4 pt-4">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="space-y-2">
                            <span class="skeleton-block block h-2.5 w-20 rounded-full"></span>
                            <span class="skeleton-block block h-11 w-full rounded-xl"></span>
                        </div>
                    @endfor
                    <span class="skeleton-block block h-11 w-full rounded-xl"></span>
                </div>
            </div>
            <div class="hidden bg-blue-950 p-12 md:flex md:flex-col md:justify-between">
                <div class="flex items-center gap-3">
                    <span class="skeleton-block h-10 w-10 rounded-xl opacity-40"></span>
                    <span class="skeleton-block h-3 w-28 rounded-full opacity-40"></span>
                </div>
                <div class="space-y-4">
                    <span class="skeleton-block block h-8 w-48 rounded-lg opacity-40"></span>
                    <span class="skeleton-block block h-3 w-full rounded-full opacity-40"></span>
                    <span class="skeleton-block block h-3 w-4/5 rounded-full opacity-40"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed top-5 right-5 z-50">
        <button type="button" data-theme-toggle
            class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-700"
            aria-label="Toggle theme" title="Toggle Theme">
            <svg data-theme-sun viewBox="0 0 24 24" class="h-4.5 w-4.5 text-amber-500 transition" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg>
            <svg data-theme-moon viewBox="0 0 24 24" class="hidden h-4.5 w-4.5 text-blue-400 transition" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
        </button>
    </div>
    @if (session('success'))
        <div id="flash-session-success" data-message="{{ session('success') }}" class="hidden"></div>
    @endif
    @if (session('error'))
        <div id="flash-session-error" data-message="{{ session('error') }}" class="hidden"></div>
    @endif
    @if ($errors->any())
        <div id="flash-session-errors" data-errors="{{ json_encode($errors->all()) }}" class="hidden"></div>
    @endif
    <main class="auth-switcher {{ $mode === 'register' ? 'show-register' : '' }}" data-auth-switcher
        data-login-url="{{ route('login', [], false) }}" data-register-url="{{ route('register', [], false) }}">
        <section class="auth-form-pane auth-register-pane" aria-labelledby="register-heading">
            <div class="auth-form-content">
                <p class="auth-eyebrow">Start planning</p>
                <h1 id="register-heading">Create account</h1>
                <p class="auth-subtitle">Build a private workspace for your tasks.</p>

                <form method="POST" action="{{ route('register.store', [], false) }}" data-animated-form>
                    @csrf
                    <div class="auth-field">
                        <label for="register-name">Full name</label>
                        <input id="register-name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="100" required>
                        @if ($mode === 'register') @error('name') <p class="auth-error" role="alert">{{ $message }}</p> @enderror @endif
                    </div>
                    <div class="auth-field">
                        <label for="register-email">Email address</label>
                        <input id="register-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" required>
                        @if ($mode === 'register') @error('email') <p class="auth-error" role="alert">{{ $message }}</p> @enderror @endif
                    </div>
                    <div class="auth-field">
                        <label for="register-password">Password</label>
                        <div class="auth-password-wrap">
                            <input id="register-password" name="password" type="password" autocomplete="new-password"
                                minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
                                title="Use at least 8 characters with uppercase, lowercase, a number, and a symbol."
                                aria-describedby="register-password-hint" required>
                            <button type="button" class="auth-password-toggle" data-password-toggle="register-password" aria-label="Show password" aria-pressed="false">
                                <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.7a2 2 0 0 0 2.7 2.7M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a17 17 0 0 1-2.1 3.1M6.6 6.6C3.5 8.5 2 12 2 12s3.5 8 10 8a10 10 0 0 0 4-.8"/></svg>
                            </button>
                        </div>
                        @if ($mode === 'register') @error('password') <p class="auth-error" role="alert">{{ $message }}</p> @enderror @endif
                    </div>
                    <div class="auth-field">
                        <label for="register-password-confirmation">Confirm password</label>
                        <div class="auth-password-wrap">
                            <input id="register-password-confirmation" name="password_confirmation" type="password"
                                autocomplete="new-password" minlength="8" required>
                            <button type="button" class="auth-password-toggle" data-password-toggle="register-password-confirmation" aria-label="Show password" aria-pressed="false">
                                <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.7a2 2 0 0 0 2.7 2.7M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a17 17 0 0 1-2.1 3.1M6.6 6.6C3.5 8.5 2 12 2 12s3.5 8 10 8a10 10 0 0 0 4-.8"/></svg>
                            </button>
                        </div>
                    </div>
                    <p id="register-password-hint" class="auth-hint">8+ characters with uppercase, lowercase, a number, and a symbol.</p>
                    <button type="submit" class="auth-submit">Create account</button>
                </form>

                <button type="button" class="auth-mobile-switch" data-show-login>Already registered? <strong>Sign in</strong></button>
            </div>
        </section>

        <section class="auth-form-pane auth-login-pane" aria-labelledby="login-heading">
            <div class="auth-form-content">
                <p class="auth-eyebrow">Welcome back</p>
                <h1 id="login-heading">Sign in</h1>
                <p class="auth-subtitle">Continue to your personal workspace.</p>

                <form method="POST" action="{{ route('login.store', [], false) }}" data-animated-form>
                    @csrf
                    <div class="auth-field">
                        <label for="login-email">Email address</label>
                        <input id="login-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" required>
                        @if ($mode === 'login') @error('email') <p class="auth-error" role="alert">{{ $message }}</p> @enderror @endif
                    </div>
                    <div class="auth-field">
                        <label for="login-password">Password</label>
                        <div class="auth-password-wrap">
                            <input id="login-password" name="password" type="password" autocomplete="current-password" required>
                            <button type="button" class="auth-password-toggle" data-password-toggle="login-password" aria-label="Show password" aria-pressed="false">
                                <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.7a2 2 0 0 0 2.7 2.7M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a17 17 0 0 1-2.1 3.1M6.6 6.6C3.5 8.5 2 12 2 12s3.5 8 10 8a10 10 0 0 0 4-.8"/></svg>
                            </button>
                        </div>
                        @if ($mode === 'login') @error('password') <p class="auth-error" role="alert">{{ $message }}</p> @enderror @endif
                    </div>
                    <label class="auth-remember">
                        <input type="checkbox" name="remember" value="1">
                        <span>Keep me signed in</span>
                    </label>
                    <button type="submit" class="auth-submit">Sign in</button>
                </form>

                <button type="button" class="auth-mobile-switch" data-show-register>New to WorkMind? <strong>Create account</strong></button>
            </div>
        </section>

        <aside class="auth-overlay" aria-live="polite">
            <div class="auth-overlay-decoration auth-orb-one"></div>
            <div class="auth-overlay-decoration auth-orb-two"></div>
            <a href="{{ route('login', [], false) }}" class="auth-brand">
                <span><img src="{{ asset('images/Logo/Glossy Blue Checklist App Icon.png') }}" alt="" class="h-full w-full object-contain"></span>
                WorkMind
            </a>

            <div class="auth-overlay-message auth-message-login">
                <h2>Hey there!</h2>
                <p>Welcome back. You are one step away from a more organized day.</p>
                <span>Don't have an account?</span>
                <button type="button" data-show-register>Create account</button>
            </div>

            <div class="auth-overlay-message auth-message-register">
                <h2>Welcome back!</h2>
                <p>Your tasks and priorities are ready when you are.</p>
                <span>Already have an account?</span>
                <button type="button" data-show-login>Sign in</button>
            </div>
        </aside>
    </main>
</body>
</html>
