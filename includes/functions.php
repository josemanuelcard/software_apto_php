<?php
/**
 * Funciones del Sistema de Reservas
 * My Suite In Cartagena
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Obtener fechas ocupadas para el calendario (reservas + fechas bloqueadas)
 */
function getFechasOcupadas($apartamento_id = 1) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Si no hay conexión, retornar array vacío
    if (!$db) {
        return [];
    }
    
    try {
        $fechas_ocupadas = [];
        
        // 1. Obtener fechas de reservas aprobadas
        $query_reservas = "
            SELECT DISTINCT fecha_entrada, fecha_salida 
            FROM reservas 
            WHERE id_apartamento = :apartamento_id 
            AND estado = 'aprobada'
            AND fecha_salida >= CURDATE()
        ";
        
        $stmt = $db->prepare($query_reservas);
        $stmt->bindParam(':apartamento_id', $apartamento_id);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $inicio = new DateTime($row['fecha_entrada']);
            $fin = new DateTime($row['fecha_salida']);
            
            while ($inicio < $fin) {
                $fechas_ocupadas[] = $inicio->format('Y-m-d');
                $inicio->add(new DateInterval('P1D'));
            }
        }
        
        // 2. Obtener fechas bloqueadas manualmente
        $query_bloqueadas = "
            SELECT fecha_inicio, fecha_fin 
            FROM fechas_bloqueadas 
            WHERE activo = 1
            AND fecha_fin >= CURDATE()
        ";
        
        $stmt = $db->prepare($query_bloqueadas);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $inicio = new DateTime($row['fecha_inicio']);
            $fin = new DateTime($row['fecha_fin']);
            
            while ($inicio <= $fin) {
                $fechas_ocupadas[] = $inicio->format('Y-m-d');
                $inicio->add(new DateInterval('P1D'));
            }
        }
        
        // Eliminar duplicados y ordenar
        $fechas_ocupadas = array_unique($fechas_ocupadas);
        sort($fechas_ocupadas);
        
        return $fechas_ocupadas;
    } catch (Exception $e) {
        // Si hay error, retornar array vacío
        error_log("Error en getFechasOcupadas: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener tarifa por fecha
 */
function getTarifaPorFecha($fecha, $apartamento_id = 1) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Si no hay conexión, retornar precio base
    if (!$db) {
        return 200000;
    }
    
    try {
        $query = "
            SELECT precio 
            FROM tarifas 
            WHERE id_apartamento = :apartamento_id 
            AND fecha = :fecha
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':apartamento_id', $apartamento_id);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['precio'] : 200000;
    } catch (Exception $e) {
        error_log("Error en getTarifaPorFecha: " . $e->getMessage());
        return 200000;
    }
}

/**
 * Guardar reserva
 */
function guardarReserva($datos) {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        return false;
    }
    
    try {
        $db->beginTransaction();
        
        $query = "
            INSERT INTO reservas (
                id_apartamento, id_usuario, nombre, apellido, correo, telefono,
                fecha_nacimiento, fecha_entrada, fecha_salida, num_adultos, num_ninos,
                vive_palmira, metodo_pago, costo_base, descuento_fidelizacion, descuento_cumpleanios,
                descuento_promocional, total, estado
            ) VALUES (
                :id_apartamento, :id_usuario, :nombre, :apellido, :correo, :telefono,
                :fecha_nacimiento, :fecha_entrada, :fecha_salida, :num_adultos, :num_ninos,
                :vive_palmira, :metodo_pago, :costo_base, :descuento_fidelizacion, :descuento_cumpleanios,
                :descuento_promocional, :total, 'pendiente'
            )
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id_apartamento', $datos['id_apartamento']);
        $stmt->bindParam(':id_usuario', $datos['id_usuario']);
        $stmt->bindParam(':nombre', $datos['nombre']);
        $stmt->bindParam(':apellido', $datos['apellido']);
        $stmt->bindParam(':correo', $datos['correo']);
        $stmt->bindParam(':telefono', $datos['telefono']);
        $stmt->bindParam(':fecha_nacimiento', $datos['fecha_nacimiento']);
        $stmt->bindParam(':fecha_entrada', $datos['fecha_entrada']);
        $stmt->bindParam(':fecha_salida', $datos['fecha_salida']);
        $stmt->bindParam(':num_adultos', $datos['num_adultos']);
        $stmt->bindParam(':num_ninos', $datos['num_ninos']);
        $stmt->bindParam(':vive_palmira', $datos['vive_palmira']);
        $stmt->bindParam(':metodo_pago', $datos['metodo_pago']);
        $stmt->bindParam(':costo_base', $datos['costo_base']);
        $stmt->bindParam(':descuento_fidelizacion', $datos['descuento_fidelizacion']);
        $stmt->bindParam(':descuento_cumpleanios', $datos['descuento_cumpleanios']);
        $stmt->bindParam(':descuento_promocional', $datos['descuento_promocional']);
        $stmt->bindParam(':total', $datos['total']);
        
        $stmt->execute();
        $reserva_id = $db->lastInsertId();
        
        $db->commit();
        
        // Enviar email de notificación
        enviarEmailConfirmacion($datos, $reserva_id);
        
        return $reserva_id;
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Error en guardarReserva: " . $e->getMessage());
        return false;
    }
}

