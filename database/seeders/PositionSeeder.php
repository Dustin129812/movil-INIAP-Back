<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            ['name' => 'OFICINISTA 2'],
            ['name' => 'DIRECTOR DE ASESORÍA JURÍDICA'],
            ['name' => 'CHOFER'],
            ['name' => 'ANALISTA DE TRANSFERENCIA DE TECNOLOGIA 1'],
            ['name' => 'ASISTENTE DE COMUNICACION SOCIAL'],
            ['name' => 'EXPERTO DE PRODUCCIÓN CIENTÍFICA (DIRECTOR DE INVESTIGACIONES, ENCARGADO)'],
            ['name' => 'ASISTENTE DE TALENTO HUMANO'],
            ['name' => 'ASISTENTE DE TECNOLOGÍAS DE LA INFORMACIÓN Y COMUNICACIÓN'],
            ['name' => 'ANALISTA DE PRESUPUESTO 3 (RESPONSABLE DE PRESUPUESTO, DELEGADO)'],
            ['name' => 'ANALISTA DE CONTRATACIÓN PÚBLICA 3'],
            ['name' => 'ANALISTA DE TALENTO HUMANO 3'],
            ['name' => 'INVESTIGADOR AGROPECUARIO 3'],
            ['name' => 'TÉCNICO DE ARCHIVO 2'],
            ['name' => 'TÉCNICO DE ARCHIVO GENERAL'],
            ['name' => 'DIRECTORA DE PLANIFICACIÓN Y GESTIÓN ESTRATÉGICA'],
            ['name' => 'ANALISTA DE CONTABILIDAD 3 (RESPONSABLE DE CONTABILIDAD, DELEGADA)'],
            ['name' => 'ANALISTA DE VALIDACIÓN AGROPECUARIA 3'],
            ['name' => 'AUXILIAR DE SERVICIOS'],
            ['name' => 'EXPERTO DE PLANIFICACIÓN Y GESTIÓN ESTRATÉGICA'],
            ['name' => 'ANALISTA DE TECNOLOGÍAS DE LA INFORMACIÓN Y COMUNICACIÓN 3'],
            ['name' => 'ANALISTA DE TESORERÍA'],
            ['name' => 'ANALISTA DE CAPACITACIÓN AGROPECUARIA 2'],
            ['name' => 'SECRETARIA EJECUTIVA'],
            ['name' => 'DIRECTOR EJECUTIVO'],
            ['name' => 'OFICINISTA 2'],
            ['name' => 'ANALISTA DE TESORERÍA 1 (RESPONSABLE DE BODEGA, DELEGADA)'],
            ['name' => 'ASESOR 5'],
            ['name' => 'EXPERTO DE PROSPECCIÓN'],
            ['name' => 'DISEÑADOR GRÁFICO'],
            ['name' => 'ANALISTA DE PATROCINIO JUDICIAL Y ASESORÍA JURÍDICA 3'],
            ['name' => 'ANALISTA DE COMPRAS PÚBLICAS 3 (RESPONSABLE DE COMPRAS PÚBLICAS, DELEGADA)'],
            ['name' => 'ANALISTA DE COMUNICACIÓN SOCIAL 3 (RESPONSABLE DE UCS, DELEGADA)'],
            ['name' => 'SERVIDOR PÚBLICO DE APOYO 3'],
            ['name' => 'ANALISTA DE TESORERÍA 2 (RESPONSABLE DE TESORERÍA, DELEGADA)'],
            ['name' => 'SERVIDOR PÚBLICO 1 (RESPONSABLE DE NÓMINA, DELEGADO)'],
            ['name' => 'EXPERTO DE SEGUIMIENTO Y EVALUACION TECNICA'],
            ['name' => 'DIRECTOR DE TALENTO HUMANO'],
            ['name' => 'ANALISTA DE SEGURIDAD Y SALUD OCUPACIONAL 4'],
            ['name' => 'ANALISTA DE TRANSFERENCIA DE TECNOLOGÍA 3'],
            ['name' => 'ANALISTA INFORMÁTICO'],
            ['name' => 'ESPECIALISTA DE SERVICIOS ESPECIALIZADOS'],
            ['name' => 'DIRECTOR ADMINISTRATIVO FINANCIERO'],
            ['name' => 'ASISTENTE DE PRESUPUESTO'],
            ['name' => 'EXPERTO DE TECNOLOGÍA DE INFORMACIÓN Y COMUNICACIÓN'],
            ['name' => 'ANALISTA DE PLANIFICACIÓN Y ECONOMÍA AGRÍCOLA 3 (RESPONSABLE DE CONVENIOS, DELEGADA)'],
            ['name' => 'ANALISTA JURÍDICO 1'],
            ['name' => 'ANALISTA DE TRANSFERENCIA DE TECNOLOGÍA 3 (DIRECTOR DE INNOVACIÓN Y TRANSFERENCIA DE TECNOLOGÍA, ENCARGADO)'],
            ['name' => 'AUXILIAR ADMINISTRATIVO DE TRÁMITES'],
            ['name' => 'CONSERJE'],
            ['name' => 'SUBDIRECTOR DE POSICIONAMIENTO ESTRATÉGICO'],
            ['name' => 'ANALISTA DE TALENTO HUMANO'],
            ['name' => 'DIRECTOR DE PRODUCCIÓN, COMERCIALIZACIÓN Y SERVICIOS ESPECIALIZADOS'],
            ['name' => 'ANALISTA DE PRODUCCIÓN 2'],
            ['name' => 'ANALISTA DE PRODUCCIÓN CIENTÍFICA 3'],
            ['name' => 'SERVIDOR PÚBLICO DE APOYO 4'],
            ['name' => 'ANALISTA JURÍDICO 3'],
            ['name' => 'MÉDICO OCUPACIONAL 8HD'],
            ['name' => 'ANALISTA DE PLANIFICACION Y GESTION ESTRATEGICA 2 DE ESTACION EXPERIMENTAL'],
            ['name' => 'INVESTIGADOR AGREGADO 2'],
            ['name' => 'ANALISTA DE TESORERÍA 2 (RESPONSABLE DE TALENTO HUMANO, DELEGADA)'],
            ['name' => 'TRABAJADOR AGRÍCOLA'],
            ['name' => 'OFICINISTA 2 DE ESTACIÓN EXPERIMENTAL'],
            // Agrega los demás elementos según sea necesario...
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }

    }
}
