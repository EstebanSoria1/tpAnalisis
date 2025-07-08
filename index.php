<?php
session_start();
if (!isset($_SESSION['autenticado']) && !isset($_POST['login']) && !isset($_POST['registrar_usuario'])) {
    session_destroy();
}

$conexion = new mysqli('localhost', 'root', '', 'listadopersonasedificio');
if ($conexion->connect_error) die("Conexión fallida: " . $conexion->connect_error);

$mensajeLogin = $mensajeRegistroUsuario = "";
$mensajeEdicion = "";

// REGISTRO USUARIO
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

// LOGIN
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

// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: /prueba/");
    exit();
}

// REGISTRAR ENTRADA
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

// REGISTRAR SALIDA
$stmtSalidaMsg = "";
if (isset($_POST['actualizar']) && !empty($_SESSION['autenticado'])) {
    $d = $_POST['dni_salida'];
    $eg = date('Y-m-d H:i:s');
    $st = $conexion->prepare("UPDATE personas SET egreso=? WHERE dni=?");
    $st->bind_param("ss", $eg, $d);
    $stmtSalidaMsg = $st->execute() ? "Salida actualizada." : "Error al actualizar.";
    $st->close();
}

// ELIMINAR PERSONA
if (isset($_POST['eliminar_dni']) && !empty($_SESSION['autenticado'])) {
    $d = $_POST['eliminar_dni'];
    $stmt = $conexion->prepare("DELETE FROM personas WHERE dni = ?");
    $stmt->bind_param("s", $d);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// FORMULARIO DE EDICIÓN
$personaEditar = null;
if (isset($_POST['editar_dni']) && !empty($_SESSION['autenticado'])) {
    $dniEditar = $_POST['editar_dni'];
    $stmt = $conexion->prepare("SELECT * FROM personas WHERE dni = ?");
    $stmt->bind_param("s", $dniEditar);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows === 1) {
        $personaEditar = $resultado->fetch_assoc();
    }
    $stmt->close();
}

// ACTUALIZAR DATOS EDITADOS
if (isset($_POST['guardar_edicion']) && !empty($_SESSION['autenticado'])) {
    $n = $_POST['editar_nombre'];
    $a = $_POST['editar_apellido'];
    $m = $_POST['editar_motivo'];
    $pv = $_POST['editar_persona'];
    $dni = $_POST['editar_dni_hidden'];
    $stmt = $conexion->prepare("UPDATE personas SET nombre=?, apellido=?, motivoVisita=?, personaQueVisita=? WHERE dni=?");
    $stmt->bind_param("sssss", $n, $a, $m, $pv, $dni);
    $mensajeEdicion = $stmt->execute() ? "Datos actualizados." : "Error al editar.";
    $stmt->close();
}

// LISTADO DE PERSONAS
$personas = [];
$rs = $conexion->query("SELECT * FROM personas ORDER BY ingreso DESC");
while ($row = $rs->fetch_assoc()) $personas[] = $row;
?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Visitas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
                <li class="nav-item"><a class="nav-link" href="?logout">Cerrar sesión</a></li>
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

<section id="ListadoPersona">
    <div class="resume-section-content">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Listado de Invitados</h2>
            <!-- Botón exportar PDF agregado -->
            <button class="btn btn-success" onclick="exportarPDF()">Exportar a PDF</button>
        </div>

        <?php if ($mensajeEdicion): ?><div class="alert alert-success"><?= $mensajeEdicion ?></div><?php endif; ?>

        <table class="table mt-3" id="tabla-personas">
            <thead>
                <tr><th>Nombre</th><th>Apellido</th><th>DNI</th><th>Motivo</th><th>Persona</th><th>Ingreso</th><th>Salida</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach($personas as $p): ?>
                    <tr>
                        <td><?=htmlspecialchars($p['nombre'])?></td>
                        <td><?=htmlspecialchars($p['apellido'])?></td>
                        <td><?=htmlspecialchars($p['dni'])?></td>
                        <td><?=htmlspecialchars($p['motivoVisita'])?></td>
                        <td><?=htmlspecialchars($p['personaQueVisita'])?></td>
                        <td><?=htmlspecialchars($p['ingreso'])?></td>
                        <td><?= $p['egreso'] ? htmlspecialchars($p['egreso']) : 'Pendiente'?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="eliminar_dni" value="<?= htmlspecialchars($p['dni']) ?>">
                                <button class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta visita?')">Eliminar</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="editar_dni" value="<?= htmlspecialchars($p['dni']) ?>">
                                <button class="btn btn-secondary btn-sm">Editar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($personaEditar): ?>
            <h3>Editar visitante</h3>
            <form method="POST">
                <input type="hidden" name="editar_dni_hidden" value="<?= htmlspecialchars($personaEditar['dni']) ?>">
                <div class="mb-2"><label>Nombre</label><input class="form-control" name="editar_nombre" value="<?= htmlspecialchars($personaEditar['nombre']) ?>" required></div>
                <div class="mb-2"><label>Apellido</label><input class="form-control" name="editar_apellido" value="<?= htmlspecialchars($personaEditar['apellido']) ?>" required></div>
                <div class="mb-2"><label>Motivo</label><input class="form-control" name="editar_motivo" value="<?= htmlspecialchars($personaEditar['motivoVisita']) ?>" required></div>
                <div class="mb-2"><label>Persona a Visitar</label><input class="form-control" name="editar_persona" value="<?= htmlspecialchars($personaEditar['personaQueVisita']) ?>" required></div>
                <button class="btn btn-success" name="guardar_edicion">Guardar Cambios</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Librería jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
const personas = <?php echo json_encode($personas); ?>;

async function exportarPDF() {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('l', 'pt', 'a4');
    const fechaActual = new Date().toLocaleString();

    pdf.setFontSize(14);
    pdf.text("Listado de Visitas - Edificio José Bonifacio 3063", 40, 40);
    pdf.setFontSize(10);
    pdf.text("Fecha de exportación: " + fechaActual, 40, 60);

    const columnas = ["Nombre", "Apellido", "DNI", "Motivo", "Persona", "Ingreso", "Salida"];
    
    const startX = 40;
    let startY = 90;
    const rowHeight = 20;
    const colWidth = 80;
    const spacing = 30; // Espacio entre columnas
	const spacingg = 10;
    // Dibujar encabezado
    columnas.forEach((col, i) => {
        pdf.text(col, startX + i * (colWidth + spacing), startY);
    });

    // Dibujar filas
    personas.forEach((p, index) => {
        const y = startY + rowHeight * (index + 1);
        pdf.text(p.nombre, startX + 0 * (colWidth + spacing), y);
        pdf.text(p.apellido, startX + 1 * (colWidth + spacing), y);
        pdf.text(p.dni, startX + 2 * (colWidth + spacing), y);
        pdf.text(p.motivoVisita, startX + 3 * (colWidth + (spacing-spacingg)), y);
        pdf.text(p.personaQueVisita, startX + 4 * (colWidth + spacing), y);
        pdf.text(p.ingreso, startX + 5 * (colWidth + spacing), y);
        pdf.text(p.egreso ? p.egreso : "Pendiente", startX + 6 * (colWidth + spacing), y);
    });

    pdf.save("visitas_edificio.pdf");
}


</script>

</body>
</html>
