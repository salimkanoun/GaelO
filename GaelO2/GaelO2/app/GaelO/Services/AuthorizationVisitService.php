<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\VisitRepository;

class AuthorizationVisitService {

    private AuthorizationPatientService $authorizationPatientService;
    private VisitRepository $visitRepository;

    private string $requestedRole;

    protected int $visitId;
    protected array $visitData;
    protected string $studyName;

    public string $visitUploadStatus;

    public function __construct(AuthorizationPatientService $authorizationPatientService, VisitRepository $visitRepository)
    {
        $this->visitRepository = $visitRepository;
        $this->authorizationPatientService = $authorizationPatientService;
    }

    public function setCurrentUserAndRole(int $userId, string $role)
    {
        $this->requestedRole = $role;
        $this->userId = $userId;
        $this->authorizationPatientService->setCurrentUserAndRole($userId, $role);
    }

    public function setVisitId(int $visitId){
        $this->visitId = $visitId;
        $visitContext = $this->visitRepository->getVisitContext($visitId);

        $this->stateQualityControl = $visitContext['state_quality_control'];
        $this->patientStudy = $visitContext['visit_type']['visit_group']['study_name'];
        $this->patientCenter = $visitContext['patient']['center_code'];
        $this->patientCode = $visitContext['patient']['code'];
        $this->visitUploadStatus = $visitContext['upload_status'];

        $this->authorizationPatientService->setPatientEntity($visitContext['patient']);

    }

    public function isVisitAllowed(): bool {
        //Check that called Role exists for users and visit is not deleted
        if ($this->requestedRole === Constants::ROLE_REVIEWER) {
            //SK Ici pb d'acces va se poser pour les etude ancillaire, le review aviaible va dependre du scope d'etude
            $this->visitRepository->isVisitAvailableForReview($this->visitId, $this->patientStudy, $this->userId);

            return $this->authorizationPatientService->isPatientAllowed();
        } else if ($this->requestedRole === Constants::ROLE_CONTROLLER) {
            //For controller controller role should be allows and visit QC status be not done or awaiting definitive conclusion
            $allowedControllerStatus = array(Constants::QUALITY_CONTROL_NOT_DONE, Constants::QUALITY_CONTROL_WAIT_DEFINITIVE_CONCLUSION);
            if (in_array($this->stateQualityControl, $allowedControllerStatus) && $this->visitUploadStatus === Constants::UPLOAD_STATUS_DONE) {
                return $this->authorizationPatientService->isPatientAllowed();
            } else {
                return false;
            }
        } else {
            //Investigator, Supervisor, Monitor simply accept when patient is available in patient's study (no specific rules)
            return $this->authorizationPatientService->isPatientAllowed();
        }

    }

}
