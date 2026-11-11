@extends('layouts.app')

@section('title', $evento->titulo . ' — FalaQ')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-12 gap-6">

    <!-- Formulário de envio de Pergunta (TICKET #007 e #008) -->
    <div class="md:col-span-5">
        <div class="bg-gray-900 border border-gray-700 rounded-xl shadow-md p-6">
            <h4 class="text-xl font-bold mb-4">💬 Faça sua Pergunta</h4>

            <form action="{{ route('eventos.perguntas.store', $evento->id) }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label for="texto" class="block text-sm text-gray-400 mb-1">Texto da Pergunta</label>

                    <textarea name="texto" id="texto" rows="4"
                              placeholder="Digite sua dúvida ou comentário para o palestrante..."
                              class="w-full rounded-md bg-gray-800 text-white p-3 border focus:outline-none focus:ring-2 focus:ring-blue-600 @error('texto') border-red-500 @else border-gray-600 @enderror">{{ old('texto') }}</textarea>

                    @error('texto')
                        <p class="text-red-500 text-sm font-semibold mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full bg-blue-600 text-white font-bold px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                    Enviar Pergunta
                </button>
            </form>
        </div>
    </div>

    <!-- Mural de Perguntas -->
    <div class="md:col-span-7">
        <div class="flex justify-between items-center mb-4">
            <h4 class="text-xl font-bold">📋 Perguntas do Evento</h4>
            <span class="text-gray-400 text-sm">Total de perguntas: {{ $perguntas->total() }}</span>
        </div>

        @forelse($perguntas as $pergunta)
            {{-- Card em formato de balão de chat --}}
            <div class="mb-4 bg-gray-800 border border-gray-700 rounded-2xl rounded-tl-none shadow-md p-5">
                <p class="text-sm text-blue-400 font-semibold mb-2">
                    👤 {{ $pergunta->user->name ?? 'Anônimo' }}
                </p>
                <p class="text-lg text-white leading-relaxed mb-3">{{ $pergunta->texto }}</p>
                <div class="flex justify-between items-center text-xs text-gray-400">
                    <span class="bg-green-600/20 text-green-400 px-2 py-0.5 rounded-full">{{ $pergunta->status }}</span>
                    <span>{{ $pergunta->created_at->format('d/m/Y H:i') }}</span>
                </div>

                {{-- TICKET #012: upvote (toggle) --}}
                @php($jaVotou = in_array($pergunta->id, $votadas))
                <form action="{{ route('perguntas.votar', $pergunta) }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit"
                            title="{{ $jaVotou ? 'Clique para remover seu voto' : 'Clique para votar' }}"
                            class="inline-flex items-center gap-2 px-3 py-1 rounded-md text-sm font-semibold border transition-colors
                                   {{ $jaVotou
                                        ? 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700'
                                        : 'bg-transparent text-gray-300 border-gray-600 hover:bg-gray-700' }}">
                        👍 {{ $jaVotou ? 'Votado' : 'Upvote' }}
                        <span class="bg-black/30 rounded-full px-2">{{ $pergunta->votos_count }}</span>
                    </button>
                </form>
            </div>
        @empty
            <div class="bg-gray-800 text-gray-300 text-center rounded-xl p-6">
                Nenhuma pergunta enviada ainda. Seja o primeiro!
            </div>
        @endforelse

        <!-- Botões de Paginação -->
        @if(method_exists($perguntas, 'links'))
            <div class="flex justify-center mt-6">
                {{ $perguntas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
