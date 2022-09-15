<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Status::create([
            'name' => 'creado'
        ]);

        Status::create([
            'name' => 'recolectado'
        ]);

        Status::create([
            'name' => 'en_estacion'
        ]);

        Status::create([
            'name' => 'en_ruta'
        ]);

        Status::create([
            'name' => 'entregado'
        ]);

        Status::create([
            'name' => 'cancelado'
        ]);
    }
}
