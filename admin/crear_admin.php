<?php
/**
 * Crear Usuario Administrador - Sistema de Reservas
 * My Suite In Cartagena
 */

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    // Verificar si ya existe un admin
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'admin'");
    $stmt->execute();
    $admin_exists = $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    
    if ($admin_exists) {
        echo "<h2>⚠️ Usuario Administrador ya existe</h2>";
        echo "<p>Ya existe un usuario administrador en el sistema.</p>";
        echo "<p><a href='login.php'>Ir al Login</a></p>";
        exit;
    }
    
    // Crear usuario administrador
    $nombre = 'Jose';
    $apellido = 'Cardenas';
    $correo = 'admin@gmail.com';
    $contrasena = password_hash('123456', PASSWORD_DEFAULT);
    $telefono = '3106640949';
    $rol = 'admin';
    
    $query = "INSERT INTO usuarios (nombre, apellido, correo, contrasena, telefono, rol) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$nombre, $apellido, $correo, $contrasena, $telefono, $rol]);
    
    if ($result) {
        echo "<h2>✅ Usuario Administrador Creado Exitosamente</h2>";
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h3>📧 Credenciales de Acceso:</h3>";
        echo "<p><strong>Correo:</strong> admin@gmail.com</p>";
        echo "<p><strong>Contraseña:</strong> 123456</p>";
        echo "</div>";
        echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Login</a></p>";
    } else {
        throw new Exception("Error al crear el usuario administrador");
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Administrador - My Suite In Cartagena</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- El contenido se genera dinámicamente con PHP -->
    </div>
</body>
</html>
