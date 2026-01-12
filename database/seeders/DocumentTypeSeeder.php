<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('document_types')->insert([
            ['name' => 'Memorando', 'prefix' => 'MEM', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Oficio', 'prefix' => 'OFI', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Circular', 'prefix' => 'CIR', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Informe', 'prefix' => 'INF', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
