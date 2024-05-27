<?php

use App\Legacy\LegacyContainer;
use App\Utils\NicknameGenerator;

require __DIR__ . '/../../app/includes.php';

set_time_limit(0);

global $file;

$projectDir = LegacyContainer::getParameter('legacy_is_on_clever_cloud') 
    ? LegacyContainer::getParameter('legacy_project_dir') . '/ffcam'
    : __DIR__ . '/../../config/ffcam-ftp-folder/';

$file = "$projectDir/6900.txt";

class Member {
    public $cafNumber;
    public $clubNumber;
    public $firstname;
    public $lastname;
    public $birthday;
    public $phoneNumber1;
    public $phoneNumber2;
    public $address;
    public $postCode;
    public $city;
    public $civility;
    public $nickname;
    public $cafNumParent;
    public $adhesionDate;
    public $hasToRenewLicence;
    public $isLicenceExpired;
}

global $mysqliHandler;
$mysqliHandler = LegacyContainer::get('legacy_mysqli_handler');

global $logger;
$logger = LegacyContainer::get('legacy_logger');

$today = new DateTime();
$startDate = new DateTime($today->format('Y') . '-08-25');
$endDate = new DateTime($today->format('Y') . '-12-31');

global $hasTolerancyPeriodPassed;
$hasTolerancyPeriodPassed = !($today >= $startDate && $today <= $endDate);

function normalizePhoneNumber($phoneNumber) {
    $normalizedPhoneNumber = $phoneNumber;

    $normalizedPhoneNumber = str_ireplace('o', '0', $normalizedPhoneNumber); // suppression des erreurs de frappe
    $normalizedPhoneNumber = preg_replace('/[^\d]+/', '', $normalizedPhoneNumber);// suppression des espaces dans les tel
    $normalizedPhoneNumber = preg_replace('/(?:\+?33)/', '0', $normalizedPhoneNumber);

    // We try to capture every sequence of 10 chars beginning by 0x
    $regex = '/(?:0[1-9](?:[ .-]?\d{2}){4})/';
    if (!preg_match_all($regex, $normalizedPhoneNumber, $matches)) {
        return $phoneNumber;
    }

    // We try to prefer a 07 or 06 personal number
    foreach ($matches[0] as $number) {
        if (substr($number, 0, 2) === "06" || substr($number, 0, 2) === "07") {
            return $number;
        }
    }

    // If no personal number has been found, we prefer a valid number
    if (count($matches[0])) {
        return $matches[0][0];
    }

    return $phoneNumber;
}

function normalizeNames($name) {
    $name = strtolower($name);
    return ucwords($name, " -");
}

function transformLineIntoMember($line, $lineNumber) : Member {
    global $hasTolerancyPeriodPassed;
    global $mysqliHandler;

    $line = mb_convert_encoding($line, 'UTF-8');
    $line = stripslashes($line);
    $line = explode(';', $line);

    if (count($line) < 33) {
        throw new Exception("Can't process line $lineNumber : Invalid format. Expected : 33 columns. Got: " . count($line));
    }

    $fullCafNum = $line[0];
    $clubNumber = $line[1];
    $cafNum= $line[2];
    $birthday = $line[6];

    if (
        !is_numeric($fullCafNum) 
        || !is_numeric($clubNumber) 
        || !is_numeric($cafNum) 
        || !preg_match('#[0-9]{4}-[0-9]{2}-[0-9]{2}#', $birthday) 
    ) {
        throw new Exception("Can't process line $lineNumber : Multiple values are wrong");
    }

    $member = new Member();
    $member->cafNumber = $mysqliHandler->escapeString($line[0]);
    $member->clubNumber = $line[1];
    $member->firstname = $mysqliHandler->escapeString(normalizeNames($line[10]));
    $member->lastname = $mysqliHandler->escapeString(normalizeNames($line[9]));

    $datePart = explode('-', $line[6]);
    $member->birthday = mktime(1, 0, 0, $datePart[1], $datePart[2], $datePart[0]);
    $member->address = $mysqliHandler->escapeString(trim($line[11]." \n".$line[12]." \n".$line[13]." \n".$line[14]));

    $member->phoneNumber1 = $mysqliHandler->escapeString(normalizePhoneNumber($line[26]));
    $member->phoneNumber2 = $mysqliHandler->escapeString(normalizePhoneNumber($line[27]));

    $member->postCode = $mysqliHandler->escapeString(normalizeNames($line[15]));
    $member->city = $mysqliHandler->escapeString(normalizeNames($line[16]));
    $member->civility = $mysqliHandler->escapeString(normalizeNames(str_replace('MLLE', 'MME', $line[8])));

    $member->nickname = NicknameGenerator::generateNickname($member->firstname, $member->lastname);

    // FILIATION : MISE À JOUR DE LA VALEUR
    $member->cafNumParent = (int) $line[5] > 0 ? $mysqliHandler->escapeString($line[1].$line[5]) : null;

    $datePart = explode('-', $line[7]);
    $member->isLicenceExpired = $line[7] === '0000-00-00';
    $member->adhesionDate = $member->isLicenceExpired ? null : mktime(1, 0, 0, $datePart[1], $datePart[2], $datePart[0]);
    $member->hasToRenewLicence = $member->isLicenceExpired && $hasTolerancyPeriodPassed;

    return $member;
}

