<?php

namespace App\GaelO\UseCases\CreateVisit;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;
use App\GaelO\Services\VisitService;

class CreateVisit {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService, VisitService $visitService){
        $this->visitService = $visitService;
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;

    }

    public function execute(CreateVisitRequest $createVisitRequest, CreateVisitResponse $createVisitResponse) : void {

        $existingVisit = $this->persistenceInterface->isExistingVisit($createVisitRequest->patientCode,
                                                        $createVisitRequest->visitTypeId);

        if($existingVisit) {
            $createVisitResponse->status = 209;
            $createVisitResponse->statusText = 'Conflict';
            return;
        }

        $this->visitService->createVisit(
            $createVisitRequest->creatorUserId,
            $createVisitRequest->patientCode,
            $createVisitRequest->acquisitionDate,
            $createVisitRequest->visitTypeId,
            $createVisitRequest->statusDone,
            $createVisitRequest->reasonForNotDone);
        $createVisitResponse->status = 201;
        $createVisitResponse->statusText = 'Created';
    }


}