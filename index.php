<?php
require 'db.php';

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Insertar la reserva general
    $stmt = $pdo->prepare("INSERT INTO bookings (tour_date) VALUES (?)");
    $stmt->execute([$_POST['tour_date']]);
    $booking_id = $pdo->lastInsertId();

    // 2. Insertar los pasajeros
    $nombres = $_POST['first_name'];
    $apellidos = $_POST['last_name'];
    $tipos = $_POST['doc_type'];
    $numeros = $_POST['doc_number'];

    $sql = "INSERT INTO passengers (booking_id, first_name, last_name, doc_type, doc_number) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    for ($i = 0; $i < count($nombres); $i++) {
        if (!empty($nombres[$i])) {
            $stmt->execute([$booking_id, $nombres[$i], $apellidos[$i], $tipos[$i], $numeros[$i]]);
        }
    }
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Seguro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { brand: '#0f172a' } } }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">

<div class="max-w-3xl mx-auto p-6 mt-10">
    
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-brand tracking-tight">Registro de Pasajeros</h1>
        <p class="text-gray-500 mt-2">Completa los datos para la póliza de seguro.</p>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 text-center">
            <strong>¡Registro Exitoso!</strong> Los datos han sido enviados correctamente.
            <a href="index.php" class="block mt-2 underline">Registrar otro grupo</a>
        </div>
    <?php else: ?>

    <form method="POST" class="space-y-6 bg-white p-8 rounded-xl shadow-sm border border-gray-100">
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha del Tour / Excursión</label>
            <input type="date" name="tour_date" required 
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:border-brand focus:ring focus:ring-brand focus:ring-opacity-20 p-3 border">
        </div>

        <hr class="border-gray-200">

        <div id="passengers-list" class="space-y-4">
            </div>

        <div class="flex justify-between items-center pt-4">
            <button type="button" onclick="addPassenger()" 
                    class="text-sm text-brand font-medium hover:text-blue-700 flex items-center gap-1 transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Agregar Acompañante
            </button>
            
            <button type="submit" 
                    class="bg-brand text-white px-8 py-3 rounded-lg font-semibold hover:bg-slate-800 transition shadow-lg shadow-slate-300/50">
                Enviar Registro
            </button>
        </div>
    </form>
    <?php endif; ?>

</div>

<script>
    // Plantilla de fila de pasajero
    function getPassengerRow(index) {
        return `
        <div class="passenger-row bg-gray-50 p-4 rounded-lg border border-gray-200 relative group transition-all duration-300">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="text" name="first_name[]" placeholder="Nombre" required class="w-full border-gray-300 rounded-md p-2 border focus:ring-brand focus:border-brand">
                <input type="text" name="last_name[]" placeholder="Apellido" required class="w-full border-gray-300 rounded-md p-2 border focus:ring-brand focus:border-brand">
                <select name="doc_type[]" class="w-full border-gray-300 rounded-md p-2 border bg-white focus:ring-brand focus:border-brand">
                    <option value="Pasaporte">Pasaporte</option>
                    <option value="Cédula">Cédula</option>
                    <option value="DNI">DNI</option>
                    <option value="ID">ID</option>
                    <option value="RG">RG</option>
                </select>
                <input type="text" name="doc_number[]" placeholder="No. Identificación" required class="w-full border-gray-300 rounded-md p-2 border focus:ring-brand focus:border-brand">
            </div>
            ${index > 0 ? `
            <button type="button" onclick="this.parentElement.remove()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 shadow-md hover:bg-red-600 opacity-0 group-hover:opacity-100 transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>` : ''}
        </div>`;
    }

    const list = document.getElementById('passengers-list');

    function addPassenger() {
        const index = list.children.length;
        list.insertAdjacentHTML('beforeend', getPassengerRow(index));
    }

    // Agregar el primer pasajero al cargar
    addPassenger();
</script>

</body>
</html>