<?php
session_start();
$db = new PDO('sqlite:vmail.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Crear tablas si no existen
$db->exec("CREATE TABLE IF NOT EXISTS usuarios (nombre TEXT PRIMARY KEY)");
$db->exec("CREATE TABLE IF NOT EXISTS mensajes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  de TEXT,
  para TEXT,
  texto TEXT
)");

// Registro
if (isset($_POST['registro'])) {
  $nombre = trim($_POST['nombre']);
  if ($nombre !== '') {
    $stmt = $db->prepare("INSERT OR IGNORE INTO usuarios (nombre) VALUES (?)");
    $stmt->execute([$nombre]);
    $_SESSION['user'] = $nombre;
    header("Location: vmail.php");
    exit;
  }
}

// Inicio de sesión
if (isset($_POST['entrar'])) {
  $nombre = trim($_POST['nombre']);
  $stmt = $db->prepare("SELECT nombre FROM usuarios WHERE nombre = ?");
  $stmt->execute([$nombre]);
  if ($stmt->fetch()) {
    $_SESSION['user'] = $nombre;
    header("Location: vmail.php");
    exit;
  }
}

// Envío de mensaje
if (isset($_POST['enviar']) && isset($_SESSION['user'])) {
  $para = trim($_POST['para']);
  $texto = trim($_POST['texto']);
  if ($para && $texto) {
    $stmt = $db->prepare("INSERT INTO mensajes (de, para, texto) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user'], $para, $texto]);
  }
}

// Cierre de sesión
if (isset($_GET['salir'])) {
  session_destroy();
  header("Location: vmail.php");
  exit;
}

// Obtener mensajes
$mensajes = [];
if (isset($_SESSION['user'])) {
  $stmt = $db->prepare("SELECT de, texto FROM mensajes WHERE para = ?");
  $stmt->execute([$_SESSION['user']]);
  $mensajes = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>VMail +</title>
  <style>
    body {
      background: radial-gradient(circle at center, #0a0f28 0%, #000);
      color: #e0ffe0;
      font-family: Segoe UI, sans-serif;
      padding: 20px;
    }
    h2 { color: #00ff55; }
    input, textarea, button {
      width: 100%;
      margin: 8px 0;
      padding: 10px;
      font-size: 16px;
      border-radius: 5px;
      border: none;
    }
    button { background: #00ff55; color: #000; font-weight: bold; }
    .msg { background: #111; padding: 10px; margin: 10px 0; border-left: 4px solid #00ff55; }
  </style>
</head>
<body>
<?php if (!isset($_SESSION['user'])): ?>
  <h2>VMail +</h2>
  <form method="post">
    <input name="nombre" placeholder="Tu nombre">
    <button name="registro">Registrar</button>
    <button name="entrar">Entrar</button>
  </form>
<?php else: ?>
  <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['user']); ?> 🛸</h2>
  <form method="post">
    <input name="para" placeholder="Para...">
    <textarea name="texto" placeholder="Tu mensaje..."></textarea>
    <button name="enviar">Enviar</button>
  </form>

  <h3>Bandeja de entrada 📬</h3>
  <?php if ($mensajes): foreach ($mensajes as $m): ?>
    <div class="msg"><strong><?php echo htmlspecialchars($m['de']); ?>:</strong><br><?php echo htmlspecialchars($m['texto']); ?></div>
  <?php endforeach; else: ?>
    <p>No tienes mensajes nuevos.</p>
  <?php endif; ?>

  <form method="get"><button name="salir" value="1">Cerrar sesión</button></form>
<?php endif; ?>
</body>
</html>