<?php
session_start();

$_SESSION['users'] = db_users();
$_SESSION['doctors'] = db_doctors();

if (!$_SESSION['users']) {
    $patientId = db_create_user([
        'name' => 'Ahmed Mohamed', 'phone' => '01012345678',
        'email' => 'patient@medibook.test', 'password' => password_hash('123456', PASSWORD_DEFAULT),
        'role' => 'patient', 'created_at' => '2026-09-01'
    ]);
    $doctorUserId = db_create_user([
        'name' => 'Dr. Mohamed Hassan', 'phone' => '01098765432',
        'email' => 'doctor@medibook.test', 'password' => password_hash('123456', PASSWORD_DEFAULT),
        'role' => 'doctor', 'created_at' => '2026-09-01'
    ]);
    db_create_doctor([
        'user_id' => $doctorUserId, 'specialization' => 'Cardiology',
        'description' => 'Experienced cardiologist focused on preventive care and heart health.',
        'image' => 'https://randomuser.me/api/portraits/men/32.jpg', 'address' => 'Nasr City, Cairo'
    ]);
    $_SESSION['users'] = db_users();
    $_SESSION['doctors'] = db_doctors();
}

// A small, repeatable directory of fictional doctors helps patient accounts
// explore the booking flow on both fresh and already-seeded databases.
$demoDoctors = [
    [
        'name' => 'Dr. Sara Adel', 'phone' => '01000000001', 'email' => 'dr.sara@aurahealth.test',
        'specialization' => 'Dermatology',
        'description' => 'Dermatologist focused on practical, evidence-based skin care.',
        'image' => 'https://randomuser.me/api/portraits/women/44.jpg', 'address' => 'Heliopolis, Cairo'
    ],
    [
        'name' => 'Dr. Omar Nabil', 'phone' => '01000000002', 'email' => 'dr.omar@aurahealth.test',
        'specialization' => 'Dentistry',
        'description' => 'General dentist offering preventive and restorative dental care.',
        'image' => 'https://randomuser.me/api/portraits/men/46.jpg', 'address' => 'Maadi, Cairo'
    ],
    [
        'name' => 'Dr. Youssef Kamal', 'phone' => '01000000003', 'email' => 'dr.youssef@aurahealth.test',
        'specialization' => 'Pediatrics',
        'description' => 'Pediatrician supporting children and families through every stage of growth.',
        'image' => 'https://randomuser.me/api/portraits/men/75.jpg', 'address' => 'Nasr City, Cairo'
    ],
    [
        'name' => 'Dr. Mariam Fathy', 'phone' => '01000000004', 'email' => 'dr.mariam@aurahealth.test',
        'specialization' => 'Nutrition',
        'description' => 'Nutrition specialist creating sustainable, personalized wellness plans.',
        'image' => 'https://randomuser.me/api/portraits/women/68.jpg', 'address' => 'New Cairo'
    ],
    [
        'name' => 'Dr. Karim Hassan', 'phone' => '01000000005', 'email' => 'dr.karim@aurahealth.test',
        'specialization' => 'Cardiology',
        'description' => 'Cardiologist with an emphasis on prevention and long-term heart health.',
        'image' => 'https://randomuser.me/api/portraits/men/52.jpg', 'address' => 'Zamalek, Cairo'
    ],
];

foreach ($demoDoctors as $index => $sample) {
    $existingUser = db_user_by_email($sample['email']);
    if (!$existingUser) {
        $userId = db_create_user([
            'name' => $sample['name'], 'phone' => $sample['phone'], 'email' => $sample['email'],
            'password' => password_hash('123456', PASSWORD_DEFAULT), 'role' => 'doctor',
            'created_at' => '2026-10-22'
        ]);
        db_create_doctor([
            'user_id' => $userId, 'specialization' => $sample['specialization'],
            'description' => $sample['description'], 'image' => $sample['image'], 'address' => $sample['address']
        ]);
    }
}

// Replace the old remote avatar only for the known demo doctor account.
$demoDoctorUser = db_user_by_email('doctor@medibook.test');
if ($demoDoctorUser) {
    foreach (db_doctors() as $doctor) {
        if ((int)$doctor['user_id'] === (int)$demoDoctorUser['id'] && str_contains($doctor['image'], 'pravatar.cc')) {
            $doctor['image'] = 'https://randomuser.me/api/portraits/men/32.jpg';
            db_update_doctor($doctor);
        }
    }
}

$_SESSION['users'] = db_users();
$_SESSION['doctors'] = db_doctors();

$allSchedules = db_all_schedules();
if (empty($allSchedules)) {
    $defaultSchedules = [
        1 => [
            ['day'=>'Saturday','start'=>'10:00','end'=>'14:00','duration'=>30],
            ['day'=>'Monday','start'=>'16:00','end'=>'20:00','duration'=>30],
            ['day'=>'Wednesday','start'=>'10:00','end'=>'14:00','duration'=>30],
        ],
        2 => [
            ['day'=>'Sunday','start'=>'11:00','end'=>'15:00','duration'=>30],
            ['day'=>'Tuesday','start'=>'17:00','end'=>'21:00','duration'=>30],
        ],
        3 => [
            ['day'=>'Monday','start'=>'12:00','end'=>'16:00','duration'=>30],
            ['day'=>'Thursday','start'=>'16:00','end'=>'20:00','duration'=>30],
        ],
    ];
    foreach ($defaultSchedules as $docId => $slots) {
        if (isset($_SESSION['doctors'][$docId])) {
            foreach ($slots as $slot) {
                db_create_schedule(array_merge($slot, ['doctor_id' => $docId]));
            }
        }
    }
    $allSchedules = db_all_schedules();
}
$_SESSION['doctor_schedules'] = $allSchedules;

