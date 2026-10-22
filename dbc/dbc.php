<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$connection = mysqli_connect('localhost', 'root', '');
mysqli_query($connection, "CREATE DATABASE IF NOT EXISTS medical_clinic_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_select_db($connection, 'medical_clinic_booking');
mysqli_set_charset($connection, 'utf8mb4');

mysqli_query($connection, "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) DEFAULT '',
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient','doctor') NOT NULL DEFAULT 'patient',
    created_at DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Existing databases are upgraded in place so patient profile photos can be
// stored without recreating the users table.
$userImageColumn = mysqli_query($connection, "SHOW COLUMNS FROM users LIKE 'image'");
if (mysqli_num_rows($userImageColumn) === 0) {
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN image VARCHAR(500) NOT NULL DEFAULT '' AFTER password");
}

mysqli_query($connection, "CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    specialization VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(500) DEFAULT '',
    address VARCHAR(255) NOT NULL,
    CONSTRAINT fk_doctors_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function db_users(): array {
    global $connection;
    $result = mysqli_query($connection, 'SELECT id, name, phone, email, password, image, role, created_at FROM users ORDER BY id');
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['id'] = (int) $row['id'];
        $users[$row['id']] = $row;
    }
    return $users;
}

function db_doctors(): array {
    global $connection;
    $result = mysqli_query($connection, 'SELECT id, user_id, specialization, description, image, address FROM doctors ORDER BY id');
    $doctors = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['id'] = (int) $row['id'];
        $row['user_id'] = (int) $row['user_id'];
        $doctors[$row['id']] = $row;
    }
    return $doctors;
}

function db_user_by_email(string $email): ?array {
    global $connection;
    $stmt = mysqli_prepare($connection, 'SELECT id, name, phone, email, password, image, role, created_at FROM users WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $row ?: null;
}

function db_create_user(array $user): int {
    global $connection;
    $stmt = mysqli_prepare($connection, 'INSERT INTO users (name, phone, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'ssssss', $user['name'], $user['phone'], $user['email'], $user['password'], $user['role'], $user['created_at']);
    mysqli_stmt_execute($stmt);
    return (int) mysqli_insert_id($connection);
}

function db_create_doctor(array $doctor): int {
    global $connection;
    $stmt = mysqli_prepare($connection, 'INSERT INTO doctors (user_id, specialization, description, image, address) VALUES (?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'issss', $doctor['user_id'], $doctor['specialization'], $doctor['description'], $doctor['image'], $doctor['address']);
    mysqli_stmt_execute($stmt);
    return (int) mysqli_insert_id($connection);
}

function db_update_user(array $user): void {
    global $connection;
    $stmt = mysqli_prepare($connection, 'UPDATE users SET name = ?, phone = ?, email = ?, password = ?, image = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'sssssi', $user['name'], $user['phone'], $user['email'], $user['password'], $user['image'], $user['id']);
    mysqli_stmt_execute($stmt);
}

function db_update_doctor(array $doctor): void {
    global $connection;
    $stmt = mysqli_prepare($connection, 'UPDATE doctors SET specialization = ?, description = ?, image = ?, address = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'ssssi', $doctor['specialization'], $doctor['description'], $doctor['image'], $doctor['address'], $doctor['id']);
    mysqli_stmt_execute($stmt);
}



mysqli_query($connection, "CREATE TABLE IF NOT EXISTS doctor_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day VARCHAR(20) NOT NULL,
    start_time VARCHAR(10) NOT NULL,
    end_time VARCHAR(10) NOT NULL,
    duration INT NOT NULL DEFAULT 30,
    CONSTRAINT fk_schedules_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Appointments must be stored in the database, not in the PHP session.
// Sessions are private to one browser and are destroyed on logout.
mysqli_query($connection, "CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
    ticket_number VARCHAR(40) NOT NULL UNIQUE,
    created_at DATE NOT NULL,
    INDEX idx_appointments_doctor (doctor_id),
    INDEX idx_appointments_patient (user_id),
    INDEX idx_appointments_slot (doctor_id, appointment_date, appointment_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($connection, "CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    appointment_id INT DEFAULT NULL,
    message VARCHAR(500) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATE NOT NULL,
    INDEX idx_notifications_user_read (user_id, is_read, id),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Patient-owned medical documents and clinician-authored visit summaries.
mysqli_query($connection, "CREATE TABLE IF NOT EXISTS medical_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT DEFAULT NULL,
    appointment_id INT DEFAULT NULL,
    category ENUM('prescription','lab','report','other') NOT NULL,
    title VARCHAR(190) NOT NULL,
    details TEXT NOT NULL,
    file_path VARCHAR(500) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_medical_documents_patient (patient_id, category, created_at),
    INDEX idx_medical_documents_doctor (doctor_id),
    CONSTRAINT fk_medical_documents_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_medical_documents_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
    CONSTRAINT fk_medical_documents_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($connection, "CREATE TABLE IF NOT EXISTS clinical_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT DEFAULT NULL,
    symptoms TEXT NOT NULL,
    diagnosis TEXT NOT NULL,
    recommendations TEXT NOT NULL,
    blood_pressure VARCHAR(30) NOT NULL DEFAULT '',
    weight_kg DECIMAL(6,2) DEFAULT NULL,
    glucose_mg_dl DECIMAL(7,2) DEFAULT NULL,
    heart_rate INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_clinical_notes_patient (patient_id, created_at),
    INDEX idx_clinical_notes_doctor (doctor_id),
    CONSTRAINT fk_clinical_notes_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_clinical_notes_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    CONSTRAINT fk_clinical_notes_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($connection, "CREATE TABLE IF NOT EXISTS patient_medical_history (
    patient_id INT PRIMARY KEY,
    allergies TEXT NOT NULL,
    chronic_conditions TEXT NOT NULL,
    surgeries TEXT NOT NULL,
    updated_by_doctor_id INT DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_medical_history_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_medical_history_doctor FOREIGN KEY (updated_by_doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function db_medical_documents_for_patient(int $patientId): array {
    global $connection;
    $stmt = mysqli_prepare($connection, 'SELECT md.*, u.name AS doctor_name FROM medical_documents md LEFT JOIN doctors d ON d.id = md.doctor_id LEFT JOIN users u ON u.id = d.user_id WHERE md.patient_id = ? ORDER BY md.created_at DESC, md.id DESC');
    mysqli_stmt_bind_param($stmt, 'i', $patientId);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

function db_medical_document_by_id(int $documentId): ?array {
    global $connection;
    $stmt = mysqli_prepare($connection, 'SELECT * FROM medical_documents WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $documentId);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
}

function db_create_medical_document(array $document): int {
    global $connection;
    $doctorId = $document['doctor_id'] ?? null;
    $appointmentId = $document['appointment_id'] ?? null;
    $stmt = mysqli_prepare($connection, 'INSERT INTO medical_documents (patient_id, doctor_id, appointment_id, category, title, details, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iiissss', $document['patient_id'], $doctorId, $appointmentId, $document['category'], $document['title'], $document['details'], $document['file_path']);
    mysqli_stmt_execute($stmt);
    return (int)mysqli_insert_id($connection);
}

function db_clinical_notes_for_patient(int $patientId): array {
    global $connection;
    $stmt = mysqli_prepare($connection, 'SELECT n.*, u.name AS doctor_name FROM clinical_notes n JOIN doctors d ON d.id = n.doctor_id JOIN users u ON u.id = d.user_id WHERE n.patient_id = ? ORDER BY n.created_at DESC, n.id DESC');
    mysqli_stmt_bind_param($stmt, 'i', $patientId);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

function db_create_clinical_note(array $note): int {
    global $connection;
    $appointmentId = $note['appointment_id'] ?? null;
    $weight = $note['weight_kg'] === '' ? null : (float)$note['weight_kg'];
    $glucose = $note['glucose_mg_dl'] === '' ? null : (float)$note['glucose_mg_dl'];
    $heartRate = $note['heart_rate'] === '' ? null : (int)$note['heart_rate'];
    $stmt = mysqli_prepare($connection, 'INSERT INTO clinical_notes (patient_id, doctor_id, appointment_id, symptoms, diagnosis, recommendations, blood_pressure, weight_kg, glucose_mg_dl, heart_rate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iiissssddi', $note['patient_id'], $note['doctor_id'], $appointmentId, $note['symptoms'], $note['diagnosis'], $note['recommendations'], $note['blood_pressure'], $weight, $glucose, $heartRate);
    mysqli_stmt_execute($stmt);
    return (int)mysqli_insert_id($connection);
}

function db_patient_medical_history(int $patientId): ?array {
    global $connection;
    $stmt = mysqli_prepare($connection, 'SELECT * FROM patient_medical_history WHERE patient_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $patientId);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
}

function db_save_patient_medical_history(int $patientId, int $doctorId, string $allergies, string $conditions, string $surgeries): void {
    global $connection;
    $stmt = mysqli_prepare($connection, 'INSERT INTO patient_medical_history (patient_id, updated_by_doctor_id, allergies, chronic_conditions, surgeries) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE updated_by_doctor_id = VALUES(updated_by_doctor_id), allergies = VALUES(allergies), chronic_conditions = VALUES(chronic_conditions), surgeries = VALUES(surgeries), updated_at = CURRENT_TIMESTAMP');
    mysqli_stmt_bind_param($stmt, 'iisss', $patientId, $doctorId, $allergies, $conditions, $surgeries);
    mysqli_stmt_execute($stmt);
}

function db_doctor_can_access_patient(int $doctorId, int $patientId): bool {
    global $connection;
    $stmt = mysqli_prepare($connection, 'SELECT 1 FROM appointments WHERE doctor_id = ? AND user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'ii', $doctorId, $patientId);
    mysqli_stmt_execute($stmt);
    return (bool)mysqli_fetch_row(mysqli_stmt_get_result($stmt));
}

function db_medical_patients_for_doctor(int $doctorId, string $search = ''): array {
    global $connection;
    $searchLike = '%' . $search . '%';
    $stmt = mysqli_prepare($connection, "SELECT DISTINCT u.id, u.name, u.phone FROM users u JOIN appointments a ON a.user_id = u.id WHERE a.doctor_id = ? AND u.role = 'patient' AND (? = '' OR u.name LIKE ? OR u.phone LIKE CAST(? AS CHAR) OR CAST(u.id AS CHAR) LIKE ?) ORDER BY u.name LIMIT 50");
    mysqli_stmt_bind_param($stmt, 'issss', $doctorId, $search, $searchLike, $searchLike, $searchLike);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

function db_notifications_for_user(int $userId): array {
    global $connection;
    $stmt = mysqli_prepare($connection, 'SELECT id, user_id, appointment_id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY id');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['id'] = (int)$row['id'];
        $row['user_id'] = (int)$row['user_id'];
        $row['appointment_id'] = $row['appointment_id'] === null ? null : (int)$row['appointment_id'];
        $row['is_read'] = (bool)$row['is_read'];
        $notifications[] = $row;
    }
    return $notifications;
}

function db_create_notification(array $notification): int {
    global $connection;
    $appointmentId = $notification['appointment_id'] ?? null;
    $stmt = mysqli_prepare($connection, 'INSERT INTO notifications (user_id, appointment_id, message, is_read, created_at) VALUES (?, ?, ?, 0, ?)');
    mysqli_stmt_bind_param($stmt, 'iiss', $notification['user_id'], $appointmentId, $notification['message'], $notification['created_at']);
    mysqli_stmt_execute($stmt);
    return (int)mysqli_insert_id($connection);
}

function db_mark_notification_read(int $notificationId, int $userId): void {
    global $connection;
    $stmt = mysqli_prepare($connection, 'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $notificationId, $userId);
    mysqli_stmt_execute($stmt);
}

function db_mark_all_notifications_read(int $userId): void {
    global $connection;
    $stmt = mysqli_prepare($connection, 'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
}

function db_appointments(): array {
    global $connection;
    $result = mysqli_query($connection, 'SELECT id, user_id, doctor_id, appointment_date, TIME_FORMAT(appointment_time, "%H:%i") AS appointment_time, status, ticket_number, created_at FROM appointments ORDER BY appointment_date, appointment_time, id');
    $appointments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['id'] = (int) $row['id'];
        $row['user_id'] = (int) $row['user_id'];
        $row['doctor_id'] = (int) $row['doctor_id'];
        $appointments[$row['id']] = $row;
    }
    return $appointments;
}

function db_create_appointment(array $appointment): int {
    global $connection;
    $stmt = mysqli_prepare($connection, 'INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time, status, ticket_number, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iisssss', $appointment['user_id'], $appointment['doctor_id'], $appointment['appointment_date'], $appointment['appointment_time'], $appointment['status'], $appointment['ticket_number'], $appointment['created_at']);
    mysqli_stmt_execute($stmt);
    return (int) mysqli_insert_id($connection);
}

function db_update_appointment_status(int $appointmentId, string $status): void {
    global $connection;
    $stmt = mysqli_prepare($connection, 'UPDATE appointments SET status = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'si', $status, $appointmentId);
    mysqli_stmt_execute($stmt);
}

function db_schedules_by_doctor(int $doctorId): array {
    global $connection;
    $stmt = mysqli_prepare($connection, 'SELECT id, doctor_id, day, start_time, end_time, duration FROM doctor_schedules WHERE doctor_id = ? ORDER BY FIELD(day, "Saturday","Sunday","Monday","Tuesday","Wednesday","Thursday","Friday"), start_time');
    mysqli_stmt_bind_param($stmt, 'i', $doctorId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['id'] = (int) $row['id'];
        $row['doctor_id'] = (int) $row['doctor_id'];
        $row['duration'] = (int) $row['duration'];

        $row['start'] = $row['start_time'];
        $row['end']   = $row['end_time'];
        $rows[] = $row;
    }
    return $rows;
}

function db_all_schedules(): array {
    global $connection;
    $result = mysqli_query($connection, 'SELECT id, doctor_id, day, start_time, end_time, duration FROM doctor_schedules ORDER BY doctor_id, id');
    $grouped = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['id'] = (int) $row['id'];
        $row['doctor_id'] = (int) $row['doctor_id'];
        $row['duration'] = (int) $row['duration'];
        $row['start'] = $row['start_time'];
        $row['end']   = $row['end_time'];
        $grouped[$row['doctor_id']][] = $row;
    }
    return $grouped;
}

function db_create_schedule(array $schedule): int {
    global $connection;
    $stmt = mysqli_prepare($connection, 'INSERT INTO doctor_schedules (doctor_id, day, start_time, end_time, duration) VALUES (?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'isssi', $schedule['doctor_id'], $schedule['day'], $schedule['start'], $schedule['end'], $schedule['duration']);
    mysqli_stmt_execute($stmt);
    return (int) mysqli_insert_id($connection);
}

function db_delete_schedule(int $scheduleId, int $doctorId): void {
    global $connection;
    $stmt = mysqli_prepare($connection, 'DELETE FROM doctor_schedules WHERE id = ? AND doctor_id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $scheduleId, $doctorId);
    mysqli_stmt_execute($stmt);
}

function db_delete_user(int $userId): void {
    global $connection;
    $stmt = mysqli_prepare($connection, 'DELETE FROM users WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
}
