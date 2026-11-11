<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventoFormRequest;
use App\Models\Evento;
use App\Models\Pergunta;
use App\Http\Requests\StorePerguntaRequest;
use Illuminate\Support\Facades\Auth;

class EventoController extends Controller
{
    public function index()
    {
        $eventos = Evento::all();
        return view('eventos.index', compact('eventos'));
    }

    public function show($id)
    {
        $evento = Evento::findOrFail($id);

        // TICKET #002/#004/#006: filtra pelo evento, só perguntas aprovadas
        // (is_public), carrega o autor de uma vez (eager loading, sem N+1),
        // mais recentes primeiro, 10 por página
        $perguntas = Pergunta::where('evento_id', $evento->id)
            ->where('is_public', true)
            ->with('user')
            ->withCount('votos')          // TICKET #012: votos_count
            ->orderByDesc('votos_count')  // mais votadas primeiro
            ->latest()                    // desempate: mais recentes primeiro
            ->paginate(10);

        // Ids das perguntas desta página em que o usuário logado já votou
        // (1 única query, sem N+1)
        $votadas = Auth::check()
            ? Auth::user()->perguntasVotadas()
                ->whereIn('perguntas.id', $perguntas->pluck('id'))
                ->pluck('perguntas.id')
                ->all()
            : [];

        return view('eventos.show', compact('evento', 'perguntas', 'votadas'));
    }

 
    public function storePergunta(StorePerguntaRequest $request, $id)
    {
        $evento = Evento::findOrFail($id);

        Pergunta::create([
            'evento_id' => $evento->id,
            'user_id' => Auth::user()->id,
            'texto'     => $request->input('texto'),
            'status'    => 'pendente',
            'is_public' => false, // aguarda moderação
        ]);

        return redirect()->route('eventos.show', $evento->id)
            ->with('sucesso', 'Sua pergunta foi enviada e aparecerá no mural após a aprovação do organizador.');
    }

    public function create(){
        return view('eventos.create');
    }

    public function store(EventoFormRequest $request){
        $evento = $request->user()->eventos()->create($request->validated());
        return redirect()->route('eventos.show', $evento->id);
    }
}
