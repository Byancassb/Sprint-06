<?php

use App\Models\Evento;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->evento = Evento::create(['titulo' => 'Evento A']);
    $this->pergunta = Pergunta::create([
        'evento_id' => $this->evento->id,
        'texto'     => 'Pergunta de teste para upvote',
        'is_public' => true,
    ]);
});

// Ticket #009
it('cria a tabela pivot pergunta_user', function () {
    expect(Schema::hasTable('pergunta_user'))->toBeTrue()
        ->and(Schema::hasColumns('pergunta_user', ['pergunta_id', 'user_id', 'created_at', 'updated_at']))->toBeTrue();
});

it('impede voto duplicado do mesmo usuário na mesma pergunta (unique composta)', function () {
    $this->pergunta->votos()->attach($this->user->id);

    expect(fn () => $this->pergunta->votos()->attach($this->user->id))
        ->toThrow(QueryException::class);
});

// Ticket #010
it('define os relacionamentos N:M com retorno BelongsToMany', function () {
    expect($this->pergunta->votos())->toBeInstanceOf(BelongsToMany::class)
        ->and($this->user->perguntasVotadas())->toBeInstanceOf(BelongsToMany::class);

    $this->pergunta->votos()->attach($this->user->id);

    expect($this->pergunta->votos->first()->is($this->user))->toBeTrue()
        ->and($this->user->perguntasVotadas->first()->is($this->pergunta))->toBeTrue();
});

// Ticket #011
it('bloqueia usuário anônimo (302 para o login) e não grava voto', function () {
    $this->post(route('perguntas.votar', $this->pergunta))
        ->assertRedirect(route('login.create'));

    expect($this->pergunta->votos()->count())->toBe(0);
});

it('retorna 401 para anônimo via JSON', function () {
    $this->postJson(route('perguntas.votar', $this->pergunta))->assertStatus(401);
});

it('insere o voto na pivot quando o usuário ainda não votou', function () {
    $this->actingAs($this->user)
        ->from(route('eventos.show', $this->evento->id))
        ->post(route('perguntas.votar', $this->pergunta))
        ->assertRedirect(route('eventos.show', $this->evento->id))
        ->assertSessionHas('sucesso', 'Voto registrado!');

    $this->assertDatabaseHas('pergunta_user', [
        'pergunta_id' => $this->pergunta->id,
        'user_id'     => $this->user->id,
    ]);
});

it('remove o voto da pivot quando o usuário já votou (toggle)', function () {
    $this->pergunta->votos()->attach($this->user->id);

    $this->actingAs($this->user)
        ->post(route('perguntas.votar', $this->pergunta))
        ->assertSessionHas('sucesso', 'Voto removido.');

    $this->assertDatabaseMissing('pergunta_user', [
        'pergunta_id' => $this->pergunta->id,
        'user_id'     => $this->user->id,
    ]);
});

it('retorna 404 para pergunta inexistente', function () {
    $this->actingAs($this->user)->post('/perguntas/999999/votar')->assertNotFound();
});

// Ticket #012
it('ordena as perguntas por votos_count decrescente', function () {
    $poucos = Pergunta::create(['evento_id' => $this->evento->id, 'texto' => 'Pergunta com um voto só', 'is_public' => true]);
    $muitos = Pergunta::create(['evento_id' => $this->evento->id, 'texto' => 'Pergunta campeã de votos', 'is_public' => true]);

    $poucos->votos()->attach(User::factory()->create()->id);
    foreach (User::factory()->count(3)->create() as $u) {
        $muitos->votos()->attach($u->id);
    }

    $perguntas = $this->get(route('eventos.show', $this->evento->id))
        ->assertOk()
        ->viewData('perguntas');

    expect($perguntas->pluck('id')->all())->toBe([$muitos->id, $poucos->id, $this->pergunta->id])
        ->and($perguntas->first()->votos_count)->toBe(3);
});

it('exibe a contagem de votos e destaca o botão de quem já votou', function () {
    $this->pergunta->votos()->attach($this->user->id);

    $this->actingAs($this->user)
        ->get(route('eventos.show', $this->evento->id))
        ->assertOk()
        ->assertSee(route('perguntas.votar', $this->pergunta), false)
        ->assertSee('Clique para remover seu voto')
        ->assertSee('Votado');
});

it('não destaca o botão para quem ainda não votou', function () {
    $this->get(route('eventos.show', $this->evento->id))
        ->assertOk()
        ->assertSee('Clique para votar')
        ->assertDontSee('Clique para remover seu voto');
});

it('mantém a paginação de 10 em 10 com o ranking', function () {
    foreach (range(1, 14) as $i) {
        Pergunta::create(['evento_id' => $this->evento->id, 'texto' => "Pergunta extra número {$i}", 'is_public' => true]);
    }

    $perguntas = $this->get(route('eventos.show', $this->evento->id))->viewData('perguntas');

    expect($perguntas->total())->toBe(15)->and($perguntas->count())->toBe(10);
});
