<?php

namespace App\Modules\Planificacion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FiasaUser; // Para el método importFiasaFile
use App\Models\User;
use App\Modules\Planificacion\Models\Ethnic_Group;
use App\Modules\Planificacion\Models\Location;
use App\Modules\Planificacion\Models\Nationality;
// use App\Modules\Planificacion\Models\Area; // YA NO SE USA (Position es texto ahora)
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Carbon\Carbon;

class ImportController extends Controller
{
    /**
     * Limpia strings: quita tildes, espacios extra y caracteres raros.
     * Convierte "  CÉDULA  " -> "cedula"
     */
    private function cleanString($value)
    {
        if (is_null($value) || $value === '') return null;
        $string = mb_strtolower($value, 'UTF-8');
        // Reemplazo de tildes para estandarizar comparaciones
        $replacements = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ñ' => 'n',
        ];
        $string = strtr($string, $replacements);
        return trim(preg_replace('/\s+/', ' ', $string));
    }

    /**
     * Obtiene el valor real de una celda, resolviendo Celdas Combinadas (Merged Cells).
     */
    private function getRealValue($sheet, $colLetter, $row)
    {
        $cell = $sheet->getCell($colLetter . $row);
        foreach ($sheet->getMergeCells() as $range) {
            if ($cell->isInRange($range)) {
                $rangeBounds = Coordinate::rangeBoundaries($range);
                $colStart = Coordinate::stringFromColumnIndex($rangeBounds[0][0]);
                $rowStart = $rangeBounds[0][1];
                // Retornamos el valor de la celda padre (superior izquierda)
                return trim($sheet->getCell($colStart . $rowStart)->getFormattedValue());
            }
        }
        return trim($cell->getFormattedValue());
    }

    /**
     * Importador Principal de Usuarios (Modernizado y Robusto)
     */
    public function importUserFile(Request $request)
    {
        set_time_limit(1200); // 20 Minutos

        if (!$request->hasFile('import_file')) {
            return response()->json(['error' => 'No se proporcionó un archivo.'], 400);
        }

        $forceUpdate = $request->boolean('update_existing');

        // --- MAPEO FIJO DE COLUMNAS (Basado en tu Excel real) ---
        $fixedMap = [
            'location'    => 'A',
            'name'        => 'D',
            'ethnic'      => 'G',
            'dni'         => 'H',
            'gender'      => 'I',
            'birth_date'  => 'J',
            'nationality' => 'K',
            'phone'       => 'Y',
            'email'       => 'AA',
            'position'    => 'AJ', // Puesto Funcional
        ];

        // Validaciones de seguridad (Para asegurar que el Excel es el correcto)
        $validationKeys = [
            'dni' => ['cedula', 'dni', 'identificacion', 'no. cedula'],
        ];

        $report = [
            'total_rows' => 0,
            'success'    => 0,
            'updated'    => 0,
            'skipped'    => 0,
            'errors'     => [],
            'audit_sample' => [] // Para ver qué leyó en las primeras filas
        ];

        try {
            $file = $request->file('import_file');
            $reader = IOFactory::createReaderForFile($file->getPathname());
            $reader->setReadDataOnly(false); // False para detectar celdas combinadas
            $spreadsheet = $reader->load($file->getPathname());

            // Busca la hoja 'users', si no existe usa la activa
            $sheet = $spreadsheet->getSheetByName('users') ?? $spreadsheet->getActiveSheet();

            // --- 1. VALIDACIÓN DE ESTRUCTURA ---
            // Buscamos la fila de encabezados (entre la 1 y la 10)
            $headerRowIndex = null;

            for ($r = 1; $r <= 10; $r++) {
                // Leemos columna H y limpiamos (para que "No. CÉDULA" coincida con "cedula")
                $valRaw = $this->getRealValue($sheet, $fixedMap['dni'], $r);
                $val = $this->cleanString($valRaw);

                foreach ($validationKeys['dni'] as $keyword) {
                    if (str_contains($val, $keyword)) {
                        $headerRowIndex = $r;
                        break 2;
                    }
                }
            }

            if (!$headerRowIndex) {
                throw new \Exception("Error Estructural: No se encontró la columna 'Cédula' en la posición {$fixedMap['dni']} (Filas 1-10).");
            }

            // --- 2. PROCESAMIENTO DE FILAS ---
            $highestRow = $sheet->getHighestRow();

            // Empezamos una fila después de los encabezados
            for ($row = $headerRowIndex + 1; $row <= $highestRow; $row++) {

                // Leer DNI
                $dniRaw = $this->getRealValue($sheet, $fixedMap['dni'], $row);
                $dni = preg_replace('/[^0-9]/', '', $dniRaw); // Solo números

                if (empty($dni)) continue; // Saltar filas vacías

                $report['total_rows']++;

                // Transacción POR FILA: Si falla una, no afecta a las demás
                DB::beginTransaction();

                try {
                    // Helper para obtener datos limpios
                    $getValue = function($key) use ($sheet, $fixedMap, $row) {
                        return trim(preg_replace('/\s+/', ' ', $this->getRealValue($sheet, $fixedMap[$key], $row)));
                    };

                    $nameRaw = $getValue('name');
                    $positionRaw = $getValue('position'); // Leemos el cargo como string directo

                    // Auditoría visual (primeras 5 filas)
                    if ($report['total_rows'] <= 5) {
                        $report['audit_sample'][] = [
                            'row' => $row,
                            'name' => $nameRaw,
                            'position' => $positionRaw
                        ];
                    }

                    if (empty($nameRaw)) throw new \Exception("El nombre está vacío.");

                    // --- LÓGICA DE EMAIL (Anti-Colisión) ---
                    $email = strtolower($getValue('email'));

                    // Si no tiene @, generamos uno dummy
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $email = $dni . '@sin-email.registrado';
                    }

                    // Verificamos si el email ya existe en OTRO usuario
                    $conflictingUser = User::where('email', $email)->where('dni', '!=', $dni)->first();
                    if ($conflictingUser) {
                        $originalEmail = $email;
                        $email = $dni . '_dup_' . $originalEmail;

                        // Reportamos advertencia (solo las primeras 20 para no llenar el log)
                        if(count($report['errors']) < 20) {
                            $report['errors'][] = ['row' => $row, 'dni' => $dni, 'msg' => "Email duplicado resuelto. Se usó: $email"];
                        }
                    }

                    // --- RELACIONES (LOOKUPS) ---

                    // Location: Corregido error de 'adress' nulo. Ponemos 'S/N' por defecto.
                    // Nota: Tu base de datos tiene la columna escrita como 'adress' (con una d), no 'address'.
                    $location = Location::firstOrCreate(
                        ['name' => $getValue('location') ?: 'Sin Definir'],
                        ['adress' => 'S/N']
                    );

                    $nationality = Nationality::firstOrCreate(['name' => $getValue('nationality') ?: 'Sin Definir']);
                    $ethnic      = Ethnic_Group::firstOrCreate(['name' => $getValue('ethnic') ?: 'Sin Definir']);

                    // --- PUESTO (CAMBIO MAYOR) ---
                    // Guardamos el string directo, ya no creamos relación con tabla Areas
                    $positionString = !empty($positionRaw) ? $positionRaw : 'Sin Definir';

                    // --- FECHAS ---
                    $birthDate = null;
                    $dateCellVal = $sheet->getCell($fixedMap['birth_date'] . $row)->getValue();
                    if (Date::isDateTime($sheet->getCell($fixedMap['birth_date'] . $row))) {
                        $birthDate = Date::excelToDateTimeObject($dateCellVal)->format('Y-m-d');
                    } else {
                        // Intento de parseo manual si viene como texto
                        $val = trim($dateCellVal);
                        if (!empty($val)) {
                            $timestamp = strtotime(str_replace('/', '-', $val));
                            if ($timestamp) $birthDate = date('Y-m-d', $timestamp);
                        }
                    }

                    // --- PREPARAR DATOS ---
                    $userData = [
                        'dni'            => $dni,
                        'name'           => $nameRaw,
                        'email'          => $email,
                        'gender'         => $getValue('gender'),
                        'phone'          => $getValue('phone'),
                        'birth_date'     => $birthDate,
                        'location_id'    => $location->id,
                        'nationality_id' => $nationality->id,
                        'ethnic_id'      => $ethnic->id,
                        'position'       => $positionString, // <--- Aquí va el texto plano
                    ];

                    // --- GUARDAR / ACTUALIZAR ---
                    $user = User::where('dni', $dni)->first();

                    if ($user) {
                        if ($forceUpdate) {
                            $user->update($userData);
                            // Asegurar rol
                            if (!$user->hasRole('user') && !$user->hasRole('admin')) {
                                $user->assignRole('user');
                            }
                            $report['updated']++;
                        } else {
                            $report['skipped']++;
                        }
                    } else {
                        $userData['password'] = Hash::make($dni);
                        $newUser = User::create($userData);
                        $newUser->assignRole('user');
                        $report['success']++;
                    }

                    DB::commit();

                } catch (\Exception $eRow) {
                    DB::rollBack();
                    $report['errors'][] = ['row' => $row, 'dni' => $dni, 'msg' => $eRow->getMessage()];
                }
            }

            return response()->json(['message' => 'Proceso finalizado.', 'stats' => $report], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error General: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    // --- MANTENEMOS TU CÓDIGO FIASA ORIGINAL (LIMPIO) ---
    public function importFiasaFile(Request $request)
    {
        set_time_limit(600);
        try {
            if (!$request->hasFile('import_file_fiasa')) throw new \Exception('Falta archivo FIASA.');

            $file = $request->file('import_file_fiasa');
            $uploadDir = 'public/excel/'; // Asegúrate que esta ruta exista y sea escribible
            $uploadFile = $uploadDir . $file->getClientOriginalName();
            $file->move($uploadDir, $file->getClientOriginalName());

            $reader = IOFactory::createReaderForFile($uploadFile);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($uploadFile);

            $counters = ['inserted' => 0, 'skipped_dup' => 0, 'skipped_invalid' => 0, 'total' => 0];

            $processSheet = function($sheetName, $cols) use ($spreadsheet, &$counters) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                if (!$sheet) return;

                $highestRow = $sheet->getHighestRow();
                for ($row = 6; $row <= $highestRow; $row++) {
                    $counters['total']++;
                    $name = $sheet->getCell($cols['name'] . $row)->getValue();
                    $dni = $sheet->getCell($cols['dni'] . $row)->getValue();
                    $loc = $sheet->getCell($cols['loc'] . $row)->getValue();
                    $this->processRowDataFiasa($dni, $name, $loc, $counters);
                }
            };

            $processSheet('FIASA', ['name' => 'K', 'dni' => 'L', 'loc' => 'C']);
            $processSheet('OTROS', ['name' => 'B', 'dni' => 'C', 'loc' => 'A']);

            return response()->json([
                'message' => 'Importación FIASA completada.',
                'filas_procesadas' => $counters['total'],
                'servicios_creados' => $counters['inserted']
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function processRowDataFiasa($dni, $name, $locationName, &$c)
    {
        $dni = preg_replace('/[^0-9]/', '', $dni);
        if (empty($dni) || empty($name)) { $c['skipped_invalid']++; return; }
        if (FiasaUser::where('dni', $dni)->exists()) { $c['skipped_dup']++; return; }

        // Lógica simple de email para Fiasa
        $parts = explode(' ', trim($name));
        $email = strtolower($parts[0] . '.' . ($parts[1] ?? 'user') . '@iniap.gob.ec'); // Simplificado

        $data = [
            'dni' => $dni, 'name' => trim($name), 'email' => $email, 'password' => Hash::make($dni)
        ];

        if (!empty($locationName)) {
            $loc = Location::firstOrCreate(['name' => trim($locationName)], ['adress' => 'S/N']);
            $data['location_id'] = $loc->id;
        }

        $u = FiasaUser::create($data);
        $u->assignRole('user');
        $c['inserted']++;
    }
}
