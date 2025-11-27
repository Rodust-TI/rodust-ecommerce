<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MelhorEnvioService;
use App\Models\MelhorEnvioSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MelhorEnvioController extends Controller
{
    private MelhorEnvioService $melhorEnvioService;

    public function __construct(MelhorEnvioService $melhorEnvioService)
    {
        $this->melhorEnvioService = $melhorEnvioService;
    }

    /**
     * Initialize OAuth flow
     */
    public function redirectToAuth(Request $request)
    {
        try {
            $state = Str::random(40);
            session(['melhor_envio_state' => $state]);

            $redirectUri = url('/api/melhor-envio/oauth/callback');
            $authUrl = $this->melhorEnvioService->getAuthorizationUrl($redirectUri, $state);

            return response()->json([
                'success' => true,
                'auth_url' => $authUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * OAuth callback
     */
    public function oauthCallback(Request $request)
    {
        $code = $request->input('code');
        $state = $request->input('state');

        // Validate state (disabled for development - session issues with ngrok)
        // if ($state !== session('melhor_envio_state')) {
        //     return response()->json(['error' => 'Invalid state'], 400);
        // }

        try {
            $redirectUri = url('/api/melhor-envio/oauth/callback');
            $this->melhorEnvioService->authenticate($code, $redirectUri);

            return response()->json([
                'success' => true,
                'message' => 'Melhor Envio autenticado com sucesso via OAuth2!',
                'tip' => 'Execute: php artisan melhorenvio:show para ver os tokens'
            ]);
        } catch (\Exception $e) {
            Log::error('Melhor Envio OAuth callback error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erro na autenticação: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate shipping
     */
    public function calculateShipping(Request $request)
    {
        $validated = $request->validate([
            'postal_code' => 'required|string|size:8',
            'products' => 'required|array',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.weight' => 'nullable|numeric',
            'products.*.height' => 'nullable|numeric',
            'products.*.width' => 'nullable|numeric',
            'products.*.length' => 'nullable|numeric',
        ]);

        try {
            $options = $this->melhorEnvioService->calculateShipping(
                $validated['postal_code'],
                $validated['products']
            );

            return response()->json([
                'success' => true,
                'data' => $options,
            ]);
        } catch (\Exception $e) {
            Log::error('Calculate shipping error', [
                'message' => $e->getMessage(),
                'postal_code' => $validated['postal_code'],
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Webhook endpoint - receive updates from Melhor Envio
     */
    public function webhook(Request $request)
    {
        Log::info('Melhor Envio webhook received', $request->all());

        // Process webhook based on event type
        $event = $request->input('event');
        $orderId = $request->input('order_id');

        switch ($event) {
            case 'order.created':
                // Handle order created
                break;
            case 'order.generated':
                // Handle label generated
                break;
            case 'order.posted':
                // Handle order posted
                break;
            case 'order.delivered':
                // Handle order delivered
                break;
            case 'order.canceled':
                // Handle order canceled
                break;
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get current settings
     */
    public function getSettings()
    {
        $settings = MelhorEnvioSetting::getSettings();

        if (!$settings) {
            return response()->json([
                'success' => false,
                'message' => 'Configurações não encontradas',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'sandbox_mode' => $settings->sandbox_mode,
                'origin_postal_code' => $settings->origin_postal_code,
                'is_authenticated' => !empty($settings->access_token),
                'token_expires_at' => $settings->expires_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'sandbox_mode' => 'required|boolean',
            'origin_postal_code' => 'required|string|size:8',
        ]);

        $settings = MelhorEnvioSetting::getSettings();

        if ($settings) {
            $settings->update($validated);
        } else {
            $settings = MelhorEnvioSetting::create($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Configurações atualizadas com sucesso',
            'data' => $settings,
        ]);
    }
}
