<?php
session_start();
include("conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CORRECCIÓN: Usar el nombre correcto del campo
    $email = trim($_POST['correo']);
    $password = trim($_POST['password']);

    // Buscar usuario por email
    $sql = "SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // Verificar contraseña
        $login_exitoso = false;
        
        // Intentar verificar con password_hash primero
        if (password_verify($password, $usuario['password'])) {
            $login_exitoso = true;
        } 
        // Si falla, verificar si es texto plano (solo para desarrollo)
        else if ($password === $usuario['password']) {
            $login_exitoso = true;
        }
        // Manejar contraseñas con formato incorrecto (como las que tienen $$)
        else if (substr($usuario['password'], 0, 2) === '$$') {
            // Remover el $ extra y verificar
            $password_corregida = substr($usuario['password'], 1);
            if (password_verify($password, $password_corregida)) {
                $login_exitoso = true;
            }
        }
        
        if ($login_exitoso) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            
            // Redirigir según el rol
            if ($usuario['rol'] === 'admin') {
                // Verificar si el archivo existe, si no redirigir a una página por defecto
                header("Location: admin/admin.php");
            } else {
                header("Location: home.php");
            }
            exit();
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Usuario no encontrado";
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="./css/cuenta.css" />
    <link rel="shortcut icon" type="image/jpg" href="img/image.png" />
</head>
<body>
    <div class="contenido">
        <nav>
            <img class="logo" src="./img/image2.png" alt="netbeamLogo" />
        </nav>
        <div class="caja">
            <h2>Iniciar sesión</h2>

            <!-- Mostrar errores -->
            <?php if (isset($error)): ?>
                <div class="error" style="color: red; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- FORMULARIO -->
            <form action="login.php" method="POST" class="form">
                <input
                    type="text"
                    name="correo"
                    placeholder="Correo electrónico o número de teléfono"
                    required
                    value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>"
                />
                <input 
                    type="password" 
                    name="password" 
                    placeholder="Contraseña" 
                    required 
                />
                <button type="submit">Iniciar sesión</button>
            </form>
            <!-- /FORMULARIO -->

            <div class="subscripcion">
                <p>¿Todavía sin Netbeam? 
                    <a href="registro.php"><span>Registrate ya.</span></a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>