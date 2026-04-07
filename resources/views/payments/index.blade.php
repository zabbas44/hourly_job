@extends('layouts.app', ['title' => 'Payments'])

@section('body')
    <div class="app-shell">
        <x-sidebar />

        <main class="content">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Payments / Pagos / ادائیگیاں</p>
                    <h1>Pagos y saldos / کارکن ادائیگیاں اور بیلنس</h1>
                </div>
            </div>

            <x-flash />

            <section class="panel stack-md">
                <div class="page-header page-header-wrap">
                    <div>
                        <p class="eyebrow">Choose worker / Elegir trabajador / کارکن منتخب کریں</p>
                    </div>
                    <div class="payment-filter-actions">
                        <form method="GET" action="{{ route('payments.index') }}" class="payment-filter-form" data-payment-filter-form>
                            <label class="field">
                                <span>Worker / Trabajador / کارکن</span>
                                <select name="worker_id" data-payment-worker-filter>
                                    <option value="">All workers / Todos / تمام کارکن</option>
                                    @foreach ($workerOptions as $workerOption)
                                        <option value="{{ $workerOption->id }}" @selected($selectedWorkerId === $workerOption->id)>{{ $workerOption->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </form>

                        <a href="{{ route('payments.export.csv', array_filter(['worker_id' => $selectedWorkerId])) }}" class="button button-secondary">Export CSV / Exportar CSV / CSV برآمد</a>
                    </div>
                </div>
            </section>

            <section class="panel stack-lg">
                @if ($workerLedgers->isEmpty())
                    <p class="muted">No matching worker found / No se encontró el trabajador / منتخب کارکن نہیں ملا۔</p>
                @else
                    @foreach ($workerLedgers as $item)
                        @php($worker = $item['worker'])
                        @php($summary = $item['summary'])
                        <article class="payment-card">
                            <div class="payment-card-header">
                                <div>
                                    <h2>{{ $worker->name }}</h2>
                                    <p class="muted">{{ $worker->email }} · {{ $worker->phone }}</p>
                                </div>
                                <a href="{{ route('payments.print', $worker) }}" class="icon-button payment-print-button" target="_blank" rel="noopener" aria-label="Print payment statement">
                                    <span aria-hidden="true">&#128424;</span>
                                </a>
                            </div>

                            <div class="payment-summary-grid">
                                <article class="stat-card">
                                    <span>Total earned / Total ganado / کل کمائی</span>
                                    <strong>{{ '€'.number_format($summary['total_earned'], 2) }}</strong>
                                </article>
                                <article class="stat-card">
                                    <span>Total paid / Total pagado / کل ادا شدہ</span>
                                    <strong>{{ '€'.number_format($summary['total_paid'], 2) }}</strong>
                                </article>
                                <article class="stat-card">
                                    <span>Outstanding / Pendiente / بقایا</span>
                                    <strong>{{ '€'.number_format($summary['outstanding'], 2) }}</strong>
                                </article>
                                <article class="stat-card">
                                    <span>Credit / Crédito / کریڈٹ</span>
                                    <strong>{{ '€'.number_format($summary['credit'], 2) }}</strong>
                                </article>
                            </div>

                            <div class="payment-action-row">
                                <a href="{{ route('payments.export.csv', ['worker_id' => $worker->id]) }}" class="button button-secondary">CSV</a>
                                <button
                                    type="button"
                                    class="button button-primary"
                                    data-payment-trigger
                                    data-worker-id="{{ $worker->id }}"
                                    data-worker-name="{{ $worker->name }}"
                                    data-total-earned="{{ number_format($summary['total_earned'], 2, '.', '') }}"
                                    data-total-paid="{{ number_format($summary['total_paid'], 2, '.', '') }}"
                                    data-outstanding="{{ number_format($summary['outstanding'], 2, '.', '') }}"
                                    data-credit="{{ number_format($summary['credit'], 2, '.', '') }}"
                                    data-oldest-unpaid-month="{{ $summary['oldest_unpaid_month'] }}"
                                    @if ($autoOpenPayment && $selectedWorkerId === $worker->id) data-payment-auto-open="true" @endif
                                >
                                    Add payment / Agregar pago / ادائیگی شامل کریں
                                </button>
                            </div>

                            @if ($summary['oldest_unpaid_month'])
                                <p class="muted">Oldest unpaid month / Mes pendiente más antiguo / سب سے پرانا بقایا مہینہ: {{ $summary['oldest_unpaid_month'] }}</p>
                            @elseif ($summary['credit'] > 0)
                                <p class="muted">Credit available for next payment / Crédito disponible para el siguiente pago / اگلی ادائیگی کے لیے کریڈٹ دستیاب</p>
                            @endif

                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date / Fecha / تاریخ</th>
                                            <th>Time / Hora / وقت</th>
                                            <th>Type / Tipo / قسم</th>
                                            <th>Method / Método / طریقہ</th>
                                            <th>Detail / Detalle / تفصیل</th>
                                            <th>Debit / Débito / ڈیبٹ</th>
                                            <th>Credit / Crédito / کریڈٹ</th>
                                            <th>Balance / Saldo / بیلنس</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($summary['history'] as $row)
                                            <tr>
                                                <td data-label="Date / Fecha / تاریخ">{{ $row['date']->format('d M Y') }}</td>
                                                <td data-label="Time / Hora / وقت">{{ $row['time'] ?? '—' }}</td>
                                                <td data-label="Type / Tipo / قسم">{{ $row['type'] === 'charge' ? 'Charge / Cargo / چارج' : 'Payment / Pago / ادائیگی' }}</td>
                                                <td data-label="Method / Método / طریقہ">{{ $row['method'] ? str_replace('_', ' ', ucfirst($row['method'])) : '—' }}</td>
                                                <td data-label="Detail / Detalle / تفصیل">{{ $row['label'] }}</td>
                                                <td data-label="Debit / Débito / ڈیبٹ">{{ $row['debit'] > 0 ? '€'.number_format($row['debit'], 2) : '—' }}</td>
                                                <td data-label="Credit / Crédito / کریڈٹ">{{ $row['credit'] > 0 ? '€'.number_format($row['credit'], 2) : '—' }}</td>
                                                <td data-label="Balance / Saldo / بیلنس">{{ '€'.number_format($row['balance'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="payment-note">
                                <p><strong>English:</strong> {{ $summary['balance_note']['en'] }}</p>
                                <p><strong>Español:</strong> {{ $summary['balance_note']['es'] }}</p>
                                <p><strong>اردو:</strong> {{ $summary['balance_note']['ur'] }}</p>
                            </div>
                        </article>
                    @endforeach
                @endif
            </section>
        </main>
    </div>

    <div class="modal-backdrop" data-payment-modal hidden>
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <p class="eyebrow">Payment / Pago / ادائیگی</p>
                    <h2 data-payment-worker-name>Worker payment / Pago del trabajador / کارکن ادائیگی</h2>
                </div>
                <button type="button" class="icon-button" data-payment-close>&times;</button>
            </div>

            <div class="payment-modal-summary">
                <p>Total earned / Total ganado / کل کمائی: <strong data-payment-total-earned>€0.00</strong></p>
                <p>Total paid / Total pagado / کل ادا شدہ: <strong data-payment-total-paid>€0.00</strong></p>
                <p>Outstanding / Pendiente / بقایا: <strong data-payment-outstanding>€0.00</strong></p>
                <p>Credit / Crédito / کریڈٹ: <strong data-payment-credit>€0.00</strong></p>
                <p data-payment-oldest-unpaid-row hidden>Oldest unpaid month / Mes pendiente más antiguo / سب سے پرانا بقایا مہینہ: <strong data-payment-oldest-unpaid></strong></p>
            </div>

            <form method="POST" action="{{ route('payments.store') }}" class="stack-md">
                @csrf
                <input type="hidden" name="worker_id" data-payment-worker-id>
                <input type="hidden" name="filter_worker_id" value="{{ $selectedWorkerId }}">

                <label class="field">
                    <span>Payment date / Fecha de pago / ادائیگی کی تاریخ</span>
                    <input type="date" name="paid_on" value="{{ now()->toDateString() }}" required>
                </label>

                <label class="field">
                    <span>Payment amount / Importe / ادائیگی رقم</span>
                    <input type="number" name="amount" min="0.01" step="0.01" required data-payment-amount>
                </label>

                <label class="field">
                    <span>Method / Método / طریقہ</span>
                    <select name="method" required>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>Notes / Notas / نوٹس</span>
                    <input type="text" name="notes">
                </label>

                <button type="submit" class="button button-primary button-block">Save payment / Guardar pago / ادائیگی محفوظ کریں</button>
            </form>
        </div>
    </div>
@endsection
