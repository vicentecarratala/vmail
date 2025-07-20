<?php
session_start();

// Configuración
$dbFile = 'vmail.sqlite';
$password = 'Latarraca123$';
$maxAttempts = 10;

// Conexión SQLite
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Intentos fallidos
if (!isset($_SESSION['admin_attempts'])) {
    $_SESSION['admin_attempts'] = 0;
}

// Acceso bloqueado
if ($_SESSION['admin_attempts'] >= $maxAttempts) {
    die("<h2 style='color:red'>Acceso bloqueado — zona restringida 🛑</h2>");
}

// Comprobación de clave
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clave'])) {
    if ($_POST['clave'] === $password) {
        $_SESSION['admin'] = true;
        $_SESSION['admin_attempts'] = 0;
    } else {
        $_SESSION['admin_attempts']++;
        echo "<p style='color:red'>Clave incorrecta (" . $_SESSION['admin_attempts'] . "/" . $maxAttempts . ")</p>";
    }
}

// Eliminación de mensajes
if (isset($_GET['delmsg']) && $_SESSION['admin']) {
    $id = intval($_GET['delmsg']);
    $db->exec("DELETE FROM mensajes WHERE id = $id");
}

// Eliminación de usuarios
if (isset($_GET['deluser']) && $_SESSION['admin']) {
    $user = $_GET['deluser'];
    $db->exec("DELETE FROM usuarios WHERE nombre = '$user'");
    $db->exec("DELETE FROM mensajes WHERE de = '$user' OR para = '$user'");
}

// Interfaz
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>Panel VMail +</title>
<style>
body {
    background: radial-gradient(circle at center, #0a0f28 0%, #000000 100%);
    font-family: Segoe UI, sans-serif; color: #e0ffe0; padding: 20px;
}
h2 { color: #00ff55; }
button, input { padding: 8px; font-size: 16px; margin: 5px 0; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
td, th { border: 1px solid #444; padding: 6px; }
a { color: #00ff55; }
</style></head><body>";

if (!isset($_SESSION['admin'])) {
    echo "<h2>Acceso al panel VMail +</h2>
          <form method='post'>
          <input type='password' name='clave' placeholder='Clave maestra'>
          <button type='submit'>Entrar</button>
          </form>";
    exit;
}

echo "<h2>Panel Administrativo 👨‍🚀</h2>";

// Usuarios
echo "<h3>Usuarios registrados</h3><table><tr><th>Nombre</th><th>Eliminar</th></tr>";
foreach ($db->query('SELECT nombre FROM usuarios') as $row) {
    $nombre = htmlspecialchars($row['nombre']);
    echo "<tr><td>$nombre</td><td><a href='?deluser=$nombre'>🗑️</a></td></tr>";
}
echo "</table>";

// Mensajes
echo "<h3>Mensajes</h3><table><tr><th>De</th><th>Para</th><th>Texto</th><th>Eliminar</th></tr>";
foreach ($db->query('SELECT id,de,para,texto FROM mensajes') as $row) {
    echo "<tr><td>".htmlspecialchars($row['de'])."</td><td>".htmlspecialchars($row['para'])."</td>
          <td>".htmlspecialchars($row['texto'])."</td><td><a href='?delmsg=".$row['id']."'>🗑️</a></td></tr>";
}
echo "</table>";

echo "</body></html>";
?>