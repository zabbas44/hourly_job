@extends('layouts.app', ['title' => 'Login'])

@section('body')
    <div class="auth-shell">
        <div class="auth-card">
            <div>
                <h1>Learning System</h1>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="stack-lg">
                @csrf

                @if ($errors->any())
                    <div class="alert alert-error">{{ $errors->first() }}</div>
                @endif

                <label class="field">
                    <span>Username / Usuario / صارف نام</span>
                    <input type="text" name="username" value="{{ old('username', $username) }}" required>
                </label>

                <label class="field">
                    <span>Password / Contraseña / پاس ورڈ</span>
                    <input type="password" name="password" required>
                </label>

                <label class="field">
                    <span>Access key / Clave de acceso / رسائی کلید</span>
                    <input type="password" name="access_key" required>
                </label>

                <button type="submit" class="button button-primary button-block">Login / Iniciar sesión / لاگ اِن</button>
            </form>
        </div>
    </div>
@endsection
