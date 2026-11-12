<?php

namespace Modules\Tenant\Modules\ENroll\Repositories;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Enroll\Models\Enroll;
use Modules\Tenant\Modules\Enroll\Models\Scale;
use Modules\Tenant\Modules\Management\Models\Period;
use Modules\Tenant\Modules\Management\Models\Cycle;
use Modules\Tenant\Modules\Management\Models\ProgramPlan;
use Modules\Tenant\Modules\Management\Models\Section;
use Modules\Tenant\Modules\Management\Models\Shift;
use Modules\Tenant\Modules\Management\Models\StudyPlan;
use Modules\Tenant\Modules\Management\Models\StudyProgram;
use Modules\Tenant\Modules\Treasury\Enum\PaymentConceptEnum;
use Modules\Tenant\Modules\Treasury\Models\PaymentConcept;
use Modules\Tenant\Modules\User\Models\AditionalData;
use Modules\Tenant\Modules\User\Models\Family;
use Modules\Tenant\Modules\User\Models\Person;
use Modules\Tenant\Modules\User\Models\SchoolData;

class EnrollRepository
{
  public static function list(Request $request)
  {
    $period_id = $request->periodId;
    $study_program_id = $request->programId;
    $person_id = $request->personId;
    $enroll = Enroll::select([
      'enrollment.id',
      'enrollment.registration_date',
      'enrollment.is_full_payment',
      'enrollment.scale_authorization_document_number',
      'enrollment.scale_authorization_full_names',
      'enrollment.scale_authorization_document_type',
      'enrollment.observations',
      'person.names',
      'person.document_number',
      'study_program.name as study_program',
      'study_plan.name as study_plan',
      'cycle.name as cycle',
      'shift.name as shift',
      'section.name as section',
      'period.name as period',
      'scale.name as scale',
      'scale.scale_amount'
    ])
      ->join('person', 'enrollment.person_id', '=', 'person.id')
      ->join('study_program', 'enrollment.study_program_id', '=', 'study_program.id')
      ->join('study_plan', 'enrollment.study_plan_id', '=', 'study_plan.id')
      ->join('cycle', 'enrollment.cycle_id', '=', 'cycle.id')
      ->join('shift', 'enrollment.shift_id', '=', 'shift.id')
      ->join('section', 'enrollment.section_id', '=', 'section.id')
      ->join('period', 'enrollment.period_id', '=', 'period.id')
      ->leftJoin('scale', 'enrollment.scale_id', '=', 'scale.id')
      ->when($person_id, function ($query, $person_id) {
        return $query->where('enrollment.person_id', $person_id);
      })
      ->when($study_program_id, function ($query, $study_program_id) {
        return $query->where('enrollment.study_program_id', $study_program_id);
      })
      ->when($period_id, function ($query, $period_id) {
        return $query->where('enrollment.period_id', $period_id);
      })
      ->orderBy('enrollment.created_at', 'desc')
      ->get();
    return $enroll;
  }
  public static function getFormsData()
  {

    $period = Period::select([
      'id',
      'name',
    ])
      ->whereNot('status', 'TERMINADO')
      ->orderBy('name', 'asc')
      ->get();
    $study_program = StudyProgram::select([
      'id',
      'name',
    ])
      ->orderBy('name', 'asc')
      ->get();
    $study_program->each(function ($program) {
      $program->study_plans = ProgramPlan::select([
        'study_plan.id',
        'study_plan.name',
      ])
        ->join('study_plan', 'program_plan.study_plan_id', '=', 'study_plan.id')
        ->where('program_plan.study_program_id', $program->id)
        ->orderBy('study_plan.name', 'asc')
        ->get();
    });

    $study_plan = StudyPlan::select([
      'id',
      'name',
    ])
      ->orderBy('name', 'asc')
      ->get();
    $cycle = Cycle::select([
      'id',
      'name',
    ])->orderBy('name', 'asc')
      ->get();
    $shift = Shift::select([
      'id',
      'name',
    ])
      ->orderBy('name', 'asc')
      ->get();
    $section = Section::select([
      'id',
      'name',
    ])
      ->orderBy('name', 'asc')
      ->get();
    $scale = Scale::select([
      'id',
      'name',
      'scale_amount'
    ])
      ->orderBy('name', 'asc')
      ->get();

    $result = [
      'period' => $period,
      'study_program' => $study_program,
      'study_plan' => $study_plan,
      'cycle' => $cycle,
      'shift' => $shift,
      'section' => $section,
      'scale' => $scale
    ];
    return $result;
  }
  public static function SearchStudent(Request $request)
  {
    $search = $request->search;
    $students = Person::select()
      ->where('names', 'like', "%$search%")
      ->orWhere('document_number', 'like', "%$search%")
      ->limit(10)
      ->get();
    // return $students
    $studentData = $students->map(function ($student) {
      return [
        'person_id' => $student->id,
        'gender' => $student->sex,
        'document_number' => $student->document_number,
        'name' => $student->names,
        'names' => $student->document_number . ' - ' . $student->names
      ];
    });
    return $studentData;
  }
  public static function validateDNI(Request $request)
  {
    $dni = $request->dni;
    $student = Person::where('document_number', $dni)
      ->with('aditionalData')
      ->first();
    if ($student) {
      return $student;
    } else {
      return null;
    }
  }
  public static function validateEnrollment(Request $request)
  {
    $personId = $request->personId;
    $actualPeriod = Period::where('status', 'SIN TERMINO')->first();
    $enroll = Enroll::where('person_id', $personId)
      ->where('period_id', $actualPeriod->id)
      ->first();
    if ($enroll) {
      return true;
    } else {
      return false;
    }
  }
  public static function validateFamilyDNI(Request $request)
  {
    $dni = $request->dni;
    $family = Family::where('document_number', $dni)
      ->first();
    if ($family) {
      return $family;
    } else {
      return null;
    }
  }
  public static function enrollRegularStudent(Request $request)
  {
    $personId = $request->personId;

    $validatedData = $request->validate([
      'enrollData.period' => 'required|integer',
      'enrollData.studyProgram' => 'required|integer',
      'enrollData.studyPlan' => 'required|integer',
      'enrollData.cycle' => 'required|integer',
      'enrollData.shift' => 'required|integer',
      'enrollData.section' => 'required|integer',
      'enrollData.enrollmentDate' => 'required|date',
      'enrollData.scale' => 'nullable|integer',
      'enrollData.documentType' => 'nullable|string',
      'enrollData.documentNumber' => 'nullable|string',
      'enrollData.fullName' => 'nullable|string',
      'enrollData.observations' => 'nullable|string',
      'enrollData.fullPayment' => 'required|boolean',
    ]);

    $existingEnroll = Enroll::where('person_id', $personId)
      ->where('period_id', $validatedData['enrollData']['period'])
      ->first();

    if ($existingEnroll) {
      throw new \Exception('Este estudiante ya se encuentra matriculado en este periodo.');
    }

    $enroll = Enroll::create([
      'person_id' => $personId,
      'period_id' => $validatedData['enrollData']['period'],
      'study_program_id' => $validatedData['enrollData']['studyProgram'],
      'study_plan_id' => $validatedData['enrollData']['studyPlan'],
      'cycle_id' => $validatedData['enrollData']['cycle'],
      'shift_id' => $validatedData['enrollData']['shift'],
      'section_id' => $validatedData['enrollData']['section'],
      'registration_date' => $validatedData['enrollData']['enrollmentDate'],
      'scale_id' => $validatedData['enrollData']['scale'],
      'scale_authorization_document_type' => $validatedData['enrollData']['documentType'],
      'scale_authorization_document_number' => $validatedData['enrollData']['documentNumber'],
      'scale_authorization_full_names' => $validatedData['enrollData']['fullName'],
      'observations' => $validatedData['enrollData']['observations'],
      'is_full_payment' => $validatedData['enrollData']['fullPayment']
    ]);
    
    return $enroll;
  }

