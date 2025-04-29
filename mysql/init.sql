CREATE DATABASE IF NOT EXISTS miapp
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE miapp;

CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    dni VARCHAR(9) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(100) NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    appointment_type ENUM('FIRST_VISIT', 'FOLLOW_UP') NOT NULL,
    appointment_datetime DATETIME NOT NULL,
    status ENUM('PENDING', 'CONFIRMED', 'CANCELLED') DEFAULT 'PENDING',
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    UNIQUE KEY unique_appointment_datetime (appointment_datetime)
);