<?php
namespace Postgres;
class PostgresBase {
    protected $connection = null;

    function __construct(&$db, &$host, &$userName, &$password) {
        $this->connection = pg_connect("dbname=$db host=$host user=$userName password=$password");
    }

    function __destruct(){
        pg_close($this->connection);
        $this->connection = null;
    }
    
    function fetchRawQueryResult(&$query) {
      return pg_query($this->connection, $query);
    }

    function fetchQueryResult(&$query) {
      $result = pg_query($this->connection, $query);
      $return = array();
      while ($row = pg_fetch_row($result)) {
        $return [] = $row;
      }
      return $return;
    }

    function printQueryResult(&$query) {
        $result = pg_query($this->connection, $query);
        while ($row = pg_fetch_row($result)) {
            foreach ($row as $elem)
                echo $elem . "\t";
            echo "\n";
        }
    }
    
    function writeQueryResultToFile(&$query, &$columns, &$file) {
        fwrite($file, $columns);
        fwrite($file, "\n");
        $result = pg_query($this->connection, $query);
        while ($row = pg_fetch_row($result)) {
            $target = "";
            foreach ($row as $elem) {
                if ($elem == null)
                    $elem = "NULL";
                $target.="\"$elem\",";
            }
            $target = rtrim($target, ",") ."\n";
            fwrite($file, $target);
        }
    }

    function executeQuery(&$query) {
        pg_query($this->connection, $query);
    }
}
?>
