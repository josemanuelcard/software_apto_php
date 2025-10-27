<?php
/**
 * Logout de Usuarios - Sistema de Reservas
 * My Suite In Cartagena
 */

session_start();

// Limpiar todas las variables de sesión específicas
unset($_SESSION['user_logged_in']);
unset($_SESSION['user_id']);
unset($_SESSION['user_nombre']);
unset($_SESSION['user_correo']);
unset($_SESSION['user_rol']);

// Limpiar variables de sesión de admin
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_nombre']);
unset($_SESSION['admin_correo']);

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redirigir a la página principal
header('Location: index.php');
exit;
?>
