<?php
/**
 * Restablecer Contraseña - Sistema de Reservas
 * My Suite In Cartagena
 */

session_start();

// Verificar si ya está logueado
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$verified = $_GET['verified'] ?? false;
$validToken = false;
$user = null;

// Verificar token válido y que el código fue verificado
if ($token && $verified) {
    require_once '../config/database.php';
    
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        $query = "SELECT prt.*, u.nombre, u.apellido, u.correo 
                  FROM password_reset_tokens prt 
                  JOIN usuarios u ON prt.user_id = u.id_usuario 
                  WHERE prt.token = ? AND prt.expires_at > NOW() AND prt.used = FALSE";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tokenData) {
            $validToken = true;
            $user = $tokenData;
        } else {
            $error = 'Token inválido o expirado';
        }
        
    } catch (Exception $e) {
        $error = 'Error del sistema: ' . $e->getMessage();
    }
} elseif ($token && !$verified) {
    // Si tiene token pero no está verificado, redirigir a verificación
    header('Location: verify-code.php?token=' . $token);
    exit;
} else {
    $error = 'Acceso no autorizado';
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Por favor, complete todos los campos';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($newPassword) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } else {
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Actualizar contraseña
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateResult = $updateStmt->execute([$hashedPassword, $user['user_id']]);
            
            if ($updateResult) {
                // Marcar token como usado
                $markUsedQuery = "UPDATE password_reset_tokens SET used = TRUE WHERE token = ?";
                $markUsedStmt = $pdo->prepare($markUsedQuery);
                $markUsedStmt->execute([$token]);
                
                $success = 'password_changed';
            } else {
                $error = 'Error al actualizar la contraseña';
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
    <title>Restablecer Contraseña - My Suite In Cartagena</title>
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
            border-color: rgb(25, 130, 151);
            box-shadow: 0 0 0 3px rgba(25, 130, 151, 0.1);
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
        .links-ihg a {
            color: rgb(25, 130, 151);
            text-decoration: none;
            display: block;
            margin-top: 5px;
            font-weight: 500;
        }
        .links-ihg {
            text-align: center;
            margin-top: 20px;
        }
        .info-text {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.5;
            font-size: 16px;
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
            background: linear-gradient(135deg, rgb(25, 130, 151), rgb(20, 110, 130));
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
            background: linear-gradient(135deg, rgb(20, 110, 130), rgb(15, 90, 110));
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="login-wrapper"> 
        <div class="login-container-ihg">
            <form action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" class="login-form-ihg" id="resetForm">
                <div class="form-left-panel">
                    <div class="logo-ihg">
                        <h1>My Suite In Cartagena </h1> 
                    </div>
                    <h2>Nueva Contraseña</h2>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb; text-align: center;">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success === 'password_changed'): ?>
                        <!-- Modal de confirmación -->
                        <div class="success-modal" id="successModal">
                            <div class="success-modal-content">
                                <div class="success-icon">✅</div>
                                <div class="success-title">¡Contraseña Actualizada!</div>
                                <div class="success-message">
                                    Tu contraseña ha sido cambiada exitosamente.<br>
                                    Ya puedes iniciar sesión con tu nueva contraseña.
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
                    
                    <?php if ($validToken): ?>
                        <div class="info-text">
                            ✅ <strong>Código verificado correctamente</strong><br>
                            Hola <strong><?php echo htmlspecialchars($user['nombre']); ?></strong>,<br>
                            Ahora puedes crear tu nueva contraseña.
                        </div>
                        
                        <div class="input-group-ihg password-group">
                            <label for="newPassword">Nueva Contraseña</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="newPassword" name="newPassword" placeholder="Mínimo 8 caracteres" required minlength="8">
                                <span class="toggle-password" onclick="togglePassword()"><i class="fas fa-eye"></i></span>
                            </div>
                            <span class="error-message" id="newPasswordError"></span> 
                        </div>

                        <div class="input-group-ihg password-group">
                            <label for="confirmPassword">Confirmar Nueva Contraseña</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Repite tu nueva contraseña" required minlength="8">
                                <span class="toggle-password" onclick="toggleConfirmPassword()"><i class="fas fa-eye"></i></span>
                            </div>
                            <span class="error-message" id="confirmPasswordError"></span> 
                        </div>
                        
                        <button type="submit" class="login-button-ihg">Cambiar Contraseña</button>
                    <?php else: ?>
                        <div class="info-text">
                            Enlace inválido o expirado. Por favor, solicita un nuevo enlace de recuperación.
                        </div>
                    <?php endif; ?>
                    
                    <div class="links-ihg">
                        <a href="login.php">Volver al Login</a> 
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Funciones para mostrar/ocultar contraseña
        function togglePassword() {
            const passwordInput = document.getElementById('newPassword');
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
        
        // Mostrar modal automáticamente si la contraseña fue cambiada
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

        // Validación del formulario
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Limpiar errores anteriores
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
            document.querySelectorAll('input').forEach(el => el.classList.remove('error'));
            
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            // Validar nueva contraseña
            if (!newPassword) {
                document.getElementById('newPasswordError').textContent = 'La nueva contraseña es requerida';
                document.getElementById('newPassword').classList.add('error');
                isValid = false;
            } else if (newPassword.length < 8) {
                document.getElementById('newPasswordError').textContent = 'La contraseña debe tener al menos 8 caracteres';
                document.getElementById('newPassword').classList.add('error');
                isValid = false;
            }
            
            // Validar confirmación de contraseña
            if (!confirmPassword) {
                document.getElementById('confirmPasswordError').textContent = 'Confirma tu nueva contraseña';
                document.getElementById('confirmPassword').classList.add('error');
                isValid = false;
            } else if (newPassword !== confirmPassword) {
                document.getElementById('confirmPasswordError').textContent = 'Las contraseñas no coinciden';
                document.getElementById('confirmPassword').classList.add('error');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
