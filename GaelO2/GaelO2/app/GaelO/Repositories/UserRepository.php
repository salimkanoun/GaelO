<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Interfaces\UserRepositoryInterface;

use App\Models\CenterUser;
use App\Models\User;
use App\Models\Role;

use App\GaelO\Constants\Constants;
use App\GaelO\Util;

class UserRepository implements PersistenceInterface, UserRepositoryInterface {

    public function __construct(User $user, Role $roles, CenterUser $centerUser){
        $this->user = $user;
        $this->roles = $roles;
        $this->centerUser = $centerUser;
    }

    public function create(array $data){
        $user = new User();
        $model = Util::fillObject($data, $user);
        $model->save();
        return $model->toArray();
    }

    public function update($id, array $data) : void{
        $model = $this->user->findOrFail($id);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id){
        return $this->user->findOrFail($id)->toArray();
    }

    public function delete($id) : void {
        $this->user->findOrFail($id)->delete();
    }

    public function getAll() : array {
        $users = $this->user->withTrashed()->get();
        return empty($users) ? [] : $users->toArray();
    }

    public function createUser(String $username, String $lastName, String $firstName, String $status,
                                String $email, ?String $phone, bool $administrator, int $centerCode, String $job,
                                ?String $orthancAdress, ?String $orthancLogin, ?String $orthancPassword,
                                String $passwordTemporary, ?String $password, String $creationDate) : array {

        $data= ['username' => $username,
        'lastname' => $lastName,
        'firstname' => $firstName,
        'status' => $status,
        'email' => $email,
        'phone' => $phone,
        'administrator' => $administrator,
        'center_code' => $centerCode,
        'job' => $job,
        'orthanc_address' => $orthancAdress,
        'orthanc_login' => $orthancLogin,
        'orthanc_password' => $orthancPassword,
        'password_temporary'=> $passwordTemporary,
        'password'=> $password,
        'creation_date'=> $creationDate,
        'last_password_update'=> null];

        return $this->create($data);

    }

    public function updateUser(int $id, String $username, ?String $lastName, ?String $firstName, String $status,
                                String $email, ?String $phone, bool $administrator, int $centerCode, String $job,
                                ?String $orthancAdress, ?String $orthancLogin, ?String $orthancPassword,
                                ?String $passwordTemporary) : void {

        $data= ['username' => $username,
                'lastname' => $lastName,
                'firstname' => $firstName,
                'status' => $status,
                'email' => $email,
                'phone' => $phone,
                'administrator' => $administrator,
                'center_code' => $centerCode,
                'job' => $job,
                'orthanc_address' => $orthancAdress,
                'orthanc_login' => $orthancLogin,
                'orthanc_password' => $orthancPassword,
                'password_temporary'=> $passwordTemporary];

        $this->update($id, $data);

    }

    public function getUserByUsername(String $username, bool $withTrashed = false) : array {
        if($withTrashed){
            $user = $this->user->withTrashed()->where('username', $username)->firstOrFail();
        }else{
            $user = $this->user->where('username', $username)->firstOrFail();
        }

        return $user->toArray();
    }

    public function isExistingUsername(String $username) : bool {
        $user = $this->user->withTrashed()->where('username', $username);
        return $user->count() > 0 ? true : false;
    }


    public function isExistingEmail(String $email) : bool {
        $user = $this->user->withTrashed()->where('email', $email);
        return $user->count() > 0 ? true : false;
    }

    public function reactivateUser(int $id) : void {
        $this->user->withTrashed()->find($id)->restore();
    }

    public function getAdministratorsEmails() : array {
        $emails = $this->user->where([['administrator', true], ['status', 'Activated']])->get();
        return empty($emails) ? [] : $emails->pluck('email')->toArray();
    }

    /**
     * Get Emails array of user having an Investigator roles, affiliated (main or affiliated) in centercode
     * and having a particular job
     */
    public function getInvestigatorsStudyFromCenterEmails(string $study, int $centerCode, ?string $job) : array {

        $emails = $this->user
        ->where('status', 'Activated')
        ->join('roles', function ($join) {
            $join->on('users.id', '=', 'roles.user_id');
        })->join('center_user', function ($join) {
            $join->on('users.id', '=', 'center_user.user_id');
        })->where(function ($query) use ($study, $job) {
            if($job !== null){
                $query->where('roles.name', '=', Constants::ROLE_INVESTIGATOR)
                ->where('roles.study_name', '=', $study)
                ->where('users.job', '=', $job);
            }else{
                $query->where('roles.name', '=', Constants::ROLE_INVESTIGATOR)
                ->where('roles.study_name', '=', $study);
            }

        })->where(function  ($query) use ($centerCode) {
            $query->where('center_user.center_code', '=', $centerCode)
            ->orWhere('users.center_code', '=', $centerCode);
        })
        ->get();

        return empty($emails) ? [] : $emails->pluck('email')->toArray();
    }

