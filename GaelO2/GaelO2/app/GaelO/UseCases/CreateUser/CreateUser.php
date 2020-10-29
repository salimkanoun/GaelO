<?php

namespace App\GaelO\UseCases\CreateUser;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\CreateUser\CreateUserRequest;
use App\GaelO\UseCases\CreateUser\CreateUserResponse;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\TrackerService;
use App\GaelO\Services\UserService;

class CreateUser {

    /**
     * Dependency injection that will be provided by the Dependency Injection Container
     * Persistence Interfate => Will be a instance of User Repository (defined by UserRepositoryProvider)
     * Tracker Service to be able to write in the Tracker
     * Mail Service to be able to send email
     */
    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService,  TrackerService $trackerService, MailServices $mailService, UserService $userService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
        $this->mailService = $mailService;
        $this->authorizationService = $authorizationService;
        $this->userService = $userService;
     }

     public function execute(CreateUserRequest $createUserRequest, CreateUserResponse $createUserResponse) : void
    {
        $this->authorizationService->isAdmin($createUserRequest->currentUserId);
        //Generate password
        $password=substr(uniqid(), 1, 10);
        $passwordTemporary = LaravelFunctionAdapter::Hash($password);
        $createdUserEntity = $this->userService->createUser($createUserRequest, $passwordTemporary);

        //save user creation in tracker
        $detailsTracker = [
            'id'=> $createdUserEntity['id']
        ];
        //Save action in Tracker
        $this->trackerService->writeAction($createUserRequest->currentUserId,
            Constants::TRACKER_ROLE_USER,
            null,
            null,
            Constants::TRACKER_CREATE_USER,
            $detailsTracker);

        //Send Welcom Email to give the plain password to new user.
        $this->mailService->sendCreatedAccountMessage($createdUserEntity['email'],
                            $createdUserEntity['firstname'].' '.$createdUserEntity['lastname'],
                            $createdUserEntity['username'],
                            $passwordTemporary);

        $createUserResponse->status = 201;
        $createUserResponse->statusText = 'Created';
    }
}
