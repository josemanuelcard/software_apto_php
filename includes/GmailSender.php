<?php
/**
 * Enviador de Email - Sistema de Reservas
 * My Suite In Cartagena
 * Usa PHPMailer para SMTP (cPanel)
 */

// Verificar e incluir PHPMailer de forma segura
$vendor_autoload = __DIR__ . '/../vendor/autoload.php';
$GLOBALS['phpmailer_available'] = false;

if (file_exists($vendor_autoload)) {
    try {
        require_once $vendor_autoload;
        // Verificar que PHPMailer esté disponible
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $GLOBALS['phpmailer_available'] = true;
        } else {
            error_log("Advertencia: vendor/autoload.php existe pero PHPMailer no está disponible");
        }
    } catch (Exception $e) {
        error_log("Error al cargar vendor/autoload.php: " . $e->getMessage());
    } catch (Error $e) {
        error_log("Error fatal al cargar vendor/autoload.php: " . $e->getMessage());
    }
} else {
    error_log("Advertencia: vendor/autoload.php no encontrado en: $vendor_autoload");
}

class GmailSender {
    private static $phpmailer_available = null;
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;

    public function __construct() {
        // Configuración SMTP para cPanel
        $this->smtp_host = 'mail.mysuiteincartagena.com.co'; // Cambiar si tu proveedor usa otro host
        $this->smtp_port = 587; // Puerto 587 para TLS, o 465 para SSL
        $this->smtp_username = 'gerencia@mysuiteincartagena.com.co';
        $this->smtp_password = 'GereCar-2.025'; // Contraseña del correo
        $this->from_email = 'gerencia@mysuiteincartagena.com.co';
        $this->from_name = 'My Suite In Cartagena';
    }

    /**
     * Verificar si PHPMailer está disponible
     */
    private static function isPHPMailerAvailable() {
        if (self::$phpmailer_available === null) {
            self::$phpmailer_available = isset($GLOBALS['phpmailer_available']) && $GLOBALS['phpmailer_available'] === true;
        }
        return self::$phpmailer_available;
    }

