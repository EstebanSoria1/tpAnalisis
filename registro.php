<?php
$conn = new mysqli("localhost", "root", "", "listadopersonasedificio");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["usuario"];
    $contrasena = $_POST["contrasena"];

    // Verificar si ya existe el usuario
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "El nombre de usuario ya está en uso.";
    } else {
        // Insertar nuevo usuario
        $hash = hash("sha256", $contrasena);
        $insert = $conn->prepare("INSERT INTO usuarios (usuario, contrasena) VALUES (?, ?)");
        $insert->bind_param("ss", $usuario, $hash);
        if ($insert->execute()) {
            echo "Cuenta creada con éxito. <a href='login.php'>Iniciar sesión</a>";
        } else {
            echo "Error al crear la cuenta.";
        }
    }
}
?>

<h2>Crear Cuenta</h2>
<form method="POST">
    Usuario: <input type="text" name="usuario" required><br>
    Contraseña: <input type="password" name="contrasena" required><br>
    <button type="submit">Registrarse</button>
</form>
<a href="login.php">Ya tengo cuenta</a>