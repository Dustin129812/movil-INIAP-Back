<?php

namespace App\Modules\Planificacion\Http\Controllers;

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\FeatureFlagController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MessengerController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Investigacion\Http\Controllers\AreaController;
use Modules\Investigacion\Http\Controllers\BudgetTypeController;
use Modules\Investigacion\Http\Controllers\CommentController;
use Modules\Investigacion\Http\Controllers\ConversationController;
use Modules\Investigacion\Http\Controllers\CropsController;
use Modules\Investigacion\Http\Controllers\DashboardController;
use Modules\Investigacion\Http\Controllers\DocumentController;
use Modules\Investigacion\Http\Controllers\DocumentTypeController;
use Modules\Investigacion\Http\Controllers\DocumentWorkflowController;
use Modules\Investigacion\Http\Controllers\EstacionDashboardController;
use Modules\Investigacion\Http\Controllers\EthnicGroupController;
use Modules\Investigacion\Http\Controllers\ExpenseTypeController;
use Modules\Investigacion\Http\Controllers\ExportController;
use Modules\Investigacion\Http\Controllers\GeneralController;
use Modules\Investigacion\Http\Controllers\GroupController;
use Modules\Investigacion\Http\Controllers\ImportController;
use Modules\Investigacion\Http\Controllers\IncidentController;
use Modules\Investigacion\Http\Controllers\IndicatorController;
use Modules\Investigacion\Http\Controllers\MaterialController;
use Modules\Investigacion\Http\Controllers\MessageController;
use Modules\Investigacion\Http\Controllers\NationalDashboardController;
use Modules\Investigacion\Http\Controllers\NationalityController;
use Modules\Investigacion\Http\Controllers\NotificationController;
use Modules\Investigacion\Http\Controllers\NoveltyController;
use Modules\Investigacion\Http\Controllers\PatchNoteController;
use Modules\Investigacion\Http\Controllers\PlannerController;
use Modules\Investigacion\Http\Controllers\ProductiveRubroController;
use Modules\Investigacion\Http\Controllers\PulseController;
use Modules\Investigacion\Http\Controllers\ReportController;
use Modules\Investigacion\Http\Controllers\ResponseController;
use Modules\Investigacion\Http\Controllers\ReusableActivityController;
use Modules\Investigacion\Http\Controllers\RubroController;
use Modules\Investigacion\Http\Controllers\StationNeedController;
use Modules\Investigacion\Http\Controllers\SurveyController;
use Modules\Investigacion\Http\Controllers\WeekActivityController;
use Modules\Investigacion\Http\Controllers\WordPressController;


Route::post('login', [AuthController::class, 'login'])->name('login');

Route::apiResource('incidents', IncidentController::class);

Route::middleware(['auth:api', 'role:administrador'])->prefix('admin')->group(function () {

    // Gestión de Usuarios y Roles
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users/{id}/roles', [UserController::class, 'updateRoles']);
    Route::get('/users/{user}/roles', [AdminUserController::class, 'getUserRoles']);

    Route::post('/users', [UserController::class, 'store']);

    // Gestión de Roles (CRUD)
    Route::apiResource('roles', RoleController::class);
    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions']);
    Route::post('roles/{role}/permissions', [RoleController::class, 'assignPermissions']);

    Route::apiResource('permissions', PermissionController::class);

    // Gestión de Funcionalidades (Feature Flags)
    Route::put('/feature-flags/{featureFlag}', [FeatureFlagController::class, 'update']);

    Route::get('/conversations', [ConversationController::class, 'index']);

    Route::apiResource('patch-notes', PatchNoteController::class);

    // Gestión de encuestas (admin)
    Route::apiResource('surveys', SurveyController::class)->except(['index', 'show']);
    Route::get('/surveys/{survey}/results', [SurveyController::class, 'results']);

    Route::get('/surveys/{survey}/export/pdf', [ReportController::class, 'exportPdf']);
    Route::get('/surveys/{survey}/export/excel', [ReportController::class, 'exportExcel']);
    Route::get('/surveys/{survey}/individual-responses', [SurveyController::class, 'individualResponses']);
});

