<?php
// Cabecera estándar
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

// --- CONFIGURACIÓN DE IDIOMAS ---

// 1. Detectar idioma (por URL amigable o parámetro)
$lang = $_GET['lang'] ?? 'es';

// 2. Definir la ruta base para los enlaces (dinámico, funciona en cualquier carpeta)
$base_path = dirname($_SERVER['SCRIPT_NAME']);
if(substr($base_path, -1) !== '/') $base_path .= '/';
// Limpiamos la ruta base por si estamos en un servidor local o subcarpeta
$base_url = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $base_path;

// Diccionario de Traducciones
$t = [
    'es' => [
        'title' => 'Registro de Pasajeros',
        'subtitle' => 'Datos para la póliza de seguro.',
        'tour_date' => 'Fecha del Tour',
        'names' => 'Nombres',
        'surnames' => 'Apellidos',
        'doc_type' => 'Tipo de Documento',
        'doc_id' => 'Número de identificación',
        'doc_placeholder' => 'N° de Identificación',
        'add_btn' => 'Agregar Pasajero',
        'submit_btn' => 'Enviar Registro',
        'success_title' => '¡Datos Guardados!',
        'success_msg' => 'Para finalizar el proceso, por favor envía la lista de pasajeros a nuestro WhatsApp.',
        'wa_btn' => 'Enviar Datos por WhatsApp',
        'new_group' => 'Registrar otro grupo',
        'passenger_label' => 'Pasajero',
        'secure_ssl' => 'Protegido por SSL. Datos confidenciales.'
    ],
    'en' => [
        'title' => 'Passenger Registration',
        'subtitle' => 'Insurance policy data.',
        'tour_date' => 'Tour Date',
        'names' => 'First Name',
        'surnames' => 'Last Name',
        'doc_type' => 'Document Type',
        'doc_id' => 'ID / Passport Number',
        'doc_placeholder' => 'ID / Passport Number',
        'add_btn' => 'Add Passenger',
        'submit_btn' => 'Submit Registration',
        'success_title' => 'Data Saved!',
        'success_msg' => 'To finish the process, please send the passenger list to our WhatsApp.',
        'wa_btn' => 'Send Data via WhatsApp',
        'new_group' => 'Register another group',
        'passenger_label' => 'Passenger',
        'secure_ssl' => 'SSL Protected. Confidential Data.'
    ],
    'pt' => [
        'title' => 'Cadastro de Passageiros',
        'subtitle' => 'Dados para o seguro viagem.',
        'tour_date' => 'Data do Passeio',
        'names' => 'Nomes',
        'surnames' => 'Sobrenomes',
        'doc_type' => 'Tipo de Documento',
        'doc_id' => 'Número do Documento',
        'doc_placeholder' => 'N° do Documento',
        'add_btn' => 'Adicionar Passageiro',
        'submit_btn' => 'Enviar Cadastro',
        'success_title' => 'Dados Salvos!',
        'success_msg' => 'Para finalizar, por favor envie a lista de passageiros para o nosso WhatsApp.',
        'wa_btn' => 'Enviar Dados pelo WhatsApp',
        'new_group' => 'Cadastrar outro grupo',
        'passenger_label' => 'Passageiro',
        'secure_ssl' => 'Protegido por SSL. Dados confidenciais.'
    ]
];

// Si el idioma no existe, forzar español
if (!array_key_exists($lang, $t)) $lang = 'es';
$tr = $t[$lang]; 

// --- LÓGICA DE GUARDADO ---
$success = false;
$whatsapp_link = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO bookings (tour_date) VALUES (?)");
    $stmt->execute([$_POST['tour_date']]);
    $booking_id = $pdo->lastInsertId();

    $nombres = $_POST['first_name'];
    $apellidos = $_POST['last_name'];
    $tipos = $_POST['doc_type'];
    $numeros = $_POST['doc_number'];
    
    $fecha_tour = date("d/m/Y", strtotime($_POST['tour_date']));
    
    // Mensaje WhatsApp (Siempre en Español para la agencia)
    $mensaje_wa = "*DATOS PARA SEGURO MÉDICO*\n\n";
    $mensaje_wa .= "*Destino:* PNN Corales del Rosario y San Bernardo\n";
    $mensaje_wa .= "*Fecha del Tour:* " . $fecha_tour . "\n\n";
    $mensaje_wa .= "*Listado:*\n";

    $sql = "INSERT INTO passengers (booking_id, first_name, last_name, doc_type, doc_number) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    for ($i = 0; $i < count($nombres); $i++) {
        if (!empty($nombres[$i])) {
            $nombre_limpio = ucwords(strtolower($nombres[$i]));
            $apellido_limpio = ucwords(strtolower($apellidos[$i]));
            $numero_limpio = strtoupper($numeros[$i]); 

            $stmt->execute([$booking_id, $nombre_limpio, $apellido_limpio, $tipos[$i], $numero_limpio]);
            $mensaje_wa .= ($i + 1) . ". " . $nombre_limpio . " " . $apellido_limpio . " – " . $tipos[$i] . ": " . $numero_limpio . "\n";
        }
    }

    $whatsapp_link = "https://wa.me/573205899997?text=" . urlencode($mensaje_wa);
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <base href="<?php echo $base_url; ?>">
    <title><?php echo $tr['title']; ?></title>
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
    <style>input, select { font-size: 16px !important; }</style>
