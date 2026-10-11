<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class ClientAccessController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('client-access.index', [
            'clients' => User::query()->where('role', 'client')->withCount('organizations')->get(),
        ]);
    }

    public function invite(Request $request, User $user): View
    {
        abort_unless($request->user()->isAdmin() && ! $user->isAdmin(), 403);

        $user->forceFill([
            'activation_token_hash' => null,
            'activation_expires_at' => now()->addDays(7),
        ])->save();

        $activationUrl = URL::temporarySignedRoute(
            'client.activate',
            $user->activation_expires_at,
            ['user' => $user]
        );

        return view('client-access.invite', [
            'client' => $user,
            'activationUrl' => $activationUrl,
        ]);
    }

    public function activate(User $user): View
    {
        abort_unless($this->activationIsAvailable($user), 404);

        return view('client-access.activate', compact('user'));
    }

    public function setPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($this->activationIsAvailable($user), 404);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'activation_token_hash' => null,
            'activation_expires_at' => null,
        ])->save();

        return redirect()->route('login')->with('success', '密碼設定完成，現在可以使用帳號登入。');
    }

    private function activationIsAvailable(User $user): bool
    {
        return $user->role === 'client'
            && $user->activation_expires_at?->isFuture();
    }
}
