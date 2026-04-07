<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkerController extends Controller
{
    public function index(): View
    {
        return view('workers.index', [
            'workers' => Worker::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('workers.form', [
            'worker' => new Worker(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Worker::create($this->validatedData($request));

        return redirect()
            ->route('workers.index')
            ->with('status', 'Worker created successfully.');
    }

    public function edit(Worker $worker): View
    {
        return view('workers.form', [
            'worker' => $worker,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Worker $worker): RedirectResponse
    {
        $worker->update($this->validatedData($request, $worker));

        return redirect()
            ->route('workers.index')
            ->with('status', 'Worker updated successfully.');
    }

    public function destroy(Worker $worker): RedirectResponse
    {
        $worker->delete();

        return redirect()
            ->route('workers.index')
            ->with('status', 'Worker removed successfully.');
    }

    private function validatedData(Request $request, ?Worker $worker = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('workers', 'email')->ignore($worker?->id),
            ],
            'bank_title' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'rate_type' => ['nullable', Rule::in([Worker::RATE_TYPE_HOUR, Worker::RATE_TYPE_DAY])],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $validated['rate_type'] = $validated['rate_type'] ?? Worker::RATE_TYPE_HOUR;
        $validated['hourly_rate'] = $validated['hourly_rate'] ?? 1;

        return $validated;
    }
}
