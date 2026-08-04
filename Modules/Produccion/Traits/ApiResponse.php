<?php

namespace Modules\Produccion\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Respuesta exitosa genérica (200 OK)
     */
    protected function successResponse($data, string $message = 'Operación exitosa', int $code = 200): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data
        ], $code);
    }

    /**
     * Respuesta para creación de registros (201 Created)
     */
    protected function createdResponse($data, string $message = 'Registro creado con éxito'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Respuesta de Error controlada (400, 401, 403, 404, 422, 500)
     */
    protected function errorResponse(string $message, int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'status'  => 'error',
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
