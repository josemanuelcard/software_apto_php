<?php
/**
 * Marcar Reserva como Pagada sin Comprobante
 * Sistema de Reservas - My Suite Cartagena
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reserva_id = $_POST['reserva_id'] ?? '';
    $notas = $_POST['notas'] ?? '';
    $marcar_pagada = $_POST['marcar_pagada'] ?? '';
    
    if (empty($reserva_id)) {
        echo json_encode(['success' => false, 'message' => 'ID de reserva no proporcionado']);
        exit;
    }
    
    if ($marcar_pagada !== '1') {
        echo json_encode(['success' => false, 'message' => 'Parámetro inválido']);
        exit;
    }
    
    try {
        $db = (new Database())->getConnection();
        
        // Actualizar la reserva como pagada
        $query = "UPDATE reservas SET 
                    estado_pago = 'pagada',
                    fecha_pago_confirmado = NOW(),
                    comentario = CONCAT(IFNULL(comentario, ''), '\n--- PAGO CONFIRMADO ---\n', ?)
                  WHERE id_reserva = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $notas ? "Notas del pago: " . $notas : "Pago confirmado por el administrador",
            $reserva_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Reserva marcada como PAGADA exitosamente'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'No se pudo actualizar la reserva'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error de base de datos: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
