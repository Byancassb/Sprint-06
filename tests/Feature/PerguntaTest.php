<?php

use App\Models\Evento;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->evento = Evento::create(['titulo' => 'Evento A']);
});

// Ticket #001
it('rejeita pergunta vazia com 422', function () {
    $this->actingAs($this->user)
        ->postJson("/eventos/{$this->evento->id}/perguntas", ['texto' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors('texto');
});

it('rejeita pergunta de uma letra com 422', function () {
    $this->actingAs($this->user)
        ->postJson("/eventos/{$this->evento->id}/perguntas", ['texto' => 'a'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('texto');
});

it('rejeita pergunta com mais de 255 caracteres', function () {
    $this->actingAs($this->user)
        ->postJson("/eventos/{$this->evento->id}/perguntas", ['texto' => str_repeat('a', 256)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('texto');
});

it('rejeita evento inexistente com 422', function () {
    $this->actingAs($this->user)
        ->postJson('/eventos/999999/perguntas', ['texto' => 'Pergunta válida de teste'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('evento_id');
});

it('aceita pergunta válida', function () {
    $this->actingAs($this->user)
        ->post("/eventos/{$this->evento->id}/perguntas", ['texto' => 'Pergunta válida de teste'])
        ->assertRedirect(route('eventos.show', $this->evento->id));

    expect(Pergunta::where('evento_id', $this->evento->id)->count())->toBe(1);
});

// Ticket #002
it('pagina 10 perguntas por vez, só do evento atual e mais recentes primeiro', function () {
    $outro = Evento::create(['titulo' => 'Evento B']);

    foreach (range(1, 25) as $i) {
        Pergunta::create([
            'evento_id'  => $this->evento->id,
            'is_public' => true,
            'texto'      => "Pergunta número {$i} do evento A",
            'created_at' => now()->subMinutes(100 - $i),
        ]);
    }
    Pergunta::create(['evento_id' => $outro->id, 'texto' => 'Pergunta do evento B']);

    $response = $this->get("/eventos/{$this->evento->id}")->assertOk();

    $perguntas = $response->viewData('perguntas');

    expect($perguntas->perPage())->toBe(10)
        ->and($perguntas->total())->toBe(25)
        ->and($perguntas->count())->toBe(10)
        ->and($perguntas->first()->texto)->toBe('Pergunta número 25 do evento A')
        ->and($perguntas->pluck('evento_id')->unique()->all())->toBe([$this->evento->id]);

    $response->assertSee('pagination', false);
});

// Ticket #003
it('relaciona Pergunta com User nos dois sentidos', function () {
    $pergunta = Pergunta::create([
        'evento_id' => $this->evento->id,
        'is_public' => true,
        'user_id'   => $this->user->id,
        'texto'     => 'Pergunta com autor definido',
    ]);

    expect($pergunta->user->is($this->user))->toBeTrue()
        ->and($this->user->perguntas->first()->is($pergunta))->toBeTrue();
});

it('exibe o nome do autor e Anônimo quando não há usuário', function () {
    Pergunta::create([
        'evento_id' => $this->evento->id,
        'is_public' => true,
        'user_id'   => $this->user->id,
        'texto'     => 'Pergunta com autor definido',
    ]);
    Pergunta::create([
        'evento_id' => $this->evento->id,
        'is_public' => true,
        'texto'     => 'Pergunta sem autor nenhum',
    ]);

    $this->get("/eventos/{$this->evento->id}")
        ->assertOk()
        ->assertSee($this->user->name)
        ->assertSee('Anônimo');
});

// Ticket #004
it('não dispara queries em loop ao listar autores (sem N+1)', function () {
    foreach (range(1, 10) as $i) {
        $autor = User::factory()->create();
        Pergunta::create([
            'evento_id' => $this->evento->id,
            'is_public' => true,
            'user_id'   => $autor->id,
            'texto'     => "Pergunta número {$i} com autor próprio",
        ]);
    }

    DB::enableQueryLog();
    $this->get("/eventos/{$this->evento->id}")->assertOk();
    $queriesAutores = collect(DB::getQueryLog())
        ->filter(fn ($q) => str_contains($q['query'], 'from "users" where "users"."id" in'))
        ->count();
    DB::disableQueryLog();

    // 1 query com whereIn para os 10 autores, e não 10 queries individuais
    expect($queriesAutores)->toBe(1);
});

// Ticket #005
it('redireciona visitante anônimo para o login ao enviar pergunta (302)', function () {
    $this->post("/eventos/{$this->evento->id}/perguntas", ['texto' => 'Pergunta válida de teste'])
        ->assertRedirect(route('login.create'));

    expect(Pergunta::count())->toBe(0);
});

it('retorna 401 para visitante anônimo via JSON', function () {
    $this->postJson("/eventos/{$this->evento->id}/perguntas", ['texto' => 'Pergunta válida de teste'])
        ->assertStatus(401);

    expect(Pergunta::count())->toBe(0);
});

// Ticket #006
it('cria a pergunta enviada como pendente de moderação (is_public false)', function () {
    $this->actingAs($this->user)
        ->post("/eventos/{$this->evento->id}/perguntas", ['texto' => 'Pergunta válida de teste']);

    expect(Pergunta::first()->is_public)->toBeFalse();
});

it('esconde perguntas pendentes e mostra só as aprovadas', function () {
    Pergunta::create(['evento_id' => $this->evento->id, 'texto' => 'Pergunta aprovada visível', 'is_public' => true]);
    Pergunta::create(['evento_id' => $this->evento->id, 'texto' => 'Pergunta pendente escondida', 'is_public' => false]);

    $response = $this->get("/eventos/{$this->evento->id}")->assertOk();

    $response->assertSee('Pergunta aprovada visível')
        ->assertDontSee('Pergunta pendente escondida');

    expect($response->viewData('perguntas')->total())->toBe(1);
});

it('mantém paginação e eager loading com o filtro de moderação', function () {
    foreach (range(1, 12) as $i) {
        Pergunta::create([
            'evento_id' => $this->evento->id,
            'user_id'   => $this->user->id,
            'texto'     => "Pergunta aprovada número {$i}",
            'is_public' => true,
        ]);
    }
    Pergunta::create(['evento_id' => $this->evento->id, 'texto' => 'Pergunta pendente escondida', 'is_public' => false]);

    $perguntas = $this->get("/eventos/{$this->evento->id}")->assertOk()->viewData('perguntas');

    expect($perguntas->total())->toBe(12)
        ->and($perguntas->count())->toBe(10)
        ->and($perguntas->first()->relationLoaded('user'))->toBeTrue();
});

// Ticket #007 e #008
it('volta ao formulário com erro e preserva o texto digitado (old)', function () {
    $url = route('eventos.show', $this->evento->id);

    $this->actingAs($this->user)
        ->from($url)
        ->post(route('eventos.perguntas.store', $this->evento->id), ['texto' => 'curto'])
        ->assertRedirect($url)
        ->assertSessionHasErrors('texto');

    $this->get($url)
        ->assertOk()
        ->assertSee('text-red-500')
        ->assertSee('border-red-500')
        ->assertSee('curto')
        ->assertSee('A pergunta deve ter no mínimo');
});

it('mostra erro ao enviar pergunta em branco', function () {
    $url = route('eventos.show', $this->evento->id);

    $this->actingAs($this->user)
        ->from($url)
        ->post(route('eventos.perguntas.store', $this->evento->id), ['texto' => ''])
        ->assertSessionHasErrors('texto');

    $this->get($url)->assertSee('text-red-500');
});

it('renderiza o botão de envio estilizado com Tailwind', function () {
    $this->get(route('eventos.show', $this->evento->id))
        ->assertSee('bg-blue-600', false)
        ->assertSee('hover:bg-blue-700', false)
        ->assertSee('rounded-md', false);
});
