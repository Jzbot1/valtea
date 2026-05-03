<?php
// fix_config.php
$db_file = __DIR__ . '/config/database.php';
$config_file = __DIR__ . '/config/config.php';

if (!file_exists($db_file)) {
    die("Error: config/database.php not found. Please run the installer first.");
}

$content = file_get_contents($db_file);

if (strpos($content, "require_once __DIR__ . '/config.php';") === false) {
    $content = str_replace("<?php", "<?php\nrequire_once __DIR__ . '/config.php';", $content);
    if (file_put_contents($db_file, $content)) {
        echo "Success! config/database.php has been fixed. <a href='login'>Go to Login</a>";
    } else {
        echo "Error: Could not write to config/database.php. Please check file permissions.";
    }
} else {
    echo "Config already seems correct. <a href='login'>Go to Login</a>";
}
