<?php
/**
 * Test completo del flujo de email de aprobación
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/GmailSender.php';

try {
    $db = (new Database())->getConnection();
    
    // Obtener una reserva de ejemplo
    echo "📋 Buscando una reserva para probar...\n\n";
    $query = "SELECT * FROM reservas LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reserva = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if (!$reserva) {
        // Crear datos de ejemplo para la prueba
        echo "No hay reservas. Usando datos de ejemplo...\n";
        $reserva = [
            'id_reserva' => 'TEST-001',
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'correo' => 'test@example.com',
            'fecha_entrada' => '2026-05-10',
            'fecha_salida' => '2026-05-12',
            'total' => '1000000',
            'num_adultos' => 4,
            'num_ninos' => 0,
            'metodo_pago' => 'transferencia'
        ];
    }
    
    echo "✅ Usando reserva #" . $reserva['id_reserva'] . "\n";
    echo "   - Nombre: " . $reserva['nombre'] . " " . $reserva['apellido'] . "\n";
    echo "   - Adultos: " . $reserva['num_adultos'] . "\n";
    echo "   - Niños: " . $reserva['num_ninos'] . "\n";
    echo "   - Método pago: " . $reserva['metodo_pago'] . "\n\n";
    
    // Crear GmailSender
    $sender = new GmailSender();
    
    // Usar reflección para acceder a los métodos privados (para testing)
    $reflection = new ReflectionClass('GmailSender');
    
    // Test getPrecioManillas
    echo "🧪 Probando getPrecioManillas...\n";
    $method_manillas = $reflection->getMethod('getPrecioManillas');
    $method_manillas->setAccessible(true);
    
    $cantidad_personas = $reserva['num_adultos'] + $reserva['num_ninos'];
    $precio_manillas = $method_manillas->invoke($sender, $cantidad_personas);
    echo "✅ Precio de manillas para $cantidad_personas personas: \$$precio_manillas\n\n";
    
    // Test invocación del método de envío de email
    echo "🧪 Generando email de aprobación (sin enviarlo)...\n";
    
    // Simular lo que hace enviarEmailAprobacion
    $metodo_pago = strtolower(trim($reserva['metodo_pago'] ?? 'transferencia'));
    
    if ($metodo_pago === 'tarjeta' || $metodo_pago === 'card') {
        echo "   - Usando template Tarjeta 20%\n";
        $result = $sender->sendReservaAprobadaTarjeta20($reserva, false);
   } else {
        echo "   - Usando template Transferencia 20%\n";
        $result = $sender->sendReservaAprobadaTransferencia20($reserva, false);
    }
    
    if ($result) {
        echo "✅ Email generado y enviado exitosamente\n\n";
    } else {
        echo "❌ Error enviando email\n\n";
    }
    
    // Verificar archivo generado
    $logs_dir = __DIR__ . '/logs';
    $latest_file = null;
    $latest_time = 0;
    
    $files = glob($logs_dir . '/last_aprobada_reserva_*.html');
    foreach ($files as $file) {
        if (filemtime($file) > $latest_time) {
            $latest_time = filemtime($file);
            $latest_file = $file;
        }
    }
    
    if ($latest_file) {
        echo "📄 Último HTML generado: " . basename($latest_file) . "\n";
        
        // Buscar línea de manillas
        $content = file_get_contents($latest_file);
        if (preg_match('/cancelar el valor de[^)]*?\\$([0-9.,]+)[^)]*?manillas/i', $content, $matches)) {
            echo "💰 Valor de manillas en email: \$" . $matches[1] . "\n";
        } else {
            echo "❌ No se encontró valor de manillas en el email\n";
            echo "🔍 Buscando cualquier mención de manillas...\n";
            if (preg_match('/manillas/i', $content)) {
                // Extraer el contexto
                preg_match('/(.{0,100}manillas.{0,100})/i', $content, $ctx);
                if (!empty($ctx[1])) {
                    echo "   Contexto: ..." . trim($ctx[1]) . "...\n";
                }
            } else {
                echo "   No hay mención de manillas en el HTML\n";
            }
        }
    } else {
        echo "⚠️ No se encontraron archivos de log HTML\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>

