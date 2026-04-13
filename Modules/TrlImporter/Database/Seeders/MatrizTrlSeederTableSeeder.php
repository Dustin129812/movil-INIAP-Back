<?php

namespace Modules\TrlImporter\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MatrizTrlSeederTableSeeder extends Seeder
{
    public function run()
    {
        // Vaciamos la tabla antes de sembrar para evitar duplicados
        DB::table('trl.matriz_trl')->truncate();

        $preguntas = [
            // ==========================================
            // HÍBRIDOS, CLONES Y/O VARIEDADES
            // ==========================================
            // TRL 1 [cite: 19]
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se han identificado y documentado rasgos agronómicos de interés (productividad, tolerancia, calidad nutricional, u otros que el sector agrícola demande) en los materiales vegetales?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se realizó un informe interno, nota técnica o publicación que reporte los principios básicos observados?'],

            // TRL 2 [cite: 20]
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se ha formulado el plan de mejoramiento genético?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se han identificado los potenciales usuarios finales del sector agrícola?'],

            // TRL 3 [cite: 22]
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Se han realizado cruzamientos o ampliación genética, evaluado y caracterizado el valor genético o inducido cambios genéticos en los materiales?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Realizó un informe experimental o producto de investigación que documente los resultados?'],

            // TRL 4 [cite: 23]
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se ha realizado la evaluación agronómica de un número significativo de genotipos en invernadero, campo experimental o primeros ciclos productivos?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Realizó un informe de resultados de evaluación de esta fase?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se cuenta con material de propagación necesario para realizar ensayos multiambiente?'],

            // TRL 5 [cite: 24]
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se realizó un protocolo de validación (multiambiente) con integración del equipo multidisciplinario (Investigación + Transferencia)?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se realizó ensayos multiambiente con los materiales seleccionados?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 5, 'critico' => false, 'pregunta' => '¿Realizó informe técnico formal o artículo científico que documente los resultados de evaluación multiambiente?'],

            // TRL 6 [cite: 25]
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se realizó ensayos multiambiente con los materiales seleccionados?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se evaluaron materiales de los ensayos mutiambiente de manera participativa con socios estratégicos (productores, multiplicadores, industria, etre otros)?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se han seleccionado los materiales que serán liberados?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Existe un informe de validación en condiciones reales con análisis técnico-económico?'],

            // TRL 7 [cite: 26]
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Se ha validado el material seleccionado con diferentes tipos de manejo agronómico en condiciones reales?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Se ha modificado el informe de validación en condiciones reales con análisis técnico-económico?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 7, 'critico' => true, 'pregunta' => '¿Se ha iniciado la planificación de multiplicación de semilla/planta de la variedad, con Producción?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Existe un borrador de la ficha técnica tecnológica?'],

