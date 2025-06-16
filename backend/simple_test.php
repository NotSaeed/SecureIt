<?php
echo "PHP is working!\n";
echo "Current directory: " . getcwd() . "\n";
echo "Files in directory:\n";
foreach (glob("*") as $file) {
    echo "- " . $file . "\n";
}
?>
