<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_login();
$user = current_user();
$document = db_medical_document_by_id((int)($_GET['id'] ?? 0));
if (!$document) { http_response_code(404); exit('Record not found.'); }
$authorized = $user['role'] === 'patient'
    ? (int)$document['patient_id'] === (int)$user['id']
    : (($doctor = array_values(array_filter($_SESSION['doctors'], fn($item) => (int)$item['user_id'] === (int)$user['id']))[0] ?? null)
        && db_doctor_can_access_patient((int)$doctor['id'], (int)$document['patient_id']));
if (!$authorized) { http_response_code(403); exit('Access denied.'); }
if (!$document['file_path'] || !str_starts_with($document['file_path'], 'uploads/medical-records/')) { http_response_code(404); exit('File not found.'); }
$root = realpath(__DIR__ . '/../uploads/medical-records');
$path = realpath(__DIR__ . '/../' . $document['file_path']);
if (!$root || !$path || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) { http_response_code(404); exit('File not found.'); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
$inline = isset($_GET['view']) && in_array($mime, ['application/pdf','image/jpeg','image/png','image/webp'], true);
$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$downloadName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $document['title']) . '.' . $extension;
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $downloadName . '"');
readfile($path);
exit;
