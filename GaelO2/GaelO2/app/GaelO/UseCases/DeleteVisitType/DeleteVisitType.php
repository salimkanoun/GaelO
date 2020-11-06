<?php

namespace App\GaelO\UseCases\DeleteVisitType;

use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use Exception;

class DeleteVisitType {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(DeleteVisitTypeRequest $deleteVisitTypeRequest, DeleteVisitTypeResponse $deleteVisitTypeResponse){

        try{

            $this->checkAuthorization($deleteVisitTypeRequest);

            $hasVisits = $this->persistenceInterface->hasVisits($deleteVisitTypeRequest->visitTypeId);
            if($hasVisits) throw new GaelOBadRequestException('Existing Child Visits');

            $this->persistenceInterface->delete($deleteVisitTypeRequest->visitTypeId);
            $deleteVisitTypeResponse->status = 200;
            $deleteVisitTypeResponse->statusText = 'OK';

        }catch(GaelOException $e){
            $deleteVisitTypeResponse->status = $e->statusCode;
            $deleteVisitTypeResponse->statusText = $e->statusText;
            $deleteVisitTypeResponse->body = $e->getErrorBody();

        }catch (Exception $e){
            throw $e;
        }


    }

    public function checkAuthorization(DeleteVisitTypeRequest $deleteVisitTypeRequest){
        $this->authorizationService->setCurrentUser($deleteVisitTypeRequest->currentUserId);
        if( ! $this->authorizationService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }

}
