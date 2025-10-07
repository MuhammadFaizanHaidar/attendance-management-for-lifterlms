<?php
/**
 * Test Page for Attendance Management Plugin
 * Access this through WordPress Admin → Courses → Performance Testing
 */

// This will be handled by the testing framework
// Let's create a simple test to verify everything is working

echo '<h1>🧪 Attendance Management Test Results</h1>';

// Test 1: Check if classes are loaded
echo '<h2>1. Class Loading Test</h2>';
if ( class_exists( 'LLMS_AT_Database' ) ) {
	echo '✅ LLMS_AT_Database class loaded<br>';
} else {
	echo '❌ LLMS_AT_Database class not found<br>';
}

if ( class_exists( 'LLMS_AT_Testing_Framework' ) ) {
	echo '✅ LLMS_AT_Testing_Framework class loaded<br>';
} else {
	echo '❌ LLMS_AT_Testing_Framework class not found<br>';
}

if ( class_exists( 'LLMS_AT_Test_Suite' ) ) {
	echo '✅ LLMS_AT_Test_Suite class loaded<br>';
} else {
	echo '❌ LLMS_AT_Test_Suite class not found<br>';
}

// Test 2: Database connection
echo '<h2>2. Database Connection Test</h2>';
global $wpdb;
if ( $wpdb->db_connect() ) {
	echo '✅ Database connection successful<br>';
} else {
	echo '❌ Database connection failed<br>';
}

// Test 3: Table existence
echo '<h2>3. Custom Table Test</h2>';
$table_name   = $wpdb->prefix . 'llms_attendance';
$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );

if ( $table_exists ) {
	echo "✅ Custom table exists: $table_name<br>";

	// Get table info
	$columns = $wpdb->get_results( "DESCRIBE $table_name" );
	echo '📊 Table has ' . count( $columns ) . ' columns<br>';

	// Get record count
	$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
	echo "📈 Total records: $count<br>";

} else {
	echo "❌ Custom table does not exist: $table_name<br>";
	echo '💡 Try accessing: WordPress Admin → Courses → Attendance Migration<br>';
}

// Test 4: Plugin options
echo '<h2>4. Plugin Configuration Test</h2>';
$reporting_enabled = get_option( 'llms_integration_reporting_enabled', 'no' );
echo '📊 Reporting enabled: ' . ( $reporting_enabled === 'yes' ? '✅ Yes' : '❌ No' ) . '<br>';

$migration_status = get_option( 'llmsat_migration_status', 'not_started' );
echo '🔄 Migration status: ' . ucfirst( $migration_status ) . '<br>';

$use_custom_table = get_option( 'llmsat_use_custom_table', false );
echo '🗄️ Custom table active: ' . ( $use_custom_table ? '✅ Yes' : '❌ No' ) . '<br>';

// Test 5: Performance test
echo '<h2>5. Performance Test</h2>';
if ( $table_exists ) {
	$start_time = microtime( true );
	$stats      = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
	$end_time   = microtime( true );
	$query_time = round( ( $end_time - $start_time ) * 1000, 2 );

	echo "⚡ Query time: {$query_time}ms<br>";
	if ( $query_time < 100 ) {
		echo '✅ Performance: Excellent (< 100ms)<br>';
	} elseif ( $query_time < 500 ) {
		echo '✅ Performance: Good (< 500ms)<br>';
	} else {
		echo '⚠️ Performance: Needs optimization (> 500ms)<br>';
	}
} else {
	echo "❌ Cannot run performance test - table doesn't exist<br>";
}

// Test 6: Next steps
echo '<h2>6. Next Steps</h2>';
if ( ! $table_exists ) {
	echo '🔧 <strong>Action Required:</strong><br>';
	echo '1. Go to WordPress Admin → Courses → Attendance Migration<br>';
	echo '2. Start the migration process<br>';
	echo '3. Run the tests again<br><br>';
}

echo '🧪 <strong>Run Full Tests:</strong><br>';
echo '1. Go to WordPress Admin → Courses → Performance Testing<br>';
echo '2. Generate test data (start with small dataset)<br>';
echo '3. Run performance tests<br>';
echo '4. Review results<br><br>';

echo '📚 <strong>CLI Testing (if available):</strong><br>';
echo 'Run: <code>wp llmsat test</code><br>';
echo 'Run: <code>wp llmsat performance</code><br><br>';

echo '<h2>✅ Test Summary</h2>';
echo 'Plugin Status: ' . ( class_exists( 'LLMS_AT_Database' ) ? '✅ Ready' : '❌ Not Ready' ) . '<br>';
echo 'Database: ' . ( $wpdb->db_connect() ? '✅ Connected' : '❌ Failed' ) . '<br>';
echo 'Custom Table: ' . ( $table_exists ? '✅ Created' : '❌ Missing' ) . '<br>';
echo 'Performance: ' . ( isset( $query_time ) && $query_time < 500 ? '✅ Good' : '⚠️ Check' ) . '<br>';

echo '<hr>';
echo '<p><strong>💡 Tip:</strong> If you see any ❌ marks, follow the suggested actions above.</p>';
echo '<p><strong>🎯 Goal:</strong> All tests should show ✅ for optimal performance.</p>';
