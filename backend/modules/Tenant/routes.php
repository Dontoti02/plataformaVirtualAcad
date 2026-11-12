<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\CheckInstitutionModuleMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Modules\Auth\Controllers\AuthController;
use Modules\Tenant\Modules\Classroom\Controllers\AnswerController;
use Modules\Tenant\Modules\Classroom\Controllers\AssistanceController;
use Modules\Tenant\Modules\Classroom\Controllers\ClassroomController;
use Modules\Tenant\Modules\Classroom\Controllers\CommentController;
use Modules\Tenant\Modules\Classroom\Controllers\ParticipantController;
use Modules\Tenant\Modules\Classroom\Controllers\ContentController;
use Modules\Tenant\Modules\Classroom\Controllers\ContentGroupController;
use Modules\Tenant\Modules\Classroom\Controllers\EvaluationController;
use Modules\Tenant\Modules\Classroom\Controllers\CapacityController;
use Modules\Tenant\Modules\Classroom\Controllers\NoteController;
use Modules\Tenant\Modules\Classroom\Controllers\EvaluationGroupController;
use Modules\Tenant\Modules\Classroom\Controllers\PublicationController;
use Modules\Tenant\Modules\EvaluationForm\Controllers\EvaluationFormController;
use Modules\Tenant\Modules\Export\Controllers\ExportController;
use Modules\Tenant\Modules\File\Controllers\FileController;
use Modules\Tenant\Modules\File\Controllers\LinkController;
use Modules\Tenant\Modules\Home\Controllers\HomeController;
use Modules\Tenant\Modules\Import\Controllers\ImportController;
use Modules\Tenant\Modules\Management\Controllers\HistoryController;
use Modules\Tenant\Modules\Management\Controllers\PeriodController;
use Modules\Tenant\Modules\Management\Controllers\RegistrationController;
use Modules\Tenant\Modules\Management\Controllers\StudyProgramController;
use Modules\Tenant\Modules\MeritOrder\Controllers\MeritOrderController;
use Modules\Tenant\Modules\Schedule\Controllers\ScheduleController;
use Modules\Tenant\Modules\SystemConfiguration\Controllers\SystemConfigurationController;
use Modules\Tenant\Modules\Testing\TestingController;
use Modules\Tenant\Modules\Treasury\Controllers\DenominationController;
use Modules\Tenant\Modules\Treasury\Controllers\PaymentConceptController;
use Modules\Tenant\Modules\Treasury\Controllers\PaymentsController;
use Modules\Tenant\Modules\Training\Controllers\TrainingAnswerController;
use Modules\Tenant\Modules\Training\Controllers\TrainingAssistanceController;
use Modules\Tenant\Modules\Training\Controllers\TrainingCommentController;
use Modules\Tenant\Modules\Training\Controllers\TrainingContentController;
use Modules\Tenant\Modules\Training\Controllers\TrainingContentGroupController;
use Modules\Tenant\Modules\Training\Controllers\TrainingController;
use Modules\Tenant\Modules\Training\Controllers\TrainingEvaluationController;
use Modules\Tenant\Modules\Training\Controllers\TrainingEvaluationFormController;
use Modules\Tenant\Modules\Training\Controllers\TrainingEvaluationGroupController;
use Modules\Tenant\Modules\Training\Controllers\TrainingParticipantController;
use Modules\Tenant\Modules\Training\Controllers\TrainingPublicationController;
use Modules\Tenant\Modules\Training\Controllers\TrainingReportController;
use Modules\Tenant\Modules\Training\Controllers\TrainingTaskGroupController;
use Modules\Tenant\Modules\User\Controllers\MenuController;
use Modules\Tenant\Modules\User\Controllers\ProfileController;
use Modules\Tenant\Modules\User\Controllers\RolController;
use Modules\Tenant\Modules\User\Controllers\UserController;
use Modules\Tenant\Modules\Enroll\Controllers\EnrollController;
use Modules\Tenant\Modules\Treasury\Controllers\ScalesController;

#rutas publicas
Route::group(['middleware' => [
  DomainTenantMiddleware::class,
  SubscriptionTenantMiddleware::class,
]], function () {

  Route::controller(SystemConfigurationController::class)
    ->prefix('system_configuration')
    ->group(function () {
      Route::get('general', 'general');
      Route::get('landing_page', 'landingPage');
    });

  Route::controller(AuthController::class)
    ->prefix('auth')
    ->group(function () {
      Route::post('login', 'login');
      Route::put('reset/password', 'resetPassword');
      Route::get('check/reset/password', 'checkResetPassword');
      Route::put('change/password', 'changePassword');
    });
});

