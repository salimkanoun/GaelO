<?php

namespace App\GaelO\UseCases\GetPatient;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationPatientService;
use App\GaelO\UseCases\GetPatient\GetPatientRequest;
use App\GaelO\UseCases\GetPatient\GetPatientResponse;
use Exception;

class GetPatient {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationPatientService $authorizationService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetPatientRequest $getPatientRequest, GetPatientResponse $getPatientResponse) : void
    {
        try{
            $code = $getPatientRequest->code;

            if ($code == 0) throw new GaelOForbiddenException();

            $this->checkAuthorization($getPatientRequest->currentUserId, $getPatientRequest->role, $code );
            $dbData = $this->persistenceInterface->find($code);
            $responseEntity = PatientEntity::fillFromDBReponseArray($dbData);

            //If Reviewer hide patient's center
            if( $getPatientRequest->role === Constants::ROLE_REVIEWER){
                $responseEntity->centerCode = null;
            }

            $getPatientResponse->body = $responseEntity;
            $getPatientResponse->status = 200;
            $getPatientResponse->statusText = 'OK';

        } catch  (GaelOException $e){

            $getPatientResponse->status = $e->statusCode;
            $getPatientResponse->statusText = $e->statusText;
            $getPatientResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $currentUserid, string $role, int $patientCode ){
        $this->authorizationService->setCurrentUserAndRole($currentUserid, $role);
        $this->authorizationService->setPatient($patientCode);
        if( ! $this->authorizationService->isPatientAllowed() ){
            throw new GaelOForbiddenException();
        };

    }

}

?>
