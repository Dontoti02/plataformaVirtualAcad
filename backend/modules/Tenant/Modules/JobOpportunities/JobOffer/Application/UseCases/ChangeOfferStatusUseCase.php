<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases;

use Illuminate\Http\JsonResponse;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Enums\JobOfferStateEnum;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Domain\Repositories\JobOfferRepositoryInterface;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOfferState;

class ChangeOfferStatusUseCase
{
  public function __construct(
    protected JobOfferRepositoryInterface $repository
  ) {}

   protected array $transitions = [
        JobOfferStateEnum::DRAFT_ID => [
          JobOfferStateEnum::ACTIVE_ID, 
          JobOfferStateEnum::FINISHED_ID, 
          JobOfferStateEnum::SUSPENDED_ID, 
          JobOfferStateEnum::CANCELED_ID
        ],
        JobOfferStateEnum::ACTIVE_ID => [
          JobOfferStateEnum::DRAFT_ID, 
          JobOfferStateEnum::FINISHED_ID, 
          JobOfferStateEnum::SUSPENDED_ID, 
          JobOfferStateEnum::CANCELED_ID
        ],
        JobOfferStateEnum::FINISHED_ID => [], 
        JobOfferStateEnum::SUSPENDED_ID => [
          JobOfferStateEnum::DRAFT_ID, 
          JobOfferStateEnum::ACTIVE_ID, 
          JobOfferStateEnum::FINISHED_ID, 
          JobOfferStateEnum::CANCELED_ID
        ],
        JobOfferStateEnum::CANCELED_ID => [
          JobOfferStateEnum::DRAFT_ID, 
          JobOfferStateEnum::ACTIVE_ID, 
          JobOfferStateEnum::FINISHED_ID, 
          JobOfferStateEnum::SUSPENDED_ID
        ],
    ];

    public function execute(array $request): JsonResponse
    {
      try {
        $offer = $this->repository->findById($request['id']);
        $newStatekey = $request['new_status'];

        if (!$offer) {
          return Response::error('Oferta de trabajo no encontrada', 404);
        }
        $current = $offer->state;
        $newState = JobOfferState::where('key',$newStatekey)->first(); // change to findById repository
    
        if (!$newState) {
          return Response::error('Estado no encontrado', 404);
        }
        $newStateId = $newState->id;
        if (!in_array($newStateId, $this->transitions[$current->id] ?? [])) {
          throw new \DomainException("No se puede cambiar de estado $current->name a $newState->name.");
        }
        $this->repository->changeStatus($offer, $newStateId);
        return Response::success([], 'Estado de la oferta de trabajo actualizado correctamente.');
      } catch (\Exception $e) {
        return Response::error('Error: ' . $e->getMessage());
      }
  
    }
}
