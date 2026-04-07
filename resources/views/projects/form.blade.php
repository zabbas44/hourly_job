@extends('layouts.app', ['title' => $mode === 'create' ? 'Add Project' : 'Edit Project'])

@section('body')
    <div class="app-shell">
        <x-sidebar />

        <main class="content">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Projects / Proyectos / منصوبے</p>
                    <h1>{{ $mode === 'create' ? 'Add a project' : 'Edit project' }}</h1>
                </div>
                <a href="{{ route('projects.index') }}" class="button button-secondary">Back / Volver / واپس</a>
            </div>

            <section class="panel form-panel">
                <form method="POST" action="{{ $mode === 'create' ? route('projects.store') : route('projects.update', $project) }}" class="form-grid">
                    @csrf
                    @if ($mode === 'edit')
                        @method('PUT')
                    @endif

                    <label class="field">
                        <span>Project name / Nombre del proyecto / منصوبے کا نام</span>
                        <input type="text" name="name" value="{{ old('name', $project->name) }}" required>
                        @error('name') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="field">
                        <span>Client name / Nombre del cliente / کلائنٹ کا نام</span>
                        <input type="text" name="client_name" value="{{ old('client_name', $project->client_name) }}">
                        @error('client_name') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="field field-full">
                        <span>Project address / Dirección del proyecto / منصوبے کا پتہ</span>
                        <input type="text" name="location" value="{{ old('location', $project->location) }}">
                        @error('location') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="field">
                        <span>Client phone / Teléfono del cliente / کلائنٹ فون</span>
                        <input type="text" name="client_phone" value="{{ old('client_phone', $project->client_phone) }}">
                        @error('client_phone') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="field field-full">
                        <span>Assign workers / Asignar trabajadores / کارکن منتخب کریں</span>
                        <select name="worker_ids[]" multiple size="8">
                            @foreach ($workers as $worker)
                                <option value="{{ $worker->id }}" @selected(in_array($worker->id, old('worker_ids', $selectedWorkers), true))>
                                    {{ $worker->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('worker_ids') <small>{{ $message }}</small> @enderror
                    </label>

                    <button type="submit" class="button button-primary">
                        {{ $mode === 'create' ? 'Save project / Guardar proyecto / منصوبہ محفوظ کریں' : 'Update project / Actualizar proyecto / منصوبہ اپڈیٹ کریں' }}
                    </button>
                </form>
            </section>
        </main>
    </div>
@endsection