/**
 * @return Member[]
 */
function transformFileToMembersArray($handle) : array{
    $members = [];
    $lineNumber = 0;
    while (($line = fgets($handle)) !== false) {
        $lineNumber++;

        try {
            $members[] = transformLineIntoMember($line, $lineNumber);
        } catch (Exception $err) {
            Sentry\captureException($err);
            continue;
        }
    }
    return $members;
}

function isMemberInDb($cafNum) {
    global $mysqliHandler;

    $handleSql = $mysqliHandler->query("SELECT id_user FROM caf_user WHERE cafnum_user LIKE '$cafNum'");
    return 1 === mysqli_num_rows($handleSql);
}

function run() {
    global $file;
    global $logger;
    global $mysqliHandler;
    
    $nb_insert = 0;
    $nb_update = 0;

    if (!file_exists($file) && !is_file($file)) {
        logAdmin("fichier inexistant : $file");
        $logger->warning("File $file not found. Can't import new members");
        
        blockExpiredAccounts();
        removeExpiredFiliations();
        return;
    }
    
    chmod($file, 0777);
    if (!$handle = @fopen($file, 'r')) {
        throw new Exception("Can't open '$file'");
    }
    
    $members = [];
    
    if ($handle) {
        $members = transformFileToMembersArray($handle);
        fclose($handle);
    } else {
        throw new Exception('Unable to obtain file handle');
    }

    $logger->info('Starting members synchronization');

    foreach($members as $member) {
        $logger->info("Processing CAF member $member->cafNumber");

        if (!isMemberInDb($member->cafNumber)) {
            $logger->info("CAF member $member->cafNumber is not in database. Inserting..");

            try {
                $mysqliHandler->query(
                    "INSERT INTO caf_user (
                        cafnum_user, firstname_user, lastname_user, created_user, birthday_user, tel_user, tel2_user, adresse_user, cp_user, ville_user, civ_user, cafnum_parent_user, valid_user, doit_renouveler_user, alerte_renouveler_user, ts_insert_user, nickname_user, manuel_user, nomade_user
                    ) VALUES (
                        '" . $member->cafNumber . "',
                        '" . $member->firstname . "',
                        '" . $member->lastname . "',
                        '" . time() . "',
                        '" . $member->birthday . "',
                        '" . $member->phoneNumber1 . "',
                        '" . $member->phoneNumber2 . "',
                        '" . $member->address . "',
                        '" . $member->postCode . "',
                        '" . $member->city . "',
                        '" . $member->civility . "',
                        " . ($member->cafNumParent === null ? 'NULL' : "'" . $member->cafNumParent . "'") . ",
                        0,
                        " . ($member->hasToRenewLicence ? 1 : 0) . ",
                        " . ($member->isLicenceExpired ? 1 : 0) . ",
                        '" . time() . "',
                        '" . $member->nickname . "',
                        0,
                        0
                    );"
                );
                
                $nb_insert++;
                continue;
            } catch (\mysqli_sql_exception $err) {
                $msg = "Error while inserting user " .  $member->cafNumber . " : " . $err->getMessage();
                \Sentry\captureException(new Exception($msg));
                $logger->warn($msg);
            }
        }

        $logger->info("CAF member $member->cafNumber is a known member. Updating..");

        $fields = [
            'ts_update_user' => time(),
            'firstname_user' => $member->firstname,
            'lastname_user' => $member->lastname,
            'doit_renouveler_user' => $member->hasToRenewLicence ? 1 : 0,
            'birthday_user' => $member->birthday,
            'civ_user' => $member->civility,
            'cafnum_parent_user' => $member->cafNumParent,
            'tel_user' => $member->phoneNumber1,
            'tel2_user' => $member->phoneNumber2,
            'adresse_user' => $member->address,
            'cp_user' => $member->postCode,
            'ville_user' => $member->city,
            'alerte_renouveler_user' => $member->isLicenceExpired ? 1 : 0,
            'nickname_user' => $member->nickname,
            'manuel_user' => 0,
            'nomade_user' => 0,
            'date_adhesion_user' => $member->adhesionDate
        ];
    
        if (!$member->isLicenceExpired) {
            $fields['date_adhesion_user'] = $member->adhesionDate;
        }
        
        $setClause = [];
        foreach ($fields as $field => $value) {
            if ($value === null) {
                $setClause[] = "$field = NULL";
            } else {
                $setClause[] = "$field = '$value'";
            }
        }
    
        try {
            $mysqliHandler->query(
                "UPDATE caf_user SET " . implode(', ', $setClause) . " WHERE cafnum_user = '$member->cafNumber'"
            );
            $nb_update++;
        } catch (\mysqli_sql_exception $err) {
            $msg = "Error while updating user " .  $member->cafNumber . " : " . $err->getMessage();
            \Sentry\captureException(new Exception($msg));
            $logger->warning($msg);
        }
    }
    
    if ($nb_insert > 0 || $nb_update > 0) {
        $zip = new ZipArchive();
        $filename = $file.'_'.date('Y-m-d').'.zip';
        $zip->open($filename, ZipArchive::CREATE);
        $zip->addFile($file, basename($file));
        $zip->close();
    }
    
    $logger->info("Members synchronization finished. New members : $nb_insert, Updated members :$nb_update");
    logAdmin("INSERT: $nb_insert, UPDATE:$nb_update, fichier ".basename($file));
    
    blockExpiredAccounts();
    removeExpiredFiliations();
}