    /**
     * Enviar email usando SMTP (cPanel)
     */
    public function sendEmail($to, $subject, $message, $is_html = true, $image_path = null) {
        try {
            // Intentar usar PHPMailer si está disponible
            if (self::isPHPMailerAvailable()) {
                return $this->sendWithPHPMailer($to, $subject, $message, $is_html, $image_path);
            } else {
                // Fallback a método básico si PHPMailer no está disponible
                error_log("PHPMailer no disponible, usando método básico para enviar email");
                return $this->sendWithBasic($to, $subject, $message, $is_html);
            }

        } catch (Exception $e) {
            error_log("Error en GmailSender: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        } catch (Error $e) {
            error_log("Error fatal en GmailSender: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Envío con PHPMailer (recomendado)
     */
    private function sendWithPHPMailer($to, $subject, $message, $is_html, $image_path = null) {
        try {
            if (!self::isPHPMailerAvailable() || !class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                error_log("PHPMailer no disponible, no se puede enviar email");
                return false;
            }

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Configuración SMTP para cPanel
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // TLS para puerto 587, o ENCRYPTION_SMTPS para puerto 465
            $mail->Port = $this->smtp_port;

            // Configuraciones adicionales para cPanel (puede requerir certificados autofirmados)
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

            // Configurar HTML antes de agregar imágenes
            $mail->isHTML($is_html);
            $mail->CharSet = 'UTF-8';

            // Prevenir que el servidor agregue firmas automáticas
            $mail->XMailer = 'My Suite In Cartagena';
            $mail->addCustomHeader('X-Auto-Response-Suppress', 'All');
            $mail->addCustomHeader('Precedence', 'bulk');
            // Intentar prevenir firmas automáticas del servidor
            $mail->addCustomHeader('List-Unsubscribe', '<mailto:' . $this->from_email . '>');

            // Agregar imagen incrustada si se proporciona (DEBE ser ANTES de establecer el Body)
            if ($image_path) {
                // Normalizar la ruta
                $normalized_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $image_path);

                if (file_exists($normalized_path) && is_readable($normalized_path)) {
                    try {
                        // Leer el archivo y determinar el tipo MIME
                        $image_info = getimagesize($normalized_path);
                        $mime_type = $image_info ? $image_info['mime'] : 'image/png';

                        // El CID debe ser exactamente 'hotel_logo' (sin 'cid:') para que coincida con el HTML
                        // Firma: addEmbeddedImage(path, cid, name, encoding, type, disposition)
                        $result = $mail->addEmbeddedImage(
                            $normalized_path,  // Ruta del archivo
                            'hotel_logo',      // CID (debe coincidir con cid:hotel_logo en el HTML)
                            'hotel_logo.png',  // Nombre del archivo
                            'base64',          // Encoding
                            $mime_type,        // Tipo MIME
                            'inline'           // Disposición
                        );

                        if ($result) {
                            error_log("✅ Imagen incrustada correctamente: $normalized_path (Tamaño: " . filesize($normalized_path) . " bytes, MIME: $mime_type, CID: hotel_logo)");
                        } else {
                            error_log("⚠️ addEmbeddedImage retornó false para: $normalized_path");
                        }
                    } catch (Exception $e) {
                        error_log("❌ Error al incrustar imagen: " . $e->getMessage());
                        error_log("Stack trace: " . $e->getTraceAsString());
                    } catch (Error $e) {
                        error_log("❌ Error fatal al incrustar imagen: " . $e->getMessage());
                        error_log("Stack trace: " . $e->getTraceAsString());
                    }
                } else {
                    error_log("⚠️ Imagen no encontrada o no es legible: $normalized_path");
                    if (file_exists($normalized_path)) {
                        error_log("   - El archivo existe pero no es legible. Permisos: " . substr(sprintf('%o', fileperms($normalized_path)), -4));
                    } else {
                        error_log("   - El archivo no existe en la ruta especificada");
                    }
                }
            } else {
                error_log("⚠️ Ruta de imagen no proporcionada (null)");
            }

            // Establecer contenido DESPUÉS de agregar las imágenes
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
        // Configurar PHP para SMTP
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
     * Obtener ruta de la imagen del hotel de forma robusta
     * Método público para que pueda ser usado desde otros archivos
     */
    public function getHotelImagePath() {
        $image_filename = 'HOTEL_CARTAGENA_silueta[1].png';
        $possible_paths = [];

        // 1. Rutas relativas desde el archivo actual (GmailSender.php está en includes/)
        $possible_paths[] = __DIR__ . '/../assets/shared/' . $image_filename;
        $possible_paths[] = __DIR__ . '/../../assets/shared/' . $image_filename;
        $possible_paths[] = dirname(__DIR__) . '/assets/shared/' . $image_filename;
        $possible_paths[] = dirname(dirname(__DIR__)) . '/assets/shared/' . $image_filename;

        // 2. Rutas basadas en DOCUMENT_ROOT (común en producción)
        if (isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT'])) {
            $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
            $possible_paths[] = $doc_root . '/assets/shared/' . $image_filename;
            $possible_paths[] = $doc_root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . $image_filename;

            // También intentar con posibles subdirectorios comunes
            $possible_paths[] = $doc_root . '/software_apto_php/assets/shared/' . $image_filename;
            $possible_paths[] = $doc_root . '/public_html/assets/shared/' . $image_filename;
            $possible_paths[] = $doc_root . '/www/assets/shared/' . $image_filename;
        }

        // 3. Rutas basadas en SCRIPT_FILENAME (donde se ejecuta el script)
        if (isset($_SERVER['SCRIPT_FILENAME']) && !empty($_SERVER['SCRIPT_FILENAME'])) {
            $script_dir = dirname($_SERVER['SCRIPT_FILENAME']);
            $possible_paths[] = $script_dir . '/assets/shared/' . $image_filename;
            $possible_paths[] = dirname($script_dir) . '/assets/shared/' . $image_filename;
            $possible_paths[] = dirname(dirname($script_dir)) . '/assets/shared/' . $image_filename;
        }

        // 4. Buscar desde la raíz del proyecto usando getcwd() si está disponible
        $current_dir = getcwd();
        if ($current_dir !== false) {
            $possible_paths[] = $current_dir . '/assets/shared/' . $image_filename;
            $possible_paths[] = dirname($current_dir) . '/assets/shared/' . $image_filename;
        }

        // 5. Buscar usando rutas absolutas basadas en el directorio del archivo actual
        $base_dir = dirname(dirname(__DIR__));
        $possible_paths[] = $base_dir . '/assets/shared/' . $image_filename;
        $base_dir_real = realpath($base_dir);
        if ($base_dir_real !== false) {
            $possible_paths[] = $base_dir_real . '/assets/shared/' . $image_filename;
        }

        // Eliminar duplicados y normalizar
        $possible_paths = array_unique($possible_paths);

        foreach ($possible_paths as $path) {
            // Normalizar la ruta (convertir / y \ a DIRECTORY_SEPARATOR)
            $normalized_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

            // Intentar obtener la ruta real (resuelve symlinks, .., etc.)
            $real_path = realpath($normalized_path);
            if ($real_path === false) {
                // Si realpath falla, intentar con la ruta normalizada
                $real_path = $normalized_path;
            }

            // Verificar si el archivo existe y es legible
            if (file_exists($real_path) && is_readable($real_path) && is_file($real_path)) {
                error_log("✅ Imagen del hotel encontrada en: $real_path");
                return $real_path;
            }
        }

        // Si no se encuentra, loggear advertencia con todas las rutas probadas
        error_log("⚠️ No se encontró la imagen del hotel en ninguna de las rutas probadas:");
        foreach ($possible_paths as $path) {
            $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            $real = realpath($normalized);
            $check_path = $real !== false ? $real : $normalized;
            $exists = file_exists($check_path) ? 'existe' : 'no existe';
            $readable = ($exists === 'existe' && is_readable($check_path)) ? ' (legible)' : ' (no legible)';
            error_log("   - $check_path ($exists$readable)");
        }

        return null;
    }

    /**
     * Enviar email de aprobación de reserva
     */
    public function sendReservaAprobada($reserva) {
        $subject = "✅ Reserva Aprobada - My Suite In Cartagena #" . $reserva['id_reserva'];

        // Obtener ruta de la imagen del hotel de forma robusta
        $hotel_image_path = $this->getHotelImagePath();

        if ($hotel_image_path) {
            error_log("📧 Enviando email de aprobación con imagen: $hotel_image_path");
            error_log("   - Archivo existe: " . (file_exists($hotel_image_path) ? 'SÍ' : 'NO'));
            error_log("   - Archivo legible: " . (is_readable($hotel_image_path) ? 'SÍ' : 'NO'));
            error_log("   - Tamaño: " . (file_exists($hotel_image_path) ? filesize($hotel_image_path) . ' bytes' : 'N/A'));
        } else {
            error_log("⚠️ Enviando email de aprobación SIN imagen (ruta no encontrada)");
            error_log("   - DOCUMENT_ROOT: " . (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'NO DEFINIDO'));
            error_log("   - SCRIPT_FILENAME: " . (isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : 'NO DEFINIDO'));
            error_log("   - __DIR__: " . __DIR__);
        }

        $fecha_entrada = date('d/m/Y', strtotime($reserva['fecha_entrada']));
        $fecha_salida = date('d/m/Y', strtotime($reserva['fecha_salida']));
        $total = (float)$reserva['total'];
        $total_formateado = number_format($total, 0, ',', '.');
        $anticipo_20 = $total * 0.20;
        $anticipo_formateado = number_format($anticipo_20, 0, ',', '.');
        $saldo_restante = $total * 0.80;
        $saldo_formateado = number_format($saldo_restante, 0, ',', '.');

        $message = $this->getEmailTemplate($reserva, $fecha_entrada, $fecha_salida, $total_formateado, $anticipo_formateado, $saldo_formateado);

        return $this->sendEmail($reserva['correo'], $subject, $message, true, $hotel_image_path);
    }

    /**
     * Enviar email de rechazo de reserva (por solapamiento)
     */
    public function sendReservaRechazada($reserva_rechazada, $reserva_aprobada) {
        $subject = "⚠️ Reserva No Disponible - My Suite In Cartagena #" . $reserva_rechazada['id_reserva'];

        // Obtener ruta de la imagen del hotel de forma robusta
        $hotel_image_path = $this->getHotelImagePath();

        $fecha_entrada_rechazada = date('d/m/Y', strtotime($reserva_rechazada['fecha_entrada']));
        $fecha_salida_rechazada = date('d/m/Y', strtotime($reserva_rechazada['fecha_salida']));
        $fecha_entrada_aprobada = date('d/m/Y', strtotime($reserva_aprobada['fecha_entrada']));
        $fecha_salida_aprobada = date('d/m/Y', strtotime($reserva_aprobada['fecha_salida']));

        $message = $this->getEmailTemplateRechazo($reserva_rechazada, $fecha_entrada_rechazada, $fecha_salida_rechazada, $fecha_entrada_aprobada, $fecha_salida_aprobada);

        return $this->sendEmail($reserva_rechazada['correo'], $subject, $message, true, $hotel_image_path);
    }

    /**
     * Enviar email de rechazo manual de reserva
     */
    public function sendReservaRechazadaManual($reserva) {
        $subject = "⚠️ Reserva Rechazada - My Suite In Cartagena #" . $reserva['id_reserva'];

        // Obtener ruta de la imagen del hotel de forma robusta
        $hotel_image_path = $this->getHotelImagePath();

        $fecha_entrada = date('d/m/Y', strtotime($reserva['fecha_entrada']));
        $fecha_salida = date('d/m/Y', strtotime($reserva['fecha_salida']));

        $message = $this->getEmailTemplateRechazoManual($reserva, $fecha_entrada, $fecha_salida);

        return $this->sendEmail($reserva['correo'], $subject, $message, true, $hotel_image_path);
    }

    /**
     * Enviar email de cancelación de reserva
     */
    public function sendReservaCancelada($reserva) {
        $subject = "❌ Reserva Cancelada - My Suite In Cartagena #" . $reserva['id_reserva'];

        // Obtener ruta de la imagen del hotel de forma robusta
        $hotel_image_path = $this->getHotelImagePath();

        $fecha_entrada = date('d/m/Y', strtotime($reserva['fecha_entrada']));
        $fecha_salida = date('d/m/Y', strtotime($reserva['fecha_salida']));

        $message = $this->getEmailTemplateCancelacion($reserva, $fecha_entrada, $fecha_salida);

        return $this->sendEmail($reserva['correo'], $subject, $message, true, $hotel_image_path);
    }

    /**
     * Template HTML para email de aprobación
     */
    private function getEmailTemplate($reserva, $fecha_entrada, $fecha_salida, $total_formateado, $anticipo_formateado, $saldo_formateado) {
        // --- INICIO: LÓGICA DEL ENLACE DE PAGO ---
        // Datos para generar enlace de pago
        $total_num = isset($reserva['total']) ? (float)$reserva['total'] : 0;

        // Calcula el monto SIN formatear (ej: 50000)
        // Usamos (int) round() para asegurarnos que sea un entero, como lo espera Bold.
        $anticipo_amount_raw = (int) round($total_num * 0.20);

        $order_id = 'RES-' . $reserva['id_reserva'] . '-' . time();
        $currency = 'COP';
        $percent = 0.20;

        // Creamos la URL que apunta a tu script de pago
        $payment_url = 'https://mysuiteincartagena.com.co/checkout.php?' . http_build_query([
                'orderId' => $order_id,
                'amount'  => $anticipo_amount_raw, // Pasamos el monto (aunque checkout.php lo ignorará por seguridad)
                'currency'=> $currency,
                'reserva' => $reserva['id_reserva'], // ¡Este es el dato CLAVE y de confianza!
                'percent' => $percent // Porcentaje a calcular, en este caso 20% para abono
            ]);

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
                    <h1 style='text-align: center; margin: 0 0 15px 0; line-height: 50px;'>
                        <img src=\"https://raw.githubusercontent.com/josemanuelcard/software_apto_php/main/assets/shared/HOTEL_CARTAGENA_silueta%5B1%5D.png\" alt=\"My Suite In Cartagena\" style=\"width: 50px; height: 50px; vertical-align: middle; margin-right: 10px; display: inline-block;\" />
                        <span style=\"vertical-align: middle; display: inline-block;\">My Suite In Cartagena</span>
                    </h1>
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
                        <p><strong>Método de Pago:</strong> " . ($reserva['metodo_pago'] === 'efectivo' || $reserva['metodo_pago'] === 'transferencia' ? 'Transferencia' : 'Tarjeta de Crédito') . "</p>
                    </div>
                    
                    <div class='total'>
                        💰 Total de la Reserva: $" . $total_formateado . " COP
                    </div>
                    
                    <div class='instructions'>
                        <h3>💳 INSTRUCCIONES DE PAGO:</h3>
                        <p><strong>Una vez aprobada tu reserva, deberás pagar el 20% del total y enviar el comprobante a gerencia@mysuiteincartagena.com.co.</strong></p>
                        <p><strong>El saldo restante se cancelará el día anterior al check-in.</strong></p>
                        <div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 15px; border-left: 4px solid #2196f3;'>
                            <p><strong>📝 Pasos a seguir:</strong></p>
                            <p><strong>1.</strong> Realiza el pago del 20% (anticipado): <strong>$" . $anticipo_formateado . " COP</strong></p>
                            <p><strong>2.</strong> Toma una foto o escanea el comprobante de pago</p>
                            <p><strong>3.</strong> <strong>IMPORTANTE:</strong> Envía el comprobante al correo: <strong>gerencia@mysuiteincartagena.com.co</strong></p>
                            <p><strong>4.</strong> Una vez confirmado el pago del anticipo, recibirás la confirmación final</p>
                            <p><strong>5.</strong> El saldo restante (<strong>$" . $saldo_formateado . " COP</strong>) se cancelará el día anterior al check-in</p>
                        </div>
                        <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 15px; border-left: 4px solid #ffc107;'>
                            <strong>⚠️ RECORDATORIO:</strong> Sin el comprobante de pago del anticipo, tu reserva no será confirmada.
                        </div>
                    </div>
                    
                    <div class='instructions' style='background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #2196f3;'>
                        <h3>💳 Medio de Pago para el Abono (20%):</h3>
                        " . (($reserva['metodo_pago'] === 'efectivo' || $reserva['metodo_pago'] === 'transferencia') ? "
                        <p>Para consignar el <strong>20% del total ($" . $anticipo_formateado . " COP)</strong>, puedes usar la siguiente llave de pago:</p>
                        <p style='text-align: center; font-size: 24px; font-weight: bold; color: #2196f3; margin: 20px 0; padding: 15px; background: white; border-radius: 8px; border: 2px solid #2196f3;'>
                            @COO3137910897
                        </p>
                        <p style='text-align: center; color: #666; font-size: 14px;'>Usa esta llave en tu aplicación bancaria para realizar la consignación del abono.</p>
                        " : "
                        <p>Para realizar el pago del <strong>20% del total ($" . $anticipo_formateado . " COP)</strong>, puedes usar el siguiente botón:</p>
                        <div style='text-align: center; margin-top: 25px;'>
                            <a href=\"" . htmlspecialchars($payment_url) . "\" style='display: inline-block; background-color: #FFE082; color: #333; padding: 15px 40px; font-family: Arial, sans-serif; font-size: 16px; font-weight: 600; text-decoration: none; border-radius: 50px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3); text-transform: uppercase; letter-spacing: 1px;'>
                                Pagar ahora con tarjeta
                            </a>
                        </div>
                        ") . "
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
        </html>
        ";
    }

    /**
     * Template HTML para email de rechazo por solapamiento
     */
    private function getEmailTemplateRechazo($reserva_rechazada, $fecha_entrada_rechazada, $fecha_salida_rechazada, $fecha_entrada_aprobada, $fecha_salida_aprobada) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
                .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px 20px; text-align: center; }
                .content { padding: 30px; background: #f8f9fa; }
                .warning { background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #ffc107; }
                .info { background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #2196f3; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
                h1, h2, h3 { margin: 0 0 15px 0; }
                p { margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='text-align: center; margin: 0 0 15px 0; line-height: 50px;'>
                        <img src=\"https://raw.githubusercontent.com/josemanuelcard/software_apto_php/main/assets/shared/HOTEL_CARTAGENA_silueta%5B1%5D.png\" alt=\"My Suite In Cartagena\" style=\"width: 50px; height: 50px; vertical-align: middle; margin-right: 10px; display: inline-block;\" />
                        <span style=\"vertical-align: middle; display: inline-block;\">My Suite In Cartagena</span>
                    </h1>
                    <h2>Reserva No Disponible</h2>
                </div>
                <div class='content'>
                    <p>Hola <strong>{$reserva_rechazada['nombre']} {$reserva_rechazada['apellido']}</strong>,</p>
                    
                    <div class='warning'>
                        <h3>⚠️ Información Importante</h3>
                        <p>Lamentamos informarte que tu solicitud de reserva <strong>#{$reserva_rechazada['id_reserva']}</strong> para las fechas:</p>
                        <p style='text-align: center; font-size: 18px; font-weight: bold;'>
                            {$fecha_entrada_rechazada} - {$fecha_salida_rechazada}
                        </p>
                        <p><strong>no puede ser procesada</strong> debido a que esas fechas ya fueron reservadas por otro cliente.</p>
                    </div>
                    
                    <div class='info'>
                        <h3>📅 Fechas Ya Reservadas:</h3>
                        <p style='text-align: center; font-size: 16px;'>
                            {$fecha_entrada_aprobada} - {$fecha_salida_aprobada}
                        </p>
                    </div>
                    
                    <p>Entendemos que esto puede ser una inconveniencia y te pedimos disculpas. Te invitamos a:</p>
                    <ul>
                        <li>Seleccionar otras fechas disponibles en nuestro calendario</li>
                        <li>Contactarnos directamente para encontrar una alternativa</li>
                    </ul>
                    
                    <p>Si tienes alguna pregunta o necesitas asistencia, no dudes en contactarnos.</p>
                    
                    <div class='info'>
                        <h3>📞 Información de Contacto:</h3>
                        <p><strong>📧 Email:</strong> gerencia@mysuiteincartagena.com.co</p>
                        <p><strong>📱 WhatsApp:</strong> +57 3105495149</p>
                    </div>
                    
                    <p>¡Esperamos poder atenderte en otra fecha!</p>
                </div>
                <div class='footer'>
                    <p>Saludos cordiales,<br>
                    <strong>Equipo My Suite In Cartagena</strong></p>
                    <p>Este es un email automático, por favor no responder a esta dirección.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Template HTML para email de rechazo manual
     */
    private function getEmailTemplateRechazoManual($reserva, $fecha_entrada, $fecha_salida) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
                .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px 20px; text-align: center; }
                .content { padding: 30px; background: #f8f9fa; }
                .warning { background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #ffc107; }
                .info { background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #2196f3; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
                h1, h2, h3 { margin: 0 0 15px 0; }
                p { margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='text-align: center; margin: 0 0 15px 0; line-height: 50px;'>
                        <img src=\"https://raw.githubusercontent.com/josemanuelcard/software_apto_php/main/assets/shared/HOTEL_CARTAGENA_silueta%5B1%5D.png\" alt=\"My Suite In Cartagena\" style=\"width: 50px; height: 50px; vertical-align: middle; margin-right: 10px; display: inline-block;\" />
                        <span style=\"vertical-align: middle; display: inline-block;\">My Suite In Cartagena</span>
                    </h1>
                    <h2>Reserva Rechazada</h2>
                </div>
                <div class='content'>
                    <p>Hola <strong>{$reserva['nombre']} {$reserva['apellido']}</strong>,</p>
                    
                    <div class='warning'>
                        <h3>⚠️ Información Importante</h3>
                        <p>Lamentamos informarte que tu solicitud de reserva <strong>#{$reserva['id_reserva']}</strong> para las fechas:</p>
                        <p style='text-align: center; font-size: 18px; font-weight: bold;'>
                            {$fecha_entrada} - {$fecha_salida}
                        </p>
                        <p><strong>ha sido rechazada</strong>.</p>
                    </div>
                    
                    <div class='info'>
                        <h3>📋 Detalles de la Reserva:</h3>
                        <p><strong>ID Reserva:</strong> #{$reserva['id_reserva']}</p>
                        <p><strong>Fecha de Entrada:</strong> {$fecha_entrada}</p>
                        <p><strong>Fecha de Salida:</strong> {$fecha_salida}</p>
                        <p><strong>Huéspedes:</strong> {$reserva['num_adultos']} adultos, {$reserva['num_ninos']} niños</p>
                    </div>
                    
                    <p>Entendemos que esto puede ser una inconveniencia y te pedimos disculpas. Te invitamos a:</p>
                    <ul>
                        <li>Seleccionar otras fechas disponibles en nuestro calendario</li>
                        <li>Contactarnos directamente para encontrar una alternativa</li>
                    </ul>
                    
                    <p>Si tienes alguna pregunta o necesitas asistencia, no dudes en contactarnos.</p>
                    
                    <div class='info'>
                        <h3>📞 Información de Contacto:</h3>
                        <p><strong>📧 Email:</strong> gerencia@mysuiteincartagena.com.co</p>
                        <p><strong>📱 WhatsApp:</strong> +57 3105495149</p>
                    </div>
                    
                    <p>¡Esperamos poder atenderte en otra fecha!</p>
                </div>
                <div class='footer'>
                    <p>Saludos cordiales,<br>
                    <strong>Equipo My Suite In Cartagena</strong></p>
                    <p>Este es un email automático, por favor no responder a esta dirección.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Template HTML para email de cancelación
     */
    private function getEmailTemplateCancelacion($reserva, $fecha_entrada, $fecha_salida) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
                .header { background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); color: white; padding: 30px 20px; text-align: center; }
                .content { padding: 30px; background: #f8f9fa; }
                .warning { background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #ffc107; }
                .info { background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #2196f3; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
                h1, h2, h3 { margin: 0 0 15px 0; }
                p { margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='text-align: center; margin: 0 0 15px 0; line-height: 50px;'>
                        <img src=\"https://raw.githubusercontent.com/josemanuelcard/software_apto_php/main/assets/shared/HOTEL_CARTAGENA_silueta%5B1%5D.png\" alt=\"My Suite In Cartagena\" style=\"width: 50px; height: 50px; vertical-align: middle; margin-right: 10px; display: inline-block;\" />
                        <span style=\"vertical-align: middle; display: inline-block;\">My Suite In Cartagena</span>
                    </h1>
                    <h2>Reserva Cancelada</h2>
                </div>
                <div class='content'>
                    <p>Hola <strong>{$reserva['nombre']} {$reserva['apellido']}</strong>,</p>
                    
                    <div class='warning'>
                        <h3>❌ Información Importante</h3>
                        <p>Te informamos que tu reserva <strong>#{$reserva['id_reserva']}</strong> para las fechas:</p>
                        <p style='text-align: center; font-size: 18px; font-weight: bold;'>
                            {$fecha_entrada} - {$fecha_salida}
                        </p>
                        <p><strong>ha sido cancelada</strong>.</p>
                    </div>
                    
                    <div class='info'>
                        <h3>📋 Detalles de la Reserva Cancelada:</h3>
                        <p><strong>ID Reserva:</strong> #{$reserva['id_reserva']}</p>
                        <p><strong>Fecha de Entrada:</strong> {$fecha_entrada}</p>
                        <p><strong>Fecha de Salida:</strong> {$fecha_salida}</p>
                        <p><strong>Huéspedes:</strong> {$reserva['num_adultos']} adultos, {$reserva['num_ninos']} niños</p>
                    </div>
                    
                    <p>Si tienes alguna pregunta sobre esta cancelación o deseas realizar una nueva reserva, no dudes en contactarnos.</p>
                    
                    <div class='info'>
                        <h3>📞 Información de Contacto:</h3>
                        <p><strong>📧 Email:</strong> gerencia@mysuiteincartagena.com.co</p>
                        <p><strong>📱 WhatsApp:</strong> +57 3105495149</p>
                    </div>
                    
                    <p>¡Esperamos poder atenderte en el futuro!</p>
                </div>
                <div class='footer'>
                    <p>Saludos cordiales,<br>
                    <strong>Equipo My Suite In Cartagena</strong></p>
                    <p>Este es un email automático, por favor no responder a esta dirección.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>
