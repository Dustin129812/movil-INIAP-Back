<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Rutas del Módulo de Talento Humano (TH)
|--------------------------------------------------------------------------
|
| Prefijo: /api/v1/th (definido en RouteServiceProvider)
| Middleware: 'auth:api' (definido en RouteServiceProvider)
| Namespace: 'App\Modules\TalentoHumano' (definido en RouteServiceProvider)
|
*/

// --- RUTAS DE "HORAS EXTRAS" ---

// === 1. Rutas para el Conductor ===
// (Permiso: th.horas_extras.registrar)
Route::group([
    'prefix' => 'horas-extras',
    'middleware' => ['permission:th.horas_extras.registrar']
], function () {
    // GET /api/v1/th/horas-extras/semana (Trae los registros de la semana para editar/revisar)
    Route::get('/semana', 'HorasExtras\Controllers\RegistroHorasController@getSemanaActual');

    // GET /api/v1/th/horas-extras/vehiculos (Trae solo las placas asignadas al conductor)
    Route::get('/vehiculos', 'Shared\Controllers\VehiculoController@getMisVehiculos');

    // POST /api/v1/th/horas-extras (Guarda un nuevo registro de hora)
    Route::post('/', 'HorasExtras\Controllers\RegistroHorasController@store');

    // GET /api/v1/th/horas-extras/{registro} (Obtiene un registro específico para editar)
    Route::get('/{registro}', 'HorasExtras\Controllers\RegistroHorasController@show');

    // PUT /api/v1/th/horas-extras/{registro} (Actualiza un registro)
    Route::put('/{registro}', 'HorasExtras\Controllers\RegistroHorasController@update');

    // DELETE /api/v1/th/horas-extras/{registro} (Elimina un registro)
    Route::delete('/{registro}', 'HorasExtras\Controllers\RegistroHorasController@destroy');
});


// === 2. Rutas para el Jefe Inmediato (Vladimir) ===
// (Permiso: th.horas_extras.revisar)
Route::group([
    'prefix' => 'aprobaciones',
    'middleware' => ['permission:th.horas_extras.revisar']
], function () {
    // GET /api/v1/th/aprobaciones/pendientes-jefe (Dashboard del Jefe)
    Route::get('/pendientes-jefe', 'HorasExtras\Controllers\AprobacionController@getPendientesJefe');

    // GET /api/v1/th/aprobaciones/reporte/{reporte}/registros (Detalle de un reporte)
    Route::get('/reporte/{reporte}/registros', 'HorasExtras\Controllers\AprobacionController@getRegistrosDelReporte');

    // POST /api/v1/th/aprobaciones/reporte/{reporte}/aprobar-jefe (Acción de aprobar)
    Route::post('/reporte/{reporte}/aprobar-jefe', 'HorasExtras\Controllers\AprobacionController@aprobarJefe');

    // POST /api/v1/th/aprobaciones/reporte/{reporte}/rechazar-jefe (Acción de rechazar)
    Route::post('/reporte/{reporte}/rechazar-jefe', 'HorasExtras\Controllers\AprobacionController@rechazarJefe');
});


// === 3. Rutas para DAF (Majo) ===
// (Permiso: th.horas_extras.aprobar_daf)
Route::group([
    'prefix' => 'aprobaciones',
    'middleware' => ['permission:th.horas_extras.aprobar_daf']
], function () {
    // GET /api/v1/th/aprobaciones/pendientes-daf (Dashboard de DAF)
    Route::get('/pendientes-daf', 'HorasExtras\Controllers\AprobacionController@getPendientesDAF');

    // POST /api/v1/th/aprobaciones/reporte/{reporte}/aprobar-daf (Acción de aprobar DAF)
    Route::post('/reporte/{reporte}/aprobar-daf', 'HorasExtras\Controllers\AprobacionController@aprobarDAF');

    // POST /api/v1/th/aprobaciones/reporte/{reporte}/rechazar-daf (Acción de rechazar DAF)
    Route::post('/reporte/{reporte}/rechazar-daf', 'HorasExtras\Controllers\AprobacionController@rechazarDAF');

    // POST /api/v1/th/aprobaciones/generar-reportes-mes
    Route::post('/generar-reportes-mes', 'HorasExtras\Controllers\AprobacionController@generarReportesMensuales');
});


// === 4. Rutas de Reportes y Alertas (DTH) ===
// (Permiso: th.reportes.ver)
Route::group([
    'prefix' => 'reportes',
    'middleware' => ['permission:th.reportes.ver']
], function () {
    // GET /api/v1/th/reportes/individual (PDF/Excel mensual CON firmas)
    Route::get('/individual', 'HorasExtras\Controllers\ReporteHorasController@generarIndividual');

    // GET /api/v1/th/reportes/rango (PDF/Excel por rango SIN firmas)
    Route::get('/rango', 'HorasExtras\Controllers\ReporteHorasController@generarPorRango');

    // GET /api/v1/th/reportes/resumen-pago (Total pagado en $$ y horas)
    Route::get('/resumen-pago', 'HorasExtras\Controllers\ReporteHorasController@generarResumenPago');
});

// (Permiso: th.alertas.ver)
Route::group([
    'prefix' => 'alertas',
    'middleware' => ['permission:th.alertas.ver']
], function () {
    // GET /api/v1/th/alertas/pendientes-registro (Alerta: Quién no puso horas)
    Route::get('/pendientes-registro', 'Shared\Controllers\DashboardController@getAlertasPendientesRegistro');

    // GET /api/v1/th/alertas/aprobados-daf (Alerta: Reportes listos aprobados por DAF)
    Route::get('/aprobados-daf', 'Shared\Controllers\DashboardController@getAlertasAprobadosDAF');
});
