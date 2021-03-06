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

/**
 * Study related API
 * Get : get studies (activated or not)
 * Post : Create Study
 * PUT : Delete or Reactivate Study
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();
$username=$_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

	if ($_SESSION['admin']) {

		$studies=Global_Data::getAllStudies($linkpdo);
		echo(json_encode($studies));

	}

}else if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	if ($_SESSION['admin']) {

		$studyName=$_POST['studyName'];
		$visitsGroupArray=$_POST['visitsData'];
		$patientCodePrefix=$_POST['patientCodePrefix'];
		if(empty($patientCodePrefix)) $patientCodePrefix = null;
		//Add the new study as an active study
		Study::createStudy($studyName, $patientCodePrefix, $linkpdo);
         
		//Add the visit in study with order
		foreach ($visitsGroupArray as $modality=>$visitsArray) {

			$visitGroup=Visit_Group::createVisitGroup($studyName, $modality, $linkpdo);

			foreach ($visitsArray as $visit) {
				//Create visit Type entry with specific table
				//Parse text boolean to boolean var
				$localForm=$visit['localForm'] === 'true';
				$qc=$visit['qc'] === 'true';
				$review=$visit['review'] === 'true';
				$optional=$visit['optional'] === 'true';
				//Create Visit Type
				Visit_Type::createVisitType($studyName, $visitGroup, $visit['name'], $visit['order'], $visit['dayMin'], $visit['dayMax'], $localForm,
				$qc, $review, $optional, $visit['anonProfile'], $linkpdo);
                
				$rootSpecificModelsFolder=$_SERVER["DOCUMENT_ROOT"].'/data/form/'.$studyName.'/Poo';
				$rootSpecificScriptFolder=$_SERVER["DOCUMENT_ROOT"].'/data/form/'.$studyName.'/scripts';
                
				//Create root folder if not existing
				if (!is_dir($rootSpecificModelsFolder)) {
					mkdir($rootSpecificModelsFolder, 0755, true);
				}
                
				if (!is_dir($rootSpecificScriptFolder)) {
					mkdir($rootSpecificScriptFolder, 0755, true);
				}
                
				//Create specific file that will need to be edited by user to fill specific data of the forms
				$modelPooFile=$_SERVER["DOCUMENT_ROOT"].'/form_models/study_visit_poo.php';
				$modelScriptFile=$_SERVER["DOCUMENT_ROOT"].'/form_models/study_visit_script.php';
				$destinationPoo=$rootSpecificModelsFolder.DIRECTORY_SEPARATOR.$modality."_".$studyName."_".$visit['name'].'.php';
				$destinationScript=$rootSpecificScriptFolder.DIRECTORY_SEPARATOR.$modality."_".$studyName."_".$visit['name'].'.php';
				//Check that File doesnt exist to avoid override of existing file
				if (!is_file($destinationPoo)) {
					copy($modelPooFile, $destinationPoo);
				}
				if (!is_file($destinationScript)) {
					copy($modelScriptFile, $destinationScript);
				}
                
                

			}

            
		}
        
		//Log the Study Creation
		$actionDetails['details']=$_POST;
		Tracker::logActivity($_SESSION['username'], User::ADMINISTRATOR, $studyName, null, "Create Study", $actionDetails);
        
		echo(json_encode(true));
        
	}else {
		echo(json_encode(false));
	}
    


}else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

}