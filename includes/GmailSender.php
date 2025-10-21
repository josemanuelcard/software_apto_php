<?php
/**
 * Enviador de Gmail - Sistema de Reservas
 * My Suite In Cartagena
 * Usa PHPMailer para Gmail SMTP
 */

// Incluir PHPMailer
require_once '../vendor/autoload.php';

class GmailSender {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        // Configuración Gmail SMTP
        $this->smtp_host = 'smtp.gmail.com';
        $this->smtp_port = 587;
        $this->smtp_username = 'jose.cardenas01@uceva.edu.co';
        $this->smtp_password = 'shbu swnk qvtx inle'; // Tu App Password
        $this->from_email = 'jose.cardenas01@uceva.edu.co';
        $this->from_name = 'My Suite Cartagena';
    }
    
    /**
     * Enviar email usando Gmail SMTP
     */
    public function sendEmail($to, $subject, $message, $is_html = true) {
        try {
            // Usar PHPMailer siempre (ya está instalado)
            return $this->sendWithPHPMailer($to, $subject, $message, $is_html);
            
        } catch (Exception $e) {
            error_log("Error en GmailSender: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envío con PHPMailer (recomendado)
     */
    private function sendWithPHPMailer($to, $subject, $message, $is_html) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configuración SMTP para Gmail
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            $mail->CharSet = 'UTF-8';
            
            // Configuraciones adicionales para Gmail
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Remitente y destinatario
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to);
            $mail->addReplyTo($this->from_email, $this->from_name);
            
            // Contenido
            $mail->isHTML($is_html);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            // Debug (temporal)
            $mail->SMTPDebug = 0; // Cambiar a 2 para ver debug completo
            
            $result = $mail->send();
            
            if ($result) {
                error_log("✅ Email enviado exitosamente a: $to");
                return true;
            } else {
                error_log("❌ Error enviando email a: $to");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("❌ Error PHPMailer: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envío básico (fallback)
     */
    private function sendWithBasic($to, $subject, $message, $is_html) {
        // Configurar PHP para Gmail
        ini_set('SMTP', $this->smtp_host);
        ini_set('smtp_port', $this->smtp_port);
        ini_set('sendmail_from', $this->from_email);
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: ' . ($is_html ? 'text/html' : 'text/plain') . '; charset=UTF-8',
            'From: ' . $this->from_name . ' <' . $this->from_email . '>',
            'Reply-To: ' . $this->from_email,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $result = mail($to, $subject, $message, implode("\r\n", $headers));
        
        if ($result) {
            error_log("Email básico enviado a: $to");
        } else {
            error_log("Error en email básico a: $to");
        }
        
        return $result;
    }
    
    /**
     * Enviar email de aprobación de reserva
     */
    public function sendReservaAprobada($reserva) {
        $subject = "✅ Reserva Aprobada - My Suite Cartagena #" . $reserva['id_reserva'];
        
        $fecha_entrada = date('d/m/Y', strtotime($reserva['fecha_entrada']));
        $fecha_salida = date('d/m/Y', strtotime($reserva['fecha_salida']));
        $total = number_format($reserva['total'], 0, ',', '.');
        
        $message = $this->getEmailTemplate($reserva, $fecha_entrada, $fecha_salida, $total);
        
        return $this->sendEmail($reserva['correo'], $subject, $message, true);
    }
    
    /**
     * Template HTML para email de aprobación
     */
    private function getEmailTemplate($reserva, $fecha_entrada, $fecha_salida, $total) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
                .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 30px 20px; text-align: center; }
                .content { padding: 30px; background: #f8f9fa; }
                .highlight { background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #2196f3; }
                .total { background: #d4edda; padding: 20px; border-radius: 10px; text-align: center; font-size: 20px; font-weight: bold; color: #155724; margin: 20px 0; }
                .instructions { background: #fff3cd; padding: 20px; border-radius: 10px; border-left: 4px solid #ffc107; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
                h1, h2, h3 { margin: 0 0 15px 0; }
                p { margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏖️ My Suite Cartagena</h1>
                    <h2>¡Reserva Aprobada!</h2>
                </div>
                <div class='content'>
                    <p>Hola <strong>{$reserva['nombre']} {$reserva['apellido']}</strong>,</p>
                    
                    <p>¡Excelentes noticias! Tu reserva ha sido <strong>APROBADA</strong> y está lista para el pago.</p>
                    
                    <div class='highlight'>
                        <h3>📋 Detalles de tu Reserva:</h3>
                        <p><strong>ID Reserva:</strong> #{$reserva['id_reserva']}</p>
                        <p><strong>Fecha de Entrada:</strong> {$fecha_entrada}</p>
                        <p><strong>Fecha de Salida:</strong> {$fecha_salida}</p>
                        <p><strong>Huéspedes:</strong> {$reserva['num_adultos']} adultos, {$reserva['num_ninos']} niños</p>
                        <p><strong>Método de Pago:</strong> " . ($reserva['metodo_pago'] === 'efectivo' ? 'Efectivo' : 'Tarjeta de Crédito') . "</p>
                    </div>
                    
                    <div class='total'>
                        💰 Total a Pagar: $${total} COP
                    </div>
                    
                    <div class='instructions'>
                        <h3>💳 INSTRUCCIONES DE PAGO:</h3>
                        <p><strong>1.</strong> Realiza el pago por el monto total indicado</p>
                        <p><strong>2.</strong> Toma una foto o escanea el comprobante de pago</p>
                        <p><strong>3.</strong> <strong>IMPORTANTE:</strong> Envía el comprobante al correo: <strong>jose.cardenas01@uceva.edu.co</strong></p>
                        <p><strong>4.</strong> Una vez confirmado el pago, recibirás la confirmación final</p>
                        <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 15px; border-left: 4px solid #ffc107;'>
                            <strong>⚠️ RECORDATORIO:</strong> Sin el comprobante de pago, tu reserva no será confirmada.
                        </div>
                    </div>
                    
                    <p>¡Esperamos darte la bienvenida pronto a My Suite Cartagena!</p>
                </div>
                <div class='footer'>
                    <p>Saludos cordiales,<br>
                    <strong>Equipo My Suite Cartagena</strong></p>
                    <p>Este es un email automático, por favor no responder a esta dirección.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>
