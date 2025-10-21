<?php
/**
 * Panel de Administración - Sistema de Reservas
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

// Obtener estadísticas
$estadisticas = getEstadisticasReservas();
$reservas_recientes = getReservasRecientes(10);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - My Suite In Cartagena</title>
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
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
        }
        .stat-card .card-body {
            padding: 2rem;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
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
                        Admin Panel
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="reservas.php">
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
                        <h2><i class="fas fa-tachometer-alt me-2"></i> Dashboard</h2>
                        <div class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('d/m/Y H:i'); ?>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                                    <div class="stat-number"><?php echo $estadisticas['total_reservas']; ?></div>
                                    <div>Total Reservas</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-clock fa-2x mb-2"></i>
                                    <div class="stat-number"><?php echo $estadisticas['reservas_pendientes']; ?></div>
                                    <div>Pendientes</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <div class="stat-number"><?php echo $estadisticas['reservas_aprobadas']; ?></div>
                                    <div>Aprobadas</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                                    <div class="stat-number">$<?php echo number_format($estadisticas['ingresos_totales'], 0, ',', '.'); ?></div>
                                    <div>Ingresos Totales</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficas y Reportes -->
                    <div class="row mb-4">
                        <!-- Gráfica de Reservas por Estado -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-pie me-2"></i> Reservas por Estado
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="reservasEstadoChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfica de Ingresos Mensuales -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-line me-2"></i> Ingresos Mensuales
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="ingresosMensualesChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reportes Adicionales -->
                    <div class="row mb-4">
                        <!-- Métodos de Pago -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-credit-card me-2"></i> Métodos de Pago
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="metodosPagoChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Reservas por Mes -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-calendar-alt me-2"></i> Reservas por Mes
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="reservasMesChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reservas Recientes -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-list me-2"></i> Reservas Recientes
                                    </h5>
                                    <a href="reservas.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i> Ver Todas
                                    </a>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Cliente</th>
                                                    <th>Fechas</th>
                                                    <th>Estado</th>
                                                    <th>Total</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($reservas_recientes)): ?>
                                                    <?php foreach ($reservas_recientes as $reserva): ?>
                                                        <tr>
                                                            <td>#<?php echo $reserva['id_reserva']; ?></td>
                                                            <td>
                                                                <div>
                                                                    <strong><?php echo htmlspecialchars($reserva['nombre'] . ' ' . $reserva['apellido']); ?></strong>
                                                                    <br>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($reserva['correo']); ?></small>
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
                                                                <?php
                                                                $estado_class = '';
                                                                switch ($reserva['estado']) {
                                                                    case 'pendiente':
                                                                        $estado_class = 'bg-warning';
                                                                        break;
                                                                    case 'aprobada':
                                                                        $estado_class = 'bg-success';
                                                                        break;
                                                                    case 'rechazada':
                                                                        $estado_class = 'bg-danger';
                                                                        break;
                                                                    case 'cancelada':
                                                                        $estado_class = 'bg-secondary';
                                                                        break;
                                                                }
                                                                ?>
                                                                <span class="badge <?php echo $estado_class; ?>">
                                                                    <?php echo ucfirst($reserva['estado']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <strong>$<?php echo number_format($reserva['total'], 0, ',', '.'); ?> COP</strong>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <a href="reservas.php?action=view&id=<?php echo $reserva['id_reserva']; ?>" 
                                                                       class="btn btn-outline-primary btn-action" title="Ver">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <?php if ($reserva['estado'] == 'pendiente'): ?>
                                                                        <a href="reservas.php?action=approve&id=<?php echo $reserva['id_reserva']; ?>" 
                                                                           class="btn btn-outline-success btn-action" title="Aprobar">
                                                                            <i class="fas fa-check"></i>
                                                                        </a>
                                                                        <a href="reservas.php?action=reject&id=<?php echo $reserva['id_reserva']; ?>" 
                                                                           class="btn btn-outline-danger btn-action" title="Rechazar">
                                                                            <i class="fas fa-times"></i>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted py-4">
                                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                                            <br>No hay reservas recientes
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Datos para las gráficas
        const estadisticas = <?php echo json_encode($estadisticas); ?>;
        
        // Gráfica de Reservas por Estado
        const ctxReservasEstado = document.getElementById('reservasEstadoChart').getContext('2d');
        new Chart(ctxReservasEstado, {
            type: 'doughnut',
            data: {
                labels: ['Pendientes', 'Aprobadas', 'Rechazadas', 'Canceladas'],
                datasets: [{
                    data: [
                        estadisticas.reservas_pendientes || 0,
                        estadisticas.reservas_aprobadas || 0,
                        estadisticas.reservas_rechazadas || 0,
                        estadisticas.reservas_canceladas || 0
                    ],
                    backgroundColor: [
                        '#ffc107',
                        '#28a745',
                        '#dc3545',
                        '#6c757d'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfica de Ingresos Mensuales (últimos 6 meses)
        const ctxIngresos = document.getElementById('ingresosMensualesChart').getContext('2d');
        new Chart(ctxIngresos, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [{
                    label: 'Ingresos (COP)',
                    data: [0, 0, 0, 0, 0, estadisticas.ingresos_totales || 0],
                    borderColor: '#1e3c72',
                    backgroundColor: 'rgba(30, 60, 114, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString('es-CO');
                            }
                        }
                    }
                }
            }
        });

        // Gráfica de Métodos de Pago
        const ctxMetodosPago = document.getElementById('metodosPagoChart').getContext('2d');
        new Chart(ctxMetodosPago, {
            type: 'bar',
            data: {
                labels: ['Tarjeta de Crédito', 'Efectivo'],
                datasets: [{
                    label: 'Cantidad de Reservas',
                    data: [estadisticas.metodo_tarjeta || 0, estadisticas.metodo_efectivo || 0],
                    backgroundColor: [
                        '#1e3c72',
                        '#2a5298'
                    ],
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfica de Reservas por Mes
        const ctxReservasMes = document.getElementById('reservasMesChart').getContext('2d');
        new Chart(ctxReservasMes, {
            type: 'bar',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [{
                    label: 'Reservas',
                    data: [0, 0, 0, 0, 0, estadisticas.total_reservas || 0],
                    backgroundColor: '#2a5298',
                    borderColor: '#1e3c72',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
