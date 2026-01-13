<?php
// LÍNEA CRÍTICA: Fuerza al navegador a usar UTF-8
header('Content-Type: text/html; charset=utf-8');

require 'db.php';

$success = false;
$whatsapp_link = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Insertar la reserva general
    $stmt = $pdo->prepare("INSERT INTO bookings (tour_date) VALUES (?)");
    $stmt->execute([$_POST['tour_date']]);
    $booking_id = $pdo->lastInsertId();

    // 2. Insertar los pasajeros y Preparar Texto para WhatsApp
    $nombres = $_POST['first_name'];
    $apellidos = $_POST['last_name'];
    $tipos = $_POST['doc_type'];
    $numeros = $_POST['doc_number'];
    
    // Formatear fecha para el mensaje
    $fecha_tour = date("d/m/Y", strtotime($_POST['tour_date']));
    
    // --- EMOJIS SEGUROS (Unicode Escape) ---
    $icon_date = "\u{1F4C5}"; // 📅
    $icon_grp  = "\u{1F465}"; // 👥
    
    // Iniciar construcción del mensaje de WhatsApp
    $mensaje_wa = "*SOLICITUD DE SEGURO*\n";
    $mensaje_wa .= $icon_date . " *Fecha Tour:* " . $fecha_tour . "\n\n";
    $mensaje_wa .= $icon_grp . " *Pasajeros:*\n";

    $sql = "INSERT INTO passengers (booking_id, first_name, last_name, doc_type, doc_number) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    for ($i = 0; $i < count($nombres); $i++) {
        if (!empty($nombres[$i])) {
            // Formatear Nombres, Apellidos y Documento
            $nombre_limpio = ucwords(strtolower($nombres[$i]));
            $apellido_limpio = ucwords(strtolower($apellidos[$i]));
            $numero_limpio = strtoupper($numeros[$i]); 

            // Guardar en DB
            $stmt->execute([$booking_id, $nombre_limpio, $apellido_limpio, $tipos[$i], $numero_limpio]);

            // Agregar al texto de WhatsApp
            $mensaje_wa .= ($i + 1) . ". " . $nombre_limpio . " " . $apellido_limpio . " - " . $tipos[$i] . ": " . $numero_limpio . "\n";
        }
    }

    // Generar enlace de WhatsApp (Codificado para URL)
    $whatsapp_link = "https://wa.me/573205899997?text=" . urlencode($mensaje_wa);
    
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Registro de Seguro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { 
                extend: { 
                    colors: { brand: '#0f172a', whatsapp: '#25D366' },
                    screens: { 'xs': '475px' },
                } 
            }
        }
    </script>
    <style>
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
        
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 text-center mx-4 md:mx-0">
            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            
            <h2 class="text-2xl font-bold text-gray-800 mb-2">¡Datos Guardados!</h2>
            <p class="text-gray-600 mb-8">Para finalizar el proceso, por favor envía la lista de pasajeros a nuestro WhatsApp.</p>
            
            <a href="<?php echo $whatsapp_link; ?>" class="block w-full bg-whatsapp text-white font-bold py-4 px-6 rounded-lg shadow-lg hover:bg-green-600 transition transform active:scale-95 flex items-center justify-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592z"/>
                </svg>
                Enviar Datos por WhatsApp
            </a>

            <a href="index.php" class="block mt-6 text-sm text-gray-400 underline hover:text-gray-600">Registrar otro grupo</a>
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
        // Botón eliminar
        const deleteButton = index > 0 ? `
            <button type="button" onclick="this.closest('.passenger-row').remove()" 
                    class="absolute -top-3 -right-2 bg-red-100 text-red-600 rounded-full p-1.5 border border-red-200 shadow-sm z-10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>` : '';

        // Título
        const title = `<div class="col-span-1 md:col-span-2 text-xs font-bold text-gray-400 uppercase mb-1">Pasajero ${index + 1}</div>`;

        return `
        <div class="passenger-row relative bg-gray-50 p-4 rounded-lg border border-gray-200 animate-fade-in">
            ${deleteButton}
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${title}
                
                <div>
                    <label class="block text-xs text-gray-500 mb-1 md:hidden">Nombres</label>
                    <input type="text" name="first_name[]" placeholder="Nombres" required 
                        class="w-full border-gray-300 rounded-md p-3 border focus:ring-brand focus:border-brand h-12 bg-white capitalize">
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1 md:hidden">Apellidos</label>
                    <input type="text" name="last_name[]" placeholder="Apellidos" required 
                        class="w-full border-gray-300 rounded-md p-3 border focus:ring-brand focus:border-brand h-12 bg-white capitalize">
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
                    <input type="text" name="doc_number[]" placeholder="N° de Identificación" required 
                        class="w-full border-gray-300 rounded-md p-3 border focus:ring-brand focus:border-brand h-12 bg-white"
                        oninput="this.value = this.value.toUpperCase()">
                </div>
            </div>
        </div>`;
    }

    const list = document.getElementById('passengers-list');

    function addPassenger() {
        const index = list.children.length; 
        list.insertAdjacentHTML('beforeend', getPassengerRow(index));
    }

    addPassenger();
</script>

</body>
</html>