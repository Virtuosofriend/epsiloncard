<?php
/**
 * Database configuration
 */

$cfg = "/home/dsakellariou/Projects/epsilon/authenticate/api/config.json";
$config = json_decode(file_get_contents($cfg));

define('DB_USERNAME', $config->db->user);
define('DB_PASSWORD', $config->db->password );
define('DB_HOST', $config->db->host);
define('DB_NAME', $config->db->database);
?>
