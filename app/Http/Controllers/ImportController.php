<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Area;
use App\Models\Ethnic_Group;
use App\Models\Location;
use App\Models\Nationality;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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

            for ($row = 6; $row <= $highestRow; $row++) {
                $data['name'] = $sheet->getCell('D' . $row)->getValue();
                $data['dni'] = $sheet->getCell('H' . $row)->getValue();
                $data['gender'] = $sheet->getCell('I' . $row)->getValue();
                $data['email'] = strtolower($sheet->getCell('AA' . $row)->getValue());
                $data['phone'] = strtolower($sheet->getCell('Y' . $row)->getValue());
                $birthDateValue = $sheet->getCell('J' . $row)->getValue();
                $data['password'] = Hash::make($sheet->getCell('H' . $row)->getValue());
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

                $existingUser = User::where('dni', $data['dni'])->first();
                if (!$existingUser) {
                    $newUser = User::create($data);
                    $newUser->assignRole('user');
                }

            }
            return response()->json(['message' => 'Datos insertados con éxito'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function importProcessedData(Request $request)
    {
        DB::beginTransaction();

        try {
            $dataToInsert = $request->json()->all();
            $userLocation = auth()->user()->location; // Asumiendo que el usuario que importa tiene una ubicación

            foreach ($dataToInsert as $item) {
                if ($item['_level'] === 'Producto') {
                    // Crear el producto
                    $product = new Product();
                    $product->name = $item['nombre_producto'] ?? null; // Ajusta los nombres de los campos según tu mapeo
                    $product->budget = $item['budget_producto'] ?? null;
                    $product->ponderacion = $item['ponderacion_producto'] ?? null;
                    $product->user_id = $item['user_id_producto'] ?? null; // Asegúrate de que el ID del usuario esté presente en tus datos mapeados
                    $product->rubro_id = $item['rubro_id_producto'] ?? null;
                    $product->location_id = $userLocation->id ?? null; // Usa la ubicación del usuario que importa
                    $product->save();

                    // Asignar rol al usuario (opcional, dependiendo de tu lógica)
                    if (isset($item['user_id_producto'])) {
                        $user = User::find($item['user_id_producto']);
                        if ($user) {
                            $user->assignRole('product-manager');
                        }
                    }

                    // Procesar actividades asociadas
                    if (isset($item['actividades']) && is_array($item['actividades'])) {
                        foreach ($item['actividades'] as $activityData) {
                            $activity = new Activity();
                            $activity->description = $activityData['nombre_actividad'] ?? null; // Ajusta los nombres de los campos
                            $activity->budget = $activityData['budget_actividad'] ?? null;
                            $activity->ponderacion = $activityData['ponderacion_actividad'] ?? null;
                            $activity->start_date = $activityData['fecha_inicio'] ? Carbon::parse($activityData['fecha_inicio']) : null;
                            $activity->end_date = $activityData['fecha_fin'] ? Carbon::parse($activityData['fecha_fin']) : null;
                            $activity->user_id = $activityData['user_id_actividad'] ?? null;
                            $activity->product_id = $product->id;
                            $activity->indicator_id = $activityData['indicator_id_actividad'] ?? null;
                            $activity->save();

                            // Guardar la distribución mensual (si la incluyes en tus datos procesados)
                            if (isset($activityData['monthly_distribution']) && is_array($activityData['monthly_distribution'])) {
                                foreach ($activityData['monthly_distribution'] as $monthData) {
                                    $activity->monthlyProgress()->create([
                                        'month' => Carbon::parse($monthData['month'])->startOfMonth(),
                                        'percentage' => $monthData['percentage'] ?? null,
                                    ]);
                                }
                            }
                        }
                    }
                }
                // Aquí podrías agregar lógica para otros niveles si los tienes
            }

            DB::commit();

            return response()->json([
                'msg' => [
                    'summary' => 'Success',
                    'detail' => 'Los datos del archivo Excel han sido importados correctamente',
                    'code' => 201,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al importar datos del Excel: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }

}