/**
 * Enviar email de confirmación
 */
function enviarEmailConfirmacion($datos, $reserva_id) {
    $asunto = "Nueva Solicitud de Reserva #$reserva_id - My Suite In Cartagena";
    
    $mensaje = "
    <h2>Nueva Solicitud de Reserva</h2>
    <p><strong>ID de Reserva:</strong> #$reserva_id</p>
    
    <h3>Datos del Cliente:</h3>
    <p><strong>Nombre:</strong> {$datos['nombre']} {$datos['apellido']}</p>
    <p><strong>Email:</strong> {$datos['correo']}</p>
    <p><strong>Teléfono:</strong> {$datos['telefono']}</p>
    <p><strong>Fecha de Nacimiento:</strong> {$datos['fecha_nacimiento']}</p>
    
    <h3>Detalles de la Reserva:</h3>
    <p><strong>Fecha de Entrada:</strong> {$datos['fecha_entrada']}</p>
    <p><strong>Fecha de Salida:</strong> {$datos['fecha_salida']}</p>
    <p><strong>Adultos:</strong> {$datos['num_adultos']}</p>
    <p><strong>Niños:</strong> {$datos['num_ninos']}</p>
    <p><strong>Vive en Palmira:</strong> " . ($datos['vive_palmira'] ? 'Sí' : 'No') . "</p>
    <p><strong>Método de Pago:</strong> {$datos['metodo_pago']}</p>
    
    <h3>Resumen Financiero:</h3>
    <p><strong>Costo Base:</strong> $" . number_format($datos['costo_base'], 0, ',', '.') . " COP</p>
    <p><strong>Descuento Fidelización:</strong> $" . number_format($datos['descuento_fidelizacion'], 0, ',', '.') . " COP</p>
    <p><strong>Descuento Cumpleaños:</strong> $" . number_format($datos['descuento_cumpleanios'], 0, ',', '.') . " COP</p>
    <p><strong>Descuento Promocional:</strong> $" . number_format($datos['descuento_promocional'], 0, ',', '.') . " COP</p>
    <p><strong>Total Final:</strong> $" . number_format($datos['total'], 0, ',', '.') . " COP</p>
    
    <p>Por favor, revisa esta solicitud en el panel de administración.</p>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@mysuitecartagena.com" . "\r\n";
    
    // Para desarrollo, solo log
    error_log("Email de reserva #$reserva_id: " . $mensaje);
}

/**
 * Validar código promocional
 */
function validarCodigoPromocion($codigo) {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        return false;
    }
    
    try {
        $query = "
            SELECT * FROM descuentos 
            WHERE codigo = :codigo 
            AND activo = 1 
            AND (fecha_inicio IS NULL OR fecha_inicio <= CURDATE()) 
            AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
            AND tipo = 'promocional'
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':codigo', $codigo);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error en validarCodigoPromocion: " . $e->getMessage());
        return false;
    }
}

/**
 * Calcular descuentos
 */
