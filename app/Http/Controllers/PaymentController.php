<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Worker;
use App\Services\WorkerLedgerService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly WorkerLedgerService $ledger)
    {
    }

    public function index(Request $request): View
    {
        $selectedWorkerId = $request->integer('worker_id');
        $selectedWorkerId = $selectedWorkerId > 0 ? $selectedWorkerId : null;

        $workers = Worker::with(['payments', 'timeEntries.project'])
            ->orderBy('name')
            ->get()
            ->map(fn (Worker $worker): array => [
                'worker' => $worker,
                'summary' => $this->ledger->summary($worker),
            ]);

        $filteredWorkers = $selectedWorkerId
            ? $workers->filter(fn (array $item): bool => $item['worker']->id === $selectedWorkerId)->values()
            : $workers;

        return view('payments.index', [
            'workerLedgers' => $filteredWorkers,
            'workerOptions' => Worker::orderBy('name')->get(['id', 'name']),
            'paymentMethods' => $this->paymentMethods(),
            'selectedWorkerId' => $selectedWorkerId,
            'autoOpenPayment' => $request->boolean('open_payment') && $selectedWorkerId !== null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'worker_id' => ['required', 'integer', 'exists:workers,id'],
            'filter_worker_id' => ['nullable', 'integer', 'exists:workers,id'],
            'paid_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'method' => ['required', 'string', 'in:bank_transfer,cash,other'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        Payment::create([
            'worker_id' => $validated['worker_id'],
            'paid_on' => CarbonImmutable::parse($validated['paid_on'])->toDateString(),
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $routeParameters = [];

        if (! empty($validated['filter_worker_id'])) {
            $routeParameters['worker_id'] = $validated['filter_worker_id'];
        }

        return redirect()
            ->route('payments.index', $routeParameters)
            ->with('status', 'Payment recorded successfully.');
    }

    public function print(Worker $worker): View
    {
        $worker->load(['payments', 'timeEntries.project']);

        return view('payments.print', [
            'worker' => $worker,
            'summary' => $this->ledger->summary($worker),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $selectedWorkerId = $request->integer('worker_id');
        $selectedWorkerId = $selectedWorkerId > 0 ? $selectedWorkerId : null;

        $workers = Worker::with(['payments', 'timeEntries.project'])
            ->orderBy('name')
            ->when($selectedWorkerId, fn ($query) => $query->whereKey($selectedWorkerId))
            ->get();

        $filename = $selectedWorkerId
            ? 'worker-payments-'.$selectedWorkerId.'-'.now()->format('Y-m-d').'.csv'
            : 'all-worker-payments-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($workers): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Worker',
                'Date',
                'Time',
                'Type',
                'Method',
                'Detail',
                'Debit (EUR)',
                'Credit (EUR)',
                'Balance (EUR)',
                'Total Earned (EUR)',
                'Total Paid (EUR)',
                'Outstanding (EUR)',
                'Credit Total (EUR)',
            ]);

            foreach ($workers as $worker) {
                $summary = $this->ledger->summary($worker);

                foreach ($summary['history'] as $row) {
                    fputcsv($handle, [
                        $worker->name,
                        $row['date']->format('Y-m-d'),
                        $row['time'] ?? '',
                        $row['type'] === 'charge' ? 'Charge' : 'Payment',
                        $row['method'] ? str_replace('_', ' ', ucfirst($row['method'])) : '',
                        $row['label'],
                        $row['debit'] > 0 ? number_format($row['debit'], 2, '.', '') : '',
                        $row['credit'] > 0 ? number_format($row['credit'], 2, '.', '') : '',
                        number_format($row['balance'], 2, '.', ''),
                        number_format($summary['total_earned'], 2, '.', ''),
                        number_format($summary['total_paid'], 2, '.', ''),
                        number_format($summary['outstanding'], 2, '.', ''),
                        number_format($summary['credit'], 2, '.', ''),
                    ]);
                }

                fputcsv($handle, []);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function paymentMethods(): array
    {
        return [
            'bank_transfer' => 'Bank transfer / Transferencia bancaria / بینک ٹرانسفر',
            'cash' => 'Cash / Efectivo / نقد',
            'other' => 'Other / Otro / دیگر',
        ];
    }
}
