<?php

namespace App\GaelO\UseCases\ModifyCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\ModifyCenter\ModifyCenterRequest;
use App\GaelO\UseCases\ModifyCenter\ModifyCenterResponse;
use App\GaelO\Services\TrackerService;
use App\GaelO\Util;

class ModifyCenter {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
     }


     public function execute(ModifyCenterRequest $centerRequest, ModifyCenterResponse $centerResponse) : void
    {

        if(!$this->persistenceInterface->isKnownCenter($centerRequest->code)){
            $centerResponse->body = ['errorMessage' => 'Non Existing Center'];
            $centerResponse->status = 400;
            $centerResponse->statusText = "Bad Request";
            return;
        };

        if(!empty($this->persistenceInterface->getCenterByName($centerRequest->name))){
            $centerResponse->body = ['errorMessage' => 'Conflict. Name already used.'];
            $centerResponse->status = 409;
            $centerResponse->statusText = "Conflict";
            return;
        };

        $this->persistenceInterface->updateCenter($centerRequest->name, $centerRequest->code, $centerRequest->countryCode);

        $actionDetails = [
            'modifiedCenter' => $centerRequest->code,
            'centerName'=> $centerRequest->name,
            'centerCountryCode' =>  $centerRequest->countryCode,
        ];

        $this->trackerService->writeAction($centerRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_CENTER, $actionDetails);

        $centerResponse->status = 200;
        $centerResponse->statusText = 'OK';
    }


}

?>
