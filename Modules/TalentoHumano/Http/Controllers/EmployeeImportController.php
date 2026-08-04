<?php

namespace Modules\TalentoHumano\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User; // Main User Model
use Modules\TalentoHumano\Entities\Process;
use Modules\TalentoHumano\Entities\AdministrativeUnit;
use Modules\TalentoHumano\Entities\Management;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmployeeImportController extends Controller
{
    public function updateOrganizationalStructure(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        DB::beginTransaction();
        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            $stats = ['updated' => 0, 'skipped_not_found' => 0];

            // --- COLUMN MAPPING (Based on your CSV) ---
            $colDni        = 'H';
            $colProcess    = 'AE';
            $colAdminUnit  = 'AF';
            $colManagement = 'AG';

            foreach ($rows as $rowIndex => $row) {
                // Skip headers (Rows 1-5 usually)
                if ($rowIndex <= 5) continue;

                // Clean DNI
                $dni = $this->sanitizeDni($row[$colDni]);
                if (empty($dni)) continue;

                // 1. FIND USER (Critical: match existing data)
                $user = User::where('dni', $dni)->first();

                if ($user) {
                    // 2. PROCESS LOGIC
                    $processName = trim($row[$colProcess]);
                    if ($this->isValidValue($processName)) {
                        $process = Process::firstOrCreate(['name' => $processName]);
                        $user->th_process_id = $process->id;
                    }

                    // 3. ADMINISTRATIVE UNIT LOGIC
                    $unitName = trim($row[$colAdminUnit]);
                    if ($this->isValidValue($unitName)) {
                        $unit = AdministrativeUnit::firstOrCreate(['name' => $unitName]);
                        $user->th_administrative_unit_id = $unit->id;
                    }

                    // 4. MANAGEMENT LOGIC
                    $managementName = trim($row[$colManagement]);
                    if ($this->isValidValue($managementName)) {
                        $management = Management::firstOrCreate(['name' => $managementName]);
                        $user->th_management_id = $management->id;
                    }

                    $user->save();
                    $stats['updated']++;
                } else {
                    $stats['skipped_not_found']++;
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Organizational structure updated successfully.',
                'details' => $stats
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error on row ' . $rowIndex . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper to clean DNI/Cedula
     */
    private function sanitizeDni($value)
    {
        // Remove non-numeric characters if strict numeric DNI is required
        // Or just trim if alphanumeric
        return trim($value);
    }

    /**
     * Helper to ignore empty cells or dashes
     */
    private function isValidValue($value)
    {
        return !empty($value) && $value !== '-' && strtolower($value) !== 'n/a';
    }
}
