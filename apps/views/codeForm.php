<?php
//TODO: verify if the client has acces

require_once dirname(__FILE__) . "/../../controller/Controller.php";

function checkUrl() {
    if (!isset($_GET["hash"])) return false;

    $hash = $_GET["hash"];

    if ($hash) {
        $uncriptedHash = Controller::getInstance()->getDataFromUrlCode($hash);
        $date = $uncriptedHash["date"];
        $email = $uncriptedHash["email"];
        $code = $uncriptedHash["code"];

        if (Controller::getInstance()->checkExpiredOneDay($date)){
            // Fallback behaviour goes here
            //TODO: redirect or show error template: error code expired invalid 
            return false;
        } else 
            return Controller::getInstance()->checkEmailDataBaseChanges($email);
    } else {
        // Fallback behaviour goes here
        //TODO: redirect or show error template: error invalid 
        return false;
    }
    return false;
}

try {
    if (!checkUrl()) {
        unset ($_SESSION['text']);
        $_SESSION['text'] = "Error validation your code!";
        header("Location: confirmation.php");
    }
} catch (Exception $e) {
    die();
}
?>

<!DOCTYPE html>
    <head>
        <title> Populetic - Validate your Code </title>
        <?php include(dirname(__FILE__) . "/layouts/head.php") ?>
        <link rel="stylesheet" href="../../web/css/emailvalidation.css">
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    </head>

    <body>
        <div class="box-login d-flex justify-content-center">
            
            <form class="align-self-center text-center form-box" method="POST" onsubmit="checkRecaptcha()" action="../../controller/ClientController.php">
                <img class="align-self-center" src="../../web/images/populetic.svg" alt="logo">

                <input type="hidden" name="hash" value="<?php echo $_GET['hash']; ?>">

                <h1 class="h3 mb-3 font-weight-normal">Insert your the code that was sent to your email here.</h1>
                <input type="text" placeholder="Ex. YZPW6A" name="code" class="form-control " value="" autofocus="" required="" autocomplete="off">
                
                <div class="mt-4">
                    <div class="g-recaptcha" data-sitekey="6LcnjRIUAAAAAKPYVfEL2M__Ix57s7zgQGVlCTux"></div>
                </div><!-- closing div mt-4 -->

                <div class="mt-4">
                    <input class="btn btn-lg btn-outline-info btn-block" type="submit" value="Send">
                </div><!-- closing div mt-4 -->

                <div class="mt-4">
                    <p>
                        I didn't received any code.
                        <!--  TODO: what to do here? -->
                        <a href="#">Send it again</a>
                    </p>
                </div><!-- closing div mt-4 -->

                <div class="mt-3 mb-3">
                    <p class="text-muted">Populetic © <?php echo date("Y"); ?></p>
                </div><!-- closing div mt-3 mb-3 -->
            </form>
        </div> <!-- closing div container -->
        
        <!-- TODO: validate the form -->

        <?php include( dirname(__FILE__) . "/layouts/scripts.php") ?>
        <!-- Main JS -->
        <script type="text/javascript" src="../../web/js/main.js"></script>
        <script type="text/javascript" src="../../web/js/emailValidation.js"></script>
        <script>
            function checkRecaptcha() {
                //TODO: change this interaction
                var recaptcha = $("#g-recaptcha-response").val();
                if (recaptcha === "") {
                    event.preventDefault();
                    alert("Please check the recaptcha");
                }
            }
        </script>
    </body>
</html>