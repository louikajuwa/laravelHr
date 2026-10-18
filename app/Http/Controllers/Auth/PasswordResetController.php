<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    private const GENERIC_STATUS = 'If an account exists for that email, a reset link has been sent.';

    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $data['email'])->where('is_active', true)->first();
        if ($user) {
            // Broker status (sent / throttled) is deliberately ignored to avoid account enumeration.
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with('status', self::GENERIC_STATUS);
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            ['email' => $data['email'], 'password' => $data['password'], 'password_confirmation' => $request->input('password_confirmation'), 'token' => $data['token']],
            function (User $user, string $password) {
                if (! $user->is_active) {
                    throw ValidationException::withMessages(['email' => 'This password reset link is invalid or has expired.']);
                }

                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

                event(new PasswordReset($user));

                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
                }
            }
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => 'This password reset link is invalid or has expired.']);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset. You can now sign in.');
    }
}
