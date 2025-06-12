<?php

namespace Database\Seeders;

use App\Models\Canton;
use App\Models\Ethnic_Group;
use App\Models\Experimental_Station;
use App\Models\Investigation_Area;
use App\Models\Investigation_Line;
use App\Models\Location;
use App\Models\Material;
use App\Models\Measure;
use App\Models\Multidisciplinary_Group;
use App\Models\Nationality;
use App\Models\Performance_Indicator;
use App\Models\Province;
use App\Models\Rubro;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MiscelaneoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        #Seeder provincias asociadas a las estaciones experimentales

        $provinces = [
            ['name' => 'Azuay'],
            ['name' => 'Bolívar'],
            ['name' => 'Cañar'],
            ['name' => 'Carchi'],
            ['name' => 'Chimborazo'],
            ['name' => 'Cotopaxi'],
            ['name' => 'El Oro'],
            ['name' => 'Esmeraldas'],
            ['name' => 'Galápagos'],
            ['name' => 'Guayas'],
            ['name' => 'Imbabura'],
            ['name' => 'Loja'],
            ['name' => 'Los Ríos'],
            ['name' => 'Manabí'],
            ['name' => 'Morona Santiago'],
            ['name' => 'Napo'],
            ['name' => 'Orellana'],
            ['name' => 'Pastaza'],
            ['name' => 'Pichincha'],
            ['name' => 'Santa Elena'],
            ['name' => 'Santo Domingo de los Tsáchilas'],
            ['name' => 'Sucumbíos'],
            ['name' => 'Tungurahua'],
            ['name' => 'Zamora Chinchipe']
        ];

        foreach ($provinces as $province) {
            Province::create($province);
        }

        $cantons = [
            ['name' => 'Quito'],
            ['name' => 'Guayaquil'],
            ['name' => 'Tumbaco'],
            ['name' => 'Gualaceo'],
            ['name' => 'Joya de los Sachas'],
            ['name' => 'Morona'],
            ['name' => 'Palora'],
            ['name' => 'San Jacinto de Yaguachi'],
            ['name' => 'Paltas'],
            ['name' => 'Galápagos-Isla San Cristóbal'],
            ['name' => 'Zapotillo'],
            ['name' => 'Portoviejo'],
            ['name' => 'Mejía'],
            ['name' => 'Riobamba'],
            ['name' => 'Ambato'],
            ['name' => 'Sangolquí'],
            ['name' => 'Urcuquí'],
            ['name' => 'Latacunga'],
            ['name' => 'Pillaro'],
            ['name' => 'Ibarra'],
            ['name' => 'Bolívar/Tulcán'],
            ['name' => 'La Concordia'],
            ['name' => 'Mocache']
        ];

        foreach ($cantons as $canton) {
            Canton::create($canton);
        }

        $ethnic_groups = [
            ['name' => 'AFROECUATORIANO'],
            ['name' => 'BLANCO'],
            ['name' => 'INDÍGENA'],
            ['name' => 'MESTIZO'],
            ['name' => 'MONTUBIO'],
        ];

        foreach ($ethnic_groups as $ethnic_group) {
            Ethnic_Group::create($ethnic_group);
        }

        $nationalities = [
            ['name' => 'ECUATORIANA'],
            ['name' => 'COLOMBIANA'],
        ];

        foreach ($nationalities as $nationality) {
            Nationality::create($nationality);
        }

        $locations = [
            [
                'name' => 'ADM. CENTRAL',
                'adress' => 'AV. ELOY ALFARO N30-350 Y AV. AMAZONAS',
                'province' => 'Pichincha',
                'canton' => 'Quito'
            ],
            [
                'name' => 'AUSTRO',
                'adress' => 'VÍA EL DESCANSO - GUALACEO KM 12 1/2, SECTOR BULLCAY',
                'province' => 'Azuay',
                'canton' => 'Gualaceo'
            ],
            [
                'name' => 'CENTRAL DE LA AMAZONÍA',
                'adress' => 'JOYA DE LOS SACHAS, VÍA SAN CARLOS – 3 KM DE LA PARQUER',
                'province' => 'Orellana',
                'canton' => 'Joya de los Sachas'
            ],
            [
                'name' => 'LITORAL SUR',
                'adress' => 'KM 26 VÍA DURAN TAMBO, PARROQUIA VIRGEN DE FÁTIMA',
                'province' => 'Guayas',
                'canton' => 'Guayaquil'
            ],
            [
                'name' => 'PORTOVIEJO',
                'adress' => 'KM. 12 VIA SANTA ANA, CANTÓN PORTOVIEJO, MANABÍ',
                'province' => 'Manabí',
                'canton' => 'Portoviejo'
            ],
            [
                'name' => 'SANTA CATALINA',
                'adress' => 'PANAMERICANA SUR KM. 1  VÍA TAMBILLO, CUTUGLAGUA',
                'province' => 'Pichincha',
                'canton' => 'Mejía'
            ],
            [
                'name' => 'SANTO DOMINGO',
                'adress' => 'KM.38 VÍA SANTO DOMINGO- QUININDÉ',
                'province' => 'Santo Domingo de los Tsáchilas',
                'canton' => 'La Concordia'
            ],
            [
                'name' => 'TROPICAL PICHILINGUE',
                'adress' => 'KM. 5 VIA QUEVEDO - EL EMPALME',
                'province' => 'Los Ríos',
                'canton' => 'Mocache'
            ]
        ];

        foreach ($locations as $location) {
            // Buscar el ID de la provincia en la tabla 'provinces'
            $provinceId = DB::table('provinces')->where('name', $location['province'])->value('id');

            // Buscar el ID del cantón en la tabla 'cantons'
            $cantonId = DB::table('cantons')->where('name', $location['canton'])->value('id');

            // Insertar en la tabla Experimental_Station con las claves foráneas
            Location::create([
                'name' => $location['name'],
                'adress' => $location['adress'],
                'province_id' => $provinceId, // Clave foránea de la provincia
                'canton_id' => $cantonId      // Clave foránea del cantón
            ]);
        }

        $investigation_areas = [
            ['name' => 'Manejo y conservación de los recursos naturales'],
            ['name' => 'Incremento de la productividad'],
            ['name' => 'Incorporación de valor agregado a la producción agropecuaria'],
            ['name' => 'Economía Agrícola'],
            ['name' => 'Transferencia de Tecnología'],
            ['name' => 'Producción y Servicios'],
        ];

        foreach ($investigation_areas as $investigation_area) {
            Investigation_Area::create($investigation_area);
        }

        $investigation_lines = [
            ['name' => 'Conservación y uso de recursos genéticos'],
            ['name' => 'Manejo integrado de cultivo y ganadería'],
            ['name' => 'Agroecología'],
            ['name' => 'Mejoramiento genético'],
            ['name' => 'Transformación y Agregación de valor de productos agropecuarios'],
            ['name' => 'Conservación de suelos y aguas'],
            ['name' => 'Transformación y Agregación de valor de subproductos agropecuarios  de suelos y aguas'],
            ['name' => 'Agrobiotecnología'],
            ['name' => 'Sensores remotos'],
        ];

        foreach ($investigation_lines as $investigation_line) {
            Investigation_Line::create($investigation_line);
        }

        $measures = [
            ['name' => 'Accesiones'],
            ['name' => 'Personas'],
            ['name' => 'toneladas'],
            ['name' => 'Plantas'],
            ['name' => 'Parcela'],
            ['name' => 'Lotes'],
            ['name' => 'Hectáreas'],
            ['name' => 'Estudio'],
            ['name' => 'Kilogramos'],
        ];

        foreach ($measures as $measure) {
            Measure::create($measure);
        }

        $indicators = [
            ['name' => 'Agricultores beneficiados (en miles)'],
            ['name' => 'Alternativa tecnológica'],
            ['name' => 'Estudio'],
            ['name' => 'Eventos científicos'],
            ['name' => 'Eventos de transferencia y difusión'],
            ['name' => 'Número de accesiones conservadas (en miles)'],
            ['name' => 'Publicación científica'],
            ['name' => 'Publicación técnica'],
            ['name' => 'Semilla mejorada (en toneladas)'],
            ['name' => 'Técnicos beneficiados'],
            ['name' => 'Variedad, clon, híbrido'],
        ];

        foreach ($indicators as $indicator) {
            Performance_Indicator::create($indicator);
        }

        $rubros = [
            ['name' => 'Arroz'],
            ['name' => 'Cacao'],
            ['name' => 'Café'],
            ['name' => 'Camote'],
            ['name' => 'Guanábana'],
            ['name' => 'Haba'],
            ['name' => 'Maíz'],
            ['name' => 'Maíz suave'],
            ['name' => 'Mora'],
            ['name' => 'Naranjilla'],
            ['name' => 'Papa'],
            ['name' => 'Quinua'],
            ['name' => 'Tomate de árbol'],
            ['name' => 'Trigo'],
        ];

        foreach ($rubros as $rubro) {
            Rubro::create($rubro);
        }

        $materials = [
            ['name' => 'Vehiculo'],
            ['name' => 'Viaticos'],
            ['name' => 'Combustible'],
            ['name' => 'Orden de movilización'],
            ['name' => 'Conductor'],
            ['name' => 'Personal de campo'],
            ['name' => 'Maquinaría agrícola'],
            ['name' => 'Insumos Agrícolas'],
            ['name' => 'Análisis de laboratorios'],
            ['name' => 'Auditorio'],
            ['name' => 'Sala de reuniones'],
            ['name' => 'Equipo Tecnológico'],
        ];

        foreach ($materials as $material) {
            Material::create($material);
        }
    }
}
