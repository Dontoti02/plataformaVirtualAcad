<?php
namespace Modules\Tenant\Modules\Enroll\Models;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Modules\User\Models\Person;
use Modules\Tenant\Modules\Management\Models\Period;
use Modules\Tenant\Modules\Management\Models\StudyProgram;
use Modules\Tenant\Modules\Management\Models\StudyPlan;
use Modules\Tenant\Modules\Management\Models\Cycle;
use Modules\Tenant\Modules\Management\Models\Shift;
use Modules\Tenant\Modules\Management\Models\Section;


class Enroll extends Model
{
    protected $table = 'enrollment';

    protected $fillable = [
        'person_id',
        'period_id',
        'study_program_id',
        'study_plan_id',
        'cycle_id',
        'shift_id',
        'section_id',
        'registration_date',
        'scale_id',
        'scale_authorization_document_type',
        'scale_authorization_document_number',
        'scale_authorization_full_names',
        'observations',
        'is_full_payment'
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function studyPlan()
    {
        return $this->belongsTo(StudyPlan::class);
    }

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function scale()
    {
        return $this->belongsTo(Scale::class);
    }
}
?>