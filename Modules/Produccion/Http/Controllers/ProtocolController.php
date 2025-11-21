<?php

namespace Modules\Produccion\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Produccion\Entities\ProdProtocol;
use Modules\Produccion\Entities\ProdProtocolDetail;
use Modules\Produccion\Entities\ProtocolDetail;

class ProtocolController extends Controller
{
    // Listar protocolos para que el usuario elija cuál usar
    public function index()
    {
        return response()->json(
            ProdProtocol::with('variety')->where('is_active', true)->get()
        );
    }

    // Mostrar una receta completa (útil para cuando le das click a "Ver detalle")
    public function show($id)
    {
        $protocol = ProdProtocol::with(['variety', 'details.inventoryProduct', 'details.inventoryMachinery'])
            ->findOrFail($id);

        return response()->json($protocol);
    }

    /**
     * GUARDAR LA RECETA MAESTRA
     * Recibe un JSON con la cabecera y un array 'activities' con las filas del Excel.
     */
    public function store(Request $request)
    {
        // 1. Validación básica de cabecera
        $request->validate([
            'variety_id' => 'required|exists:prod_varieties,id',
            'name' => 'required|string',
            'estimated_days' => 'required|integer',
            'base_quantity' => 'required|integer', // Ej: 10000
            'activities' => 'required|array|min:1' // Las filas del excel
        ]);

        DB::beginTransaction(); // Importante: Todo o nada

        try {
            // A. Crear Cabecera
            $protocol = ProdProtocol::create([
                'variety_id' => $request->variety_id,
                'name' => $request->name,
                'estimated_days' => $request->estimated_days,
                'base_quantity' => $request->base_quantity,
                'description' => $request->description ?? null
            ]);

            // B. Procesar las Actividades (Filas del Excel)
            foreach ($request->activities as $row) {

                // Preparamos los datos base
                $detailData = [
                    'protocol_id' => $protocol->id,
                    'stage' => $row['stage'], // Ej: "1. Adquisición de insumos"
                    'task' => $row['task'],   // Ej: "Riego"
                    'day_start' => $row['day_start'],
                    'day_end' => $row['day_end'] ?? null,
                    'resource_type' => $row['resource_type'], // PRODUCT, MACHINERY, LABOR
                    'quantity' => $row['quantity'], // Cantidad teórica
                    'reference_unit_cost' => $row['reference_unit_cost'] ?? 0,
                ];

                // C. Vincular con Inventario según el tipo
                if ($row['resource_type'] === 'PRODUCT') {
                    // Es un insumo (Fertilizante)
                    $detailData['inv_product_id'] = $row['resource_id']; // ID de inv_products
                    $detailData['resource_name'] = null; // Ya tenemos el nombre en la tabla productos

                } elseif ($row['resource_type'] === 'MACHINERY') {
                    // Es un activo (Bomba)
                    $detailData['inv_machinery_id'] = $row['resource_id']; // ID de inv_machinery
                    $detailData['resource_name'] = null;

                } else {
                    // Es MANO DE OBRA (LABOR) o SERVICIO
                    // Aquí guardamos el nombre texto porque no hay ID de inventario
                    $detailData['resource_name'] = $row['resource_name']; // Ej: "Técnico Agropecuario"
                }

                ProtocolDetail::create($detailData);
            }

            DB::commit();
            return response()->json(['message' => 'Protocolo guardado correctamente', 'id' => $protocol->id], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error guardando protocolo: ' . $e->getMessage()], 500);
        }
    }
}