function calcularDescuentos($subtotal, $usuario_id = null, $codigo_promocion = null, $metodo_pago = 'tarjeta_credito') {
    $descuentos = [
        'fidelidad' => 0,
        'cumpleanos' => 0,
        'vendedores' => 0,
        'promocion' => 0,
        'efectivo' => 0
    ];
    
    // Descuento por fidelización (si está registrado)
    if ($usuario_id) {
        $descuentos['fidelidad'] = $subtotal * 0.05; // 5%
        
        // Descuento por cumpleaños
        $database = new Database();
        $db = $database->getConnection();
        if ($db) {
            try {
                $query = "SELECT fecha_nacimiento FROM usuarios WHERE id_usuario = :usuario_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':usuario_id', $usuario_id);
                $stmt->execute();
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($usuario && $usuario['fecha_nacimiento']) {
                    $fecha_nacimiento = new DateTime($usuario['fecha_nacimiento']);
                    $hoy = new DateTime();
                    if ($fecha_nacimiento->format('m-d') == $hoy->format('m-d')) {
                        $descuentos['cumpleanos'] = $subtotal * 0.30; // 30%
                    }
                }
            } catch (Exception $e) {
                error_log("Error en calcularDescuentos: " . $e->getMessage());
            }
        }
    }
    
    // Descuento por código promocional
    if ($codigo_promocion) {
        $codigo = validarCodigoPromocion($codigo_promocion);
        if ($codigo) {
            $descuentos['promocion'] = $subtotal * ($codigo['porcentaje'] / 100);
        }
    }
    
    // Descuento por pago en efectivo
    if ($metodo_pago == 'efectivo') {
        $descuentos['efectivo'] = $subtotal * 0.03; // 3%
    }
    
    return $descuentos;
}

/**
 * Obtener estadísticas de reservas
 */
function getEstadisticasReservas() {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        return [
            'total_reservas' => 0,
            'reservas_pendientes' => 0,
            'ingresos_mes' => 0
        ];
    }
    
    $stats = [];
    
    try {
        // Total de reservas
        $query = "SELECT COUNT(*) as total FROM reservas";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['total_reservas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Reservas por estado
        $query = "SELECT estado, COUNT(*) as cantidad FROM reservas GROUP BY estado";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['reservas_pendientes'] = 0;
        $stats['reservas_aprobadas'] = 0;
        $stats['reservas_rechazadas'] = 0;
        $stats['reservas_canceladas'] = 0;
        
        foreach ($estados as $estado) {
            switch ($estado['estado']) {
                case 'pendiente':
                    $stats['reservas_pendientes'] = $estado['cantidad'];
                    break;
                case 'aprobada':
                    $stats['reservas_aprobadas'] = $estado['cantidad'];
                    break;
                case 'rechazada':
                    $stats['reservas_rechazadas'] = $estado['cantidad'];
                    break;
                case 'cancelada':
                    $stats['reservas_canceladas'] = $estado['cantidad'];
                    break;
            }
        }
        
        // Ingresos totales
        $query = "SELECT SUM(total) as ingresos FROM reservas WHERE estado = 'aprobada'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['ingresos_totales'] = $stmt->fetch(PDO::FETCH_ASSOC)['ingresos'] ?? 0;
        
        // Métodos de pago
        $query = "SELECT metodo_pago, COUNT(*) as cantidad FROM reservas GROUP BY metodo_pago";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['metodo_tarjeta'] = 0;
        $stats['metodo_efectivo'] = 0;
        
        foreach ($metodos as $metodo) {
            if ($metodo['metodo_pago'] === 'tarjeta_credito') {
                $stats['metodo_tarjeta'] = $metodo['cantidad'];
            } elseif ($metodo['metodo_pago'] === 'efectivo') {
                $stats['metodo_efectivo'] = $metodo['cantidad'];
            }
        }
        
    } catch (Exception $e) {
        error_log("Error en getEstadisticasReservas: " . $e->getMessage());
        $stats = [
            'total_reservas' => 0,
            'reservas_pendientes' => 0,
            'reservas_aprobadas' => 0,
            'reservas_rechazadas' => 0,
            'reservas_canceladas' => 0,
            'ingresos_totales' => 0,
            'metodo_tarjeta' => 0,
            'metodo_efectivo' => 0
        ];
    }
    
    return $stats;
}

/**
 * Obtener reservas recientes
 */
function getReservasRecientes($limite = 10) {
    global $db;
    
    if (!$db) {
        return [];
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM reservas ORDER BY creado_en DESC LIMIT ?");
        $stmt->execute([$limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        return [];
    }
}
?>
