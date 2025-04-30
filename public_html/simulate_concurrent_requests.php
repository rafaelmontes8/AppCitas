<?php
// Start session to get the CSRF token
session_start();
// Base URL for requests
$base_url = 'http://localhost/index.php';

// Create a shared cookie file
$cookie_file = tempnam(sys_get_temp_dir(), 'CURLCOOKIE');

// First make a GET request to get the session cookie and CSRF token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
$response = curl_exec($ch);
curl_close($ch);

// Extract CSRF token from the form
preg_match('/<input[^>]*name="csrf"[^>]*value="([^"]*)"[^>]*>/', $response, $matches);
$csrf_token = $matches[1] ?? '';

if (empty($csrf_token)) {
    die("No se pudo encontrar el token CSRF en el formulario");
}

// Function to generate a random DNI
function generateRandomDNI() {
    $number = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
    $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
    $letter = $letters[$number % 23];
    return $number . $letter;
}

// Function to generate a random phone number
function generateRandomPhone() {
    return '6' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
}

// Create multiple concurrent requests
$num_requests = 5; // Number of concurrent requests
$requests = [];

echo "<h2>Simulación de " . $num_requests . " concurrent requests</h2>";

// Create an array with request data
for ($i = 0; $i < $num_requests; $i++) {
    $requests[] = [
        'name' => "Paciente " . ($i + 1),
        'dni' => generateRandomDNI(),
        'phone' => generateRandomPhone(),
        'email' => "paciente" . ($i + 1) . "@example.com",
        'appointment_type' => ($i % 2 == 0) ? 'FIRST_VISIT' : 'FOLLOW_UP',
        'csrf' => $csrf_token
    ];
}

// Initialize multi-curl
$mh = curl_multi_init();
$curl_handles = [];

// Prepare all requests
foreach ($requests as $i => $request) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    
    curl_multi_add_handle($mh, $ch);
    $curl_handles[] = $ch;
    
    echo "<p>Preparando solicitud para " . $request['name'] . " (DNI: " . $request['dni'] . ")</p>";
}

// Execute all requests simultaneously
$running = null;
do {
    $status = curl_multi_exec($mh, $running);
    if ($running) {
        // Wait for activity on any of the handles
        curl_multi_select($mh);
    }
} while ($running && $status == CURLM_OK);

// Get results
echo "<h3>Resultados:</h3>";
foreach ($curl_handles as $i => $ch) {
    $result = curl_multi_getcontent($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px;'>";
    echo "<h4>Request " . ($i + 1) . ":</h4>";
    echo "<p>HTTP Status: " . $httpCode . "</p>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
    echo "</div>";
    
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}

curl_multi_close($mh);

// Clean up cookie file
unlink($cookie_file);

echo "<p><a href='check_appointments.php'>Ver todas las citas programadas</a></p>";
?> 