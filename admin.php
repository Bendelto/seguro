<?php
session_start();
require 'db.php';

// --- 1. Lógica de Autenticación ---
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

// Procesar Eliminación
if (isset($_POST['delete_id']) && isset($_SESSION['admin_logged'])) {
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    // Recargar para limpiar el POST y actualizar la lista
    header("Location: admin.php");
    exit;
}

// Si no está logueado, mostrar login
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

// --- 2. Lógica del Dashboard y Buscador ---

$searchTerm = $_GET['q'] ?? '';
$params = [];
$whereClause = "";

// Configurar filtro de búsqueda
if (!empty($searchTerm)) {
    // Busca coincidencias en nombre o apellido
    $whereClause = "WHERE (p.first_name LIKE ? OR p.last_name LIKE ?)";
    $term = "%" . $searchTerm . "%";
    $params = [$term, $term];
}

// Consulta Principal: Obtiene datos uniendo tablas, filtra y ordena por MÁS RECIENTE
$sql = "SELECT DISTINCT b.id, b.tour_date, b.created_at 
        FROM bookings b 
        JOIN passengers p ON b.id = p.booking_id 
        $whereClause
        ORDER BY b.id DESC"; // Ordenar del más nuevo al más antiguo

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$booking_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para llenar los detalles de cada reserva encontrada
$bookings = [];
if ($booking_rows) {
    // Extraemos los IDs para hacer una segunda consulta eficiente de los pasajeros
    $ids = array_column($booking_rows, 'id');
    $inQuery = implode(',', array_fill(0, count($ids), '?'));
    
    // Consulta de pasajeros solo para las reservas filtradas
    $stmtP = $pdo->prepare("SELECT * FROM passengers WHERE booking_id IN ($inQuery) ORDER BY id ASC");
    $stmtP->execute($ids);
    $allPassengers = $stmtP->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC); // Agrupar por booking_id

    // Armar el array final
    foreach ($booking_rows as $row) {
        $bookings[$row['id']] = [
            'tour_date' => $row['tour_date'],
            'created_at' => $row['created_at'],
            'passengers' => $allPassengers[$row['id']] ?? []
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: '#0f172a' } } } }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">

<nav class="bg-brand text-white p-4 shadow-md sticky top-0 z-50">
    <div class="max-w-6xl mx-auto flex justify-between items-center">
        <h1 class="font-bold text-lg md:text-xl">Panel de Seguros</h1>
        <a href="?logout=true" class="text-xs bg-red-600 hover:bg-red-700 px-3 py-2 rounded transition">Cerrar Sesión</a>
    </div>
</nav>

<div class="max-w-6xl mx-auto p-4 md:p-6">
    
    <div class="mb-8">
        <form method="GET" class="flex gap-2">
            <input type="text" name="q" value="<?php echo htmlspecialchars($searchTerm); ?>" 
                   placeholder="Buscar por nombre o apellido..." 
                   class="w-full p-3 rounded-lg border border-gray-300 shadow-sm focus:ring-brand focus:border-brand">
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700 shadow-sm">
                Buscar
            </button>
            <?php if(!empty($searchTerm)): ?>
                <a href="admin.php" class="bg-gray-300 text-gray-700 px-4 py-3 rounded-lg font-bold hover:bg-gray-400">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="grid gap-6">
        <?php if (empty($bookings)): ?>
            <p class="text-center text-gray-500 py-10">No se encontraron registros.</p>
        <?php else: ?>
            
        <?php foreach ($bookings as $id => $booking): 
            $first_p = $booking['passengers'][0] ?? ['first_name'=>'Desconocido', 'last_name'=>'']; 
            $title = $first_p['first_name'] . ' ' . $first_p['last_name'];
            $count = count($booking['passengers']);
        ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden relative">
            
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <span class="text-xs font-bold uppercase text-gray-500 tracking-wide block">Fecha Tour</span>
                    <div class="text-xl font-bold text-gray-800"><?php echo date("d/m/Y", strtotime($booking['tour_date'])); ?></div>
                    <div class="text-sm text-gray-500 mt-1">
                        Titular: <span class="font-semibold text-gray-700"><?php echo $title; ?></span> 
                        <?php if($count > 1) echo "<span class='bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full ml-1'>+" . ($count-1) . " acomp.</span>"; ?>
                    </div>
                </div>
                
                <div class="flex gap-2 w-full md:w-auto">
                    <button onclick="copyToClipboard(<?php echo $id; ?>)" class="flex-1 md:flex-none flex justify-center items-center gap-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md transition text-sm font-medium shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" /><path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" /></svg>
                        Copiar
                    </button>

                    <form method="POST" onsubmit="return confirm('¿Estás seguro de ELIMINAR este registro y todos sus pasajeros? Esta acción no se puede deshacer.');">
                        <input type="hidden" name="delete_id" value="<?php echo $id; ?>">
                        <button type="submit" class="h-full bg-red-100 text-red-600 hover:bg-red-200 px-3 py-2 rounded-md transition border border-red-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            <textarea id="data-<?php echo $id; ?>" class="hidden">
*SOLICITUD DE SEGURO*
📅 *Fecha Tour:* <?php echo date("d/m/Y", strtotime($booking['tour_date'])); ?>

👤 *Titular:* <?php echo $title; ?>

👥 *Pasajeros:*
<?php foreach ($booking['passengers'] as $idx => $p): ?>
<?php echo ($idx + 1) . ". " . $p['first_name'] . " " . $p['last_name'] . " - " . $p['doc_type'] . ": " . $p['doc_number']; ?>

<?php endforeach; ?>
            </textarea>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 w-10">#</th>
                            <th class="px-4 py-3">Nombres y Apellidos</th>
                            <th class="px-4 py-3">Documento</th>
                            <th class="px-4 py-3">Número</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($booking['passengers'] as $idx => $p): ?>
                        <tr class="border-b last:border-0 hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-400 text-xs"><?php echo $idx + 1; ?></td>
                            <td class="px-4 py-3 font-medium text-gray-900"><?php echo $p['first_name'] . ' ' . $p['last_name']; ?></td>
                            <td class="px-4 py-3"><?php echo $p['doc_type']; ?></td>
                            <td class="px-4 py-3 font-mono text-gray-600"><?php echo $p['doc_number']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
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