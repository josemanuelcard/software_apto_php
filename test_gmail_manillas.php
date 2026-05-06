<?php
/**
 * Test directo de getPrecioManillas desde GmailSender
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/GmailSender.php';

try {
    $sender = new GmailSender();
    
    echo "🧪 Prueba método getPrecioManillas desde GmailSender (usando reflexión)...\n\n";
    
    // Usar reflexión para acceder al método privado (solo para testing)
    $reflection = new ReflectionClass('GmailSender');
    $method = $reflection->getMethod('getPrecioManillas');
    $method->setAccessible(true);
    
    // Test con 4 personas (rango 1-7)
    $precio_4 = $method->invoke($sender, 4);
    echo "✅ 4 personas: \${$precio_4}\n";
    
    // Test con 8 personas (rango 8+)
    $precio_8 = $method->invoke($sender, 8);
    echo "✅ 8 personas: \${$precio_8}\n";
    
    // Test con 1 persona
    $precio_1 = $method->invoke($sender, 1);
    echo "✅ 1 persona: \${$precio_1}\n";
    
    // Test con 15 personas
    $precio_15 = $method->invoke($sender, 15);
    echo "✅ 15 personas: \${$precio_15}\n";
    
    if ($precio_4 > 0 && $precio_8 > 0) {
        echo "\n✅ getPrecioManillas está funcionando correctamente\n";
    } else {
        echo "\n❌ getPrecioManillas devolvió 0\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>

