<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'username' => config('scheduler.username'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'access_key' => ['required', 'string'],
        ]);

        $expectedUsername = (string) config('scheduler.username');
        $expectedPassword = (string) config('scheduler.password');
        $expectedAccessKey = (string) config('scheduler.access_key');

        if (
            $credentials['username'] !== $expectedUsername
            || ! hash_equals($expectedPassword, $credentials['password'])
            || ! hash_equals($expectedAccessKey, $credentials['access_key'])
        ) {
            return back()
                ->withErrors(['username' => 'The provided login details are incorrect.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('scheduler_authenticated', true);

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function autoLogin(Request $request, string $token): RedirectResponse
    {
        $expectedToken = (string) config('scheduler.auto_login_token');
        $providedAccessKey = (string) $request->query('key', '');
        $expectedAccessKey = (string) config('scheduler.access_key');

        if (
            $expectedToken === ''
            || ! hash_equals($expectedToken, $token)
            || $providedAccessKey === ''
            || ! hash_equals($expectedAccessKey, $providedAccessKey)
        ) {
            throw new NotFoundHttpException();
        }

        $request->session()->regenerate();
        $request->session()->put('scheduler_authenticated', true);

        return redirect()->route('dashboard');
    }
}
