@extends('layouts.app', ['title' => 'Projects'])

@section('body')
    <div class="app-shell">
        <x-sidebar />

        <main class="content">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Projects / Proyectos / منصوبے</p>
                    <h1>Manage projects</h1>
                </div>
                <a href="{{ route('projects.create') }}" class="button button-primary">Add project / Agregar proyecto / منصوبہ شامل کریں</a>
            </div>

            <x-flash />

            <section class="panel">
                @if ($projects->isEmpty())
                    <p class="muted">No projects added yet / Aún no hay proyectos / ابھی تک کوئی منصوبہ شامل نہیں کیا گیا۔</p>
                @else
                    <div class="project-mobile-list">
                        @foreach ($projects as $project)
                            <details class="project-mobile-card">
                                <summary class="project-mobile-summary">
                                    <div class="project-mobile-heading">
                                        <strong>{{ $project->name }}</strong>
                                        <span>{{ $project->workers->count() }} workers</span>
                                    </div>
                                    <span class="project-mobile-arrow" aria-hidden="true">&#x203A;</span>
                                </summary>

                                <div class="project-mobile-body">
                                    <div class="project-mobile-meta">
                                        <p><span>Location / Ubicación / مقام</span>{{ $project->location ?: '—' }}</p>
                                        <p><span>Client name / Nombre del cliente / کلائنٹ کا نام</span>{{ $project->client_name ?: '—' }}</p>
                                        <p><span>Client phone / Teléfono del cliente / کلائنٹ فون</span>{{ $project->client_phone ?: '—' }}</p>
                                        <p><span>Workers / Trabajadores / کارکن</span>{{ $project->workers->pluck('name')->join(', ') ?: '—' }}</p>
                                    </div>

                                    <div class="project-mobile-actions">
                                        <a href="{{ route('projects.edit', $project) }}" class="button button-secondary">Edit</a>
                                        <form method="POST" action="{{ route('projects.destroy', $project) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="button button-danger" onclick="return confirm('Remove this project?')">Delete</button>
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
                                    <th>Project name / Nombre del proyecto / منصوبے کا نام</th>
                                    <th>Location / Ubicación / مقام</th>
                                    <th>Client name / Nombre del cliente / کلائنٹ کا نام</th>
                                    <th>Client phone / Teléfono del cliente / کلائنٹ فون</th>
                                    <th>Workers / Trabajadores / کارکن</th>
                                    <th>Actions / Acciones / کارروائیاں</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($projects as $project)
                                    <tr>
                                        <td data-label="Project name / Nombre del proyecto / منصوبے کا نام">{{ $project->name }}</td>
                                        <td data-label="Location / Ubicación / مقام">{{ $project->location ?: '—' }}</td>
                                        <td data-label="Client name / Nombre del cliente / کلائنٹ کا نام">{{ $project->client_name ?: '—' }}</td>
                                        <td data-label="Client phone / Teléfono del cliente / کلائنٹ فون">{{ $project->client_phone ?: '—' }}</td>
                                        <td data-label="Workers / Trabajadores / کارکن">{{ $project->workers->pluck('name')->join(', ') ?: '—' }}</td>
                                        <td data-label="Actions / Acciones / کارروائیاں">
                                            <div class="actions">
                                                <a href="{{ route('projects.edit', $project) }}" class="button button-secondary">Edit / Editar / ترمیم</a>
                                                <form method="POST" action="{{ route('projects.destroy', $project) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="button button-danger" onclick="return confirm('Remove this project?')">Delete / Eliminar / حذف کریں</button>
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