  public static function enrollNewStudent(Request $request)
  {
    $academic_data = $request->academicData;
    $personal_data = $request->personalData;
    $familiar_data = $request->familiarData;
    $enroll_data = $request->enrollmentData;
    $contact_data = $request->contactData;

    $person = Person::create([
      'document_type' => $personal_data['documentType'],
      'document_number' => $personal_data['documentNumber'],
      'names' => $personal_data['lastName'] . ' ' . $personal_data['firstName'],
      'phone' => $contact_data['telephone'],
      'email' => $contact_data['email'],
      'sex' => $personal_data['gender'],
      'birth_date' => $personal_data['birthDate'],
    ]);

    $person_id = $person->id;

    $person_aditional = AditionalData::create([
      'person_id' => $person_id,
      'civil_status' => $personal_data['maritalStatus'],
      'country' => $personal_data['country'],
      'department' => $personal_data['department'],
      'province' => $personal_data['province'] ?? null,
      'district' => $personal_data['district'] ?? null,
      'current_address' => $contact_data['actualAddress'],
      'permanent_address' => $contact_data['permanentAddress'] ?? null,
      'cell_phone' => $contact_data['cellphone'] ?? null,
    ]);

    $studentPhoto = $request->file('studentPhoto');
    if (is_array($studentPhoto) && isset($studentPhoto[0])) {
      $photoPath = $studentPhoto[0]->store('public/images');
    } else {
      $photoPath = $studentPhoto->store('public/images');
    }
    if ($request->hasFile('academicValidation')) {
      $academicValidation = $request->file('academicValidation');
      if (is_array($academicValidation) && isset($academicValidation[0])) {
        $pdfPath = $academicValidation[0]->store('public/pdf');
      } else {
        $pdfPath = $academicValidation->store('public/pdf');
      }
    } else {
      $pdfPath = null;
    }
    $school_data = SchoolData::create([
      'modular_code' => $academic_data['modularCode'],
      'name' => $academic_data['previousSchool'],
      'start_date' => $academic_data['admissionDate'],
      'end_date' => $academic_data['graduationYear'],
      'type' => $academic_data['schoolType'],
      'category' => $academic_data['schoolCategory'],
      'CEVA_certificate' => $academic_data['CEVA_certificate'] ?? null,
      'condition' => $academic_data['studentCondition'],
      'observations' => $academic_data['observations'] ?? null,
      'photo' => $photoPath,
      'academic_validation' => $pdfPath,
      'person_id' => $person_id,
    ]);

    foreach ($familiar_data as $familiar) {
      Family::create([
        'document_type' => $familiar['documentType'],
        'document_number' => $familiar['documentNumber'],
        'full_names' => $familiar['fullName'],
        'relationship' => $familiar['relationship'],
        'phone' => $familiar['phone'] ?? null,
        'email' => $familiar['email'],
        'cell_phone' => $familiar['mobile'],
        'address' => $familiar['address'],
        'occupation' => $familiar['occupation'],
        'person_id' => $person_id,
      ]);
    }

    $existingEnroll = Enroll::where('person_id', $person_id)
      ->where('period_id', $enroll_data['period'])
      ->first();

    if ($existingEnroll) {
      throw new \Exception('Este estudiante ya se encuentra matriculado en este periodo.');
    }
    $enroll = Enroll::create([
      'person_id' => $person_id,
      'period_id' => $enroll_data['period'],
      'study_program_id' => $enroll_data['studyProgram'],
      'study_plan_id' => $enroll_data['studyPlan'],
      'cycle_id' => $enroll_data['cycle'],
      'shift_id' => $enroll_data['shift'],
      'section_id' => $enroll_data['section'],
      'registration_date' => $enroll_data['enrollmentDate'],
      'scale_id' => $enroll_data['scale'] ?? null,
      'scale_authorization_document_type' => $enroll_data['documentType'] ?? null,
      'scale_authorization_document_number' => $enroll_data['documentNumber'] ?? null,
      'scale_authorization_full_names' => $enroll_data['fullName'] ?? null,
      'observations' => $enroll_data['observations'] ?? null,
      'is_full_payment' => $enroll_data['fullPayment'] ? 1 : 0
    ]);




    return [
      'person_id' => $person_id,
      'enroll_id' => $enroll->id
    ];
  }
  public static function delete(int $id)
  {
    $enroll = Enroll::find($id);
    if ($enroll) {
      $enroll->delete();
      return true;
    } else {
      return false;
    }
  }
  public static function get(int $id)
  {
    $enroll = Enroll::find($id);
    return $enroll;
  }

