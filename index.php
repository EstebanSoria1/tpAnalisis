<?php
// Conexión a la base de datos
$conexion = new mysqli('localhost', 'root', '', 'edificio');
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Registro de entrada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar'])) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $dni = $_POST['dni'];
    $motivoVisita = $_POST['motivo_visita'];
    $personaQueVisita = $_POST['persona_visita'];
    $ingreso = date('Y-m-d H:i:s');

    $stmt = $conexion->prepare("INSERT INTO personas (nombre, apellido, dni, motivoVisita, personaQueVisita, ingreso) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nombre, $apellido, $dni, $motivoVisita, $personaQueVisita, $ingreso);
    $stmt->execute();
    $stmt->close();
}

// Actualizar hora de salida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    $dni = $_POST['dni_salida'];
    $egreso = date('Y-m-d H:i:s');

    $stmt = $conexion->prepare("UPDATE personas SET egreso = ? WHERE dni = ?");
    $stmt->bind_param("ss", $egreso, $dni);
    $stmt->execute();
    $stmt->close();
}

// Filtrado y listado
$personas = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['filtrar'])) {
    $filtro = $_GET['filtro'];
    $valor = $_GET['valor'];

    if ($filtro === 'dni') {
        $stmt = $conexion->prepare("SELECT * FROM personas WHERE dni LIKE ?");
        $valor = "%$valor%";
        $stmt->bind_param("s", $valor);
    } elseif ($filtro === 'fecha') {
        $stmt = $conexion->prepare("SELECT * FROM personas WHERE DATE(ingreso) = ?");
        $stmt->bind_param("s", $valor);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $personas[] = $row;
    }
    $stmt->close();
} else {
    $result = $conexion->query("SELECT * FROM personas");
    while ($row = $result->fetch_assoc()) {
        $personas[] = $row;
    }
}

$seccion = $_GET['seccion'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registro de Invitados</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css?family=Saira+Extra+Condensed:500,700" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Muli:400,400i,800,800i" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
</head>

<body id="page-top">
    <!-- Navegación lateral -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top" id="sideNav">
        <span class="d-block d-lg-none">ASISTENCIA DE EDIFICIO</span>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive"
            aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#Inicio">Inicio</a></li>
                <li class="nav-item"><a class="nav-link js-scroll-trigger" href="?seccion=registrar#RegistroEntrada">Registro de entrada</a></li>
                <li class="nav-item"><a class="nav-link js-scroll-trigger" href="?seccion=salida#SalidaEdificio">Salida del edificio</a></li>
                <li class="nav-item"><a class="nav-link js-scroll-trigger" href="?seccion=listado#ListadoPersona">Listado persona</a></li>
            </ul>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="container-fluid p-0">
        <!-- Sección Inicio -->
        <section class="resume-section" id="Inicio">
            <div class="resume-section-content">
                <h1 class="mb-0">MENÚ <span class="text-primary">Inicio</span></h1>
                <p class="lead mb-5">EDIFICIO JOSÉ BONIFACIO 3063<br>BARRIO FLORES</p>
            </div>
        </section>
        <hr class="m-0" />

        <!-- Registro de Entrada -->
        <?php if ($seccion === 'registrar'): ?>
        <section class="resume-section" id="RegistroEntrada">
            <div class="resume-section-content">
                <h2 class="mb-5">Registro de Entrada</h2>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Apellido</label>
                        <input type="text" class="form-control" name="apellido" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">DNI</label>
                        <input type="text" class="form-control" name="dni" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo de Visita</label>
                        <textarea class="form-control" name="motivo_visita" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Persona a Visitar</label>
                        <input type="text" class="form-control" name="persona_visita" required />
                    </div>
                    <button type="submit" class="btn btn-primary" name="registrar">Registrar</button>
                </form>
            </div>
        </section>
        <hr class="m-0" />
        <?php endif; ?>

        <!-- Actualizar Salida -->
        <?php if ($seccion === 'salida'): ?>
        <section class="resume-section" id="SalidaEdificio">
            <div class="resume-section-content">
                <h2 class="mb-5">Actualizar Hora de Salida</h2>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">DNI</label>
                        <input type="text" class="form-control" name="dni_salida" required />
                    </div>
                    <button type="submit" class="btn btn-warning" name="actualizar">Actualizar</button>
                </form>
            </div>
        </section>
        <hr class="m-0" />
        <?php endif; ?>

        <!-- Listado de personas -->
        <?php if ($seccion === 'listado'): ?>
        <section class="resume-section" id="ListadoPersona">
            <div class="resume-section-content">
                <h2 class="mb-5">Listado de Invitados</h2>
                <form method="GET">
                    <input type="hidden" name="seccion" value="listado" />
                    <div class="mb-3">
                        <label class="form-label">Filtrar por</label>
                        <select class="form-select" name="filtro">
                            <option value="dni">DNI</option>
                            <option value="fecha">Fecha</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor</label>
                        <input type="text" class="form-control" name="valor" required />
                    </div>
                    <button type="submit" class="btn btn-info" name="filtrar">Filtrar</button>
                </form>
                <table class="table mt-3">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>DNI</th>
                            <th>Motivo</th>
                            <th>Persona</th>
                            <th>Ingreso</th>
                            <th>Salida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personas as $persona): ?>
                            <tr>
                                <td><?= htmlspecialchars($persona['nombre']) ?></td>
                                <td><?= htmlspecialchars($persona['apellido']) ?></td>
                                <td><?= htmlspecialchars($persona['dni']) ?></td>
                                <td><?= htmlspecialchars($persona['motivoVisita']) ?></td>
                                <td><?= htmlspecialchars($persona['personaQueVisita']) ?></td>
                                <td><?= htmlspecialchars($persona['ingreso']) ?></td>
                                <td><?= $persona['egreso'] ? htmlspecialchars($persona['egreso']) : 'Pendiente' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <hr class="m-0" />
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
</body>
</html>

<?php
$conexion->close();
?>
