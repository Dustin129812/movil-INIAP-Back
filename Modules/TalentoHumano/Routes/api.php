<?php

use Illuminate\Support\Facades\Route;
use Modules\TalentoHumano\Http\Controllers\Api\V1\AdminReportController;
use Modules\TalentoHumano\Http\Controllers\Api\V1\ApprovalDafController;
use Modules\TalentoHumano\Http\Controllers\Api\V1\ConfigController;
use Modules\TalentoHumano\Http\Controllers\Api\V1\DriverReportController;
use Modules\TalentoHumano\Http\Controllers\EmployeeImportController;
use Modules\TalentoHumano\Http\Controllers\PersonnelController;

// --- Importar todos los Controladores ---

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->prefix('v1/talento-humano')->name('api.th.v1.')->group(function () {

    // --- Rutas del Conductor (Rol: TH Conductor) ---
    Route::middleware('permission:th.horas_extras.registrar,api')->name('driver.')->group(function () {

        // Catálogos (Vehículos, Actividades) para el formulario
        Route::get('/form-data', [DriverReportController::class, 'getFormData'])->name('form-data');

        // Obtener lista de versiones (V1, V2...)
        Route::get('/reports/history', [DriverReportController::class, 'getMonthHistory'])->name('reports.history');

        // Reporte del mes actual
        Route::get('/reports/current', [DriverReportController::class, 'getCurrentReport'])->name('reports.current');


        // Ver un reporte específico por ID (Necesario para ver la V1 en solo lectura)
        Route::get('/reports/{report}', [DriverReportController::class, 'show'])->name('reports.show');

        // CRUD para las entradas (viajes)
        Route::post('/entries', [DriverReportController::class, 'storeEntry'])->name('entries.store');
        Route::put('/entries/{entry}', [DriverReportController::class, 'updateEntry'])->name('entries.update');
        Route::delete('/entries/{entry}', [DriverReportController::class, 'destroyEntry'])->name('entries.destroy');

        // Envío (Usa el mismo permiso 'registrar')
        Route::post('/reports/{report}/submit', [DriverReportController::class, 'submitReport'])->name('reports.submit');
    });

    // --- Rutas Generales (Permiso: th.view.module) ---
    Route::middleware('permission:th.view.module,api')->group(function () {
        // Ver un reporte específico
        Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');

        // Descargar el PDF de un reporte
        Route::get('/reports/{report}/download-pdf', [AdminReportController::class, 'downloadPdf'])->name('reports.download.pdf');
    });

    // --- Rutas del DAF (Rol: TH DAF) ---
    Route::middleware('permission:th.horas_extras.aprobar_daf,api')->prefix('approvals/daf')->name('daf.')->group(function () {
        // Reportes pendientes para DAF
        Route::get('/', [ApprovalDafController::class, 'index'])->name('index');
        Route::post('/{report}/approve', [ApprovalDafController::class, 'approve'])->name('approve');
        Route::post('/{report}/reject', [ApprovalDafController::class, 'reject'])->name('reject');
    });

    // --- Rutas de Admin (Rol: TH DTH) ---
    Route::middleware('permission:th.reportes.ver,api')->prefix('admin')->name('admin.')->group(function () {
        // Todos los reportes (para filtros, historial)
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');

        // Data para el dashboard
        Route::get('/analytics', [AdminReportController::class, 'getAnalytics'])->name('analytics');

        Route::post('/reports/{report}/finalize', [AdminReportController::class, 'finalize'])->name('reports.finalize');
        Route::get('/reports/{report}/download-payment-report', [AdminReportController::class, 'downloadPaymentReport'])->name('reports.download.payment');
    });

    // --- Rutas de Configuración (Rol: TH DTH) ---
    Route::middleware('permission:th.configuracion.gestionar,api')->prefix('config')->name('config.')->group(function () {

        // --- Gestión de Vehículos ---
        Route::get('/vehicles', [ConfigController::class, 'vehiclesIndex']);
        Route::post('/vehicles', [ConfigController::class, 'vehiclesStore']);
        Route::put('/vehicles/{vehicle}', [ConfigController::class, 'vehiclesUpdate']);

        // --- Gestión de Tipos de Actividad ---
        Route::get('/activity-types', [ConfigController::class, 'activitiesIndex']);
        Route::post('/activity-types', [ConfigController::class, 'activitiesStore']);
        Route::put('/activity-types/{activity}', [ConfigController::class, 'activitiesUpdate']);

        // --- Gestión de Feriados ---
        Route::get('/holidays', [ConfigController::class, 'holidaysIndex']);
        Route::post('/holidays', [ConfigController::class, 'holidaysStore']);
        Route::delete('/holidays/{holiday}', [ConfigController::class, 'holidaysDestroy']);

        // --- Gestión de Configuración de Empleados (RMU) ---
        Route::get('/employee-configs', [ConfigController::class, 'employeeConfigsIndex']);
        Route::post('/employee-configs', [ConfigController::class, 'employeeConfigsStore']);
        Route::put('/employee-configs/{config}', [ConfigController::class, 'employeeConfigsUpdate']);

        // --- Gestión de Autoridades (Firmas PDF) ---
        Route::get('/authorities', [ConfigController::class, 'getAuthorities'])->name('authorities.get');
        Route::post('/authorities', [ConfigController::class, 'updateAuthorities'])->name('authorities.update');

        // --- Utilidades ---
        Route::get('/all-users', [ConfigController::class, 'getAllUsers'])->name('config.all-users');
        Route::post('/employees/import-structure', [EmployeeImportController::class, 'updateOrganizationalStructure']);
    });

    Route::middleware('permission:th.configuracion.gestionar,api')->prefix('personnel')->name('personnel.')->group(function () {

        // CRUD Personal
        Route::get('/', [PersonnelController::class, 'index']);
        Route::post('/', [PersonnelController::class, 'store']);
        Route::put('/{id}', [PersonnelController::class, 'update']);
        Route::patch('/{id}/status', [PersonnelController::class, 'toggleStatus']);

        // Catálogos para el formulario
        Route::get('/catalogs', [PersonnelController::class, 'getCatalogs']);
    });
});
