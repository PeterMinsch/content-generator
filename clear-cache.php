<?php
/**
 * Clear PHP OpCode Cache & Check Syntax
 */

header('Content-Type: text/plain');

echo "=== PHP CACHE & SYNTAX CHECK ===\n\n";

// Clear opcode caches
if ( function_exists( 'opcache_reset' ) ) {
	opcache_reset();
	echo "✅ OPcache cleared\n";
} else {
	echo "⚠️ OPcache not available\n";
}

if ( function_exists( 'apc_clear_cache' ) ) {
	apc_clear_cache();
	echo "✅ APC cache cleared\n";
}

echo "\n";

// Check syntax of DefaultPrompts.php
$file = __DIR__ . '/includes/Data/DefaultPrompts.php';
echo "Checking syntax of: $file\n\n";

$output = array();
$return_var = 0;

// Use php -l to check syntax
exec( "php -l " . escapeshellarg( $file ) . " 2>&1", $output, $return_var );

if ( $return_var === 0 ) {
	echo "✅ SYNTAX OK: " . implode( "\n", $output ) . "\n";
} else {
	echo "❌ SYNTAX ERROR:\n";
	echo implode( "\n", $output ) . "\n";
}

echo "\n=== Done ===\n";
echo "Now try trigger-cron.php again\n";
