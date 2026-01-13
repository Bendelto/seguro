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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registro de Seguro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { 
                extend: { 
                    colors: { brand: '#0f172a' },
                    screens: { 'xs': '475px' },
                } 
            }
        }
    </script>
    <style>
        /* Evita que iOS haga zoom al escribir */
        input, select { font-size: 16px !important; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 font-sans antialiased">

<div class="w-full md:max-w-3xl md:mx-auto md:p-6 md:mt-10">
    
    <div class="text-center py-6 px-4 md:mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-brand tracking-tight">Registro de Pasajeros</h1>
        <p class="text-sm text-gray-500 mt-1">Datos para la póliza de seguro.</p>
    </div>

    <?php if ($success): ?>
        <div class="mx-4 md:mx-0 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 text-center shadow-sm">
            <strong>¡Registro Exitoso!</strong><br>Los datos han sido guardados.
            <a href="index.php" class="block mt-3 font-bold underline">Registrar nuevo grupo</a>
        </div>
    <?php else: ?>

    <form method="POST" class="bg-white p-4 md:p-8 md:rounded-xl shadow-md border-y md:border border-gray-200 space-y-5">
        
        <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Fecha del Tour</label>
            <input type="date" name="tour_date" required 
                   class="w-full bg-white border-gray-300 text-gray-900 rounded-md shadow-sm focus:border-brand focus:ring focus:ring-brand focus:ring-opacity-20 p-2 border h-12">
        </div>

        <div id="passengers-list" class="space-y-6">
            </div>

        <div class="pt-2 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
            <button type="button" onclick="addPassenger()" 
                    class="w-full md:w-auto py-3 px-4 border-2 border-dashed border-gray-300 text-gray-600 rounded-lg font-medium hover:border-brand hover:text-brand flex justify-center items-center gap-2 transition active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Agregar Pasajero
            </button>
            
            <button type="submit" 
                    class="w-full md:w-auto bg-brand text-white py-3.5 px-8 rounded-lg font-bold text-lg hover:bg-slate-800 transition shadow-lg shadow-slate-400/30 active:scale-95">
                Enviar Registro
            </button>
        </div>
    </form>
    
    <p class="text-center text-xs text-gray-400 mt-6 mb-10">Protegido por SSL. Datos confidenciales.</p>
    
    <?php endif; ?>

</div>

<script>
    function getPassengerRow(index) {
        // Botón eliminar (solo si no es el primero)
        const deleteButton = index > 0 ? `
            <button type="button" onclick="this.closest('.passenger-row').remove()" 
                    class="absolute -top-3 -right-2 bg-red-100 text-red-600 rounded-full p-1.5 border border-red-200 shadow-sm z-10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>` : '';

        // Título del pasajero
        const title = `<div class="col-span-1 md:col-span-2 text-xs font-bold text-gray-400 uppercase mb-1">Pasajero ${index + 1}</div>`;

        return `
        <div class="passenger-row relative bg-gray-50 p-4 rounded-lg border border-gray-200 animate-fade-in">
            ${deleteButton}
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${title}
                
                <div>
                    <label class="block text-xs text-gray-500 mb-1 md:hidden">Nombre</label>
                    <input type="text" name="first_name[]" placeholder="Nombre" required 
                        class="w-full border-gray-300 rounded-md p-3 border focus:ring-brand focus:border-brand h-12 bg-white">
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1 md:hidden">Apellido</label>
                    <input type="text" name="last_name[]" placeholder="Apellido" required 
                        class="w-full border-gray-300 rounded-md p-3 border focus:ring-brand focus:border-brand h-12 bg-white">
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1 md:hidden">Tipo de Documento</label>
                    <select name="doc_type[]" class="w-full border-gray-300 rounded-md p-3 border bg-white focus:ring-brand focus:border-brand h-12">
                        <option value="Pasaporte">Pasaporte</option>
                        <option value="Cédula">Cédula</option>
                        <option value="DNI">DNI</option>
                        <option value="ID">ID</option>
                        <option value="RG">RG</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1 md:hidden">Número de identificación</label>
                    <input type="tel" name="doc_number[]" placeholder="No. Identificación" required 
                        class="w-full border-gray-300 rounded-md p-3 border focus:ring-brand focus:border-brand h-12 bg-white">
                </div>
            </div>
        </div>`;
    }

    const list = document.getElementById('passengers-list');

    function addPassenger() {
        const index = list.children.length; 
        list.insertAdjacentHTML('beforeend', getPassengerRow(index));
    }

    // Inicializar con uno
    addPassenger();
</script>

</body>
</html>