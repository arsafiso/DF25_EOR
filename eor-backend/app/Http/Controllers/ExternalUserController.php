<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExternalUserController extends Controller
{
    /**
     * Busca todos os usuários da API externa.
     */
    public function index()
    {
        try {
            $cacheKey = 'external_users';
            $cacheDuration = 60; // Cache por 60 minutos

            // Verifica se os dados estão no cache
            $users = Cache::remember($cacheKey, $cacheDuration, function () {
                $apiUrl = env('EXTERNAL_API_URL') . '/users';
                $response = Http::get($apiUrl);

                if ($response->successful()) {
                    return $response->json();
                }

                throw new \Exception('Erro ao buscar usuários da API externa.');
            });

            return response()->json([
                'message' => 'Usuários buscados com sucesso!',
                'data' => $users,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao conectar com a API externa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login federado usando o token do sistema principal.
     */
    public function federatedLogin(Request $request)
    {
        $validated = $request->validate([
            'external_token' => 'required|string',
        ]);
        $apiUrl = env('EXTERNAL_API_URL') . '/validate-token';

        // Valida o token no sistema principal
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $validated['external_token'],
        ])->get($apiUrl);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Token inválido ou expirado.',
            ], 401);
        }

        // Obtém os dados do usuário do sistema principal
        $externalUserData = $response->json();

        // Verifica se o usuário já existe no nosso sistema
        $user = User::firstOrCreate(
            ['email' => $externalUserData['email']],
            [
                'name' => $externalUserData['name'],
                'password' => bcrypt('senha-padrao'), // Senha padrão (não será usada)
            ]
        );

        // Gera um token para o usuário no nosso sistema
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login realizado com sucesso!',
            'token' => $token,
            'user' => $user,
        ], 200);
    }

    /**
     * Login do usuário.
     */
    public function login(Request $request)
    {
        if (empty($request->all())) {
            return response()->json([
                'message' => 'Usuário não autenticado. Forneça o token de autenticação.',
                'error' => 'Request vazio',
            ], 401);
        }

        # se tem external_token, chama o federatedLogin
        if ($request->has('external_token')) {
            return $this->federatedLogin($request);
        }

        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !password_verify($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
            ], 401);
        }

        // Gera um token para o usuário
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login realizado com sucesso!',
            'token' => $token,
            'user' => $user,
        ], 200);
    }

    public function getCurrentAccount()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Usuário não autenticado.',
            ], 401);
        }

        return response()->json([
            'data' => $user,
        ], 200);
    }

    /**
     * Logout do usuário.
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // Revoga o token do usuário
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso!',
        ], 200);
    }

    /**
     * Sincroniza os usuários externos com o banco de dados local.
     */
    public function sincronizarUsuariosExternos()
    {
        $apiUrl = env('EXTERNAL_API_URL') . '/users';
        // Faz uma requisição ao sistema externo para buscar os usuários
        $response = Http::get($apiUrl);

        if ($response->failed()) {
            throw new \Exception('Erro ao sincronizar usuários externos.');
        }

        $usuariosExternos = $response->json();

        // Itera sobre os usuários externos e sincroniza com o banco local
        foreach ($usuariosExternos as $usuarioExterno) {
            User::updateOrCreate(
                ['id' => $usuarioExterno['id']], // ID do sistema externo
                [
                    'name' => $usuarioExterno['name'],
                    'email' => $usuarioExterno['email'],
                ]
            );
        }
    }

    /**
     * Registra o usuário no sistema manualmente.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        return response()->json([
            'message' => 'Usuário registrado com sucesso!',
            'user' => $user,
        ], 201);
    }

}
