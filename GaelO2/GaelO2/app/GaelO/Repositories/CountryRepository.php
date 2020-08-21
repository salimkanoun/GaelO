<?php

namespace App\GaelO\Repositories;

use App\Country;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class CountryRepository implements PersistenceInterface {

    public function __construct(){
        $this->country = new Country();
    }

    public function create(array $data){
        $model = Util::fillObject($data, $this->country);
        $model->save();
    }

    public function update($code, array $data){
        $model = $this->country->find($code);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($code){
        return $this->country->get()->where('code', $code);
    }

    public function delete($code) {
        return $this->country->find($code)->delete();
    }

    public function getAll() {
        return $this->country->get()->toArray();
    }

}

?>