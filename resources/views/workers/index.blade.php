@extends('layouts.app', ['title' => 'Workers'])

@section('body')
    <div class="app-shell">
        <x-sidebar />

        <main class="content">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Workers / Trabajadores / کارکن</p>
                    <h1>Manage worker records</h1>
                </div>
                <a href="{{ route('workers.create') }}" class="button button-primary">Add worker / Agregar trabajador / کارکن شامل کریں</a>
            </div>

            <x-flash />

            <section class="panel">
                @if ($workers->isEmpty())
                    <p class="muted">No workers added yet / Aún no hay trabajadores / ابھی تک کوئی کارکن شامل نہیں کیا گیا۔</p>
                @else
                    <div class="worker-mobile-list">
                        @foreach ($workers as $worker)
                            <details class="worker-mobile-card">
                                <summary class="worker-mobile-summary">
                                    <div class="worker-mobile-heading">
                                        <strong>{{ $worker->name }}</strong>
                                        <span>{{ '€'.number_format((float) $worker->hourly_rate, 2) }}</span>
                                    </div>
                                    <span class="worker-mobile-arrow" aria-hidden="true">&#x203A;</span>
                                </summary>

                                <div class="worker-mobile-body">
                                    <div class="worker-mobile-meta">
                                        <p><span>Phone / Teléfono / فون</span>{{ $worker->phone }}</p>
                                        <p><span>Email / Correo / ای میل</span>{{ $worker->email }}</p>
                                        <p><span>Bank title / Título bancario / بینک عنوان</span>{{ $worker->bank_title }}</p>
                                        <p><span>Account number / Número de cuenta / اکاؤنٹ نمبر</span>{{ $worker->account_number }}</p>
                                    </div>

                                    <div class="worker-mobile-actions">
                                        <a href="{{ route('workers.schedule.show', $worker) }}" class="button button-secondary">Time</a>
                                        <a href="{{ route('payments.index', ['worker_id' => $worker->id, 'open_payment' => 1]) }}" class="button button-secondary">Pay</a>
                                        <a href="{{ route('workers.edit', $worker) }}" class="button button-secondary">Edit</a>
                                        <form method="POST" action="{{ route('workers.destroy', $worker) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="button button-danger" onclick="return confirm('Remove this worker?')">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name / Nombre / نام</th>
                                    <th>Phone / Teléfono / فون</th>
                                    <th>Email / Correo / ای میل</th>
                                    <th>Bank title / Título bancario / بینک عنوان</th>
                                    <th>Account number / Número de cuenta / اکاؤنٹ نمبر</th>
                                    <th>Hourly price / Precio por hora / فی گھنٹہ قیمت</th>
                                    <th>Actions / Acciones / کارروائیاں</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($workers as $worker)
                                    <tr>
                                        <td data-label="Name / Nombre / نام">{{ $worker->name }}</td>
                                        <td data-label="Phone / Teléfono / فون">{{ $worker->phone }}</td>
                                        <td data-label="Email / Correo / ای میل">{{ $worker->email }}</td>
                                        <td data-label="Bank title / Título bancario / بینک عنوان">{{ $worker->bank_title }}</td>
                                        <td data-label="Account number / Número de cuenta / اکاؤنٹ نمبر">{{ $worker->account_number }}</td>
                                        <td data-label="Hourly price / Precio por hora / فی گھنٹہ قیمت">{{ '€'.number_format((float) $worker->hourly_rate, 2) }}</td>
                                        <td data-label="Actions / Acciones / کارروائیاں">
                                            <div class="actions">
                                                <a href="{{ route('workers.schedule.show', $worker) }}" class="button button-secondary">Time / Tiempo / وقت</a>
                                                <a href="{{ route('payments.index', ['worker_id' => $worker->id, 'open_payment' => 1]) }}" class="button button-secondary">Pay / Pagar / ادائیگی</a>
                                                <a href="{{ route('workers.edit', $worker) }}" class="button button-secondary">Edit / Editar / ترمیم</a>
                                                <form method="POST" action="{{ route('workers.destroy', $worker) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="button button-danger" onclick="return confirm('Remove this worker?')">Delete / Eliminar / حذف کریں</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </main>
    </div>
@endsection
