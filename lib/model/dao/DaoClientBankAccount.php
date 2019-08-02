<?php 

class DaoClientBankAccount {
    private $conn;

    public function setConn($conn) {
        $this->conn = $conn;
    }

    public function getConn() {
        return $this->conn;
    }

    /**
     * Function to insert the client bank account into the database
     * @param $clientId foreign key of the client
     * @param $bankAccount the client bank account
     * @throws error connecting with the database
     * 
     */
    public function insert($conn, $account_number, $account_holder, $billing_address, 
                        $email_client, $phone_client ,$id_claim, $swift = "") {
        if($conn) {
            $query = "INSERT IGNORE INTO populetic_form.bank_account_info (account_number, swift, account_holder, billing_address, email_client, phone_client, id_claim)
            VALUES ("
            ."'". $account_number ."'" . ", " 
            ."'". $swift ."'" . ", " 
            ."'". $account_holder ."'".  ", " 
            ."'". $billing_address ."'". ", " 
            ."'". $email_client ."'".  ", " 
            ."'". $phone_client ."'".  ", " 
            ."'". $id_claim ."'" . ");"; 
            
            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn))
                throw new Exception('Error inserting bank account into mysql: ' . mysqli_error($conn));
        
            $this->updateToReadyToPayment($conn, $id);
            $this->deletePendingBankAccount($email_client);
        }
    }

    //TODO: after test this change to private
    public function updateToReadyToPayment($conn, $id) {
        //-- cambiar de estado a 'LISTO PARA PAGO' si ya insertado los datos de pago y exit
        $query = "UPDATE populetic_form.populetic_form_vuelos pfv
                SET pfv.Id_Estado = 37
                WHERE pfv.Id_Cliente = ". $id .";";

        $result = mysqli_query($conn, $query);
    }

    /**
     * Function to update an existent bank account
     * 
     */
    public function update($conn, $id, $account_number, $swift, $account_holder, $billing_address, $email_client, $id_claim) {
        if($conn) {
            $query = "UPDATE populetic_form.bank_account_info 
                    SET "; //TODO: finish this query
            if (isset($account_number))
                $query .= "account_number = " . $account_number;
            if (isset($swift))
                $query .= "swift = " . $swift;
            if (isset($account_holder))
                $query .= "account_holder = " . $account_holder;
            if (isset($billing_address))
                $query .= "billing_address = " . $billing_address;
            if (isset($email_client))
                $query .= "email_client = " . $email_client;

            if (isset($id_claim))
                $query .= "WHERE id_claim = " . $id_claim;
            else
                $query .= "WHERE id= " . $id;

            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn))
                throw new Exception('Error updating user bank account: ' . mysqli_error($conn));
        }
    }

    //TODO: after test this change to private
    public function insertIntoPendingBankAccount($conn, $emailClaim, $idClaim) {
        if($conn) {
            $query = "INSERT INTO 
                        populetic_form.pending_bank_account (email_claim, principal_claim)
                    VALUES ("
                        . "'" . $emailClaim . "'" . ", " 
                        . "'" . $idClaim . "'" ."
                    );";

            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn))
                throw new Exception('Error inserting bank account into mysql: ' . mysqli_error($conn));
        }
    }

    //TODO: after test this change to private
    public function changeStateToWithoutBankAccount($conn, $id) {
        //-- si ya existe y es igual a 3 cambiar de estado 'SIN DATOS DE PAGO' y exit
        $query = "UPDATE populetic_form.populetic_form_vuelos pfv
                SET pfv.Id_Estado = 31
                WHERE pfv.ID = " . $id .";";

        $result = mysqli_query($conn, $query);

        if (mysqli_errno($conn))
            throw new Exception('Error chaging the state of claim with id '. $id . ' ' . mysqli_error($conn));
    }

    //TODO: after test this change to private
    public function updateTimesSentTheEmail($conn, $email) {
        //-- si ya existe y es distin to a 3
        $query = "UPDATE populetic_form.pending_bank_account pba
                    SET pba.number_of_times_sent = pba.number_of_times_sent + 1
                    WHERE pba.email_claim = " . "'" . $email . "';";

        $result = mysqli_query($conn, $query);

        if (mysqli_errno($conn))
            throw new Exception('Error chaging the state of claim with id '. $id . ' ' . mysqli_error($conn));
    }

    public function updatePendingBankAccount($conn, $emailClaim, $idClaim) {
        if ($conn) {

            $timeLimit = strtotime("-1 year");

            $query = "SELECT 
                number_of_times_sent AS numberOfTimesSent
            FROM
                populetic_form.pending_bank_account pba
            WHERE
                pba.email_claim = " . "'" . $emailClaim . "';"; 

            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn))
                throw new Exception('Error getting users: ' . mysqli_error($conn));
            else {
                $numberOfTimesSent = mysqli_fetch_assoc($result)["numberOfTimesSent"];
                
                if (!isset($numberOfTimesSent)){
                    //insert the information
                    $this->insertIntoPendingBankAccount($conn, $emailClaim, $idClaim);
                }else if ($numberOfTimesSent == 3){
                    //update to 'SIN DATOS PAGO'
                    $this->changeStateToWithoutBankAccount($conn, $idClaim);
                } else {
                    //increment the time the email was sent
                    $this->updateTimesSentTheEmail($conn, $emailClaim);
                    //TODO: insert log estados
                    
                }
            }
        }
    }

    //TODO: after test this change to private
    public function deletePendingBankAccount($conn, $emailClaim) {
        //delete this if we have the client
        $query = "DELETE FROM populetic_form.pending_bank_account 
                    WHERE populetic_form.pending_bank_account.email_claim = ". "'" . $emailClaim . "';";

        $result = mysqli_query($conn, $query);

        if (mysqli_errno($conn))
            throw new Exception('Error deleting the sql entry ' . mysqli_error($conn));
    }
}