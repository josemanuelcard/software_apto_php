<?php
/**
 * Panel de Administración - Sistema de Reservas
 * My Suite In Cartagena
 */

session_start();

// Verificar si el usuario está logueado como admin
if ((isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) ||
    (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true && 
     isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin')) {
    // Usuario logueado correctamente, continuar
} else {
    // No está logueado, redirigir al login principal
    header('Location: ../en/login.php');
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
                        <a class="nav-link" href="#" onclick="showPalmiraFilter()">
                            <i class="fas fa-map-marker-alt me-2"></i> Clientes Palmira
                        </a>
                        <a class="nav-link" href="#" onclick="showDateBlocking()">
                            <i class="fas fa-calendar-times me-2"></i> Bloquear Fechas
                        </a>
                        <a class="nav-link" href="#" onclick="showClientManagement()">
                            <i class="fas fa-users me-2"></i> Usuarios Registrados
                        </a>
                        <a class="nav-link" href="descuentos.php">
                            <i class="fas fa-percentage me-2"></i> Descuentos
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="../en/index.php" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i> Ver Sitio Web
                        </a>
                        <a class="nav-link" href="../en/logout.php">
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
                        <div>
                            <h2><i class="fas fa-tachometer-alt me-2"></i> Dashboard</h2>
                            <p class="text-muted mb-0">
                                <i class="fas fa-user me-1"></i>
                                Bienvenido, <?php 
                                    if (isset($_SESSION['admin_nombre'])) {
                                        echo $_SESSION['admin_nombre'];
                                    } elseif (isset($_SESSION['user_nombre'])) {
                                        echo $_SESSION['user_nombre'];
                                    } else {
                                        echo 'Administrador';
                                    }
                                ?>
                            </p>
                        </div>
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

    <!-- Modales para nuevas funcionalidades -->
    
    <!-- Modal Clientes Palmira -->
    <div class="modal fade" id="palmiraModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Clientes que viven en Palmira
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="palmiraTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Fecha Registro</th>
                                    <th>Reservas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargarán via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Bloqueo de Fechas -->
    <div class="modal fade" id="dateBlockingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-times me-2"></i>
                        Bloquear Fechas Manualmente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="dateBlockingForm">
                        <div class="mb-3">
                            <label for="blockStartDate" class="form-label">Fecha de Inicio</label>
                            <input type="date" class="form-control" id="blockStartDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="blockEndDate" class="form-label">Fecha de Fin</label>
                            <input type="date" class="form-control" id="blockEndDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="blockReason" class="form-label">Motivo del Bloqueo</label>
                            <select class="form-control" id="blockReason" required>
                                <option value="">Seleccionar motivo...</option>
                                <option value="mantenimiento">Mantenimiento</option>
                                <option value="uso_interno">Uso Interno</option>
                                <option value="evento_especial">Evento Especial</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="blockDescription" class="form-label">Descripción (opcional)</label>
                            <textarea class="form-control" id="blockDescription" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="blockDates()">
                        <i class="fas fa-lock me-2"></i>Bloquear Fechas
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Gestión de Clientes -->
    <div class="modal fade" id="clientManagementModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-users me-2"></i>
                        Usuarios Registrados
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="clientSearch" placeholder="Buscar cliente por nombre o email...">
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-primary" onclick="searchClients()">
                                <i class="fas fa-search me-2"></i>Buscar
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped" id="clientsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Fecha Nacimiento</th>
                                    <th>Reservas</th>
                                    <th>Descuentos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargarán via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript para las nuevas funcionalidades -->
    <script>
        // Esperar a que el DOM esté listo
        $(document).ready(function() {
            console.log('DOM cargado, funciones disponibles');
        });
        
        // Mostrar modal de clientes Palmira
        function showPalmiraFilter() {
            console.log('Ejecutando showPalmiraFilter');
            $('#palmiraModal').modal('show');
            loadPalmiraClients();
        }

        // Mostrar modal de bloqueo de fechas
        function showDateBlocking() {
            console.log('Ejecutando showDateBlocking');
            $('#dateBlockingModal').modal('show');
        }

        // Mostrar modal de gestión de clientes
        function showClientManagement() {
            console.log('Ejecutando showClientManagement');
            $('#clientManagementModal').modal('show');
            loadAllClients();
        }

        // Cargar clientes de Palmira
        function loadPalmiraClients() {
            fetch('get_palmira_clients.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#palmiraTable tbody');
                    tbody.innerHTML = '';
                    
                    if (data.success && data.clients.length > 0) {
                        data.clients.forEach(client => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${client.id_usuario}</td>
                                <td>${client.nombre} ${client.apellido}</td>
                                <td>${client.correo}</td>
                                <td>${client.telefono}</td>
                                <td>${client.fecha_registro}</td>
                                <td>${client.total_reservas}</td>
                            `;
                            tbody.appendChild(row);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay clientes registrados en Palmira</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.querySelector('#palmiraTable tbody').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error al cargar los datos</td></tr>';
                });
        }

        // Cargar todos los clientes
        function loadAllClients() {
            fetch('get_all_clients.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#clientsTable tbody');
                    tbody.innerHTML = '';
                    
                    if (data.success && data.clients.length > 0) {
                        data.clients.forEach(client => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${client.id_usuario}</td>
                                <td>${client.nombre} ${client.apellido}</td>
                                <td>${client.correo}</td>
                                <td>${client.telefono}</td>
                                <td>${client.fecha_nacimiento || 'No registrada'}</td>
                                <td>${client.total_reservas}</td>
                                <td>
                                    <span class="badge bg-success">Fidelidad: 5%</span>
                                    <span class="badge bg-warning">Cumpleaños: 30%</span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewClientDetails(${client.id_usuario})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay clientes registrados</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.querySelector('#clientsTable tbody').innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error al cargar los datos</td></tr>';
                });
        }

        // Bloquear fechas
        function blockDates() {
            const startDate = document.getElementById('blockStartDate').value;
            const endDate = document.getElementById('blockEndDate').value;
            const reason = document.getElementById('blockReason').value;
            const description = document.getElementById('blockDescription').value;

            if (!startDate || !endDate || !reason) {
                alert('Por favor complete todos los campos obligatorios');
                return;
            }

            const formData = new FormData();
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);
            formData.append('reason', reason);
            formData.append('description', description);

            fetch('block_dates.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Fechas bloqueadas exitosamente');
                    $('#dateBlockingModal').modal('hide');
                    document.getElementById('dateBlockingForm').reset();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al bloquear las fechas');
            });
        }

        // Buscar clientes
        function searchClients() {
            const searchTerm = document.getElementById('clientSearch').value;
            if (!searchTerm.trim()) {
                loadAllClients();
                return;
            }

            fetch(`search_clients.php?q=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#clientsTable tbody');
                    tbody.innerHTML = '';
                    
                    if (data.success && data.clients.length > 0) {
                        data.clients.forEach(client => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${client.id_usuario}</td>
                                <td>${client.nombre} ${client.apellido}</td>
                                <td>${client.correo}</td>
                                <td>${client.telefono}</td>
                                <td>${client.fecha_nacimiento || 'No registrada'}</td>
                                <td>${client.total_reservas}</td>
                                <td>
                                    <span class="badge bg-success">Fidelidad: 5%</span>
                                    <span class="badge bg-warning">Cumpleaños: 30%</span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewClientDetails(${client.id_usuario})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No se encontraron clientes</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.querySelector('#clientsTable tbody').innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error al buscar clientes</td></tr>';
                });
        }

        // Ver detalles del cliente
        function viewClientDetails(clientId) {
            console.log('Cargando detalles del cliente ID:', clientId);
            
            // Crear modal dinámico
            const modalHtml = `
                <div class="modal fade" id="clientDetailsModal" tabindex="-1">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-user me-2"></i>
                                    Detalles del Cliente
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="clientDetailsContent">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-2">Cargando detalles del cliente...</p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remover modal anterior si existe
            const existingModal = document.getElementById('clientDetailsModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Agregar modal al DOM
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('clientDetailsModal'));
            modal.show();
            
            // Cargar detalles del cliente
            loadClientDetails(clientId);
        }
        
        // Cargar detalles del cliente
        function loadClientDetails(clientId) {
            fetch(`get_client_details.php?id=${clientId}`)
                .then(response => response.json())
                .then(data => {
                    const content = document.getElementById('clientDetailsContent');
                    
                    if (data.success) {
                        const client = data.client;
                        const reservations = data.reservations || [];
                        
                        content.innerHTML = `
                            <div class="row mb-4">
                                <div class="col-lg-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Información Personal</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td class="fw-bold" style="width: 40%;">ID:</td>
                                                    <td><span class="badge bg-secondary">${client.id_usuario}</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Nombre:</td>
                                                    <td>${client.nombre} ${client.apellido}</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Email:</td>
                                                    <td><a href="mailto:${client.correo}" class="text-decoration-none">${client.correo}</a></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Teléfono:</td>
                                                    <td>${client.telefono || '<span class="text-muted">No registrado</span>'}</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Ciudad:</td>
                                                    <td><span class="badge bg-info">${client.ciudad || 'No registrada'}</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Fecha Nacimiento:</td>
                                                    <td>${client.fecha_nacimiento || '<span class="text-muted">No registrada</span>'}</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Fecha Registro:</td>
                                                    <td>${client.fecha_registro}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estadísticas</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row text-center mb-3">
                                                <div class="col-4">
                                                    <div class="border rounded p-2">
                                                        <h4 class="text-primary mb-0">${client.total_reservas}</h4>
                                                        <small class="text-muted">Total Reservas</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="border rounded p-2">
                                                        <h4 class="text-success mb-0">${client.reservas_confirmadas || 0}</h4>
                                                        <small class="text-muted">Confirmadas</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="border rounded p-2">
                                                        <h4 class="text-warning mb-0">${client.reservas_pendientes || 0}</h4>
                                                        <small class="text-muted">Pendientes</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <h6 class="fw-bold mb-3">Descuentos Aplicables:</h6>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <span class="badge bg-success fs-6">Fidelidad: 5%</span>
                                                <span class="badge bg-warning fs-6">Cumpleaños: 30%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Historial de Reservas</h6>
                                        </div>
                                        <div class="card-body">
                                            ${reservations.length > 0 ? `
                                                <div class="table-responsive">
                                                    <table class="table table-hover">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>ID Reserva</th>
                                                                <th>Fecha Entrada</th>
                                                                <th>Fecha Salida</th>
                                                                <th>Estado</th>
                                                                <th>Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            ${reservations.map(res => `
                                                                <tr>
                                                                    <td><span class="badge bg-secondary">${res.id_reserva}</span></td>
                                                                    <td>${res.fecha_entrada}</td>
                                                                    <td>${res.fecha_salida}</td>
                                                                    <td><span class="badge ${res.estado === 'confirmada' ? 'bg-success' : res.estado === 'pendiente' ? 'bg-warning' : 'bg-secondary'}">${res.estado}</span></td>
                                                                    <td class="fw-bold">$${parseFloat(res.total).toLocaleString('es-CO')} COP</td>
                                                                </tr>
                                                            `).join('')}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            ` : '<div class="text-center py-4"><i class="fas fa-calendar-times fa-3x text-muted mb-3"></i><p class="text-muted fs-5">No hay reservas registradas</p></div>'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        content.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error al cargar los detalles: ${data.message}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('clientDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error al cargar los detalles del cliente
                        </div>
                    `;
                });
        }
    </script>
</body>
</html>
