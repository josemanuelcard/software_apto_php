<?php
/**
 * Registro de Usuarios - Sistema de Reservas
 * My Suite In Cartagena
 */

session_start();

// Verificar si ya está logueado
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    // Si es admin, redirigir al panel
    if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin') {
        header('Location: ../admin/index.php');
        exit;
    } else {
        // Si es cliente, redirigir al calendario
        header('Location: index.php');
        exit;
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $dob = $_POST['dob'] ?? '';
    
    // Validaciones
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword) || empty($phone) || empty($dob)) {
        $error = 'Por favor, complete todos los campos';
    } elseif ($password !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un email válido';
    } else {
        require_once '../config/database.php';
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            if (!$db) {
                throw new Exception("Error de conexión a la base de datos");
            }
            
            // Verificar si el email ya existe
            $query = "SELECT id_usuario FROM usuarios WHERE correo = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Este email ya está registrado';
            } else {
                // Crear nuevo usuario
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO usuarios (nombre, apellido, correo, contrasena, telefono, fecha_nacimiento, rol) VALUES (?, ?, ?, ?, ?, ?, 'cliente')";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $phone, $dob]);
                
                if ($result) {
                    $success = 'account_created';
                } else {
                    $error = 'Error al crear la cuenta. Inténtalo de nuevo.';
                }
            }
            
        } catch (Exception $e) {
            $error = 'Error del sistema: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - My Suite In Cartagena</title>
    <link rel="shortcut icon" href="images/favicon.png"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilos completos inline para asegurar que se carguen */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: url('../images/cartagena.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #343a40;
        }
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 600px; 
            padding: 20px;
            box-sizing: border-box;
        }
        .login-container-ihg {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            width: 100%;
            padding: 50px;
            box-sizing: border-box;
        }
        .login-form-ihg {
            width: 100%;
        }
        .form-left-panel {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .logo-ihg h1 {
            font-size: 28px;
            color: rgb(199, 156, 65);
            text-align: center;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .form-left-panel h2 {
            font-size: 24px;
            text-align: center;
            margin-bottom: 30px;
            color: #343a40;
            font-weight: 600;
        }
        .input-group-ihg {
            margin-bottom: 20px;
        }
        .input-group-ihg label {
            display: block;
            margin-bottom: 8px;
            font-size: 15px;
            font-weight: 600;
            color: #343a40;
        }
        .input-group-ihg input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .input-group-ihg input:focus {
            outline: none;
            border-color: rgb(199, 156, 65);
            box-shadow: 0 0 0 3px rgba(199, 156, 65, 0.1);
        }
        .login-button-ihg {
            background: rgb(199, 156, 65);
            color: white;
            padding: 18px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(199, 156, 65, 0.3);
            margin-top: 10px;
        }
        .login-button-ihg:hover {
            background: rgb(186, 117, 13);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(225, 143, 19, 0.7);
        }
        .register-button-ihg {
            background: rgb(199, 156, 65);
            color: white;
            padding: 12px 4px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            width: 100%;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(199, 156, 65, 0.3);
        }
        .register-button-ihg:hover {
            background: rgb(186, 117, 13);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(225, 143, 19, 0.7);
        }
        .password-input-wrapper {
            position: relative;
            width: 100%;
        }
        .password-input-wrapper input {
            padding-right: 40px;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #666;
            z-index: 10;
            padding: 5px;
            border-radius: 3px;
            transition: color 0.3s ease;
        }
        .toggle-password:hover {
            color: #007bff;
        }
        .links-ihg a {
            color: #007bff;
            text-decoration: none;
            display: block;
            margin-top: 5px;
        }
        .links-ihg .need-help {
            color: #343a40;
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .checkbox-group-ihg {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .checkbox-group-ihg input[type="checkbox"] {
            width: auto;
        }
        .checkbox-group-ihg label {
            margin: 0;
            font-weight: normal;
        }
        
        /* Estilos para secciones del formulario */
        .form-section {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .section-header h3 {
            font-size: 20px;
            color: rgb(199, 156, 65);
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .section-header p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }
        
        .section-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .next-btn, .back-btn {
            flex: 1;
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .next-btn {
            background: linear-gradient(135deg, rgb(199, 156, 65), rgb(186, 117, 13));
            color: white;
          
        }
        
        .next-btn:hover {
            background:rgb(186, 117, 13);
            transform: translateY(-2px);
        }
        
        .back-btn {
            background: #f8f9fa;
            color: #666;
            border: 2px solid #e1e5e9;
        }
        
        .back-btn:hover {
            background: #e9ecef;
            color: #495057;
            border-color: #ced4da;
        }
        
        /* Indicador de progreso */
        .progress-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .progress-step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
        }
        
        .progress-step.active {
            background: rgb(199, 156, 65);
            color: white;
        }
        
        .progress-step.completed {
            background:rgb(255, 213, 0);
            color: white;
        }
        
        .progress-step.inactive {
            background: #e9ecef;
            color: #6c757d;
        }
        
        /* Modal de confirmación */
        .success-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }
        
        .success-modal-content {
            background: white;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .success-icon {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .success-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .success-message {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .success-btn {
            background: linear-gradient(135deg, rgb(199, 156, 65), rgb(186, 117, 13));
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .success-btn:hover {
            background: linear-gradient(135deg, rgb(199, 156, 65), rgb(186, 117, 13));
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="login-wrapper"> 
        <div class="login-container-ihg">
            <form action="register.php" method="POST" class="login-form-ihg" id="registrationForm">
                <div class="form-left-panel">
                    <div class="logo-ihg">
                        <h1> My Suite In Cartagena</h1> 
                    </div>
                    <h2>Crear una Cuenta</h2>
                    
                    <!-- Indicador de progreso -->
                    <div class="progress-indicator">
                        <div class="progress-step active" id="step1">1</div>
                        <div class="progress-step inactive" id="step2">2</div>
                        <div class="progress-step inactive" id="step3">3</div>
                        <div class="progress-step inactive" id="step4">4</div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb; text-align: center;">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success === 'account_created'): ?>
                        <!-- Modal de confirmación -->
                        <div class="success-modal" id="successModal">
                            <div class="success-modal-content">
                                <div class="success-icon">✅</div>
                                <div class="success-title">¡Cuenta Creada!</div>
                                <div class="success-message">
                                    Tu cuenta ha sido creada exitosamente.<br>
                                    Ya puedes iniciar sesión con tus credenciales.
                                </div>
                                <div id="countdown" style="margin-bottom: 20px; color: #666; font-size: 14px;">
                                    Redirigiendo automáticamente en <span id="timer">3</span> segundos...
                                </div>
                                <button class="success-btn" onclick="redirectToLogin()">
                                    Ir al Login
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Sección 1: Nombre y Apellido -->
                    <div class="form-section" id="section1">
                        <div class="section-header">
                            <h3>Paso 1 de 4</h3>
                            <p>Información Personal</p>
                        </div>
                        
                        <div class="input-group-ihg">
                            <label for="firstName">Nombre</label>
                            <input type="text" id="firstName" name="firstName" placeholder="Tu nombre" value="<?php echo htmlspecialchars($firstName ?? ''); ?>" required>
                            <span class="error-message" id="firstNameError"></span> 
                        </div>

                        <div class="input-group-ihg">
                            <label for="lastName">Apellido</label>
                            <input type="text" id="lastName" name="lastName" placeholder="Tu apellido" value="<?php echo htmlspecialchars($lastName ?? ''); ?>" required>
                            <span class="error-message" id="lastNameError"></span> 
                        </div>
                        
                        <button type="button" class="next-btn" onclick="nextSection(1)">Continuar</button>
                    </div>

                    <!-- Sección 2: Email -->
                    <div class="form-section" id="section2" style="display: none;">
                        <div class="section-header">
                            <h3>Paso 2 de 4</h3>
                            <p>Correo Electrónico</p>
                        </div>
                        
                        <div class="input-group-ihg">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="ej: tu_nombre@email.com" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            <span class="error-message" id="emailError"></span> 
                        </div>
                        
                        <div class="section-buttons">
                            <button type="button" class="back-btn" onclick="prevSection(2)">Atrás</button>
                            <button type="button" class="next-btn" onclick="nextSection(2)">Continuar</button>
                        </div>
                    </div>

                    <!-- Sección 3: Contraseñas -->
                    <div class="form-section" id="section3" style="display: none;">
                        <div class="section-header">
                            <h3>Paso 3 de 4</h3>
                            <p>Contraseña de Seguridad</p>
                        </div>
                        
                        <div class="input-group-ihg password-group">
                            <label for="password">Contraseña</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required minlength="8">
                                <span class="toggle-password" onclick="togglePassword()"><i class="fas fa-eye"></i></span>
                            </div>
                            <span class="error-message" id="passError"></span> 
                        </div>

                        <div class="input-group-ihg password-group">
                            <label for="confirmPassword">Confirmar Contraseña</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Repite tu contraseña" required minlength="8">
                                <span class="toggle-password" onclick="toggleConfirmPassword()"><i class="fas fa-eye"></i></span>
                            </div>
                            <span class="error-message" id="confirmPassError"></span> 
                        </div>
                        
                        <div class="section-buttons">
                            <button type="button" class="back-btn" onclick="prevSection(3)">Atrás</button>
                            <button type="button" class="next-btn" onclick="nextSection(3)">Continuar</button>
                        </div>
                    </div>

                    <!-- Sección 4: Teléfono y Fecha -->
                    <div class="form-section" id="section4" style="display: none;">
                        <div class="section-header">
                            <h3>Paso 4 de 4</h3>
                            <p>Información de Contacto</p>
                        </div>
                        
                        <div class="input-group-ihg">
                            <label for="phone">Teléfono</label>
                            <input type="tel" id="phone" name="phone" placeholder="Ej: 300 123 4567" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
                            <span class="error-message" id="phoneError"></span> 
                        </div>

                        <div class="input-group-ihg">
                            <label for="dob">Fecha de Nacimiento</label>
                            <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($dob ?? ''); ?>" required>
                            <span class="error-message" id="dobError"></span> 
                        </div>
                        
                        <div class="section-buttons">
                            <button type="button" class="back-btn" onclick="prevSection(4)">Atrás</button>
                            <button type="submit" class="login-button-ihg">Registrarme</button>
                        </div>
                    </div>
                    
                    <div class="links-ihg" style="text-align: center; margin-top: 20px;">
                        <a href="login.php" style="color: rgb(199, 156, 65); text-decoration: none; font-weight: 500;">¿Ya tienes una cuenta? Iniciar Sesión</a> 
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Variables globales para el control de secciones
        let currentSection = 1;
        const totalSections = 4;
        
        // Funciones para navegación entre secciones
        function nextSection(sectionNumber) {
            if (validateSection(sectionNumber)) {
                // Marcar sección actual como completada
                document.getElementById(`step${sectionNumber}`).classList.remove('active');
                document.getElementById(`step${sectionNumber}`).classList.add('completed');
                
                // Ocultar sección actual
                document.getElementById(`section${sectionNumber}`).style.display = 'none';
                
                // Mostrar siguiente sección
                const nextSectionNum = sectionNumber + 1;
                document.getElementById(`section${nextSectionNum}`).style.display = 'block';
                
                // Actualizar indicador de progreso
                document.getElementById(`step${nextSectionNum}`).classList.remove('inactive');
                document.getElementById(`step${nextSectionNum}`).classList.add('active');
                
                currentSection = nextSectionNum;
            }
        }
        
        function prevSection(sectionNumber) {
            // Marcar sección actual como inactiva
            document.getElementById(`step${sectionNumber}`).classList.remove('active');
            document.getElementById(`step${sectionNumber}`).classList.add('inactive');
            
            // Ocultar sección actual
            document.getElementById(`section${sectionNumber}`).style.display = 'none';
            
            // Mostrar sección anterior
            const prevSectionNum = sectionNumber - 1;
            document.getElementById(`section${prevSectionNum}`).style.display = 'block';
            
            // Actualizar indicador de progreso
            document.getElementById(`step${prevSectionNum}`).classList.remove('completed');
            document.getElementById(`step${prevSectionNum}`).classList.add('active');
            
            currentSection = prevSectionNum;
        }
        
        // Función para validar cada sección
        function validateSection(sectionNumber) {
            let isValid = true;
            
            // Limpiar errores anteriores
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
            document.querySelectorAll('input').forEach(el => el.classList.remove('error'));
            
            if (sectionNumber === 1) {
                // Validar nombre y apellido
                const firstName = document.getElementById('firstName').value.trim();
                const lastName = document.getElementById('lastName').value.trim();
                
                if (!firstName) {
                    document.getElementById('firstNameError').textContent = 'El nombre es requerido';
                    document.getElementById('firstName').classList.add('error');
                    isValid = false;
                }
                
                if (!lastName) {
                    document.getElementById('lastNameError').textContent = 'El apellido es requerido';
                    document.getElementById('lastName').classList.add('error');
                    isValid = false;
                }
            } else if (sectionNumber === 2) {
                // Validar email
                const email = document.getElementById('email').value.trim();
                
                if (!email) {
                    document.getElementById('emailError').textContent = 'El email es requerido';
                    document.getElementById('email').classList.add('error');
                    isValid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    document.getElementById('emailError').textContent = 'Ingresa un email válido';
                    document.getElementById('email').classList.add('error');
                    isValid = false;
                }
            } else if (sectionNumber === 3) {
                // Validar contraseñas
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                
                if (!password) {
                    document.getElementById('passError').textContent = 'La contraseña es requerida';
                    document.getElementById('password').classList.add('error');
                    isValid = false;
                } else if (password.length < 8) {
                    document.getElementById('passError').textContent = 'La contraseña debe tener al menos 8 caracteres';
                    document.getElementById('password').classList.add('error');
                    isValid = false;
                }
                
                if (!confirmPassword) {
                    document.getElementById('confirmPassError').textContent = 'Confirma tu contraseña';
                    document.getElementById('confirmPassword').classList.add('error');
                    isValid = false;
                } else if (password !== confirmPassword) {
                    document.getElementById('confirmPassError').textContent = 'Las contraseñas no coinciden';
                    document.getElementById('confirmPassword').classList.add('error');
                    isValid = false;
                }
            } else if (sectionNumber === 4) {
                // Validar teléfono y fecha de nacimiento
                const phone = document.getElementById('phone').value.trim();
                const dob = document.getElementById('dob').value;
                
                if (!phone) {
                    document.getElementById('phoneError').textContent = 'El teléfono es requerido';
                    document.getElementById('phone').classList.add('error');
                    isValid = false;
                }
                
                if (!dob) {
                    document.getElementById('dobError').textContent = 'La fecha de nacimiento es requerida';
                    document.getElementById('dob').classList.add('error');
                    isValid = false;
                }
            }
            
            return isValid;
        }
        
        // Funciones para mostrar/ocultar contraseña
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-input-wrapper .toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                toggleIcon.innerHTML = '<i class="fas fa-eye"></i>';
            }
        }
        
        function toggleConfirmPassword() {
            const passwordInput = document.getElementById('confirmPassword');
            const toggleIcon = document.querySelectorAll('.password-input-wrapper .toggle-password')[1];
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                toggleIcon.innerHTML = '<i class="fas fa-eye"></i>';
            }
        }
        

        // Función para redirigir al login
        function redirectToLogin() {
            window.location.href = 'login.php';
        }
        
        // Mostrar modal automáticamente si la cuenta fue creada
        document.addEventListener('DOMContentLoaded', function() {
            const successModal = document.getElementById('successModal');
            if (successModal) {
                let countdown = 3;
                const timerElement = document.getElementById('timer');
                
                // Actualizar contador cada segundo
                const countdownInterval = setInterval(function() {
                    countdown--;
                    if (timerElement) {
                        timerElement.textContent = countdown;
                    }
                    
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        redirectToLogin();
                    }
                }, 1000);
            }
        });

        // Validación final del formulario (solo para la última sección)
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            // Validar la última sección antes de enviar
            if (!validateSection(4)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
