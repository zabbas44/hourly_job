<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Worker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        return view('projects.index', [
            'projects' => Project::with('workers')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('projects.form', [
            'project' => new Project(),
            'mode' => 'create',
            'workers' => Worker::orderBy('name')->get(),
            'selectedWorkers' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $project = Project::create($validated);
        $project->workers()->sync($validated['worker_ids'] ?? []);

        return redirect()
            ->route('projects.index')
            ->with('status', 'Project created successfully.');
    }

    public function edit(Project $project): View
    {
        return view('projects.form', [
            'project' => $project,
            'mode' => 'edit',
            'workers' => Worker::orderBy('name')->get(),
            'selectedWorkers' => $project->workers()->pluck('workers.id')->all(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $project->update($validated);
        $project->workers()->sync($validated['worker_ids'] ?? []);

        return redirect()
            ->route('projects.index')
            ->with('status', 'Project updated successfully.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('status', 'Project removed successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:500'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:50'],
            'worker_ids' => ['nullable', 'array'],
            'worker_ids.*' => ['integer', 'exists:workers,id'],
        ]);
    }
}
