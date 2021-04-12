<?php
namespace App\GaelO\Services\SpecificStudiesRules;

use App\GaelO\Adapters\ValidatorAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Services\SpecificStudiesRules\AbstractStudyRules;

class TEST_CT_CT0 extends AbstractStudyRules {

    public function checkInvestigatorFormValidity(array $data, bool $validated)  : bool {

        $validatorAdapter = new ValidatorAdapter($validated);
        $validatorAdapter->addValidatorString('comment', false);
        return $validatorAdapter->validate($data);
    }

    public function checkReviewFormValidity(array $data, bool $validated, bool $adjudication) : bool {
        //Here no adjudication if needed can be splitted in two privates methods
        $validatorAdapter = new ValidatorAdapter($validated);
        $validatorAdapter->addValidatorString('comment', false);
        return $validatorAdapter->validate($data);
    }

    public function getReviewStatus() : string {
        return Constants::REVIEW_STATUS_DONE;
    }

    public function getReviewConclusion() : string {
        return 'CR';
    }

}