</head>
<body class="bg-gray-100 text-gray-800 font-sans antialiased">

<div class="w-full md:max-w-3xl md:mx-auto md:p-6 md:mt-10">
    
    <div class="text-center py-6 px-4 md:mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-brand tracking-tight"><?php echo $tr['title']; ?></h1>
        <p class="text-sm text-gray-500 mt-1"><?php echo $tr['subtitle']; ?></p>
        
        <div class="mt-4 flex justify-center gap-3 text-sm font-medium text-gray-400">
            <a href="./" class="<?php echo $lang=='es'?'text-brand underline':'hover:text-brand'; ?>">ES</a>
            <span class="text-gray-300">|</span>
            <a href="en" class="<?php echo $lang=='en'?'text-brand underline':'hover:text-brand'; ?>">EN</a>
            <span class="text-gray-300">|</span>
            <a href="pt" class="<?php echo $lang=='pt'?'text-brand underline':'hover:text-brand'; ?>">PT</a>
        </div>
    </div>

    <?php if ($success): ?>
        
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 text-center mx-4 md:mx-0">
            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            
            <h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo $tr['success_title']; ?></h2>
            <p class="text-gray-600 mb-8"><?php echo $tr['success_msg']; ?></p>
            
            <a href="<?php echo $whatsapp_link; ?>" class="block w-full bg-whatsapp text-white font-bold py-4 px-6 rounded-lg shadow-lg hover:bg-green-600 transition transform active:scale-95 flex items-center justify-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592z"/>
                </svg>
                <?php echo $tr['wa_btn']; ?>
            </a>

            <a href="<?php echo $base_url; ?>" class="block mt-6 text-sm text-gray-400 underline hover:text-gray-600"><?php echo $tr['new_group']; ?></a>
        </div>

    <?php else: ?>

    <form method="POST" action="" class="bg-white p-4 md:p-8 md:rounded-xl shadow-md border-y md:border border-gray-200 space-y-5">
        
        <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1"><?php echo $tr['tour_date']; ?></label>
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
                <?php echo $tr['add_btn']; ?>
            </button>
            
            <button type="submit" 
                    class="w-full md:w-auto bg-brand text-white py-3.5 px-8 rounded-lg font-bold text-lg hover:bg-slate-800 transition shadow-lg shadow-slate-400/30 active:scale-95">
                <?php echo $tr['submit_btn']; ?>
            </button>
        </div>
    </form>
    
    <p class="text-center text-xs text-gray-400 mt-6 mb-10"><?php echo $tr['secure_ssl']; ?></p>
    
    <?php endif; ?>

</div>

<script>
    // Traducciones para JS
    const labels = {
        names: "<?php echo $tr['names']; ?>",
        surnames: "<?php echo $tr['surnames']; ?>",
        doc_type: "<?php echo $tr['doc_type']; ?>",
        doc_id: "<?php echo $tr['doc_id']; ?>",
        placeholder_id: "<?php echo $tr['doc_placeholder']; ?>",
        passenger: "<?php echo $tr['passenger_label']; ?>"
    };

    function getPassengerRow(index) {
        const deleteButton = index > 0 ? `
            <button type="button" onclick="this.closest('.passenger-row').remove()" 
                    class="absolute -top-3 -right-2 bg-red-100 text-red-600 rounded-full p-1.5 border border-red-200 shadow-sm z-10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>` : '';

        const title = `<div class="col-span-1 md:col-span-2 text-xs font-bold text-gray-400 uppercase mb-1">${labels.passenger} ${index + 1}</div>`;

        return `
        <div class="passenger-row relative bg-gray-50 p-4 rounded-lg border border-gray-200 animate-fade-in">
            ${deleteButton}
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${title}
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">${labels.names}</label>
                    <input type="text" name="first_name[]" placeholder="${labels.names}" required 
                        class="w-full border-gray-300 rounded-md p-3 border focus:ring-brand focus:border-brand h-12 bg-white capitalize">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">${labels.surnames}</label>
                    <input type="text" name="last_name[]" placeholder="${labels.surnames}" required 
                        class="w-full border-gray-300 rounded-md p-3 border focus:ring-brand focus:border-brand h-12 bg-white capitalize">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">${labels.doc_type}</label>
                    <select name="doc_type[]" class="w-full border-gray-300 rounded-md p-3 border bg-white focus:ring-brand focus:border-brand h-12">
                        <option value="Pasaporte">Pasaporte</option>
                        <option value="Cédula">Cédula</option>
                        <option value="DNI">DNI</option>
                        <option value="ID">ID</option>
                        <option value="RG">RG</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">${labels.doc_id}</label>
                    <input type="text" name="doc_number[]" placeholder="${labels.placeholder_id}" required 
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