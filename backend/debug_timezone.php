<?php
require_once 'classes/Database.php';
$db = new Database();

echo "PHP timezone: " . date_default_timezone_get() . "\n";
echo "PHP time: " . date('Y-m-d H:i:s') . "\n";

try {
    $mysqlTz = $db->fetchOne('SELECT @@session.time_zone as tz, NOW() as now_time');
    echo "MySQL timezone: " . $mysqlTz['tz'] . "\n";
    echo "MySQL time: " . $mysqlTz['now_time'] . "\n";
} catch (Exception $e) {
    echo "MySQL error: " . $e->getMessage() . "\n";
}
?>
