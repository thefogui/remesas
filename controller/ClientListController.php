<?php

require_once "../config/config.php";
include_once '../lib/model/dao/DaoUrlClient.php';
include_once '../lib/model/dao/DaoClient.php';
include_once '../lib/model/entity/UrlClient.php';
include("Controller.php");

session_start();
//atributtes form

$conn;


//functions

/**
 * 
 */
function sendEmails($destination= "../cache/"){
    $file = 'clients';
    $appConfig = new AppConfig();
    $conn = $appConfig->connect( "populetic_form", "replica" );
    $daoClient = new DaoClient();

    $jsonData = file_get_contents($destination . $file . ".json", 'r');
    $jsonArrayData = json_decode($jsonData, true);

    if ($jsonArrayData) {
        try {
            //Controller::getInstance()->deleteJson($file, "../../cache/");
        } catch (Exception $e) {
            throw $e;
        }

        foreach ($jsonArrayData as $user) {
            $email = $user[3];
            $name = $user[1];
            $clientId = $user[2];

            $daoClient->changeToSolicitarDatosPago($conn, $clientId);
            $reclamacionID = $daoClient->getIdReclamacion($conn, $clientId);
            $daoClient->insertLogChange($conn, $clientId, $reclamacionID);

            $info = "";
            $hash = "";
            $date = date('Y-m-d H:i:s');
            $hash = Controller::getInstance()->generateHash($date);

            echo Controller::getInstance()->sendEmail($info, $name, $email, $hash);
            $appConfig->closeConnection($conn);
        }
    } else {
        throw new Exception('Error reading emails');
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    try {
        sendEmails();
    } catch (Exception $e) {
        echo $e;
    }
}

//header("Location: ../apps/views/clientList.php?amount=" . $amount);