run();

function logAdmin(string $description) : void {
    global $mysqliHandler;

    try {
        $mysqliHandler->query(
            "INSERT INTO  `caf_log_admin` (`code_log_admin` ,`desc_log_admin` ,`ip_log_admin`,`date_log_admin`)
            VALUES ('import-ffcam',  '$description', '127.0.0.1', '".time()."');"
        );
    } catch (\mysqli_sql_exception $exc) {
        \Sentry\captureException($exc);
    }
}

// suppression des filiations sur comptes non mis ŕ jour depuis + de 200j
function removeExpiredFiliations() : void {
    global $mysqliHandler, $logger;

    $logger->info("Removing expired filiations");

    try {
        $mysqliHandler->query(
            'UPDATE caf_user SET cafnum_parent_user = null WHERE ts_update_user < (UNIX_TIMESTAMP( ) - ( 86400 * 200 ))'
        );
    } catch (\mysqli_sql_exception $exc) {
        \Sentry\captureException($exc);
    }
}

// blocage des comptes expires ( inscription < 31/08/Y-1 ) ou non mis ŕ jour depuis + de 10j
function blockExpiredAccounts() {
    global $mysqliHandler, $logger;

    $logger->info("Blocking expired accounts");

    try {
        $mysqliHandler->query(
            'UPDATE caf_user SET doit_renouveler_user=1 WHERE id_user!=1 AND nomade_user=0 AND manuel_user=0 AND (
                FROM_UNIXTIME( date_adhesion_user ) < MAKEDATE('.(date('Y') - 1).', 240 )
                OR ts_update_user < (UNIX_TIMESTAMP( ) - ( 86400 *10 ))
            )'
        );
    } catch (\mysqli_sql_exception $exc) {
        \Sentry\captureException($exc);
    }
}
