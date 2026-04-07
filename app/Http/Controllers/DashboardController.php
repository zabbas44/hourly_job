<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\Worker;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|Response
    {
        $today = CarbonImmutable::today();
        $month = $request->string('month')->toString();
        $selectedMonth = $month !== ''
            ? CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth()
            : $today->startOfMonth();
        $preset = $request->string('preset')->toString();
        $fromDate = $request->string('from_date')->toString();
        $toDate = $request->string('to_date')->toString();
        [$presetStart, $presetEnd] = $this->presetRange($preset, $today);
        $usesPreset = $presetStart !== null && $presetEnd !== null;
        $usesCustomRange = ! $usesPreset && $fromDate !== '' && $toDate !== '';
        $rangeStart = $usesPreset
            ? $presetStart->startOfDay()
            : ($usesCustomRange ? CarbonImmutable::parse($fromDate)->startOfDay() : $selectedMonth->startOfMonth());
        $rangeEnd = $usesPreset
            ? $presetEnd->endOfDay()
            : ($usesCustomRange ? CarbonImmutable::parse($toDate)->endOfDay() : $selectedMonth->endOfMonth());
        if ($usesPreset) {
            $fromDate = $rangeStart->toDateString();
            $toDate = $rangeEnd->toDateString();
        }
        $monthlyTargetHours = 160;
        $monthOptions = collect(range(0, 11))
            ->map(fn (int $offset) => $today->startOfMonth()->subMonths($offset))
            ->values();
        $presetOptions = [
            'last_10_days' => 'Last 10 days',
            'last_30_days' => 'Last 30 days',
            'last_60_days' => 'Last 60 days',
            'last_90_days' => 'Last 90 days',
            'last_3_months' => 'Last 3 months',
            'last_6_months' => 'Last 6 months',
        ];

        $workerChart = Worker::query()
            ->with(['timeEntries' => function ($query) use ($rangeStart, $rangeEnd): void {
                $query->whereBetween('work_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()]);
            }])
            ->orderBy('name')
            ->get()
            ->map(function (Worker $worker) use ($monthlyTargetHours): array {
                $hours = (float) $worker->timeEntries->sum('hours');
                $percent = $monthlyTargetHours > 0
                    ? min(100, round(($hours / $monthlyTargetHours) * 100, 1))
                    : 0;
                $amount = $worker->timeEntries->sum(
                    fn (TimeEntry $entry) => $worker->calculateEntryAmount($entry)
                );

                return [
                    'id' => $worker->id,
                    'name' => $worker->name,
                    'hours' => $hours,
                    'amount' => $amount,
                    'target' => $monthlyTargetHours,
                    'percent' => $percent,
                ];
            });

        $viewData = [
            'workerCount' => Worker::count(),
            'entriesToday' => TimeEntry::whereDate('work_date', $today)->count(),
            'hoursToday' => TimeEntry::whereDate('work_date', $today)->sum('hours'),
            'selectedMonth' => $selectedMonth,
            'monthOptions' => $monthOptions,
            'preset' => $preset,
            'presetOptions' => $presetOptions,
            'workerChart' => $workerChart,
            'monthlyTargetHours' => $monthlyTargetHours,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'usesCustomRange' => $usesCustomRange,
            'usesPreset' => $usesPreset,
            'rangeLabel' => $usesCustomRange
                ? $rangeStart->format('d M Y').' - '.$rangeEnd->format('d M Y')
                : ($usesPreset ? $rangeStart->format('d M Y').' - '.$rangeEnd->format('d M Y') : $selectedMonth->format('F Y')),
        ];

        if ($request->ajax()) {
            return response()->view('dashboard.partials.chart-panel', $viewData);
        }

        return view('dashboard.index', $viewData);
    }

    private function presetRange(string $preset, CarbonImmutable $today): array
    {
        return match ($preset) {
            'last_10_days' => [$today->subDays(9), $today],
            'last_30_days' => [$today->subDays(29), $today],
            'last_60_days' => [$today->subDays(59), $today],
            'last_90_days' => [$today->subDays(89), $today],
            'last_3_months' => [$today->subMonths(3)->addDay(), $today],
            'last_6_months' => [$today->subMonths(6)->addDay(), $today],
            default => [null, null],
        };
    }
}
