@extends('layouts.app', ['title' => 'Worker Schedule'])

@section('body')
    <div class="app-shell">
        <x-sidebar />

        <main class="content">
            <div class="page-header page-header-wrap">
                <div class="schedule-header-main">
                    <p class="eyebrow">Schedule / Horario / شیڈول</p>
                    <div class="schedule-title-row">
                        <h1>{{ $worker->name }}</h1>
                        <div class="schedule-title-actions">
                            <a href="{{ route('payments.index', ['worker_id' => $worker->id, 'open_payment' => 1]) }}" class="schedule-pay-icon" aria-label="Pay worker">
                                <span aria-hidden="true">&euro;</span>
                            </a>
                            <a href="{{ route('workers.index') }}" class="schedule-back-icon" aria-label="Back to workers">
                                <span aria-hidden="true">&#x2039;</span>
                            </a>
                        </div>
                    </div>
                    <p class="muted">{{ $worker->email }} · {{ $worker->phone }} · {{ $worker->formattedRate() }}</p>
                </div>
                <div class="schedule-header-actions">
                    <a href="{{ route('payments.index', ['worker_id' => $worker->id, 'open_payment' => 1]) }}" class="button button-success">Pay worker / Pagar / ادائیگی</a>
                    <a href="{{ route('workers.index') }}" class="button button-secondary schedule-back-button">Back to workers / Volver a trabajadores / کارکنوں پر واپس</a>
                </div>
            </div>

            <x-flash />

            <section class="panel stack-lg">
                <div data-schedule-content>
                    @include('workers.partials.schedule-panel')
                </div>
            </section>
        </main>
    </div>

    <div class="modal-backdrop" data-schedule-modal hidden>
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <p class="eyebrow">Daily hours / Horas diarias / یومیہ گھنٹے</p>
                    <h2 data-modal-date>Select hours / Seleccionar horas / گھنٹے منتخب کریں</h2>
                </div>
                <button type="button" class="icon-button" data-modal-close>&times;</button>
            </div>

            <form method="POST" action="{{ route('workers.schedule.store', $worker) }}" class="stack-md" id="schedule-entry-form">
                @csrf
                <input type="hidden" name="work_date" data-modal-work-date>
                <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}" data-modal-month>

                <label class="field">
                    <span>Project / Proyecto / منصوبہ</span>
                    <select name="project_id" data-modal-project required>
                        <option value="">Select project / Seleccionar proyecto / منصوبہ منتخب کریں</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>Rate type / Tipo de tarifa / ریٹ کی قسم</span>
                    <select name="rate_type_override" data-modal-rate-type>
                        <option value="">Use worker default ({{ $worker->rateTypeLabel() === 'day' ? 'day' : 'hour' }})</option>
                        @foreach ($rateTypeOptions as $rateTypeValue => $rateTypeLabel)
                            <option value="{{ $rateTypeValue }}">{{ $rateTypeLabel }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>Rate / Tarifa / ریٹ</span>
                    <select name="hourly_rate_override" data-modal-rate>
                        <option value="">Use worker default ({{ $worker->formattedRate() }})</option>
                        @foreach ($rateOptions as $rateOption)
                            <option value="{{ $rateOption }}">{{ '€'.number_format((float) $rateOption, 2) }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="hours-grid">
                    @foreach ($hourOptions as $hours)
                        <label class="hour-option">
                            <input type="radio" name="hours" value="{{ $hours }}" required>
                            <span>{{ $hours }} hour{{ $hours > 1 ? 's' : '' }} / {{ $hours }} hora{{ $hours > 1 ? 's' : '' }} / {{ $hours }} گھنٹہ</span>
                        </label>
                    @endforeach
                </div>

            </form>

            <div class="modal-action-row single-action" data-modal-actions>
                <button type="submit" form="schedule-entry-form" class="button button-success button-block">Save hours / Guardar horas / گھنٹے محفوظ کریں</button>

                <form method="POST" action="" data-delete-form hidden class="modal-action-form">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                    <button type="submit" class="button button-danger button-block">Remove entry / Eliminar entrada / اندراج حذف کریں</button>
                </form>
            </div>
        </div>
    </div>
@endsection
