<?php
/**
 * Gestión de Reservas - Panel de Administración
 * My Suite In Cartagena
 */

session_start();

// Verificar si el usuario está logueado como admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/GmailSender.php';

/**
 * Enviar email de aprobación al cliente
 */
function enviarEmailAprobacion($reserva) {
    try {
        $emailSender = new GmailSender();
        return $emailSender->sendReservaAprobada($reserva);
    } catch (Exception $e) {
        error_log("Error enviando email: " . $e->getMessage());
        return false;
    }
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? 0;
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception("Error de conexión a la base de datos");
        }
        
        switch ($action) {
            case 'approve':
                // Obtener datos de la reserva antes de aprobar
                $query_reserva = "SELECT *, email_enviado FROM reservas WHERE id_reserva = ?";
                $stmt_reserva = $db->prepare($query_reserva);
                $stmt_reserva->execute([$id]);
                $reserva = $stmt_reserva->fetch(PDO::FETCH_ASSOC);
                
                if ($reserva) {
                    // Aprobar la reserva
                    $query = "UPDATE reservas SET estado = 'aprobada' WHERE id_reserva = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$id]);
                    
                    // Solo enviar email si no se ha enviado antes
                    if (!isset($reserva['email_enviado']) || !$reserva['email_enviado']) {
                        $email_enviado = enviarEmailAprobacion($reserva);
                        
                        if ($email_enviado) {
                            // Marcar como email enviado solo si se envió exitosamente
                            $update_email = "UPDATE reservas SET email_enviado = TRUE WHERE id_reserva = ?";
                            $stmt_email = $db->prepare($update_email);
                            $stmt_email->execute([$id]);
                            
                            $mensaje = 'Reserva aprobada exitosamente. Se envió email al cliente.';
                        } else {
                            $mensaje = 'Reserva aprobada exitosamente. Error al enviar email.';
                        }
                    } else {
                        $mensaje = 'Reserva aprobada exitosamente. Email ya fue enviado anteriormente.';
                    }
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = 'Reserva no encontrada';
                    $tipo_mensaje = 'danger';
                }
                break;
                
            case 'reject':
                $query = "UPDATE reservas SET estado = 'rechazada' WHERE id_reserva = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$id]);
                $mensaje = 'Reserva rechazada';
                $tipo_mensaje = 'warning';
                break;
                
            case 'cancel':
                $query = "UPDATE reservas SET estado = 'cancelada' WHERE id_reserva = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$id]);
                $mensaje = 'Reserva cancelada';
                $tipo_mensaje = 'info';
                break;
        }
        
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Obtener filtros
$estado_filtro = $_GET['estado'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Construir consulta
$where_conditions = [];
$params = [];

if ($estado_filtro) {
    $where_conditions[] = "r.estado = ?";
    $params[] = $estado_filtro;
}

if ($fecha_desde) {
    $where_conditions[] = "r.fecha_entrada >= ?";
    $params[] = $fecha_desde;
}

if ($fecha_hasta) {
    $where_conditions[] = "r.fecha_entrada <= ?";
    $params[] = $fecha_hasta;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Obtener reservas
$query = "SELECT r.*, a.nombre as apartamento_nombre 
          FROM reservas r 
          LEFT JOIN apartamentos a ON r.id_apartamento = a.id_apartamento 
          $where_clause 
          ORDER BY r.creado_en DESC";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $reservas = [];
    $mensaje = 'Error al cargar reservas: ' . $e->getMessage();
    $tipo_mensaje = 'danger';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reservas - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 10px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateX(5px);
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-plane me-2"></i>
                        My Suite Cartagena
                    </h4>
                    <p class="text-white-50 mb-0">Panel de Control</p>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link active" href="reservas.php">
                            <i class="fas fa-calendar-check me-2"></i> Reservas
                        </a>
                        <a class="nav-link" href="calendario.php">
                            <i class="fas fa-calendar-alt me-2"></i> Calendario
                        </a>
                        <a class="nav-link" href="usuarios.php">
                            <i class="fas fa-users me-2"></i> Usuarios
                        </a>
                        <a class="nav-link" href="descuentos.php">
                            <i class="fas fa-percentage me-2"></i> Descuentos
                        </a>
                        <a class="nav-link" href="configuracion.php">
                            <i class="fas fa-cog me-2"></i> Configuración
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="../en/index.php" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i> Ver Sitio Web
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-calendar-check me-2"></i> Gestión de Reservas</h2>
                        <div class="d-flex align-items-center gap-3">
                            <button class="btn btn-outline-primary" onclick="location.reload()" title="Refrescar tabla">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                            <div class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('d/m/Y H:i'); ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($mensaje): ?>
                        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php echo htmlspecialchars($mensaje); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Filtros -->
                    <div class="card filter-card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-filter me-2"></i>Filtros
                            </h5>
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="estado" class="form-label">Estado</label>
                                    <select class="form-select" id="estado" name="estado">
                                        <option value="">Todos los estados</option>
                                        <option value="pendiente" <?php echo $estado_filtro === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="aprobada" <?php echo $estado_filtro === 'aprobada' ? 'selected' : ''; ?>>Aprobada</option>
                                        <option value="rechazada" <?php echo $estado_filtro === 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                                        <option value="cancelada" <?php echo $estado_filtro === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="fecha_desde" class="form-label">Fecha Desde</label>
                                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?php echo htmlspecialchars($fecha_desde); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-2"></i>Filtrar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Lista de Reservas -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i> Lista de Reservas
                                <span class="badge bg-primary ms-2"><?php echo count($reservas); ?> reservas</span>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Cliente</th>
                                            <th>Contacto</th>
                                            <th>Fechas</th>
                                            <th>Huéspedes</th>
                                            <th>Estado</th>
                                            <th>Total</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($reservas)): ?>
                                            <?php foreach ($reservas as $reserva): ?>
                                                <tr>
                                                    <td>#<?php echo $reserva['id_reserva']; ?></td>
                                                    <td>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($reserva['nombre'] . ' ' . $reserva['apellido']); ?></strong>
                                                            <?php if ($reserva['vive_palmira']): ?>
                                                                <span class="badge bg-info ms-1">Palmira</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <i class="fas fa-envelope me-1"></i>
                                                            <small><?php echo htmlspecialchars($reserva['correo']); ?></small>
                                                            <br>
                                                            <i class="fas fa-phone me-1"></i>
                                                            <small><?php echo htmlspecialchars($reserva['telefono']); ?></small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <strong>Entrada:</strong> <?php echo date('d/m/Y', strtotime($reserva['fecha_entrada'])); ?>
                                                            <br>
                                                            <strong>Salida:</strong> <?php echo date('d/m/Y', strtotime($reserva['fecha_salida'])); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <i class="fas fa-user me-1"></i>
                                                            <?php echo $reserva['num_adultos']; ?> adultos
                                                            <?php if ($reserva['num_ninos'] > 0): ?>
                                                                <br>
                                                                <i class="fas fa-child me-1"></i>
                                                                <?php echo $reserva['num_ninos']; ?> niños
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        // Lógica simplificada: solo mostrar un estado
                                                        $estado_class = '';
                                                        $estado_text = '';
                                                        $estado_icon = '';
                                                        
                                                        if ($reserva['estado'] == 'aprobada' && $reserva['estado_pago'] == 'pagada') {
                                                            // Si está aprobada Y pagada, mostrar "Pagada"
                                                            $estado_class = 'bg-success';
                                                            $estado_text = 'Pagada';
                                                            $estado_icon = 'fas fa-check-circle';
                                                        } elseif ($reserva['estado'] == 'aprobada' && $reserva['estado_pago'] == 'pendiente') {
                                                            // Si está aprobada pero pendiente de pago, mostrar "Aprobada"
                                                            $estado_class = 'bg-primary';
                                                            $estado_text = 'Aprobada';
                                                            $estado_icon = 'fas fa-check';
                                                        } else {
                                                            // Para otros estados, mostrar el estado de la reserva
                                                            switch ($reserva['estado']) {
                                                                case 'pendiente':
                                                                    $estado_class = 'bg-warning';
                                                                    $estado_text = 'Pendiente';
                                                                    $estado_icon = 'fas fa-clock';
                                                                    break;
                                                                case 'rechazada':
                                                                    $estado_class = 'bg-danger';
                                                                    $estado_text = 'Rechazada';
                                                                    $estado_icon = 'fas fa-times';
                                                                    break;
                                                                case 'cancelada':
                                                                    $estado_class = 'bg-secondary';
                                                                    $estado_text = 'Cancelada';
                                                                    $estado_icon = 'fas fa-ban';
                                                                    break;
                                                            }
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $estado_class; ?>">
                                                            <i class="<?php echo $estado_icon; ?> me-1"></i><?php echo $estado_text; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <strong>$<?php echo number_format($reserva['total'], 0, ',', '.'); ?> COP</strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?php echo $reserva['metodo_pago'] === 'efectivo' ? 'Efectivo' : 'Tarjeta'; ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-outline-info btn-action" 
                                                                    onclick="verDetalle(<?php echo $reserva['id_reserva']; ?>)" title="Ver Detalle">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <?php if ($reserva['estado'] == 'aprobada' && $reserva['estado_pago'] == 'pendiente'): ?>
                                                                <button type="button" class="btn btn-outline-success btn-action" 
                                                                        onclick="marcarComprobante(<?php echo $reserva['id_reserva']; ?>)" title="Marcar Comprobante Recibido">
                                                                    <i class="fas fa-dollar-sign"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <?php if ($reserva['estado'] == 'pendiente'): ?>
                                                                <a href="?action=approve&id=<?php echo $reserva['id_reserva']; ?>" 
                                                                   class="btn btn-outline-success btn-action" title="Aprobar"
                                                                   onclick="return confirm('¿Aprobar esta reserva?')">
                                                                    <i class="fas fa-check"></i>
                                                                </a>
                                                                <a href="?action=reject&id=<?php echo $reserva['id_reserva']; ?>" 
                                                                   class="btn btn-outline-danger btn-action" title="Rechazar"
                                                                   onclick="return confirm('¿Rechazar esta reserva?')">
                                                                    <i class="fas fa-times"></i>
                                                                </a>
                                                            <?php elseif ($reserva['estado'] == 'aprobada'): ?>
                                                                <a href="?action=cancel&id=<?php echo $reserva['id_reserva']; ?>" 
                                                                   class="btn btn-outline-warning btn-action" title="Cancelar"
                                                                   onclick="return confirm('¿Cancelar esta reserva?')">
                                                                    <i class="fas fa-ban"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                                    <br>No se encontraron reservas
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalle de Reserva -->
    <div class="modal fade" id="detalleReservaModal" tabindex="-1" aria-labelledby="detalleReservaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detalleReservaModalLabel">
                        <i class="fas fa-calendar-check me-2"></i>Detalle de Reserva
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detalleReservaContent">
                        <!-- El contenido se carga dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Pago -->
    <div class="modal fade" id="comprobanteModal" tabindex="-1" aria-labelledby="comprobanteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="comprobanteModalLabel">
                        <i class="fas fa-dollar-sign me-2"></i>Confirmar Pago
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reservaIdComprobante" name="reserva_id">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Instrucciones:</strong> El cliente debe enviar el comprobante de pago al correo: <strong>jose.cardenas01@uceva.edu.co</strong>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notasPago" class="form-label">
                            <i class="fas fa-sticky-note me-2"></i>Notas sobre el Pago (Opcional)
                        </label>
                        <textarea class="form-control" id="notasPago" name="notas" rows="3" placeholder="Información adicional sobre el pago (método, fecha, etc.)..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Importante:</strong> Al confirmar el pago, la reserva se marcará como PAGADA y se enviará un email de confirmación al cliente.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnConfirmarPago" onclick="confirmarPago()">
                        <i class="fas fa-check-circle me-2"></i>Confirmar Pago
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verDetalle(id) {
            // Mostrar loading
            document.getElementById('detalleReservaContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando detalles de la reserva...</p>
                </div>
            `;
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('detalleReservaModal'));
            modal.show();
            
            // Cargar datos de la reserva
            fetch(`get_reserva_detalle.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarDetalleReserva(data.reserva);
                    } else {
                        document.getElementById('detalleReservaContent').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error al cargar los detalles: ${data.message}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('detalleReservaContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error de conexión: ${error.message}
                        </div>
                    `;
                });
        }
        
        function mostrarDetalleReserva(reserva) {
            const estadoClass = {
                'pendiente': 'bg-warning',
                'aprobada': 'bg-success',
                'rechazada': 'bg-danger',
                'cancelada': 'bg-secondary'
            }[reserva.estado] || 'bg-secondary';
            
            const metodoPago = reserva.metodo_pago === 'efectivo' ? 'Efectivo' : 'Tarjeta de Crédito';
            const vivePalmira = reserva.vive_palmira == 1 ? 'Sí' : 'No';
            
            document.getElementById('detalleReservaContent').innerHTML = `
                <div class="row">
                    <!-- Información General -->
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-user me-2"></i>Información del Cliente
                        </h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Nombre:</strong></td>
                                <td>${reserva.nombre} ${reserva.apellido}</td>
                            </tr>
                            <tr>
                                <td><strong>Correo:</strong></td>
                                <td>${reserva.correo}</td>
                            </tr>
                            <tr>
                                <td><strong>Teléfono:</strong></td>
                                <td>${reserva.telefono}</td>
                            </tr>
                            <tr>
                                <td><strong>Fecha de Nacimiento:</strong></td>
                                <td>${reserva.fecha_nacimiento || 'No especificada'}</td>
                            </tr>
                            <tr>
                                <td><strong>Vive en Palmira:</strong></td>
                                <td>
                                    ${vivePalmira}
                                    ${reserva.vive_palmira == 1 ? '<span class="badge bg-info ms-2">Transporte gratis</span>' : ''}
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Información de la Reserva -->
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-calendar me-2"></i>Detalles de la Reserva
                        </h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>ID Reserva:</strong></td>
                                <td>#${reserva.id_reserva}</td>
                            </tr>
                            <tr>
                                <td><strong>Estado:</strong></td>
                                <td><span class="badge ${estadoClass}">${reserva.estado.charAt(0).toUpperCase() + reserva.estado.slice(1)}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Fecha de Entrada:</strong></td>
                                <td>${new Date(reserva.fecha_entrada).toLocaleDateString('es-CO')}</td>
                            </tr>
                            <tr>
                                <td><strong>Fecha de Salida:</strong></td>
                                <td>${new Date(reserva.fecha_salida).toLocaleDateString('es-CO')}</td>
                            </tr>
                            <tr>
                                <td><strong>Adultos:</strong></td>
                                <td>${reserva.num_adultos}</td>
                            </tr>
                            <tr>
                                <td><strong>Niños:</strong></td>
                                <td>${reserva.num_ninos}</td>
                            </tr>
                            <tr>
                                <td><strong>Método de Pago:</strong></td>
                                <td>${metodoPago}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <hr>
                
                <!-- Información Financiera -->
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-dollar-sign me-2"></i>Información Financiera
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Costo Base:</strong></td>
                                        <td>$${parseFloat(reserva.costo_base).toLocaleString('es-CO')} COP</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Descuento Fidelización:</strong></td>
                                        <td>$${parseFloat(reserva.descuento_fidelizacion || 0).toLocaleString('es-CO')} COP</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Descuento Cumpleaños:</strong></td>
                                        <td>$${parseFloat(reserva.descuento_cumpleanios || 0).toLocaleString('es-CO')} COP</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Descuento Promocional:</strong></td>
                                        <td>$${parseFloat(reserva.descuento_promocional || 0).toLocaleString('es-CO')} COP</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td><strong>Total:</strong></td>
                                        <td><strong>$${parseFloat(reserva.total).toLocaleString('es-CO')} COP</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${reserva.comentario ? `
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-comment me-2"></i>Comentarios
                        </h6>
                        <div class="alert alert-light">
                            ${reserva.comentario}
                        </div>
                    </div>
                </div>
                ` : ''}
                
                ${reserva.comprobante_pago ? `
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-dollar-sign me-2"></i>Comprobante de Pago
                        </h6>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Pago Confirmado:</strong> ${reserva.fecha_pago_confirmado ? new Date(reserva.fecha_pago_confirmado).toLocaleString('es-CO') : 'Fecha no disponible'}
                        </div>
                        <div class="text-center">
                            <a href="view_comprobante.php?id=${reserva.id_reserva}&file=${reserva.comprobante_pago}" target="_blank" class="btn btn-outline-primary">
                                <i class="fas fa-eye me-2"></i>Ver Comprobante
                            </a>
                        </div>
                    </div>
                </div>
                ` : ''}
                
                <hr>
                
                <!-- Información del Sistema -->
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-info-circle me-2"></i>Información del Sistema
                        </h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Fecha de Creación:</strong></td>
                                <td>${new Date(reserva.creado_en).toLocaleString('es-CO')}</td>
                            </tr>
                            <tr>
                                <td><strong>Última Actualización:</strong></td>
                                <td>${new Date(reserva.actualizado_en).toLocaleString('es-CO')}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            `;
        }
        
        function marcarComprobante(id) {
            document.getElementById('reservaIdComprobante').value = id;
            document.getElementById('notasPago').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('comprobanteModal'));
            modal.show();
        }
        
        function confirmarPago() {
            const reservaId = document.getElementById('reservaIdComprobante').value;
            const notas = document.getElementById('notasPago').value;
            
            if (!reservaId) {
                alert('Error: ID de reserva no encontrado');
                return;
            }
            
            // Mostrar loading
            const submitBtn = document.getElementById('btnConfirmarPago');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
            submitBtn.disabled = true;
            
            // Crear datos para enviar
            const formData = new FormData();
            formData.append('reserva_id', reservaId);
            formData.append('notas', notas);
            formData.append('marcar_pagada', '1');
            
            fetch('marcar_pagada.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Reserva marcada como PAGADA exitosamente');
                    // Cerrar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('comprobanteModal'));
                    if (modal) {
                        modal.hide();
                    }
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión: ' + error.message);
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }
    </script>
</body>
</html>
