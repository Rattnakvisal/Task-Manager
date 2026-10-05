<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $profile = $user->profile()->firstOrNew();
        $taskStats = $user->tasks()
            ->selectRaw('COUNT(*) AS total_tasks')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_tasks")
            ->first();
        $selectedSections = $this->selectedSections($profile);

        $profileFields = [
            $user->name,
            $profile->headline,
            $profile->bio,
            $profile->city,
            $profile->country,
        ];
        if (in_array('work', $selectedSections, true)) {
            array_push($profileFields, $profile->work_status, $profile->job_title, $profile->company, $profile->industry);
        }
        if (in_array('study', $selectedSections, true)) {
            array_push($profileFields, $profile->study_status, $profile->education_level, $profile->institution, $profile->field_of_study);
        }
        if (in_array('other', $selectedSections, true)) {
            array_push($profileFields, $profile->skills, $profile->interests, $profile->website);
        }
        $completedFields = collect($profileFields)->filter(fn ($value) => ! empty($value))->count();

        return view('profile.edit', [
            'user' => $user,
            'profile' => $profile,
            'totalTasks' => (int) $taskStats->total_tasks,
            'completedTasks' => (int) $taskStats->completed_tasks,
            'profileCompletion' => (int) round(($completedFields / count($profileFields)) * 100),
            'selectedSections' => $selectedSections,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $sections = ['personal', 'work', 'study', 'other'];
        $requestedSection = $request->string('section')->toString();
        $section = in_array($requestedSection, $sections, true) ? $requestedSection : 'personal';

        if ($section !== 'personal' && ! in_array($section, $this->selectedSections($request->user()->profile), true)) {
            return redirect()->to(route('profile.edit').'#personal')
                ->withErrors(['profile_sections' => 'Select and save this profile area before adding its information.']);
        }

        $sectionRules = match ($section) {
            'work' => [
                'work_status' => 'nullable|string|in:not_working,employed,self_employed,freelancer,looking_for_work',
                'job_title' => 'nullable|string|max:120',
                'company' => 'nullable|string|max:120',
                'industry' => 'nullable|string|max:120',
            ],
            'study' => [
                'study_status' => 'nullable|string|in:not_studying,student,self_learning,graduated',
                'education_level' => 'nullable|string|in:high_school,associate,bachelor,master,doctorate,professional,other',
                'institution' => 'nullable|string|max:160',
                'field_of_study' => 'nullable|string|max:160',
            ],
            'other' => [
                'skills' => 'nullable|string|max:1000',
                'interests' => 'nullable|string|max:1000',
                'website' => 'nullable|url:http,https|max:255',
            ],
            default => [
                'name' => 'required|string|max:100',
                'headline' => 'nullable|string|max:160',
                'bio' => 'nullable|string|max:1500',
                'city' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'profile_sections' => 'required|array|min:1|max:3',
                'profile_sections.*' => 'required|string|distinct|in:work,study,other',
            ],
        };

        $validator = Validator::make($request->all(), [
            'section' => 'required|string|in:personal,work,study,other',
            ...$sectionRules,
        ]);

        if ($validator->fails()) {
            return redirect()->to(route('profile.edit').'#'.$section)
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        unset($validated['section']);

        $profileData = collect($validated)->except(['name', 'skills', 'interests'])->all();
        if ($section === 'other') {
            $profileData['skills'] = $this->parseList($validated['skills'] ?? null);
            $profileData['interests'] = $this->parseList($validated['interests'] ?? null);
        }

        DB::transaction(function () use ($request, $section, $validated, $profileData) {
            if ($section === 'personal') {
                $request->user()->update(['name' => $validated['name']]);
            }

            $request->user()->profile()->updateOrCreate(
                ['user_id' => $request->user()->id],
                $profileData,
            );
        });

        return redirect()->to(route('profile.edit').'#'.$section)
            ->with('success', ucfirst($section).' information has been updated.');
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

    private function selectedSections($profile): array
    {
        if (! $profile) {
            return [];
        }

        if (is_array($profile->profile_sections)) {
            return array_values(array_intersect(['work', 'study', 'other'], $profile->profile_sections));
        }

        return collect([
            'work' => [$profile->work_status, $profile->job_title, $profile->company, $profile->industry],
            'study' => [$profile->study_status, $profile->education_level, $profile->institution, $profile->field_of_study],
            'other' => [$profile->skills, $profile->interests, $profile->website],
        ])->filter(fn ($fields) => collect($fields)->contains(fn ($value) => ! empty($value)))
            ->keys()
            ->all();
    }
}
