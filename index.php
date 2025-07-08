<?php
session_start();
if (!isset($_SESSION['autenticado']) && !isset($_POST['login']) && !isset($_POST['registrar_usuario'])) {
    session_destroy(); // Cierra sesión al recargar sin login/registro
}

$conexion = new mysqli('localhost', 'root', '', 'listadopersonasedificio');
if ($conexion->connect_error) die("Conexión fallida: " . $conexion->connect_error);

$mensajeLogin = $mensajeRegistroUsuario = "";

if (isset($_POST['registrar_usuario'])) {
    $dni = $_POST['nuevo_dni'];
    $pass = $_POST['nueva_contrasena'];
    $v = $conexion->prepare("SELECT 1 FROM usuarios WHERE usuario=?");
    $v->bind_param("s", $dni);
    $v->execute(); $r = $v->get_result();
    if ($r->num_rows > 0) {
        $mensajeRegistroUsuario = "Ese DNI ya está registrado.";
    } else {
        $i = $conexion->prepare("INSERT INTO usuarios(usuario, contrasena) VALUES(?, SHA2(?,256))");
        $i->bind_param("ss", $dni, $pass);
        $mensajeRegistroUsuario = $i->execute() ? "Cuenta creada. Iniciá sesión." : "Error al crear cuenta.";
        $i->close();
    }
    $v->close();
}

if (isset($_POST['login'])) {
    $u = $_POST['dni'];
    $p = $_POST['contrasena'];
    $s = $conexion->prepare("SELECT 1 FROM usuarios WHERE usuario=? AND contrasena=SHA2(?,256)");
    $s->bind_param("ss", $u, $p);
    $s->execute(); $res = $s->get_result();
    if ($res->num_rows === 1) {
        $_SESSION['autenticado'] = true;
    } else {
        $mensajeLogin = "DNI o contraseña incorrectos.";
    }
    $s->close();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: /prueba/");
    exit();
}

$stmtEntradaMsg = "";
if (isset($_POST['registrar']) && !empty($_SESSION['autenticado'])) {
    $n = $_POST['nombre']; $a = $_POST['apellido'];
    $d = $_POST['dni']; $m = $_POST['motivo_visita'];
    $pv = $_POST['persona_visita']; $ing = date('Y-m-d H:i:s');
    $st = $conexion->prepare("INSERT INTO personas(nombre,apellido,dni,motivoVisita,personaQueVisita,ingreso) VALUES(?,?,?,?,?,?)");
    $st->bind_param("ssssss", $n,$a,$d,$m,$pv,$ing);
    $stmtEntradaMsg = $st->execute() ? "Entrada registrada." : "Error al registrar.";
    $st->close();
}

$stmtSalidaMsg = "";
if (isset($_POST['actualizar']) && !empty($_SESSION['autenticado'])) {
    $d = $_POST['dni_salida'];
    $eg = date('Y-m-d H:i:s');
    $st = $conexion->prepare("UPDATE personas SET egreso=? WHERE dni=?");
    $st->bind_param("ss", $eg, $d);
    $stmtSalidaMsg = $st->execute() ? "Salida actualizada." : "Error al actualizar.";
    $st->close();
}

$personas = [];
$rs = $conexion->query("SELECT * FROM personas ORDER BY ingreso DESC");
while ($row = $rs->fetch_assoc()) $personas[] = $row;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Control de Visitas</title>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="css/styles.css" rel="stylesheet"/>
    <style>html { scroll-behavior: smooth; } section { padding: 60px 0; }</style>
</head>
<body id="page-top">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top" id="sideNav">
    <span class="navbar-brand d-block d-lg-none">ASISTENCIA DE EDIFICIO</span>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navMenu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" href="#Inicio">Inicio</a></li>
            <?php if (!empty($_SESSION['autenticado'])): ?>
                <li class="nav-item"><a class="nav-link" href="#RegistroEntrada">Registro de entrada</a></li>
                <li class="nav-item"><a class="nav-link" href="#SalidaEdificio">Salida del edificio</a></li>
                <li class="nav-item"><a class="nav-link" href="#ListadoPersona">Listado persona</a></li>
                <li class="nav-item"><a class="nav-link" href="/prueba/?logout">Cerrar sesión</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<div class="container-fluid p-0">

