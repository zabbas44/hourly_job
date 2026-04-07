@extends('layouts.app', ['title' => $mode === 'create' ? 'Add Worker' : 'Edit Worker'])

@section('body')
    <div class="app-shell">
        <x-sidebar />

        <main class="content">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Workers / Trabajadores / کارکن</p>
                    <h1>{{ $mode === 'create' ? 'Add a worker' : 'Edit worker' }}</h1>
                </div>
                <a href="{{ route('workers.index') }}" class="button button-secondary">Back / Volver / واپس</a>
            </div>

            <section class="panel form-panel">
                <form method="POST" action="{{ $mode === 'create' ? route('workers.store') : route('workers.update', $worker) }}" class="form-grid">
                    @csrf
                    @if ($mode === 'edit')
                        @method('PUT')
                    @endif

                    <label class="field">
                        <span>Name / Nombre / نام</span>
                        <input type="text" name="name" value="{{ old('name', $worker->name) }}" required>
                        @error('name') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="field">
                        <span>Phone number / Número de teléfono / فون نمبر</span>
                        <input type="text" name="phone" value="{{ old('phone', $worker->phone) }}" required>
                        @error('phone') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="field">
                        <span>Email address / Correo electrónico / ای میل پتہ</span>
                        <input type="email" name="email" value="{{ old('email', $worker->email) }}" required>
                        @error('email') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="field">
                        <span>Bank title / Título bancario / بینک عنوان</span>
                        <input type="text" name="bank_title" value="{{ old('bank_title', $worker->bank_title) }}" required>
                        @error('bank_title') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="field field-full">
                        <span>Account number / Número de cuenta / اکاؤنٹ نمبر</span>
                        <input type="text" name="account_number" value="{{ old('account_number', $worker->account_number) }}" required>
                        @error('account_number') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="field">
                        <span>Hourly price (EUR) / Precio por hora (EUR) / فی گھنٹہ قیمت (یورو)</span>
                        <input type="number" name="hourly_rate" value="{{ old('hourly_rate', $worker->hourly_rate) }}" min="0" step="0.01" required>
                        @error('hourly_rate') <small>{{ $message }}</small> @enderror
                    </label>

                    <button type="submit" class="button button-primary">
                        {{ $mode === 'create' ? 'Save worker / Guardar trabajador / کارکن محفوظ کریں' : 'Update worker / Actualizar trabajador / کارکن اپڈیٹ کریں' }}
                    </button>
                </form>
            </section>
        </main>
    </div>
@endsection
