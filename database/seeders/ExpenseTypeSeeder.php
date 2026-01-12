<?php

namespace Database\Seeders;

use App\Modules\Planificacion\Models\ExpenseType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('expense_types')->delete(); // Limpia la tabla para evitar duplicados en re-ejecuciones

        $group53Items = [
            ['code' => '530101', 'name' => 'Agua Potable'
            , 'description' => 'Egresos por el consumo de agua potable, provisión de agua y sus relacionados.'
            ],
            ['code' => '530102', 'name' => 'Agua de Riego'
                , 'description' => 'Egresos por el servicio de agua de riego y sus relacionados.'
            ],
            ['code' => '530104', 'name' => 'Energía Eléctrica'
                , 'description' => 'Egresos por servicio de energía eléctrica, energía alternativa y sus relacionados.'
            ],
            ['code' => '530105', 'name' => 'Telecomunicaciones'
                , 'description' => 'Egresos por servicios de telefonía fija y móvil, telegrafia, fax, radiotelegráfico, satelital, internet, arrendamiento de canales de frecuencia y otros relacionados.'
            ],
            ['code' => '530106', 'name' => 'Servicio de Correo'
                , 'description' => 'Egresos por servicios postales y relacionados prestados por empresas autorizadas.'
            ],
            ['code' => '530201', 'name' => 'Transporte de Personal'
                , 'description' => 'Egresos para el servicio de transporte de personas.'
            ],
            ['code' => '530202', 'name' => 'Fletes y Maniobras'
                , 'description' => 'Egresos por traslado, maniobras, embarque y desembarque nacional e internacional de toda clase de bienes de conformidad con la normativa vigente.'
            ],
            ['code' => '530203', 'name' => 'Almacenamiento, Embalaje, Desembalaje, Envase, Desenvase y Recarga de Extintores'
                , 'description' => 'Egresos por almacenamiento, embalaje, desembalaje, envase, desenvase de toda clase de objetos, bienes y recarga de lextintores.'
            ],
            ['code' => '530204', 'name' => 'Edición, Impresión, Reproducción, Publicaciones, Suscripciones, Fotocopiado, Traducción, Empastado, Enmarcación, Serigrafía, Fotografía, Carnetización, Filmación e Imágenes Satelitales.'
                , 'description' => 'Egresos por impresión y empastado de libros, folletos, revistas, memorias, instructivos, manuales y otros elementos oficiales; reproducción de especies fiscales, suscripciones, fotocopiado, traducciones, enmarcaciones, serigrafías, fotografías, carnetización; filmación e imágenes satelitales.'
            ],
            ['code' => '530205', 'name' => 'Espectáculos Culturales y Sociales'
                , 'description' => 'Egresos por la realización de eventos culturales y sociales, incluye los egresos de logística de estos eventos.'
            ],
            ['code' => '530207', 'name' => 'Difusión, Información y Publicidad'
                , 'description' => 'Egresos para cubrir todos los servicios de difusión de información oficial y pública por cualquier medio de comunicación.'
            ],
            ['code' => '530208', 'name' => 'Servicio de Seguridad y Vigilancia'
                , 'description' => 'Egresos por servicios de seguridad y vigilancia de personas, bienes muebles, inmuebles, valores y otros.'
            ],
            ['code' => '530209', 'name' => 'Servicios de Aseo, Lavado de Vestimenta de Trabajo, Fumigación, Desinfección, Limpieza de Instalaciones, manejo de desechos contaminados, recuperación y clasificación de materiales reciclables.'
                , 'description' => 'Egresos por servicios de lavado de todo tipo de ropa de trabajo y similares; fumigación, desinfección, aseo de instalaciones de trabajo; recolección y manejo de desechos contaminados, recuperación y clasificación de materiales reciclables.'
            ],
            ['code' => '530210', 'name' => 'Servicio de Guardería'
                , 'description' => 'Egresos por servicios o contribución económica por el cuidado y alimentación de hijos de los servidores y trabajadores del sector público.'
            ],
            ['code' => '530215', 'name' => 'Servicios especiales para Inteligencia y Contrainteligencia'
                , 'description' => 'Egresos especiales para actividades de inteligencia y contrainteligencia de protección interna, mantenimiento del orden público y defensa nacional.'
            ],
            ['code' => '530216', 'name' => 'Servicios de Voluntariado'
                , 'description' => 'Egresos por servicios prestados por el voluntariado de acción social y desarrollo.'
            ],
            ['code' => '530220', 'name' => 'Servicios para Actividades Agropecuarias, Pesca y Caza'
                , 'description' => 'Egresos por servicios de erradicación de plagas de mitigación de su impacto en actividades agrícolas, ganaderas, pesca y caza.'
            ],
            ['code' => '530221', 'name' => 'Servicios Personales Eventuales sin Relación de Dependencia'
                , 'description' => 'Egresos por servicios personales eventuales sin relación de dependencia, contratados para: procesos electorales, encuestas, avalúos, remates; así como para ejercer funciones de jueces y conjueces de la Corte por ausencia temporal del titular y/o por el número de causas despachadas, entre otros.'
            ],
            ['code' => '530222', 'name' => 'Servicios y Derechos en Producción y Programación de Radio y Televisión'
                , 'description' => 'Egresos por servicios y derechos en producción y programación para la transmisión en radio y televisión, locución de idiomas, traducción de spots de televisión, producción de audio y video de carácter oficial.'
            ],
            ['code' => '530224', 'name' => 'Servicio de Implementación y Administración de Bancos de Información'
                , 'description' => 'Egresos por servicios de implementación y administración de bancos de datos.'
            ],
            ['code' => '530225', 'name' => 'Servicio de Incineración de Documentos Públicos, Sustancias Estupefacientes y Psicotrópicas, Bienes Defectuosos y/o Caducados, Productos Agropecuarios Decomisados, Desechos de Laboratorio y Otros'
                , 'description' => 'Egresos por servicios de incineración de documentos públicos, sustancias estupefacientes y psicotrópicas, bienes defectuosos y/o caducados, productos agropecuarios decomisados, desechos de laboratorio y otros determinados por la normativa vigente.'
            ],
            ['code' => '530226', 'name' => 'Servicios Médicos Hospitalarios y Complementarios'
                , 'description' => 'Egresos por servicios médicos hospitalarios, exámenes de laboratorio, exámenes de evaluación, pre-trasplante, sesiones de hemodiálisis, quimioterapias, TAC, procalcitonina, entre otros cuando las unidades médicas no dispongan del servicio.'
            ],
            ['code' => '530227', 'name' => 'Servicios de Repatriación de Cadáveres de Ecuatorianos Fallecidos en el Exterior'
                , 'description' => 'Egresos de adquisición de cofres, cajas de embalaje, servicio de embalsamiento y otros relacionados, trámites legales, traslados y fletes aéreos para repatriación de cadáveres.'
            ],
            ['code' => '530228', 'name' => 'Servicios de Provisión de Dispositivos Electrónicos y Certificación para Registro de Firmas Digitales'
                , 'description' => 'Egresos por el servicio de provisión de dispositivos electrónicos para el registro de firmas digitales y su certificación.'
            ],
            ['code' => '530229', 'name' => 'Soporte al Usuario a través de Centros de Servicios y Operadores Telefónicos'
                , 'description' => 'Egresos por soporte al usuario a través de centros de servicios y operadores telefónicos.'
            ],
            ['code' => '530230', 'name' => 'Digitalización de Información y Datos Públicos'
                , 'description' => 'Egresos por servicios de digitalización de información y datos públicos.'
            ],
            ['code' => '530231', 'name' => 'Servicios de Protección y Asistencia Técnica a Víctimas, Testigos y Otros Participantes en Procesos Penales'
                , 'description' => 'Egresos por servicios de protección y asistencia integral a victimas, testigos y otros participantes en procesos penales'
            ],
            ['code' => '530232', 'name' => 'Barrido Predial para la Modernización del Sistema de Información'
                , 'description' => 'Egresos para la investigación técnica y jurídica de la ficha predial rural, relevamiento predial rural y generar la base de datos gráfica y alfanumérica'
            ],
            ['code' => '530233', 'name' => 'Servicios en Actividades Mineras e Hidrocarburíferas'
                , 'description' => 'Egresos por servicios técnicos especializados prestados en los procesos de extracción y comercialización de la producción minera e hidrocarburifera.'
            ],
            ['code' => '530234', 'name' => 'Comisiones por la Venta de Productos, Servicios Postales y Financieros'
                , 'description' => 'Egresos por comisiones por la venta de productos, servicios postales y financieros.'
            ],
            ['code' => '530235', 'name' => 'Servicio de Alimentación'
                , 'description' => 'Egresos para la prestación del servicio de alimentación.'
            ],
            ['code' => '530236', 'name' => 'Servicios en Plantaciones Forestales'
                , 'description' => 'Egresos por servicios de hoyado, plantado, cercado, limpieza y otros en plantaciones forestales.'
            ],
            ['code' => '530237', 'name' => 'Remediación, Restauración y Descontaminación de Cuerpos de Agua'
                , 'description' => 'Egresos por servicios de remediación, restauración y descontaminación de mares, ríos, lagos, lagunas, esteros y quebradas.'
            ],
            ['code' => '530238', 'name' => 'Servicio de Administración de Patio de Contenedores'
                , 'description' => 'Egresos por servicios provistos por la mano de obra calificada para la administración y operación de patio de contendores dentro del recinto portuario.'
            ],
            ['code' => '530239', 'name' => 'Membrecías'
                , 'description' => 'Egresos por cuotas y membrecías gestionadas por las entidades del sector público.'
            ],
            ['code' => '530240', 'name' => 'Servicios Exequiales'
                , 'description' => 'Egresos por servicios fúnebres requeridos por el fallecimiento de personas, asi como también de aquellas que se encuentran dentro de los grupos prioritarios o vulnerables, en situación de riesgo y emergencia, discapacidad, pobreza o extrema pobreza, con enfermedades terminales, catastróficas, raras o huérfanas.'
            ],
            ['code' => '530241', 'name' => 'Servicio de Monitoreo de la Información en Televisión, Radio, Prensa, Medios On-Line y Otros'
                , 'description' => 'Egresos por servicios de monitoreo de la información en televisión, radio, prensa, medios on-line y otros.'
            ],
            ['code' => '530242', 'name' => 'Servicios de Almacenamiento, Control, Custodia, Dispensación de Medicamentos, Materiales e Insumos Médicos y Otros'
                , 'description' => 'Egresos por servicios de almacenamiento, control, custodia, dispensación de medicamentos, materiales e insumos médicos y otros.'
            ],
            ['code' => '530243', 'name' => 'Garantía Extendida de Bienes'
                , 'description' => 'Egresos por servicio de garantía extendida de bienes en aplicación del principio de vigencia tecnológica.'
            ],
            ['code' => '530244', 'name' => 'Servicio de Confección de Menaje de Hogar y/o Prendas de Protección'
                , 'description' => 'Egresos por servicio de confección de menaje de hogar y/o prendas de protección.'
            ],
            ['code' => '530245', 'name' => 'Servicios relacionados a la exhumación e inhumación de cadáveres'
                , 'description' => 'Egresos por servicios de traslado, inscripción, reubicación y otros relacionados con la exhumación e inhumación de cadáveres y restos humanos.'
            ],
            ['code' => '530246', 'name' => 'Servicios de Identificación, Marcación, Autentificación, Rastreo, Monitoreo, Seguimiento y/o Trazabilidad'
                , 'description' => 'Egresos por servicios de identificación, marcación, autentificación, rastreo, monitoreo, seguimiento y/o trazabilidad, relacionados con mecanismos de control para reconocer y/o diferenciar los bienes de origen lícito.'
            ],
            ['code' => '530247', 'name' => 'Servicio de Educación en el Exterior para hijos/as del personal diplomático y auxiliar del servicio exterior'
                , 'description' => 'Egresos para cubrir servicios de educación de los hijos e hijas del personal diplomático y auxiliar del servicio exterior que presten sus servicios fuera del país y que sus hijos/as estén estudiando en el exterior y dependan económicamente del funcionario/a, mientras dure sus funciones en el exterior de conformidad a la normativa legal vigente.'
            ],
            ['code' => '530248', 'name' => 'Eventos Oficiales'
                , 'description' => 'Egresos para la realización de actos y ceremonias oficiales, incluye los que requieran las oficinas instaladas en el exterior para la recepción y atención del cuerpo diplomático, misiones diplomáticas y huéspedes oficiales.'
            ],
            ['code' => '530249', 'name' => 'Eventos Públicos Promocionales'
                , 'description' => 'Egresos para la organización y ejecución de ferias, exposiciones, ruedas de negocios y negociaciones, incluye alquiler, montaje, desmontaje, logistica, organización, ejecución y otros relacionados con eventos públicos promocionales nacionales e internacionales.'
            ],
            ['code' => '530250', 'name' => 'Egresos para Migrantes en Procesos de Deportación o en Estados de Vulnerabilidad'
                , 'description' => 'Egresos para el pago de alimentación, hospedaje, transporte, cobertores, materiales de aseo de los migrantes deportados, en estado de vulnerabilidad y otros relacionados con los procesos de deportación y vulnerabilidad.'
            ],
            ['code' => '530251', 'name' => 'Procesos de Deportación de Inmigrantes, Control Migratorio y de Residencia en la provincia de Galápagos'
                , 'description' => 'Egresos por alimentación, vituallas, hospedaje y transporte para inmigrantes que no dispongan de recursos para su salida del país y su respectiva custodia, control migratorio y de residencia en la provincia de Galápagos de conformidad con las disposiciones legales vigentes.'
            ],
            ['code' => '530252', 'name' => 'Licencias y Derechos No Exclusivos de Obras y Productos Culturales'
                , 'description' => 'Servicios de carácter no exclusivo de explotación de obras y productos culturales, transferencia de derechos y autorización de uso por terceros, de conformidad con las disposiciones legales vigentes.'
            ],
            ['code' => '530253', 'name' => 'Servicios Generales para Subastas, Arriendos y Remates'
                , 'description' => 'Egresos en los servicios que se incurren por procesos de subastas, arriendos y remates.'
            ],
            ['code' => '530254', 'name' => 'Servicios de Prestaciones o Protecciones'
                , 'description' => 'Egresos por servicios médicos hospitalarios, prestaciones o protecciones a beneficiarios que se encuentren habilitados para el pago o desembolsos por accidentes de transito.'
            ],
            ['code' => '530255', 'name' => 'Combustibles'
                , 'description' => 'Egresos para combustibles y gas en general.'
            ],
            ['code' => '530301', 'name' => 'Pasajes al Interior'
                , 'description' => 'Egresos por movilización y transporte de servidores y trabajadores públicos dentro del país; transporte de delegados, misiones, comisiones y representaciones extranjeras y nacionales que brindan asistencia técnica y participan en eventos de entidades públicas, para deportistas, entrenadores y cuerpo técnico que representen al pais.'
            ],
            ['code' => '530302', 'name' => 'Pasajes al Exterior'
                , 'description' => 'Egresos por movilización y transporte de servidores y trabajadores públicos fuera del país transporte de delegados, misiones, comisiones y representaciones extranjeras y nacionales que brindan asistencia técnica y participan en eventos de entidades públicas y para deportistas, entrenadores y cuerpo técnico que representen al país.'
            ],
            ['code' => '530303', 'name' => 'Viáticos y Subsistencias en el Interior'
                , 'description' => 'Egresos por hospedaje y alimentación de los servidores y trabajadores públicos en comisión de servicios dentro del país'
            ],
            ['code' => '530304', 'name' => 'Viáticos y Subsistencias en el Exterior'
                , 'description' => 'Egresos para cubrir valores diarios de hospedaje y alimentación de los servidores y trabajadores públicos enviados en comisión de servicios al exterior.'
            ],
            ['code' => '530305', 'name' => 'Mudanzas e Instalaciones'
                , 'description' => 'Egresos para funcionarios por su traslado e instalación dentro y fuera del país'
            ],
            ['code' => '530306', 'name' => 'Viáticos por Gastos de Residencia'
                , 'description' => 'Gastos de vivienda para los servidores y servidoras que tengan su domicilio habitual fuera de la ciudad en la que prestan sus servicios de acuerdo con las disposiciones legales vigentes, incluyen los que por la naturaleza de sus funciones deben residir len el exterior.'
            ],
            ['code' => '530307', 'name' => 'Atención a Delegados Extranjeros y Nacionales, Deportistas, Entrenadores y Cuerpo Técnico que Representen al País'
                , 'description' => 'Egresos de hospedaje y alimentación a delegados, misiones, comisiones y representaciones extranjeras y nacionales que brindan asistencia técnica y participan en eventos de entidades públicas, deportistas, entrenadores y cuerpo técnico que representen al país.'
            ],
            ['code' => '530308', 'name' => 'Recargos por cambios en pasajes al interior y al exterior del país'
                , 'description' => 'Egresos por recargos o penalización por cambios en la utilización de pasajes al interior y al exterior del país.'
            ],
            ['code' => '530309', 'name' => 'Gastos de Representación en el Exterior'
                , 'description' => 'Gastos, desembolsos o erogaciones efectuadas por los servidores/as del servicio exterior que, en el ejercicio de su cargo o atribuciones, efectivicen egresos con motivo de actividades de interés oficial en las que el funcionario/a actúe en representación del Estado; valores que serán liquidados en los porcentajes que establezca para el efecto el ente rector en materia de remuneraciones.'
            ],
            ['code' => '530401', 'name' => 'Terrenos (Mantenimiento)'
                , 'description' => 'Egresos para mantenimiento de predios urbanos y rurales.'
            ],
            ['code' => '530402', 'name' => 'Edificios, Locales, Residencias y Cableado Estructurado (Instalación, Mantenimiento y Reparación)'
                , 'description' => 'Egresos por mantenimiento y reparación de edificios, locales, residencias por armada y desarmada de estaciones de trabajo, mamparas, piso, techo y cableado estructurado.'
            ],
            ['code' => '530403', 'name' => 'Mobiliarios (Instalación, Mantenimiento y Reparación)'
                , 'description' => 'Egresos por instalación, mantenimiento y reparación de bienes muebles.'
            ],
            ['code' => '530404', 'name' => 'Maquinarias y Equipos (Instalación, Mantenimiento y Reparación)'
                , 'description' => 'Egresos por instalación, mantenimiento, reparación de maquinarias y equipos, excepto equipos informáticos.'
            ],
            ['code' => '530405', 'name' => 'Vehículos (Servicio para Mantenimiento y Reparación)'
                , 'description' => 'Egresos por el servicio de mantenimiento y reparación de vehículos.'
            ],
            ['code' => '530406', 'name' => 'Herramientas (Mantenimiento y Reparación)'
                , 'description' => 'Egresos por mantenimiento y reparación de herramientas.'
            ],
            ['code' => '530408', 'name' => 'Bienes Artísticos y Culturales'
                , 'description' => 'Egresos por mantenimiento y reparación de objetos artísticos y culturales que constituyan acervo patrimonial público.'
            ],
            ['code' => '530409', 'name' => 'Libros y Colecciones'
                , 'description' => 'Egresos por mantenimiento y reparación de libros y colecciones de bibliotecas y oficinas públicas.'
            ],
            ['code' => '530410', 'name' => 'Bienes de Uso Bélico y de Seguridad Pública'
                , 'description' => 'Egresos por instalación, mantenimiento y reparación de equipo bélico y de seguridad pública.'
            ],
            ['code' => '530415', 'name' => 'Bienes Biológicos'
                , 'description' => 'Egresos por el cuidado y crianza de bienes biológicos.'
            ],
            ['code' => '530417', 'name' => 'Infraestructura'
                , 'description' => 'Egresos por mantenimiento, adecuación y reparación de infraestructura para garantizar su utilización durante su vida útil, excluyen las mejoras, renovaciones o ampliaciones que tengan como propósito aumentar el rendimiento y la capacidad de los activos fijos o prolongar significativamente su vida útil esperada.'
            ],
            ['code' => '530418', 'name' => 'Mantenimiento de Áreas Verdes y Arreglo de Vías Internas'
                , 'description' => 'Egresos por mantenimiento de áreas verdes, jardines, poda de árboles, hierbas, plantas, fertilización y arreglo de vias internas.'
            ],
            ['code' => '530419', 'name' => 'Bienes Deportivos (Instalación, Mantenimiento y Reparación)'
                , 'description' => 'Egresos para instalación, mantenimiento y reparación de bienes deportivos.'
            ],
            ['code' => '530425', 'name' => 'Instalación, Readecuación, Montaje de Exposiciones, Mantenimiento y Reparación de Espacios y Bienes Culturales'
                , 'description' => 'Egresos para la instalación, readecuación, montaje de exposiciones, mantenimiento y reparación de espacios y bienes culturales.'
            ],
            ['code' => '530426', 'name' => 'Demoliciones de Edificios, Locales, Residencias y Otros'
                , 'description' => 'Egresos destinados para la demolición, de edificios, locales, residencias y otros.'
            ],
            ['code' => '530501', 'name' => 'Terrenos (Arrendamiento)'
                , 'description' => 'Egresos por alquiler de terrenos.'
            ],
            ['code' => '530502', 'name' => 'Edificios, Locales y Residencias, Parqueaderos, Casilleros Judiciales y Bancarios (Arrendamiento)'
                , 'description' => 'Egresos por el alquiler de edificios, locales, residencias, parqueaderos, casilleros judiciales y bancarios.'
            ],
            ['code' => '530503', 'name' => 'Mobiliario (Arrendamiento)'
                , 'description' => 'Egresos por alquiler de mobiliario.'
            ],
            ['code' => '530504', 'name' => 'Maquinarias y Equipos (Arrendamiento)'
                , 'description' => 'Egresos por alquiler de maquinarias y equipos, excepto informáticos.'
            ],
            ['code' => '530505', 'name' => 'Vehículos (Arrendamiento)'
                , 'description' => 'Egresos por alquiler de vehículos necesarios para el desarrollo de actividades institucionales.'
            ],
            ['code' => '530506', 'name' => 'Herramientas (Arrendamiento)'
                , 'description' => 'Egresos por alquiler de herramientas.'
            ],
            ['code' => '530515', 'name' => 'Bienes Biológicos (Alquiler)'
                , 'description' => 'Egresos por alquiler de bienes biológicos: plantas, semovientes y otros similares'
            ],
            ['code' => '530516', 'name' => 'Indumentaria, Prendas de protección, Accesorios y Otros'
                , 'description' => 'Egresos por alquiler de indumentaria, prendas de protección, accesorios y otros similares de utilización en actos culturales y artisticos.'
            ],
            ['code' => '530601', 'name' => 'Consultoria, Asesoría e Investigación Especializada'
                , 'description' => 'Egresos por servicios especializados de consultoría, asesoría e investigación profesional y técnica.'
            ],
            ['code' => '530602', 'name' => 'Servicio de Auditoría'
                , 'description' => 'Egresos por servicios especializados de auditoría.'
            ],
            ['code' => '530604', 'name' => 'Fiscalización e Inspecciones Técnicas'
                , 'description' => 'Egresos por servicios especializados para la entrega o recepción de obras o peritajes.'
            ],
            ['code' => '530605', 'name' => 'Estudio y Diseño de Proyectos'
                , 'description' => 'Egresos por servicios especializados para la elaboración de estudios y diseño de proyectos.'
            ],
            ['code' => '530606', 'name' => 'Honorarios por Contratos Civiles de Servicios'
                , 'description' => 'Egresos por servicios profesionales o técnicos especializados, sin relación de dependencia para puestos comprendidos en todos los grupos ocupacionales.'
            ],
            ['code' => '530607', 'name' => 'Servicios Técnicos Especializados'
                , 'description' => 'Egresos por servicios de inspección técnica agropecuaria, prestación de servicios sociales para personas en situación de vulnerabilidad, registro e identificación de infracciones a la norma de tránsito y seguridad vial, desaduanización y legalización de mercaderías importadas, incluye servicios que recibe el avión presidencial en el exterior e interior y otros.'
            ],
            ['code' => '530608', 'name' => 'Registro, Inscripción y Otros Egresos Previos a la Aceptación para Capacitación en el Exterior'
                , 'description' => 'Egresos de registro, inscripción y otros asociados a los procedimientos para la aceptación de candidaturas para capacitación en el exterior.'
            ],
            ['code' => '530609', 'name' => 'Investigaciones Profesionales y Análisis de Laboratorio'
                , 'description' => 'Egresos para cubrir la realización de investigaciones profesionales y análisis de laboratorio para la ejecución de actividades de control, monitoreo y otras relacionadas con ámbitos especializados.'
            ],
            ['code' => '530610', 'name' => 'Servicios de Cartografia'
                , 'description' => 'Egresos por servicios de cartografia.'
            ],
            ['code' => '530611', 'name' => 'Congresos, Seminarios y Convenciones'
                , 'description' => 'Egresos para financiar congresos, seminarios, convenciones y talleres dentro y fuera del país.'
            ],
            ['code' => '530612', 'name' => 'Capacitación a Servidores Públicos'
                , 'description' => 'Egresos por contratación de servicios especializados para la capacitación y adiestramiento exclusivamente para servidores públicos.'
            ],
            ['code' => '530613', 'name' => 'Capacitación para la Ciudadanía en General'
                , 'description' => 'Egresos por contratación de servicios especializados para la capacitación y adiestramiento de la ciudadanía en general'
            ],
            ['code' => '530701', 'name' => 'Desarrollo, Actualización, Asistencia Técnica y Soporte de Sistemas Informáticos'
                , 'description' => 'Egresos por generación de programas integrados, análisis, diseño, implementación, actualización, asistencia técnica y soporte de sistemas informáticos.'
            ],
            ['code' => '530702', 'name' => 'Arrendamiento y Licencias de Uso de Paquetes Informáticos'
                , 'description' => 'Egresos por arrendamiento de paquetes informáticos, licencias de software y páginas web.'
            ],
            ['code' => '530703', 'name' => 'Arrendamiento de Equipos Informáticos'
                , 'description' => 'Egresos por el alquiler de equipos informáticos.'
            ],
            ['code' => '530704', 'name' => 'Mantenimiento y Reparación de Equipos y Sistemas Informáticos'
                , 'description' => 'Egresos por mantenimiento y reparación de equipos y sistemas informáticos.'
            ],
            ['code' => '530801', 'name' => 'Alimentos y Bebidas'
                , 'description' => 'Egresos por adquisición de alimentos y bebidas.'
            ],
            ['code' => '530802', 'name' => 'Vestuario, Lencería, Prendas de Protección, Insumos y Accesorios para uniformes del personal de Protección, Vigilancia y Seguridad.'
                , 'description' => 'Egresos por adquisición de indumentaria, prendas de protección, insumos y accesorios para uniformes del personal de protección, vigilancia y seguridad.'
            ],
            ['code' => '530803', 'name' => 'Lubricantes'
                , 'description' => 'Egresos para lubricantes y aditivos en general.'
            ],
            ['code' => '530804', 'name' => 'Materiales de Oficina'
                , 'description' => 'Egresos para suministros, materiales y accesorios de oficina.'
            ],
            ['code' => '530805', 'name' => 'Materiales de Aseo'
                , 'description' => 'Egresos para suministros y materiales de aseo y limpieza.'
            ],
            ['code' => '530807', 'name' => 'Materiales de Impresión, Fotografia, Reproducción y Publicaciones'
                , 'description' => 'Egresos por suministros y materiales para imprenta, fotografía, reproducción, revistas, periódicos y otras publicaciones'
            ],
            ['code' => '530808', 'name' => 'Instrumental Médico Quirúrgico'
                , 'description' => 'Egresos para la adquisición de todo tipo de instrumental médico quirúrgico utilizado en los diferentes procedimientos quirúrgicos, excepto los equipos biomédicos.'
            ],
            ['code' => '530809', 'name' => 'Medicamentos'
                , 'description' => 'Egresos por la adquisición de medicamentos que servirán para diagnóstico, tratamiento, mitigación, profilaxis, anomalía física, síntoma, restablecimiento, corrección, modificación del equilibrio de las funciones orgánicas de los seres humanos; asociada a las sustancias de valor dietético con indicaciones terapéuticas o alimentos preparados que reemplacen regímenes alimenticios especiales.'
            ],
            ['code' => '530810', 'name' => 'Dispositivos Médicos para Laboratorio Clinico y de Patología'
                , 'description' => 'Egresos para la adquisición de dispositivos médicos utilizados en los servicios de laboratorio clínico y de patología excepto los equipos biomédicos.'
            ],
            ['code' => '530811', 'name' => 'Insumos, Materiales y Suministros para Construcción, Electricidad, Plomería, Carpintería, Señalización Vial, Navegación, Contra Incendios y Placas'
                , 'description' => 'Egresos por insumos, materiales y suministros para construcción, electricidad, plomeria, carpinteria, señalización vial, tránsito, navegación, contra incendios y placas.'
            ],
            ['code' => '530812', 'name' => 'Materiales Didácticos'
                , 'description' => 'Egresos por la adquisición de suministros, materiales, libros y folletos destinados a actividades educativas y su distribución.'
            ],
            ['code' => '530813', 'name' => 'Repuestos y Accesorios'
                , 'description' => 'Egresos por la adquisición de repuestos y accesorios necesarios para el funcionamiento de los bienes.'
            ],
            ['code' => '530814', 'name' => 'Suministros para Actividades Agropecuarias, Pesca y Caza'
                , 'description' => 'Egresos por la adquisición de suministros y materiales utilizados en las actividades agrícolas, ganaderas, caza y pesca'
            ],
            ['code' => '530815', 'name' => 'Acuñación de Monedas'
                , 'description' => 'Egresos por la acuñación de monedas.'
            ],
            ['code' => '530816', 'name' => 'Derivados de Hidrocarburos para la Comercialización Interna'
                , 'description' => 'Egresos para cubrir obligaciones relacionadas con la importación de derivados de hidrocarburos.'
            ],
            ['code' => '530817', 'name' => 'Productos Agrícolas'
                , 'description' => 'Egresos por la adquisición de productos agrícolas en situaciones de excedente o escasez de producción.'
            ],
            ['code' => '530819', 'name' => 'Accesorios e Insumos Químicos y Orgánicos'
                , 'description' => 'Egresos por la adquisición de accesorios e insumos quimicos y orgánicos para prevención, control, mitigación y erradicación de epidemias, pandemias y otros.'
            ],
            ['code' => '530820', 'name' => 'Menaje y Accesorios Descartables'
                , 'description' => 'Egresos por la adquisición de menaje de hogar, cocina y accesorios descartables.'
            ],
            ['code' => '530821', 'name' => 'Egresos para Situaciones de Emergencia'
                , 'description' => 'Egresos para la adquisición de alimentos, viveres, medicinas, movilización, hospedaje, vituallas, menaje mínimo de casa, ropa, mantenimiento, reparación y otros de atención a la población vulnerable en situaciones de emergencia.'
            ],
            ['code' => '530822', 'name' => 'Condecoraciones'
                , 'description' => 'Egresos para la adquisición de placas, medallas y similares para condecoraciones en actos protocolarios.'
            ],
            ['code' => '530823', 'name' => 'Egresos para Sanidad Agropecuaria'
                , 'description' => 'Egresos por la adquisición de alimentos, medicinas para prevención y tratamiento, productos farmacéuticos, dispositivos médicos, aseo y accesorios, vacunas, reactivos, sustancias antisépticas y desinfectantes relacionados con sanidad agropecuaria (animal, vegetal, inocuidad de los alimentos, registro de productos agropecuarios y capacidad analítica en todas estas ramas).'
            ],
            ['code' => '530824', 'name' => 'Insumos, Bienes y Materiales para Producción de Programas de Radio, Televisión, Eventos Culturales, Artísticos y Entretenimiento en General'
                , 'description' => 'Egresos por la adquisición de insumos, bienes y materiales para la producción de pro programas de radio y televisión, eventos culturales, artisticos y entretenimiento en general.'
            ],
            ['code' => '530825', 'name' => 'Insumos y Accesorios para Compensar Discapacidades'
                , 'description' => 'Egresos en insumos médicos, accesorios, electrodomésticos, menaje de hogar y equipamiento de viviendas para personas con capacidades especiales.'
            ],
            ['code' => '530826', 'name' => 'Dispositivos Médicos de Uso General'
                , 'description' => 'Egresos por la adquisición de dispositivos médicos para uso general, utilizados en los diferentes procedimientos médicos, incluyen desinfectantes para los dispositivos médicos, excepto los equipos biomédicos, de laboratorio y odontología.'
            ],
            ['code' => '530827', 'name' => 'Uniformes Deportivos'
                , 'description' => 'Egresos para la adquisición o confección de uniformes para deportistas, entrenadores y cuerpo técnico que representen al país y eventos deportivos de carácter local.'
            ],
            ['code' => '530828', 'name' => 'Materiales de Peluquería'
                , 'description' => 'Egresos para la adquisición de materiales de peluquería.'
            ],
            ['code' => '530829', 'name' => 'Insumos, Materiales, Suministros y Bienes para Investigación'
                , 'description' => 'Egresos en insumos, materiales, suministros y bienes para investigación.'
            ],
            ['code' => '530832', 'name' => 'Dispositivos Médicos para Odontologia'
                , 'description' => 'Egresos para la adquisición de dispositivos médicos utilizados en odontología, excepto los equipos biomédicos.'
            ],
            ['code' => '530833', 'name' => 'Dispositivos Médicos para Imagen'
                , 'description' => 'Egresos para la adquisición de dispositivos médicos utilizados en imagen, excepto los equipos biomédicos.'
            ],
            ['code' => '530834', 'name' => 'Prótesis, Endoprótesis e Implantes Corporales'
                , 'description' => 'Egresos para la adquisición de prótesis, endoprótesis, órtesis, accesorios externos, accesorios odontológicos y otros necesarios para la reparación artificial, sustitución y rehabilitación de las partes músculo-esqueléticas, bucales y órganos de los sentidos.'
            ],
            ['code' => '530836', 'name' => 'Muestras de Productos para Ferias, Exposiciones y Negociaciones Nacionales e Internacionales'
                , 'description' => 'Egresos por la adquisición de muestras de productos para ferias, exposiciones y negociaciones nacionales e internacionales.'
            ],
            ['code' => '530845', 'name' => 'Productos Homeopáticos'
                , 'description' => 'Egresos para la adquisición de productos homeopáticos, tintura madre o cepa homeopática, diluciones de conformidad a las reglas descritas en las farmacopeas homeopáticas.'
            ],
            ['code' => '530846', 'name' => 'Insumos para Medicina Alternativa'
                , 'description' => 'Egresos por la adquisición de insumos para medicina alternativa.'
            ],
            ['code' => '531001', 'name' => 'Logística'
                , 'description' => 'Egresos por la adquisición de vituallas para la fuerza pública.'
            ],
            ['code' => '531002', 'name' => 'Suministros para la Defensa y Seguridad Pública'
                , 'description' => 'Egresos para la adquisición de municiones y otros materiales fungibles utilizados por la fuerza pública.'
            ],
            ['code' => '531101', 'name' => 'Convenios de Adhesión para Adquisición de Medicamentos de Consulta Externa en Farmacias Externalizadas'
                , 'description' => 'Egresos para el registro de la aplicación de convenios de adhesión para la adquisición de medicamentos de consulta externa de la Red de Salud Pública y entrega de medicamentos en farmacias externalizadas.'
            ],
            ['code' => '531403', 'name' => 'Mobiliario'
                , 'description' => 'Egresos para la adquisición de mobiliario.'
            ],
            ['code' => '531404', 'name' => 'Maquinarias y Equipos'
                , 'description' => 'Egresos para la adquisición de maquinarias y equipos, excepto de equipos informáticos.'
            ],
            ['code' => '531406', 'name' => 'Herramientas y Equipos menores'
                , 'description' => 'Egresos para la adquisición de herramientas y equipos menores.'
            ],
            ['code' => '531407', 'name' => 'Equipos, Sistemas y Paquetes Informáticos'
                , 'description' => 'Egresos para la adquisición de equipos, sistemas y paquetes informáticos.'
            ],
            ['code' => '531408', 'name' => 'Bienes Artísticos, Culturales, Deportivos y Símbolos Patrios'
                , 'description' => 'Egresos para la adquisición de objetos artísticos, culturales, deportivos, medallas, trofeos y símbolos patrios.'
            ],
            ['code' => '531409', 'name' => 'Libros y Colecciones'
                , 'description' => 'Egresos para la adquisición de colecciones, libros, revistas y ediciones técnicas.'
            ],
            ['code' => '531411', 'name' => 'Partes y Repuestos'
                , 'description' => 'Egresos para adquisición de partes y repuestos.'
            ],
            ['code' => '531512', 'name' => 'Semovientes'
                , 'description' => 'Egresos para la adquisición de animales.'
            ],
            ['code' => '531514', 'name' => 'Acuáticos'
                , 'description' => 'Egresos para la adquisición de especies relacionadas con el medio acuático.'
            ],
            ['code' => '531515', 'name' => 'Plantas'
                , 'description' => 'Egresos para la adquisición de plantas o árboles, inclusive aquellas para recuperar tierras degradadas, proteger cuencas hidrográficas e integrar sistemas agroforestales.'
            ],
            ['code' => '531601', 'name' => 'Fondos de Reposición Cajas Chicas'
                , 'description' => 'Fondos que tienen como finalidad pagar obligaciones no previsibles, urgentes y de valor mínimo, su destino, límite, prohibición, operación y obligatoriedad se aplicarán de conformidad a la normativa vigente.'
            ],
            ['code' => '531602', 'name' => 'Fondos Rotativos'
                , 'description' => 'Fondos destinados para cubrir obligaciones que por su característica no pueden ser realizados con los procesos normales de la gestión financiera institucional, su destino, límite, prohibición, operación y obligatoriedad se aplicarán de conformidad a la normativa vigente.'
            ],
        ];

        $group84Items = [
            ['code' => '840103', 'name' => 'Mobiliarios'
            , 'description' => 'Egresos para la compra de mobiliario.'
            ],
            ['code' => '840104', 'name' => 'Maquinarias y Equipos'
                , 'description' => 'Egresos para la compra de maquinarias y equipos, excepto equipos informáticos, médicos y odontológicos.'
            ],
            ['code' => '840105', 'name' => 'Vehículos'
                , 'description' => 'Egresos para la compra de vehiculos de transporte terrestre, ferroviario, aéreo, marítimo y fluvial.'
            ],
            ['code' => '840106', 'name' => 'Herramientas'
                , 'description' => 'Egresos para la compra de herramientas consideradas capitalizables.'
            ],
            ['code' => '840107', 'name' => 'Equipos, Sistemas y Paquetes Informáticos'
                , 'description' => 'Egresos para la compra de equipos, sistemas y paquetes informáticos.'
            ],
            ['code' => '840108', 'name' => 'Bienes Artísticos y Culturales'
                , 'description' => 'Egresos para la compra de objetos artísticos y culturales que constituyan acervo patrimonial público.'
            ],
            ['code' => '840109', 'name' => 'Libros y Colecciones'
                , 'description' => 'Egresos para la compra de libros, colecciones y ediciones técnicas considerados bienes de capital.'
            ],
            ['code' => '840111', 'name' => 'Partes y Repuestos'
                , 'description' => 'Egresos para la compra de partes y repuestos considerados bienes de capital.'
            ],
            ['code' => '840112', 'name' => 'Bienes de Seguridad Nacional Estratégica'
                , 'description' => 'Egresos para la compra de bienes de seguridad nacional estratégica.'
            ],
            ['code' => '840113', 'name' => 'Equipos Médicos'
                , 'description' => 'Egresos para la adquisición de equipos médicos y sus accesorios, excepto equipo odontológico.'
            ],
            ['code' => '840115', 'name' => 'Equipos Odontológicos'
                , 'description' => 'Egresos para la adquisición de equipos odontológicos y sus accesorios.'
            ],
            ['code' => '840201', 'name' => 'Terrenos (Inmuebles)'
                , 'description' => 'Egresos para la compra de predios urbanos y rurales de conformidad con las necesidades de la función pública.'
            ],
            ['code' => '840202', 'name' => 'Edificios, Locales y Residencias (Inmuebles)'
                , 'description' => 'Egresos para la compra de edificios, locales y residencias para fines de la función pública.'
            ],
            ['code' => '840203', 'name' => 'Bienes Prefabricados (Inmuebles)'
                , 'description' => 'Egresos para la adquisición de bienes prefabricados que serán inventariados y podrán ser movilizados de una unidad administrativa a otra por necesidad institucional.'
            ],
            ['code' => '840301', 'name' => 'Terrenos (Expropiación)'
                , 'description' => 'Egresos para indemnizar el valor de los predios urbanos o rurales declarados de utilidad pública.'
            ],
            ['code' => '840302', 'name' => 'Edificios, Locales y Residencias (Expropiación)'
                , 'description' => 'Egresos para indemnizar el valor de edificios, locales y residencias, incluye los terrenos correspondientes.'
            ],
            ['code' => '840401', 'name' => 'Patentes, Derechos de Autor, Marcas Registradas y Derecho de Llave.'
                , 'description' => 'Egresos por patentes, derechos de autor, marcas registradas y derecho de llave.'
            ],
            ['code' => '840402', 'name' => 'Licencias Computacionales'
                , 'description' => 'Egresos por la adquisición de licencias computacionales, con duración superior a un año, de conformidad con la Norma Técnica de Contabilidad.'
            ],
            ['code' => '840403', 'name' => 'Sistemas de Información'
                , 'description' => 'Egresos por la adquisición de sistemas de información, de conformidad con la Norma Técnica de Contabilidad.'
            ],
            ['code' => '840404', 'name' => 'Páginas Web'
                , 'description' => 'Egresos por la adquisición de páginas web, de conformidad con la Norma Técnica de Contabilidad.'
            ],
            ['code' => '840512', 'name' => 'Semovientes'
                , 'description' => 'Egresos por la adquisición de animales destinados al trabajo y reproducción.'
            ],
            ['code' => '840513', 'name' => 'Bosques'
                , 'description' => 'Egresos para la compra de bosques y su explotación.'
            ],
            ['code' => '840514', 'name' => 'Acuáticos'
                , 'description' => 'Egresos para la adquisición de especies relacionadas con el medio acuático con fines reproductivos.'
            ],
            ['code' => '840515', 'name' => 'Plantas'
                , 'description' => 'Egresos para la adquisición de plantas o árboles de los que se obtendrán productos agricolas o productos procesados luego de la recolección o cosecha.'
            ],
        ];

        foreach ($group53Items as $item) {
            ExpenseType::firstOrCreate(
                ['name' => $item['name']],
                [
                    'group' => '53',
                    'code' => $item['code'],
                    'description' => $item['description'] ?? null
                ]
            );
        }

        foreach ($group84Items as $item) {
            ExpenseType::firstOrCreate(
                ['name' => $item['name']],
                [
                    'group' => '84',
                    'code' => $item['code'],
                    'description' => $item['description'] ?? null
                ]
            );
        }
    }
}
