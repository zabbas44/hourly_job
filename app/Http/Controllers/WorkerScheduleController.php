<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\Worker;
use App\Services\WorkerLedgerService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class WorkerScheduleController extends Controller
{
    public function __construct(private readonly WorkerLedgerService $ledger)
    {
    }

    public function show(Request $request, Worker $worker): View|Response
    {
        $month = $request->string('month')->toString();
        $selectedMonth = $month !== ''
            ? CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth()
            : CarbonImmutable::today()->startOfMonth();

        $start = $selectedMonth->startOfMonth()->startOfWeek();
        $end = $selectedMonth->endOfMonth()->endOfWeek();
        $monthlyEntriesQuery = $worker->timeEntries()
            ->whereYear('work_date', $selectedMonth->year)
            ->whereMonth('work_date', $selectedMonth->month);
        $monthlyEntries = (clone $monthlyEntriesQuery)->get();
        $entries = $worker->timeEntries()
            ->with('project')
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn (TimeEntry $entry) => $entry->work_date->toDateString());
        $monthlyHours = (clone $monthlyEntriesQuery)->sum('hours');
        $monthlyTotal = $monthlyEntries->sum(
            fn (TimeEntry $entry) => $worker->calculateEntryAmount($entry)
        );
        $ledgerSummary = $this->ledger->summary($worker);

        $days = [];
        $cursor = $start;

        while ($cursor->lte($end)) {
            $entry = $entries->get($cursor->toDateString());
            if ($entry) {
                $entry->is_paid = $ledgerSummary['entry_statuses'][$entry->id]['is_paid'] ?? false;
            }

            $days[] = [
                'date' => $cursor,
                'inMonth' => $cursor->month === $selectedMonth->month,
                'entry' => $entry,
            ];
            $cursor = $cursor->addDay();
        }

        $viewData = [
            'worker' => $worker,
            'calendarDays' => $days,
            'selectedMonth' => $selectedMonth,
            'previousMonth' => $selectedMonth->subMonth()->format('Y-m'),
            'nextMonth' => $selectedMonth->addMonth()->format('Y-m'),
            'hourOptions' => range(1, 16),
            'monthlyHours' => $monthlyHours,
            'monthlyTotal' => $monthlyTotal,
            'projects' => \App\Models\Project::query()->orderBy('name')->get(),
            'rateOptions' => $this->rateOptions((float) $worker->hourly_rate),
            'rateTypeOptions' => [
                Worker::RATE_TYPE_HOUR => 'Per hour / Por hora / فی گھنٹہ',
                Worker::RATE_TYPE_DAY => 'Per day / Por día / یومیہ',
            ],
        ];

        if ($request->ajax()) {
            return response()->view('workers.partials.schedule-panel', $viewData);
        }

        return view('workers.schedule', $viewData);
    }

    public function store(Request $request, Worker $worker): RedirectResponse
    {
        $validated = $request->validate([
            'work_date' => ['required', 'date'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'hours' => ['required', 'integer', 'min:1', 'max:16'],
            'hourly_rate_override' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'rate_type_override' => ['nullable', 'string', 'in:hour,day'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $date = CarbonImmutable::parse($validated['work_date'])->toDateString();
        $timeEntry = $worker->timeEntries()->whereDate('work_date', $date)->first();

        if ($timeEntry) {
            $timeEntry->update([
                'project_id' => $validated['project_id'],
                'hours' => $validated['hours'],
                'hourly_rate_override' => $validated['hourly_rate_override'] ?? null,
                'rate_type_override' => $validated['rate_type_override'] ?? null,
            ]);
        } else {
            $worker->timeEntries()->create([
                'project_id' => $validated['project_id'],
                'work_date' => $date,
                'hours' => $validated['hours'],
                'hourly_rate_override' => $validated['hourly_rate_override'] ?? null,
                'rate_type_override' => $validated['rate_type_override'] ?? null,
            ]);
        }

        return redirect()
            ->route('workers.schedule.show', [
                'worker' => $worker,
                'month' => $validated['month'] ?? CarbonImmutable::parse($date)->format('Y-m'),
            ])
            ->with('status', 'Daily hours saved successfully.');
    }

    private function rateOptions(float $workerRate): array
    {
        $options = collect(range(0, 200))
            ->map(fn (int $index) => number_format($index * 0.5, 2, '.', ''))
            ->push(number_format($workerRate, 2, '.', ''))
            ->unique()
            ->sortBy(fn (string $rate) => (float) $rate)
            ->values();

        return $options->all();
    }

    public function destroy(Request $request, Worker $worker, TimeEntry $timeEntry): RedirectResponse
    {
        abort_unless($timeEntry->worker_id === $worker->id, 404);

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $validated['month'] ?? $timeEntry->work_date->format('Y-m');

        $timeEntry->delete();

        return redirect()
            ->route('workers.schedule.show', [
                'worker' => $worker,
                'month' => $month,
            ])
            ->with('status', 'Daily hours removed successfully.');
    }
}
