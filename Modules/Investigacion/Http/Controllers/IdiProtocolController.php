<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Models\Crops;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\ResearchArea;
use Modules\Investigacion\Entities\ProtocolAnnex;
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
     */
    public function store(StoreIdiProtocolRequest $request)
    {
        try {
            $protocol = DB::transaction(function () use ($request) {

                $data = $request->except(['annexes', 'canton_ids', 'collaborator_ids']);
                $newProtocol = IdiProtocol::create($data);

                if ($request->has('collaborator_ids')) {
                    $newProtocol->collaborators()->sync($request->collaborator_ids);
                }
                if ($request->has('canton_ids')) {
                    $newProtocol->influenceCantons()->sync($request->canton_ids);
                }

                if ($request->hasFile('annexes')) {
                    foreach ($request->file('annexes') as $file) {

                        $uuid = Str::uuid();
                        $extension = $file->getClientOriginalExtension();
                        $storedName = "{$newProtocol->id}/{$uuid}.{$extension}";

                        $path = $file->storeAs(
                            '',
                            $storedName,
                            'protocol_evidences'
                        );

                        ProtocolAnnex::create([
                            'protocol_id' => $newProtocol->id,
                            'file_name'   => $file->getClientOriginalName(),
                            'file_path'   => $storedName,
                            'file_type'   => $file->getMimeType(),
                            'file_size'   => $file->getSize(),
                        ]);
                    }
                }

                return $newProtocol;
            });

            return response()->json([
                'message' => 'Protocolo creado exitosamente con anexos',
                'data' => $protocol
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al guardar el protocolo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /investigacion/protocols/{id}
     */
    public function show($id)
    {
        $protocol = IdiProtocol::with([
            'responsible',
            'station',
            'researchLine.area',
            'crop',
            'collaborators',
            'influenceCantons',
            'annexes' // <--- AHORA INCLUIMOS LOS ANEXOS
        ])->findOrFail($id);

        return response()->json($protocol);
    }

    /**
     * GET /investigacion/protocols/download/{annexId}
     * Nueva función para descargar archivos seguros
     */
    public function downloadAnnex($annexId)
    {
        try {
            $annex = ProtocolAnnex::findOrFail($annexId);

            if (!Storage::disk('protocol_evidences')->exists($annex->file_path)) {
                return response()->json(['message' => 'El archivo físico no se encuentra'], 404);
            }

            return Storage::disk('protocol_evidences')->download($annex->file_path, $annex->file_name);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al descargar: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $protocol = IdiProtocol::findOrFail($id);
        $protocol->delete();
        return response()->json(['message' => 'Protocolo eliminado correctamente']);
    }
}