            // TRL 8 [cite: 27]
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 8, 'critico' => true, 'pregunta' => '¿El registro varietal ha sido concedido?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 8, 'critico' => false, 'pregunta' => '¿Las pruebas DHE han sido aprobadas por la autoridad competente?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 8, 'critico' => true, 'pregunta' => '¿La ficha tecnológica final aprobada y disponible?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 8, 'critico' => true, 'pregunta' => '¿La semilla/plantas están disponible en cantidad suficiente para la comercialización?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 8, 'critico' => true, 'pregunta' => '¿Los materiales divulgativos (folletos, guías, videos) están listos?'],

            // TRL 9 [cite: 28]
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 9, 'critico' => false, 'pregunta' => '¿Existen parcelas demostrativas o parcelas de difusión establecidas con material registrado?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 9, 'critico' => false, 'pregunta' => '¿Existen licenciamientos de la nueva variedad?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 9, 'critico' => true, 'pregunta' => '¿El material se encuentra liberado?'],

            // ==========================================
            // MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)
            // ==========================================
            // TRL 1 [cite: 31]
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se han identificado y documentado los principios de control, manejo integrado o nutrición relevantes para el cultivo objetivo?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se realizó un informe interno, nota técnica o publicación que reporte los principios básicos observados?'],

            // TRL 2 [cite: 32]
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se ha formulado el diseño conceptual de la estrategia de manejo integrado, nutrición o control?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se han identificado los potenciales stakeholders (productores, técnicos, gremios) que se verán afectados?'],

            // TRL 3 [cite: 34]
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Se han realizado experimentos en laboratorio o invernadero o microparcelas que demuestren el principio de la práctica de manejo?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Realizó un informe experimental o producto de investigación que documente los resultados?'],

            // TRL 4 [cite: 35]
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se ha realizado la evaluación de la tecnología a escala experimental bajo condiciones controladas (campo experimental o estación) ?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 4, 'critico' => false, 'pregunta' => '¿Se han determinado los costos de producción de tecnología a nivel experimental controlada?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Realizó un informe de resultados de evaluación de esta fase y se ha socializado con Transferencia?'],

            // TRL 5 [cite: 36]
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se realizó un protocolo de validación (multiambiente) con integración del equipo multidisciplinario (Investigación + Transferencia)?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se realizó ensayos multiambiente con tecnologías seleccionadas?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 5, 'critico' => false, 'pregunta' => '¿Realizó informe técnico formal o artículo científico que documente los resultados de evaluación multiambiente?'],

            // TRL 6 [cite: 37]
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se realizó ensayos multiambiente con las tecnologías seleccionadas?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se evaluaron las tecnologías en campo de los ensayos multiambiente, de manera participativa con socios estratégicos (productores, industria, entre otros)?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Existe un informe de validación en condiciones reales con análisis técnico-económico?'],

            // TRL 7 [cite: 38]
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Se ha validado (ajustado) la alternativa tecnológica de manejo en condiciones reales de productores?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 7, 'critico' => true, 'pregunta' => '¿Existe un borrador de una ficha técnica de recomendación tecnológica con respaldo experimental?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Se ha modificado el informe de validación en condiciones reales con análisis técnico-económico?'],

            // TRL 8 [cite: 39]
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 8, 'critico' => false, 'pregunta' => '¿El registro de la tecnologia aprobado, cuando aplica?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 8, 'critico' => true, 'pregunta' => '¿La ficha tecnológica y protocolo/manual final aprobada y disponible?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 8, 'critico' => true, 'pregunta' => '¿Los materiales divulgativos (folletos, guías, videos) están listos?'],

            // TRL 9 [cite: 40]
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 9, 'critico' => false, 'pregunta' => '¿Existen parcelas demostrativas o parcelas de difusión establecidas para transferencia a técnicos o productores?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 9, 'critico' => false, 'pregunta' => '¿Existen licenciamientos del Know how de la alternativa tecnológica?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 9, 'critico' => true, 'pregunta' => '¿La alternativa tecnológica se encuentra publicada o liberadas?'],

            // ==========================================
            // BIOINSUMOS
            // ==========================================
            // TRL 1 [cite: 43]
            ['tipo' => 'BIOINSUMOS', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se ha identificado y documentado un agente o compuesto con potencial de Control Biológico?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se ha revisado el marco regulatorio aplicable (AGROCALIDAD, normativa de bioinsumos)?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se realizó un informe interno, nota técnica o publicación que reporte los principios básicos observados?'],

            // TRL 2 [cite: 44]
            ['tipo' => 'BIOINSUMOS', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se ha formulado la propuesta de uso del agente o compuesto con potencial de Control Biológico?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se han identificado los potenciales stakeholders (productores, técnicos, gremios) que se verán afectados?'],

            // TRL 3 [cite: 46]
            ['tipo' => 'BIOINSUMOS', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Se han realizado ensayos de eficacia biológica en laboratorio y/o invernadero con resultados documentados?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Realizó un informe que respalde la eficacia del agente o compuesto con potencial de control biológico?'],

            // TRL 4 [cite: 47]
            ['tipo' => 'BIOINSUMOS', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se cuenta con una formulación o presentación estable del agente o compuesto con potencial de control biológico?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se han determinado la dosis, frecuencia y modo de aplicación en condiciones controladas?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se ha realizado la evaluación del bioinsumo a escala experimental bajo condiciones controladas (campo experimental o estación)?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Realizó un informe de resultados de evaluación de esta fase?'],

            // TRL 5 [cite: 48]
            ['tipo' => 'BIOINSUMOS', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se realizó un protocolo de validación con integración del equipo muticiplinario (Investigación + Transferencia)?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se realizó ensayos multiambiente con los bioinsumos seleccionados?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 5, 'critico' => false, 'pregunta' => '¿Realizó informe técnico formal o artículo científico que documente los resultados de evaluación multiambiente?'],

            // TRL 6 [cite: 49]
            ['tipo' => 'BIOINSUMOS', 'trl' => 6, 'critico' => false, 'pregunta' => '¿Se realizó ensayos multiambiente con el bioinsumo seleccionado?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se evaluaron los bioinsumos en campo de los ensayos multiambiente, de manera participativa con socios estratégicos (productores, industria, entre otros)?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Existe un informe de validación en condiciones reales con análisis técnico-económico?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se ha realizado la producción piloto del bioinsumo con documentación del proceso y costos de producción?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se han realizado estudios de vida útil y control de calidad?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 6, 'critico' => false, 'pregunta' => '¿Se ha evaluado el escalamiento de la producción del bioinsumo?'],

            // TRL 7 [cite: 50]
            ['tipo' => 'BIOINSUMOS', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Se ha validado (ajustado) el bioinsumo en condiciones reales de productores?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Se ha validado (ajustado) el bioinsumo en condiciones reales de producción a mayor escala?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Se ha modificado el informe de validación con análisis técnico-económico en condiciones de campo de productor o producción a mayor escala?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 7, 'critico' => true, 'pregunta' => '¿Existe un borrador de ficha técnica tecnológica del bioinsumo?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Existen interés por el licenciamientos del relacionados con el desarrollo del bioinsumo?'],

            // TRL 8 [cite: 51]
            ['tipo' => 'BIOINSUMOS', 'trl' => 8, 'critico' => false, 'pregunta' => '¿La ficha tecnológica y protocolo/manual final aprobada y disponible?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 8, 'critico' => true, 'pregunta' => '¿El registro ante la autoridad nacional (AGROCALIDAD) ha sido obtenido?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 8, 'critico' => true, 'pregunta' => '¿El protocolo de producción industrial, empaque y control de calidad está en versión definitiva?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 8, 'critico' => false, 'pregunta' => '¿Existe el registro de propiedad intelectual del bioinsumo, cuando aplique?'],

            // TRL 9 [cite: 52]
            ['tipo' => 'BIOINSUMOS', 'trl' => 9, 'critico' => true, 'pregunta' => '¿El bioinsumo se encuentra liberado?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 9, 'critico' => false, 'pregunta' => '¿Existen parcelas demostrativas o parcelas de difusión establecidas para transferencia a técnicos o productores?'],

            // ==========================================
            // AGROINDUSTRIA / VALOR AGREGADO
            // ==========================================
            // TRL 1 [cite: 55]
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se han identificado y documentado los principios con potenciales aplicaciones agroindustriales?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se realizó un informe interno, nota técnica o publicación que reporte los potenciables observados?'],

            // TRL 2 [cite: 56]
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se ha formulado un protocolo de investigación/desarrolllo del proceso o producto?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se ha revisado la normativa alimentaria o industrial aplicable al producto objetivo?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se han identificado los potenciales usuarios industriales y/o mercado objetivo?'],

            // TRL 3 [cite: 58]
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Se han realizado pruebas experimentales de laboratorio?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Existe un informe técnico del proceso/producto en condiciones de laboratorio?'],

            // TRL 4 [cite: 59]
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se cuenta con un prototipo básico, parámetros establecidos, con un proceso documentado?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se ha verificado el cumplimiento de los requisitos normativos aplicables al producto (INEN, ARCSA, AGROCALIDAD)?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 4, 'critico' => false, 'pregunta' => '¿Se han iniciado procesos relacionados con propiedad intelectual?'],

            // TRL 5 [cite: 60]
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se ha realizado la prueba en entornos representativos del proceso/producto en entornos relevantes (industria)?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se ha verificado el cumplimiento de los requisitos normativos aplicables al producto (INEN, ARCSA, AGROCALIDAD)?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se ha realizado la evaluación de viabilidad (Ej: Focus Group) con potenciales usuarios del proceso/producto con resultados documentados?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se ha desarrollado el ajuste de la fórmula y/o los procesos agroindustriales?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Existe un informe técnico y económico sobre el desempeño y viabilidad del proceso/producto en condiciones representativas (debe incluir costos de producción)?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 5, 'critico' => false, 'pregunta' => '¿Se han iniciado procesos relacionados con propiedad intelectual?'],

            // TRL 6 [cite: 61]
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se ha desarrollado la ficha técnica final del proceso/producto?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se han finalizado procesos relacionados con propiedad intelectual?'],

            // Los TRL 7, 8 y 9 para Agroindustria son omitidos, según lo indicado:
            // "El receptor de tecnología opera bajo el esquema de PI acordado..."
        ];

        $registrosDb = [];
        foreach ($preguntas as $p) {
            $registrosDb[] = [
                'tipo_tecnologia' => $p['tipo'],
                'nivel_trl'       => $p['trl'],
                'criterio_texto'  => $p['pregunta'],
                'es_critico'      => $p['critico'] ? 1 : 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        foreach (array_chunk($registrosDb, 100) as $bloque) {
            DB::table('trl.matriz_trl')->insert($bloque);
        }
    }
}
