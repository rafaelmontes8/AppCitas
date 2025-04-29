# Ejercicio 1 - Script de Decodificación (decodificar.py)

El proyecto incluye un script de Python (`decodificar.py`) que se encarga de decodificar puntuaciones almacenadas en un archivo CSV. Este script:

1. Lee un archivo `puntuaciones.csv` que contiene:
   - Nombre de usuario
   - Sistema de codificacion
   - Puntuacion codificada

2. Utiliza la función `decode_score` para convertir las cadenas codificadas en números enteros, usando un sistema de numeración personalizado.

3. Muestra los resultados por la consola: `usuario,puntuación_decodificada`

Para ejecutar el script:
```bash
py decodificar.py
```

El script espera que el archivo `puntuaciones.csv` esté en el mismo directorio y tenga el formato correcto. 

# Ejercicio 2 - Sistema de Citas Online - Clínica de Psicología

Sistema de gestión de citas online para una clínica de psicología, desarrollado en PHP sin frameworks.

## Características

- Formulario de solicitud de citas con validación
- Verificación de DNI en tiempo real
- Sistema de asignación automática de citas
- Manejo de concurrencia y deadlocks
- Interfaz de usuario intuitiva
- Base de datos MySQL

## Requisitos

- Docker
- Docker Compose
- PHP 8.2 o superior
- MySQL 5.7 o superior

## Estructura del Proyecto

```
.
├── docker-compose.yml
├── mysql/
│   └── init.sql
├── php/
│   └── Dockerfile
└── public_html/
    ├── index.php
    ├── check_patient.php
    ├── simulate_concurrent_requests.php
    └── check_appointments.php
```

## Instalación

1. Clonar el repositorio:
```bash
git clone https://github.com/rafaelmontes8/AppCitas.git
cd AppCitas
```

2.Instalar Docker (Puede variar segun OS, en windows necesita tambien WSL).


3. Iniciar los contenedores Docker:
```bash
docker-compose up --build -d
```

4. Acceder a la aplicación:
```
http://localhost:8080
```

## Uso

### Solicitud de Citas
1. Accede a `http://localhost:8080`
2. Completa el formulario con los datos del paciente
3. El sistema verificará automáticamente si el DNI existe
4. Selecciona el tipo de cita (Primera consulta o Revisión)
5. El sistema asignará automáticamente la próxima hora disponible

### Simulación de Concurrencia
Para probar el sistema con múltiples solicitudes simultáneas:
1. Accede a `http://localhost:8080/simulate_concurrent_requests.php`
2. El sistema realizará 5 solicitudes de citas simultáneas
3. Verás los resultados de cada solicitud en la página

### Ver Citas Programadas
Para ver todas las citas programadas:
1. Accede a `http://localhost:8080/check_appointments.php`
2. Se mostrará una tabla con todas las citas ordenadas por fecha

## Base de Datos

### Estructura

#### Tabla: patients
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- name (VARCHAR)
- dni (VARCHAR, UNIQUE)
- phone (VARCHAR)
- email (VARCHAR)
- registration_date (TIMESTAMP)

#### Tabla: appointments
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- patient_id (INT, FOREIGN KEY)
- appointment_type (ENUM: 'FIRST_VISIT', 'FOLLOW_UP')
- appointment_datetime (DATETIME)
- status (ENUM: 'PENDING', 'CONFIRMED', 'CANCELLED')

## Características Técnicas

- Manejo de concurrencia con transacciones MySQL
- Sistema de reintentos automáticos para deadlocks
- Validación de datos en cliente y servidor
- Interfaz responsiva
- Verificación de DNI en tiempo real con AJAX

## Consideraciones de Seguridad

- Validación de datos en servidor
- Uso de prepared statements para prevenir SQL injection
- Sanitización de salida HTML
- Manejo seguro de transacciones

## Solución de Problemas

### Deadlocks
Si encuentras errores de deadlock:
1. El sistema reintentará automáticamente hasta 3 veces
2. Si persiste el error, intenta nuevamente más tarde
3. Los deadlocks son normales en entornos de alta concurrencia

### Conexión a la Base de Datos
Si hay problemas de conexión:
1. Verifica que los contenedores Docker estén corriendo
2. Comprueba las credenciales en la configuración
3. Revisa los logs de Docker

## Licencia

Este proyecto está protegido por una licencia de uso restrictivo. Se prohíbe expresamente la copia, distribución o modificación del código sin autorización expresa. Para más detalles, consulte el archivo [LICENSE](LICENSE).