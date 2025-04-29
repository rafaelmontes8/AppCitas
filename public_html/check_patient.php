<?php
header('Content-Type: application/json');

$connection = new mysqli("db", "root", "", "miapp");

if ($connection->connect_error) {
    die(json_encode(['error' => 'Connection error']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dni'])) {
    $dni = $_POST['dni'];
    
    $stmt = $connection->prepare("SELECT id FROM patients WHERE dni = ?");
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode(['exists' => $result->num_rows > 0]);
} else {
    echo json_encode(['error' => 'DNI not provided']);
}

$connection->close();
?> 