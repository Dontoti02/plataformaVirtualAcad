<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases;

use Exception;
use Illuminate\Http\JsonResponse;
use Modules\Shared\Helpers\SessionManager;
use Modules\Shared\Utils\Response;
use Illuminate\Support\Facades\Log;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\DTOs\CreateJobOfferDTO;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Domain\Repositories\CreateJobOfferRepositoryInterface;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class CreateJobOfferUseCase
{
  public function __construct(
    protected CreateJobOfferRepositoryInterface $repository
  ) {}

  public function execute(array $form): JsonResponse
  {
    try {
      $session = SessionManager::get();
      $user = User::find($session->id);
      $isCompany = $user->hasRole(RolTenant::COMPANY);
      $companyId = $form['companyId'] ?? null;
      if ($isCompany) {
        if ($user->company->id !== $companyId && $companyId !== null) {
          return Response::error('No tienes permiso para crear una oferta de trabajo para esta empresa');
        }
        $companyId = $user->company->id;
      }
      $dto = new CreateJobOfferDTO(
        title: $form['title'],
        description: $form['description'],
        requirements: $form['requirements'],
        benefits: $form['benefits'],
        companyId: $companyId,
        categoryId: $form['categoryId'],
        locationId: $form['locationId'],
        contractTypeId: $form['contractTypeId'],
        scheduleId: $form['scheduleId'],
        salary: $form['salary'],
        salaryCurrency: $form['salaryCurrency'],
        address: $form['address'],
        department: $form['department'],
        province: $form['province'],
        country: $form['country'],
        publicationDate: $form['publicationDate'],
        attachments: $form['attachments'] ?? null,
      );
      $this->repository->create($dto);
      return Response::success([], 'Oferta laboral registrada correctamente');
    } catch (Exception $e) {
      Log::error('Error al registrar oferta laboral: ' . $e->getMessage());
      return Response::error('Error: ' . $e->getMessage());
    }
  }
}
