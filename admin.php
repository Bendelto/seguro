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
    $whereClause = "WHERE (p.first_name LIKE ? OR p.last_name LIKE ?)";
    $term = "%" . $searchTerm . "%";
    $params = [$term, $term];
}

// Consulta Principal
$sql = "SELECT DISTINCT b.id, b.tour_date, b.created_at 
        FROM bookings b 
        JOIN passengers p ON b.id = p.booking_id 
        $whereClause
        ORDER BY b.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$booking_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$bookings = [];
if ($booking_rows) {
    // Obtenemos los IDs de las reservas encontradas
    $ids = array_column($booking_rows, 'id');
    $inQuery = implode(',', array_fill(0, count($ids), '?'));
    
    // CORRECCIÓN IMPORTANTE AQUÍ:
    // Seleccionamos explícitamente booking_id PRIMERO para que PDO::FETCH_GROUP funcione correctamente
    $sqlPassengers = "SELECT booking_id, first_name, last_name, doc_type, doc_number 
                      FROM passengers 
                      WHERE booking_id IN ($inQuery) 
                      ORDER BY id ASC";
                      
    $stmtP = $pdo->prepare($sqlPassengers);
    $stmtP->execute($ids);
    
    // Agrupamos los resultados por la primera columna (booking_id)
    $allPassengers = $stmtP->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

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
    <title>Panel de Seguros</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: '#0f172a' } } } }
    </script>
</head>
<body class="bg-brand text-gray-100 min-h-screen">

<nav class="bg-slate-900 text-white p-4 border-b border-slate-700">
    <div class="max-w-5xl mx-auto flex justify-between items-center">
        <h1 class="font-bold text-xl tracking-tight">Panel de Seguros</h1>
        <a href="?logout=true" class="text-xs bg-red-600 hover:bg-red-700 px-4 py-2 rounded transition font-semibold">Cerrar Sesión</a>
    </div>
</nav>

<div class="max-w-5xl mx-auto p-4 md:p-8">
    
    <div class="mb-10">
        <form method="GET" class="flex gap-2">
            <input type="text" name="q" value="<?php echo htmlspecialchars($searchTerm); ?>" 
                   placeholder="Buscar por nombre o apellido..." 
                   class="w-full p-4 rounded-lg border-0 shadow-lg text-gray-800 focus:ring-2 focus:ring-blue-500 outline-none">
            <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-500 shadow-lg transition">
                Buscar
            </button>
            <?php if(!empty($searchTerm)): ?>
                <a href="admin.php" class="bg-slate-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-slate-500 flex items-center">Borrar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="space-y-6">
        <?php if (empty($bookings)): ?>
            <div class="text-center py-20 bg-slate-800 rounded-lg opacity-50">
                <p class="text-xl font-medium">No se encontraron registros recientes.</p>
            </div>
        <?php else: ?>
            
        <?php foreach ($bookings as $id => $booking): 
            $first_p = $booking['passengers'][0] ?? ['first_name'=>'Sin', 'last_name'=>'Datos']; 
            $title = $first_p['first_name'] . ' ' . $first_p['last_name'];
            $count = count($booking['passengers']);
        ?>
        <div class="bg-white rounded-lg shadow-xl overflow-hidden text-gray-800">
            
            <div class="p-5 bg-gray-50 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <span class="text-xs font-bold uppercase text-gray-400 tracking-wider block mb-1">FECHA TOUR</span>
                    <div class="text-2xl font-bold text-gray-800"><?php echo date("d/m/Y", strtotime($booking['tour_date'])); ?></div>
                    <div class="text-sm text-gray-500 mt-1">
                        Titular: <span class="font-semibold text-gray-900"><?php echo $title; ?></span> 
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <button onclick="copyToClipboard(<?php echo $id; ?>)" class="flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded shadow-sm transition font-bold text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" /><path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" /></svg>
                        Copiar
                    </button>

                    <form method="POST" onsubmit="return confirm('¿Eliminar este registro?');">
                        <input type="hidden" name="delete_id" value="<?php echo $id; ?>">
                        <button type="submit" class="bg-red-100 text-red-500 hover:bg-red-200 p-2 rounded transition border border-red-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                <table class="w-full text-left">
                    <thead class="text-xs font-bold text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-3 w-12">#</th>
                            <th class="px-6 py-3">Nombres y Apellidos</th>
                            <th class="px-6 py-3">Documento</th>
                            <th class="px-6 py-3 text-right">Número</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($booking['passengers'] as $idx => $p): ?>
                        <tr class="hover:bg-blue-50 transition">
                            <td class="px-6 py-3 text-gray-400 font-mono text-xs"><?php echo $idx + 1; ?></td>
                            <td class="px-6 py-3 font-semibold text-gray-800"><?php echo $p['first_name'] . ' ' . $p['last_name']; ?></td>
                            <td class="px-6 py-3 text-sm text-gray-600"><?php echo $p['doc_type']; ?></td>
                            <td class="px-6 py-3 text-sm font-mono text-gray-600 text-right"><?php echo $p['doc_number']; ?></td>
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
            alert("¡Copiado al portapapeles!");
        }).catch(err => {
            console.error('Error:', err);
        });
    }
</script>

</body>
</html>