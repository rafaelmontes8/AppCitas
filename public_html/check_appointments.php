<?php
$connection = new mysqli("db", "root", "", "miapp");

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$query = "SELECT a.appointment_datetime, p.name, p.dni, a.appointment_type 
          FROM appointments a 
          JOIN patients p ON a.patient_id = p.id 
          ORDER BY a.appointment_datetime";

$result = $connection->query($query);

echo "<h2>Citas Programadas</h2>";
echo "<table border='1'>";
echo "<tr><th>Fecha y Hora</th><th>Paciente</th><th>DNI</th><th>Tipo de Cita</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . date('d/m/Y H:i', strtotime($row['appointment_datetime'])) . "</td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['dni']) . "</td>";
    echo "<td>" . htmlspecialchars($row['appointment_type']) . "</td>";
    echo "</tr>";
}

echo "</table>";

$connection->close();
?> 