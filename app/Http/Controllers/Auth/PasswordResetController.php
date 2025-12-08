<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    /**
     * Exibir formulário de criação de senha (para usuários sincronizados do Bling)
     */
    public function showResetForm()
    {
        $user = Auth::user();
        
        if (!$user || !$user->must_reset_password) {
            return redirect('/minha-conta');
        }
        
        return view('auth.force-password-reset', ['user' => $user]);
    }
    
    /**
     * Processar a criação da nova senha
     */
    public function resetPassword(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !$user->must_reset_password) {
            return response()->json([
                'success' => false,
                'message' => 'Você não precisa redefinir sua senha.',
            ], 400);
        }
        
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        
        $user->update([
            'password' => Hash::make($request->password),
            'must_reset_password' => false,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Senha criada com sucesso!',
            'redirect_url' => '/minha-conta',
        ]);
    }
    
    /**
     * Verificar status de reset de senha (para chamadas AJAX)
     */
    public function checkStatus()
    {
        $user = Auth::user();
        
        return response()->json([
            'must_reset_password' => $user ? $user->must_reset_password : false,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'synced_from_bling' => $user->synced_from_bling,
            ] : null,
        ]);
    }
}
