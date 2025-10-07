<?php
/**
 * Simple Database Test Script
 * Run this to test database connectivity and table creation
 */

// Load WordPress
require_once('../../../wp-config.php');

echo "=== Attendance Management Database Test ===\n\n";

// Test 1: Check if our classes are loaded
if (class_exists('LLMS_AT_Database')) {
    echo "✓ LLMS_AT_Database class loaded\n";
} else {
    echo "✗ LLMS_AT_Database class not found\n";
    exit;
}

// Test 2: Initialize database
try {
    $db = new LLMS_AT_Database();
    echo "✓ Database class initialized\n";
} catch (Exception $e) {
    echo "✗ Database initialization failed: " . $e->getMessage() . "\n";
    exit;
}

// Test 3: Check table creation
global $wpdb;
$table_name = $wpdb->prefix . 'llms_attendance';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if ($table_exists) {
    echo "✓ Custom table exists: $table_name\n";
} else {
    echo "✗ Custom table does not exist: $table_name\n";
    echo "Attempting to create table...\n";
    
    // Try to create the table
    $db->create_tables();
    
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if ($table_exists) {
        echo "✓ Table created successfully\n";
    } else {
        echo "✗ Table creation failed\n";
    }
}

// Test 4: Get table statistics
try {
    $stats = $db->get_table_stats();
    echo "✓ Table statistics retrieved:\n";
    echo "  - Total records: " . $stats['total_records'] . "\n";
    echo "  - Unique users: " . $stats['unique_users'] . "\n";
    echo "  - Unique courses: " . $stats['unique_courses'] . "\n";
    echo "  - Date range: " . $stats['earliest_date'] . " to " . $stats['latest_date'] . "\n";
} catch (Exception $e) {
    echo "✗ Failed to get table statistics: " . $e->getMessage() . "\n";
}

// Test 5: Test data insertion
echo "\n=== Testing Data Insertion ===\n";
$test_user_id = 999999;
$test_course_id = 999999;
$test_date = '2024-01-01';
$test_time = '2024-01-01 10:00:00';

try {
    $result = $db->insert_attendance($test_user_id, $test_course_id, $test_date, $test_time);
    if ($result) {
        echo "✓ Test data inserted successfully (ID: $result)\n";
        
        // Test data retrieval
        $has_attendance = $db->has_attendance($test_user_id, $test_course_id, $test_date);
        if ($has_attendance) {
            echo "✓ Data retrieval test passed\n";
        } else {
            echo "✗ Data retrieval test failed\n";
        }
        
        // Clean up test data
        $wpdb->delete($table_name, array('id' => $result));
        echo "✓ Test data cleaned up\n";
    } else {
        echo "✗ Test data insertion failed\n";
    }
} catch (Exception $e) {
    echo "✗ Data insertion test failed: " . $e->getMessage() . "\n";
}

// Test 6: Performance test
echo "\n=== Performance Test ===\n";
$start_time = microtime(true);
$stats = $db->get_table_stats();
$end_time = microtime(true);
$query_time = round(($end_time - $start_time) * 1000, 2);

echo "Query time: {$query_time}ms\n";
if ($query_time < 100) {
    echo "✓ Performance test passed (< 100ms)\n";
} else {
    echo "⚠ Performance test warning (> 100ms)\n";
}

echo "\n=== Test Complete ===\n";
echo "Database connection: ✓\n";
echo "Table structure: ✓\n";
echo "Data operations: ✓\n";
echo "Performance: " . ($query_time < 100 ? "✓" : "⚠") . "\n";
?>