    public function getUsersEmailsByRolesInStudy(string $study, string $role ) : array {

        $emails = $this->user
        ->where('status', 'Activated')
        ->join('roles', function ($join) {
            $join->on('users.id', '=', 'roles.user_id');
        })->where(function ($query) use ($study, $role) {
            $query->where('roles.name', '=', $role)
            ->where('roles.study_name', '=', $study);
        })->get();

        return empty($emails) ? [] : $emails->pluck('email')->toArray();

    }

    /**
     * Return users data of users affiliated (main or affiliated) to a center
     */
    public function getUsersAffiliatedToCenter(int $centerCode) : array {

        $users = $this->user
        ->leftJoin('center_user', function ($join) {
            $join->on('users.id', '=', 'center_user.user_id');
        })->where(function  ($query) use ($centerCode) {
            $query->where('center_user.center_code', '=', $centerCode)
            ->orWhere('users.center_code', '=', $centerCode);
        })
        ->where('status', 'Activated')
        ->get();

        return empty($users) ? [] : $users->toArray();
    }

    public function getAllStudiesWithRoleForUser(string $username) : array {
        $user = $this->user->withTrashed()->where('username', $username)->firstOrFail();
        $studies = $user->roles()->distinct('study_name')->get();
        return $studies->count() === 0 ?  [] : $studies->pluck('study_name')->toArray();
    }

    public function getUsersRoles(int $userId) : array {
        $roles = $this->user->findOrFail($userId)->roles()->get(['name', 'study_name']);
        $roles = $roles->groupBy(['study_name'])
                ->map(function ($group) {
                    return $group->map(function ($value) {
                        return $value->name;
                    });
                });

        return empty($roles) ? [] : $roles->toArray();
    }

    public function getUsersRolesInStudy(int $userId, String $study) : array {
        $user = $this->user->findOrFail($userId);
        $roles = $user->roles()->where('study_name', $study)->get();
        return $roles->count() === 0 ? [] : $roles->pluck('name')->toArray();
    }

    public function addUserRoleInStudy(int $userId, String $study, string $role) : void {

        $user = $this->user->findOrFail($userId);
        $insertData =[
            'user_id'=>$user['id'],
            'study_name'=> $study,
            'name'=>$role
        ];
        $user->roles()->insert($insertData);

    }

    public function deleteRoleForUser(int $userId, String $study, String $role) : void {
        $this->roles->where([
            ['user_id', '=', $userId],
            ['study_name', '=', $study],
            ['name','=', $role]
            ])->delete();
    }

    public function addAffiliatedCenter(int $userId, int $centerCode) : void {

        $user = $this->user->findOrFail($userId);

        $insertArray = [
            'user_id'=>$user['id'],
            'center_code'=> $centerCode
        ];

        $this->centerUser->insert($insertArray);

    }

    public function deleteAffiliatedCenter(int $userId, int $centerCode) : void {
        $affiliatedCenter=$this->centerUser->where( ['user_id'=> $userId,'center_code'=>$centerCode] )->firstOrFail();
        $affiliatedCenter->delete();
    }

    public function getAffiliatedCenter(int $userId) : array {
        $user = $this->user->where('id', $userId)->first();
        $centers = $user->affiliatedCenters()->get();
        return empty($centers) ? [] : $centers->toArray();
    }

    public function getAllUsersCenters(int $userId) : array {

        $user = $this->user->findOrFail($userId);
        $centers = $user->affiliatedCenters()->get()->pluck('code');
        if(empty($centers)){
            return [ $user['center_code'] ];
        }else {
            return [...$centers->toArray(), $user['center_code']];
        }


    }

    public function getUsersFromStudy(string $studyName) : array {

        $users = $this->user->join('roles', function ($join) {
            $join->on('roles.user_id', '=', 'users.id');
        })->where('study_name', $studyName)->groupBy('user_id')->with('roles')->get();
        return empty($users) ? [] : $users->toArray();
    }
}

?>
