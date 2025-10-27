<?php
/**
 * Obtener datos del calendario para el admin
 * Sistema de Reservas - My Suite Cartagena
 */

session_start();

// Verificar si el usuario está logueado como admin
if (!((isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) ||
    (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true && 
     isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin'))) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $db = (new Database())->getConnection();
    
    $month = $_GET['month'] ?? date('n') - 1; // 0-11
    $year = $_GET['year'] ?? date('Y');
    
    // Obtener reservas del mes
    $query_reservations = "
        SELECT r.*, u.nombre, u.apellido, u.correo
        FROM reservas r 
        LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario 
        WHERE (
            (MONTH(r.fecha_entrada) = ? AND YEAR(r.fecha_entrada) = ?) OR
            (MONTH(r.fecha_salida) = ? AND YEAR(r.fecha_salida) = ?) OR
            (r.fecha_entrada <= LAST_DAY(STR_TO_DATE(CONCAT(?, '-', ?, '-01'), '%Y-%m-%d')) AND 
             r.fecha_salida >= STR_TO_DATE(CONCAT(?, '-', ?, '-01'), '%Y-%m-%d'))
        )
        ORDER BY r.fecha_entrada ASC
    ";
    
    $stmt_reservations = $db->prepare($query_reservations);
    $stmt_reservations->execute([
        $month + 1, $year,
        $month + 1, $year,
        $year, $month + 1,
        $year, $month + 1
    ]);
    $reservations = $stmt_reservations->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener fechas bloqueadas del mes (solo las que realmente existen)
    $query_blocked = "
        SELECT * FROM fechas_bloqueadas 
        WHERE (
            (MONTH(fecha_inicio) = ? AND YEAR(fecha_inicio) = ?) OR
            (MONTH(fecha_fin) = ? AND YEAR(fecha_fin) = ?) OR
            (fecha_inicio <= LAST_DAY(STR_TO_DATE(CONCAT(?, '-', ?, '-01'), '%Y-%m-%d')) AND 
             fecha_fin >= STR_TO_DATE(CONCAT(?, '-', ?, '-01'), '%Y-%m-%d'))
        )
        AND activo = 1
        ORDER BY fecha_inicio ASC
    ";
    
    $stmt_blocked = $db->prepare($query_blocked);
    $stmt_blocked->execute([
        $month + 1, $year,
        $month + 1, $year,
        $year, $month + 1,
        $year, $month + 1
    ]);
    $blocked_dates = $stmt_blocked->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear fechas para JavaScript
    foreach ($reservations as &$reservation) {
        $reservation['fecha_entrada'] = date('Y-m-d', strtotime($reservation['fecha_entrada']));
        $reservation['fecha_salida'] = date('Y-m-d', strtotime($reservation['fecha_salida']));
    }
    
    foreach ($blocked_dates as &$blocked) {
        $blocked['fecha_inicio'] = date('Y-m-d', strtotime($blocked['fecha_inicio']));
        $blocked['fecha_fin'] = date('Y-m-d', strtotime($blocked['fecha_fin']));
    }
    
    echo json_encode([
        'success' => true,
        'reservations' => $reservations,
        'blocked_dates' => $blocked_dates,
        'month' => $month,
        'year' => $year
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>
