<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        $token = Str::random(64);
        $user->forceFill([
            'activation_token_hash' => hash('sha256', $token),
            'activation_expires_at' => now()->addDays(7),
        ])->save();

        return view('client-access.invite', [
            'client' => $user,
            'activationUrl' => route('client.activate', ['user' => $user, 'token' => $token]),
        ]);
    }

    public function activate(User $user, string $token): View
    {
        abort_unless($this->validToken($user, $token), 404);

        return view('client-access.activate', compact('user', 'token'));
    }

    public function setPassword(Request $request, User $user, string $token): RedirectResponse
    {
        abort_unless($this->validToken($user, $token), 404);

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

    private function validToken(User $user, string $token): bool
    {
        return filled($user->activation_token_hash)
            && $user->activation_expires_at?->isFuture()
            && hash_equals($user->activation_token_hash, hash('sha256', $token));
    }
}