Route::middleware('auth:api', 'throttle:60,1')->group(callback: function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/register-past-week', [WeekActivityController::class, 'registerPastWeek']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read/{notificationId}', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/mark-as-read-batch', [NotificationController::class, 'markAsReadBatch']);
    Route::post('/notifications/mark-as-unread-batch', [NotificationController::class, 'markAsUnreadBatch']); // Si necesitas desmarcar
    Route::delete('/notifications/batch', [NotificationController::class, 'destroyBatch']); // Para eliminar o archivar

    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user/roles', [AuthController::class, 'getUserRoles']);

    Route::post('users/{id}/roles', [UserController::class, 'updateRoles']);
    Route::get('getUsers', [UserController::class, 'getUsers']);
    Route::get('profile', [UserController::class, 'getProfile']);
    Route::put('profile/update-password', [UserController::class, 'updatePassword']);
    Route::get('getUsersbyLocation', [UserController::class, 'getUsersbyLocation']);

    Route::get('exportPlanificacion', [ExportController::class, 'exportPlanificacion']);
    Route::get('exportPlanificacionAllLocations', [ExportController::class, 'exportPlanificacionAllLocations']);

    Route::post('addProductAndActivity', [PlannerController::class, 'addProductAndActivity']);
    Route::put('week-activities/{activityId}/approve', [PlannerController::class, 'approveActivity']);
    Route::get('getWeeklyPlanningByResponsible', [PlannerController::class, 'getWeeklyPlanningByResponsible']);
    Route::get('getProductsWithActivities', [PlannerController::class, 'getProductsWithActivities']);
    Route::get('getProductsWithActivitiesExtraPoa', [PlannerController::class, 'getProductsWithActivitiesExtraPoa']);
    Route::get('/plannable-products', [PlannerController::class, 'getPlannableProductsForCurrentUser']);
    Route::put('/products/{id}', [PlannerController::class, 'updateProductAndActivity']);

    Route::resource('materials', MaterialController::class);
    Route::apiResource('rubros', RubroController::class);
    Route::apiResource('indicators', IndicatorController::class);

    Route::get('user-associated-counts', [PlannerController::class, 'getUserAssociatedCounts']);

    Route::get('getProducts', [GeneralController::class, 'getProducts']);
    Route::get('products-with-activities', [GeneralController::class, 'getProductsWithActivities']);
    Route::get('getUniqueLocations', [PlannerController::class, 'getUniqueLocations']);
    Route::get('products-by-location/{locationId}', [PlannerController::class, 'getProductsByLocationId']);
    Route::get('activities', [GeneralController::class, 'getActivitiesByProduct']);
    Route::get('activities/{productId}', [GeneralController::class, 'getActivitiesByProduct']);
    Route::get('getProductsByLocation', [GeneralController::class, 'getProductsByLocation']);
    Route::get('getRubrosByLocation', [GeneralController::class, 'getRubrosByLocation']);

    Route::post('weeklyPlanner', [WeekActivityController::class, 'weeklyPlanner']);
    Route::get('week-activities/previous', [WeekActivityController::class, 'getPreviousWeekActivities']);
    Route::put('week-activities/progress', [WeekActivityController::class, 'updateWeeklyProgress']);
    Route::get('activities/progress', [WeekActivityController::class, 'getActivitiesWithProgress']);
    Route::get('/previous-week-activities', [WeekActivityController::class, 'getPreviousWeekActivitiesForReview']);
    Route::post('/novelties', [WeekActivityController::class, 'storeNovelty']);
    Route::get('/novelties', [WeekActivityController::class, 'getNoveltiesForWeek']);

    Route::post('addLogistic', [GeneralController::class, 'addLogisticSupport']);

    Route::get('getLocations', [GeneralController::class, 'getLocations']);
    Route::get('getNationality', [GeneralController::class, 'getNationality']);
    Route::get('getEthnics', [GeneralController::class, 'getEthnics']);
    Route::get('getPositions', [GeneralController::class, 'getPositions']);
    Route::get('getIndicators', [GeneralController::class, 'getIndicators']);
    Route::get('getLogistic', [GeneralController::class, 'getLogistic']);

    Route::get('weekly-plan-report', [ReportController::class, 'generateWeeklyPlanReport']);
    Route::get('user-weekly-plans', [ReportController::class, 'getUserWeeklyPlans']);
    Route::get('getUserWeeklyPlansbyLocation', [ReportController::class, 'getUserWeeklyPlansbyLocation']);
    Route::get('generateWeeklyMonitoringReport', [ReportController::class, 'generateWeeklyMonitoringReport']);

    Route::get('/wordpress-posts', [WordPressController::class, 'getPosts']);

    Route::get('/feature-flags', [FeatureFlagController::class, 'index']);

    Route::get('/unified-poa-by-station', [PlannerController::class, 'getUnifiedPoaData']);

    Route::post('/conversations', [ConversationController::class, 'create']);
    Route::get('/conversations/{id}/messages', [MessageController::class, 'list']);
    Route::post('/conversations/{id}/messages', [MessageController::class, 'store']);
    Route::post('conversations/{conversation}/read', [ConversationController::class, 'markAsRead']);

    //--GESTION DE GRUPOS--
    Route::put('/groups/{group}/members', [GroupController::class, 'syncMembers'])->name('groups.syncMembers');
    Route::put('/groups/{group}/responsible', [GroupController::class, 'changeResponsible'])->name('groups.changeResponsible');
    Route::apiResource('groups', GroupController::class);

    Route::get('/dashboard/researcher', [DashboardController::class, 'getResearcherDashboardData']);
    Route::get('/dashboard/product-manager', [DashboardController::class, 'getProductManagerDashboardData']);

    Route::get('team-pulse-report', [ReportController::class, 'generateTeamPulseReport']);

    Route::get('/patch-notes/latest', [PatchNoteController::class, 'getLatest']);

    Route::get('/station-needs/create', [StationNeedController::class, 'create']);

    Route::apiResource('station-needs', StationNeedController::class);

    Route::get('/expense-types', [ExpenseTypeController::class, 'index']);
    Route::get('/expense-types/search', [ExpenseTypeController::class, 'search']);

    Route::apiResource('reusable-activities', ReusableActivityController::class);

    Route::get('/document-types', [DocumentTypeController::class, 'index']);

    Route::prefix('documents')->group(function () {
        Route::get('/search', [DocumentController::class, 'search'])->name('documents.search');
        Route::get('/inbox', [DocumentController::class, 'inbox']);
        Route::get('/sent', [DocumentController::class, 'sent']);
        Route::get('/drafts', [DocumentController::class, 'drafts']);
        Route::get('/archived', [DocumentController::class, 'archived'])->name('documents.archived');
        Route::get('/{document}', [DocumentController::class, 'show'])->name('documents.show');

        Route::post('/', [DocumentController::class, 'store'])->name('documents.store');
        Route::post('/{document}/attachments', [DocumentController::class, 'attach'])->name('documents.attach');
        Route::post('/{document}/send', [DocumentController::class, 'send'])->name('documents.send');
        Route::post('/{document}/read', [DocumentController::class, 'markAsRead'])->name('documents.read');
        Route::post('/{document}/inform', [DocumentController::class, 'inform'])->name('documents.inform');
        Route::post('/{document}/reassign', [DocumentController::class, 'reassign'])->name('documents.reassign');
        Route::post('/{document}/finalize', [DocumentController::class, 'finalize'])->name('documents.finalize');

        Route::put('/{document}', [DocumentController::class, 'update'])->name('documents.update');
    });

    Route::prefix('workflows/{workflow}')->group(function () {
        Route::post('/archive', [DocumentWorkflowController::class, 'archive'])->name('workflows.archive');
        Route::post('/trash', [DocumentWorkflowController::class, 'trash'])->name('workflows.trash');
        Route::post('/restore', [DocumentWorkflowController::class, 'restore'])->name('workflows.restore');
    });

    Route::get('/documents/{document}/comments', [CommentController::class, 'index'])->name('comments.index');
    Route::post('/documents/{document}/comments', [CommentController::class, 'store'])->name('comments.store');

    Route::get('/dashboard/portfolio-progress', [DashboardController::class, 'getPortfolioProgress']);
    Route::get('/dashboard/team-performance', [DashboardController::class, 'getTeamPerformance']);
    Route::get('/dashboard/review-queue', [DashboardController::class, 'getReviewQueue']);
    Route::get('/dashboard/team-pulse', [DashboardController::class, 'getTeamPulseData']);

    Route::get('/reports/user-deep-dive/{user}', [ReportController::class, 'generateUserDeepDivePdf']);
    Route::get('/reports/user-deep-dive/{user}/data', [ReportController::class, 'getUserDeepDiveData']);

    Route::get('/reports/my-performance', [DashboardController::class, 'getMyPerformance']);
    Route::get('/reports/my-pulse-history', [DashboardController::class, 'getMyPulseHistory']);

    Route::get('/dashboard/estacion/kpis', [EstacionDashboardController::class, 'getKpis']);
    Route::get('/dashboard/estacion/group-performance', [EstacionDashboardController::class, 'getGroupPerformance']);
    Route::get('/dashboard/estacion/team-pulse', [EstacionDashboardController::class, 'getTeamPulse']);
    Route::get('/dashboard/estacion/rubro-deep-dive/{rubro}', [EstacionDashboardController::class, 'getRubroDeepDive']);
    Route::get('/dashboard/estacion/rubro-performance', [EstacionDashboardController::class, 'getRubroPerformance']);
    Route::get('/reports/rubro-deep-dive/{rubro}', [ReportController::class, 'generateRubroDeepDivePdf']);

    Route::post('/novelties/batch', [NoveltyController::class, 'storeBatch']);
    Route::get('/novelties', [NoveltyController::class, 'getForCurrentWeek']);

    Route::middleware(['auth:api', 'permission:view-direccion-dashboard'])->group(function () {

        Route::get('/dashboard/national/kpis', [NationalDashboardController::class, 'getNationalKpis']);
        Route::get('/dashboard/national/station-performance', [NationalDashboardController::class, 'getStationPerformance']);
        Route::get('/dashboard/national/pulse-summary', [NationalDashboardController::class, 'getNationalPulseSummary']);
        Route::get('/dashboard/national/rubro-performance', [NationalDashboardController::class, 'getNationalRubroPerformance']);
        Route::get('/reports/national/executive-summary', [ReportController::class, 'generateNationalExecutiveSummary']);
        Route::get('/reports/national/station-comparison', [ReportController::class, 'generateStationComparisonReport']);

    });

    Route::get('surveys', [SurveyController::class, 'index']);
    Route::get('surveys/{survey}', [SurveyController::class, 'show']);
    Route::post('surveys/{surveyId}/responses', [ResponseController::class, 'store']);

    Route::get('/nationalities', [NationalityController::class, 'index']);
    Route::get('/ethnic_groups', [EthnicGroupController::class, 'index']);
    Route::get('/areas', [AreaController::class, 'index']);

    Route::apiResource('budgettypes', BudgetTypeController::class);
    Route::apiResource('productive-rubro',ProductiveRubroController::class);
    Route::apiResource('crops',CropsController::class);
    Route::get('getCrops',[CropsController::class,'getCrops']);
    Route::get('getCropsbyProductiveRubro/{id}',[CropsController::class,'getCropsbyProductiveRubro']);

});

Route::get('/webhook/messenger', [MessengerController::class, 'verify']);
Route::post('/webhook/messenger', [MessengerController::class, 'handleMessage']);
Route::post('/import-users', [ImportController::class, 'importUserFile']);

require base_path('Modules/TalentoHumano/Routes/api.php');
require_once base_path('Modules/Inventario/Routes/api.php');
require_once base_path('Modules/Campo/Routes/api.php');
require_once base_path('Modules/Produccion/Routes/api.php');
require_once base_path('Modules/Investigacion/Routes/api.php');
