<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Cognito\CognitoHosted;
use App\Services\Cognito\CognitoSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class AuthSessionController extends Controller
{
    public function __construct(
        private readonly CognitoHosted $cognito,
        private readonly CognitoSession $cognitoSession,
    ) {}

    public function showLogin(): View|RedirectResponse
    {
        if (! $this->cognito->isConfigured()) {
            return view('app.login', ['cognitoConfigured' => false]);
        }

        return redirect()->away($this->cognito->loginUrl());
    }

    public function showRegister(Request $request): View|RedirectResponse
    {
        if (! $this->cognito->isConfigured()) {
            return view('app.register', ['cognitoConfigured' => false]);
        }

        $state = $request->query('invite_code');

        return redirect()->away($this->cognito->signupUrl(is_string($state) ? $state : null));
    }

    public function redirect(Request $request): RedirectResponse|View
    {
        $oauthError = $request->query('error_description') ?? $request->query('error');
        if (is_string($oauthError) && $oauthError !== '') {
            return view('app.auth-redirect', ['error' => $oauthError]);
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return view('app.auth-redirect', ['error' => 'No authorization code found in URL.']);
        }

        try {
            $this->cognitoSession->completeTokenExchange($request, $code);
        } catch (RuntimeException $exception) {
            return view('app.auth-redirect', ['error' => $exception->getMessage()]);
        }

        return redirect()->intended(route('app.home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($this->cognito->isConfigured()) {
            return redirect()->away($this->cognito->logoutUrl());
        }

        return redirect()->route('app.home');
    }
}
