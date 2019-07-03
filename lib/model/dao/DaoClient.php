<?php

/**
 * Class to connect to the client table in the database
 * @package 
 * @see     
 */
class DaoClient {

    private $conn;

    public function setConn($conn) {
        $this->conn = $conn;
    }

    public function getConn() {
        return $this->conn;
    }
    
    /**
     * This function returns all the clients that has the state : 
     * 'solicitar datos pago'
     */
    public function getClientVip($conn) {
        $state = 'solicitar datos pago';
        $clients = array();
    
        if($conn) {
            $query = "SELECT pfv.'Id_Cliente', c.'Email' 
                      FROM populetic_form_vuelos pfv
                      LEFT JOIN clientes c 
                      ON c.'ID' = pfv.'Id_Cliente'
                      WHERE pfv.'Id_Estado' = 36";

            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn)) {
                throw new Exception('Error getting users: ' . mysqli_error($conn));
            } else {
                while ($client = mysqli_fetch_object($result, 'Client')) {
                    $clients[] = $client;
                }
            }
        }
        return  $clients;

    }
}