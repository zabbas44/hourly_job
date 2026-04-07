@extends('layouts.app', ['title' => 'Backups'])

@section('body')
    <div class="app-shell">
        <x-sidebar />

        <main class="content">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Backup database / Respaldo BD / بیک اپ</p>
                    <h1>Backup and restore center</h1>
                </div>
                <form method="POST" action="{{ route('backups.store') }}">
                    @csrf
                    <button type="submit" class="button button-primary">Create backup / Crear respaldo / بیک اپ بنائیں</button>
                </form>
            </div>

            <x-flash />

            <section class="panel stack-lg">
                <div class="backup-toolbar">
                    <article class="stat-card">
                        <span>Automatic backup / Respaldo automático / خودکار بیک اپ</span>
                        <strong>Daily 01:00</strong>
                    </article>
                    <article class="stat-card">
                        <span>Workers export / Exportar trabajadores / کارکن ایکسپورٹ</span>
                        <a href="{{ route('backups.export.workers') }}" class="button button-secondary">Export CSV</a>
                    </article>
                    <article class="stat-card">
                        <span>Workers import / Importar trabajadores / کارکن امپورٹ</span>
                        <form method="POST" action="{{ route('backups.import.workers') }}" enctype="multipart/form-data" class="stack-md">
                            @csrf
                            <input type="file" name="workers_csv" accept=".csv,text/csv" required>
                            <button type="submit" class="button button-secondary">Import CSV</button>
                        </form>
                    </article>
                </div>

                @if ($backups === [])
                    <p class="muted">No backups available yet / No hay respaldos todavía / ابھی تک کوئی بیک اپ موجود نہیں۔</p>
                @else
                    <div class="backup-list">
                        @foreach ($backups as $backup)
                            <article class="backup-card">
                                <div class="backup-card-header">
                                    <div>
                                        <h2>{{ $backup['name'] }}</h2>
                                        <p class="muted">{{ $backup['modified_at'] }} · {{ number_format($backup['size'] / 1024, 1) }} KB</p>
                                    </div>
                                    <a href="{{ route('backups.download', $backup['name']) }}" class="button button-secondary">Download</a>
                                </div>

                                <form method="POST" action="{{ route('backups.restore') }}" class="form-grid">
                                    @csrf
                                    <input type="hidden" name="backup" value="{{ $backup['name'] }}">

                                    <label class="field">
                                        <span>Restore scope / Alcance / دائرہ</span>
                                        <select name="scope" required>
                                            <option value="all">Full system / Sistema completo / مکمل سسٹم</option>
                                            <option value="workers">Workers / Trabajadores / کارکن</option>
                                            <option value="projects">Projects / Proyectos / منصوبے</option>
                                            <option value="time_entries">Time logs / Registros / وقت لاگز</option>
                                            <option value="payments">Payments / Pagos / ادائیگیاں</option>
                                        </select>
                                    </label>

                                    <label class="field">
                                        <span>Specific worker / Trabajador / مخصوص کارکن</span>
                                        <select name="worker_id">
                                            <option value="">All workers</option>
                                            @foreach ($workers as $worker)
                                                <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                                            @endforeach
                                        </select>
                                    </label>

                                    <label class="field">
                                        <span>Specific project / Proyecto / مخصوص منصوبہ</span>
                                        <select name="project_id">
                                            <option value="">All projects</option>
                                            @foreach ($projects as $project)
                                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                                            @endforeach
                                        </select>
                                    </label>

                                    <label class="field">
                                        <span>Specific day / Día / مخصوص دن</span>
                                        <input type="date" name="work_date">
                                    </label>

                                    <button type="submit" class="button button-danger" onclick="return confirm('Restore this backup data?')">Restore / Restaurar / بحال کریں</button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>
    </div>
@endsection