#rutas privadas
Route::group(['middleware' => [
  DomainTenantMiddleware::class,
  AuthTenantMiddleware::class,
  SubscriptionTenantMiddleware::class,
]], function () {

  Route::controller(TestingController::class)
    ->prefix('testing')
    ->group(function () {
      Route::get('', 'test');
    });

  Route::controller(RolController::class)
    ->prefix('rol')
    ->group(function () {
      Route::get('list', 'list');
      Route::put('change/{id}', 'change');
    });

  Route::controller(MenuController::class)
    ->prefix('menu')
    ->group(function () {
      Route::get('', 'get');
    });

  Route::controller(ProfileController::class)
    ->prefix('profile')
    ->group(function () {
      Route::get('', 'get');
    });

  Route::controller(UserController::class)
    ->prefix('user')
    ->group(function () {
      Route::get('params', 'params');
      Route::post('list', 'list');
      Route::post('create', 'create');
      Route::put('update/{id}', 'update');
      Route::put('change/password/{id}', 'changePassword');
      Route::post('change/photo/{id}', 'changePhoto');
      Route::delete('delete/photo/{id}', 'deletePhoto');
      Route::put('reset/password/{id}', 'resetPassword');
      Route::put('disable/{id}', 'disable');
      Route::delete('delete/{id}', 'delete');
    });

  Route::controller(ImportController::class)
    ->prefix('import')
    ->group(function () {
      Route::get('list', 'list');
      Route::get('get/{id}', 'get');
      Route::get('currently', 'currently');
      Route::get('history/{id}', 'history');
      Route::post('execute/{key}', 'execute');
      Route::post('finish', 'finish');
      Route::post('finish/all', 'finishAll');
    });

  Route::controller(HomeController::class)
    ->prefix('home')
    ->group(function () {
      Route::get('', 'home');
    });

  Route::controller(PeriodController::class)
    ->prefix('period')
    ->group(function () {
      Route::get('list', 'list');
      Route::get('current', 'current');
      Route::put('disable/{id}', 'disable');
      Route::get('filters/classrooms', 'filtersClassrooms');
      Route::post('list/classrooms', 'listClassrooms');
      Route::put('updateDate', 'updateDate');
    });

  Route::controller(ClassroomController::class)
    ->prefix('classroom')
    ->group(function () {
      Route::post('list', 'list');
      Route::post('list/options', 'options');
      Route::get('{id}', 'get');
      Route::post('update/image', 'updateImage');
      Route::put('update/favorite/{id}', 'updateFavorite');
    });

  Route::controller(ParticipantController::class)
    ->prefix('participant')
    ->group(function () {
      Route::get('list/{classroom_id}', 'list');
    });

  Route::controller(EvaluationGroupController::class)
    ->prefix('evaluation-group')
    ->group(function () {
      Route::get('list/{classroom_id}', 'list');
      Route::post('set', 'set');
    });

  Route::controller(NoteController::class)
    ->prefix('note')
    ->group(function () {
      Route::post('list', 'list');
      Route::post('report', 'report');
      Route::post('save-recovery', 'saveRecovery');
    });

  Route::controller(CapacityController::class)
    ->prefix('capacity')
    ->group(function () {
      Route::get('list/{classroomId}', 'list');
      Route::post('create', 'create');
      Route::post('create/with-indicators', 'createWithIndicators');
      Route::put('update/{id}', 'update');
      Route::put('update/with-indicators/{id}', 'updateWithIndicators');
      Route::put('sort/{id}/{position}', 'sort');
      Route::delete('delete/{id}', 'delete');
      Route::post('create/indicator', 'createIndicator');
      Route::put('update/indicator/{id}', 'updateIndicator');
      Route::put('sort/indicator/{id}/{position}', 'sortIndicator');
      Route::delete('delete/indicator/{id}', 'deleteIndicator');
    });

  Route::controller(ContentGroupController::class)
    ->prefix('content-group')
    ->group(function () {
      Route::get('list/{classroom_id}', 'list');
      Route::post('create', 'create');
      Route::put('update/{id}', 'update');
      Route::delete('delete/{id}', 'delete');
    });

  Route::controller(ContentController::class)
    ->prefix('content')
    ->group(function () {
      Route::get('list/{classroom_id}', 'list');
      Route::get('detail/{id}', 'detail');
      Route::post('create', 'create');
      Route::put('update/{id}', 'update');
      Route::put('update/visibility/{id}', 'updateVisibility');
      Route::put('update/status/{id}', 'updateStatus');
      Route::delete('delete/{id}', 'delete');
    });

  Route::controller(PublicationController::class)
    ->prefix('publication')
    ->group(function () {
      Route::post('list', 'list');
      Route::post('create', 'create');
      Route::put('update/{id}', 'update');
      Route::delete('delete/{id}', 'delete');
    });

  Route::controller(CommentController::class)
    ->prefix('comment')
    ->group(function () {
      Route::post('create', 'create');
      Route::put('update/{id}', 'update');
      Route::delete('delete/{id}', 'delete');
    });

  Route::controller(AnswerController::class)
    ->prefix('answer')
    ->group(function () {
      Route::get('list/{content_id}', 'list');
      Route::put('delivered/{id}', 'delivered');
    });

  Route::controller(FileController::class)
    ->prefix('file')
    ->group(function () {
      Route::get('download/{uuid}', 'download');
      Route::post('upload', 'upload');
      Route::delete('delete/{id}/{model}', 'delete');
    });

  Route::controller(LinkController::class)
    ->prefix('link')
    ->group(function () {
      Route::post('create', 'create');
      Route::delete('delete/{id}/{model}', 'delete');
    });

  Route::controller(AssistanceController::class)
    ->prefix('assistance')
    ->group(function () {
      Route::get('dates/{classroom_id}', 'dates');
      Route::post('list', 'list');
      Route::post('create', 'create');
      Route::put('mark/{id}', 'mark');
      Route::post('delete', 'deleteMultiple');
    });

  Route::controller(EvaluationController::class)
    ->prefix('evaluation')
    ->group(function () {
      Route::get('list/{classroom_id}/{person_id?}', 'list');
      Route::put('evaluate', 'evaluate');
    });

  Route::controller(SystemConfigurationController::class)
    ->prefix('system_configuration')
    ->group(function () {
      Route::get('list', 'list');
      Route::post('update/{key}', 'update');
      Route::post('upload/image', 'uploadImage');
      Route::delete('delete/image', 'deleteImage');
      // Correo saliente: lo configura la propia institución desde Ajustes.
      Route::get('mail', 'mail');
      Route::post('mail', 'updateMail');
      Route::post('mail/test', 'testMail');
    });

  Route::controller(ScheduleController::class)
    ->prefix('schedule')
    ->group(function () {
      Route::get('filters', 'filters');
      Route::post('filters/export', 'filtersByExport');
      Route::post('list', 'list');
      Route::post('list/classrooms', 'listClassrooms');
      Route::post('list/classrooms-existing', 'listClassroomsExisting');
      Route::post('list/teachers', 'listTeachers');
      Route::post('create', 'create');
      Route::put('update/{id}', 'update');
      Route::delete('delete/{id}', 'delete');
      Route::post('assign/teacher', 'assignTeacher');
    });

  Route::controller(EnrollController::class)
    ->prefix('enrollment')
    ->group(function () {
      Route::post('list', 'list');
      Route::get('get/{id}', 'get');
      Route::get('getFormsData', 'getFormsData');
      Route::post('searchStudent', 'searchStudent');
      Route::post('validateDNI', 'validateDNI');
      Route::post('validateEnrollment', 'validateEnrollment');
      Route::post('enrollRegularStudent', 'enrollRegularStudent');
      Route::post('enrollNewStudent', 'enrollNewStudent');
      Route::post('validateFamilyDNI', 'validateFamilyDNI');
      Route::delete('deleteEnroll/{id}', 'delete');
      Route::put('updateEnroll/{id}', 'updateEnroll');
      Route::get('validations', 'getValidationsForEnrollment');
    });

  Route::controller(RegistrationController::class)
    ->prefix('registration')
    ->group(function () {
      Route::get('filters', 'filters');
      Route::post('list/students', 'listStudents');
      Route::post('list', 'list');
    });

  Route::controller(StudyProgramController::class)
    ->prefix('study_program')
    ->group(function () {
      Route::get('filters', 'filters');
      Route::post('list', 'list');
    });

  Route::controller(HistoryController::class)
    ->prefix('history')
    ->group(function () {
      Route::get('filters', 'filters');
      Route::post('list/students', 'listStudents');
      Route::post('list', 'list');
    });

  Route::controller(ExportController::class)
    ->prefix('export')
    ->group(function () {
      Route::get('execute/{key}/{type}', 'execute');
    });

  Route::controller(MeritOrderController::class)
    ->prefix('merit_order')
    ->group(function () {
      Route::get('filters', 'filters');
      Route::post('list', 'list');
    });

  Route::controller(EvaluationFormController::class)
    ->prefix('evaluation_form')
    ->group(function () {
      Route::get('question/types', 'questionTypes');
      Route::get('{uuid}/{person_id?}', 'get');
      Route::post('delivered', 'delivered');
    });

  Route::controller(PaymentConceptController::class)
    ->prefix('payment-concept')
    ->group(function () {
      Route::get('all', 'all');
    });


  Route::prefix('treasury')->group(function () {
    Route::controller(PaymentConceptController::class)
      ->prefix('payment-concepts')
      ->group(function () {
        Route::get('all', 'all');
        Route::post('create', 'create');
        Route::put('update/{id}', 'update');
        Route::delete('delete/{id}', 'delete');
        Route::get('movements/{id}', 'movements');
        Route::put('active/{id}', 'active');
        Route::put('inactive/{id}', 'inactive');
        Route::post('search/concept', 'search');
        Route::get('enrollmentConceptsData', 'enrollmentConceptsData');
        Route::get('history/{id}', 'history');
      });

    Route::controller(PaymentsController::class)
      ->prefix('payments')
      ->group(function () {
        Route::get('get/{person_id}/{is_paid}', 'get');
        Route::post('search/student', 'searchStudent');
        Route::post('create', 'create');
        Route::post('refund', 'refund');
        Route::get('detail/{id}', 'detail');
        Route::post('payNextDetail/{id}', 'payNextDetail');
        Route::post('payEnrollment/{id}', 'payEnrollment');
        Route::get('movementsByConcept/{id}', 'movementsByConcept');
        Route::get('ticket/export/{id}', 'exportTicket');
        Route::get('ticket/export/movement/{id}', 'exportMovementTicket');
      });

    Route::controller(ScalesController::class)
      ->prefix('scales')
      ->group(function () {
        Route::post('create', 'create');
        Route::get('all', 'all');
        Route::put('update/{id}', 'update');
        Route::delete('delete/{id}', 'delete');
        Route::get('enrollments/{id}', 'enrollments');
      });

    Route::controller(DenominationController::class)
      ->prefix('denomination')
      ->group(function () {
        Route::get('all', 'all');
      });
  });
  Route::controller(EvaluationController::class)
    ->prefix('evaluation')
    ->group(function () {
      Route::get('list/{classroom_id}/{person_id?}', 'list');
      Route::put('evaluate', 'evaluate');
    });

  Route::controller(SystemConfigurationController::class)
    ->prefix('system_configuration')
    ->group(function () {
      Route::get('list', 'list');
      Route::post('update/{key}', 'update');
      Route::post('upload/image', 'uploadImage');
      Route::delete('delete/image', 'deleteImage');
    });

  Route::controller(ScheduleController::class)
    ->prefix('schedule')
    ->group(function () {
      Route::get('filters', 'filters');
      Route::post('filters/export', 'filtersByExport');
      Route::post('list', 'list');
      Route::post('list/classrooms', 'listClassrooms');
      Route::post('list/classrooms-existing', 'listClassroomsExisting');
      Route::post('list/teachers', 'listTeachers');
      Route::post('create', 'create');
      Route::put('update/{id}', 'update');
      Route::delete('delete/{id}', 'delete');
      Route::post('assign/teacher', 'assignTeacher');
    });

  Route::controller(RegistrationController::class)
    ->prefix('registration')
    ->group(function () {
      Route::get('filters', 'filters');
      Route::post('list/students', 'listStudents');
      Route::post('list', 'list');
    });

  Route::controller(StudyProgramController::class)
    ->prefix('study_program')
    ->group(function () {
      Route::get('filters', 'filters');
      Route::post('list', 'list');
    });

  Route::controller(HistoryController::class)
    ->prefix('history')
    ->group(function () {
      Route::get('filters', 'filters');
      Route::post('list/students', 'listStudents');
      Route::post('list', 'list');
    });

  Route::controller(ExportController::class)
    ->prefix('export')
    ->group(function () {
      Route::get('execute/{key}/{type}', 'execute');
    });

  Route::controller(MeritOrderController::class)
    ->prefix('merit_order')
    ->group(function () {
      Route::get('filters', 'filters');
      Route::post('list', 'list');
    });

  Route::controller(EvaluationFormController::class)
    ->prefix('evaluation_form')
    ->group(function () {
      Route::get('question/types', 'questionTypes');
      Route::get('{uuid}/{person_id?}', 'get');
      Route::post('delivered', 'delivered');
    });

  // Modulo Capacitaciones
  Route::controller(TrainingController::class)
    ->prefix('training')
    ->group(function () {
      Route::get('filters', 'filters');
      Route::post('list', 'list');
      Route::get('{id}', 'get');
      Route::post('set', 'set');
      Route::delete('delete/{id}', 'delete');
      Route::post('update/image', 'updateImage');
      Route::delete('delete/image/{id}', 'deleteImage');
      Route::put('update/favorite/{id}', 'updateFavorite');
      Route::get('find/person', 'findPerson');
      Route::post('create/person', 'createPerson');
      Route::post('assign/teacher', 'assignTeacher');
      Route::get('filters/students', 'filtersStudents');
      Route::post('list/students', 'listStudents');
      Route::post('list/students/download', 'listStudentsDownload');
      Route::post('assign/student', 'assignStudent');
      Route::put('unassign/student', 'unassignStudent');
      Route::put('activate/student', 'activateStudent');
      Route::post('certificate/download', 'certificateDownload');
      Route::get('certificate/download/zip/{id}', 'certificateDownloadZip');
      Route::post('create-category', 'createCategory');
    });

  Route::controller(TrainingReportController::class)
    ->prefix('training/report')
    ->group(function () {
      Route::get('filters', 'filters');
      Route::post('list', 'list');
      Route::post('list/download', 'listDownload');
    });

  Route::controller(TrainingParticipantController::class)
    ->prefix('training/participant')
    ->group(function () {
      Route::get('list/{training_id}', 'list');
    });

  Route::controller(TrainingEvaluationGroupController::class)
    ->prefix('training/evaluation-group')
    ->group(function () {
      Route::get('list/{training_id}', 'list');
      Route::post('set', 'set');
    });

  Route::controller(TrainingContentGroupController::class)
    ->prefix('training/content-group')
    ->group(function () {
      Route::get('list/{training_id}', 'list');
      Route::post('create', 'create');
      Route::put('update/{id}', 'update');
      Route::delete('delete/{id}', 'delete');
    });

  Route::controller(TrainingContentController::class)
    ->prefix('training/content')
    ->group(function () {
      Route::get('list/{training_id}', 'list');
      Route::get('detail/{id}', 'detail');
      Route::post('create', 'create');
      Route::put('update/{id}', 'update');
      Route::put('update/visibility/{id}', 'updateVisibility');
      Route::put('update/status/{id}', 'updateStatus');
      Route::delete('delete/{id}', 'delete');
    });

  Route::controller(TrainingTaskGroupController::class)
    ->prefix('training/content/task_group')
    ->group(function () {
      Route::get('list/{training_content_id}', 'list');
      Route::post('set', 'set');
      Route::delete('delete/{id}', 'delete');
      Route::get('participant/list/{training_id}', 'listParticipants');
      Route::post('participant/set', 'setParticipant');
      Route::delete('participant/delete/{id}', 'deleteParticipant');
    });

  Route::controller(TrainingPublicationController::class)
    ->prefix('training/publication')
    ->group(function () {
      Route::post('list', 'list');
      Route::post('create', 'create');
      Route::put('update/{id}', 'update');
      Route::delete('delete/{id}', 'delete');
    });

  Route::controller(TrainingCommentController::class)
    ->prefix('training/comment')
    ->group(function () {
      Route::post('create', 'create');
      Route::put('update/{id}', 'update');
      Route::delete('delete/{id}', 'delete');
    });

  Route::controller(TrainingAnswerController::class)
    ->prefix('training/answer')
    ->group(function () {
      Route::get('list/{training_content_id}', 'list');
      Route::put('delivered/{id}', 'delivered');
    });

  Route::controller(TrainingAssistanceController::class)
    ->prefix('training/assistance')
    ->group(function () {
      Route::get('dates/{training_id}', 'dates');
      Route::post('list', 'list');
      Route::post('create', 'create');
      Route::put('mark/{id}', 'mark');
      Route::post('delete', 'deleteMultiple');
    });

  Route::controller(TrainingEvaluationController::class)
    ->prefix('training/evaluation')
    ->group(function () {
      Route::get('list/{training_id}/{person_id?}', 'list');
      Route::put('evaluate', 'evaluate');
    });

  Route::controller(TrainingEvaluationFormController::class)
    ->prefix('training/evaluation_form')
    ->group(function () {
      Route::get('question/types', 'questionTypes');
      Route::get('{uuid}/{person_id?}', 'get');
      Route::post('delivered', 'delivered');
    });
});

$submodulesPath = __DIR__ . '/Modules';

if (is_dir($submodulesPath)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($submodulesPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getFilename() === 'routes.php') {
            require $file->getPathname();
        }
    }
}