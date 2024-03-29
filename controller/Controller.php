<?php

/**
 * Class Controller
 */
class Controller {
    private static $instance;
    
    private function __construct() {}
    private function __clone() {}

    public static function getInstance() {
        if (!Controller::$instance instanceof self)
            Controller::$instance = new self();
        return Controller::$instance;
    }

    /**
     * This function saves a Json file using the array parameter
     * @param String $name of the json file
     * @param $array
     * @param string $destination the folder that we gonna save the file
     */
    function arrayToJson($name, $array, $destination = "../cache/") {
        $fp = fopen($destination . $name . ".json", 'w');
        fwrite($fp, json_encode($array));
        fclose($fp);
    }

    /**
     * Function to delete a json file
     * be careful using it
     *
     * @param String $name file name
     * @param string $source file source folder
     * @throws Exception
     * @exception throws an exception if the file does not exist
     */
    function deleteJson($name, $source = "../cache/") {
        $fileRouter = $source . $name . '.json';
        if (file_exists($fileRouter))
            unlink($fileRouter);
        else
            throw new Exception('Error deleting file: ' . $fileRouter .' does not exist!');
    }

    /**
     * Function to send an email to an user
     *
     * @param $name
     * @param $email
     * @param $hash
     * @param $date
     * @param int $amount
     * @param string $ref
     * @param string $languageId
     * @param string $codigo
     * @param $listClaimRefs
     * @return string
     * @throws Mandrill_Error
     * @throws phpmailerException
     */
    function sendEmailValidation($name, $email, $hash, $date, $amount = 0,
                                $ref = "X0000XXX", $languageId = 'es',
                                $codigo = "not defined", $listClaimRefs) {
        $correo = $this->initPHPMailer();
        $mandrill = $this->initMandrill();
        $templateSubject = "_(¡Hemos conseguido su indemnización! índiquenos dónde desea recibirla)";

        if (isset($listClaimRefs)) {
            $emailInfo[] = array(
                "email" => utf8_encode($email),
                "name" => utf8_encode($name),
                "amount" => utf8_encode($amount),
                "hash" => utf8_encode($hash),
                "codigo" => utf8_encode($codigo),
                "reclamacionesRelaciones" => $listClaimRefs
            );
        } else {
            $emailInfo[] = array(
                "email" => utf8_encode($email),
                "name" => utf8_encode($name),
                "amount" => utf8_encode($amount),
                "hash" => utf8_encode($hash),
                "codigo" => utf8_encode($codigo)
            );
        }

        try {
            sendMandrillBatchMail($mandrill, $ref, $email, 'correo_verificación_datos_bancarios_' . $languageId, $templateSubject, $emailInfo, 0);
        } catch(Mandrill_Error $e) {
            echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
            throw $e;
        }
        
        //TODO: remove code below
        $result = "Nothing";
        preg_match('/(\S+)(@(\S+))/', $email, $match);
        $emailExtension = $match[2];
        
        $to      = $email; // Send email to our user
        $subject = '¡Hemos conseguido su indemnización! índiquenos dónde desea recibirla ' . $ref; // Give the email a subject 
        $body = '
        
        Hello from team Populetic, 
        Zombie ipsum reversus ab viral inferno' . $name . ', nam rick grimes malum cerebro. De carne lumbering animata corpora quaeritis. Summus brains sit​​, morbo vel maleficia? De apocalypsi gorger omero undead survivor dictum mauris. Hi mindless mortuis soulless creaturas, imo evil stalking monstra adventus resi dentevil vultus comedat cerebella viventium. Qui animated corpse, cricket bat max brucks terribilem incessu zomby.
        
        Please click this link to activate your account:

        <br>
        <br>
        <br>
        <br>

        localhost/remesas/apps/views/verify.php?email=' . $email.'&hash=' . $hash ; // Our message above including the link
        
        if (isset($listClaimRefs)) {
            foreach ($listClaimRefs as $claimRef) {
                $body .= "<br>Referencia relacionada " . $claimRef;
            }
        } 

        $correo->Subject = $subject;   
        $correo->MsgHTML($body);
        $correo->AddAddress($to, "Populetic");

        if( !$correo->Send() ) 
            $result= "Error yes";
        else 
            $result= "Error no";
        return $result;
    }

