<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->profile()->first();

        if (! $profile || $profile->onboarding_completed_at) {
            return redirect()->intended(route('dashboard'));
        }

        return view('onboarding.show', [
            'user' => $request->user(),
            'profile' => $profile,
            'preferences' => $request->user()->aiPreference()->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $request->user()->profile()->first();
        if (! $profile || $profile->onboarding_completed_at) {
            return redirect()->intended(route('dashboard'));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'headline' => ['nullable', 'string', 'max:160'],
            'bio' => ['nullable', 'string', 'max:1500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'profile_sections' => ['required', 'array', 'min:1', 'max:3'],
            'profile_sections.*' => ['required', 'string', 'distinct', 'in:work,study,other'],
            'work_status' => ['nullable', 'string', 'in:not_working,employed,self_employed,freelancer,looking_for_work'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'industry' => ['nullable', 'string', 'max:120'],
            'study_status' => ['nullable', 'string', 'in:not_studying,student,self_learning,graduated'],
            'education_level' => ['nullable', 'string', 'in:high_school,associate,bachelor,master,doctorate,professional,other'],
            'institution' => ['nullable', 'string', 'max:160'],
            'field_of_study' => ['nullable', 'string', 'max:160'],
            'skills' => ['nullable', 'string', 'max:1000'],
            'interests' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'occupation' => ['required', 'string', 'in:student,software_it,education,business,design_creative,marketing_sales,finance,healthcare,engineering,government_ngo,freelancer,other'],
            'experience_level' => ['required', 'string', 'in:beginner,intermediate,advanced'],
            'learning_interests' => ['required', 'array', 'min:1', 'max:11'],
            'learning_interests.*' => ['string', 'distinct', 'in:technology,languages,business,design_creative,finance,marketing_sales,leadership,health_wellness,academic,life_skills,other'],
            'assistance_areas' => ['required', 'array', 'min:1', 'max:8'],
            'assistance_areas.*' => ['string', 'distinct', 'in:learning,work,personal,career,technical,writing_translation,planning,creativity'],
            'other_needs' => ['nullable', 'string', 'max:1000'],
        ]);

        $profileData = collect($validated)->only([
            'headline', 'bio', 'city', 'country', 'profile_sections', 'work_status',
            'job_title', 'company', 'industry', 'study_status', 'education_level',
            'institution', 'field_of_study', 'website',
        ])->all();
        $profileData['skills'] = $this->parseList($validated['skills'] ?? null);
        $profileData['interests'] = $this->parseList($validated['interests'] ?? null);
        $profileData['onboarding_completed_at'] = now();

        $preferenceData = collect($validated)->only([
            'occupation', 'experience_level', 'learning_interests', 'assistance_areas', 'other_needs',
        ])->all();
        $preferenceData['work_skills'] = [];

        DB::transaction(function () use ($request, $validated, $profileData, $preferenceData) {
            $request->user()->update(['name' => $validated['name']]);
            $request->user()->profile()->updateOrCreate(
                ['user_id' => $request->user()->id],
                $profileData,
            );
            $request->user()->aiPreference()->updateOrCreate(
                ['user_id' => $request->user()->id],
                $preferenceData,
            );
        });

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Welcome to WorkMind! Your profile and Nova personalization are ready.');
    }

    private function parseList(?string $value): array
    {
        if (blank($value)) {
            return [];
        }

        return collect(preg_split('/[,\n]+/', $value))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique(fn ($item) => mb_strtolower($item))
            ->take(20)
            ->values()
            ->all();
    }
}
