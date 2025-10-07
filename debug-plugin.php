<?php
/**
 * Debug Script for Attendance Management Plugin
 * Run this to check plugin status and menu visibility
 */

// Load WordPress
require_once('../../../wp-config.php');

echo "<h1>🔍 Attendance Management Plugin Debug</h1>";

// Check if we're in admin
echo "<h2>1. WordPress Environment</h2>";
echo "Is Admin: " . (is_admin() ? "✅ Yes" : "❌ No") . "<br>";
echo "Current User: " . (is_user_logged_in() ? "✅ Logged In" : "❌ Not Logged In") . "<br>";
echo "User Capability: " . (current_user_can('manage_options') ? "✅ Can Manage Options" : "❌ Cannot Manage Options") . "<br>";

// Check if LifterLMS is active
echo "<h2>2. LifterLMS Status</h2>";
if (class_exists('LifterLMS')) {
    echo "✅ LifterLMS is active<br>";
} else {
    echo "❌ LifterLMS is not active<br>";
}

// Check if our plugin classes exist
echo "<h2>3. Plugin Classes</h2>";
$classes = [
    'LLMS_AT_Database',
    'LLMS_AT_Testing_Framework', 
    'LLMS_AT_Migration',
    'LLMS_AT_Hybrid_Manager',
    'LLMS_AT_Test_Suite',
    'LLMS_AT_CLI_Testing'
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "✅ $class exists<br>";
    } else {
        echo "❌ $class not found<br>";
    }
}

// Check if our plugin is active
echo "<h2>4. Plugin Status</h2>";
if (class_exists('LLMS_Attendance')) {
    echo "✅ Main plugin class exists<br>";
    
    // Check if instance exists
    if (isset($GLOBALS['LLMS_Attendance'])) {
        echo "✅ Plugin instance exists<br>";
    } else {
        echo "❌ Plugin instance not found<br>";
    }
} else {
    echo "❌ Main plugin class not found<br>";
}

// Check menu hooks
echo "<h2>5. Menu Hooks</h2>";
global $wp_filter;

$menu_hooks = [
    'admin_menu',
    'init',
    'plugins_loaded'
];

foreach ($menu_hooks as $hook) {
    if (isset($wp_filter[$hook])) {
        echo "✅ $hook hook exists<br>";
        $callbacks = $wp_filter[$hook]->callbacks;
        echo "&nbsp;&nbsp;Callbacks: " . count($callbacks) . "<br>";
    } else {
        echo "❌ $hook hook not found<br>";
    }
}

// Check database table
echo "<h2>6. Database Table</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'llms_attendance';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if ($table_exists) {
    echo "✅ Custom table exists: $table_name<br>";
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "&nbsp;&nbsp;Records: $count<br>";
} else {
    echo "❌ Custom table does not exist: $table_name<br>";
}

// Check plugin options
echo "<h2>7. Plugin Options</h2>";
$options = [
    'llms_integration_reporting_enabled',
    'llmsat_migration_status', 
    'llmsat_use_custom_table',
    'llmsat_db_version'
];

foreach ($options as $option) {
    $value = get_option($option, 'not_set');
    echo "$option: $value<br>";
}

// Check if menu items are registered
echo "<h2>8. Menu Items Check</h2>";
global $menu, $submenu;

if (isset($submenu['edit.php?post_type=course'])) {
    echo "✅ Course submenu exists<br>";
    $course_submenu = $submenu['edit.php?post_type=course'];
    
    $attendance_menus = [
        'Attendance Reports',
        'Attendance Migration', 
        'Performance Testing'
    ];
    
    foreach ($course_submenu as $item) {
        if (in_array($item[0], $attendance_menus)) {
            echo "✅ Found menu: " . $item[0] . "<br>";
        }
    }
} else {
    echo "❌ Course submenu not found<br>";
}

echo "<h2>9. Next Steps</h2>";
echo "If you see ❌ marks above, here's what to do:<br><br>";

echo "<strong>If classes are missing:</strong><br>";
echo "1. Check if all files are uploaded correctly<br>";
echo "2. Check file permissions<br>";
echo "3. Check for PHP errors in error logs<br><br>";

echo "<strong>If menu items are missing:</strong><br>";
echo "1. Make sure you're logged in as admin<br>";
echo "2. Check if LifterLMS is active<br>";
echo "3. Try deactivating and reactivating the plugin<br><br>";

echo "<strong>If database table is missing:</strong><br>";
echo "1. Go to WordPress Admin → Courses → Attendance Migration<br>";
echo "2. Start the migration process<br><br>";

echo "<hr>";
echo "<p><strong>💡 Tip:</strong> After fixing any issues, refresh this page to see updated status.</p>";
?>
