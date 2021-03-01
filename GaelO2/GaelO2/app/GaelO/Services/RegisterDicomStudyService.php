<?php

/**
 Copyright (C) 2018-2020 KANOUN Salim
 This program is free software; you can redistribute it and/or modify
 it under the terms of the Affero GNU General Public v.3 License as published by
 the Free Software Foundation;
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 Affero GNU General Public Public for more details.
 You should have received a copy of the Affero GNU General Public Public along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

namespace App\GaelO\Services;

use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Interfaces\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\DicomStudyRepositoryInterface;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\StoreObjects\OrthancSeries;
use App\GaelO\Services\StoreObjects\OrthancStudy;
use App\GaelO\Util;
use DateTime;

/**
 * Fill Orthanc_Studies and Orthanc_Series table to link DICOM Orthanc storage with the web plateform
 * Used in the validation process of the upload
 */

class RegisterDicomStudyService
{

    private OrthancService $orthancService;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;

    private int $visitId;
    private int $userId;
    private string $originalStudyOrthancId;
    private string $studyName;


    public function __construct(OrthancService $orthancService, DicomStudyRepositoryInterface $dicomStudyRepositoryInterface, DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface)
    {
        $this->orthancService = $orthancService;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
    }

    public function setData(int $visitId, string $studyName, string $userId, string $studyOrthancId, string $originalStudyOrthancId)
    {
        $this->orthancService->setOrthancServer(true);
        $this->visitId = $visitId;
        $this->userId = $userId;
        $this->studyOrthancId = $studyOrthancId;
        $this->originalStudyOrthancId = $originalStudyOrthancId;
        $this->studyName = $studyName;
    }

    /**
     * Fill data in the database, write data for study and for each series
     * Once done trigger the change upload status of visit to update upload status and eventually skip
     * local form and/or QC
     */
    public function execute()
    {
        $studyOrthancObject = new OrthancStudy($this->orthancService);
        $studyOrthancObject->setStudyOrthancID($this->studyOrthancId);
        $studyOrthancObject->retrieveStudyData();

        //Check that original OrthancID is unknown for this study
        if ( ! $this->dicomStudyRepositoryInterface->isExistingOriginalOrthancStudyID($this->originalStudyOrthancId, $this->studyName)) {

            //Fill la database
            $this->addToDbStudy($studyOrthancObject);

            foreach ($studyOrthancObject->orthancSeries as $serie) {
                //Fill each series in database
                $this->addtoDbSerie($serie, $studyOrthancObject->studyInstanceUID);
            }

        } else {
            throw new GaelOBadRequestException("Error during import Study Already Known");
        }
    }

    /**
     * Private function to write into Orthanc_Studies DB
     * @param string $anonFromOrthancStudyId
     */
    private function addToDbStudy(OrthancStudy $studyOrthancObject)
    {
        $studyAcquisitionDate = $this->parseDateTime($studyOrthancObject->studyDate, 1);
        $studyAcquisitionTime = $this->parseDateTime($studyOrthancObject->studyTime, 2);

        if ($this->dicomStudyRepositoryInterface->isExistingStudyInstanceUID($studyOrthancObject->studyInstanceUID)) {

            $this->dicomStudyRepositoryInterface->updateStudy(
                $studyOrthancObject->studyOrthancId,
                $this->visitId,
                $this->userId,
                Util::now(),
                $studyAcquisitionDate,
                $studyAcquisitionTime,
                $this->originalStudyOrthancId,
                $studyOrthancObject->studyInstanceUID,
                $studyOrthancObject->studyDescription,
                $studyOrthancObject->parentPartientOrthancID,
                $studyOrthancObject->parentPatientName,
                $studyOrthancObject->parentPatientID,
                $studyOrthancObject->numberOfSeriesInStudy,
                $studyOrthancObject->countInstances,
                $studyOrthancObject->diskSizeMb,
                $studyOrthancObject->uncompressedSizeMb
            );
        } else {

            $this->dicomStudyRepositoryInterface->addStudy(
                $studyOrthancObject->studyOrthancID,
                $this->visitId,
                $this->userId,
                Util::now(),
                $studyAcquisitionDate,
                $studyAcquisitionTime,
                $this->originalStudyOrthancId,
                $studyOrthancObject->studyInstanceUID,
                $studyOrthancObject->studyDescription,
                $studyOrthancObject->parentPartientOrthancID,
                $studyOrthancObject->parentPatientName,
                $studyOrthancObject->parentPatientID,
                $studyOrthancObject->numberOfSeriesInStudy,
                $studyOrthancObject->countInstances,
                $studyOrthancObject->diskSizeMb,
                $studyOrthancObject->uncompressedSizeMb
            );
        }
    }