    /**
     * Function to send the email code to the clients 
     * 
     */
    function sendEmailCode($name, $email, $code, $refReclamacion) {
        $correo = $this->initPHPMailer();

        
        $to      = $email; // Send email to our user
        $subject = 'Asunto: Clave de aceso - Solicitud pago indemnización ' . $refReclamacion; // Give the email a subject 
        $body    = '
    
        <p style="color:black">Apreciado/a, ' . $name . '</p>
        <p style="color:black">Hemos recibido su solicitud de verificación de los datos bancarios.</p>
        <p style="color:black">Su clave segura de acceso es: <span style="font-weight:bold"> ' .  $code .'</span></p>
        <br>
        <br>
        <p style="color:black">Gracias, </p>
        <p style="color:black">El Equipo de Populetic</p>
        <br> 
        <img width="230" height="75" src="https://ci5.googleusercontent.com/proxy/6YtNeNXk-Org0Zf-MfnzOORNDJjnYW48xOER4fr6PQxOmaItPmT2uC65TCud330gaTcVIfypAqEu5iQffink5H1jEsI_A7TPDFLm2A=s0-d-e1-ft#https://www.populetic.com/templates/images/logos/logo.png" alt="logo">
        <br>
        <p style="font-weight:bold;text-decoration:underline;">www.populetic.com</p>
        <p style="color:#0B5394;">+34 93 445 97 64</p>
        <br>
        <p style="font-size:0.8em;color:#36375E">
            Este mensaje se dirige, de modo exclusivo, a su destinatario y contiene información confidencial, cuya divulgación, copia o utilización no autorizada es contraria a la ley. <span style="font-weight:bold">Si recibe este mensaje por error, le rogamos nos lo comunique de inmediato</span> y lo elimine sin conservar copia del mismo ni de sus documentos adjuntos  
        </p>
        '; // Our message above including the link
                            
        $correo->Subject = $subject;   
        $correo->MsgHTML($body);
        $correo->AddAddress($to, "Populetic");

        if( !$correo->Send() )
            $result= "Error yes";
        else 
            $result= "Error no";
        return $result;
    }

    /**
     * Function to encrypt a text
     * @param String the text we want to encrypt
     *
     * @return bool|string
     */
    function encryptText($text) {
        if(!$text) 
            return false;

        //TODO: change the secret key (Something encrypted in the database...)
        $encrypt_method = "AES-256-CBC";
        $secret_key = 'BHEJLNWWQVQWGJSQS52D1QW32E52XWQE8';
        $secret_iv = 'BHEJLNWWQVQWGJSQS52D1QW32E52XWQE8';

        $key = hash('sha256', $secret_key);
    
        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);
   
        $crypttext = openssl_encrypt($text, $encrypt_method, $key, 0, $iv);

