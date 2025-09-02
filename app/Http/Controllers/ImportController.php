<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Ethnic_Group;
use App\Models\FiasaUser;
use App\Models\Location;
use App\Models\Nationality;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function importUserFile(Request $request)
    {
        set_time_limit(600);

        try {
            if (!$request->hasFile('import_file')) {
                throw new \Exception('No se proporcionó un archivo para importar.');
            }

            $file = $request->file('import_file');
            if (!$file->isValid()) {
                return response()->json(['error' => 'Archivo no válido.'], Response::HTTP_BAD_REQUEST);
            }

            $uploaddir = 'public/excel/';
            $uploadfile = $uploaddir . $file->getClientOriginalName();
            $file->move($uploaddir, $file->getClientOriginalName());

            if (!file_exists($uploadfile)) {
                throw new \Exception('Error al mover el archivo.');
            }

            $reader = IOFactory::createReaderForFile($uploadfile);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($uploadfile);

            if (!$spreadsheet->getSheetByName('users')) {
                throw new \Exception('La hoja "users" no se encuentra en el archivo Excel.');
            }

            $sheet = $spreadsheet->getSheetByName('users');

            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();

            $insertedCount = 0;
            $skippedCount = 0;

            for ($row = 6; $row <= $highestRow; $row++) {
                $dni = $sheet->getCell('H' . $row)->getValue();
                $email = strtolower($sheet->getCell('AA' . $row)->getValue());

                $existingUser = User::where('dni', $dni)
                    ->orWhere('email', $email)
                    ->first();

                if ($existingUser) {
                    $skippedCount++;
                    continue;
                }

                $data['dni'] = $dni;
                $data['name'] = $sheet->getCell('D' . $row)->getValue();
                $data['gender'] = $sheet->getCell('I' . $row)->getValue();
                $data['email'] = $email;
                $data['phone'] = strtolower($sheet->getCell('Y' . $row)->getValue());
                $birthDateValue = $sheet->getCell('J' . $row)->getValue();
                $data['password'] = Hash::make($dni);

                if (is_numeric($birthDateValue)) {
                    $data['birth_date'] = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($birthDateValue))->format('Y/m/d');
                }

                $locationsName = $sheet->getCell('A' . $row)->getValue();
                $locations = Location::firstOrCreate(['name' => $locationsName]);
                $data['location_id'] = $locations->id;

                $nationalityName = $sheet->getCell('K' . $row)->getValue();
                $nationality = Nationality::firstOrCreate(['name' => $nationalityName]);
                $data['nationality_id'] = $nationality->id;

                $ethnicName = $sheet->getCell('G' . $row)->getValue();
                $ethnic = Ethnic_Group::firstOrCreate(['name' => $ethnicName]);
                $data['ethnic_id'] = $ethnic->id;

                $positionsName = $sheet->getCell('AJ' . $row)->getValue();
                $positions = Area::firstOrCreate(['name' => $positionsName]);
                $data['position_id'] = $positions->id;

                $newUser = User::create($data);
                $newUser->assignRole('user');
                $insertedCount++;
            }

            return response()->json([
                'message' => 'Proceso completado',
                'usuarios_creados' => $insertedCount,
                'usuarios_saltados' => $skippedCount
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function importFiasaFile(Request $request)
    {
        set_time_limit(600);

        try {
            if (!$request->hasFile('import_file_fiasa')) {
                throw new \Exception('No se proporcionó un archivo para importar.');
            }

            $file = $request->file('import_file_fiasa');
            if (!$file->isValid()) {
                return response()->json(['error' => 'Archivo no válido.'], Response::HTTP_BAD_REQUEST);
            }

            $uploadDir = 'public/excel/';
            $uploadFile = $uploadDir . $file->getClientOriginalName();
            $file->move($uploadDir, $file->getClientOriginalName());

            if (!file_exists($uploadFile)) {
                throw new \Exception('Error al mover el archivo.');
            }

            $reader = IOFactory::createReaderForFile($uploadFile);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($uploadFile);

            // --- Contadores totales para el resumen final ---
            $totalInsertedCount = 0;
            $totalSkippedDuplicateCount = 0;
            $totalSkippedInvalidDataCount = 0;
            $totalProcessedRows = 0;

            // --- PROCESAR HOJA "FIASA" ---
            $fiasaSheet = $spreadsheet->getSheetByName('FIASA');
            if ($fiasaSheet !== null) {
                $highestRow = $fiasaSheet->getHighestRow();
                for ($row = 6; $row <= $highestRow; $row++) {
                    $totalProcessedRows++;
                    $name = $fiasaSheet->getCell('K' . $row)->getValue();
                    $dni = $fiasaSheet->getCell('L' . $row)->getValue();
                    $locationName = $fiasaSheet->getCell('C' . $row)->getValue();

                    $this->processRowData($dni, $name, $locationName, $totalInsertedCount, $totalSkippedDuplicateCount, $totalSkippedInvalidDataCount);
                }
            }

            // --- PROCESAR HOJA "OTROS" ---
            $otrosSheet = $spreadsheet->getSheetByName('OTROS');
            if ($otrosSheet !== null) {
                $highestRow = $otrosSheet->getHighestRow();
                for ($row = 6; $row <= $highestRow; $row++) {
                    $totalProcessedRows++;
                    $name = $otrosSheet->getCell('B' . $row)->getValue();
                    $dni = $otrosSheet->getCell('C' . $row)->getValue();
                    $locationName = $otrosSheet->getCell('A' . $row)->getValue();

                    $this->processRowData($dni, $name, $locationName, $totalInsertedCount, $totalSkippedDuplicateCount, $totalSkippedInvalidDataCount);
                }
            }

            if ($fiasaSheet === null && $otrosSheet === null) {
                throw new \Exception('El archivo no contiene una hoja llamada "FIASA" ni "OTROS".');
            }

            return response()->json([
                'message' => 'Proceso de importación completado.',
                'filas_procesadas' => $totalProcessedRows,
                'servicios_creados' => $totalInsertedCount,
                'servicios_saltados_por_duplicado' => $totalSkippedDuplicateCount,
                'servicios_saltados_por_datos_invalidos' => $totalSkippedInvalidDataCount
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Procesa una fila de datos, crea el servicio y asigna el rol.
     */
    private function processRowData($dni, $name, $locationName, &$insertedCount, &$skippedDuplicateCount, &$skippedInvalidDataCount)
    {
        $dni = str_replace('.', '', trim($dni));

        if (empty($dni) || empty($name)) {
            $skippedInvalidDataCount++;
            return;
        }

        if (FiasaUser::where('dni', $dni)->exists()) {
            $skippedDuplicateCount++;
            return;
        }

        $nameParts = explode(' ', trim($name));
        $numParts = count($nameParts);
        $email = null;

        if ($numParts >= 2) {
            $lastName = strtolower($nameParts[0]);
            $firstName = ($numParts >= 3) ? strtolower($nameParts[2]) : strtolower($nameParts[1]);

            $baseEmail = $firstName . '.' . $lastName . '@iniap.gob.ec';
            $email = $baseEmail;
            $counter = 1;
            while (FiasaUser::where('email', $email)->exists()) {
                $counter++;
                $email = $firstName . '.' . $lastName . $counter . '@iniap.gob.ec';
            }
        }

        if (is_null($email)) {
            $skippedInvalidDataCount++;
            return;
        }

        $data = [
            'dni' => $dni,
            'name' => trim($name),
            'email' => $email,
            'password' => Hash::make($dni),
        ];

        if (!empty($locationName)) {
            $location = Location::firstOrCreate(['name' => trim($locationName)]);
            $data['location_id'] = $location->id;
        }

        $newService = FiasaUser::create($data);
        $newService->assignRole('user'); // <-- ASIGNACIÓN DEL ROL
        $insertedCount++;
    }
}
