<?php

namespace Modules\Produccion\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Exception;

use Illuminate\Support\Str;
use Modules\Produccion\Entities\LibroCampo;
use Modules\Produccion\Http\Requests\LibroCampo\CosecharLibroRequest;
use Modules\Produccion\Http\Requests\LibroCampo\RegistrarClimaRequest;
use Modules\Produccion\Http\Requests\LibroCampo\RegistrarLaborRequest;
use Modules\Produccion\Http\Requests\LibroCampo\RegistrarPersonalRequest;
use Modules\Produccion\Services\ProduccionService;
use Modules\Produccion\Traits\ApiResponse;
use Modules\Produccion\Transformers\LibroCampoResource;

use Modules\Produccion\Http\Requests\LibroCampo\StoreLibroCampoRequest;
use Modules\Produccion\Http\Requests\LibroCampo\RegistrarMaquinariaRequest;

class LibroCampoController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProduccionService $produccionService
    ) {}

    public function index(): JsonResponse
    {
        try {
            $libros = LibroCampo::with(['lote', 'actividades'])->latest()->paginate(15);
            return LibroCampoResource::collection($libros)->response();
        } catch (Exception $e) {
            return $this->errorResponse('Error al cargar los libros de campo', 500);
        }
    }

    public function store(StoreLibroCampoRequest $request): JsonResponse
    {
        try {
            // El request ya viene validado y sanitizado
            $libro = $this->produccionService->crearLibroCampo($request->validated());

            return $this->createdResponse(new LibroCampoResource($libro->load('lote')), 'Libro de Campo abierto correctamente.');
        } catch (Exception $e) {
            return $this->errorResponse('Error al crear el libro de campo', 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $libro = LibroCampo::with([
                'lote',
                'actividades',
                'actividadesPersonal',
                'actividadesMaquinaria'
            ])->findOrFail($id);

            return $this->successResponse(new LibroCampoResource($libro));
        } catch (Exception $e) {
            return $this->errorResponse('Libro de campo no encontrado.', 404);
        }
    }

    public function registrarLabor(RegistrarLaborRequest $request): JsonResponse
    {
        try {
            $actividad = $this->produccionService->registrarLaborConInsumo($request->validated());
            return $this->createdResponse($actividad, 'Labor registrada y costo cargado al libro de campo.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function registrarPersonal(RegistrarPersonalRequest $request): JsonResponse
    {
        try {
            $actividad = $this->produccionService->registrarTrabajoPersonal($request->validated());
            return $this->createdResponse($actividad, 'Jornal registrado y cargado al costo del lote.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function registrarMaquinaria(RegistrarMaquinariaRequest $request): JsonResponse
    {
        try {
            $actividad = $this->produccionService->registrarUsoMaquinaria($request->validated());
            return $this->createdResponse($actividad, 'Uso de maquinaria registrado exitosamente.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function cosechar(CosecharLibroRequest $request, $id): JsonResponse
    {
        try {
            $libro = LibroCampo::findOrFail($id);
            $resultado = $this->produccionService->registrarCosechaYCerrar($libro, $request->validated());

            return $this->successResponse($resultado, 'Cosecha registrada y libro cerrado con éxito.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function buscarPorQr($token): JsonResponse
    {
        if (!Str::isUuid($token)) {
            return $this->errorResponse('Código QR inválido o de formato antiguo.', 400);
        }

        $libro = LibroCampo::where('qr_token', $token)
            ->with(['lote'])
            ->first();

        if (!$libro) {
            return $this->errorResponse('Código QR de Libro de Campo inválido.', 404);
        }

        return $this->successResponse(new LibroCampoResource($libro), 'Contexto cargado');
    }

    public function regenerarQr($id): JsonResponse
    {
        try {
            $libro = $this->produccionService->regenerarQrLibro($id);

            return $this->successResponse(
                new LibroCampoResource($libro->load('lote')),
                'QR regenerado exitosamente.'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function registrarClima(RegistrarClimaRequest $request): JsonResponse
    {
        try {
            $registro = $this->produccionService->registrarCondicionesClimaticas($request->validated());
            return $this->createdResponse($registro, 'Datos climáticos registrados para el ensayo.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
