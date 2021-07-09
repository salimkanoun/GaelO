<?php

namespace App\GaelO\Interfaces\Repositories;

interface VisitTypeRepositoryInterface
{

    public function find($id) : array ;

    public function delete($id) : void ;

    public function createVisitType(
        int $visitGroupId,
        string $name,
        int $visitOrder,
        bool $localFormNeeded,
        bool $qcNeeded,
        bool $reviewNeeded,
        bool $optional,
        int $limitLowDays,
        int $limitUpDays,
        string $anonProfile,
        array $dicomConstraints
    ) :void ;

    public function hasVisits(int $visitTypeId): bool ;

    public function isExistingVisitType(int $visitGroupId, string $name): bool ;
}