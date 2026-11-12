<?php

namespace Modules\Tenant\Modules\Home\Repositories;

use Modules\Tenant\Modules\Classroom\Models\Answer;
use Modules\Tenant\Modules\Classroom\Models\Assistance;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Content;
use Modules\Tenant\Modules\Classroom\Models\Publication;
use Modules\Tenant\Modules\Classroom\Models\Student;
use Modules\Tenant\Modules\Classroom\Repositories\ContentRepository;
use Modules\Tenant\Modules\File\Repositories\FileRepository;
use Modules\Tenant\Modules\Schedule\Models\Schedule;
use Modules\Tenant\Modules\User\Helpers\RolHelper;

class HomeStudentRepository
{
    public static function home()
    {
        $user = RolHelper::validateStudentAccess();

        $student = Student::select()
            ->where('person_id', $user->person_id)
            ->orderBy('registration_date', 'desc')
            ->first();

        $period_id = $student->period_id;
        $person_id = $student->person_id;

        $classrooms = self::getClassrooms($period_id, $person_id);
        $alerts = self::getAlerts($classrooms->ids, $person_id);
        $answers = self::getAnswers($classrooms->ids, $person_id);
        $assistants = self::getAssistants($classrooms->ids, $person_id);
        $publications = self::getPublications($classrooms->ids, $person_id);
        $schedule = self::getSchedule($classrooms->ids);

        $summary = [
            [
                'title' => 'Cursos inscritos',
                'value' => $classrooms->total,
            ],
            [
                'title' => 'Tareas presentadas',
                'value' => $answers->totalPresented,
            ],
            [
                'title' => 'Publicaciones',
                'value' => $publications->total,
            ],
            [
                'title' => 'Tardanzas',
                'value' => $assistants->totalLate,
            ],
        ];

        $result = [
            'alerts' => $alerts,
            'summary' => $summary,
            'courses' => $classrooms->items,
            'tasks' => $answers->tasks,
            'assistants' => $assistants->items,
            'publications' => $publications->items,
            'schedule' => $schedule->items,
        ];

        return $result;
    }

    public static function getAlerts(array $classrooms_id, int $person_id)
    {
        $select = Assistance::selectRaw("
            classroom.id,
            course.name,
            COUNT(assistance.id) as total,
            CAST(SUM(IF(assistance.status = 'absence', 1, 0)) AS UNSIGNED) as absences
        ")
            ->join('classroom', 'assistance.classroom_id', 'classroom.id')
            ->join('course', 'classroom.course_id', 'course.id')
            ->whereIn('assistance.classroom_id', $classrooms_id)
            ->where('assistance.person_id', $person_id)
            ->groupBy('classroom.id', 'course.name')
            ->get();

        $selectMap = [];
        foreach ($select as $item) {
            $total = $item->total;
            $absences = $item->absences;

            $alert_percentage = $total ? $absences / $total : 0;

            if ($alert_percentage >= 0.1) {
                $selectMap[] = [
                    'id' => $item->id,
                    'course' => $item->name,
                    'absences' => $absences,
                    'total' => $total,
                ];
            }
        }

        usort($selectMap, function ($a, $b) {
            return $b['absences'] <=> $a['absences'];
        });

        $result = [
            'classrooms' => $selectMap,
        ];

        return $result;
    }

    public static function getClassrooms(int $period_id, int $person_id)
    {
        $classrooms = Classroom::select([
            'classroom.id',
            'course.name',
            'person.names as teacher',
        ])
            ->join('course', 'classroom.course_id', 'course.id')
            ->join('participant', 'classroom.id', 'participant.classroom_id')
            ->leftJoin('teacher', 'classroom.id', 'teacher.classroom_id')
            ->leftJoin('person', 'teacher.person_id', 'person.id')
            ->where('classroom.period_id', $period_id)
            ->where('participant.person_id', $person_id)
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

    public static function getAnswers(array $classrooms_id, int $person_id)
    {

        $contents = Content::whereIn('content_group_id', function ($query) use ($classrooms_id) {
            $query->select('id')
                ->from('content_group')
                ->whereIn('classroom_id', $classrooms_id);
        })->whereIn('type', ['task', 'evaluation'])->get();

        foreach ($contents as $content) {
            ContentRepository::manageStates($content);
        }

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
        $query->where('answer.person_id', $person_id);
        $query->whereIn('content.type', ['task', 'evaluation']);
        $query->where('content.is_visible', true);

        $pending = (clone $query)
            ->where('answer.status', 'pending')
            ->orderBy('content.date_start', 'asc');

        $evaluated = (clone $query)
            ->where('answer.status', 'evaluated')
            ->orderBy('content.date_start', 'desc');

        $totalPresented = (clone $query)
            ->whereIn('answer.status', ['delivered', 'overdue', 'evaluated'])
            ->count();

        $tasks = [
            'pending' => $pending->limit(3)->get(),
            'evaluated' => $evaluated->limit(3)->get(),
        ];

        $result = (object) [
            'tasks' => $tasks,
            'totalPresented' => $totalPresented,
        ];

        return $result;
    }

    public static function getAssistants(array $classrooms_id, int $person_id)
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
            ->where('assistance.person_id', $person_id)
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

        $totalLate = $assistants->sum('late.value');

        $result = (object) [
            'items' => $assistants,
            'totalLate' => $totalLate,
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
        }

        $result = (object) [
            'items' => $schedules,
        ];

        return $result;
    }
}
