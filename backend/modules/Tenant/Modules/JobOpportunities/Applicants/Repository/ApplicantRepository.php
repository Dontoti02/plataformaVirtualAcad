<?php

namespace Modules\Tenant\Modules\JobOpportunities\Applicants\Repository;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Enum\EmailBodyTemplate;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\JobOpportunities\Applicants\Enums\ApplicantEnum;
use Modules\Tenant\Modules\JobOpportunities\Applicants\Mailings\ApplicantMailing;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Enums\JobOfferStateEnum;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobCompany;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOffer;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOfferApplications;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOfferCv;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class ApplicantRepository
{
  public static function checkOffer($slug)
  {
    $session = SessionManager::get();
    $user = User::find($session->id);

    $find = JobOffer::where('slug', $slug)
      ->with([
        'company',
        'category',
        'schedule',
        'location',
        'contractType',
      ])
      ->first();
    if (!$find) {
      return null;
    }

    $applicant = JobOfferApplications::where('user_id', $user?->id)
      ->where('offer_id', $find->id)
      ->first();

    return [
      'hasApplied' => !!$applicant,
      'applicationId' => $applicant?->id,
      'offerId' => $find->id,
      'offerSlug' => $find->slug,
      'offerTitle' => $find->title,
      'isActive' => $find->state_id === JobOfferStateEnum::ACTIVE_ID,
      'applicantName' => $user?->person?->names ?? null,
      'cvs' =>  $user->cvs
    ];
  }

  public static function uploadCV($request)
  {
    $session = SessionManager::get();

    $user = User::find($session->id);

    $file = $request->file('file');

    if (!$file) {
      throw new \Exception('No file uploaded');
    }

    $hashName = $file->hashName();

    $path = $file->storeAs('public/cvs', $hashName);

    $path = str_replace('public/', '', $path);

    $cv = $user->cvs()->create([
      'version' => 'CV versión ' . ($user->cvs()->count() + 1) . ' - ' . now()->format('d-m-Y H:i:s'),
      'url' => $path,
    ]);

    return $cv;
  }

  public static function applyOffer($request)
  {
    $session = SessionManager::get();
    $user = User::find($session->id);

    $offer = JobOffer::findOrFail($request->offerId);

    if (!$offer) {
      throw new \Exception('Oferta no encontrada');
    }

    $application = JobOfferApplications::where('user_id', $user->id)
      ->where('offer_id', $offer->id)
      ->first();

    if ($application) {
      throw new \Exception('Ya has aplicado a esta oferta');
    }

    $cv = JobOfferCv::find($request->cvId);

    $applicant = JobOfferApplications::create([
      'fullname' => $request->fullname ?? $user->person?->names,
      'message' => $request->message ?? null,
      'status' => ApplicantEnum::APPLICANT_STATE_POSTULATED,
      'user_id' => $user->id,
      'offer_id' => $offer->id,
      'cv' => $cv->url,
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    $company = $offer->company;

    if ($company->mailbox) {
      $host = $request->header('x-subdomain');
      ApplicantMailing::applicantToCompany([
        'subject' => "Nueva postulación a la oferta: " . $offer->title,
        'offerTitle' => $offer->title,
        'applicantName' => $applicant->fullname,
        'applicantEmail' => $user->email,
        'applicantPhone' => $user->person->phone ?? 'No proporcionado',
        'cvUrl' => url('storage/' . $cv->url),
        'offerUrl' => 'https://' . $host . '/bolsa-laboral-panel/postulations?companyId=' . $company->id . '&offerId=' . $offer->id,
        'companyMailBox' =>  $offer->company->mailbox
      ]);
    }
    if ($user->email) {
      ApplicantMailing::applicantConfirmation([
        'subject' => "Tu postulación a la oferta: " . $offer->title . " fue exitosa",
        'offerTitle' => $offer->title,
        'applicantName' => $applicant->fullname,
        'applicantEmail' => $user->email,
        'companyName' => $company->name,
        'cvUrl' => url('storage/' . $cv->url),
      ]);
    }

    return ApplicantRepository::checkOffer($offer->slug);
  }

  public static function myApplications(Request $request)
  {
    $session = SessionManager::get();
    $user = User::find($session->id);

    if (!$user) {
      throw new \Exception('Usuario no encontrado');
    }

    $page = $request->query('page', 1);

    $query = JobOfferApplications::where('user_id', $user->id)
      ->with(['offer'])
      ->paginate(10, ['*'], 'page', $page);

    $applications = $query->map(function ($application) {
      $count = JobOfferApplications::where('offer_id', $application->offer_id)
        ->count();
      return [
        'id' => $application->id,
        'offerId' => $application->offer_id,
        'offerTitle' => $application->offer->title,
        'offerSlug' => $application->offer->slug,
        'status' => $application->status,
        'statusLabel' => ApplicantEnum::APPLICANT_STATE_POSTULATED,
        'totalPostulated' => $count,
        'postulatedAt' => $application->created_at->format('d-m-Y H:i:s'),
      ];
    });

    $allApplicationsIds = JobOfferApplications::select('offer_id')
      ->where('user_id', $user->id)
      ->pluck('offer_id')
      ->toArray();

    $lastOffers = JobOffer::orderBy('publication_date', 'desc')
      ->where('state_id', JobOfferStateEnum::ACTIVE_ID)
      ->whereNotIn('id', $allApplicationsIds)
      ->take(10)
      ->get();

    $pagination = $query->toArray();

    return [
      'applications' => $applications,
      'pagination' => [
        'totalItems' => $pagination['total'],
        'currentPage' => $pagination['current_page'],
        'lastPage' => $pagination['last_page'],
        'itemsPerPage' => $pagination['per_page'],
      ],
      'lastOffers' => $lastOffers->map(function ($offer) {
        return [
          'id' => $offer->id,
          'title' => $offer->title,
          'slug' => $offer->slug,
          'publicationDate' => $offer->publication_date->format('d-m-Y H:i:s'),
        ];
      })->toArray(),
    ];
  }

  public static function cancelApplication($id)
  {
    $application = JobOfferApplications::findOrFail($id);

    if (!$application) {
      throw new \Exception('Postulación no encontrada');
    }

    $offer = $application->offer;

    if ($application->status === ApplicantEnum::APPLICANT_STATE_ACCEPTED) {
      throw new \Exception('No puedes anular una postulación que esté en estado "Aceptado"');
    }

    if ($offer->state_id === JobOfferStateEnum::FINISHED_ID) {
      throw new \Exception('No puedes anular una postulación a una oferta que ya Finalizó');
    }

    $application->delete();

    return [
      'message' => 'Postulación anulada exitosamente',
      'applicationId' => $application->id,
    ];
  }

  public static function myCvs()
  {
    $session = SessionManager::get();
    $user = User::find($session->id);

    if (!$user) {
      throw new \Exception('Usuario no encontrado');
    }

    return $user->cvs()->orderBy('created_at','desc')->get()->map(function ($cv) {
      return [
        'id' => $cv->id,
        'version' => $cv->version,
        'url' => $cv->url,
        'createdAt' => $cv->created_at->format('d-m-Y H:i:s'),
      ];
    });
  }
  public static function deleteMyCv($id)
  {
    $cv = JobOfferCv::findOrFail($id);

    if (!$cv) {
      throw new \Exception('CV no encontrado');
    }

    $cv->delete();

    return [
      'message' => 'CV eliminado exitosamente.',
      'cvId' => $id,
    ];
  }

  public static function applicationsByOfferFilters($offerId, $companyId)
  {
    $session = SessionManager::get();
    $user = User::find($session->id);
    if (!$user) {
      throw new \Exception('Usuario no encontrado');
    }

    if ($user->hasRole(RolTenant::COMPANY)) {
      if ($companyId && $companyId != $user->company->id) {
        return [
          'permission' => [
            'state' => false,
            'message' => 'No tienes permiso para ver las postulaciones de esta empresa',
          ],
          'filters' => [],
        ];
      }
      if ($offerId && JobOffer::where('id', $offerId)->where('company_id', $user->company->id)->doesntExist()) {
        return [
          'permission' => [
            'state' => false,
            'message' => 'No tienes permiso para ver las postulaciones de esta oferta',
          ],
          'filters' => [],
        ];
      }
      $companyId = $user->company->id;
    }

    $queryCompanies = JobCompany::query();

    if ($companyId) {
      $queryCompanies->where('id', $companyId);
    }

    $companies = $queryCompanies->get();

    $offers = JobOffer::whereIn('company_id', $companies->pluck('id'))
      ->get()
      ->map(function ($offer) {
        $totalApplicants = JobOfferApplications::where('offer_id', $offer->id)->count();
        return [
          'id' => $offer->id,
          'title' => $offer->title,
          'slug' => $offer->slug,
          'totalApplicants' => $totalApplicants,
          'companyId' => $offer->company_id,
        ];
      });

    $filters = [
      'companies' => $companies->map(function ($company) {
        return [
          'id' => $company->id,
          'name' => $company->name,
        ];
      }),
      'offers' => $offers,
    ];

    return [
      'permission' => [
        'state' => true,
        'message' => 'Permiso concedido',
      ],
      'filters' => $filters,
    ];
  }

  public static function applicationsByOffer($offerId, $companyId = null)
  {
    $session = SessionManager::get();
    $user = User::find($session->id);
    if (!$user) {
      throw new \Exception('Usuario no encontrado');
    }

    if ($user->hasRole(RolTenant::COMPANY)) {
      $companyId = $user->company->id;
    }

    $query= JobOfferApplications::whereHas('offer', function ($query) use ($companyId) {
        if ($companyId) {
          $query->where('company_id', $companyId);
        }
      })->where('offer_id', $offerId)
      ->orderBy('created_at', 'desc')
      ->get();

    $offer = JobOffer::find($offerId);

    return [
      'offer' => [
        'id' => $offer->id ?? null,
        'title' => $offer->title,
        'slug' => $offer->slug,
      ],
      'applicants' => $query->map(function ($application) {
        return [
          'id' => $application->id,
          'fullname' => $application->fullname,
          'message' => $application->message,
          'status' => $application->status,
          'appliedAt' => $application->created_at->format('d-m-Y H:i:s'),
          'userId' => $application->user_id,
          'email' => $application->user->email,
          'phone' => $application->user->person?->phone ?? 'No proporcionado',
          'cvUrl' => $application->cv,
        ];
      })
    ];
  }

  public static function setState($id, $request)
  {
    $application = JobOfferApplications::findOrFail($id);

    if (!$application) {
      throw new \Exception('Postulación no encontrada');
    }

    if (!$application->status === ApplicantEnum::APPLICANT_STATE_POSTULATED) {
      throw new \Exception('No puedes cambiar el estado de una postulación que no está en estado "Postulada"');
    }
    $state = $request['state'] ?? null;
    $feedback = $request['feedback'] ?? null;
    $application->status = $state;
    if ($feedback) {
      $application->feedback = $feedback;
      $application->feedback_date = Carbon::now();
    }
    $application->save();

    $icon = $request['state'] === ApplicantEnum::APPLICANT_STATE_ACCEPTED ? '✅' : ($request['state'] === ApplicantEnum::APPLICANT_STATE_REJECTED ? '❌' : 'ℹ️');

    $message = "¡Felicitaciones! Has sido seleccionado para continuar con el proceso de selección. La empresa se pondrá en contacto contigo pronto.";

    if ($request['state'] === ApplicantEnum::APPLICANT_STATE_REJECTED) {
      $message = "Lamentablemente, no has sido seleccionado para continuar con el proceso de selección. Te deseamos mucho éxito en tus futuras postulaciones.";
    }

    ApplicantMailing::applicantDecision([
      'subject' => "Estado de tu postulación a la oferta: " . $application->offer->title,
      'statusIcon' => $icon,
      'applicantName' => $application->fullname,
      'offerTitle' => $application->offer->title,
      'companyName' => $application->offer->company->name,
      'message' => $message,
      'feedback' => $feedback,
      'applicantEmail' => $application->user->email,
    ]);

    return [
      'message' => 'Estado de la postulación actualizado exitosamente',
      'applicationId' => $application->id,
      'newState' => $state,
    ];
  }
}
