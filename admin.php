<?php
session_start();
require 'db.php';

// --- Lógica de Autenticación Simple ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Procesar Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['user'] === 'Benko' && $_POST['pass'] === 'Dc@6691400') {
        $_SESSION['admin_logged'] = true;
    } else {
        $error = "Credenciales incorrectas";
    }
}

// Si no está logueado, mostrar form de login
if (!isset($_SESSION['admin_logged'])) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <form method="POST" class="bg-white p-8 rounded-lg shadow-lg w-96">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Admin Acceso</h2>
        <?php if(isset($error)) echo "<p class='text-red-500 text-sm mb-4'>$error</p>"; ?>
        <input type="text" name="user" placeholder="Usuario" class="w-full mb-4 p-3 border rounded">
        <input type="password" name="pass" placeholder="Contraseña" class="w-full mb-6 p-3 border rounded">
        <button type="submit" name="login" class="w-full bg-blue-900 text-white p-3 rounded font-bold hover:bg-blue-800">Entrar</button>
    </form>
</body>
</html>
<?php
    exit;
}

// --- Lógica del Dashboard ---

// Obtener todas las reservas ordenadas por fecha de tour y luego creación
$sql = "SELECT b.id, b.tour_date, b.created_at, 
               p.first_name, p.last_name, p.doc_type, p.doc_number 
        FROM bookings b 
        JOIN passengers p ON b.id = p.booking_id 
        ORDER BY b.tour_date DESC, b.created_at DESC, p.id ASC";

$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por Reserva
$bookings = [];
foreach ($data as $row) {
    $bookings[$row['id']]['tour_date'] = $row['tour_date'];
    $bookings[$row['id']]['created_at'] = $row['created_at'];
    $bookings[$row['id']]['passengers'][] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: '#0f172a' } } } }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">

<nav class="bg-brand text-white p-4 shadow-md">
    <div class="max-w-6xl mx-auto flex justify-between items-center">
        <h1 class="font-bold text-xl">Panel de Seguros</h1>
        <a href="?logout=true" class="text-sm bg-red-600 hover:bg-red-700 px-3 py-1 rounded">Salir</a>
    </div>
</nav>

<div class="max-w-6xl mx-auto p-6">
    
    <div class="grid gap-6">
        <?php foreach ($bookings as $id => $booking): 
            $first_p = $booking['passengers'][0]; 
            $title = $first_p['first_name'] . ' ' . $first_p['last_name'];
            $count = count($booking['passengers']);
        ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <span class="text-xs font-bold uppercase text-gray-500 tracking-wide">Fecha Tour</span>
                    <div class="text-lg font-bold text-gray-800"><?php echo date("d/m/Y", strtotime($booking['tour_date'])); ?></div>
                    <div class="text-sm text-gray-500">Titular: <?php echo $title; ?> (+<?php echo $count-1; ?>)</div>
                </div>
                <button onclick="copyToClipboard(<?php echo $id; ?>)" class="flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md transition text-sm font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" /><path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" /></svg>
                    Copiar para WhatsApp
                </button>
            </div>

            <textarea id="data-<?php echo $id; ?>" class="hidden">
*SOLICITUD DE SEGURO*
📅 *Fecha:* <?php echo date("d/m/Y", strtotime($booking['tour_date'])); ?>

👤 *Titular:* <?php echo $title; ?>

👥 *Pasajeros:*
<?php foreach ($booking['passengers'] as $idx => $p): ?>
<?php echo ($idx + 1) . ". " . $p['first_name'] . " " . $p['last_name'] . " - " . $p['doc_type'] . ": " . $p['doc_number']; ?>

<?php endforeach; ?>
            </textarea>

            <div class="p-4 overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-4 py-2">Nombre</th>
                            <th class="px-4 py-2">Documento</th>
                            <th class="px-4 py-2">Número</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($booking['passengers'] as $p): ?>
                        <tr class="border-b">
                            <td class="px-4 py-2 font-medium text-gray-900"><?php echo $p['first_name'] . ' ' . $p['last_name']; ?></td>
                            <td class="px-4 py-2"><?php echo $p['doc_type']; ?></td>
                            <td class="px-4 py-2"><?php echo $p['doc_number']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
    function copyToClipboard(id) {
        const text = document.getElementById('data-' + id).value;
        navigator.clipboard.writeText(text).then(() => {
            alert("¡Datos copiados! Listo para pegar en WhatsApp.");
        }).catch(err => {
            console.error('Error al copiar: ', err);
        });
    }
</script>

</body>
</html>