@extends('layouts.app', ['title' => 'Dashboard'])

@section('body')
    <div class="app-shell">
        <x-sidebar />

        <main class="content">
            <x-flash />

            <section class="hero-card">
                <p class="eyebrow">Overview / Resumen / جائزہ</p>
                <h1>Manage workers and daily hours from one place.</h1>
                <p class="muted">Use the workers section to maintain staff records and assign 1 to 16 hours per day.</p>
            </section>

            <section class="stats-grid">
                <article class="stat-card">
                    <span class="stat-icon" aria-hidden="true">&#128101;</span>
                    <span>Total workers / Total trabajadores / کل کارکن</span>
                    <strong>{{ $workerCount }}</strong>
                </article>
                <article class="stat-card">
                    <span class="stat-icon" aria-hidden="true">&#128221;</span>
                    <span>Entries today / Entradas hoy / آج کے اندراجات</span>
                    <strong>{{ $entriesToday }}</strong>
                </article>
                <article class="stat-card">
                    <span class="stat-icon" aria-hidden="true">&#9716;</span>
                    <span>Hours today / Horas hoy / آج کے گھنٹے</span>
                    <strong>{{ $hoursToday }}</strong>
                </article>
            </section>

            <section class="panel stack-lg">
                <div data-dashboard-content>
                    @include('dashboard.partials.chart-panel')
                </div>
            </section>
        </main>
    </div>
@endsection
