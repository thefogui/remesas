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
     *
     * @param $amount int the amount of money
     * @param $conn mysqli the connection with the sql
     * @return array that contains the clients array the amount to pay to the vips clients and the amount left after pay clients.
     * @throws Exception
     */
    public function getClientVip($conn, $amount) {
        $state = 'solicitar datos pago';
        $clients = array();
        $amountToPay = 0.0;
        $clientsVip = 0.0;
        $amountLeft = $amount;
    
        if($conn) {
            $query = "SELECT
                         pfv.ID AS id_reclamacion
                        , IFNULL(pfv.Ref, '') AS referencia 
                        , IFNULL(pfv.Codigo, '') AS codigo
                        ,c.DocIdentidad AS nif
                        , CONCAT(c.Nombre, ' ',c.Apellidos) AS name
                        ,pfv.Id_Cliente AS id
                        ,c.Email AS email 
                        ,pfv.langId AS lang
                        ,pfv.Cuantia_pasajero AS amountReviewed
                        ,pfv.Acompanyante AS listAssociates
                    FROM    
                        populetic_form_vuelos pfv
                    INNER JOIN 
                        clientes c ON c.ID = pfv.Id_Cliente
                    WHERE 
                        pfv.Id_Estado = 36
                    ORDER BY 
                        amountReviewed";

            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn)) 
                throw new Exception('Error getting users: ' . mysqli_error($conn));
            else {
                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    
                    $client = new Client($row['nif'], $row['name'], $row['id'], $row['email']);
                    
                    //logical behind the amount
                    $clientAmount = $client->amountToPay($row['amountReviewed']);
                    
                    $amountToPay = $amountToPay + $clientAmount;

                    if ($amountToPay <= $amount) {
                        $amountLeft = $amountLeft - $clientAmount;
                        $clientValue = array(
                            "idClient" => $row['id'],
                            "nif" => $row['nif'], 
                            "name" => utf8_encode($row['name']),
                            "email" => utf8_encode($row['email']), 
                            "clientAmount" => $clientAmount, 
                            "referencia" => $row['referencia'], 
                            "lang" => $row['lang'],
                            "id_reclamacion" => $row['id_reclamacion'],
                            "codigo" => $row['codigo'],
                            "vipSattus" => true, 
                            "listAssociates" => $row["listAssociates"]
                        );
                        $clients[] = $clientValue;
                    }
                }
            }
            $clientsSize = count($clients);
        }
        return array("clients"=>$clients, "amountLeft"=> $amountLeft, "amountToPay"=>$amountToPay, "totalClients"=>$clientsSize);
    }

     /**
     * Function to get the oldest month that contains a possible bill
     * @param conn
     * @return month
     * @throws exception error connecting to the sql database
     */
    public function getTheOldestDate($conn) {
        if ($conn) {

            $timeLimit = strtotime("-1 year");

            $query = "SELECT 
                        lg.Data AS d
                    FROM 
                        populetic_form.populetic_form_vuelos pfv
                    INNER JOIN 
                        halbrand.logs_estados lg ON pfv.ID = lg.Id_reclamacion
                    WHERE 
                        pfv.Id_Estado = 18 
                    AND 
                        lg.Data > " . $timeLimit . " 
                    ORDER BY
                        d"; 

            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn))
                throw new Exception('Error getting users: ' . mysqli_error($conn));
            else
                $d = mysqli_fetch_assoc($result)["d"];
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
    public function getClientsByMonth($conn, $month, $year, $amount) {
        $clients = array();
        $amountToPay = 0.0;
        $clientsVip = 0.0;
        $amountLeft = $amount;

        if ($conn) {
            $query = "SELECT 
                            pfv.ID AS id_reclamacion
                            , IFNULL(pfv.Ref, '') AS referencia 
                            , IFNULL(pfv.Codigo, '') AS codigo
                            ,c.DocIdentidad AS nif
                            , CONCAT(c.Nombre, ' ',c.Apellidos) AS name
                            ,pfv.Id_Cliente AS id
                            ,c.Email AS email 
                            ,pfv.langId AS lang
                            ,pfv.Cuantia_pasajero AS amountReviewed
                            ,pfv.Acompanyante AS listAssociates
                            ,FROM_UNIXTIME(lg.Data) AS `date`
                        FROM 
                            populetic_form.populetic_form_vuelos pfv
                        INNER JOIN 
                            populetic_form.clientes c ON c.ID = pfv.Id_Cliente
                        INNER JOIN 
                            halbrand.logs_estados lg ON pfv.ID = lg.Id_reclamacion
                        WHERE 
                            pfv.Id_Estado = 18 
                            AND 
                            MONTH(FROM_UNIXTIME(lg.data)) = " . $month .
                            " AND
                            YEAR(FROM_UNIXTIME(lg.data)) = " . $year .
                        " ORDER BY 
                            amountReviewed";

            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn)) throw new Exception('Error getting users: ' . mysqli_error($conn));
            else {
                while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    
                    $client = new Client($row['nif'], $row['name'], $row['id'], $row['email']);
                    
                    //logical behind the amount
                    $clientAmount = $client->amountToPay($row['amountReviewed']);
                    
                    $amountToPay = $amountToPay + $clientAmount;

                    if ($amountToPay <= $amount) {
                        $amountLeft = $amountLeft - $clientAmount;
                        $clientValue = array(
                            "idClient" => $row['id'],
                            "nif" => $row['nif'], 
                            "name" => utf8_encode($row['name']),
                            "email" => utf8_encode($row['email']), 
                            "clientAmount" => $clientAmount, 
                            "referencia" => $row['referencia'], 
                            "lang" => $row['lang'],
                            "id_reclamacion" => $row['id_reclamacion'],
                            "codigo" => $row['codigo'],
                            "vipSattus" => false,
                            "listAssociates" => $row["listAssociates"]
                        );
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
    public function changeToSolicitarDatosPago($conn, $id) {
        if ($conn) {
            $query = "UPDATE populetic_form.populetic_form_vuelos pfv
                      SET pfv.Id_Estado = 36
                      WHERE pfv.ID = ". $id .";";
            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn)) throw new Exception('Error getting users: ' . mysqli_error($conn));
        } else
            throw new Exception('Error conecting to the sql database!');
    }

    public function getIdReclamacion($conn, $id){
        $id_result = null;

        if ($conn) {
            $query = "SELECT  `populetic_form_vuelos`.`ID` AS id_pfv, `Ref` AS ref
                      FROM `populetic_form`.`populetic_form_vuelos` 
                      WHERE `Id_Cliente` IN (". $id . ")
                      ORDER BY id_pfv ASC
                      LIMIT 1;";
            $result = mysqli_query($conn, $query);
            if (mysqli_errno($conn)) throw new Exception('Error getting users: ' . mysqli_error($conn));
            
            $id_result = mysqli_fetch_assoc($result)["id_pfv"];
        } else
            throw new Exception('Error conecting to the sql database!');
        return $id_result;
    }

    public function getIdReclamacionById($conn, $idReclamacion) {
        $row = null;

        if ($conn) {
            $query = "SELECT 
                        pfv.ID AS id_reclamacion 
                    , IFNULL(pfv.Ref, '') AS referencia 
                    , CONCAT(c.Nombre, ' ',c.Apellidos) AS name 
                    , pfv.langId AS lang
                    , pfv.Cuantia_pasajero AS compensation
                    FROM 
                        populetic_form.populetic_form_vuelos pfv
                    INNER JOIN 
                        populetic_form.clientes c ON c.ID = pfv.Id_Cliente
                    WHERE 
                        pfv.ID = ". $idReclamacion . "
                    ORDER BY 
                        compensation";

            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn)) throw new Exception('Error getting users: ' . mysqli_error($conn));
            
            $row = mysqli_fetch_assoc($result);
        } else
            throw new Exception('Error conecting to the sql database!');
        return $row;
    }

    public function insertLogChange($conn, $clienld, $reclamacionId, $estado) {
        if ($conn) {
            $query = "INSERT INTO 
                        halbrand.logs_estados (`Id_reclamacion`, `Data`, `Estado`, `Tipo`, `Id_Agente`, `Checked`) 
                        VALUES (" . $reclamacionId . ", CURRENT_TIMESTAMP, " . "'" . $estado . "' , '1', '19', '0');";
            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn)) throw new Exception('Error getting users: ' . mysqli_error($conn));
        } else
            throw new Exception('Error conecting to the sql database!');
    }

    /**
     *
     * @throws exception
     */
    public function getClients($conn, $amount) {
        $result = $this->getClientVip($conn, $amount);
        ini_set('max_execution_time', 300);
        set_time_limit(300);

        $amountLeft = $result["amountLeft"];

        try {
            $d = $this->getTheOldestDate($conn);
        } catch (exception $e) {

        }

        $start = date("Y-m-d H:i:s", $d);
        $ts_start = $d;
        $end = date("Y-m-d H:i:s", strtotime('first day of +1 month'));

        $amountToPay = $result["amountToPay"];

        $month = intval(date("m", $d));
        $year = intval(date("Y", $d));
        
        $result["numVips"] = intval($result["totalClients"]);

        while (($start < $end) && ($amountToPay <= $amount)) {
            $resultsClientsMonth = $this->getClientsByMonth($conn, $month, $year, $amountLeft);
            $amountToPay = $amountToPay + $resultsClientsMonth["amountToPay"];
            $amountLeft = $amount - $amountToPay;

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

    public function getAllCalims($conn, $email, $id) {
        $listOfClaims = array();

        if ($conn) {
            $query = "SELECT 
                        pfv.ID AS id_reclamacion
                        , IFNULL(pfv.Ref, '') AS referencia 
                        , IFNULL(pfv.Codigo, '') AS codigo
                        ,c.DocIdentidad AS nif
                        , CONCAT(c.Nombre, ' ',c.Apellidos) AS name
                        ,pfv.Id_Cliente AS id
                        ,c.Email AS email 
                        ,pfv.langId AS lang
                        ,pfv.Cuantia_pasajero AS amountReviewed 
                    FROM 
                        populetic_form.populetic_form_vuelos pfv 
                    INNER JOIN
                        populetic_form.clientes c
                    ON 
                        pfv.Id_Cliente = c.ID
                    WHERE 
                        c.Email = " . "'". $email ."'" .
                    " AND
                        pfv.Id_Estado = 36 
                     AND
                        pfv.Ref IS NOT NULL;";

            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn)) throw new Exception('Error getting users: ' . mysqli_error($conn));
            else {
                while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    $client = new Client($row['nif'], $row['name'], $row['id'], $row['email']);

                    $clientAmount = $client->amountToPay($row['amountReviewed']);
                    $compensation = ($row['amountReviewed'] - $clientAmount);

                    $claimInfo = array(
                        "idClient" => $row['id'],
                        "nif" => $row['nif'], 
                        "name" => utf8_encode($row['name']),
                        "email" => utf8_encode($row['email']), 
                        "clientAmount" => $clientAmount, 
                        "referencia" => $row['referencia'], 
                        "lang" => $row['lang'],
                        "id_reclamacion" => $row['id_reclamacion'],
                        "compensation" => $row['amountReviewed'],
                        "comision" => $compensation
                    );
                
                    array_push($listOfClaims, $claimInfo);
                }
            }
        }
        return $listOfClaims;
    }

    public function getUserNameByClaimRef($conn,  $reclamacionRef) {
        if ($conn) {
            $query = sprintf("SELECT 
                        c.Nombre AS `name`
                    FROM 
                        clientes c
                    INNER JOIN 
                        populetic_form_vuelos pfv
                    ON 
                        c.ID = pfv.Id_Cliente
                    WHERE 
                        pfv.Ref = '%s'", $reclamacionRef);

            $result = mysqli_query($conn, $query);

            if (mysqli_errno($conn))
                throw new Exception('Error getting users: ' . mysqli_error($conn));
            else {
                $row = mysqli_fetch_assoc($result);
                return $row['name'];
            }
        } else
            throw new Exception('Error connecting to the sql database!');
    }

    private function mergeData($array1, $array2) {
        $smallestArray = $array2;
        $biggestArray = $array1;

        if (count($array1) < count($array2)) {
            $smallestArray = $array1;
            $biggestArray = $array2;
        }

        foreach ($smallestArray as $row) $biggestArray[] = $row;
        
        return $biggestArray;
    }
}