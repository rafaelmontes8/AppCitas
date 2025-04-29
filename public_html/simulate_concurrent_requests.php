<?php
// Función para generar un DNI aleatorio
function generateRandomDNI() {
    $number = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
    $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
    $letter = $letters[$number % 23];
    return $number . $letter;
}

// Función para generar un teléfono aleatorio
function generateRandomPhone() {
    return '6' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
}

// Función para realizar una solicitud de cita usando cURL
function makeAppointmentRequest($name, $dni, $phone, $email, $appointment_type) {
    $url = 'http://localhost/index.php';
    $data = [
        'name' => $name,
        'dni' => $dni,
        'phone' => $phone,
        'email' => $email,
        'appointment_type' => $appointment_type
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}

// Crear múltiples solicitudes simultáneas
$num_requests = 5; // Número de solicitudes simultáneas
$requests = [];

echo "<h2>Simulación de " . $num_requests . " solicitudes simultáneas</h2>";

// Crear un array con los datos de las solicitudes
for ($i = 0; $i < $num_requests; $i++) {
    $requests[] = [
        'name' => "Paciente " . ($i + 1),
        'dni' => generateRandomDNI(),
        'phone' => generateRandomPhone(),
        'email' => "paciente" . ($i + 1) . "@example.com",
        'appointment_type' => ($i % 2 == 0) ? 'FIRST_VISIT' : 'FOLLOW_UP'
    ];
}

// Inicializar el multi-curl
$mh = curl_multi_init();
$curl_handles = [];

// Preparar todas las solicitudes
foreach ($requests as $i => $request) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/index.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_multi_add_handle($mh, $ch);
    $curl_handles[] = $ch;
    
    echo "<p>Preparando solicitud para " . $request['name'] . " (DNI: " . $request['dni'] . ")</p>";
}

// Ejecutar todas las solicitudes simultáneamente
$running = null;
do {
    curl_multi_exec($mh, $running);
} while ($running);

// Obtener los resultados
echo "<h3>Resultados:</h3>";
foreach ($curl_handles as $i => $ch) {
    $result = curl_multi_getcontent($ch);
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px;'>";
    echo "<h4>Solicitud " . ($i + 1) . ":</h4>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
    echo "</div>";
    
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}

curl_multi_close($mh);

echo "<p><a href='check_appointments.php'>Ver todas las citas programadas</a></p>";
?> 