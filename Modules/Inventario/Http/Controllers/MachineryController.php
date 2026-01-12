<?php

namespace Modules\Inventario\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Inventario\Entities\Machinery;
use Modules\Inventario\Http\Requests\StoreToolRequest;
use Modules\Inventario\Http\Requests\StoreVehicleRequest;
use Modules\Inventario\Services\CostCalculationService;

class MachineryController extends Controller
{
    protected $calculator;

    public function __construct(CostCalculationService $calculator)
    {
        $this->calculator = $calculator;
    }

    public function index()
    {
        return response()->json(
            Machinery::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->paginate(15)
        );
    }

    public function store(Request $request)
    {
        // 1. Validar Tipo Inicialmente
        $request->validate(['type' => 'required|in:TOOL,VEHICLE']);

        $data = [];

        // 2. Bifurcación de Lógica (Strategy Pattern simple)
        if ($request->type === 'TOOL') {
            // A. Validar como Herramienta
            $req = app(StoreToolRequest::class);
            $validated = $req->validated();

            // Preparar datos: Guardamos 8 y 20 en el JSON explícitamente
            // Esto es vital para saber en el futuro con qué fórmula se calculó
            $hours = $request->input('cost_parameters.hours_per_day', 8);
            $days = $request->input('cost_parameters.days_per_month', 20);

            $data = [
                'name' => $validated['name'],
                'type' => 'TOOL',
                'acquisition_cost' => $validated['acquisition_cost'],
                'acquisition_year' => $validated['acquisition_year'],
                'useful_life_years' => $validated['useful_life_years'],
                'cost_parameters' => [
                    'hours_per_day' => $hours,
                    'days_per_month' => $days
                ],
                // Cálculo referencial de horas anuales (no afecta precio hora, pero sirve para reportes)
                'annual_usage_hours' => ($hours * $days) * 12
            ];

        } else {
            // B. Validar como Vehículo
            $req = app(StoreVehicleRequest::class);
            $validated = $req->validated();

            // Vehículo pasa directo porque el Request ya validó toda la estructura JSON
            $data = $validated;
        }

        // 3. Crear Registro
        $machine = Machinery::create($data);

        // 4. Calcular Costo Hora Inmediato
        $this->calculator->updateHourlyCost($machine);

        return response()->json($machine, 201);
    }

    public function update(Request $request, $id)
    {
        $machine = Machinery::findOrFail($id);

        // Para el update, usamos el tipo que ya tiene la máquina (no permitimos cambiar tipo fácilmente)
        $type = $machine->type;

        if ($type === 'TOOL') {
            $req = app(StoreToolRequest::class);
        } else {
            $req = app(StoreVehicleRequest::class);
        }

        // Validamos los datos entrantes
        $validated = $req->validated();

        // Si es herramienta, reconstruimos el cost_parameters para no perder los defaults
        if ($type === 'TOOL') {
            $hours = $request->input('cost_parameters.hours_per_day', $machine->cost_parameters['hours_per_day'] ?? 8);
            $days = $request->input('cost_parameters.days_per_month', $machine->cost_parameters['days_per_month'] ?? 20);

            $validated['cost_parameters'] = [
                'hours_per_day' => $hours,
                'days_per_month' => $days
            ];
            $validated['annual_usage_hours'] = ($hours * $days) * 12;
        }

        $machine->fill($validated);
        $machine->save();

        // Recalcular
        $this->calculator->updateHourlyCost($machine);

        return response()->json($machine);
    }

    public function destroy($id)
    {
        $machine = Machinery::findOrFail($id);
        $machine->update(['is_active' => false]); // Soft delete lógico
        return response()->json(['message' => 'Equipo eliminado correctamente']);
    }
}
