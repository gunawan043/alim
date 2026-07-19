<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SecureAccessToken;
use App\Models\User;
use App\Support\AbilityRegistry;
use App\Support\TokenExpiration;
use App\Support\TokenName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TokenSesiController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $tab = $request->tab ?? 'sessions';

        if ($tab === 'sessions') {
            // Active sessions dari database (personal access tokens via Sanctum)
            $tokens = DB::table('personal_access_tokens')
                ->join('users', 'personal_access_tokens.tokenable_id', '=', 'users.id')
                ->where('personal_access_tokens.tokenable_type', User::class)
                ->whereDate('personal_access_tokens.expires_at', '>', now())
                ->orWhereNull('personal_access_tokens.expires_at')
                ->select(
                    'personal_access_tokens.*',
                    'users.name as user_name',
                    'users.email as user_email'
                )
                ->orderBy('personal_access_tokens.last_used_at', 'desc')
                ->paginate(20, ['*'], 'page');

            return view('super-admin.tokens.index', [
                'tab' => $tab,
                'tokens' => $tokens,
                'secureTokens' => null,
                'users' => null,
                'userId' => $userId,
            ]);
        }

        if ($tab === 'secure-tokens') {
            $secureTokens = SecureAccessToken::with('user')
                ->where('expires_at', '>', now())
                ->orWhereNull('expires_at')
                ->orderBy('created_at', 'desc')
                ->paginate(20, ['*'], 'page');

            return view('super-admin.tokens.index', [
                'tab' => $tab,
                'tokens' => null,
                'secureTokens' => $secureTokens,
                'users' => null,
                'userId' => $userId,
            ]);
        }

        return view('super-admin.tokens.index', [
            'tab' => $tab,
            'tokens' => null,
            'secureTokens' => null,
            'users' => null,
            'userId' => $userId,
        ]);
    }

    public function createToken(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'expires_at' => 'nullable|date|after:now',
            'note' => 'nullable|string|max:255',
        ]);

        $tokenable = User::findOrFail($validated['user_id']);

        $expiresAt = $validated['expires_at'] ?? TokenExpiration::mobileDefaultExpiresAt();

        $tokenName = TokenName::admin(
            clientKind: 'super-admin',
            channel: TokenName::CHANNEL_PASSWORD,
            platform: TokenName::platformFromRequest($request),
            deviceFp: 'fp_admin_'.substr(hash('sha256', (string) $request->ip()), 0, 12),
        );

        $abilities = AbilityRegistry::forRoles($tokenable->effectiveRoles());

        $new = $tokenable->createToken($tokenName, $abilities, $expiresAt);

        SecureAccessToken::create([
            'user_id' => $validated['user_id'],
            'token' => hash('sha256', $new->plainTextToken),
            'name' => $validated['note'] ?? 'Access Token',
            'expires_at' => $expiresAt,
        ]);

        return back()->with('token', $new->plainTextToken)
            ->with('success', 'Token berhasil dibuat.');
    }

    public function revokeToken(string $id)
    {
        $token = DB::table('personal_access_tokens')->where('id', $id)->first();

        if (! $token) {
            return back()->with('error', 'Token tidak ditemukan.');
        }

        DB::table('personal_access_tokens')->where('id', $id)->delete();

        return back()->with('success', 'Token berhasil dicabut.');
    }

    public function revokeSecureToken(string $id)
    {
        $token = SecureAccessToken::findOrFail($id);
        $token->delete();

        return back()->with('success', 'Secure token berhasil dicabut.');
    }
}
