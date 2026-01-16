<?php

namespace Modules\Investigacion\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Ethnic_Group;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\Material;
use Modules\Investigacion\Entities\Nationality;
use Modules\Investigacion\Entities\Province;

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
            $provinceId = DB::table('provinces')->where('name', $location['province'])->value('id');

            $cantonId = DB::table('cantons')->where('name', $location['canton'])->value('id');

            Location::create([
                'name' => $location['name'],
                'adress' => $location['adress'],
                'province_id' => $provinceId,
                'canton_id' => $cantonId
            ]);
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
