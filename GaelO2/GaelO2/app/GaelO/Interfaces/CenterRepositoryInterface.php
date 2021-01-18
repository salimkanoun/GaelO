<?php

namespace App\GaelO\Interfaces;

interface CenterRepositoryInterface {


    public function createCenter(int $code, string $name, string $countryCode) : void ;

    public function getCenterByName(string $name) : array ;

    public function getCenterByCode(int $code) : array ;

    public function isKnownCenter(int $code) : bool ;

    public function updateCenter(int $code, String $name, String $countryCode) : void ;

}