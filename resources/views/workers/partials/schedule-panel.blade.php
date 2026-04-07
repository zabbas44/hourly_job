<div class="stats-grid schedule-stats">
    <article class="stat-card">
        <span>Month hours / Horas del mes / ماہ کے گھنٹے</span>
        <strong>{{ number_format((float) $monthlyHours, 2) }}</strong>
    </article>
    <article class="stat-card">
        <span>Hourly price / Precio por hora / فی گھنٹہ قیمت</span>
        <strong>{{ '€'.number_format((float) $worker->hourly_rate, 2) }}</strong>
    </article>
    <article class="stat-card">
        <span>Month total price / Precio total del mes / ماہ کی کل قیمت</span>
        <strong>{{ '€'.number_format((float) $monthlyTotal, 2) }}</strong>
    </article>
</div>

@if ((float) $worker->hourly_rate <= 0)
    <div class="alert alert-error">
        Hourly price is currently €0.00, so the month total price stays €0.00.
        Update the worker price to calculate the total amount.
    </div>
@endif

<div class="calendar-topbar">
    <a href="{{ route('workers.schedule.show', ['worker' => $worker, 'month' => $previousMonth]) }}" class="calendar-nav-button" data-calendar-nav aria-label="Previous month">
        <span aria-hidden="true">&#x2039;</span>
    </a>
    <h2>{{ $selectedMonth->format('F Y') }}</h2>
    <a href="{{ route('workers.schedule.show', ['worker' => $worker, 'month' => $nextMonth]) }}" class="calendar-nav-button" data-calendar-nav aria-label="Next month">
        <span aria-hidden="true">&#x203A;</span>
    </a>
</div>

<div class="calendar-grid calendar-head">
    @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
        <div>{{ $dayName }}</div>
    @endforeach
</div>

<div class="calendar-grid">
    @foreach ($calendarDays as $day)
        @php($dateString = $day['date']->toDateString())
        @php($entry = $day['entry'])
        @php($dailyRate = $entry ? $entry->effectiveHourlyRate((float) $worker->hourly_rate) : null)
        @php($dailyTotal = $entry ? ((float) $entry->hours * (float) $dailyRate) : null)
        <button
            type="button"
            class="calendar-day {{ $day['inMonth'] ? '' : 'calendar-day-muted' }} {{ $entry ? 'calendar-day-filled' : '' }} {{ $entry?->is_paid ? 'calendar-day-paid' : '' }}"
            data-schedule-trigger
            data-date="{{ $dateString }}"
            data-display-date="{{ $day['date']->format('D, d M Y') }}"
            data-hours="{{ $entry?->hours }}"
            data-entry-id="{{ $entry?->id }}"
            data-project-id="{{ $entry?->project_id }}"
            data-rate-override="{{ $entry?->hourly_rate_override }}"
        >
            <span>{{ $day['date']->day }}</span>
            @if ($entry)
                <div class="calendar-metrics">
                    <strong class="calendar-metric calendar-metric-project">
                        <span class="calendar-metric-icon" aria-hidden="true">&#9638;</span>
                        <span>{{ \Illuminate\Support\Str::title($entry->project?->name ?? '') }}</span>
                    </strong>
                    <strong class="calendar-metric">
                        <span class="calendar-metric-icon" aria-hidden="true">&#9716;</span>
                        <span>{{ $entry->hours }}h</span>
                    </strong>
                    <strong class="calendar-metric calendar-metric-price">
                        <span class="calendar-metric-icon" aria-hidden="true">&euro;</span>
                        <span>{{ '€'.number_format((float) $dailyTotal, 2) }}</span>
                    </strong>
                    @if ($entry->is_paid)
                        <small class="calendar-paid-label">Paid</small>
                    @endif
                </div>
            @else
                <small>Add hours / Agregar horas / گھنٹے شامل کریں</small>
            @endif
        </button>
    @endforeach
</div>
