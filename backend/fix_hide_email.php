<?php
/**
 * Fix hide_email removal in SendManager.php
 */

$file = 'classes/SendManager.php';
$content = file_get_contents($file);

// Fix SQL queries - remove hide_email from INSERT statements
$content = str_replace(
    'INSERT INTO sends (user_id, type, name, access_token, expires_at, max_views, password_hash, hide_email, metadata, content, view_count)',
    'INSERT INTO sends (user_id, type, name, access_token, expires_at, max_views, password_hash, metadata, content, view_count)',
    $content
);

// Fix VALUES clauses - remove one placeholder
$content = str_replace(
    'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)',
    'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)',
    $content
);

// Remove the hide_email parameter from the query arrays
$pattern = '/(\$sendData\[\'password_hash\'\],\s*)\$sendData\[\'hide_email\'\],(\s*\$sendData\[\'metadata\'\])/';
$content = preg_replace($pattern, '$1$2', $content);

// Write back to file
file_put_contents($file, $content);

echo "✅ Fixed hide_email removal in SendManager.php\n";

// Verify the changes
$newContent = file_get_contents($file);
$hideEmailCount = substr_count($newContent, 'hide_email');
echo "Remaining hide_email references: $hideEmailCount\n";

if ($hideEmailCount === 0) {
    echo "✅ All hide_email references successfully removed!\n";
} else {
    echo "⚠️ Some hide_email references remain.\n";
}
?>
