<?php
$connection = new mysqli("db", "root", "", "miapp");

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Function to validate DNI
function validateDNI($dni) {
    return preg_match('/^[0-9]{8}[A-Z]$/', $dni);
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone
function validatePhone($phone) {
    return preg_match('/^[0-9]{9}$/', $phone);
}

// Function to find next available time slot with transaction and retry
function findNextAvailableTime($connection) {
    $start_hour = 10; // 10:00 AM
    $end_hour = 22;   // 10:00 PM
    $max_retries = 3;
    $retry_count = 0;
    
    while ($retry_count < $max_retries) {
        try {
            // Start transaction
            $connection->begin_transaction();
            
            // Find the last scheduled appointment with a lock
            $query = "SELECT MAX(appointment_datetime) as last_appointment 
                     FROM appointments 
                     WHERE appointment_datetime >= CURDATE() 
                     FOR UPDATE";
            
            $result = $connection->query($query);
            $row = $result->fetch_assoc();
            
            if ($row['last_appointment']) {
                $last_appointment = new DateTime($row['last_appointment']);
                $next_time = $last_appointment->modify('+1 hour');
            } else {
                $next_time = new DateTime();
                $next_time->setTime($start_hour, 0);
            }
            
            // If next time is after 10 PM, move to next day
            if ($next_time->format('H') >= $end_hour) {
                $next_time->modify('+1 day');
                $next_time->setTime($start_hour, 0);
            }
            
            return $next_time;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $connection->rollback();
            
            // Check if it's a deadlock
            if (strpos($e->getMessage(), 'Deadlock') !== false) {
                $retry_count++;
                if ($retry_count < $max_retries) {
                    // Wait a random time before retrying
                    usleep(rand(100000, 500000)); // 100-500ms
                    continue;
                }
            }
            throw $e;
        }
    }
    throw new Exception("Maximum retry attempts reached");
}

// Process form if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $dni = $_POST['dni'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $appointment_type = $_POST['appointment_type'] ?? '';
    
    $errors = [];
    
    if (empty($name)) $errors[] = "Name is required";
    if (!validateDNI($dni)) $errors[] = "Invalid DNI";
    if (!validatePhone($phone)) $errors[] = "Invalid phone number";
    if (!validateEmail($email)) $errors[] = "Invalid email";
    if (empty($appointment_type)) $errors[] = "Appointment type is required";
    
    if (empty($errors)) {
        $max_retries = 3;
        $retry_count = 0;
        
        while ($retry_count < $max_retries) {
            try {
                // Start transaction for the entire appointment creation process
                $connection->begin_transaction();
                
                // Check if patient exists with a lock
                $stmt = $connection->prepare("SELECT id FROM patients WHERE dni = ? FOR UPDATE");
                $stmt->bind_param("s", $dni);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    // Insert new patient
                    $stmt = $connection->prepare("INSERT INTO patients (name, dni, phone, email) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $name, $dni, $phone, $email);
                    $stmt->execute();
                    $patient_id = $connection->insert_id;
                } else {
                    $row = $result->fetch_assoc();
                    $patient_id = $row['id'];
                }
                
                // Find next available time
                $next_time = findNextAvailableTime($connection);
                $appointment_datetime = $next_time->format('Y-m-d H:i:s');
                
                // Insert appointment
                $stmt = $connection->prepare("INSERT INTO appointments (patient_id, appointment_type, appointment_datetime) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $patient_id, $appointment_type, $appointment_datetime);
                
                if ($stmt->execute()) {
                    $connection->commit();
                    // Store success message in session
                    session_start();
                    $_SESSION['success_message'] = "Appointment scheduled successfully for " . date('d/m/Y H:i', strtotime($appointment_datetime));
                    // Redirect to the same page
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    throw new Exception("Error scheduling appointment");
                }
            } catch (Exception $e) {
                $connection->rollback();
                
                // Check if it's a deadlock
                if (strpos($e->getMessage(), 'Deadlock') !== false) {
                    $retry_count++;
                    if ($retry_count < $max_retries) {
                        // Wait a random time before retrying
                        usleep(rand(100000, 500000)); // 100-500ms
                        continue;
                    }
                }
                $errors[] = $e->getMessage();
                break; // Exit retry loop on non-deadlock error
            }
        }
        
        if ($retry_count >= $max_retries) {
            $errors[] = "No se pudo procesar la solicitud después de varios intentos. Por favor, intente nuevamente.";
        }
    }
}

// Start session at the beginning of the file
session_start();

// Get success message from session if it exists
$message = $_SESSION['success_message'] ?? null;

// Clear the message from session after displaying it
if (isset($_SESSION['success_message'])) {
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Citas - Clínica de Psicología</title>
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #6c8fc7;
            --success-color: #28a745;
            --error-color: #dc3545;
            --background-color: #f8f9fa;
            --text-color: #333;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background-color: var(--background-color);
            color: var(--text-color);
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 2rem;
            font-size: 2.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        input, select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        button {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: var(--secondary-color);
        }

        .error {
            color: var(--error-color);
            margin-bottom: 1rem;
            padding: 0.8rem;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }

        .success {
            color: var(--success-color);
            margin-bottom: 1rem;
            padding: 0.8rem;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
            
            h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sistema de Citas - Clínica de Psicología</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($message)): ?>
            <div class="success">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>
        
        <form id="appointmentForm" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Nombre:</label>
                    <input type="text" id="name" name="name" required placeholder="Ingrese su nombre completo">
                </div>
                
                <div class="form-group">
                    <label for="dni">DNI:</label>
                    <input type="text" id="dni" name="dni" required pattern="[0-9]{8}[A-Z]" placeholder="12345678A">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Teléfono:</label>
                    <input type="tel" id="phone" name="phone" required pattern="[0-9]{9}" placeholder="123456789">
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required placeholder="ejemplo@correo.com">
                </div>
            </div>
            
            <div class="form-group">
                <label for="appointment_type">Tipo de cita:</label>
                <select id="appointment_type" name="appointment_type" required>
                    <option value="">Selecciona un tipo</option>
                    <option value="FIRST_VISIT">Primera consulta</option>
                    <option value="FOLLOW_UP" disabled>Revisión</option>
                </select>
            </div>
            
            <button type="submit">Solicitar Cita</button>
        </form>
    </div>

    <script>
        document.getElementById('dni').addEventListener('blur', function() {
            var dni = this.value;
            if (dni.length === 9) {
                fetch('check_patient.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'dni=' + encodeURIComponent(dni)
                })
                .then(response => response.json())
                .then(data => {
                    var appointmentTypeSelect = document.getElementById('appointment_type');
                    var followUpOption = appointmentTypeSelect.querySelector('option[value="FOLLOW_UP"]');
                    
                    if (data.exists) {
                        followUpOption.disabled = false;
                    } else {
                        followUpOption.disabled = true;
                        if (appointmentTypeSelect.value === 'FOLLOW_UP') {
                            appointmentTypeSelect.value = '';
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>