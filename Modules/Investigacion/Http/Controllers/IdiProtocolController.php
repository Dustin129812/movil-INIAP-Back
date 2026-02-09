<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Models\Crops;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\ResearchArea;
use Modules\Investigacion\Http\Requests\StoreIdiProtocolRequest;
use Modules\Investigacion\Entities\IdiProtocol;
use App\Models\User;

class IdiProtocolController extends Controller
{

    /**
     * GET /investigacion/protocols
     * Lista paginada para la tabla principal
     */
    public function index()
    {
        $protocols = IdiProtocol::with(['responsible:id,name', 'station:id,name'])
            ->latest()
            ->paginate(10);

        return response()->json($protocols);
    }

    /**
     * GET /investigacion/catalogs/all
     * Carga todos los selects del Modal Wizard
     */
    public function catalogs()
    {
        try {
            $data = [
                // Selects simples: solo ID y Nombre para no pesar la red
                'stations' => Location::select('id', 'name')->get(),

                'users'    => User::select('id', 'name', 'dni')->orderBy('name')->get(),

                // Relaciones anidadas para lógica dependiente
                'crops'    => Crops::with('productiveRubro:id,name')
                    ->select('id', 'name', 'productive_rubro_id')
                    ->orderBy('name')
                    ->get(),

                'areas'    => ResearchArea::with('lines:id,research_area_id,name')
                    ->select('id', 'name')
                    ->get(),

                'cantons'  => Canton::select('id', 'name')->orderBy('name')->get(),
            ];

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar catálogos: ' . $e->getMessage()], 500);
        }
    }
    /**
     * POST /investigacion/protocols
     * Guarda el nuevo protocolo
     */
    public function store(StoreIdiProtocolRequest $request)
    {
        try {
            // Usamos transacción por si falla el guardado de colaboradores, no quede el protocolo huérfano
            $protocol = DB::transaction(function () use ($request) {

                // 1. Crear el registro principal
                $newProtocol = IdiProtocol::create($request->validated());

                // 2. Guardar relaciones Many-to-Many (Pivotes)
                if ($request->has('collaborator_ids')) {
                    $newProtocol->collaborators()->sync($request->collaborator_ids);
                }

                if ($request->has('canton_ids')) {
                    $newProtocol->influenceCantons()->sync($request->canton_ids);
                }

                return $newProtocol;
            });

            return response()->json([
                'message' => 'Protocolo creado exitosamente',
                'data' => $protocol
            ], 201); // 201 = Created

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al guardar el protocolo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /investigacion/protocols/{id}
     * Para ver el detalle o editar (carga relaciones completas)
     */
    public function show($id)
    {
        $protocol = IdiProtocol::with([
            'responsible',
            'station',
            'researchLine.area', // Para saber el área al editar
            'crop',
            'collaborators',
            'influenceCantons'
        ])->findOrFail($id);

        return response()->json($protocol);
    }

    /**
     * DELETE /investigacion/protocols/{id}
     */
    public function destroy($id)
    {
        $protocol = IdiProtocol::findOrFail($id);
        $protocol->delete(); // Soft delete

        return response()->json(['message' => 'Protocolo eliminado correctamente']);
    }
}