// Give the seeded sample doctors bookable weekly slots even when the database
// already contained schedules for other doctors.
$sampleScheduleTemplates = [
    ['day' => 'Sunday', 'start' => '10:00', 'end' => '14:00', 'duration' => 30],
    ['day' => 'Tuesday', 'start' => '15:00', 'end' => '19:00', 'duration' => 30],
];
foreach (db_doctors() as $doctor) {
    $doctorUser = $_SESSION['users'][$doctor['user_id']] ?? null;
    if ($doctorUser && str_ends_with($doctorUser['email'], '@aurahealth.test') && !db_schedules_by_doctor((int)$doctor['id'])) {
        foreach ($sampleScheduleTemplates as $slot) {
            db_create_schedule(array_merge($slot, ['doctor_id' => (int)$doctor['id']]));
        }
    }
}
$_SESSION['doctor_schedules'] = db_all_schedules();

// Reload appointments from MySQL on every request so patients and doctors
// see the same data, even after logout or from different browsers.
if (empty(db_appointments())) {
    $seedAppointments = [
        ['user_id'=>1, 'doctor_id'=>1, 'appointment_date'=>'2026-09-07', 'appointment_time'=>'16:30', 'status'=>'confirmed', 'ticket_number'=>'MED-1001', 'created_at'=>'2026-09-03'],
        ['user_id'=>1, 'doctor_id'=>2, 'appointment_date'=>'2026-09-12', 'appointment_time'=>'11:30', 'status'=>'pending', 'ticket_number'=>'MED-1002', 'created_at'=>'2026-09-04'],
    ];
    foreach ($seedAppointments as $appointment) {
        if (isset($_SESSION['users'][$appointment['user_id']], $_SESSION['doctors'][$appointment['doctor_id']])) {
            db_create_appointment($appointment);
        }
    }
}
$_SESSION['appointments'] = db_appointments();

$demoPatient = db_user_by_email('patient@medibook.test');
if ($demoPatient && !db_notifications_for_user((int)$demoPatient['id'])) {
    db_create_notification([
        'user_id' => (int)$demoPatient['id'], 'appointment_id' => null,
        'message' => 'Welcome to AuraHealth. Your appointment updates will appear here.', 'created_at' => date('Y-m-d')
    ]);
}
$demoDoctorAccount = db_user_by_email('doctor@medibook.test');
if ($demoDoctorAccount && !db_notifications_for_user((int)$demoDoctorAccount['id'])) {
    db_create_notification([
        'user_id' => (int)$demoDoctorAccount['id'], 'appointment_id' => null,
        'message' => 'Welcome to AuraHealth. New booking updates will appear here.', 'created_at' => date('Y-m-d')
    ]);
}

function current_user(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    return $_SESSION['users'][$_SESSION['user_id']] ?? null;
}

function default_avatar_icon(): string {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3.25"/><path d="M5.5 20a6.5 6.5 0 0 1 13 0"/></svg>';
}

function app_url(string $path = ''): string {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    if (basename($scriptDir) === 'doctor' || basename($scriptDir) === 'patient') {
        $base = dirname($scriptDir);
    } else {
        $base = $scriptDir;
    }
    $base = rtrim(str_replace('\\', '/', $base), '/');
    return ($base ? $base : '') . '/' . ltrim($path, '/');
}

function require_login(): void {
    if (!current_user()) {
        header('Location: ' . app_url('login.php'));
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if (current_user()['role'] !== $role) {
        header('Location: ' . app_url('index.php'));
        exit;
    }
}

function doctor_by_id(int $doctorId): ?array {
    foreach ($_SESSION['doctors'] as $doctor) {
        if ((int)$doctor['id'] === $doctorId) {
            $user = $_SESSION['users'][$doctor['user_id']] ?? [];
            return array_merge($doctor, ['name'=>$user['name'] ?? 'Doctor']);
        }
    }
    return null;
}

function doctors(): array {
    $result = [];
    foreach ($_SESSION['doctors'] as $doctor) {
        $user = $_SESSION['users'][$doctor['user_id']] ?? [];
        $result[] = array_merge($doctor, ['name'=>$user['name'] ?? 'Doctor']);
    }
    return $result;
}

function appointment_by_id(int $id): ?array {
    return $_SESSION['appointments'][$id] ?? null;
}

// Always read schedules from MySQL so a newly added schedule is visible
// immediately to both the doctor and patients, even when their session is old.
function schedules_for_doctor(int $doctorId): array {
    $schedules = db_schedules_by_doctor($doctorId);
    $_SESSION['doctor_schedules'][$doctorId] = $schedules;
    return $schedules;
}

function status_class(string $status): string {
    return match ($status) {
        'confirmed' => 'success',
        'completed' => 'info',
        'cancelled' => 'danger',
        default => 'warning',
    };
}
