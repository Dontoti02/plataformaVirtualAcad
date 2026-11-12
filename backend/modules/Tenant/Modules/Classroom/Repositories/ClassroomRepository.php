<?php

namespace Modules\Tenant\Modules\Classroom\Repositories;

use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Illuminate\Support\Facades\Storage;
use Modules\Tenant\Modules\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Participant;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Management\Models\Period;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class ClassroomRepository
{
    /**
     * Clases para elegir en «Reporte de evaluaciones»: secretaría y
     * administración ven todas; un docente, las suyas; un estudiante, en las
     * que participa. Mismo formato que la línea actual.
     */
    public static function options(Request $request)
    {
        ClassroomHelper::validateOptionsRequest($request);

        $session = SessionManager::get();
        $user = User::find($session->id);

        $isTeacher = $user->rol_id === RolTenant::TEACHER;
        $isStudent = $user->rol_id === RolTenant::STUDENT;

        $search = trim((string) $request->input('search'));
        $periodId = $request->input('period_id');

        return Classroom::select([
            'classroom.id',
            'classroom.period_id',
            'period.name as period',
            'course.name as course',
            'cycle.name as cycle',
            'shift.name as shift',
            'section.name as section',
        ])
            ->join('period', 'classroom.period_id', 'period.id')
            ->join('course', 'classroom.course_id', 'course.id')
            ->join('cycle', 'course.cycle_id', 'cycle.id')
            ->join('shift', 'classroom.shift_id', 'shift.id')
            ->join('section', 'classroom.section_id', 'section.id')
            ->when($periodId, fn ($query) => $query->where('classroom.period_id', $periodId))
            ->when($isTeacher, fn ($query) => $query->whereExists(fn ($exists) => $exists
                ->selectRaw('1')
                ->from('teacher')
                ->whereColumn('teacher.classroom_id', 'classroom.id')
                ->where('teacher.person_id', $user->person_id)))
            ->when($isStudent, fn ($query) => $query->whereExists(fn ($exists) => $exists
                ->selectRaw('1')
                ->from('participant')
                ->whereColumn('participant.classroom_id', 'classroom.id')
                ->where('participant.person_id', $user->person_id)))
            ->when($search !== '', fn ($query) => $query->where(fn ($sub) => $sub
                ->orWhere('course.name', 'like', "%$search%")
                ->orWhere('period.name', 'like', "%$search%")
                ->orWhere('section.name', 'like', "%$search%")))
            ->orderBy('period.name', 'desc')
            ->orderBy('cycle.name', 'asc')
            ->orderBy('course.name', 'asc')
            ->get();
    }

    public static function list(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        $periods = Period::select('id', 'name')
            ->when($request->filled("period_id"), function ($query) use ($request) {
                $query->where('id', $request->period_id);
            })
            ->when(!$request->filled("period_id"), function ($query) {
                $query->whereNot('status', 'SIN TERMINO');
            })
            ->orderBy('name', 'desc')
            ->get();

        $result = [];
        foreach ($periods as $period) {

            $classrooms = $period->classrooms()->selectRaw("
                classroom.id,
                course.name as course,
                person.names as teacher,
                cycle.name as cycle,
                (
                    SELECT COUNT(*) FROM participant WHERE classroom_id = classroom.id
                ) as students,
                classroom.avatar as image
                " . ($user->rol_id === RolTenant::STUDENT ? ",participant.is_favorite" : "") . "
            ")
                ->join('course', 'classroom.course_id', '=', 'course.id')
                ->join('cycle', 'course.cycle_id', '=', 'cycle.id')
                ->when($user->rol_id === RolTenant::STUDENT, function ($query) {
                    $query->join('participant', 'classroom.id', '=', 'participant.classroom_id');
                })
                ->leftJoin('teacher', 'classroom.id', '=', 'teacher.classroom_id')
                ->leftJoin('person', 'teacher.person_id', '=', 'person.id')
                ->when($user->rol_id === RolTenant::STUDENT, function ($query) use ($user) {
                    $query->where('participant.person_id', $user->person_id);
                })
                ->when($user->rol_id === RolTenant::TEACHER, function ($query) use ($user) {
                    $query->where('teacher.person_id', $user->person_id);
                })
                ->when($user->rol_id === RolTenant::STUDENT, function ($query) {
                    $query->orderBy('participant.is_favorite', 'desc');
                })
                ->orderBy('course.name', 'asc')
                ->get();

            $result[] = [
                ...$period->toArray(),
                'classrooms' => $classrooms,
            ];
        }

        return $result;
    }

    public static function get(int $id)
    {
        $classroom = Classroom::selectRaw("
            classroom.id,
            course.name as course,
            person.names as teacher,
            cycle.name as cycle,
            (
                SELECT COUNT(*) FROM participant WHERE classroom_id = classroom.id
            ) as students,
            classroom.avatar as image
        ")
            ->join('course', 'classroom.course_id', 'course.id')
            ->join('cycle', 'course.cycle_id', 'cycle.id')
            ->leftJoin('teacher', 'classroom.id', 'teacher.classroom_id')
            ->leftJoin('person', 'teacher.person_id', 'person.id')
            ->where('classroom.id', $id)
            ->first();

        return $classroom;
    }

    public static function updateImage(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        ClassroomHelper::validateUpdateImageRequest($request);
        ClassroomHelper::validateTeacherAccess($user->person_id, $request->classroom_id);
        PeriodHelper::validateByClassroom($request->classroom_id);

        $classroom = Classroom::byKey('id', $request->classroom_id);

        if ($classroom->avatar) {
            $classroom->avatar = 'public/' . $classroom->avatar;

            if (Storage::exists($classroom->avatar)) {
                Storage::delete($classroom->avatar);
            }
        }

        $file = $request->file('file');
        $path = $file->store('public/classroom');
        $path = str_replace('public/', '', $path);

        $classroom->update([
            'avatar' => $path,
        ]);

        return $path;
    }

    public static function updateFavorite(int $id)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        ClassroomHelper::validateStudentAccess($user->person_id, $id);
        PeriodHelper::validateByClassroom($id);

        $participant = Participant::select()
            ->where('person_id', $user->person_id)
            ->where('classroom_id', $id)
            ->first();

        $participant->update([
            'is_favorite' => !$participant->is_favorite,
        ]);

        return "Clase " . ($participant->is_favorite === true ? 'marcada' : 'desmarcada') . " como favorita correctamente";
    }
}
