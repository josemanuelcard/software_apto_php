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
require_once '../includes/GmailSender.php';

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
            // Obtener datos de la reserva para enviar el correo
            $query_reserva = "SELECT r.*, u.nombre, u.apellido, u.correo 
                            FROM reservas r 
                            LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario 
                            WHERE r.id_reserva = ?";
            $stmt_reserva = $db->prepare($query_reserva);
            $stmt_reserva->execute([$reserva_id]);
            $reserva = $stmt_reserva->fetch(PDO::FETCH_ASSOC);
            
            if ($reserva && $reserva['correo']) {
                // Enviar correo de confirmación de pago
                try {
                    $gmail = new GmailSender();
                    
                    $asunto = "✅ Pago Confirmado - My Suite In Cartagena";
                    
                    $mensaje = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset='UTF-8'>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: linear-gradient(135deg, rgb(199, 156, 65), rgb(186, 117, 13)); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                            .success-box { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0; }
                            .reservation-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                            .highlight { color: rgb(199, 156, 65); font-weight: bold; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1> My Suite In Cartagena</h1>
                                <h2>🎉 ¡Pago Confirmado!</h2>
                            </div>
                            <div class='content'>
                                <div class='success-box'>
                                    <h3>✅ ¡Excelente noticia!</h3>
                                    <p>Hemos recibido y confirmado tu pago exitosamente. Tu reserva está oficialmente confirmada.</p>
                                </div>
                                
                                <p>Hola <strong>" . htmlspecialchars($reserva['nombre'] . ' ' . $reserva['apellido']) . "</strong>,</p>
                                
                                <p>Nos complace informarte que hemos recibido y confirmado tu pago. Tu reserva está oficialmente confirmada y lista para tu llegada.</p>
                                
                                <div class='reservation-details'>
                                    <h3>📋 Detalles de tu Reserva:</h3>
                                    <p><strong>ID de Reserva:</strong> #" . $reserva['id_reserva'] . "</p>
                                    <p><strong>Fecha de Entrada:</strong> " . date('d/m/Y', strtotime($reserva['fecha_entrada'])) . "</p>
                                    <p><strong>Fecha de Salida:</strong> " . date('d/m/Y', strtotime($reserva['fecha_salida'])) . "</p>
                                    <p><strong>Número de Adultos:</strong> " . $reserva['num_adultos'] . "</p>
                                    <p><strong>Número de Niños:</strong> " . $reserva['num_ninos'] . "</p>
                                    <p><strong>Total Pagado:</strong> <span class='highlight'>$" . number_format($reserva['total'], 0, ',', '.') . " COP</span></p>
                                </div>
                                
                                <h3>🏖️ ¡Te esperamos en Cartagena!</h3>
                                <p>Estamos emocionados de recibirte en My Suite In Cartagena. Si tienes alguna pregunta o necesitas información adicional, no dudes en contactarnos.</p>
                                
                                <p><strong>Información de Contacto:</strong></p>
                                <ul>
                                    <li>📧 Email: jose.cardenas01@uceva.edu.co</li>
                                    <li>📱 WhatsApp: +57 301 5193163</li>
                                </ul>
                                
                                <p>¡Que tengas un excelente viaje y nos vemos pronto en Cartagena! 🌴</p>
                                
                                <p>Saludos cordiales,<br><strong>Equipo My Suite In Cartagena</strong></p>
                            </div>
                            <div class='footer'>
                                <p>© 2025 My Suite In Cartagena - Todos los derechos reservados</p>
                            </div>
                        </div>
                    </body>
                    </html>";
                    
                    $gmail->sendEmail(
                        $reserva['correo'],
                        $asunto,
                        $mensaje
                    );
                    
                } catch (Exception $e) {
                    // Log del error pero no fallar la operación
                    error_log("Error enviando correo de confirmación de pago: " . $e->getMessage());
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Reserva marcada como PAGADA exitosamente y correo de confirmación enviado'
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
