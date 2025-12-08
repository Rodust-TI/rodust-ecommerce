<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Buscar usuário por google_id OU email
            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();
            
            if ($user) {
                // Usuário existe: vincular Google ID se não tiver
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar,
                        'email_verified_at' => now(), // Email confirmado pelo Google
                        'must_reset_password' => false, // Não precisa mais resetar
                    ]);
                }
            } else {
                // Criar novo usuário
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'email_verified_at' => now(),
                    'password' => null, // Sem senha, só Google
                ]);
            }
            
            // Fazer login no Laravel
            Auth::login($user, true);
            
            // Gerar token temporário para WordPress fazer login (válido por 5 minutos)
            $token = base64_encode(json_encode([
                'user_id' => $user->id,
                'expires_at' => now()->addMinutes(5)->timestamp,
                'signature' => hash_hmac('sha256', $user->id . $user->email, config('app.key'))
            ]));
            
            // Redirecionar para WordPress com token
            return redirect('https://localhost:8443/minha-conta?google_login=' . $token);
            
        } catch (\Exception $e) {
            Log::error('Google Auth Error: ' . $e->getMessage());
            return redirect('https://localhost:8443/login?error=google_auth_failed&message=' . urlencode($e->getMessage()));
        }
    }
    
    /**
     * Logout (desvincula Google também)
     */
    public function logout()
    {
        Auth::logout();
        return redirect('https://localhost:8443?logout=success');
    }
    
    /**
     * Validar token do Google OAuth e retornar dados do usuário
     * Usado pelo WordPress para fazer login após OAuth
     */
    public function validateToken(Request $request)
    {
        try {
            $token = $request->input('token');
            
            if (!$token) {
                return response()->json(['error' => 'Token não fornecido'], 400);
            }
            
            // Decodificar token
            $data = json_decode(base64_decode($token), true);
            
            if (!$data || !isset($data['user_id']) || !isset($data['expires_at']) || !isset($data['signature'])) {
                return response()->json(['error' => 'Token inválido'], 401);
            }
            
            // Verificar expiração
            if ($data['expires_at'] < time()) {
                return response()->json(['error' => 'Token expirado'], 401);
            }
            
            // Buscar usuário
            $user = User::find($data['user_id']);
            
            if (!$user) {
                return response()->json(['error' => 'Usuário não encontrado'], 404);
            }
            
            // Verificar assinatura
            $expectedSignature = hash_hmac('sha256', $user->id . $user->email, config('app.key'));
            
            if (!hash_equals($expectedSignature, $data['signature'])) {
                return response()->json(['error' => 'Assinatura inválida'], 401);
            }
            
            // Retornar dados do usuário
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->google_id,
                    'avatar' => $user->avatar,
                    'email_verified_at' => $user->email_verified_at,
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Token validation error: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao validar token'], 500);
        }
    }
}
