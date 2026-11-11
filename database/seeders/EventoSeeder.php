<?php

namespace Database\Seeders;

use App\Models\Evento;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $eventoPrincipal = Evento::create([
            'titulo'      => 'Palestra Principal: O Futuro da Computação em Nuvem',
            'descricao'   => 'Evento corporativo de tecnologia com 500 participantes simultâneos.',
            'data_evento' => Carbon::now(),
        ]);

        for ($i = 0; $i < 100; $i++) {
        Evento::create([
            'titulo'      => 'Teste: ' . $i,
            'descricao'   => 'Minicurso prático sobre arquitetura MVC e Eloquent.',
            'data_evento' => Carbon::now()->addDays(1),
        ]);
    }
    }
}
