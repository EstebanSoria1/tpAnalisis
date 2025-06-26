<?php
session_start();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "listadopersonasedificio");
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Cierre de sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Mensajes
$mensajeLogin = "";
$mensajeRegistroUsuario = "";
$mensajeRegistroVisita = "";

// Registro de usuario (crear cuenta)
if (isset($_POST['crear_usuario'])) {
    $nuevoUsuario = $_POST['nuevo_usuario']; // DNI
    $nuevaContrasena = $_POST['nueva_contrasena'];

    $verificar = $conexion->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $verificar->bind_param("s", $nuevoUsuario);
    $verificar->execute();
    $resultado = $verificar->get_result();

    if ($resultado->num_rows > 0) {
        $mensajeRegistroUsuario = "Ese DNI ya está registrado como usuario.";
    } else {
        $stmt = $conexion->prepare("INSERT INTO usuarios (usuario, contrasena) VALUES (?, SHA2(?, 256))");
        $stmt->bind_param("ss", $nuevoUsuario, $nuevaContrasena);
        if ($stmt->execute()) {
            $mensajeRegistroUsuario = "Cuenta creada. Ahora podés iniciar sesión.";
        } else {
            $mensajeRegistroUsuario = "Error al crear la cuenta.";
        }
        $stmt->close();
    }
}

// Login
if (isset($_POST['login'])) {
    $usuario = $_POST['usuario']; // DNI
    $contrasena = $_POST['contrasena'];

    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE usuario = ? AND contrasena = SHA2(?, 256)");
    $stmt->bind_param("ss", $usuario, $contrasena);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $_SESSION['autenticado'] = true;
        $_SESSION['usuario'] = $usuario;
    } else {
        $mensajeLogin = "DNI o contraseña incorrectos.";
    }
    $stmt->close();
}

// Registro de visita
if (isset($_POST['registrar']) && isset($_SESSION['autenticado'])) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $dni = $_POST['dni'];
    $motivo = $_POST['motivo'];
    $fecha = $_POST['fecha'];

    $sql = "INSERT INTO visitas (nombre, apellido, dni, motivo, fecha)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssss", $nombre, $apellido, $dni, $motivo, $fecha);

    if ($stmt->execute()) {
        $mensajeRegistroVisita = "Visita registrada exitosamente.";
    } else {
        $mensajeRegistroVisita = "Error al registrar visita.";
    }
    $stmt->close();
}

// Listado de visitas
$visitas = $conexion->query("SELECT * FROM visitas ORDER BY fecha DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="C:\xampp\htdocs\prueba\css\styles.css" rel="stylesheet"/>
</head>
<body class="bg-light">
    <div class="container mt-5">

        <?php if (!isset($_SESSION['autenticado'])): ?>
            <h2 class="mb-4">Inicio de Sesión</h2>

            <?php if ($mensajeLogin): ?>
                <div class="alert alert-danger"><?= $mensajeLogin ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="login" />
                <div class="mb-3">
                    <label class="form-label">DNI (usuario)</label>
                    <input type="text" class="form-control" name="usuario" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" class="form-control" name="contrasena" required />
                </div>
                <button type="submit" class="btn btn-primary">Ingresar</button>
            </form>

            <hr class="my-5" />

            <h2>Crear cuenta nueva</h2>
            <?php if ($mensajeRegistroUsuario): ?>
                <div class="alert alert-info"><?= $mensajeRegistroUsuario ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="crear_usuario" />
                <div class="mb-3">
                    <label class="form-label">DNI (será tu usuario)</label>
                    <input type="text" class="form-control" name="nuevo_usuario" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" class="form-control" name="nueva_contrasena" required />
                </div>
                <button type="submit" class="btn btn-secondary">Crear Cuenta</button>
            </form>

        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Registro de Visitas</h2>
                <a href="?logout" class="btn btn-danger">Cerrar sesión</a>
            </div>

            <?php if ($mensajeRegistroVisita): ?>
                <div class="alert alert-success"><?= $mensajeRegistroVisita ?></div>
            <?php endif; ?>

            <form method="POST" class="mb-4">
                <input type="hidden" name="registrar" />
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" name="nombre" class="form-control" placeholder="Nombre" required />
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="apellido" class="form-control" placeholder="Apellido" required />
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="dni" class="form-control" placeholder="DNI" required />
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="motivo" class="form-control" placeholder="Motivo" required />
                    </div>
                    <div class="col-md-4">
                        <input type="date" name="fecha" class="form-control" required />
                    </div>
                </div>
                <button type="submit" class="btn btn-success mt-3">Registrar Visita</button>
            </form>

            <h3 class="mt-5">Listado de Visitas</h3>
            <table class="table table-striped table-hover mt-3">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>DNI</th>
                        <th>Motivo</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($fila = $visitas->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($fila["nombre"]) ?></td>
                            <td><?= htmlspecialchars($fila["apellido"]) ?></td>
                            <td><?= htmlspecialchars($fila["dni"]) ?></td>
                            <td><?= htmlspecialchars($fila["motivo"]) ?></td>
                            <td><?= htmlspecialchars($fila["fecha"]) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
</body>
</html>
