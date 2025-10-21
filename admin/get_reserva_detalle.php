<?php
/**
 * Obtener Detalle de Reserva - Panel de Administración
 * My Suite In Cartagena
 */

session_start();

// Verificar si el usuario está logueado como admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $id_reserva = $_GET['id'] ?? 0;
    
    if (!$id_reserva || !is_numeric($id_reserva)) {
        throw new Exception('ID de reserva inválido');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    // Obtener detalles completos de la reserva
    $query = "
        SELECT 
            r.*,
            a.nombre as apartamento_nombre,
            u.nombre as usuario_nombre,
            u.apellido as usuario_apellido
        FROM reservas r
        LEFT JOIN apartamentos a ON r.id_apartamento = a.id_apartamento
        LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
        WHERE r.id_reserva = ?
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$id_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserva) {
        throw new Exception('Reserva no encontrada');
    }
    
    // Formatear fechas
    $reserva['fecha_entrada_formatted'] = date('d/m/Y', strtotime($reserva['fecha_entrada']));
    $reserva['fecha_salida_formatted'] = date('d/m/Y', strtotime($reserva['fecha_salida']));
    $reserva['creado_en_formatted'] = date('d/m/Y H:i', strtotime($reserva['creado_en']));
    $reserva['actualizado_en_formatted'] = date('d/m/Y H:i', strtotime($reserva['actualizado_en']));
    
    // Calcular número de noches
    $fecha_entrada = new DateTime($reserva['fecha_entrada']);
    $fecha_salida = new DateTime($reserva['fecha_salida']);
    $reserva['num_noches'] = $fecha_entrada->diff($fecha_salida)->days;
    
    echo json_encode([
        'success' => true,
        'reserva' => $reserva
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
