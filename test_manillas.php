<?php
/**
 * Script de prueba para verificar tabla de manillas
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Verificar que la tabla exista
    echo "📋 Verificando tabla manillas_tarifas...\n";
    $query = "SHOW TABLES LIKE 'manillas_tarifas'";
    $result = $db->query($query)->fetchAll();
    
    if (!empty($result)) {
        echo "✅ Tabla manillas_tarifas existe\n\n";
    } else {
        echo "❌ Tabla manillas_tarifas NO existe\n";
        exit(1);
    }
    
    // Mostrar contenido de la tabla
    echo "📊 Datos en manillas_tarifas:\n";
    $query = "SELECT * FROM manillas_tarifas WHERE activo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    foreach ($rows as $row) {
        echo "  - De {$row['personas_desde']} a " . ($row['personas_hasta'] ?? 'sin límite') . " personas: \${$row['precio']}\n";
    }
    
    // Prueba el query de búsqueda
    echo "\n🔍 Prueba con 4 personas:\n";
    $query = "SELECT precio FROM manillas_tarifas 
              WHERE personas_desde <= 4
              AND (personas_hasta >= 4 OR personas_hasta IS NULL)
              AND activo = 1
              ORDER BY personas_desde DESC
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([]);
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ Precio encontrado: \${$result['precio']}\n";
    } else {
        echo "❌ No se encontró precio\n";
    }
    
    // Prueba con 8 personas
    echo "\n🔍 Prueba con 8 personas:\n";
    $query = "SELECT precio FROM manillas_tarifas 
              WHERE personas_desde <= 8
              AND (personas_hasta >= 8 OR personas_hasta IS NULL)
              AND activo = 1
              ORDER BY personas_desde DESC
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([]);
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ Precio encontrado: \${$result['precio']}\n";
    } else {
        echo "❌ No se encontró precio\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