  public static function updateEnroll(int $id, Request $request)
  {
    if ($request->scale_id == -1) {
      $request->merge([
        'scale_id' => null,
        'scale_authorization_document_number' => null,
        'scale_authorization_document_type' => null,
        'scale_authorization_full_names' => null,
      ]);
    }
    $enroll = Enroll::find($id);
    $enroll->update($request->all());
    return $enroll;
  }

  public static function getValidationsForEnrollment() {

    $errors = [];

    $diffInMonths = 0;

    $currentPeriod = Period::where('status', 'SIN TERMINO')->first();

    if (!$currentPeriod) {
      $errors[] = [
        'title' => 'No hay periodo activo',
        'caption' => 'Es necesario que tengas establecido un periodo activo.'
      ];
    }

    if (!$currentPeriod->start_date || !$currentPeriod->end_date) {
      $errors[] = [
        'title' => 'Fechas de periodo no configuradas',
        'caption' => 'Es necesario que tengas establecidas las fechas de inicio y fin del periodo activo, para poder generar las pensiones.
        Por favor, ve a la sección de <strong>Procesos Académicos > Periodos académicos</strong> y configura las fechas del periodo activo.
        '
      ];
    }

    if ($currentPeriod->start_date && $currentPeriod->end_date) {
      $diffInMonths = ceil(Carbon::parse($currentPeriod->start_date)->diffInMonths(Carbon::parse($currentPeriod->end_date)));
    }
    
    $paymentConceptEnrollment = PaymentConcept::exists('code', PaymentConceptEnum::MATRICULA_CONCEPT_CODE);

    $paymentConceptPension = PaymentConcept::exists('code', PaymentConceptEnum::PENSIONES_CONCEPT_CODE);

    if (!$paymentConceptEnrollment) {
      $errors[] = [
        'title' => 'Concepto de pago para matrícula no configurado',
        'caption' => 'Es necesario que tengas configurado el concepto de pago para matrícula.
        Por favor, ve a la sección de <strong>Tesorería > Conceptos de pago</strong> y configura el concepto de pago para matrícula.'
      ];
    }

    if (!$paymentConceptPension) {
      $errors[] = [
        'title' => 'Concepto de pago para pensiones no configurado',
        'caption' => 'Es necesario que tengas configurado el concepto de pago para pensiones.
        Por favor, ve a la sección de <strong>Tesorería > Conceptos de pago</strong> y configura el concepto de pago para pensiones.'
      ];
    }

    return [
      'hasCurrentPeriod' => !!$currentPeriod,
      'hasPeriodDates' => !!$currentPeriod->start_date && !!$currentPeriod->end_date,
      'hasPaymentConceptEnrollment' => !!$paymentConceptEnrollment,
      'hasPaymentConceptPension' => !!$paymentConceptPension,
      'maxMonthsPeriod' => $diffInMonths,
      'errors' => $errors
    ];
  }
}
