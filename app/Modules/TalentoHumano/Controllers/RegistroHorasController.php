<?php

namespace App\Modules\TalentoHumano\HorasExtras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TalentoHumano\HorasExtras\Models\RegistroHora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Response;

class RegistroHorasController extends Controller
{
    /**
     * Devuelve los registros de la semana actual para el conductor autenticado.
     * Requerimiento: "Revisar la semana y editar en caso de equivocación"
     */
    public function getSemanaActual(Request $request)
    {
        $user = Auth::user();
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $registros = RegistroHora::where('user_id', $user->id)
            ->whereBetween('fecha', [$startOfWeek, $endOfWeek])
            ->with('vehiculo:id,placa') // Solo trae la placa
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json($registros);
    }

    /**
     * Muestra un registro específico para edición.
     */
    public function show(RegistroHora $registro)
    {
        // Política de seguridad: Asegurar que el usuario solo vea sus propios registros
        if ($registro->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        // Requerimiento: Solo se puede editar si está en estado 'registrado'
        if ($registro->estado !== 'registrado') {
            return response()->json(['error' => 'Este registro ya ha sido procesado y no puede editarse.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json($registro);
    }

    /**
     * Almacena un nuevo registro de horas extras.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // --- 1. Validación de Datos (incluye reglas de negocio) ---
        $validator = Validator::make($request->all(), [
            'fecha' => 'required|date|before_or_equal:today',
            'vehiculo_id' => 'required|integer',
            'horas_suplementarias' => 'required|numeric|min:0',
            'horas_extraordinarias' => 'required|numeric|min:0',
            'descripcion_actividad' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $fecha = Carbon::parse($request->fecha)->startOfDay();
        $totalHorasNuevas = (float)$request->horas_suplementarias + (float)$request->horas_extraordinarias;

        if ($totalHorasNuevas <= 0) {
            return response()->json(['errors' => ['horas_suplementarias' => ['El total de horas debe ser mayor a 0.']]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // --- 2. Reglas de Negocio Específicas ---

        // Regla 1: Máximo 2 días para registrar (ej. hoy es 29, puede registrar 29, 28. No 27)
        $limiteRegistro = Carbon::now()->subDays(2)->startOfDay();
        if ($fecha->isBefore($limiteRegistro)) {
            return response()->json(['errors' => ['fecha' => ['No puede registrar horas con más de 2 días de antigüedad.']]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Regla 2: El vehículo debe estar asignado al conductor
        if (!$user->vehiculos()->where('vehiculo_id', $request->vehiculo_id)->exists()) {
            return response()->json(['errors' => ['vehiculo_id' => ['Esta placa no está asignada a su usuario.']]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Regla 3: Límite diario de 4 horas
        $horasYaRegistradasDia = RegistroHora::where('user_id', $user->id)
            ->where('fecha', $fecha->toDateString())
            ->sum(DB::raw('horas_suplementarias + horas_extraordinarias'));

        if (($horasYaRegistradasDia + $totalHorasNuevas) > 4) {
            return response()->json(['errors' => ['horas_suplementarias' => ['Límite diario de 4 horas excedido. Ya tiene ' . $horasYaRegistradasDia . ' horas registradas este día.']]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Regla 4: Límite semanal de 12 horas
        $startOfWeek = $fecha->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $fecha->copy()->endOfWeek(Carbon::SUNDAY);
        $horasYaRegistradasSemana = RegistroHora::where('user_id', $user->id)
            ->whereBetween('fecha', [$startOfWeek, $endOfWeek])
            ->sum(DB::raw('horas_suplementarias + horas_extraordinarias'));

        if (($horasYaRegistradasSemana + $totalHorasNuevas) > 12) {
            return response()->json(['errors' => ['horas_suplementarias' => ['Límite semanal de 12 horas excedido. Ya tiene ' . $horasYaRegistradasSemana . ' horas registradas esta semana.']]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // --- 3. Creación del Registro ---
        try {
            $registro = RegistroHora::create([
                'user_id' => $user->id,
                'fecha' => $fecha,
                'vehiculo_id' => $request->vehiculo_id,
                'horas_suplementarias' => $request->horas_suplementarias,
                'horas_extraordinarias' => $request->horas_extraordinarias,
                'descripcion_actividad' => $request->descripcion_actividad,
                'fecha_limite_registro' => $fecha->copy()->addDays(2)->endOfDay(), // Límite para editar
                'estado' => 'registrado',
            ]);

            return response()->json($registro, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error interno al guardar el registro.', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualiza un registro de horas existente.
     */
    public function update(Request $request, RegistroHora $registro)
    {
        $user = Auth::user();

        // --- 1. Autorización y Reglas de Estado ---
        if ($registro->user_id !== $user->id) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        if ($registro->estado !== 'registrado') {
            return response()->json(['error' => 'Este registro ya ha sido procesado y no puede modificarse.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // (Opcional) Re-validar el tiempo límite de edición
        // if (Carbon::now()->isAfter($registro->fecha_limite_registro)) {
        //     return response()->json(['error' => 'El tiempo para editar este registro ha expirado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        // }

        // --- 2. Validación de Datos ---
        $validator = Validator::make($request->all(), [
            'fecha' => 'required|date|before_or_equal:today',
            'vehiculo_id' => 'required|integer',
            'horas_suplementarias' => 'required|numeric|min:0',
            'horas_extraordinarias' => 'required|numeric|min:0',
            'descripcion_actividad' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $fecha = Carbon::parse($request->fecha)->startOfDay();
        $totalHorasNuevas = (float)$request->horas_suplementarias + (float)$request->horas_extraordinarias;

        // --- 3. Reglas de Negocio (excluyendo el registro actual de los cálculos) ---

        // Regla 1: Límite diario (4h)
        $horasYaRegistradasDia = RegistroHora::where('user_id', $user->id)
            ->where('fecha', $fecha->toDateString())
            ->where('id', '!=', $registro->id) // Excluye el registro actual
            ->sum(DB::raw('horas_suplementarias + horas_extraordinarias'));

        if (($horasYaRegistradasDia + $totalHorasNuevas) > 4) {
            return response()->json(['errors' => ['horas_suplementarias' => ['Límite diario de 4 horas excedido.']]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Regla 2: Límite semanal (12h)
        $startOfWeek = $fecha->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $fecha->copy()->endOfWeek(Carbon::SUNDAY);
        $horasYaRegistradasSemana = RegistroHora::where('user_id', $user->id)
            ->whereBetween('fecha', [$startOfWeek, $endOfWeek])
            ->where('id', '!=', $registro->id) // Excluye el registro actual
            ->sum(DB::raw('horas_suplementarias + horas_extraordinarias'));

        if (($horasYaRegistradasSemana + $totalHorasNuevas) > 12) {
            return response()->json(['errors' => ['horas_suplementarias' => ['Límite semanal de 12 horas excedido.']]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // --- 4. Actualización ---
        $registro->update($request->all());
        return response()->json($registro);
    }

    /**
     * Elimina un registro de horas.
     */
    public function destroy(RegistroHora $registro)
    {
        // --- 1. Autorización y Reglas de Estado ---
        if ($registro->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        if ($registro->estado !== 'registrado') {
            return response()->json(['error' => 'Este registro ya ha sido procesado y no puede eliminarse.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // --- 2. Eliminación ---
        $registro->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
