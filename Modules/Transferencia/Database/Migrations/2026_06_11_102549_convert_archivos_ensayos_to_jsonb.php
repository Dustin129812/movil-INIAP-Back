<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Forzamos el cast a ::jsonb en el THEN para que coincida con el ELSE
        DB::statement("
            ALTER TABLE transferencia.ensayos
            ALTER COLUMN archivo_protocolo_path TYPE jsonb
            USING CASE
                WHEN archivo_protocolo_path IS NOT NULL AND archivo_protocolo_path != ''
                THEN json_build_array(archivo_protocolo_path)::jsonb
                ELSE '[]'::jsonb
            END;
        ");

        DB::statement("
            ALTER TABLE transferencia.ensayos
            ALTER COLUMN archivo_informe_path TYPE jsonb
            USING CASE
                WHEN archivo_informe_path IS NOT NULL AND archivo_informe_path != ''
                THEN json_build_array(archivo_informe_path)::jsonb
                ELSE '[]'::jsonb
            END;
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE transferencia.ensayos
            ALTER COLUMN archivo_protocolo_path TYPE character varying
            USING archivo_protocolo_path->>0;
        ");

        DB::statement("
            ALTER TABLE transferencia.ensayos
            ALTER COLUMN archivo_informe_path TYPE character varying
            USING archivo_informe_path->>0;
        ");
    }
};