        return base64_encode($crypttext);
    }

    /**
     * Function to decrypt a encrypted text
     * @param String encryptedText the text encrypt
     * @return bool|string
     */
    function decryptText($encryptedText) {
        if(!$encryptedText) 
            return false;

        $encrypt_method = "AES-256-CBC";
        $secret_key = 'BHEJLNWWQVQWGJSQS52D1QW32E52XWQE8';
        $secret_iv = 'BHEJLNWWQVQWGJSQS52D1QW32E52XWQE8';

        $key = hash('sha256', $secret_key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        return openssl_decrypt(base64_decode($encryptedText), $encrypt_method, $key, 0, $iv);
    }

    /**
     * Set the phpmailer attributes
     */
    function initPHPMailer() {
        date_default_timezone_set('Etc/UTC');

        require_once( dirname(__FILE__) . '/../plugins/phpmailer/PHPMailerAutoload.php');
        require_once( dirname(__FILE__) . '/../plugins/phpmailer/class.phpmailer.php');
        
        //Create a new PHPMailer instance
        $correo = new PHPMailer;
        //Tell PHPMailer to use SMTP
        $correo->isSMTP();
        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $correo->SMTPDebug = 0;
        //Ask for HTML-friendly debug output
        $correo->Debugoutput = 'html';
        //Set the hostname of the mail server
        $correo->Host = 'smtp.gmail.com';
        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $correo->Port = 587;
        //Set the encryption system to use - ssl (deprecated) or tls
        $correo->SMTPSecure = 'tls';
        //Whether to use SMTP authentication
        $correo->SMTPAuth = true;
        //Username to use for SMTP authentication - use full email address for gmail
        $correo->Username = "populetic.test@gmail.com";
        //Password to use for SMTP authentication
        $correo->Password = "populetic123";

        return $correo;
    }

    /**
     * 
     */
    private function initMandrill() {
        require_once( dirname(__FILE__) . "/../plugins/mandrill/mandrill.php");

        $mandrill = new Mandrill('vrjSix_nhICY9pQa2gnYtQ');

        return $mandrill;
    }

    public function generateHash($expireDate, $idReclamacion) {
        $messageToEncrypt = "date=" . $expireDate . "id=" . $idReclamacion;
        return base64_encode(bin2hex($messageToEncrypt));
    }

    /**
     * Function to get the expire date from hash url
     * 
     */
    public function hashToActualData($hash) {
        $uncriptedHash = hex2bin(base64_decode($hash));
        $idReclamacion = $this->getSubStringAfter("id=", $uncriptedHash);
        $expiringDate = $this->getSubStringBetween("date=", "id=", $uncriptedHash);

        return array("expiringDate" => $expiringDate, "idReclamacion" => $idReclamacion);
    }

    /**
     * Function to check the expire date of the actual url
     * 
     * @return true if the url still valid and false otherwise
     */
    public function checkExpireDate($date) {
        if (DateTime::createFromFormat('Y-m-d H:i:s', $date) !== FALSE) {
            $exp_date = strtotime($date . '+7 days');        
            $today    = date("Y-m-d H:i:s");
            $tsToday  = strtotime($today);
            return ($tsToday > $exp_date);
        }
        return true;
    }

    public function checkExpiredOneDay($date) {
        if (DateTime::createFromFormat('Y-m-d H:i:s', $date) !== FALSE) {
            $exp_date = strtotime($date . '+1 days');        
            $today    = date("Y-m-d H:i:s");
            $tsToday  = strtotime($today);

            return ($tsToday > $exp_date);
        }
        return true;
    }

    public function uniqidReal($lenght = 13) {
        // uniqid gives 13 chars, but you could adjust it to your needs.
        if (function_exists("random_bytes")) 
            $bytes = random_bytes(ceil($lenght / 2));
        elseif (function_exists("openssl_random_pseudo_bytes")) 
            $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
        else 
            throw new Exception("no cryptographically secure random function available");
        
        return strtoupper(substr(bin2hex($bytes), 0, $lenght));
    }

    public function generateUrlCodeValidation($email, $idReclamacion) {
        $date = date('Y-m-d H:i:s');
        $code = "";
        try {
            $code = $this->uniqidReal(6);
        } catch (Exception $e) {
            //TODO: redirect to error page
        }
        
        $url = "date=" . $date . "code=" . $code . "email=" . $email . "id=" . $idReclamacion;
        $url = $this->encryptText($url);

        return array('url' => $url, 
                    'code' => $code, 
                    'email' => $email, 
                    'date' => $date, 
                    "idReclamacion" => $idReclamacion 
        );
    }

    private function getSubStringBetween($start, $end, $string) {
        $string = ' ' . $string;
        $ini = strpos($string, $start);

        if ($ini == 0) return '';

        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    /**
     * Function that return a string after a string sequence
     * @param start the string sequence
     * @param str the string we want to remove the string sequence
     * @return bool|string
     */
    private function getSubStringAfter($start, $str) {
        if (!is_bool(strpos($str, $start)))
            return substr($str, strpos($str, $start) + strlen($start));
        return false;
    }

    public function getDataFromUrlCode($url) {
        $url_decoded = $this->decryptText($url);

        $date  = $this->getSubStringBetween('date=', 'code=', $url_decoded);
        $code  = $this->getSubStringBetween('code=', "email=", $url_decoded);
        $email = $this->getSubStringBetween('email=', 'id=', $url_decoded);

        $idReclamacion = $this->getSubStringAfter("id=", $url_decoded);

        return array('date' => $date, 'code' => $code, 'email' => $email, 'idReclamacion' => $idReclamacion);
    }
    
    public function rediectToInfoPage($message) {
        unset ($_SESSION['text']);
        $_SESSION['text'] = $message;
        header("Location: ../apps/views/confirmation.php");
    }

    public function checkUrl($hash) {
        if (!isset($hash)) return false;
    
        $_SESSION['hash'] = $hash;
    
        if ($hash) {
            $uncriptedHash = $this->getInstance()->getDataFromUrlCode($hash);
    
            $date = $uncriptedHash["date"];
            $email = $uncriptedHash["email"];
    
            $_SESSION['email'] = $email;
    
            return !($this->getInstance()->checkExpiredOneDay($date)) ||
                    $this->getInstance()->checkEmailDataBaseChanges($email);
        }
        return false;
    }

    public function checkUrlbankAccountView($hash) {
        if (!isset($hash)) return false;

        require_once "../../lib/model/dao/DaoClient.php";
        require_once "../../lib/model/entity/Client.php";

        $appConfig = new AppConfig();
        $daoClient = new DaoClient();

        $conn = $appConfig->connect( "populetic_form", "replica" );
        
        $uncriptedHash = Controller::getInstance()->getDataFromUrlCode($hash);

        $date = $uncriptedHash["date"];
        $_SESSION["email"] = $uncriptedHash["email"];
        $email = $_SESSION["email"];
        $idReclamacion = $uncriptedHash["idReclamacion"];
        $_SESSION['id_claim'] = $idReclamacion;

        $reclamacion = $daoClient->getIdReclamacionById($conn, $idReclamacion);

        if (!isset($reclamacion)) return false;

        $_SESSION["reclamacion"] = $reclamacion;

        $appConfig->closeConnection($conn);

        return !Controller::getInstance()->checkExpiredOneDay($date);
    }

    public function getListOfClaims($emailClaim, $idClaim) {
        require_once "../lib/model/dao/DaoClient.php";
        require_once "../lib/model/entity/Client.php";
        require_once "../config/config.php";

        $appConfig = new AppConfig();
        $daoClient = new DaoClient();

        $conn = $appConfig->connect( "populetic_form", "replica" );

        $claims = $daoClient->getAllCalims($conn, $emailClaim, $idClaim);
        
        return $claims;
    }

    public function redirectToInfoPage($message="", $error_message="") {
        unset($_SESSION['text']);
        unset($_SESSION["error_message"]);
        if (isset($message))
            $_SESSION['text'] = $message;
        if (isset($error_message))
            $_SESSION["error_message"] = $error_message;
        header("Location: confirmation.php");
    }

    public function getBankAccountData() {
        require_once "../../lib/model/dao/DaoClientBankAccount.php";
        require_once "../../lib/model/entity/Client.php";
        require_once "../../config/config.php";

        $appConfig = new AppConfig();
        $daoClientBankAccount = new DaoClientBankAccount();
        return $daoClientBankAccount->getAllAccounts($appConfig->connect("populetic_form", "replica"));
    }

    public function displayView($template, $templateData = array()) {
        $view = new View($template);

        foreach ($templateData as $key => $value) {
            $view->$key = $value;
        }

        return $view;
    }

    /**
     * Función para recuperar el nombre un usuario usando la refenrencia de la
     * reclamación.
     * @param $refReclamacion String
     * @return String nombre del cliente que tiene la referencia indicada
     * @throws Exception
     */
    public function getUserNameByClaimRef($refReclamacion) {
        require_once "../lib/model/dao/DaoClient.php";
        $daoClient = new DaoClient();
        $appConfig = new AppConfig();

        $conn = $appConfig->connect( "populetic_form", "replica" );

        return $daoClient->getUserNameByClaimRef($conn, $refReclamacion);
    }

    public function translationsFileToSession($languageCode) {
        $languageJson     = $languageCode . '.json';
        $cacheDir         = dirname(__FILE__) . "/../cache/";
        $languageJsonFile = $cacheDir . $languageJson;

        $translations = array();

        if (file_exists($languageJsonFile)) {
            $translations = json_decode(file_get_contents($languageJsonFile), true);
        } else {
            //TODO: what to do here?
        }

        return $translations;
    }

    public function getText($tag, $languageCode) {
        $addTag = false;
        $text   = "";
        $tag    = str_replace(")", "", str_replace("_(", "", $tag));
        $aTag   = explode(".", $tag);

        //create the file for the languageCode
        if (!isset($_SESSION["translations"])) {

        } else {
            $translations = $_SESSION["translations"];

        }
    }
}