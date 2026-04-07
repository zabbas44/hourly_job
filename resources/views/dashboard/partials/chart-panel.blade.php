<div class="page-header page-header-wrap">
    <div>
        <p class="eyebrow">Monthly chart / Gráfico mensual / ماہانہ چارٹ</p>
        <p class="muted chart-range-label">Range / Rango / مدت: {{ $rangeLabel }}</p>
    </div>

    <form method="GET" action="{{ route('dashboard') }}" class="chart-filter" data-dashboard-filter>
        <label class="field">
            <span>Quick range / Rango rápido / فوری مدت</span>
            <select name="preset" data-filter-preset>
                <option value="">Custom or month</option>
                @foreach ($presetOptions as $presetValue => $presetLabel)
                    <option value="{{ $presetValue }}" @selected($preset === $presetValue)>{{ $presetLabel }}</option>
                @endforeach
            </select>
        </label>

        <label class="field">
            <span>Month / Mes / مہینہ</span>
            <select name="month" data-filter-month>
                @foreach ($monthOptions as $monthOption)
                    <option value="{{ $monthOption->format('Y-m') }}" @selected($selectedMonth->format('Y-m') === $monthOption->format('Y-m'))>
                        {{ $monthOption->format('F Y') }}
                    </option>
                @endforeach
            </select>
        </label>

        <div class="field chart-range-field">
            <span>Date selector / Selector de fechas / تاریخ منتخب کریں</span>
            <button type="button" class="chart-range-trigger" data-range-trigger aria-label="Select date range">
                <span class="chart-range-trigger-icon" aria-hidden="true">&#128197;</span>
                <span class="chart-range-trigger-text">
                    {{ $fromDate && $toDate ? $fromDate.' - '.$toDate : 'Select dates' }}
                </span>
            </button>

            <div class="chart-range-popover" data-range-popover hidden>
                <div class="chart-range-inputs">
                    <input type="date" name="from_date" value="{{ $fromDate }}" data-range-date="from" aria-label="Start date">
                    <input type="date" name="to_date" value="{{ $toDate }}" data-range-date="to" aria-label="End date">
                </div>
            </div>
        </div>
    </form>
</div>

@if ($workerChart->isEmpty())
    <p class="muted">No workers available for chart / No hay trabajadores para el gráfico / چارٹ کے لیے کوئی کارکن موجود نہیں۔</p>
@else
    <div class="chart-list">
        @foreach ($workerChart as $row)
            <article class="chart-row">
                <div class="chart-row-header">
                    <div class="chart-worker-meta">
                        <strong>
                            <a href="{{ route('workers.schedule.show', ['worker' => $row['id'], 'month' => $selectedMonth->format('Y-m')]) }}" class="chart-worker-link">
                                {{ $row['name'] }}
                            </a>
                        </strong>
                        <div class="chart-worker-links">
                            <small class="muted">{{ '€'.number_format($row['amount'], 2) }}</small>
                            <a href="{{ route('payments.index', ['worker_id' => $row['id'], 'open_payment' => 1]) }}" class="chart-payment-link">Pay / Pagar / ادائیگی</a>
                        </div>
                    </div>
                    <span>{{ number_format($row['hours'], 2) }} / {{ $monthlyTargetHours }}h</span>
                </div>
                <div class="chart-bar">
                    <div class="chart-bar-fill" style="width: {{ $row['percent'] }}%"></div>
                </div>
                <small class="muted">{{ number_format($row['percent'], 1) }}% of target / del objetivo / ہدف کا حصہ</small>
            </article>
        @endforeach
    </div>
@endif
