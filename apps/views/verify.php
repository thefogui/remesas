<?php

require_once dirname(__FILE__) . "/../../controller/Controller.php";

/**
 * This function verifies if the url that the used to get here is valid or not
 * @throws Exception if the email ins't a valid format
 */
function checkUrl() {
    session_start();
    
    if (isset($_GET['email'])) {

        $email = $_GET['email']; //chekcs if the email insert in the url is a valid format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
            throw new Exception("The email inserted isn't a valid format.");

        if (isset($_GET['hash'])) {            
            try {
                $date = Controller::getInstance()->hashToActualData($_GET['hash']);
            
                if (!Controller::getInstance()->checkExpireDate($date)){
                    header("Location: emailForm.php?email=" . $_GET['email'] . '&' . $_GET['hash']);
                } else{
                    //TODO: change date
                    unset ($_SESSION['text']);
                    echo "erro date";
                    $_SESSION['text'] = "Error date!";
                    header("Location: confirmation.php");
                }
            } catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }
        } else {
            // Fallback behaviour goes here
            //TODO: redirect or show error template
        }
    } else {
        // Fallback behaviour goes here
         //TODO: redirect or show error template
    }
}

try {
    checkUrl();
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
?>

<!DOCTYPE html>
    <?php include(dirname(__FILE__) . "/layouts/head.php") ?>

    <body>
        <section>
            
        </section>

        <?php include(dirname(__FILE__) . "/layouts/scripts.php") ?>
        <!-- Main JS -->
        <script  type="text/javascript" src="../../web/js/main.js"></script>
    </body>
</html>