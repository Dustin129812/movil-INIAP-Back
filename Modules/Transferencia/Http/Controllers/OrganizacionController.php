<?php

namespace Modules\Transferencia\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Modules\Transferencia\Http\Requests\OrganizacionRequest;
use Modules\Transferencia\Services\OrganizacionService;
use Modules\Transferencia\Transformers\OrganizacionResource;
use Modules\Transferencia\Entities\Organizacion;

class OrganizacionController extends Controller
{
    public function __construct(
        private readonly OrganizacionService $organizacionService
    ) {}

    public function index(OrganizacionRequest $request): AnonymousResourceCollection
    {
        $organizaciones = $this->organizacionService->paginate($request->validated());

        return OrganizacionResource::collection($organizaciones);
    }

    public function store(OrganizacionRequest $request): OrganizacionResource
    {
        $organizacion = $this->organizacionService->create($request->validated());


        return new OrganizacionResource($organizacion);
    }

    public function show(OrganizacionRequest $request, Organizacion $organizacion): OrganizacionResource
    {
        $organizacion->load(['provincia', 'canton', 'parroquia']);
        return new OrganizacionResource($organizacion);
    }

    public function update(OrganizacionRequest $request, Organizacion $organizacion): OrganizacionResource
    {
        $organizacionActualizada = $this->organizacionService->update($organizacion, $request->validated());

        return new OrganizacionResource($organizacionActualizada);
    }

    public function destroy(OrganizacionRequest $request, Organizacion $organizacion): JsonResponse
    {
        $this->organizacionService->delete($organizacion);

        return response()->json(['message' => 'Organización eliminada correctamente']);
    }
}
