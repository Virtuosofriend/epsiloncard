<?php

class DbHandler {

    private $conn;

    function __construct() {

        require_once 'dbConnect.php';
        // opening db connection

        $db = new dbConnect();
        $this->conn = $db->connect();
    }
    /**
     * Fetching single record
     */
    public function getOneRecord($query) {
        #print $query ."\n";
        $r = pg_query($query.' LIMIT 1'); #or die($this->conn->error.__LINE__);
        return pg_fetch_assoc($r);    
    }
    /**
     * Creating new record
     */
    public function insertIntoTable($obj, $column_names, $table_name) {
        
        $c = (array) $obj;
        $keys = array_keys($c);
        $columns = '';
        $values = '';
        foreach($column_names as $desired_key){ // Check the obj received. If blank insert blank into the array.
           if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
            }else{
                $$desired_key = $c[$desired_key];
            }
            $columns = $columns.$desired_key.',';
            $values = $values."'".$$desired_key."',";
        }
        $query = "INSERT INTO ".$table_name."(".trim($columns,',').") VALUES(".trim($values,',').") RETURNING uid, created, type";
        $r = pg_query($query); 
        $r = pg_fetch_row($r);
        if ($r) {
            return $r;
            } else {
            return NULL;
        }
    }
public function executeQuery (&$query) {
    pg_query($query);
}    

public function getSession(){
    if (!isset($_SESSION)) {
        session_start();
    }
    $sess = array();
    if(isset($_SESSION['uid']))
    {
        $sess["uid"] = $_SESSION['uid'];
        $sess["name"] = $_SESSION['name'];
        $sess["email"] = $_SESSION['email'];
        $sess["session_id"] = $_SESSION["session_id"];
        $sess["type"] = $_SESSION["type"];
    }
    else
    {
        $sess["uid"] = '';
        $sess["name"] = 'Guest';
        $sess["email"] = '';
        $sess["session_id"] = "";
         $sess["type"] = "";
    }
    return $sess;
}
public function destroySession(){
    if (!isset($_SESSION)) {
    session_start();
    }
    if(isSet($_SESSION['uid']))
    {
        $query = "DELETE FROM customers_session WHERE user_id = " . $_SESSION['uid'] . " AND session_id = '" . $_SESSION['session_id'] ."';";
        $this->executeQuery($query);
        unset($_SESSION['uid']);
        unset($_SESSION['name']);
        unset($_SESSION['email']);
        $info='info';
        if(isSet($_COOKIE[$info]))
        {
            setcookie ($info, '', time() - $cookie_time);
        }
        $msg="Logged Out Successfully...";
        
    }
    else
    {
        $msg = "Not logged in...";
    }
    return $msg;
}
 
}

?>
