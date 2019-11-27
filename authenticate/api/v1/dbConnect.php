<?php

class dbConnect {

    private $conn;

    function __construct() {     
    }

    /**
     * Establishing database connection
     * @return database connection handler
     */
    function connect() {

        include_once '../config.php';

        // Connecting to mysql database
        $this->conn = pg_connect("dbname=" . DB_NAME ." host=" . DB_HOST . " user= ". DB_USERNAME ." password= " . DB_PASSWORD);
        #echo "dbname=" . DB_NAME ." host=DB_HOST user=DB_USERNAME password=DB_PASSWORD";
        #echo "dbname=" . DB_NAME ." host=" . DB_HOST . " user= ". DB_USERNAME ." password= " . DB_PASSWORD;
        // Check for database connection error
        /*
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        */

        // returing connection resource
        return $this->conn;
    }

}


#var_dump($db);
?>
