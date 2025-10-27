<?php
/**
 * Calendario - Panel de Administración
 * My Suite In Cartagena
 */

session_start();

// Verificar si el usuario está logueado como admin
if (!((isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) ||
    (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true && 
     isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin'))) {
    header('Location: ../en/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../includes/functions.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario - Panel de Administración</title>
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
        
        /* Estilos del Calendario Admin */
        .calendar-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e9ecef;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .calendar-day {
            background: white;
            min-height: 80px;
            padding: 8px;
            border: 1px solid #e9ecef;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1;
            pointer-events: auto;
        }
        
        .calendar-day:hover {
            background: #f8f9fa;
            transform: scale(1.02);
        }
        
        .calendar-day.other-month {
            background: #f8f9fa;
            color: #adb5bd;
        }
        
        .calendar-day.today {
            background: #e3f2fd;
        }
        
        .calendar-day.has-reservation {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        .calendar-day.blocked {
            background: #000000 !important;
            color: #ffffff !important;
            border-left: 4px solid #000000 !important;
            font-weight: bold !important;
        }
        
        .blocked-info {
            color: #ffffff !important;
            font-size: 10px !important;
            text-align: center !important;
            margin-top: 2px !important;
        }
        
        .calendar-day.pending {
            background: #ffc107 !important;
            color: #000000 !important;  
            border-left: 4px solid #ffc107 !important;
        }
        
        .calendar-day.approved {
            background: #007bff !important;
            color: #000000 !important;
            border-left: 4px solid #007bff !important;
        }
        
        .calendar-day.paid {
            background: #28a745 !important;
            color: #000000 !important;
            border-left: 4px solid #28a745 !important;
        }
        
        .day-number {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 4px;
            pointer-events: none;
        }
        
        .reservation-info {
            font-size: 10px;
            margin: 2px 0;
            padding: 2px 4px;
            border-radius: 3px;
            color: white;
            pointer-events: none;
        }
        
        .reservation-info.pending {
            background: #ffc107;
        }
        
        .reservation-info.approved {
            background: #28a745;
        }
        
        .reservation-info.rejected {
            background: #dc3545;
        }
        
        .reservation-info.paid {
            background: #17a2b8;
        }
        
        .reservation-info.cancelled {
            background: #6c757d;
        }
        
        .blocked-info {
            font-size: 10px;
            background: #dc3545;
            color: white;
            padding: 2px 4px;
            border-radius: 3px;
            margin: 2px 0;
        }
        
        .calendar-legend {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .stats-card {
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
        }
        
        /* Estilos para el botón de refresh */
        #refreshCalendar {
            transition: all 0.3s ease;
        }
        
        #refreshCalendar:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        #refreshCalendar:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="reservas.php">
                            <i class="fas fa-calendar-check me-2"></i> Reservas
                        </a>
                        <a class="nav-link active" href="calendario.php">
                            <i class="fas fa-calendar-alt me-2"></i> Calendario
                        </a>
                        <a class="nav-link" href="#" onclick="showPalmiraFilter()">
                            <i class="fas fa-map-marker-alt me-2"></i> Clientes Palmira
                        </a>
                        <a class="nav-link" href="#" onclick="showDateBlocking()">
                            <i class="fas fa-calendar-times me-2"></i> Bloquear Fechas
                        </a>
                        <a class="nav-link" href="#" onclick="showClientManagement()">
                            <i class="fas fa-users me-2"></i> Clientes Registrados
                        </a>
                        <a class="nav-link" href="descuentos.php">
                            <i class="fas fa-percentage me-2"></i> Descuentos
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
                        <h2><i class="fas fa-calendar-alt me-2"></i> Calendario de Reservas</h2>
                        <div class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('d/m/Y H:i'); ?>
                        </div>
                    </div>

                    <!-- Contenido del calendario -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-calendar-alt me-2"></i> Calendario de Reservas
                                    </h5>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-success" id="refreshCalendar" title="Actualizar calendario">
                                            <i class="fas fa-sync-alt"></i> Actualizar
                                        </button>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-primary" id="prevMonth">
                                                <i class="fas fa-chevron-left"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" id="currentMonth">
                                                <span id="monthYear"></span>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" id="nextMonth">
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Calendario -->
                                    <div id="adminCalendar" class="calendar-container">
                                        <!-- El calendario se generará dinámicamente -->
                                    </div>
                                    
                                    <!-- Leyenda -->
                                    <div class="calendar-legend mt-3">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h6>Estado de Reservas:</h6>
                                                <div class="d-flex flex-wrap gap-3">
                                                    <span class="badge" style="background-color:rgba(118, 112, 112, 0.69); color: #ffffff;">Bloqueado</span>
                                                    <span class="badge" style="background-color: #ffc107; color: #000000;">Pendiente</span>
                                                    <span class="badge" style="background-color:rgb(0, 122, 251); color: #000000;">Aprobada</span>
                                                    <span class="badge" style="background-color: #28a745; color: #000000;">Pagada</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estadísticas del mes -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Reservas del Mes</h6>
                                            <h3 id="monthReservations">0</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-calendar-check fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Noches Ocupadas</h6>
                                            <h3 id="monthNights">0</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-bed fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Fechas Bloqueadas</h6>
                                            <h3 id="monthBlocked">0</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-ban fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Ocupación %</h6>
                                            <h3 id="monthOccupancy">0%</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-chart-pie fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                        <!-- Tipo de bloqueo -->
                        <div class="mb-3">
                            <label class="form-label">Tipo de Bloqueo</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="blockType" id="blockSingle" value="single" checked>
                                <label class="form-check-label" for="blockSingle">
                                    <i class="fas fa-calendar-day me-2"></i>Bloquear un solo día
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="blockType" id="blockRange" value="range">
                                <label class="form-check-label" for="blockRange">
                                    <i class="fas fa-calendar-week me-2"></i>Bloquear rango de fechas
                                </label>
                            </div>
                        </div>

                        <!-- Fecha única -->
                        <div class="mb-3" id="singleDateGroup">
                            <label for="blockSingleDate" class="form-label">Fecha a Bloquear</label>
                            <input type="date" class="form-control" id="blockSingleDate">
                        </div>

                        <!-- Rango de fechas -->
                        <div class="mb-3" id="rangeDateGroup" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="blockStartDate" class="form-label">Fecha de Inicio</label>
                                    <input type="date" class="form-control" id="blockStartDate">
                                </div>
                                <div class="col-md-6">
                                    <label for="blockEndDate" class="form-label">Fecha de Fin</label>
                                    <input type="date" class="form-control" id="blockEndDate">
                                </div>
                            </div>
                        </div>

                        <!-- Motivo del bloqueo -->
                        <div class="mb-3">
                            <label for="blockReason" class="form-label">Motivo del Bloqueo</label>
                            <select class="form-control" id="blockReason" required>
                                <option value="">Seleccionar motivo...</option>
                                <option value="mantenimiento">🔧 Mantenimiento</option>
                                <option value="uso_interno">🏢 Uso Interno</option>
                                <option value="evento_especial">🎉 Evento Especial</option>
                                <option value="limpieza">🧹 Limpieza Profunda</option>
                                <option value="reparacion">🔨 Reparaciones</option>
                                <option value="otro">📝 Otro</option>
                            </select>
                        </div>

                        <!-- Descripción -->
                        <div class="mb-3">
                            <label for="blockDescription" class="form-label">Descripción (opcional)</label>
                            <textarea class="form-control" id="blockDescription" rows="3" 
                                placeholder="Detalles adicionales sobre el bloqueo..."></textarea>
                        </div>

                        <!-- Información adicional -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Información:</strong> No se pueden bloquear fechas que ya tienen reservas aprobadas.
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

        // Manejar cambio de tipo de bloqueo
        function handleBlockTypeChange() {
            const blockType = document.querySelector('input[name="blockType"]:checked').value;
            const singleDateGroup = document.getElementById('singleDateGroup');
            const rangeDateGroup = document.getElementById('rangeDateGroup');
            
            if (blockType === 'single') {
                singleDateGroup.style.display = 'block';
                rangeDateGroup.style.display = 'none';
                document.getElementById('blockSingleDate').required = true;
                document.getElementById('blockStartDate').required = false;
                document.getElementById('blockEndDate').required = false;
            } else {
                singleDateGroup.style.display = 'none';
                rangeDateGroup.style.display = 'block';
                document.getElementById('blockSingleDate').required = false;
                document.getElementById('blockStartDate').required = true;
                document.getElementById('blockEndDate').required = true;
            }
        }

        // Bloquear fechas
        function blockDates() {
            const blockType = document.querySelector('input[name="blockType"]:checked').value;
            const reason = document.getElementById('blockReason').value;
            const description = document.getElementById('blockDescription').value;
            
            let startDate, endDate;
            
            if (blockType === 'single') {
                startDate = document.getElementById('blockSingleDate').value;
                endDate = startDate; // Para un solo día, usar la misma fecha
            } else {
                startDate = document.getElementById('blockStartDate').value;
                endDate = document.getElementById('blockEndDate').value;
            }

            // Validaciones
            if (!startDate || !reason) {
                alert('Por favor complete todos los campos obligatorios');
                return;
            }

            if (blockType === 'range' && !endDate) {
                alert('Para bloquear un rango debe proporcionar fecha de fin');
                return;
            }

            if (blockType === 'range' && new Date(startDate) > new Date(endDate)) {
                alert('La fecha de inicio debe ser anterior a la fecha de fin');
                return;
            }

            if (new Date(startDate) < new Date()) {
                alert('No se pueden bloquear fechas pasadas');
                return;
            }

            const formData = new FormData();
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);
            formData.append('reason', reason);
            formData.append('description', description);
            formData.append('block_type', blockType);

            // Mostrar loading
            const submitBtn = document.querySelector('button[onclick="blockDates()"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Bloqueando...';
            submitBtn.disabled = true;

            fetch('block_dates.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito con más detalles
                    const message = blockType === 'single' 
                        ? `✅ Fecha bloqueada exitosamente: ${new Date(startDate).toLocaleDateString('es-CO')}`
                        : `✅ Rango bloqueado exitosamente: ${new Date(startDate).toLocaleDateString('es-CO')} - ${new Date(endDate).toLocaleDateString('es-CO')}`;
                    
                    alert(message);
                    $('#dateBlockingModal').modal('hide');
                    document.getElementById('dateBlockingForm').reset();
                    
                    // Recargar calendario si está en la página del calendario
                    if (typeof loadCalendarData === 'function') {
                        loadCalendarData();
                    }
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error al bloquear las fechas');
            })
            .finally(() => {
                // Restaurar botón
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
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

        // JavaScript del Calendario Admin
        let currentDate = new Date(2025, 9, 1); // Octubre 2025 (mes 9 = octubre)
        let reservations = [];
        let blockedDates = [];

        // Inicializar calendario
        document.addEventListener('DOMContentLoaded', function() {
            loadCalendarData();
            setupCalendarEvents();
            setupBlockDateEvents();
        });

        // Configurar eventos para bloqueo de fechas
        function setupBlockDateEvents() {
            // Event listeners para cambio de tipo de bloqueo
            const blockTypeRadios = document.querySelectorAll('input[name="blockType"]');
            blockTypeRadios.forEach(radio => {
                radio.addEventListener('change', handleBlockTypeChange);
            });
        }

        // Cargar datos del calendario
        function loadCalendarData() {
            fetch('get_calendar_data.php?month=' + currentDate.getMonth() + '&year=' + currentDate.getFullYear())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        reservations = data.reservations || [];
                        blockedDates = data.blocked_dates || [];
                        renderCalendar();
                        updateStats();
                    }
                })
                .catch(error => {
                    console.error('Error al cargar datos del calendario:', error);
                });
        }

        // Renderizar calendario
        function renderCalendar() {
            const calendar = document.getElementById('adminCalendar');
            const monthYear = document.getElementById('monthYear');
            
            // Actualizar título del mes
            const monthNames = [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ];
            monthYear.textContent = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
            
            // Limpiar calendario
            calendar.innerHTML = '';
            
            // Crear grid del calendario
            const calendarGrid = document.createElement('div');
            calendarGrid.className = 'calendar-grid';
            
            // Días de la semana
            const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
            dayNames.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'calendar-day text-center fw-bold bg-light';
                dayHeader.textContent = day;
                dayHeader.style.cursor = 'default';
                calendarGrid.appendChild(dayHeader);
            });
            
            // Obtener primer día del mes
            const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
            const startingDayOfWeek = firstDay.getDay();
            const daysInMonth = lastDay.getDate();
            
            // Días del mes anterior
            const prevMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 0);
            for (let i = startingDayOfWeek - 1; i >= 0; i--) {
                const day = document.createElement('div');
                day.className = 'calendar-day other-month';
                day.textContent = prevMonth.getDate() - i;
                calendarGrid.appendChild(day);
            }
            
            // Días del mes actual
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = document.createElement('div');
                const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
                const dateString = date.toISOString().split('T')[0];
                
                dayElement.className = 'calendar-day';
                
                // Número del día
                const dayNumber = document.createElement('div');
                dayNumber.className = 'day-number';
                dayNumber.textContent = day;
                dayElement.appendChild(dayNumber);
                
                // Verificar si es hoy
                const today = new Date();
                if (date.toDateString() === today.toDateString()) {
                    dayElement.classList.add('today');
                }
                
                // VERIFICAR SI ESTÁ BLOQUEADO
                let isBlocked = false;
                let blockedReason = '';
                
                for (let i = 0; i < blockedDates.length; i++) {
                    const blocked = blockedDates[i];
                    if (dateString === blocked.fecha_inicio) {
                        isBlocked = true;
                        blockedReason = blocked.motivo;
                        break;
                    }
                }
                
                // Verificar si hay reservas (siempre definir dayReservations)
                const dayReservations = reservations.filter(res => {
                    const startDate = new Date(res.fecha_entrada);
                    const endDate = new Date(res.fecha_salida);
                    return date >= startDate && date <= endDate;
                });
                
                if (isBlocked) {
                    // APLICAR ESTILOS DE BLOQUEO
                    dayElement.style.backgroundColor = 'rgb(147, 141, 141)';
                    dayElement.style.color = '#ffffff';
                    dayElement.style.borderLeft = '4px solid rgb(204, 194, 194)';
                    dayElement.style.fontWeight = 'bold';
                    
                    // AGREGAR TEXTO DE BLOQUEO
                    const blockedText = document.createElement('div');
                    blockedText.style.color = '#ffffff';
                    blockedText.style.fontSize = '12px';
                    blockedText.style.textAlign = 'center';
                    blockedText.style.fontWeight = 'bold';
                    blockedText.style.marginTop = '5px';
                    blockedText.textContent = 'BLOQUEADO';
                    dayElement.appendChild(blockedText);
                    
                    // AGREGAR MOTIVO
                    const reasonText = document.createElement('div');
                    reasonText.style.color = '#ffffff';
                    reasonText.style.fontSize = '10px';
                    reasonText.style.textAlign = 'center';
                    reasonText.style.marginTop = '2px';
                    reasonText.textContent = blockedReason;
                    dayElement.appendChild(reasonText);
                } else if (dayReservations.length > 0) {
                    // Determinar el estado principal del día - PRIORIDAD: PAGADO > APROBADO > PENDIENTE
                    const hasPaid = dayReservations.some(res => res.estado_pago === 'pagada');
                    const hasApproved = dayReservations.some(res => res.estado === 'aprobada' && res.estado_pago !== 'pagada');
                    const hasPending = dayReservations.some(res => res.estado === 'pendiente');
                    
                    if (hasPaid) {
                        dayElement.classList.add('paid');
                        dayElement.style.backgroundColor = '#28a745';
                        dayElement.style.color = '#000000';
                        dayElement.style.borderLeft = '4px solid #28a745';
                    } else if (hasApproved) {
                        dayElement.classList.add('approved');
                        dayElement.style.backgroundColor = '#007bff';
                        dayElement.style.color = '#000000';
                        dayElement.style.borderLeft = '4px solid #007bff';
                    } else if (hasPending) {
                        dayElement.classList.add('pending');
                        dayElement.style.backgroundColor = '#ffc107';
                        dayElement.style.color = '#000000';
                        dayElement.style.borderLeft = '4px solid #ffc107';
                    }
                    
                    dayReservations.forEach(res => {
                        const resInfo = document.createElement('div');
                        resInfo.className = `reservation-info ${res.estado}`;
                        
                        // Mostrar estado de pago si está pagado, sino mostrar estado normal
                        const estadoTexto = res.estado_pago === 'pagada' ? 'PAGADA' : res.estado.toUpperCase();
                        resInfo.textContent = `${res.nombre} (${estadoTexto})`;
                        resInfo.title = `Reserva #${res.id_reserva} - ${res.nombre} ${res.apellido}`;
                        dayElement.appendChild(resInfo);
                    });
                }
                
                // Event listener para mostrar detalles
                dayElement.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    showDayDetails(date, dayReservations, isBlocked);
                });
                
                calendarGrid.appendChild(dayElement);
            }
            
            // Días del mes siguiente
            const nextMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
            const remainingDays = 42 - (startingDayOfWeek + daysInMonth);
            for (let day = 1; day <= remainingDays; day++) {
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day other-month';
                dayElement.textContent = day;
                calendarGrid.appendChild(dayElement);
            }
            
            calendar.appendChild(calendarGrid);
        }

        // Configurar eventos del calendario
        function setupCalendarEvents() {
            document.getElementById('prevMonth').addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() - 1);
                loadCalendarData().then(() => renderCalendar());
            });
            
            document.getElementById('nextMonth').addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() + 1);
                loadCalendarData().then(() => renderCalendar());
            });
            
            // Botón de actualizar calendario
            document.getElementById('refreshCalendar').addEventListener('click', async () => {
                const refreshBtn = document.getElementById('refreshCalendar');
                const originalText = refreshBtn.innerHTML;
                
                // Mostrar estado de carga
                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
                refreshBtn.disabled = true;
                
                try {
                    // Recargar datos del calendario
                    loadCalendarData();
                    
                    // Mostrar mensaje de éxito
                    refreshBtn.innerHTML = '<i class="fas fa-check"></i> Actualizado';
                    refreshBtn.classList.remove('btn-outline-success');
                    refreshBtn.classList.add('btn-success');
                    
                    // Restaurar botón después de 2 segundos
                    setTimeout(() => {
                        refreshBtn.innerHTML = originalText;
                        refreshBtn.classList.remove('btn-success');
                        refreshBtn.classList.add('btn-outline-success');
                        refreshBtn.disabled = false;
                    }, 2000);
                    
                } catch (error) {
                    console.error('Error actualizando calendario:', error);
                    
                    // Mostrar error
                    refreshBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                    refreshBtn.classList.remove('btn-outline-success');
                    refreshBtn.classList.add('btn-danger');
                    
                    // Restaurar botón después de 3 segundos
                    setTimeout(() => {
                        refreshBtn.innerHTML = originalText;
                        refreshBtn.classList.remove('btn-danger');
                        refreshBtn.classList.add('btn-outline-success');
                        refreshBtn.disabled = false;
                    }, 3000);
                }
            });
        }

        // Mostrar detalles del día
        function showDayDetails(date, reservations, isBlocked) {
            const dateString = date.toLocaleDateString('es-CO');
            let content = `<h5><i class="fas fa-calendar-day me-2"></i>${dateString}</h5>`;
            
            if (isBlocked) {
                // Buscar información del bloqueo
                const dateStringISO = date.toISOString().split('T')[0];
                const blockedInfo = blockedDates.find(blocked => blocked.fecha_inicio === dateStringISO);
                
                content += `
                    <div class="alert alert-danger">
                        <i class="fas fa-ban me-2"></i><strong>Fecha Bloqueada</strong>
                        <hr>
                        <p class="mb-1"><strong>Motivo:</strong> ${blockedInfo ? blockedInfo.motivo : 'No especificado'}</p>
                        <p class="mb-0"><strong>Descripción:</strong> ${blockedInfo ? (blockedInfo.descripcion || 'Sin descripción') : 'Sin información'}</p>
                        <p class="mb-0"><strong>Bloqueado desde:</strong> ${blockedInfo ? blockedInfo.fecha_inicio : 'N/A'}</p>
                        ${blockedInfo && blockedInfo.fecha_fin ? `<p class="mb-0"><strong>Hasta:</strong> ${blockedInfo.fecha_fin}</p>` : ''}
                    </div>
                `;
            }
            
            if (reservations.length > 0) {
                content += '<h6><i class="fas fa-calendar-check me-2"></i>Reservas:</h6><ul class="list-group">';
                reservations.forEach(res => {
                    const estadoColor = res.estado_pago === 'pagada' ? 'success' : 
                                     res.estado === 'aprobada' ? 'primary' : 
                                     res.estado === 'pendiente' ? 'warning' : 'secondary';
                    
                    const estadoTexto = res.estado_pago === 'pagada' ? 'PAGADA' : 
                                      res.estado === 'aprobada' ? 'APROBADA' : 
                                      res.estado === 'pendiente' ? 'PENDIENTE' : res.estado.toUpperCase();
                    
                    content += `
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>${res.nombre} ${res.apellido}</strong><br>
                                    <small class="text-muted">ID: ${res.id_reserva}</small><br>
                                    <small class="text-muted">Email: ${res.correo || 'No disponible'}</small><br>
                                    <small class="text-muted">Teléfono: ${res.telefono || 'No disponible'}</small><br>
                                    <small class="text-muted">Fechas: ${res.fecha_entrada} - ${res.fecha_salida}</small><br>
                                    <small class="text-muted">Adultos: ${res.num_adultos || 1} | Niños: ${res.num_ninos || 0}</small><br>
                                    <small class="text-muted">Método de pago: ${res.metodo_pago}</small><br>
                                    <small class="text-muted">Total: $${parseFloat(res.total).toLocaleString('es-CO')} COP</small>
                                    ${res.comentario ? `<br><small class="text-muted"><strong>Comentario:</strong> ${res.comentario}</small>` : ''}
                                </div>
                                <span class="badge bg-${estadoColor}">${estadoTexto}</span>
                            </div>
                        </li>
                    `;
                });
                content += '</ul>';
            } else if (!isBlocked) {
                content += '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Fecha disponible para reservas</div>';
            }
            
            // Crear modal bonito y estético
            const modalHtml = `
                <div class="modal fade" id="dayDetailsModal" tabindex="-1" aria-labelledby="dayDetailsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="dayDetailsModalLabel">
                                    <i class="fas fa-calendar-day me-2"></i>Detalles del ${dateString}
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                ${content}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remover modal anterior si existe
            const existingModal = document.getElementById('dayDetailsModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Agregar nuevo modal al body
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('dayDetailsModal'));
            modal.show();
            
            // Limpiar modal cuando se cierre
            document.getElementById('dayDetailsModal').addEventListener('hidden.bs.modal', function () {
                this.remove();
            });
        }

        // Actualizar estadísticas
        function updateStats() {
            const monthReservations = reservations.length;
            const monthNights = reservations.reduce((total, res) => {
                const start = new Date(res.fecha_entrada);
                const end = new Date(res.fecha_salida);
                return total + Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            }, 0);
            const monthBlocked = blockedDates.length;
            const monthOccupancy = monthNights > 0 ? Math.round((monthNights / 30) * 100) : 0;
            
            document.getElementById('monthReservations').textContent = monthReservations;
            document.getElementById('monthNights').textContent = monthNights;
            document.getElementById('monthBlocked').textContent = monthBlocked;
            document.getElementById('monthOccupancy').textContent = monthOccupancy + '%';
        }
    </script>
</body>
</html>
