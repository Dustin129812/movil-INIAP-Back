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
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se han identificado y documentado rasgos agronómicos de interés (productividad, tolerancia, calidad nutricional, u otros que el sector agrícola demande) en los materiales vegetales?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se realizó un informe interno, nota técnica o publicación que reporte los principios básicos observados?'],

            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se ha formulado el plan de cruzamientos o el diseño experimental de la siguiente fase?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se han identificado los potenciales usuarios finales del sector agrícola?'],

            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Se han realizado cruzamientos o ampliación genética, evaluado y caracterizado el valor genético o inducido cambios genéticos en los materiales?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Realizó un informe experimental o producto de investigación que documente los resultados?'],

            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se está realizando la evaluación agronómica de un número significativo de genotipos en invernadero, campo experimental o primeros ciclos productivos?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Realizó un informe de resultados de evaluación de esta fase?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se cuenta con material de propagación necesario para realizar ensayos multiambiente?'],

            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se realizó un protocolo de validación con integración del equipo muticiplinario (Investigación + Transferencia+Producción)?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se realizó ensayos multiambiente con los materiales seleccionados?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 5, 'critico' => false, 'pregunta' => '¿Realizó informe técnico formal o artículo científico que documente los resultados de evaluación multiambiente?'],

            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se evaluaron materiales en campo bajo condiciones reales, de manera participativa con socios estratégicos (productores, multiplicadores, industria, etre otros)?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se cuenta con material de propagación necesario para realizar parcelas de las siguiente fase?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se han seleccionado los materiales que serán liberados?'],

            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Se ha validado el material seleccionado con diferentes tipos de manejo agronómico en condiciones reales?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 7, 'critico' => true, 'pregunta' => '¿Se ha iniciado la planificación de multiplicación de semilla/planta de la variedad, con Producción?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 7, 'critico' => true, 'pregunta' => '¿Existe un informe de validación en condiciones reales con análisis técnico-económico?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Existe ficha técnica tecnológica?'],

            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 8, 'critico' => true, 'pregunta' => '¿El registro varietal ha sido concedido y las pruebas DHE aprobadas por la autoridad competente?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 8, 'critico' => true, 'pregunta' => '¿La ficha tecnológica final aprobada y disponible?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 8, 'critico' => true, 'pregunta' => '¿La semilla/plantas están disponible en cantidad suficiente para la comercialización?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 8, 'critico' => true, 'pregunta' => '¿Los materiales divulgativos (folletos, guías, videos) están listos?'],

            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 9, 'critico' => true, 'pregunta' => '¿El material se encuentra liberado?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 9, 'critico' => true, 'pregunta' => '¿La semilla/plantas están disponible en cantidad suficiente para la comercialización?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 9, 'critico' => true, 'pregunta' => '¿Existen parcelas demostrativas o parcelas de difusión establecidas con material registrado?'],
            ['tipo' => 'HÍBRIDOS, CLONES Y/O VARIEDADES', 'trl' => 9, 'critico' => false, 'pregunta' => '¿Existen licenciamientos de la nueva variedad?'],

            // ==========================================
            // MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)
            // ==========================================
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se han identificado y documentado los principios de control, manejo integrado o nutrición relevantes para el cultivo objetivo?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se realizó un informe interno, nota técnica o publicación que reporte los principios básicos observados?'],

            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se ha formulado el diseño conceptual de la estrategia de manejo integrado, nutrición o control?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se han identificado los potenciales stakeholders (productores, técnicos, gremios) que se verán afectados?'],

            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Se han realizado experimentos en laboratorio o invernadero o microparcelas que demuestren el principio de la práctica de manejo?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Realizó un informe experimental o producto de investigación que documente los resultados?'],

            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se ha realizado la evaluación de la tecnología a escala experimental bajo condiciones controladas (campo experimental o estación)?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 4, 'critico' => false, 'pregunta' => '¿Se han determinado los costos de producción de tecnología a nivel experimental controlada?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Realizó un informe de resultados de evaluación de esta fase?'],

            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se realizó un protocolo de validación con integración del equipo muticiplinario (Investigación + Transferencia+Producción)?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se realizó ensayos multiambiente con tecnologías seleccionadas?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 5, 'critico' => false, 'pregunta' => '¿Realizó informe técnico formal o artículo científico que documente los resultados de evaluación multiambiente?'],

            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se evaluaron las tecnologías en campo bajo condiciones reales, de manera participativa con socios estratégicos (productores, industria, entre otros)?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Existe una recomendación tecnológica preliminar formal, documentada y con respaldo experimental?'],

            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Se ha validado (ajustado) la alternativa tecnológica de manejo en condiciones reales de productores?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 7, 'critico' => true, 'pregunta' => '¿Existe un informe de validación con análisis técnico-económico en condiciones de campo de productor?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Existe ficha técnica tecnológica?'],

            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 8, 'critico' => false, 'pregunta' => '¿El registro de la tecnologia aprobado, cuando aplica?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 8, 'critico' => true, 'pregunta' => '¿La ficha tecnológica y protocolo/manual final aprobada y disponible?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 8, 'critico' => true, 'pregunta' => '¿Los materiales divulgativos (folletos, guías, videos) están listos?'],

            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 9, 'critico' => true, 'pregunta' => '¿La alternativa tecnológica se encuentra publicada o liberadas?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 9, 'critico' => true, 'pregunta' => '¿Existen parcelas demostrativas o parcelas de difusión establecidas para transferencia a técnicos o productores?'],
            ['tipo' => 'MANEJO INTEGRADO DE CULTIVO (ALT. TECNOLÓGICAS)', 'trl' => 9, 'critico' => false, 'pregunta' => '¿Existen licenciamientos del Know how de la alternatica tecnológica?'],

            // ==========================================
            // BIOINSUMOS
            // ==========================================
            ['tipo' => 'BIOINSUMOS', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se ha identificado y documentado un agente o compuesto con potencial de Control Biológico?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se ha revisado el marco regulatorio aplicable (AGROCALIDAD, normativa de bioinsumos)?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se realizó un informe interno, nota técnica o publicación que reporte los principios básicos observados?'],

            ['tipo' => 'BIOINSUMOS', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se ha formulado la propuesta de uso del agente o compuesto con potencial de Control Biológico?'],

            ['tipo' => 'BIOINSUMOS', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Se han realizado ensayos de eficacia biológica en laboratorio y/o invernadero con resultados documentados?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Realizó un informe que respalde la eficacia del agente o compuesto con potencial de control biológico?'],

            ['tipo' => 'BIOINSUMOS', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se cuenta con una formulación o presentación estable del agente o compuesto con potencial de control biológico?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se han determinado la dosis, frecuencia y modo de aplicación en condiciones de microparcela?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se ha realizado la evaluación del bioinsumo a escala experimental bajo condiciones controladas (campo experimental o estación)?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Realizó un informe de resultados de evaluación de esta fase?'],

            ['tipo' => 'BIOINSUMOS', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se realizó un protocolo de validación con integración del equipo muticiplinario (Investigación + Transferencia+Producción)?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se realizó ensayos multiambiente con los bioinsumos seleccionados?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 5, 'critico' => false, 'pregunta' => '¿Realizó informe técnico formal o artículo científico que documente los resultados de evaluación multiambiente?'],

            ['tipo' => 'BIOINSUMOS', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se evaluaron los bioinsumos en campo bajo condiciones reales, de manera participativa con socios estratégicos (productores, industria, entre otros)?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Existe una recomendación tecnológica en campo preliminar formal, documentada y con respaldo experimental?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se ha realizado la producción piloto del bioinsumo con documentación del proceso y costos de producción?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se han realizado estudios de vida útil y control de calidad?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 6, 'critico' => false, 'pregunta' => '¿Se ha evaluado el escalamiento de la producción del bioinsumo?'],

            ['tipo' => 'BIOINSUMOS', 'trl' => 7, 'critico' => true, 'pregunta' => '¿Se ha validado (ajustado) el bioinsumo en condiciones reales de productores?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 7, 'critico' => true, 'pregunta' => '¿Existe un informe de validación con análisis técnico-económico en condiciones de campo de productor?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 7, 'critico' => true, 'pregunta' => '¿Existe ficha técnica tecnológica del bioinsumo?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Existen licenciamientos del Know how de la alternatica tecnológica, cuando aplique?'],

            ['tipo' => 'BIOINSUMOS', 'trl' => 8, 'critico' => false, 'pregunta' => '¿La ficha tecnológica y protocolo/manual final aprobada y disponible?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 8, 'critico' => true, 'pregunta' => '¿El registro ante la autoridad nacional (AGROCALIDAD) ha sido obtenido?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 8, 'critico' => true, 'pregunta' => '¿El protocolo de producción industrial, empaque y control de calidad está en versión definitiva?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 8, 'critico' => false, 'pregunta' => '¿Existe el registro de propiedad intelectual del bioinsumo, cuando aplique?'],

            ['tipo' => 'BIOINSUMOS', 'trl' => 9, 'critico' => true, 'pregunta' => '¿El bioinsumo se encuentra liberado?'],
            ['tipo' => 'BIOINSUMOS', 'trl' => 9, 'critico' => true, 'pregunta' => '¿Existen parcelas demostrativas o parcelas de difusión establecidas para transferencia a técnicos o productores?'],

            // ==========================================
            // AGROINDUSTRIA / VALOR AGREGADO
            // ==========================================
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se han identificado y documentado los principios con potenciales aplicaciones agroindustriales?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 1, 'critico' => true, 'pregunta' => '¿Se realizó un informe interno, nota técnica o publicación que reporte los potenciables observados?'],

            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se ha formulado un protocolo de investigación/desarrolllo del proceso o producto?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 2, 'critico' => true, 'pregunta' => '¿Se ha revisado la normativa alimentaria o industrial aplicable al producto objetivo?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 2, 'critico' => false, 'pregunta' => '¿Se han identificado los potenciales usuarios industriales o del mercado objetivo?'],

            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Se han realizado pruebas piloto a escala de laboratorio?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Se cuenta con un prototipo básico con un proceso documentado?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 3, 'critico' => true, 'pregunta' => '¿Se realizó demuestran que el proceso/producto es técnicamente factible?'],

            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se ha ajustado la fórmula y los procesos agroindustriales en planta piloto?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Se cuenta con un prototipo funcional en planta piloto documentado?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 4, 'critico' => true, 'pregunta' => '¿Existe un informe técnico de la evaluación en planta piloto?'],

            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se ha realizado la prueba piloto del proceso/producto en entornos relevantes (condiciones semi-industriales)?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Existe un informe tecnico y económico sobre el desempeño y viabilidad del proceso/producto en condiciones representativas?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 5, 'critico' => true, 'pregunta' => '¿Se ha verificado el cumplimiento de los requisitos normativos aplicables al producto (INEN, ARCSA, AGROCALIDAD)?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 5, 'critico' => false, 'pregunta' => '¿Se cuenta con registro de propiedad intelectual o acuerdo de licenciamiento o colaboración?'],

            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se ha realizado la evaluación industrial con potenciales usuarios del proceso/producto con resultados documentados?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se ha realizado un informe con la viabilidad técnica y económica del proceso?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 6, 'critico' => true, 'pregunta' => '¿Se cuenta con registro de propiedad intelectual o acuerdo de licenciamiento o colaboración?'],

            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 7, 'critico' => true, 'pregunta' => '¿Se ha validado (ajustado) el proceso/producto en un entorno operativo real (planta industrial o semi-industrial)?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 7, 'critico' => true, 'pregunta' => '¿Existe un informe de validación operativa con análisis técnico y económico?'],
            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 7, 'critico' => false, 'pregunta' => '¿Se ha iniciado la elaboración de manuales de operación y la gestión de registros/certificaciones?'],

            ['tipo' => 'AGROINDUSTRIA / VALOR AGREGADO', 'trl' => 8, 'critico' => true, 'pregunta' => '¿Ficha técnica y manual de elaboración estan listos?'],
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

        // Dividimos en lotes por si a futuro crecen mucho las preguntas
        foreach (array_chunk($registrosDb, 100) as $bloque) {
            DB::table('trl.matriz_trl')->insert($bloque);
        }
    }
}
