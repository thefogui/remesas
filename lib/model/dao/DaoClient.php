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
     * Function to get the client Amount Reviewed
     * @param $clientId the id used to identify the client
     */
    private function getClientAmountReviewed($clienld) {
        $amountReviewed = 400;
        //TODO: query to get the amount reviewed
        return $amountReviewed;
    }

    
    /**
     * This function returns all the clients that has the state : 
     * 'solicitar datos pago'
     * 
     * @param amount the amount of money
     * @param conn the connection with the sql
     * @return array that contains the clients array the amount to pay to the vips clients and the amount left after pay clients.
     */
    public function getClientVip($conn, $amount) {
        $state = 'solicitar datos pago';
        $clients = array();
        $amountToPay = 0.0;
        $clientsVip = 0.0;
        $amountLeft = $amount;
    
        if($conn) {
            //TODO: orderby the amount of money the client gonna receive
            $query = "SELECT 
                         c.DocIdentidad AS nif
                        ,c.Nombre AS name
                        ,pfv.Id_Cliente AS id
                        ,c.Email AS email 
                        ,pfv.Cuantia_pasajero AS amountReviewed
                    FROM 
                        populetic_form_vuelos pfv
                    INNER JOIN 
                        clientes c ON c.ID = pfv.Id_Cliente
                    WHERE 
                        pfv.Id_Estado = 36 
                    ORDER BY 
                        amountReviewed";

            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn)) {
                throw new Exception('Error getting users: ' . mysqli_error($conn));
            } else {
                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    
                    $client = new Client($row['nif'], $row['name'], $row['id'], $row['email']);
                    
                    //logical behind the amount
                    $clientAmount = $client->amountToPay($row['amountReviewed']);
                    
                    $amountToPay = $amountToPay + $clientAmount;

                    if ($amountToPay <= $amount) {
                        $amountLeft = $amountLeft - $clientAmount;
                        $clientValue = array($row['nif'], $row['name'], $row['id'], $row['email'], $clientAmount);
                        $clients[] = $clientValue;
                    }
                }
            }
            $clientsSize = count($clients);
        }
        return array("clients"=>$clients,"amountLeft"=> $amountLeft, "amountToPay"=>$amountToPay, "totalClients"=>$clientsSize);
    }

     /**
     * Function to get the oldest month that contains a possible bill
     * @param conn
     * @return month
     * @throws exception error connecting to the sql database
     */
    public function getTheOldestDate($conn) {
        if ($conn) {
            $query = "SELECT 
                        lg.Data As d
                    FROM 
                        populetic_form.populetic_form_vuelos pfv
                    INNER JOIN 
                        halbrand.logs_estados lg ON pfv.ID = lg.Id_reclamacion
                    WHERE 
                        pfv.Id_Estado = 18 AND 
                        lg.Estado = 18
                    ORDER BY
                        d
                    LIMIT 1"; 

            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn)) {
                throw new Exception('Error getting users: ' . mysqli_error($conn));
            } else {
                $d = mysqli_fetch_assoc($result)["d"];
            }
        }
        return $d;
    }

    /**
     * Function to get the bills order by the month inserted
     * @param $conn
     * @param $month
     * @return 
     * @throws 
     */
    public function getClientsByMonth($conn, $month, $year,$amount) {
        $clients = array();
        $amountToPay = 0.0;
        $clientsVip = 0.0;
        $amountLeft = $amount;

        if ($conn) {
            $query = "SELECT 
                            c.DocIdentidad AS nif
                            ,c.Nombre AS name
                            ,pfv.Id_Cliente AS id
                            ,c.Email AS email 
                            ,pfv.Cantidadcompensacion AS amountReviewed
                            ,pfv.Cuantia_pasajero
                        FROM 
                            populetic_form.populetic_form_vuelos pfv
                        INNER JOIN 
                            populetic_form.clientes c ON c.ID = pfv.Id_Cliente
                        INNER JOIN 
                            halbrand.logs_estados lg ON pfv.ID = lg.Id_reclamacion
                        WHERE 
                            pfv.Id_Estado = 18 
                            AND 
                            MONTH(FROM_UNIXTIME(lg.data)) =" . $month .
                            " AND
                            YEAR(FROM_UNIXTIME(lg.data)) =" . $year .
                        " ORDER BY 
                            amountReviewed";

            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn)) {
                throw new Exception('Error getting users: ' . mysqli_error($conn));
            } else {
                while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    
                    $client = new Client($row['nif'], $row['name'], $row['id'], $row['email']);
                    
                    //logical behind the amount
                    $clientAmount = $client->amountToPay($row['amountReviewed']);
                    
                    $amountToPay = $amountToPay + $clientAmount;

                    if ($amountToPay <= $amount) {
                        $amountLeft = $amountLeft - $clientAmount;
                        $clientValue = array($row['nif'], $row['name'], $row['id'], $row['email'], $clientAmount);
                        $clients[] = $clientValue;
                    }
                }
            }
            $clientsSize = count($clients);
        }
        return array("clients"=>$clients,"amountLeft"=> $amountLeft, "amountToPay"=>$amountToPay, "totalClients"=>$clientsSize);
    }

    /**
     * 
     */
    public function getClients($conn, $amount) {
        $result = $this->getClientVip($conn, $amount);
        $amountLeft = $result["amountLeft"];
        $d = $this->getTheOldestDate($conn);

        $start = date("Y-m-d H:i:s", $d);
        $ts_start = $d;
        $end = date("Y-m-d H:i:s", strtotime('first day of +1 month'));

        $amountToPay = $result["amountToPay"];

        $month = intval(date("m", $d));
        $year = intval(date("y", $d));

        $result["numVips"] = $result["totalClients"];

        while (($start < $end) && ($amountToPay <= $amount)) {
            $resultsClientsMonth = $this->getClientsByMonth($conn, $month, $year, $amountLeft);
            $amountToPay = $amountToPay + $resultsClientsMonth["amountToPay"];
            $amountLeft = $amount - $amountToPay;
            
            echo "<br><hr>" . $month . " d ". $d . " Start ". $start . " TimeStamp ". $ts_start;

            $result["clients"] = $this->mergeData($result["clients"], $resultsClientsMonth["clients"]);
            $result["totalClients"] = $result["totalClients"] + $resultsClientsMonth["totalClients"];
            $result["amountLeft"] = $amountLeft;
            $result["amountToPay"] = $amountToPay;

            $month = $month + 1;
            if ($month == 13) {
                $month = 1;
                $year = $year + 1;
            }

            $start = date("Y-m-d H:i:s", strtotime("+1 month", $ts_start));
            $ts_start = strtotime("+1 month", $ts_start);          
        }
        return $result;
    }

    private function mergeData($array1, $array2) {
        $smallestArray = $array2;
        $bigestArray = $array1;

        if (count($array1) < count($array2)) {
            $smallestArray = $array1;
            $bigestArray = $array2;
        }

        foreach ($smallestArray as $row) {
            $bigestArray[] = $row;
        }

        return $bigestArray;
    }
}