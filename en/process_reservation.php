<?php
/**
 * Procesar Reserva - Sistema de Reservas
 * My Suite In Cartagena
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos de reserva inválidos');
    }
    
    // Debug temporal - eliminar después
    error_log("Datos recibidos: " . json_encode($input));
    error_log("Descuento promocional recibido: " . ($input['descuento_promocional'] ?? 'NO ENVIADO'));
    
    // Validar datos requeridos
    $required_fields = ['nombre', 'apellido', 'correo', 'telefono', 'fecha_entrada', 'fecha_salida', 'num_adultos', 'metodo_pago'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Campo requerido: $field");
        }
    }
    
    // Validar fechas
    $fecha_entrada = new DateTime($input['fecha_entrada']);
    $fecha_salida = new DateTime($input['fecha_salida']);
    
    if ($fecha_entrada >= $fecha_salida) {
        throw new Exception('La fecha de salida debe ser posterior a la fecha de entrada');
    }
    
    if ($fecha_entrada < new DateTime()) {
        throw new Exception('No se pueden hacer reservas para fechas pasadas');
    }
    
    // Validar que las fechas no estén ocupadas
    $fechas_ocupadas = getFechasOcupadas();
    $fecha_actual = clone $fecha_entrada;
    
    while ($fecha_actual < $fecha_salida) {
        if (in_array($fecha_actual->format('Y-m-d'), $fechas_ocupadas)) {
            throw new Exception('Las fechas seleccionadas contienen días ya ocupados');
        }
        $fecha_actual->add(new DateInterval('P1D'));
    }
    
    // Preparar datos para guardar
    $datos_reserva = [
        'id_apartamento' => $input['id_apartamento'] ?? 1,
        'id_usuario' => $input['id_usuario'] ?? null,
        'nombre' => trim($input['nombre']),
        'apellido' => trim($input['apellido']),
        'correo' => trim($input['correo']),
        'telefono' => trim($input['telefono']),
        'fecha_nacimiento' => $input['fecha_nacimiento'] ?? null,
        'fecha_entrada' => $input['fecha_entrada'],
        'fecha_salida' => $input['fecha_salida'],
        'num_adultos' => (int)$input['num_adultos'],
        'num_ninos' => (int)($input['num_ninos'] ?? 0),
        'vive_palmira' => isset($input['vive_palmira']) && $input['vive_palmira'] ? 1 : 0,
        'metodo_pago' => $input['metodo_pago'],
        'costo_base' => (float)$input['costo_base'],
        'descuento_fidelizacion' => (float)($input['descuento_fidelizacion'] ?? 0),
        'descuento_cumpleanios' => (float)($input['descuento_cumpleanios'] ?? 0),
        'descuento_promocional' => (float)($input['descuento_promocional'] ?? 0),
        'total' => (float)$input['total']
    ];
    
    // Guardar reserva
    $reserva_id = guardarReserva($datos_reserva);
    
    if ($reserva_id) {
        echo json_encode([
            'success' => true,
            'message' => 'Reserva enviada exitosamente',
            'reserva_id' => $reserva_id
        ]);
    } else {
        throw new Exception('Error al guardar la reserva en la base de datos');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
