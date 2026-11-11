<?php

namespace App\Http\Controllers;

use App\Models\Pergunta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PerguntaController extends Controller
{
    /**
     * Alterna o upvote: vota se ainda não votou, remove o voto se já votou.
     */
    public function votar(Pergunta $pergunta): RedirectResponse
    {
        $resultado = $pergunta->votos()->toggle(Auth::id());

        $mensagem = count($resultado['attached']) > 0
            ? 'Voto registrado!'
            : 'Voto removido.';

        return back()->with('sucesso', $mensagem);
    }
}
