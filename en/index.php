<?php
/**
 * Sistema de Reservas - My Suite In Cartagena
 * Integrado con base de datos MySQL
 */

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Las funciones getFechasOcupadas, getTarifaPorFecha, guardarReserva, etc.
// ahora están en includes/functions.php y se cargan automáticamente

// Obtener fechas ocupadas y precio base desde la base de datos
$occupied_dates = getFechasOcupadas();
$base_price = 200000;

// Obtener descuentos desde la base de datos
$descuentos = [];
try {
    $database = new Database();
    $db = $database->getConnection();
    $query = "SELECT tipo_descuento, porcentaje, activo FROM descuentos_config";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $descuentos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($descuentos_db as $descuento) {
        $descuentos[$descuento['tipo_descuento']] = [
            'porcentaje' => floatval($descuento['porcentaje']),
            'activo' => (bool)$descuento['activo']
        ];
    }
} catch (Exception $e) {
    // Valores por defecto en caso de error
    $descuentos = [
        'fidelidad' => ['porcentaje' => 5.0, 'activo' => true],
        'cumpleanos' => ['porcentaje' => 30.0, 'activo' => true],
        'promocional' => ['porcentaje' => 0.0, 'activo' => false]
    ];
}

// Verificar si el usuario está logueado
$user_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
$user_name = $user_logged_in ? $_SESSION['user_nombre'] : '';
$user_role = $user_logged_in ? $_SESSION['user_rol'] : '';

