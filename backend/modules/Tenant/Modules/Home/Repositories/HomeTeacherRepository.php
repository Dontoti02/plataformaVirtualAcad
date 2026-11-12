<?php

namespace Modules\Tenant\Modules\Home\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Modules\Classroom\Models\Answer;
use Modules\Tenant\Modules\Classroom\Models\Assistance;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Publication;
use Modules\Tenant\Modules\File\Repositories\FileRepository;
use Modules\Tenant\Modules\Management\Models\Staff;
use Modules\Tenant\Modules\Schedule\Models\Schedule;
use Modules\Tenant\Modules\User\Helpers\RolHelper;

class HomeTeacherRepository
{
    public static function home()
    {
        $user = RolHelper::validateTeacherAccess();

        $teacher = Staff::select()
            ->where('person_id', $user->person_id)
            ->orderBy('registration_date', 'desc')
            ->first();

        $period_id = $teacher->period_id;
        $person_id = $teacher->person_id;

        $classrooms = self::getClassrooms($period_id, $person_id);
        $answers = self::getAnswers($classrooms->ids);
        $assistants = self::getAssistants($classrooms->ids);
        $publications = self::getPublications($classrooms->ids, $person_id);
        $schedule = self::getSchedule($classrooms->ids);

        $summary = [
            [
                'title' => 'Cursos a cargo',
                'value' => $classrooms->total,
            ],
            [
                'title' => 'Tareas evaluadas',
                'value' => $answers->totalEvaluated,
            ],
            [
                'title' => 'Publicaciones',
                'value' => $publications->total,
            ],
            [
                'title' => 'Horas de clase a la semana',
                'value' => $schedule->totalHours,
            ],
        ];

        $result = [
            'summary' => $summary,
            'courses' => $classrooms->items,
            'tasks' => $answers->tasks,
            'assistants' => $assistants->items,
            'publications' => $publications->items,
            'schedule' => $schedule->items,
        ];

        return $result;
    }

