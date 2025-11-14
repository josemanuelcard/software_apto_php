<?php
/**
 * Marcar Reserva como Pagada sin Comprobante
 * Sistema de Reservas - My Suite In Cartagena
 */

session_start();

// Verificar si el usuario está logueado como admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/GmailSender.php';

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
        
        // Obtener el estado actual de la reserva
        $query_estado = "SELECT estado, estado_pago FROM reservas WHERE id_reserva = ?";
        $stmt_estado = $db->prepare($query_estado);
        $stmt_estado->execute([$reserva_id]);
        $reserva_actual = $stmt_estado->fetch(PDO::FETCH_ASSOC);
        
        if (!$reserva_actual) {
            echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
            exit;
        }
        
        // Normalizar estados para comparación
        $estado_actual = strtolower(trim($reserva_actual['estado'] ?? ''));
        $estado_pago_actual = strtolower(trim($reserva_actual['estado_pago'] ?? ''));
        
        // Log para debugging
        error_log("Marcar pagada - Reserva ID: $reserva_id, Estado: {$reserva_actual['estado']}, Estado Pago: {$reserva_actual['estado_pago']}");
        error_log("Normalizado - Estado: $estado_actual, Estado Pago: $estado_pago_actual");
        
        // Lógica del flujo:
        // - Si está aprobada → cambiar a abonada
        // - Si está abonada → cambiar a aprobada y marcar como pagada
        if ($estado_actual === 'aprobada' && $estado_pago_actual === 'pendiente') {
            // Aprobada → Abonada
            $query = "UPDATE reservas SET 
                        estado = 'abonada',
                        comentario = CONCAT(IFNULL(comentario, ''), '\n--- MARCADA COMO ABONADA ---\n', ?)
                      WHERE id_reserva = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $notas ? "Notas: " . $notas : "Marcada como abonada",
                $reserva_id
            ]);
        } elseif ($estado_actual === 'abonada') {
            // Abonada → Pagada (cambiar a aprobada y marcar como pagada)
            $query = "UPDATE reservas SET 
                        estado = 'aprobada',
                        estado_pago = 'pagada',
                        fecha_pago_confirmado = NOW(),
                        comentario = CONCAT(IFNULL(comentario, ''), '\n--- PAGO CONFIRMADO ---\n', ?)
                      WHERE id_reserva = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $notas ? "Notas del pago: " . $notas : "Pago confirmado por el administrador",
                $reserva_id
            ]);
        } else {
            // Para otros casos, solo marcar como pagada (compatibilidad)
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
        }
        
        if ($stmt->rowCount() > 0) {
            // Determinar el mensaje según el cambio realizado
            if ($estado_actual === 'aprobada' && $estado_pago_actual === 'pendiente') {
                // Se cambió de aprobada a abonada - enviar correo de abono
                // Obtener datos de la reserva para enviar el correo
                $query_reserva = "SELECT r.*, 
                                COALESCE(u.nombre, r.nombre) as nombre, 
                                COALESCE(u.apellido, r.apellido) as apellido,
                                COALESCE(u.correo, r.correo) as correo
                                FROM reservas r 
                                LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario 
                                WHERE r.id_reserva = ?";
                $stmt_reserva = $db->prepare($query_reserva);
                $stmt_reserva->execute([$reserva_id]);
                $reserva = $stmt_reserva->fetch(PDO::FETCH_ASSOC);
                
                // Usar el correo de la reserva (r.correo) que siempre existe
                $correo_cliente = !empty($reserva['correo']) ? $reserva['correo'] : null;
                
                if ($reserva && $correo_cliente && filter_var($correo_cliente, FILTER_VALIDATE_EMAIL)) {
                    // Enviar correo de abono
                    try {
                        $gmail = new GmailSender();
                        
                        // Obtener ruta de la imagen del hotel de forma robusta
                        $hotel_image_path = $gmail->getHotelImagePath();
                        
                        $asunto = "💰 Abono Recibido - My Suite In Cartagena #" . $reserva_id;
                        
                        // Calcular fechas y montos
                        $fecha_entrada = date('d/m/Y', strtotime($reserva['fecha_entrada']));
                        $fecha_salida = date('d/m/Y', strtotime($reserva['fecha_salida']));
                        $total = (float)$reserva['total'];
                        $abono_20 = $total * 0.20;
                        $saldo_pendiente = $total * 0.80;
                        
                        $total_formateado = number_format($total, 0, ',', '.');
                        $abono_formateado = number_format($abono_20, 0, ',', '.');
                        $saldo_formateado = number_format($saldo_pendiente, 0, ',', '.');
                        
                        $mensaje = "
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset='UTF-8'>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                                .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
                                .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 30px 20px; text-align: center; }
                                .content { padding: 30px; background: #f8f9fa; }
                                .success-box { background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #28a745; }
                                .info-box { background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #ffc107; }
                                .details-box { background: white; padding: 20px; border-radius: 10px; margin: 20px 0; border: 1px solid #ddd; }
                                .highlight { color: #2a5298; font-weight: bold; }
                                .amount { font-size: 18px; font-weight: bold; color: #28a745; }
                                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
                                h1, h2, h3 { margin: 0 0 15px 0; }
                                p { margin: 10px 0; }
                                .field-label { font-weight: bold; color: #555; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h1 style='text-align: center; margin: 0 0 15px 0; line-height: 50px;'>
                                        <img src=\"cid:hotel_logo\" alt=\"My Suite In Cartagena\" style=\"width: 50px; height: 50px; vertical-align: middle; margin-right: 10px; display: inline-block;\" />
                                        <span style=\"vertical-align: middle; display: inline-block;\">My Suite In Cartagena</span>
                                    </h1>
                                    <h2>💰 Abono Recibido</h2>
                                </div>
                                <div class='content'>
                                    <div class='success-box'>
                                        <h3>✅ ¡Hemos recibido tu abono exitosamente!</h3>
                                        <p>Tu reserva está oficialmente confirmada.</p>
                                    </div>
                                    
                                    <p>Hola <strong>" . htmlspecialchars(($reserva['nombre'] ?? '') . ' ' . ($reserva['apellido'] ?? '')) . "</strong>,</p>
                                    
                                    <div class='details-box'>
                                        <h3>📋 Detalles de tu Reserva:</h3>
                                        <p><span class='field-label'>Fecha de entrada:</span> <strong>$fecha_entrada</strong></p>
                                        <p style='margin-left: 20px; color: #666;'>Check in: 3 p.m.</p>
                                        
                                        <p><span class='field-label'>Fecha de salida:</span> <strong>$fecha_salida</strong></p>
                                        <p style='margin-left: 20px; color: #666;'>Check out: 11 a.m.</p>
                                    </div>
                                    
                                    <div class='info-box'>
                                        <h3>💵 Información de Pago:</h3>
                                        <p><span class='field-label'>Total de la reserva:</span> <span class='amount'>$$total_formateado COP</span></p>
                                        <p><span class='field-label'>Abono recibido (20%):</span> <span class='amount'>$$abono_formateado COP</span></p>
                                        <p><span class='field-label'>Saldo pendiente por pagar (80%):</span> <span class='amount'>$$saldo_formateado COP</span></p>
                                    </div>
                                    
                                    
                                    <div class='info-box'>
                                        <h3>📧 Instrucciones para el Pago del Saldo:</h3>
                                        <p>El saldo restante se cancela el día anterior al check-in, ya sea en <strong>efectivo</strong> o en <strong>tarjeta</strong>, de forma personal.</p>
                                    </div>
                                    
                                    <div class='details-box'>
                                        <h3>📞 Información de Contacto:</h3>
                                        <p><strong>📧 Email:</strong> gerencia@mysuiteincartagena.com.co</p>
                                        <p><strong>📱 WhatsApp:</strong> +57 3105495149</p>
                                    </div>
                                    
                                    <p>¡Esperamos darte la bienvenida pronto a My Suite In Cartagena!</p>
                                </div>
                                <div class='footer'>
                                    <p>Saludos cordiales,<br>
                                    <strong>Equipo My Suite In Cartagena</strong></p>
                                    <p>Este es un email automático, por favor no responder a esta dirección.</p>
                                </div>
                            </div>
                        </body>
                        </html>";
                        
                        $gmail->sendEmail(
                            $correo_cliente,
                            $asunto,
                            $mensaje,
                            true,
                            $hotel_image_path
                        );
                        
                        error_log("Email de abono enviado a: " . $correo_cliente . " para reserva #" . $reserva_id);
                        $mensaje_respuesta = 'Reserva marcada como ABONADA exitosamente y correo de abono enviado';
                        
                    } catch (Exception $e) {
                        // Log del error pero no fallar la operación
                        error_log("Error enviando correo de abono: " . $e->getMessage());
                        $mensaje_respuesta = 'Reserva marcada como ABONADA exitosamente (error al enviar correo)';
                    }
                } else {
                    $mensaje_respuesta = 'Reserva marcada como ABONADA exitosamente (correo no disponible)';
                }
                
                error_log("Reserva $reserva_id cambiada de aprobada a abonada");
            } elseif ($estado_actual === 'abonada') {
                // Se cambió de abonada a pagada - enviar correo
                // Obtener datos de la reserva para enviar el correo
                $query_reserva = "SELECT r.*, 
                                COALESCE(u.nombre, r.nombre) as nombre, 
                                COALESCE(u.apellido, r.apellido) as apellido,
                                COALESCE(u.correo, r.correo) as correo
                                FROM reservas r 
                                LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario 
                                WHERE r.id_reserva = ?";
                $stmt_reserva = $db->prepare($query_reserva);
                $stmt_reserva->execute([$reserva_id]);
                $reserva = $stmt_reserva->fetch(PDO::FETCH_ASSOC);
                
                // Usar el correo de la reserva (r.correo) que siempre existe
                $correo_cliente = !empty($reserva['correo']) ? $reserva['correo'] : null;
                
                // Log para debugging
                error_log("Intentando enviar correo de confirmación de pago. Reserva ID: " . $reserva_id);
                error_log("Correo cliente: " . ($correo_cliente ?? 'NO ENCONTRADO'));
                
                if ($reserva && $correo_cliente && filter_var($correo_cliente, FILTER_VALIDATE_EMAIL)) {
                    // Enviar correo de confirmación de pago
                    try {
                        $gmail = new GmailSender();
                        
                        // Obtener ruta de la imagen del hotel de forma robusta
                        $hotel_image_path = $gmail->getHotelImagePath();
                        
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
                                    <h1 style='text-align: center; margin: 0 0 15px 0; line-height: 50px;'>
                                        <img src=\"cid:hotel_logo\" alt=\"My Suite In Cartagena\" style=\"width: 50px; height: 50px; vertical-align: middle; margin-right: 10px; display: inline-block;\" />
                                        <span style=\"vertical-align: middle; display: inline-block;\">My Suite In Cartagena</span>
                                    </h1>
                                    <h2>🎉 ¡Pago Confirmado!</h2>
                                </div>
                                <div class='content'>
                                    <div class='success-box'>
                                        <h3>✅ ¡Excelente noticia!</h3>
                                        <p>Hemos recibido y confirmado el pago completo de tu reserva. Tu reserva está oficialmente confirmada y pagada al 100%.</p>
                                    </div>
                                    
                                    <p>Hola <strong>" . htmlspecialchars(($reserva['nombre'] ?? '') . ' ' . ($reserva['apellido'] ?? '')) . "</strong>,</p>
                                    
                                    <p>Nos complace informarte que hemos recibido y confirmado el <strong>pago completo</strong> de tu reserva. Ya has cancelado el <strong>100% del total</strong> y tu reserva está oficialmente confirmada y lista para tu llegada.</p>
                                    
                                    <div class='reservation-details'>
                                        <h3>📋 Detalles de tu Reserva:</h3>
                                        <p><strong>ID de Reserva:</strong> #" . $reserva['id_reserva'] . "</p>
                                        <p><strong>Fecha de Entrada:</strong> " . date('d/m/Y', strtotime($reserva['fecha_entrada'])) . "</p>
                                        <p style='margin-left: 20px; color: #666;'>Check in: 3 p.m.</p>
                                        <p><strong>Fecha de Salida:</strong> " . date('d/m/Y', strtotime($reserva['fecha_salida'])) . "</p>
                                        <p style='margin-left: 20px; color: #666;'>Check out: 11 a.m.</p>
                                        <p><strong>Número de Adultos:</strong> " . $reserva['num_adultos'] . "</p>
                                        <p><strong>Número de Niños:</strong> " . $reserva['num_ninos'] . "</p>
                                    </div>
                                    
                                    <div class='success-box' style='background: #d1ecf1; border-color: #bee5eb; color: #0c5460;'>
                                        <h3>💵 Estado de Pago:</h3>
                                        <p><strong>Total de la Reserva:</strong> $" . number_format($reserva['total'], 0, ',', '.') . " COP</p>
                                        <p style='font-size: 18px; margin-top: 10px;'><strong>✅ Total Pagado (100%):</strong> <span class='highlight' style='color: #28a745; font-size: 20px;'>$" . number_format($reserva['total'], 0, ',', '.') . " COP</span></p>
                                        <p style='margin-top: 10px;'><strong>🎉 ¡Tu reserva está completamente pagada!</strong></p>
                                    </div>
                                    
                                    <div class='reservation-details'>
                                        <h3>📞 Información de Contacto:</h3>
                                        <p><strong>📧 Email:</strong> gerencia@mysuiteincartagena.com.co</p>
                                        <p><strong>📱 WhatsApp:</strong> +57 3105495149</p>
                                    </div>
                                    
                                    <h3>🏖️ ¡Te esperamos en Cartagena!</h3>
                                    <p>Estamos emocionados de recibirte en My Suite In Cartagena. Tu reserva está completamente confirmada y pagada. Si tienes alguna pregunta o necesitas información adicional, no dudes en contactarnos.</p>
                                    
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
                            $correo_cliente,
                            $asunto,
                            $mensaje,
                            true,
                            $hotel_image_path
                        );
                        
                        error_log("Email de confirmación de pago enviado a: " . $correo_cliente . " para reserva #" . $reserva_id);
                        $mensaje_respuesta = 'Reserva marcada como PAGADA exitosamente y correo de confirmación enviado';
                        
                    } catch (Exception $e) {
                        // Log del error pero no fallar la operación
                        error_log("Error enviando correo de confirmación de pago: " . $e->getMessage());
                        $mensaje_respuesta = 'Reserva marcada como PAGADA exitosamente (error al enviar correo)';
                    }
                } else {
                    $mensaje_respuesta = 'Reserva marcada como PAGADA exitosamente (correo no disponible)';
                }
            } else {
                $mensaje_respuesta = 'Reserva actualizada exitosamente';
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $mensaje_respuesta
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
