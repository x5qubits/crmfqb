<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/oblio_api.php';

header('Content-Type: text/plain');

try {
    $oblio = new OblioAPI($pdo);
    
    echo "=== Oblio Configuration Test ===\n\n";
    
    // Check if configured
    echo "Is Configured: " . ($oblio->isConfigured() ? "YES" : "NO") . "\n";
    
    // Get settings
    $settings = $oblio->getSettings();
    echo "\nSettings:\n";
    echo "- Email: " . ($settings['email'] ?: 'MISSING') . "\n";
    echo "- Company: " . ($settings['company'] ?: 'MISSING') . "\n";
    echo "- CIF: " . ($settings['cif'] ?: 'MISSING') . "\n";
    echo "- Configured: " . ($settings['configured'] ? 'YES' : 'NO') . "\n";
    
    if (!$oblio->isConfigured()) {
        echo "\n❌ Oblio is NOT configured. Please set credentials in bills.php\n";
        exit;
    }
    
    echo "\n=== Testing Oblio API Connection ===\n\n";
    
    // Test with reflection to access private client
    $reflection = new ReflectionClass($oblio);
    $clientProperty = $reflection->getProperty('client');
    $clientProperty->setAccessible(true);
    $client = $clientProperty->getValue($oblio);
    
    if (!$client) {
        echo "❌ Client not initialized\n";
        exit;
    }
    
    echo "✓ Client initialized\n";
    
    // Get settings property
    $settingsProperty = $reflection->getProperty('settings');
    $settingsProperty->setAccessible(true);
    $internalSettings = $settingsProperty->getValue($oblio);
    
    echo "\nInternal Settings:\n";
    print_r($internalSettings);
    
    // Try to fetch invoices for current month
    echo "\n=== Testing Invoice Fetch ===\n";
    try {
        $invoices = $oblio->fetchAllInvoicesByYear(2024); 
        echo "✓ Successfully fetched " . count($invoices) . " invoices\n";
        if (count($invoices) > 0) {
            echo "First invoice: " . json_encode($invoices[0]) . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Invoice fetch failed: " . $e->getMessage() . "\n";
    }
    
    // Try to fetch clients - THIS IS WHERE IT FAILS
    echo "\n=== Testing Client Sync ===\n";
    
    // Show exact parameters being sent
    echo "CIF being used: " . $internalSettings['cif'] . "\n";
    echo "Company being used: " . $internalSettings['company'] . "\n";
    
    try {
        $synced = $oblio->syncClientsFromOblio();
        echo "✓ Successfully synced $synced clients\n";
    } catch (Exception $e) {
        echo "❌ Client sync failed: " . $e->getMessage() . "\n";
        echo "\nError details:\n";
        echo $e->getTraceAsString() . "\n";
    }
	
    try {
$count = $oblio->syncFirmClientsFromInvoices(2024);
echo "x✓ Synced $count firms from invoices\n";
    } catch (Exception $e) {
        echo "❌ Client sync failed: " . $e->getMessage() . "\n";
        echo "\nError details:\n";
        echo $e->getTraceAsString() . "\n";
    }	
	

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>