    public static function getClassrooms(int $period_id, int $person_id)
    {
        $classrooms = Classroom::selectRaw("
            classroom.id,
            course.name,
            period.name as period,
            COUNT(DISTINCT participant.id) as students
        ")
            ->join('course', 'classroom.course_id', 'course.id')
            ->join('teacher', 'classroom.id', 'teacher.classroom_id')
            ->join('period', 'classroom.period_id', 'period.id')
            ->leftJoin('participant', 'classroom.id', 'participant.classroom_id')
            ->where('classroom.period_id', $period_id)
            ->where('teacher.person_id', $person_id)
            ->groupBy('classroom.id')
            ->orderBy('course.name', 'asc')
            ->distinct()
            ->get();

        $total = $classrooms->count();
        $classrooms_id = $classrooms->pluck('id')->toArray();

        $result = (object) [
            'items' => $classrooms,
            'total' => $total,
            'ids' => $classrooms_id,
        ];

        return $result;
    }

    public static function getAnswers(array $classrooms_id)
    {
        $query = Answer::select([
            'answer.id',
            'content.id as content_id',
            'content_group.classroom_id',
            'course.name as course',
            'content.type',
            'content.title',
            'content.date_start',
            'content.date_limit',
        ]);
        $query->join('content', 'answer.content_id', 'content.id');
        $query->join('content_group', 'content.content_group_id', 'content_group.id');
        $query->join('classroom', 'content_group.classroom_id', 'classroom.id');
        $query->join('course', 'classroom.course_id', 'course.id');
        $query->whereIn('content_group.classroom_id', $classrooms_id);

        $pending = (clone $query)
            ->whereIn('answer.status', ['delivered', 'overdue'])
            ->orderBy('content.date_start', 'asc');

        $evaluated = (clone $query)
            ->where('answer.status', 'evaluated')
            ->orderBy('content.date_start', 'desc');

        $totalEvaluated = $evaluated->count();

        $tasks = [
            'pending' => $pending->limit(3)->get(),
            'evaluated' => $evaluated->limit(3)->get(),
        ];

        $result = (object) [
            'tasks' => $tasks,
            'totalEvaluated' => $totalEvaluated,
        ];

        return $result;
    }

    public static function getAssistants(array $classrooms_id)
    {
        $assistants = Assistance::selectRaw("
            course.name as course,
            COUNT(assistance.id) as total,
            CAST(SUM(IF(assistance.status = 'attended', 1, 0)) AS UNSIGNED) as attended,
            CAST(SUM(IF(assistance.status = 'absence', 1, 0)) AS UNSIGNED) as absence,
            CAST(SUM(IF(assistance.status = 'late', 1, 0)) AS UNSIGNED) as late
        ")
            ->join('classroom', 'assistance.classroom_id', 'classroom.id')
            ->join('course', 'classroom.course_id', 'course.id')
            ->whereIn('assistance.classroom_id', $classrooms_id)
            ->groupBy('course.name')
            ->get()
            ->map(function ($item) {
                return [
                    'course' => $item->course,
                    'total' => $item->total,
                    'attended' => [
                        'value' => $item->attended,
                        'percentage' => $item->total > 0
                            ? round(($item->attended * 100) / $item->total) . '%'
                            : '0%',
                    ],
                    'absence' => [
                        'value' => $item->absence,
                        'percentage' => $item->total > 0
                            ? round(($item->absence * 100) / $item->total) . '%'
                            : '0%',
                    ],
                    'late' => [
                        'value' => $item->late,
                        'percentage' => $item->total > 0
                            ? round(($item->late * 100) / $item->total) . '%'
                            : '0%',
                    ],
                ];
            });

        $result = (object) [
            'items' => $assistants,
        ];

        return $result;
    }

    public static function getPublications(array $classrooms_id, int $person_id)
    {
        $query = Publication::select([
            'publication.id',
            'person.names as person',
            'user.avatar as photo',
            'course.name as course',
            'publication.created_at as date',
            'publication.value',
        ]);
        $query->join('classroom', 'publication.classroom_id', 'classroom.id');
        $query->join('course', 'classroom.course_id', 'course.id');
        $query->join('person', 'publication.person_id', 'person.id');
        $query->join('user', 'person.id', 'user.person_id');
        $query->whereIn('publication.classroom_id', $classrooms_id);

        $publications = (clone $query)->orderBy('publication.created_at', 'desc')->limit(3)->get();

        foreach ($publications as $publication) {
            $publication->files = FileRepository::listByModel($publication);
        }

        $total = (clone $query)->where('publication.person_id', $person_id)->count();

        $result = (object) [
            'items' => $publications,
            'total' => $total,
        ];

        return $result;
    }

    public static function getSchedule(array $classrooms_id)
    {
        $classrooms = Classroom::select([
            'classroom.id',
            'course.id as course_id',
            'course.name as course_name',
            'cycle.id as cycle_id',
            'cycle.name as cycle_name',
            'section.id as section_id',
            'section.name as section_name',
        ])
            ->join('schedule', 'classroom.id', 'schedule.classroom_id')
            ->join('course', 'classroom.course_id', 'course.id')
            ->join('cycle', 'course.cycle_id', 'cycle.id')
            ->join('section', 'classroom.section_id', 'section.id')
            ->whereIn('schedule.classroom_id', $classrooms_id)
            ->distinct()
            ->get();

        $schedules = [];
        $totalMinutes = 0;
        foreach ($classrooms as $classroom) {

            $add['id'] = $classroom->id;
            $add['course'] = [
                'id' => $classroom->course_id,
                'name' => $classroom->course_name,
            ];
            $add['cycle'] = [
                'id' => $classroom->cycle_id,
                'name' => $classroom->cycle_name,
            ];
            $add['section'] = [
                'id' => $classroom->section_id,
                'name' => $classroom->section_name,
            ];

            $days = Schedule::select([
                'id',
                'day',
                'hour_start',
                'hour_end',
            ])
                ->where('classroom_id', $classroom->id)
                ->get();
            $add['days'] = $days;

            $schedules[] = $add;

            foreach ($days as $day) {
                $hourStart = Carbon::createFromFormat('H:i', $day->hour_start);
                $hourEnd = Carbon::createFromFormat('H:i', $day->hour_end);

                $minutes = $hourStart->diffInMinutes($hourEnd);
                $totalMinutes += $minutes;
            }
        }

        $totalHours = intdiv($totalMinutes, 60);
        $remainingMinutes = $totalMinutes % 60;

        $totalHoursString = "{$totalHours}h";
        $remainingMinutesString = $remainingMinutes > 0 ? "{$remainingMinutes}m" : '';

        $result = (object) [
            'items' => $schedules,
            'totalHours' => $totalHoursString . $remainingMinutesString,
        ];

        return $result;
    }
}
