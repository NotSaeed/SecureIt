<?php
require_once 'classes/Database.php';

$db = new Database();
$result = $db->fetchAll('SELECT id, name, password_hash, access_password FROM sends LIMIT 5');
echo "Sends in database:\n";
print_r($result);
?>
