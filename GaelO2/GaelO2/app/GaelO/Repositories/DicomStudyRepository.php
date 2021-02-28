<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\DicomStudyRepositoryInterface;
use App\GaelO\Util;
use App\Models\DicomStudy;

class DicomStudyRepository implements DicomStudyRepositoryInterface {


    public function __construct(DicomStudy $dicomStudy){
        $this->dicomStudy = $dicomStudy;
    }

    private function create(array $data){
        $dicomStudy = new DicomStudy();
        $model = Util::fillObject($data, $dicomStudy);
        $model->save();
    }

    private function update($studyInstanceUID, array $data) : void {
        $model = $this->dicomStudy->find($studyInstanceUID);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function delete($studyInstanceUID) :void {
        $this->dicomStudy->findOrFail($studyInstanceUID)->delete();
    }

    public function reactivateByStudyInstanceUID(string $studyInstanceUID) :void {
        $this->dicomStudy->withTrashed()->where('study_uid',$studyInstanceUID)->sole()->restore();
    }

    public function addStudy(string $orthancStudyID, int $visitID, int $uploaderID, string $uploadDate,
                    ?string $acquisitionDate, ?string $acquisitionTime, string $anonFromOrthancID,
                    string $studyUID, ?string $studyDescription, string $patientOrthancID,
                    ?string $patientName, ?string $patientID, int $numberOfSeries, int $numberOfInstance,
                    int $diskSize, int $uncompressedDisksize  ) : void {
        $data = [
            'orthanc_id' => $orthancStudyID,
            'visit_id' => $visitID,
            'uploader_id' => $uploaderID,
            'upload_date' => $uploadDate,
            'acquisition_date' => $acquisitionDate,
            'acquisition_time' => $acquisitionTime,
            'anon_from_orthanc_id' => $anonFromOrthancID,
            'study_uid' => $studyUID,
            'study_description' => $studyDescription,
            'patient_orthanc_id' => $patientOrthancID,
            'patient_name' => $patientName,
            'patient_id' => $patientID,
            'number_of_series' => $numberOfSeries,
            'number_of_instances' => $numberOfInstance,
            'disk_size' => $diskSize,
            'uncompressed_disk_size' => $uncompressedDisksize

        ];

        $this->create($data);

    }

    public function updateStudy(string $orthancStudyID, int $visitID, int $uploaderID, string $uploadDate,
                                ?string $acquisitionDate, ?string $acquisitionTime, string $anonFromOrthancID,
                                string $studyInstanceUID, ?string $studyDescription, string $patientOrthancID,
                                ?string $patientName, ?string $patientID, int $numberOfSeries, int $numberOfInstance,
                                int $diskSize, int $uncompressedDisksize ) : void {

        $data = [
            'orthanc_id'=>$orthancStudyID,
            'visit_id' => $visitID,
            'uploader_id' => $uploaderID,
            'upload_date' => $uploadDate,
            'acquisition_date' => $acquisitionDate,
            'acquisition_time' => $acquisitionTime,
            'anon_from_orthanc_id' => $anonFromOrthancID,
            'study_description' => $studyDescription,
            'patient_orthanc_id' => $patientOrthancID,
            'patient_name' => $patientName,
            'patient_id' => $patientID,
            'number_of_series' => $numberOfSeries,
            'number_of_instances' => $numberOfInstance,
            'disk_size' => $diskSize,
            'uncompressed_disk_size' => $uncompressedDisksize
        ];

        $this->update($studyInstanceUID, $data);


    }

    /**
     * Check that for a study the original Orthanc Id (StudyUID Hash) is not existing
     * This is done per study as a imaging procedure can be included in different trial
     */
    public function isExistingOriginalOrthancStudyID(string $originalOrthancStudyID, string $studyName) : bool {
        $orthancStudies = $this->dicomStudy->where('anon_from_orthanc_id', $originalOrthancStudyID)
                                                        ->join('visits', function ($join) {
                                                            $join->on('dicom_studies.visit_id', '=', 'visits.id');
                                                        })->join('visit_types', function ($join) {
                                                            $join->on('visit_types.id', '=', 'visits.visit_type_id');
                                                        })->join('visit_groups', function ($join) {
                                                            $join->on('visit_groups.id', '=', 'visit_types.visit_group_id');
                                                        })
                                                        ->where('study_name', '=', $studyName)
                                                        ->get();

        return $orthancStudies->count()>0 ? true : false;
    }

    /**
     * Check that OrthancStudyID is not existing.
     * OrthancStudyID is a primary key, even if a same original study is included in two trials the ID will be
     * Generated by anonymization
     */
    public function isExistingStudyInstantUID(string $studyInstanceUID) : bool {
        $orthancStudies = $this->dicomStudy->where('study_uid',$studyInstanceUID);
        return $orthancStudies->count()>0 ? true : false;
    }

    public function getStudyInstanceUidFromVisit(int $visitID) : string {
        return $this->dicomStudy->where('visit_id', $visitID)->sole()->value('study_uid');
    }

    public function isExistingDicomStudyForVisit(int $visitID) : bool {
        $dicomStudies =  $this->dicomStudy->where('visit_id', $visitID)->get();
        return $dicomStudies->count()>0 ? true : false;
    }

    public function getDicomsDataFromVisit(int $visitID, bool $withDeleted) : array {

        if($withDeleted){
            $studies = $this->dicomStudy->withTrashed()->with(['dicomSeries' => function ($query){ $query->withTrashed(); } ])->where('visit_id', $visitID)->get();
        }else{
            $studies = $this->dicomStudy->where('visit_id', $visitID)->with('dicomSeries')->get();
        }

        return $studies->count() == 0 ? [] : $studies->toArray();
    }

    public function getOrthancStudyByStudyInstanceUID(string $studyInstanceUID, bool $includeDeleted) : array {
        if($includeDeleted){
            $study = $this->dicomStudy->where('study_uid',$studyInstanceUID)->withTrashed()->sole()->toArray();
        }else{
            $study = $this->dicomStudy->where('study_uid',$studyInstanceUID)->sole()->toArray();
        }

        return $study;

    }

    public function getChildSeries(string $studyInstanceUID, bool $deleted) : array {
        if( ! $deleted ){
            $series = $this->dicomStudy->findOrFail($studyInstanceUID)->dicomSeries()->get()->toArray();
        }else{
            $series = $this->dicomStudy->findOrFail($studyInstanceUID)->dicomSeries()->onlyTrashed()->get()->toArray();
        }

        return $series;

    }

}