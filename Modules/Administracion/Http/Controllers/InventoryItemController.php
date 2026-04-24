<?php

namespace Modules\Administracion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Administracion\Entities\InventoryItem;
use Modules\Administracion\Http\Requests\StoreInventoryItemRequest;
use Modules\Administracion\Http\Requests\UpdateInventoryItemRequest;
use Modules\Administracion\Services\InventoryItemService;
use Modules\Administracion\Transformers\InventoryItemResource;

class InventoryItemController extends Controller
{
    public function __construct(
        private readonly InventoryItemService $inventoryService
    ) {}

    /**
     * Lista el catálogo con filtros por categoría y búsqueda profunda.
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryItem::query();

        if ($request->has('type') && in_array($request->type, ['insumo', 'vehiculo', 'equipo'])) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'ilike', "%{$searchTerm}%")
                    ->orWhere('sku', 'ilike', "%{$searchTerm}%")
                    ->orWhereRaw("attributes::text ilike ?", ["%{$searchTerm}%"]);
            });
        }

        $items = $query->where('is_active', true)
            ->orderBy('name', 'asc')
            ->paginate(15);

        return response()->json([
            'data' => InventoryItemResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ]
        ]);
    }

    /**
     * Almacena un nuevo ítem en el Catálogo Maestro.
     */
    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $item = $this->inventoryService->createItem($request->validated());

        return response()->json([
            'msg' => [
                'summary' => 'Ítem registrado',
                'detail' => "El {$item->type} '{$item->name}' fue añadido al catálogo.",
                'code' => 201,
            ],
            'data' => new InventoryItemResource($item)
        ], 201);
    }

    /**
     * Actualiza un ítem específico.
     */
    public function update(UpdateInventoryItemRequest $request, $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);

        $updatedItem = $this->inventoryService->updateItem($item, $request->validated());

        return response()->json([
            'msg' => [
                'summary' => 'Ítem actualizado',
                'detail' => 'El catálogo ha sido modificado correctamente.',
                'code' => 200,
            ],
            'data' => new InventoryItemResource($updatedItem)
        ]);
    }

    /**
     * Elimina un ítem del catálogo.
     */
    public function destroy($id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);

        try {
            $this->inventoryService->deleteItem($item);

            return response()->json([
                'msg' => [
                    'summary' => 'Ítem eliminado',
                    'detail' => 'El registro fue borrado permanentemente.',
                    'code' => 200,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'msg' => [
                    'summary' => 'Operación denegada',
                    'detail' => $e->getMessage(),
                    'code' => 409, // Conflict
                ]
            ], 409);
        }
    }
}