// Obtener datos completos del usuario logueado para prellenar formulario
$user_data = null;
if ($user_logged_in && isset($_SESSION['user_id'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        $query = "SELECT nombre, apellido, correo, telefono, fecha_nacimiento FROM usuarios WHERE id_usuario = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // En caso de error, continuar sin datos del usuario
        $user_data = null;
    }
}

?>

<!--
Author: W3layouts
Author URL: http://w3layouts.com
License: Creative Commons Attribution 3.0 Unported
License URL: http://creativecommons.org/licenses/by/3.0/
-->

<!DOCTYPE html>
<html lang="zxx">

<head>
<link rel="shortcut icon" href="images/favicon.png"/>
	<title>My Suite In Cartagena</title>
	<!-- Meta tag Keywords -->
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="UTF-8" />
	<meta name="keywords" content="Villas Responsive web template, Bootstrap Web Templates, Flat Web Templates, Android Compatible web template, Smartphone Compatible web template, free webdesigns for Nokia, Samsung, LG, SonyEricsson, Motorola web design" />
	<script>
		addEventListener("load", function () {
			setTimeout(hideURLbar, 0);
		}, false);

		function hideURLbar() {
			window.scrollTo(0, 1);
		}
	</script>
	<!-- //Meta tag Keywords -->

	<!-- Custom-Files -->
	<link rel="stylesheet" href="css/bootstrap.css">
	<!-- Bootstrap-Core-CSS -->
	<link href="css/css_slider.css" type="text/css" rel="stylesheet" media="all">
	<!-- banner slider -->
	<link rel="stylesheet" href="css/style.css" type="text/css" media="all" />
	<!-- Style-CSS -->
	<link href="css/font-awesome.min.css" rel="stylesheet">
	<!-- Font-Awesome-Icons-CSS -->
	<!-- //Custom-Files -->

	<!-- Web-Fonts -->
	<link href="//fonts.googleapis.com/css?family=Crimson+Text:400,400i,600,600i,700,700i" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Oxygen:300,400,700&amp;subset=latin-ext" rel="stylesheet">
	<!-- //Web-Fonts -->
</head>

<body>
	<!-- main banner -->
	<div class="main-top" id="home">
		<!-- header -->
		<header>
			<div class="container-fluid">
				<div class="header d-md-flex justify-content-between align-items-center py-3 px-xl-5 px-lg-3 px-2">
					<!-- logo -->
					<div id="logo">
						<h1><a href="index.php">My Suite In Cartagena</a></h1>
					</div>
					<!-- //logo -->
					
					<!-- //Language Selector -->
					<!-- nav -->
					<div class="nav_w3ls">
						<nav>
							<label for="drop" class="toggle">Menu</label>
							<input type="checkbox" id="drop" />
							<ul class="menu">
								<li><a href="index.php" class="active">Home</a></li>
								<li><a href="elapto.html">The Apartment</a></li>
								<li><a href="lasinstalaciones.html">Facilities</a></li>
								<li><a href="tarifas.html">Rates and Cancellations</a></li>
								<!-- <li><a href="tarifas.html">Testimonios</a></li> -->
								<li><a href="contactenos.html">Contact Us</a></li>
								<?php if ($user_logged_in): ?>
									<?php if ($user_role === 'admin'): ?>
										<li><a href="../admin/index.php" style="color:rgb(255, 255, 255); ">
											 Panel
										</a></li>
									<?php endif; ?>
									<li class="dropdown">
										<a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" style="color: #333; font-weight: bold;">
											👤 Hello <?php echo htmlspecialchars($user_name); ?>
										</a>
										<ul class="dropdown-menu">
											<li><a class="dropdown-item" href="#" onclick="showProfileInfo(); return false;">
												<i class="fas fa-user"></i> My Profile
											</a></li>
											<li><a class="dropdown-item" href="#" onclick="showMyReservations(); return false;">
												<i class="fas fa-calendar-check"></i> My Reservations
											</a></li>
											<li><hr class="dropdown-divider"></li>
											<li><a class="dropdown-item" href="logout.php" style="color: #FF4136;">
												<i class="fas fa-sign-out-alt"></i> Logout
											</a></li>
										</ul>
									</li>
								<?php else: ?>
									<li><a href="login.php">Login</a></li>
								<?php endif; ?>

								<li>	
						 <!-- <a  href="https://www.lavozdelospanelerosco.com/"><img src="../../apto/web/images/bcolombia.png" target="self"></a>	 -->							
							 <a  href="../index.html"><img src="images/bcolombia.png" target="self"></a> 
   							</li>
															<li>						
							 <a  href="../it/index.html"><img src="images/bitalia.png" target="self"></a> 
   							</li>
							<li>														
<div id="sfcnpga1u87mu84g9kudrfy5skwu8td29lp"></div>
<div id="sfcxds958c1a9mzdg2f1bg2yfdm1p7xxkzz"></div><script type="text/javascript" src="https://counter6.optistats.ovh/private/counter.js?c=xds958c1a9mzdg2f1bg2yfdm1p7xxkzz&down=async" async></script><noscript><a href="https://www.contadorvisitasgratis.com" title="contador de visitas online"><img src="https://counter6.optistats.ovh/private/contadorvisitasgratis.php?c=xds958c1a9mzdg2f1bg2yfdm1p7xxkzz"></a></noscript>
							</li>



							</ul>
						</nav>
					</div>
					<!-- //nav -->
				</div>
			</div>
		</header>
		<!-- //header -->


		<!-- banner -->
		<div class="banner_w3lspvt">
			<div class="csslider infinity" id="slider1">
				<input type="radio" name="slides" checked="checked" id="slides_1" />
				<input type="radio" name="slides" id="slides_2" />
				<input type="radio" name="slides" id="slides_3" />
				<input type="radio" name="slides" id="slides_4" />
				<ul class="banner_slide_bg">
					<li>
						<div class="container">
							<div class="w3ls_banner_txt">
								<p>Welcome to My Suite in Cartagena</p>
								<h3 class="w3ls_pvt-title text-wh text-uppercase let">Relaxation & Rest</h3>
							</div>
						</div>
					</li>
					<li>
						<div class="container">
							<div class="w3ls_banner_txt">
								<p>Welcome to My Suite in Cartagena</p>
								<h3 class="w3ls_pvt-title text-wh text-uppercase let">Enjoy your moments</h3>
							</div>
						</div>
					</li>
					<li>
						<div class="container">
							<div class="w3ls_banner_txt">
								<p>Welcome to My Suite in Cartagena</p>
								<h3 class="w3ls_pvt-title text-wh text-uppercase let">Modern spaces and rooms</h3>
							</div>
						</div>
					</li>
					<li>
						<div class="container">
							<div class="w3ls_banner_txt">
								<p>Welcome to My Suite in Cartagena</p>
								<h3 class="w3ls_pvt-title text-wh text-uppercase let">The perfect spot</h3>
							</div>
						</div>
					</li>
				</ul>
				<div class="arrows">
					<label for="slides_1"></label>
					<label for="slides_2"></label>
					<label for="slides_3"></label>
					<label for="slides_4"></label>
				</div>
			</div>
		</div>
		<!-- //banner -->
	</div>
	<!-- //main banner -->

<p><br><br><center><strong>availability</strong></center></p>
	<hr>
<center>
 </center>

<!-- Sistema de Reservas Interactivo -->
<div class="container-fluid py-5">
    <div class="row">
        <!-- Calendario de Reservas -->
        <div class="col-lg-8 col-md-12">
            <div class="calendar-container">
                <div class="calendar-header">
                    <h3 class="text-center mb-4" style="font-family: Arial, Helvetica, sans-serif;"> Selecciona tus fechas</h3>
                    <div class="calendar-navigation">
                        <button id="prevMonth" class="btn btn-outline-primary">‹</button>
                        <span id="currentMonth" class="month-display"></span>
                        <button id="nextMonth" class="btn btn-outline-primary">›</button>
                    </div>
                </div>
                <div id="calendar" class="calendar-grid"></div>
                <div class="calendar-legend mt-3">
                    <div class="legend-item">
                        <span class="legend-color available"></span>
                        <span>Disponible</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color occupied"></span>
                        <span>Ocupado</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color checkin"></span>
                        <span>Check-in</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color checkout"></span>
                        <span>Check-out</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color in-range"></span>
                        <span>Rango seleccionado</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Resumen de Reserva -->
        <div class="col-lg-4 col-md-12">
            <div class="reservation-summary">
                <h4 class="text-center mb-4">💰 Resumen de Reserva</h4>
                <div id="reservationDetails" class="reservation-details">
                    <div class="detail-item">
                        <span class="label">Fecha de entrada:</span>
                        <span id="checkinDate" class="value">-</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Fecha de salida:</span>
                        <span id="checkoutDate" class="value">-</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Número de noches:</span>
                        <span id="nightsCount" class="value">-</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Tarifa por noche:</span>
                        <span id="pricePerNight" class="value">$200.000 COP</span>
                    </div>
                    <hr>
                    <div class="detail-item total">
                        <span class="label">Total:</span>
                        <span id="totalPrice" class="value">$0 COP</span>
                    </div>
                </div>
                <button id="reserveBtn" class="btn btn-primary btn-lg w-100 mt-3" disabled>
                    🏨 Reservar Ahora
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Advertencia de Rango -->
<div class="modal fade" id="rangeErrorModal" tabindex="-1" aria-labelledby="rangeErrorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rangeErrorModalLabel">⚠️ Rango No Disponible</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fa fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h6>Lo siento, en este rango hay días reservados</h6>
                    <p class="text-muted">Por lo tanto no puedes reservar en estas fechas.</p>
                    <p class="text-muted">Por favor selecciona otro rango de fechas disponibles.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Formulario de Reserva -->
<div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reservationModalLabel">📋 Formulario de Reserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="reservationForm">
                    <?php if ($user_logged_in && $user_data): ?>
                    <div class="alert alert-info mb-3" style="background-color: #e3f2fd; border: 1px solid #2196f3; color: #1976d2; padding: 12px; border-radius: 8px;">
                        <i class="fas fa-user-check"></i> <strong>¡Hola <?php echo htmlspecialchars($user_data['nombre']); ?>!</strong> 
                        Los campos están prellenados con tus datos. Puedes editarlos si la reserva es para otra persona.
                    </div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombres" class="form-label">Nombres *</label>
                            <input type="text" class="form-control" id="nombres" name="nombres" value="<?php echo $user_data ? htmlspecialchars($user_data['nombre']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="apellidos" class="form-label">Apellidos *</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?php echo $user_data ? htmlspecialchars($user_data['apellido']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="celular" class="form-label">Celular *</label>
                            <input type="tel" class="form-control" id="celular" name="celular" value="<?php echo $user_data ? htmlspecialchars($user_data['telefono']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="correo" class="form-label">Correo *</label>
                            <input type="email" class="form-control" id="correo" name="correo" value="<?php echo $user_data ? htmlspecialchars($user_data['correo']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fechaNacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fechaNacimiento" name="fechaNacimiento" value="<?php echo $user_data && $user_data['fecha_nacimiento'] ? $user_data['fecha_nacimiento'] : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="adultos" class="form-label">Número de Adultos *</label>
                            <select class="form-control" id="adultos" name="adultos" required>
                                <option value="">Seleccionar</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="ninos" class="form-label">Número de Niños</label>
                            <select class="form-control" id="ninos" name="ninos">
                                <option value="0">0</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="vivePalmira" name="vivePalmira">
                                <label class="form-check-label" for="vivePalmira">
                                    ¿Vive en Palmira?
                                </label>
                            </div>
                            <div id="palmiraInfo" class="alert alert-info mt-2" style="display: none;">
                                <small>✅ Incluye transporte gratuito al aeropuerto</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Método de Pago -->
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Método de Pago *</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="metodoPago" id="tarjetaCredito" value="tarjeta_credito" checked>
                                        <label class="form-check-label" for="tarjetaCredito">
                                            💳 Tarjeta de Crédito
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="metodoPago" id="efectivo" value="efectivo">
                                        <label class="form-check-label" for="efectivo">
                                            💰 Efectivo (<?php echo isset($descuentos['promocional']) && $descuentos['promocional']['activo'] ? $descuentos['promocional']['porcentaje'] : 3; ?>% descuento)
                                        </label>
                                    </div>
                                </div>
                            </div>
                <div id="descuentoInfo" class="alert alert-success mt-2" style="display: none;">
                    <small>🎉 ¡Descuento del <?php echo isset($descuentos['promocional']) && $descuentos['promocional']['activo'] ? $descuentos['promocional']['porcentaje'] : 3; ?>% aplicado por pago en efectivo!</small>
                </div>
            </div>
        </div>
        
        <!-- Información de Comprobante de Pago -->
        <div class="row">
            <div class="col-12 mb-3">
                <div class="alert alert-info">
                    <h6 class="mb-2">📧 Importante - Comprobante de Pago:</h6>
                    <p class="mb-0">
                        Una vez aprobada tu reserva, deberás realizar el pago y enviar el comprobante al correo: 
                        <strong>jose.cardenas01@uceva.edu.co</strong>
                    </p>
                </div>
            </div>
        </div>
                    
                    <!-- Resumen de Costo en el Modal -->
                    <div class="cost-summary-modal">
                        <h6>💰 Resumen de Costo</h6>
                        <div class="row mb-2">
                            <div class="col-6">
                                <small>Fechas: <span id="modalCheckinDate">-</span> a <span id="modalCheckoutDate">-</span></small>
                            </div>
                            <div class="col-6 text-end">
                                <small>Noches: <span id="modalNights">-</span></small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <small>Subtotal:</small>
                            </div>
                            <div class="col-6 text-end">
                                <small><span id="modalSubtotal">$0 COP</span></small>
                            </div>
                        </div>
                        <div class="row" id="descuentoRow" style="display: none;">
                            <div class="col-6">
                                <small>Descuento (<?php echo isset($descuentos['promocional']) && $descuentos['promocional']['activo'] ? $descuentos['promocional']['porcentaje'] : 3; ?>%):</small>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-success"><span id="modalDescuento">$0 COP</span></small>
                            </div>
                        </div>
                        <?php if ($user_logged_in): ?>
                        <div class="row" id="fidelidadRow">
                            <div class="col-6">
                                <small>Descuento Fidelidad (<?php echo isset($descuentos['fidelidad']) ? $descuentos['fidelidad']['porcentaje'] : 5; ?>%):</small>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-success"><span id="modalFidelidad">$0 COP</span></small>
                            </div>
                        </div>
                        <div class="row" id="cumpleanosRow" style="display: none;">
                            <div class="col-6">
                                <small>🎂 Descuento Cumpleaños (<?php echo isset($descuentos['cumpleanos']) ? $descuentos['cumpleanos']['porcentaje'] : 30; ?>%):</small>
                                <br><small class="text-muted" id="cumpleanosInfo" style="font-size: 11px;"></small>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-success"><span id="modalCumpleanos">$0 COP</span></small>
                            </div>
                        </div>
                        <?php endif; ?>
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <strong>Total:</strong>
                            </div>
                            <div class="col-6 text-end">
                                <strong><span id="modalTotal">$0 COP</span></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="aceptaPolitica" name="aceptaPolitica" required>
                        <label class="form-check-label" for="aceptaPolitica">
                            Acepto la política de privacidad y Habeas Data *
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelReservation">Cancelar</button>
                <button type="button" class="btn btn-primary" id="submitReservation">Enviar Reserva</button>
            </div>
        </div>
    </div>
</div>

<!-- WhatsApp y Transporte -->
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <img src="images/ws.png" width="200" height="200" alt="WhatsApp">
            <p><strong>BOOK NOW</strong><br> WhatsApp +57 301 5193163</p>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <img src="images/optra.png" width="405" height="200" alt="Transporte">
            <p>If your reservation is from the city of Palmira we have<br><strong>FREE</strong> transport to Alfonso Bonilla Aragon Airport in the city of Palmira.</p>
        </div>
    </div>
</div>

		
		
		

		
		
		
		
		<!-- footer -->
	<footer class="py-5">
		<div class="container pt-xl-4">
			<div class="row footer-top">
				
				<div class="col-lg-3 col-md-6 footer-grid_section_1its mt-lg-0 mt-4">
					<!-- social icons -->
					<div class="mobamuinfo_social_icons">
						
						<h3 class="sub-con-fo text-li my-4">Social Media</h3>
												<ul class="mobamuits_social_list list-unstyled">
							<li class="w3_mobamu_facebook">
								<a href="https://www.facebook.com/profile.php?id=61569691859314" target="_blank">
									<span class="fa fa-facebook-f"></span>
								</a>
							</li>
							<li class="w3_mobamu_dribble">
								<a href="https://www.instagram.com/mysuiteincartagena/" target="_blank">
									<span class="fa fa-instagram"></span>
								</a>


								<li>
								<a href="https://wa.me/+573015193163" target="_blank">
									<img src="images/wss.png" width="35" height="35" >
								</a>
							</li>

							</li>
						</ul>
					</div>
					<!-- social icons -->
				</div>
			</div>
		</div>
	</footer>

		<div>
<center><p>www.mysuiteincartagena.com.co rejects the sexual abuse of children and adolescents in compliance with Article 17 of Law 679 of 2001. It is reported that the sexual exploitation and abuse of children are punishable by criminal and civil law under Colombian law.

</a>

		</p></center>
	</div>
	<!-- //footer -->
	<!-- copyright -->
	<div class="cpy-right text-center py-3">
		<p>© 2025 mysuiteincartagena.com.co All Rights Reserved</a>
		</p>
	</div>
	<!-- //copyright -->

	<!-- move top icon -->
	<a href="#home" class="move-top text-center"></a>
	<!-- //move top icon -->

	<!-- Estilos CSS para el Sistema de Reservas -->
	<style>
		/* Estilos del Calendario */
		.calendar-container {
			background: #fff;
			border-radius: 10px;
			box-shadow: 0 4px 6px rgb(255, 255, 255);
			padding: 30px;
			max-width: 900px;
			margin: 0 auto;
		}
		
		.calendar-header {
			text-align: center;
			margin-bottom: 20px;
		}
		
		.calendar-navigation {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 20px;
		}
		
		.month-display {
			font-size: 1.8rem;
			font-weight: bold;
			color: #333;
		}
		
		.calendar-grid {
			display: grid;
			grid-template-columns: repeat(7, 1fr);
			gap: 4px;
			margin-bottom: 35px;
			max-width: 700px;
			margin-left: auto;
			margin-right: auto;
			grid-auto-rows: minmax(60px, auto);
		}
		
		.calendar-day {
			aspect-ratio: 1;
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			transition: all 0.3s ease;
			position: relative;
			min-height: 60px;
			font-size: 18px;
		}
		
		
		
		.calendar-day.available {
			background-color:rgb(255, 255, 255);
			color:rgb(0, 0, 0);
			border: 2px solid rgb(229, 231, 235); 
			border-radius: 4px;
		}
		
		.calendar-day.occupied {
			background-color: #e9ecef;
			color: #6c757d;
			cursor: not-allowed;
			border: 1px solid #6c757d;
		}
		
		.calendar-day.checkin {
			background-color:rgb(255, 255, 255) !important;
			color:rgb(0, 0, 0) !important;
			border: 3px solid rgb(0, 201, 80) !important;
			font-weight: bold;
			transform: scale(1.05);
		}
		
		.calendar-day.checkout {
			background-color:rgb(255, 255, 255) !important;
			color:rgb(0, 0, 0) !important;
			border: 3px solid rgb(43, 127, 255) !important;
			font-weight: bold;
			transform: scale(1.05);
		}
		
		.calendar-day.in-range {
			background-color:rgb(240, 253, 244);
			color:rgb(0, 0, 0);
			border: 3px solid rgb(185, 248, 207)
		}
		
		.calendar-day.other-month {
			color:rgb(201, 201, 201);
			border: 1px solid rgb(229, 231, 235);
		}
		
		.day-price {
			font-size: 0.9rem;
			position: absolute;
			bottom: 5px;
			left: 50%;
			transform: translateX(-50%);
			color: #6c757d;
			font-weight: 600;
		}
		
		.calendar-legend {
			display: flex;
			justify-content: center;
			gap: 25px;
			flex-wrap: wrap;
			font-size: 18px;
		}
		
		.legend-item {
			display: flex;
			align-items: center;
			gap: 5px;
		}
		
		.legend-color {
			width: 25px;
			height: 25px;
			border-radius: 3px;
		}
		
		.legend-color.available { 
			background-color: #ffffff; 
			border: 1px solid #6c757d;
		}
		.legend-color.occupied { 
			background-color: #e9ecef; 
			border: 1px solid #6c757d;
		}
		.legend-color.checkin { 
			background-color: #28a745; 
			border: 3px solid #1e7e34;
		}
		.legend-color.checkout { 
			background-color: #007bff; 
			border: 3px solid #0056b3;
		}
		.legend-color.in-range { background-color: #d4edda; }
		
		/* Estilos del Resumen de Reserva */
		.reservation-summary {
			background: #f8f9fa;
			border-radius: 10px;
			padding: 20px;
			height: fit-content;
		}
		
		.reservation-details {
			background: white;
			border-radius: 8px;
			padding: 15px;
		}
		
		.detail-item {
			display: flex;
			justify-content: space-between;
			margin-bottom: 10px;
		}
		
		.detail-item.total {
			font-weight: bold;
			font-size: 1.1rem;
			border-top: 2px solid #007bff;
			padding-top: 10px;
		}
		
		.label {
			color: #666;
		}
		
		.value {
			font-weight: bold;
			color: #333;
		}
		
		/* Tooltips */
		.tooltip {
			position: relative;
		}
		
		.tooltip .tooltiptext {
			visibility: hidden;
			width: 200px;
			background-color: #2c3e50;
			color: #fff;
			text-align: center;
			border-radius: 8px;
			padding: 8px;
			position: absolute;
			z-index: 1;
			bottom: 125%;
			left: 50%;
			margin-left: -100px;
			opacity: 0;
			transition: opacity 0.3s;
			font-size: 12px;
		}
		
		.tooltip:hover .tooltiptext {
			visibility: visible;
			opacity: 1;
		}
		
		/* Estilos del Modal */
		.cost-summary-modal {
			background: linear-gradient(135deg, #f8f9fa, #e9ecef);
			border-radius: 12px;
			padding: 20px;
			margin: 20px 0;
			border-left: 4px solid #3498db;
		}
		
		/* Estilos para modal manual (fallback) */
		.modal {
			display: none;
			position: fixed;
			z-index: 1050;
			left: 0;
			top: 0;
			width: 100%;
			height: 100%;
			overflow: hidden;
			background-color: rgba(0,0,0,0.5);
		}
		
		.modal.show {
			display: block !important;
		}
		
		.modal-dialog {
			position: relative;
			width: auto;
			margin: 0.5rem;
			pointer-events: none;
		}
		
		.modal-content {
			position: relative;
			display: flex;
			flex-direction: column;
			width: 100%;
			pointer-events: auto;
			background-color: #fff;
			background-clip: padding-box;
			border: 1px solid rgba(0,0,0,.2);
			border-radius: 0.3rem;
			outline: 0;
		}
		
		.modal-backdrop {
			position: fixed;
			top: 0;
			left: 0;
			z-index: 1040;
			width: 100vw;
			height: 100vh;
			background-color: #000;
		}
		
		.modal-backdrop.fade {
			opacity: 0;
		}
		
		.modal-backdrop.show {
			opacity: 0.5;
		}
		
		/* Estilos específicos para modal de advertencia */
		#rangeErrorModal .modal-content {
			border-left: 4px solid #ffc107;
		}
		
		#rangeErrorModal .fa-exclamation-triangle {
			color: #ffc107 !important;
		}
		
		/* Estilo para el botón de cerrar del modal */
		.btn-close {
			background: none;
			border: none;
			font-size: 1.5rem;
			font-weight: bold;
			color: #6c757d;
			cursor: pointer;
			padding: 0.5rem;
			line-height: 1;
			opacity: 0.7;
			transition: opacity 0.15s ease-in-out;
		}
		
		.btn-close:hover {
			opacity: 1;
			color: #000;
		}
		
		.btn-close:focus {
			outline: none;
			box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
		}
		
		.btn-close::before {
			content: "×";
			font-size: 1.5rem;
			font-weight: bold;
		}
		
		@media (min-width: 576px) {
			.modal-dialog {
				max-width: 500px;
				margin: 1.75rem auto;
			}
		}
		
		/* Estilos del Modal de Login */
		.modal .close:hover {
			color: #ff6b6b !important;
		}
		
		.modal .input-group input:focus {
			border-color: #007BFF !important;
			outline: none;
			box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
		}
		
		.modal .login-button:hover {
			background-color: #FF8C00 !important;
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(255, 165, 0, 0.3);
		}
		
		.modal .login-button {
			transition: all 0.3s ease;
		}
		
		.modal .links a:hover {
			color: #0056b3 !important;
			text-decoration: underline !important;
		}
		
		/* Estilos para el dropdown del perfil */
		.dropdown-menu {
			background: white;
			border: 1px solid #ddd;
			border-radius: 8px;
			box-shadow: 0 4px 12px rgba(0,0,0,0.15);
			padding: 8px 0;
			min-width: 200px;
		}
		
		.dropdown-item {
			padding: 8px 16px;
			color: #333;
			text-decoration: none;
			display: flex;
			align-items: center;
			transition: background-color 0.2s ease;
		}
		
		.dropdown-item:hover {
			background-color: #f8f9fa;
			color: #007bff;
		}
		
		.dropdown-item i {
			margin-right: 8px;
			width: 16px;
		}
		
		.dropdown-divider {
			margin: 4px 0;
			border-top: 1px solid #dee2e6;
		}
		
		.dropdown-toggle::after {
			margin-left: 8px;
		}
		
		/* Responsive Design Mejorado */
		@media (max-width: 768px) {
			.calendar-container {
				margin-bottom: 20px;
				padding: 15px;
			}
			
			.calendar-grid {
				gap: 1px;
				max-width: 100%;
			}
			
			.calendar-day {
				min-height: 40px;
				font-size: 12px;
			}
			
			.day-number {
				font-size: 12px;
			}
			
			.day-price {
				font-size: 7px;
			}
			
			.calendar-legend {
				justify-content: center;
				gap: 15px;
			}
			
			.legend-item {
				font-size: 12px;
			}
			
			.legend-color {
				width: 20px;
				height: 20px;
			}
			
			.reservation-summary {
				padding: 20px;
			}
			
			.detail-item {
				flex-direction: column;
				align-items: flex-start;
				gap: 5px;
			}
			
			.detail-item.total {
				flex-direction: row;
				justify-content: space-between;
			}
			
			/* Modal responsive */
			.modal .modal-content {
				width: 95% !important;
				margin: 10% auto !important;
			}
			
			.modal .modal-body {
				padding: 20px !important;
			}
			
			.modal .links {
				flex-direction: column !important;
				gap: 10px !important;
			}
		}
		
		@media (max-width: 480px) {
			.calendar-container {
				padding: 10px;
			}
			
			.calendar-day {
				min-height: 35px;
			}
			
			.day-number {
				font-size: 11px;
			}
			
			.day-price {
				font-size: 6px;
			}
			
			.month-display {
				font-size: 1.4rem;
			}
		}
	</style>

	<!-- JavaScript para el Sistema de Reservas -->
	<script>
		// Variables globales
		let currentDate = new Date();
		let selectedStartDate = null;
		let selectedEndDate = null;
		let occupiedDates = <?php echo json_encode($occupied_dates); ?>;
		let basePrice = <?php echo $base_price; ?>; // Precio base por noche en COP
		
		// Función para obtener fechas ocupadas (placeholder para futura integración con BD)
		function getFechasOcupadas() {
			// Los datos ya vienen del servidor PHP
			return occupiedDates;
		}
		
		// Función para obtener tarifa por fecha (placeholder para futura integración con BD)
		function getTarifaPorFecha(fecha) {
			// TODO: Conectar con base de datos para tarifas dinámicas
			// Por ahora, retorna precio fijo
			return basePrice;
		}
		
		
		// Inicializar calendario
		function initCalendar() {
			console.log('Inicializando calendario...');
			occupiedDates = getFechasOcupadas();
			console.log('Fechas ocupadas:', occupiedDates);
			renderCalendar();
			setupEventListeners();
			console.log('Calendario inicializado correctamente');
		}
		
		
		// Renderizar calendario
		function renderCalendar() {
			const calendar = document.getElementById('calendar');
			const monthDisplay = document.getElementById('currentMonth');
			
			console.log('Renderizando calendario...', calendar, monthDisplay);
			console.log('Fecha actual:', currentDate);
			console.log('Mes actual:', currentDate.getMonth());
			console.log('Año actual:', currentDate.getFullYear());
			
			// Limpiar calendario
			calendar.innerHTML = '';
			
			// Mostrar mes actual
			const monthNames = [
				'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
				'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
			];
			const monthText = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
			monthDisplay.textContent = monthText;
			console.log('Mes mostrado:', monthText);
			
			// Grid fijo de 6 filas para mantener estructura consistente
			calendar.style.gridTemplateRows = 'repeat(6, 1fr)';
			
			// Obtener primer día del mes y cuántos días tiene
			const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
			const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
			const daysInMonth = lastDay.getDate();
			const startingDayOfWeek = firstDay.getDay();
			
			// Días de la semana
			const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
			dayNames.forEach(day => {
				const dayHeader = document.createElement('div');
				dayHeader.className = 'calendar-day text-center fw-bold';
				dayHeader.textContent = day;
				dayHeader.style.background = '#e9ecef';
				dayHeader.style.cursor = 'default';
				calendar.appendChild(dayHeader);
			});
			
			// Días del mes anterior (invisibles pero mantienen la estructura)
			const prevMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 0);
			for (let i = startingDayOfWeek - 1; i >= 0; i--) {
				const day = document.createElement('div');
				day.className = 'calendar-day other-month';
				day.style.visibility = 'hidden'; // Hacer invisibles pero mantener estructura
				day.textContent = prevMonth.getDate() - i;
				calendar.appendChild(day);
			}
			
			// Días del mes actual
			for (let day = 1; day <= daysInMonth; day++) {
				const dayElement = document.createElement('div');
				const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
				const dateString = date.toISOString().split('T')[0];
				
				dayElement.className = 'calendar-day';
				dayElement.dataset.date = dateString;
				
				// Crear estructura del día
				const dayNumber = document.createElement('div');
				dayNumber.className = 'day-number';
				dayNumber.textContent = day;
				dayElement.appendChild(dayNumber);
				
				console.log('Día creado:', day, 'Elemento:', dayElement);
				
				// Verificar si es un día pasado
				const today = new Date();
				today.setHours(0, 0, 0, 0); // Resetear horas para comparar solo fechas
				const dayDate = new Date(date);
				dayDate.setHours(0, 0, 0, 0);
				
				if (dayDate < today) {
					// Día pasado - estilo como other-month
					dayElement.classList.add('other-month');
					dayElement.title = 'Día pasado';
				} else if (occupiedDates.includes(dateString)) {
					// Día ocupado
					dayElement.classList.add('occupied');
					dayElement.title = 'No disponible';
				} else {
					// Día disponible
					dayElement.classList.add('available');
					dayElement.addEventListener('click', () => selectDate(date));
					
					// Agregar precio
					const priceElement = document.createElement('div');
					priceElement.className = 'day-price';
					priceElement.textContent = '$' + (getTarifaPorFecha(dateString) / 1000) + 'k';
					dayElement.appendChild(priceElement);
				}
				
				calendar.appendChild(dayElement);
			}
			
			// Días del mes siguiente (invisibles pero mantienen la estructura)
			const nextMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
			const remainingDays = 42 - (startingDayOfWeek + daysInMonth);
			for (let day = 1; day <= remainingDays; day++) {
				const dayElement = document.createElement('div');
				dayElement.className = 'calendar-day other-month';
				dayElement.style.visibility = 'hidden'; // Hacer invisibles pero mantener estructura
				dayElement.textContent = day;
				calendar.appendChild(dayElement);
			}
			
			// Aplicar estados de selección
			updateSelectionStates();
		}
		
		// Seleccionar fecha
		function selectDate(date) {
			if (selectedStartDate === null) {
				// Primera selección (check-in)
				selectedStartDate = date;
				updateReservationSummary();
				updateSelectionStates();
				
			} else if (selectedEndDate === null) {
				// Segunda selección (check-out)
				if (date <= selectedStartDate) {
					// Si la fecha es anterior o igual, hacer nueva selección
					clearSelection();
					selectedStartDate = date;
					updateReservationSummary();
					updateSelectionStates();
				} else {
					// Validar que no haya días ocupados en el rango
					if (validateDateRange(selectedStartDate, date)) {
						selectedEndDate = date;
						updateReservationSummary();
						updateSelectionStates();
					} else {
						showRangeError();
					}
				}
			} else {
				// Nueva selección
				clearSelection();
				selectedStartDate = date;
				selectedEndDate = null;
				updateReservationSummary();
				updateSelectionStates();
			}
		}
		
		// Limpiar selección
		function clearSelection() {
			selectedStartDate = null;
			selectedEndDate = null;
			updateSelectionStates();
		}
		
		
		// Validar rango de fechas
		function validateDateRange(startDate, endDate) {
			const start = new Date(startDate);
			const end = new Date(endDate);
			
			// Verificar cada día en el rango
			for (let d = new Date(start); d < end; d.setDate(d.getDate() + 1)) {
				const dateString = d.toISOString().split('T')[0];
				if (occupiedDates.includes(dateString)) {
					return false;
				}
			}
			return true;
		}
		
		// Mostrar error de rango
		function showRangeError() {
			const modalElement = document.getElementById('rangeErrorModal');
			if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
				// Bootstrap 5
				const modal = new bootstrap.Modal(modalElement);
				modal.show();
			} else if (typeof $ !== 'undefined' && $.fn.modal) {
				// Bootstrap 4 con jQuery
				$(modalElement).modal('show');
			} else {
				// Fallback manual
				modalElement.style.display = 'block';
				modalElement.classList.add('show');
				document.body.classList.add('modal-open');
				
				// Crear backdrop
				const backdrop = document.createElement('div');
				backdrop.className = 'modal-backdrop fade show';
				backdrop.id = 'rangeErrorBackdrop';
				document.body.appendChild(backdrop);
			}
		}
		
		// Actualizar estados visuales de selección
		function updateSelectionStates() {
			document.querySelectorAll('.calendar-day').forEach(day => {
				day.classList.remove('checkin', 'checkout', 'in-range');
				
				if (day.dataset.date) {
					const dayDateString = day.dataset.date;
					
					// Convertir selectedStartDate y selectedEndDate a strings para comparar
					const startDateString = selectedStartDate ? selectedStartDate.toISOString().split('T')[0] : null;
					const endDateString = selectedEndDate ? selectedEndDate.toISOString().split('T')[0] : null;
					
					if (selectedStartDate && dayDateString === startDateString) {
						day.classList.add('checkin');
					} else if (selectedEndDate && dayDateString === endDateString) {
						day.classList.add('checkout');
					} else if (selectedStartDate && selectedEndDate && 
							  dayDateString > startDateString && dayDateString < endDateString) {
						day.classList.add('in-range');
					}
				}
			});
		}
		
		
		// Actualizar resumen de reserva
		function updateReservationSummary() {
			const checkinDate = document.getElementById('checkinDate');
			const checkoutDate = document.getElementById('checkoutDate');
			const nightsCount = document.getElementById('nightsCount');
			const totalPrice = document.getElementById('totalPrice');
			const reserveBtn = document.getElementById('reserveBtn');
			
			if (selectedStartDate) {
				checkinDate.textContent = selectedStartDate.toLocaleDateString('es-CO');
				
				if (selectedEndDate) {
					checkoutDate.textContent = selectedEndDate.toLocaleDateString('es-CO');
					
					// Calcular noches
					const timeDiff = selectedEndDate.getTime() - selectedStartDate.getTime();
					const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
					nightsCount.textContent = nights;
					
					// Calcular total
					const total = nights * basePrice;
					totalPrice.textContent = '$' + total.toLocaleString('es-CO') + ' COP';
					
					// Habilitar botón de reserva
					reserveBtn.disabled = false;
				} else {
					checkoutDate.textContent = '-';
					nightsCount.textContent = '-';
					totalPrice.textContent = '$0 COP';
					reserveBtn.disabled = true;
				}
			} else {
				checkinDate.textContent = '-';
				checkoutDate.textContent = '-';
				nightsCount.textContent = '-';
				totalPrice.textContent = '$0 COP';
				reserveBtn.disabled = true;
			}
		}
		
		// Configurar event listeners
		function setupEventListeners() {
			// Navegación del calendario
			document.getElementById('prevMonth').addEventListener('click', () => {
				console.log('Botón anterior clickeado');
				currentDate.setMonth(currentDate.getMonth() - 1);
				console.log('Nueva fecha:', currentDate);
				renderCalendar();
			});
			
			document.getElementById('nextMonth').addEventListener('click', () => {
				console.log('Botón siguiente clickeado');
				currentDate.setMonth(currentDate.getMonth() + 1);
				console.log('Nueva fecha:', currentDate);
				renderCalendar();
			});
			
			// Deseleccionar al hacer clic fuera del calendario
			document.addEventListener('click', (e) => {
				// Si hay una selección activa (check-in seleccionado pero no check-out)
				if (selectedStartDate && !selectedEndDate) {
					// Verificar si el clic fue fuera del calendario
					const calendarContainer = document.querySelector('.calendar-container');
					const isClickInsideCalendar = calendarContainer && calendarContainer.contains(e.target);
					
					// También verificar que no sea clic en botones de navegación del calendario
					const isNavigationClick = e.target.id === 'prevMonth' || e.target.id === 'nextMonth';
					
					// Si el clic fue fuera del calendario y no es navegación
					if (!isClickInsideCalendar && !isNavigationClick) {
						console.log('Clic fuera del calendario - deseleccionando check-in');
						clearSelection();
						updateReservationSummary();
						
					}
				}
			});
			
			// Botón de cerrar del modal de reserva
			const closeBtn = document.querySelector('#reservationModal .btn-close');
			if (closeBtn) {
				closeBtn.addEventListener('click', () => {
					// Cerrar modal manualmente
					const modal = document.getElementById('reservationModal');
					modal.style.display = 'none';
					modal.classList.remove('show');
					document.body.classList.remove('modal-open');
					
					// Remover backdrop si existe
					const backdrop = document.querySelector('.modal-backdrop');
					if (backdrop) {
						backdrop.remove();
					}
				});
			}
			
			// Botón de cerrar del modal de error
			const closeErrorBtn = document.querySelector('#rangeErrorModal .btn-close');
			if (closeErrorBtn) {
				closeErrorBtn.addEventListener('click', () => {
					// Cerrar modal manualmente
					const modal = document.getElementById('rangeErrorModal');
					modal.style.display = 'none';
					modal.classList.remove('show');
					document.body.classList.remove('modal-open');
					
					// Remover backdrop si existe
					const backdrop = document.querySelector('.modal-backdrop');
					if (backdrop) {
						backdrop.remove();
					}
				});
			}
			
			// Botón de reserva
			document.getElementById('reserveBtn').addEventListener('click', () => {
				if (selectedStartDate && selectedEndDate) {
					openReservationModal();
				}
			});
			
			// Modal de reserva
			document.getElementById('submitReservation').addEventListener('click', submitReservation);
			
			// Botón cancelar del modal
			document.getElementById('cancelReservation').addEventListener('click', function() {
				// Cerrar modal manualmente
				const modal = document.getElementById('reservationModal');
				modal.style.display = 'none';
				modal.classList.remove('show');
				document.body.classList.remove('modal-open');
				const backdrop = document.querySelector('.modal-backdrop');
				if (backdrop) {
					backdrop.remove();
				}
				
				// Limpiar formulario
				document.getElementById('reservationForm').reset();
				
				// Limpiar selección del calendario
				clearSelection();
			});
			
			// Cerrar modal al hacer click en el backdrop
			document.getElementById('reservationModal').addEventListener('click', (e) => {
				if (e.target.id === 'reservationModal') {
					closeModal();
				}
			});
			
			// Botón "Entendido" del modal de error
			const entendidoBtn = document.querySelector('#rangeErrorModal .btn-primary');
			if (entendidoBtn) {
				entendidoBtn.addEventListener('click', () => {
					const modalElement = document.getElementById('rangeErrorModal');
					if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
						// Bootstrap 5
						const modal = bootstrap.Modal.getInstance(modalElement);
						if (modal) modal.hide();
					} else if (typeof $ !== 'undefined' && $.fn.modal) {
						// Bootstrap 4 con jQuery
						$(modalElement).modal('hide');
					} else {
						// Fallback manual
						modalElement.style.display = 'none';
						modalElement.classList.remove('show');
						document.body.classList.remove('modal-open');
						
						// Remover backdrop
						const backdrop = document.getElementById('rangeErrorBackdrop');
						if (backdrop) backdrop.remove();
					}
				});
			}
			
			// Checkbox de Palmira
			document.getElementById('vivePalmira').addEventListener('change', (e) => {
				const palmiraInfo = document.getElementById('palmiraInfo');
				palmiraInfo.style.display = e.target.checked ? 'block' : 'none';
			});
			
			// Método de pago
			document.querySelectorAll('input[name="metodoPago"]').forEach(radio => {
				radio.addEventListener('change', (e) => {
					const descuentoInfo = document.getElementById('descuentoInfo');
					if (e.target.value === 'efectivo') {
						descuentoInfo.style.display = 'block';
						updateModalCostSummary();
					} else {
						descuentoInfo.style.display = 'none';
						updateModalCostSummary();
					}
				});
			});
			
			// Fecha de nacimiento - actualizar descuento por cumpleaños
			document.getElementById('fechaNacimiento').addEventListener('change', () => {
				updateModalCostSummary();
			});
		}
		
		// Abrir modal de reserva
		function openReservationModal() {
			// Actualizar fechas en el modal
			document.getElementById('modalCheckinDate').textContent = selectedStartDate.toLocaleDateString('es-CO');
			document.getElementById('modalCheckoutDate').textContent = selectedEndDate.toLocaleDateString('es-CO');
			
			// Actualizar resumen de costos
			updateModalCostSummary();
			
			// Mostrar modal - Múltiples métodos de compatibilidad
			const modalElement = document.getElementById('reservationModal');
			
			// Método 1: Bootstrap 5
			if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
				const modal = new bootstrap.Modal(modalElement);
				modal.show();
			}
			// Método 2: Bootstrap 4 con jQuery
			else if (typeof $ !== 'undefined' && $.fn.modal) {
				$(modalElement).modal('show');
			}
			// Método 3: Fallback manual
			else {
				modalElement.style.display = 'block';
				modalElement.classList.add('show');
				document.body.classList.add('modal-open');
				
				// Crear backdrop
				const backdrop = document.createElement('div');
				backdrop.className = 'modal-backdrop fade show';
				backdrop.id = 'modalBackdrop';
				document.body.appendChild(backdrop);
			}
		}
		
		// Cerrar modal
		function closeModal() {
			const modalElement = document.getElementById('reservationModal');
			
			// Método 1: Bootstrap 5
			if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
				const modal = bootstrap.Modal.getInstance(modalElement);
				if (modal) modal.hide();
			}
			// Método 2: Bootstrap 4 con jQuery
			else if (typeof $ !== 'undefined' && $.fn.modal) {
				$(modalElement).modal('hide');
			}
			// Método 3: Fallback manual
			else {
				modalElement.style.display = 'none';
				modalElement.classList.remove('show');
				document.body.classList.remove('modal-open');
				
				// Remover backdrop
				const backdrop = document.getElementById('modalBackdrop');
				if (backdrop) backdrop.remove();
			}
		}
		
		// Actualizar resumen de costos en el modal
		function updateModalCostSummary() {
			const nights = Math.ceil((selectedEndDate.getTime() - selectedStartDate.getTime()) / (1000 * 3600 * 24));
			const subtotal = nights * basePrice;
			
			// Verificar método de pago seleccionado
			const metodoPago = document.querySelector('input[name="metodoPago"]:checked').value;
			let descuentoEfectivo = 0;
			let descuentoFidelidad = 0;
			let descuentoCumpleanos = 0;
			let total = subtotal;
			
			// Descuento por pago en efectivo
			if (metodoPago === 'efectivo') {
				descuentoEfectivo = subtotal * <?php echo isset($descuentos['promocional']) && $descuentos['promocional']['activo'] ? $descuentos['promocional']['porcentaje'] / 100 : 0; ?>;
			}
			
			// Descuento por fidelidad - solo para usuarios logueados
			<?php if ($user_logged_in && isset($descuentos['fidelidad']) && $descuentos['fidelidad']['activo']): ?>
			descuentoFidelidad = subtotal * <?php echo $descuentos['fidelidad']['porcentaje'] / 100; ?>;
			
			// Verificar si el cumpleaños está dentro del rango de fechas de la reserva
			<?php if ($user_data && $user_data['fecha_nacimiento']): ?>
			// Usar fecha de nacimiento de la BD del usuario logueado (más segura)
			const fechaNacimientoBD = '<?php echo $user_data['fecha_nacimiento']; ?>';
			if (fechaNacimientoBD) {
				const fechaNac = new Date(fechaNacimientoBD);
				
				// Verificar si el cumpleaños está dentro del rango de fechas de la reserva
				const cumpleanosDia = fechaNac.getDate();
				const cumpleanosMes = fechaNac.getMonth();
				
				// Verificar si el cumpleaños está entre las fechas de entrada y salida
				const fechaEntrada = new Date(selectedStartDate);
				const fechaSalida = new Date(selectedEndDate);
				
				// Crear fecha de cumpleaños para el año de la reserva
				const añoReserva = fechaEntrada.getFullYear();
				const cumpleanosActual = new Date(añoReserva, cumpleanosMes, cumpleanosDia);
				
				// Verificar si el cumpleaños está dentro del rango de la reserva
				if (cumpleanosActual >= fechaEntrada && cumpleanosActual <= fechaSalida) {
					descuentoCumpleanos = subtotal * <?php echo isset($descuentos['cumpleanos']) && $descuentos['cumpleanos']['activo'] ? $descuentos['cumpleanos']['porcentaje'] / 100 : 0; ?>; // Descuento por cumpleaños
				}
			}
			<?php endif; ?>
			<?php endif; ?>
			
			// Calcular total con todos los descuentos
			total = subtotal - descuentoEfectivo - descuentoFidelidad - descuentoCumpleanos;
			
			// Actualizar elementos del modal
			document.getElementById('modalNights').textContent = nights;
			document.getElementById('modalSubtotal').textContent = '$' + subtotal.toLocaleString('es-CO') + ' COP';
			document.getElementById('modalDescuento').textContent = '$' + descuentoEfectivo.toLocaleString('es-CO') + ' COP';
			document.getElementById('modalTotal').textContent = '$' + total.toLocaleString('es-CO') + ' COP';
			
			// Mostrar/ocultar fila de descuento por efectivo
			const descuentoRow = document.getElementById('descuentoRow');
			if (metodoPago === 'efectivo' && descuentoEfectivo > 0) {
				descuentoRow.style.display = 'flex';
			} else {
				descuentoRow.style.display = 'none';
			}
			
			// Mostrar descuento por fidelidad si el usuario está logueado
			<?php if ($user_logged_in): ?>
			document.getElementById('modalFidelidad').textContent = '$' + descuentoFidelidad.toLocaleString('es-CO') + ' COP';
			
			// Mostrar/ocultar descuento por cumpleaños
			const cumpleanosRow = document.getElementById('cumpleanosRow');
			if (descuentoCumpleanos > 0) {
				cumpleanosRow.style.display = 'flex';
				document.getElementById('modalCumpleanos').textContent = '$' + descuentoCumpleanos.toLocaleString('es-CO') + ' COP';
				
				// Mostrar información sobre el cumpleaños
				<?php if ($user_data && $user_data['fecha_nacimiento']): ?>
				const fechaNacimientoBD = '<?php echo $user_data['fecha_nacimiento']; ?>';
				if (fechaNacimientoBD) {
					const fechaNac = new Date(fechaNacimientoBD);
					const cumpleanosDia = fechaNac.getDate();
					const cumpleanosMes = fechaNac.getMonth();
					const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
					document.getElementById('cumpleanosInfo').textContent = `Tu cumpleaños (${cumpleanosDia} de ${meses[cumpleanosMes]}) está en el rango de fechas seleccionado`;
				}
				<?php endif; ?>
			} else {
				cumpleanosRow.style.display = 'none';
			}
			<?php endif; ?>
		}
		
		// Enviar reserva
		function submitReservation() {
			const form = document.getElementById('reservationForm');
			const formData = new FormData(form);
			
			// Validaciones básicas
			if (!form.checkValidity()) {
				form.reportValidity();
				return;
			}
			
			// Recopilar datos
			const reservationData = {
				id_apartamento: 1,
				id_usuario: <?php echo $user_logged_in && isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>,
				nombre: formData.get('nombres'),
				apellido: formData.get('apellidos'),
				correo: formData.get('correo'),
				telefono: formData.get('celular'),
				fecha_nacimiento: formData.get('fechaNacimiento'),
				fecha_entrada: selectedStartDate.toISOString().split('T')[0],
				fecha_salida: selectedEndDate.toISOString().split('T')[0],
				num_adultos: parseInt(formData.get('adultos')),
				num_ninos: parseInt(formData.get('ninos')),
				vive_palmira: formData.get('vivePalmira') === 'on',
				metodo_pago: formData.get('metodoPago'),
				costo_base: (Math.ceil((selectedEndDate.getTime() - selectedStartDate.getTime()) / (1000 * 3600 * 24))) * basePrice,
				descuento_fidelizacion: (() => {
					<?php if ($user_logged_in): ?>
					// Calcular descuento por fidelidad como monto en pesos
					const nights = Math.ceil((selectedEndDate.getTime() - selectedStartDate.getTime()) / (1000 * 3600 * 24));
					const subtotal = nights * basePrice;
					return subtotal * <?php echo isset($descuentos['fidelidad']) && $descuentos['fidelidad']['activo'] ? $descuentos['fidelidad']['porcentaje'] / 100 : 0; ?>; // Descuento por fidelidad
					<?php endif; ?>
					return 0;
				})(),
				descuento_cumpleanios: (() => {
					<?php if ($user_logged_in && $user_data && $user_data['fecha_nacimiento']): ?>
					// Usar fecha de nacimiento de la BD del usuario logueado (más segura)
					const fechaNacimientoBD = '<?php echo $user_data['fecha_nacimiento']; ?>';
					if (fechaNacimientoBD) {
						const fechaNac = new Date(fechaNacimientoBD);
						
						// Verificar si el cumpleaños está dentro del rango de fechas de la reserva
						const cumpleanosDia = fechaNac.getDate();
						const cumpleanosMes = fechaNac.getMonth();
						
						// Verificar si el cumpleaños está entre las fechas de entrada y salida
						const fechaEntrada = new Date(selectedStartDate);
						const fechaSalida = new Date(selectedEndDate);
						
						// Crear fecha de cumpleaños para el año de la reserva
						const añoReserva = fechaEntrada.getFullYear();
						const cumpleanosActual = new Date(añoReserva, cumpleanosMes, cumpleanosDia);
						
						// Verificar si el cumpleaños está dentro del rango de la reserva
						if (cumpleanosActual >= fechaEntrada && cumpleanosActual <= fechaSalida) {
							// Calcular descuento por cumpleaños como monto en pesos
							const nights = Math.ceil((selectedEndDate.getTime() - selectedStartDate.getTime()) / (1000 * 3600 * 24));
							const subtotal = nights * basePrice;
							return subtotal * <?php echo isset($descuentos['cumpleanos']) && $descuentos['cumpleanos']['activo'] ? $descuentos['cumpleanos']['porcentaje'] / 100 : 0; ?>; // Descuento por cumpleaños
						}
					}
					<?php endif; ?>
					return 0;
				})(),
				descuento_promocional: formData.get('metodoPago') === 'efectivo' ? (Math.ceil((selectedEndDate.getTime() - selectedStartDate.getTime()) / (1000 * 3600 * 24))) * basePrice * <?php echo isset($descuentos['promocional']) && $descuentos['promocional']['activo'] ? $descuentos['promocional']['porcentaje'] / 100 : 0.03; ?> : 0,
				total: (() => {
					const nights = Math.ceil((selectedEndDate.getTime() - selectedStartDate.getTime()) / (1000 * 3600 * 24));
					const subtotal = nights * basePrice;
					let total = subtotal;
					
					// Descuento por pago en efectivo (3%)
					if (formData.get('metodoPago') === 'efectivo') {
						total = total * (1 - <?php echo isset($descuentos['promocional']) && $descuentos['promocional']['activo'] ? $descuentos['promocional']['porcentaje'] / 100 : 0.03; ?>);
					}
					
					<?php if ($user_logged_in): ?>
					// Descuento por fidelidad
					total = total * (1 - <?php echo isset($descuentos['fidelidad']) && $descuentos['fidelidad']['activo'] ? $descuentos['fidelidad']['porcentaje'] / 100 : 0.05; ?>);
					
					// Descuento por cumpleaños (30%) - verificar si está dentro del rango de fechas
					<?php if ($user_data && $user_data['fecha_nacimiento']): ?>
					const fechaNacimientoBD = '<?php echo $user_data['fecha_nacimiento']; ?>';
					if (fechaNacimientoBD) {
						const fechaNac = new Date(fechaNacimientoBD);
						
						// Verificar si el cumpleaños está dentro del rango de fechas de la reserva
						const cumpleanosDia = fechaNac.getDate();
						const cumpleanosMes = fechaNac.getMonth();
						
						// Verificar si el cumpleaños está entre las fechas de entrada y salida
						const fechaEntrada = new Date(selectedStartDate);
						const fechaSalida = new Date(selectedEndDate);
						
						// Crear fecha de cumpleaños para el año de la reserva
						const añoReserva = fechaEntrada.getFullYear();
						const cumpleanosActual = new Date(añoReserva, cumpleanosMes, cumpleanosDia);
						
						// Verificar si el cumpleaños está dentro del rango de la reserva
						if (cumpleanosActual >= fechaEntrada && cumpleanosActual <= fechaSalida) {
							total = total * (1 - <?php echo isset($descuentos['cumpleanos']) && $descuentos['cumpleanos']['activo'] ? $descuentos['cumpleanos']['porcentaje'] / 100 : 0.30; ?>); // Descuento adicional por cumpleaños
						}
					}
					<?php endif; ?>
					<?php endif; ?>
					
					return total;
				})()
			};
			
		// Debug: Mostrar datos que se van a enviar
		console.log('=== DATOS DE RESERVA ===');
		console.log('Costo base:', reservationData.costo_base);
		console.log('Descuento fidelización:', reservationData.descuento_fidelizacion);
		console.log('Descuento cumpleaños:', reservationData.descuento_cumpleanios);
		console.log('Descuento promocional:', reservationData.descuento_promocional);
		console.log('Total:', reservationData.total);
		
		// Funciones del perfil de usuario
		function showProfileInfo() {
			<?php if ($user_logged_in && $user_data): ?>
			const userInfo = {
				nombre: '<?php echo htmlspecialchars($user_data['nombre']); ?>',
				apellido: '<?php echo htmlspecialchars($user_data['apellido']); ?>',
				correo: '<?php echo htmlspecialchars($user_data['correo']); ?>',
				telefono: '<?php echo htmlspecialchars($user_data['telefono']); ?>',
				fechaNacimiento: '<?php echo $user_data['fecha_nacimiento'] ? $user_data['fecha_nacimiento'] : 'No registrada'; ?>'
			};
			
			alert(`👤 MY PROFILE\n\n` +
				  `Name: ${userInfo.nombre} ${userInfo.apellido}\n` +
				  `Email: ${userInfo.correo}\n` +
				  `Phone: ${userInfo.telefono}\n` +
				  `Birthday: ${userInfo.fechaNacimiento}\n\n` +
				  `🎂 Available Discounts:\n` +
				  `• Fidelity: <?php echo isset($descuentos['fidelidad']) ? $descuentos['fidelidad']['porcentaje'] : 5; ?>% (always)\n` +
				  `• Birthday: <?php echo isset($descuentos['cumpleanos']) ? $descuentos['cumpleanos']['porcentaje'] : 30; ?>% (if your birthday is in the date range)`);
			<?php endif; ?>
		}
		
		function showMyReservations() {
			alert('📅 MY RESERVATIONS\n\n' +
				  'This functionality will be available soon.\n' +
				  'You will be able to view your reservation history and status.');
		}
			
			// Enviar datos al servidor PHP
			fetch('process_reservation.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(reservationData)
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					alert('Solicitud enviada exitosamente. Te contactaremos pronto.');
					
					// Cerrar modal y limpiar formulario
					closeModal();
					form.reset();
					
					// Limpiar selección del calendario
					clearSelection();
					updateReservationSummary();
				} else {
					alert('❌ Error al enviar la reserva: ' + data.message);
				}
			})
			.catch(error => {
				console.error('Error:', error);
				alert('❌ Error al enviar la reserva. Por favor intenta de nuevo.');
			});
		}
		
		// Inicializar cuando el DOM esté listo
		document.addEventListener('DOMContentLoaded', initCalendar);
	</script>

	<?php if ($user_logged_in && $user_role !== 'admin'): ?>
	<!-- Modal de descuento para usuarios logueados -->
	<div id="discountModal" class="modal" style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
		<div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: none; border-radius: 15px; width: 80%; max-width: 500px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
			<div style="color: #FFA500; font-size: 48px; margin-bottom: 15px;">🎉</div>
			<h2 style="color: #007BFF; margin-bottom: 15px;">¡Bienvenido de vuelta!</h2>
			<p style="font-size: 18px; color: #333; margin-bottom: 20px;">
				Gracias por iniciar sesión, <strong><?php echo htmlspecialchars($user_name); ?></strong>.
			</p>
			<div style="background: linear-gradient(135deg, #FFA500, #FF8C00); color: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
				<h3 style="margin: 0; font-size: 28px;"><?php echo isset($descuentos['fidelidad']) ? $descuentos['fidelidad']['porcentaje'] : 5; ?>% DE DESCUENTO</h3>
				<p style="margin: 10px 0 0 0; font-size: 16px;">en tu próxima reserva</p>
			</div>
			<p style="color: #666; font-size: 14px; margin-bottom: 20px;">
				Este descuento se aplicará automáticamente al hacer tu reserva.
			</p>
			<button onclick="closeDiscountModal()" style="background: #007BFF; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-size: 16px; cursor: pointer; transition: background 0.3s;">
				¡Genial, gracias!
			</button>
		</div>
	</div>

	<script>
		// Mostrar modal de descuento después de 2 segundos
		setTimeout(function() {
			document.getElementById('discountModal').style.display = 'block';
		}, 2000);

		function closeDiscountModal() {
			document.getElementById('discountModal').style.display = 'none';
		}

		// Cerrar modal al hacer clic fuera de él
		window.onclick = function(event) {
			const modal = document.getElementById('discountModal');
			if (event.target == modal) {
				modal.style.display = 'none';
			}
		}
	</script>
	<?php endif; ?>


<script>
// Función para abrir el modal de login
// Función eliminada - ahora se usa login.php

// Funciones del modal eliminadas - ahora se usa login.php
</script>


</body>

</html>
