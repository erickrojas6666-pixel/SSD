<?php

namespace Database\Seeders;

use App\Models\Cuatrimestre;
use Illuminate\Database\Seeder;

class CuatrimestreSeeder extends Seeder
{
    public function run(): void
    {
        $nombres = [
            1 => 'Primer cuatrimestre', 2 => 'Segundo cuatrimestre', 3 => 'Tercer cuatrimestre',
            4 => 'Cuarto cuatrimestre', 5 => 'Quinto cuatrimestre', 6 => 'Sexto cuatrimestre',
            7 => 'Séptimo cuatrimestre', 8 => 'Octavo cuatrimestre', 9 => 'Noveno cuatrimestre',
            10 => 'Décimo cuatrimestre', 11 => 'Undécimo cuatrimestre', 12 => 'Duodécimo cuatrimestre',
        ];

        foreach ($nombres as $numero => $nombre) {
            Cuatrimestre::create(['numero' => $numero, 'nombre' => $nombre]);
        }
    }
}
