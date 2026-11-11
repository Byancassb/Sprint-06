<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se o usuário NÃO está autenticado
        if (!Auth::check()) {
            // Se for uma requisição via API/JSON
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Não autorizado.'], 401);
            }

            // Se for uma requisição web comum, redireciona para o login
            return redirect()->route('login.create')->with('error', 'Você precisa estar logado para acessar esta página.');
        }

        // Permite que a requisição prossiga
        return $next($request);
    }
}