<?php

namespace App\GaelO\UseCases\ModifyInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\InvestigatorFormService;
use Exception;

class ModifyInvestigatorForm {

    private AuthorizationVisitService $authorizationVisitService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private InvestigatorFormService $investigatorFormService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(AuthorizationVisitService $authorizationVisitService, VisitRepositoryInterface $visitRepositoryInterface, InvestigatorFormService $investigatorFormService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->investigatorFormService = $investigatorFormService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ModifyInvestigatorFormRequest $modifyInvestigatorFormRequest, ModifyInvestigatorFormResponse $modifyInvestigatorFormResponse){

        try{

            if( ! isset($modifyInvestigatorFormRequest->validated) || !isset($modifyInvestigatorFormRequest->visitId) ){
                throw new GaelOBadRequestException('VisitID and Validated Status are mandatory');
            }

            $visitContext = $this->visitRepositoryInterface->getVisitContext($modifyInvestigatorFormRequest->visitId);
            $studyName = $visitContext['visit_type']['visit_group']['study_name'];
            $isLocalFormNeeded = $visitContext['visit_type']['local_form_needed'];

            $this->checkAuthorization(
                $modifyInvestigatorFormRequest->currentUserId,
                $modifyInvestigatorFormRequest->visitId,
                $visitContext['state_investigator_form'],
                $isLocalFormNeeded
            );

            $this->investigatorFormService->setCurrentUserId($modifyInvestigatorFormRequest->currentUserId);
            $this->investigatorFormService->setVisitContext($visitContext);
            $this->investigatorFormService->updateInvestigatorForm($modifyInvestigatorFormRequest->data, $modifyInvestigatorFormRequest->validated);

            $actionDetails = [
                'raw_data' => $modifyInvestigatorFormRequest->data,
                'validated' => $modifyInvestigatorFormRequest->validated
            ];

            $this->trackerRepositoryInterface->writeAction($modifyInvestigatorFormRequest->currentUserId, Constants::ROLE_INVESTIGATOR, $studyName, $modifyInvestigatorFormRequest->visitId, Constants::TRACKER_MODIFY_INVESTIGATOR_FORM, $actionDetails);

            $modifyInvestigatorFormResponse->status = 200;
            $modifyInvestigatorFormResponse->statusText =  'OK';

        } catch (GaelOException $e){

            $modifyInvestigatorFormResponse->body = $e->getErrorBody();
            $modifyInvestigatorFormResponse->status = $e->statusCode;
            $modifyInvestigatorFormResponse->statusText =  $e->statusText;

        }catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, int $visitId, string $visitInvestigatorFormStatus, bool $investigatorFormNeeded){

        if(in_array($visitInvestigatorFormStatus, [Constants::INVESTIGATOR_FORM_DONE])){
            throw new GaelOForbiddenException();
        };

        if(!$investigatorFormNeeded){
            throw new GaelOForbiddenException();
        };

        $this->authorizationVisitService->setCurrentUserAndRole($currentUserId, Constants::ROLE_INVESTIGATOR);
        $this->authorizationVisitService->setVisitId($visitId);
        if ( ! $this->authorizationVisitService->isVisitAllowed()){
            throw new GaelOForbiddenException();
        }
    }
}