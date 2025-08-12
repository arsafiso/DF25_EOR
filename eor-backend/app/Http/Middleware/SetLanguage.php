<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Supondo que você obtenha a linguagem do usuário autenticado
        $locale = 'pt';
        // auth()->check() ? auth()->user()->profile->lang : 'en'; 

        // Definir o locale da aplicação
        App::setLocale($locale);

        return $next($request);
    }
}
