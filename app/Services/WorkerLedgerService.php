<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\TimeEntry;
use App\Models\Worker;
use Illuminate\Support\Collection;
use NumberFormatter;

class WorkerLedgerService
{
    public function summary(Worker $worker): array
    {
        $worker->loadMissing(['timeEntries.project', 'payments']);

        /** @var Collection<int, TimeEntry> $entries */
        $entries = $worker->timeEntries->sortBy([['work_date', 'asc'], ['id', 'asc']])->values();
        /** @var Collection<int, Payment> $payments */
        $payments = $worker->payments->sortBy([['paid_on', 'asc'], ['id', 'asc']])->values();

        $entryStatuses = [];
        $ledger = [];
        $totalEarned = 0.0;
        $totalPaid = (float) $payments->sum('amount');
        $remainingPaid = $totalPaid;
        $oldestUnpaidMonth = null;
        $runningBalance = 0.0;

        foreach ($entries as $entry) {
            $rate = $entry->effectiveHourlyRate((float) $worker->hourly_rate);
            $amount = round($entry->hours * $rate, 2);
            $totalEarned += $amount;
            $paidAmount = min($remainingPaid, $amount);
            $remainingPaid = round($remainingPaid - $paidAmount, 2);
            $isPaid = $paidAmount >= $amount - 0.009;

            if (! $isPaid && $oldestUnpaidMonth === null) {
                $oldestUnpaidMonth = $entry->work_date->format('F Y');
            }

            $entryStatuses[$entry->id] = [
                'amount' => $amount,
                'paid_amount' => $paidAmount,
                'is_paid' => $isPaid,
                'unpaid_amount' => round($amount - $paidAmount, 2),
            ];

            $runningBalance = round($runningBalance + $amount, 2);
            $ledger[] = [
                'date' => $entry->work_date,
                'time' => null,
                'type' => 'charge',
                'label' => ($entry->project?->name ? $entry->project->name.' · ' : '').$entry->hours.'h',
                'method' => null,
                'debit' => $amount,
                'credit' => 0.0,
                'balance' => $runningBalance,
                'sort_at' => $entry->work_date->copy()->startOfDay(),
            ];
        }

        foreach ($payments as $payment) {
            $runningBalance = round($runningBalance - (float) $payment->amount, 2);
            $ledger[] = [
                'date' => $payment->paid_on,
                'time' => $payment->created_at?->format('H:i'),
                'type' => 'payment',
                'label' => 'Payment',
                'method' => $payment->method,
                'debit' => 0.0,
                'credit' => (float) $payment->amount,
                'balance' => $runningBalance,
                'sort_at' => $payment->created_at
                    ? $payment->created_at->copy()->setDate(
                        $payment->paid_on->year,
                        $payment->paid_on->month,
                        $payment->paid_on->day
                    )
                    : $payment->paid_on->copy()->endOfDay(),
            ];
        }

        $ledger = collect($ledger)
            ->sortBy(fn (array $row) => $row['sort_at']->timestamp)
            ->values()
            ->map(function (array $row): array {
                unset($row['sort_at']);

                return $row;
            })
            ->all();

        $outstanding = round(max($totalEarned - $totalPaid, 0), 2);
        $credit = round(max($totalPaid - $totalEarned, 0), 2);

        return [
            'total_earned' => round($totalEarned, 2),
            'total_paid' => round($totalPaid, 2),
            'outstanding' => $outstanding,
            'credit' => $credit,
            'oldest_unpaid_month' => $oldestUnpaidMonth,
            'entry_statuses' => $entryStatuses,
            'history' => $ledger,
            'balance_note' => $this->balanceNote($outstanding, $credit),
        ];
    }

    private function balanceNote(float $outstanding, float $credit): array
    {
        if ($credit > 0) {
            return [
                'en' => 'You have paid more by '.$this->formatAmountPhrase($credit, 'en').'.',
                'es' => 'Ha pagado de más '.$this->formatAmountPhrase($credit, 'es').'.',
                'ur' => 'آپ نے زیادہ ادائیگی کی ہے '.$this->formatAmountPhrase($credit, 'ur').'۔',
            ];
        }

        if ($outstanding > 0) {
            return [
                'en' => 'You have paid less by '.$this->formatAmountPhrase($outstanding, 'en').'.',
                'es' => 'Ha pagado de menos '.$this->formatAmountPhrase($outstanding, 'es').'.',
                'ur' => 'آپ نے کم ادائیگی کی ہے '.$this->formatAmountPhrase($outstanding, 'ur').'۔',
            ];
        }

        return [
            'en' => 'Payments are settled in full at '.$this->formatAmountPhrase(0, 'en').'.',
            'es' => 'Los pagos están liquidados por completo en '.$this->formatAmountPhrase(0, 'es').'.',
            'ur' => 'ادائیگیاں مکمل طور پر نمٹا دی گئی ہیں '.$this->formatAmountPhrase(0, 'ur').'۔',
        ];
    }

    private function formatAmountPhrase(float $amount, string $locale): string
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::SPELLOUT);
        $euros = (int) floor($amount);
        $cents = (int) round(($amount - $euros) * 100);
        $euroWords = $formatter->format($euros);
        $centWords = $formatter->format($cents);

        return match ($locale) {
            'es' => '€'.number_format($amount, 2).' ('.$euroWords.' euros'.($cents > 0 ? ' y '.$centWords.' céntimos' : '').')',
            'ur' => '€'.number_format($amount, 2).' ('.$euroWords.' یورو'.($cents > 0 ? ' اور '.$centWords.' سینٹ' : '').')',
            default => '€'.number_format($amount, 2).' ('.$euroWords.' euro'.($euros === 1 ? '' : 's').($cents > 0 ? ' and '.$centWords.' cents' : '').')',
        };
    }
}
