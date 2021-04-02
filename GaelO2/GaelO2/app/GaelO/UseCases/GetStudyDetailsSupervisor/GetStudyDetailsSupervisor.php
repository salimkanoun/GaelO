<?php

namespace App\GaelO\UseCases\GetStudyDetailsSupervisor;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\StudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetStudy\StudyEntity;
use App\GaelO\UseCases\GetVisitGroup\VisitGroupEntity;
use App\GaelO\UseCases\GetVisitType\VisitTypeEntity;
use Exception;

class GetStudyDetailsSupervisor {

    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationService $authorizationService){
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetStudyDetailsSupervisorRequest $getStudyDetailsSupervisorRequest, GetStudyDetailsSupervisorResponse $etStudyDetailsSupervisorResponse) : void {

        try{
            $this->checkAuthorization($getStudyDetailsSupervisorRequest->currentUserId, $getStudyDetailsSupervisorRequest->studyName);

            $studyDetails = $this->studyRepositoryInterface->getStudyDetails($getStudyDetailsSupervisorRequest->studyName);

            $studyDetailResponse = [];

            foreach($studyDetails['visit_group_details'] as $visitGroupDetails){
                $visitGroupEntity = VisitGroupEntity::fillFromDBReponseArray($visitGroupDetails);

                foreach($visitGroupDetails['visit_types'] as $visitType){
                    $visitTypeEntity = VisitTypeEntity::fillFromDBReponseArray($visitType);
                    $studyDetailResponse[] = [
                        'visitGroupId'=>$visitGroupEntity->id,
                        'visitGroupModality'=>$visitGroupEntity->modality,
                        'visitTypeId'=>$visitTypeEntity->id,
                        'visitTypeName'=>$visitTypeEntity->name
                    ];
                }

            }


            $etStudyDetailsSupervisorResponse->body = $studyDetailResponse;
            $etStudyDetailsSupervisorResponse->status = 200;
            $etStudyDetailsSupervisorResponse->statusText = 'OK';

        } catch (GaelOException $e ){

            $etStudyDetailsSupervisorResponse->body = $e->getErrorBody();
            $etStudyDetailsSupervisorResponse->status = $e->statusCode;
            $etStudyDetailsSupervisorResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $userId, string $studyName) : void {
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        if( ! $this->authorizationService->isRoleAllowed($studyName) ) {
            throw new GaelOForbiddenException();
        };
    }
}