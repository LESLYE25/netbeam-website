<?php
// Inicia la sesión para poder usar $_SESSION
session_start();

// 1. CONFIGURACIÓN DE CREDENCIALES FIJAS
// Puedes cambiarlas aquí. Estas credenciales NO se consultan en la base de datos.
define('ADMIN_EMAIL', 'admin@netbeam.com');
define('ADMIN_PASSWORD', 'superadmin123'); 

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['correo']);
    $password = trim($_POST['password']);

    // 2. VERIFICACIÓN DE CREDENCIALES FIJAS
    if ($email === ADMIN_EMAIL && $password === ADMIN_PASSWORD) {
        
        // Login exitoso: Establecer variables de sesión
        // Usamos un ID y nombre de usuario fijos.
        $_SESSION['usuario_id'] = 9999; 
        $_SESSION['usuario_nombre'] = 'Administrador Fijo';
        $_SESSION['usuario_rol'] = 'admin'; // ¡Lo más importante es el rol 'admin'!
        
        // 3. REDIRECCIÓN EXITOSA
        // Redirige al dashboard de administrador, que está en el mismo directorio.
        header("Location: admin/admin.php"); 
        exit();
        
    } else {
        $error = "Credenciales de Administrador Fijo incorrectas.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login Fijo</title>
    <link rel="stylesheet" href="../css/cuenta.css" /> 
    <link rel="shortcut icon" type="image/jpg" href="../img/image.png" />
</head>
<body>
    <div class="contenido">
        <nav>
            <img class="logo" src="../img/image2.png" alt="netbeamLogo" /> 
        </nav>
        <div class="caja">
            <h2>Acceso Admin Fijo</h2>

            <?php if (isset($error)): ?>
                <div class="error" style="color: red; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login_admin_fijo.php" method="POST" class="form">
                <input
                    type="text"
                    name="correo"
                    placeholder="Correo: admin@netbeam.com"
                    required
                    value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>"
                />
                <input 
                    type="password" 
                    name="password" 
                    placeholder="Contraseña: superadmin123" 
                    required 
                />
                <button type="submit">Iniciar sesión Admin</button>
            </form>
            <div class="subscripcion">
                <p>
                    <a href="../login.php"><span>Volver al Login Normal.</span></a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>