    /**
     * Private function to write into the Orthanc_Series DB
     * @param Orthanc_Serie $serie
     */
    private function addtoDbSerie(OrthancSeries $series, string $studyInstanceUID)
    {

        $serieAcquisitionDate = $this->parseDateTime($series->seriesDate, 1);
        $serieAcquisitionTime = $this->parseDateTime($series->seriesTime, 2);
        $injectedDateTime =$this->parseDateTime($series->injectedDateTime, 0);

        if ($this->dicomSeriesRepositoryInterface->isExistingSeriesInstanceUID($series->seriesInstanceUID)) {

            $this->dicomSeriesRepositoryInterface->updateSeries(
                $series->serieOrthancID,
                $serieAcquisitionDate,
                $serieAcquisitionTime,
                $series->modality,
                $series->seriesDescription,
                $series->injectedDose,
                $series->radiopharmaceutical,
                $series->halfLife,
                $series->injectedTime,
                $injectedDateTime,
                $series->injectedActivity,
                $series->patientWeight,
                $series->numberOfInstanceInOrthanc,
                $series->seriesInstanceUID,
                $series->seriesNumber,
                $series->diskSizeMb,
                $series->uncompressedSizeMb,
                $series->manufacturer,
                $series->modelName
            );
        } else {
            $this->dicomSeriesRepositoryInterface->addSeries(
                $series->serieOrthancID,
                $studyInstanceUID,
                $serieAcquisitionDate,
                $serieAcquisitionTime,
                $series->modality,
                $series->seriesDescription,
                $series->injectedDose,
                $series->radiopharmaceutical,
                $series->halfLife,
                $series->injectedTime,
                $injectedDateTime,
                $series->injectedActivity,
                $series->patientWeight,
                $series->numberOfInstanceInOrthanc,
                $series->seriesInstanceUID,
                $series->seriesNumber,
                $series->diskSizeMb,
                $series->uncompressedSizeMb,
                $series->manufacturer,
                $series->modelName
            );
        }

    }

    /**
     * Parse a DICOM date or Time string and return a string ready to send to database
     * Return null if non parsable
     * @param string $string
     * @param type 0=dateTime, 1=Date, 2=Time
     * return formated date for db saving, null if parse failed
     */
    private function parseDateTime(?string $string, int $type)
    {
        $parsedDateTime = null;

        //If contain time split the ms (after.) which are not constant
        if ($type == 0 || $type == 2) {
            if (strpos($string, ".")) {
                $timeWithoutms = explode(".", $string);
                $string = $timeWithoutms[0];
            }
        }

        if ($type == 2) {
            $dateObject = DateTime::createFromFormat('His', $string);
            if ($dateObject !== false) {
                $parsedDateTime = $dateObject->format('H:i:s');
            }
        } else if ($type == 1) {
            $dateObject = DateTime::createFromFormat('Ymd', $string);
            if ($dateObject !== false) {
                $parsedDateTime = $dateObject->format('Y-m-d');
            }
        } else if ($type == 0) {
            $dateObject = DateTime::createFromFormat('YmdHis', $string);
            if ($dateObject !== false) {
                $parsedDateTime = $dateObject->format('Y-m-d H:i:s');
            }
        }

        return $parsedDateTime;
    }
}