<section id="Inicio">
    <div class="resume-section-content">
        <h1 class="mb-0">MENÚ <span class="text-primary">Inicio</span></h1>
        <p class="lead mb-5">EDIFICIO JOSÉ BONIFACIO 3063<br>BARRIO FLORES</p>
    </div>
</section>
<hr class="m-0">

<?php if (empty($_SESSION['autenticado'])): ?>
<section id="LoginRegistro">
    <div class="container py-5">
        <h2>Iniciar sesión</h2>
        <?php if ($mensajeLogin): ?><div class="alert alert-danger"><?= $mensajeLogin ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="login">
            <div class="mb-3"><label>DNI</label><input class="form-control" name="dni" required></div>
            <div class="mb-3"><label>Contraseña</label><input type="password" class="form-control" name="contrasena" required></div>
            <button class="btn btn-primary">Ingresar</button>
        </form>
        <hr>
        <h2>Crear cuenta nueva</h2>
        <?php if ($mensajeRegistroUsuario): ?><div class="alert alert-info"><?= $mensajeRegistroUsuario ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="registrar_usuario">
            <div class="mb-3"><label>DNI</label><input class="form-control" name="nuevo_dni" required></div>
            <div class="mb-3"><label>Contraseña</label><input type="password" class="form-control" name="nueva_contrasena" required></div>
            <button class="btn btn-secondary">Registrar</button>
        </form>
    </div>
</section>
<hr class="m-0">
<?php else: ?>

<section id="RegistroEntrada">
    <div class="resume-section-content">
        <h2>Registro de Entrada</h2>
        <?php if ($stmtEntradaMsg): ?><div class="alert alert-info"><?= $stmtEntradaMsg ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3"><label>Nombre</label><input class="form-control" name="nombre" required></div>
            <div class="mb-3"><label>Apellido</label><input class="form-control" name="apellido" required></div>
            <div class="mb-3"><label>DNI</label><input class="form-control" name="dni" required></div>
            <div class="mb-3"><label>Motivo</label><textarea class="form-control" name="motivo_visita" required></textarea></div>
            <div class="mb-3"><label>Persona a Visitar</label><input class="form-control" name="persona_visita" required></div>
            <button class="btn btn-primary" name="registrar">Registrar</button>
        </form>
    </div>
</section>
<hr class="m-0">

<section id="SalidaEdificio">
    <div class="resume-section-content">
        <h2>Salida del edificio</h2>
        <?php if ($stmtSalidaMsg): ?><div class="alert alert-info"><?= $stmtSalidaMsg ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3"><label>DNI</label><input class="form-control" name="dni_salida" required></div>
            <button class="btn btn-warning" name="actualizar">Actualizar</button>
        </form>
    </div>
</section>
<hr class="m-0">

<section id="ListadoPersona">
    <div class="resume-section-content">
        <h2>Listado de Invitados</h2>
        <table class="table mt-3">
            <thead><tr><th>Nombre</th><th>Apellido</th><th>DNI</th><th>Motivo</th><th>Persona</th><th>Ingreso</th><th>Salida</th></tr></thead>
            <tbody><?php foreach($personas as $p): ?>
                <tr><td><?=htmlspecialchars($p['nombre'])?></td><td><?=htmlspecialchars($p['apellido'])?></td><td><?=htmlspecialchars($p['dni'])?></td><td><?=htmlspecialchars($p['motivoVisita'])?></td><td><?=htmlspecialchars($p['personaQueVisita'])?></td><td><?=htmlspecialchars($p['ingreso'])?></td><td><?= $p['egreso']?htmlspecialchars($p['egreso']):'Pendiente'?></td></tr>
            <?php endforeach; ?></tbody>
        </table>
    </div>
</section>
<hr class="m-0">

<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
