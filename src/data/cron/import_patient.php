<?php
/**
 Copyright (C) 2018 KANOUN Salim
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
 * Automatically import patients defined in JSON for a set of study
 * Define the local or FTP path as source
 * This is called by cron.php script
 */

$_SERVER['DOCUMENT_ROOT'] ='/gaelo';
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

$linkpdo=Session::getLinkpdo();

echo ('ScriptStarted');

$studyName = "ITSELF";

$ftpReader = new FTP_Reader($linkpdo);    
$ftpReader->setFTPCredential();
$ftpReader->setFolder("/GAELO/".$studyName."/ExportCS");
$ftpReader->setSearchedFile($studyName . '_PATIENTS.txt');
$ftpReader->setLastUpdateTimingLimit(10*24 * 60);

try {
    $files = $ftpReader->getFilesFromFTP();
} catch (Exception $e) {
    $ftpReader->sendFailedReadFTP($e->getMessage());
    print($e->getMessage());
}

$fileAsString = file_get_contents($files[0]);
$arrayLysarc = $ftpReader::parseLysarcTxt($fileAsString);

print_r($arrayLysarc);
$jsonImport = json_encode($arrayLysarc);
print($jsonImport);

$importPatient = new Import_Patient($jsonImport, $studyName, $linkpdo);
$importPatient -> readJson();

print_r($importPatient->sucessList);
print_r($importPatient->failList);

//log activity
$actionDetails['Success']=$importPatient->sucessList;
$actionDetails['Fail']=$importPatient->failList;
$actionDetails['email']=$importPatient->getTextImportAnswer();
Tracker::logActivity("administrator", User::SUPERVISOR, $studyName , null , "Import Patients", $actionDetails);


//Send the email to administrators of the plateforme
$email = new Send_Email($linkpdo);
$email->addGroupEmails($studyName, User::SUPERVISOR);
$email->setSubject('Auto Import Report');
$email->setMessage($importPatient->getHTMLImportAnswer());
$email->sendEmail();


