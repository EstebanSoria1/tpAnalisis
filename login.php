<?php
session_start();
$conn = new mysqli("localhost", "root", "", "listadopersonasedificio");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["usuario"];
    $contrasena = $_POST["contrasena"];
    $hash = hash("sha256", $contrasena);

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ? AND contrasena = ?");
    $stmt->bind_param("ss", $usuario, $hash);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $_SESSION["usuario"] = $usuario;
        header("Location: index.php");
        exit();
    } else {
        echo "Usuario o contraseña incorrectos.";
    }
}
?>

<h2>Iniciar Sesión</h2>
<form method="POST">
    Usuario: <input type="text" name="usuario" required><br>
    Contraseña: <input type="password" name="contrasena" required><br>
    <button type="submit">Entrar</button>
</form>
<a href="registro.php">Crear cuenta nueva</a>
