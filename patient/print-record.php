<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_login();
$user = current_user();
$document = db_medical_document_by_id((int)($_GET['id'] ?? 0));
if (!$document || $document['category'] !== 'prescription') { http_response_code(404); exit('Prescription not found.'); }
$doctor = array_values(array_filter($_SESSION['doctors'], fn($item) => (int)$item['user_id'] === (int)$user['id']))[0] ?? null;
$authorized = $user['role'] === 'patient'
    ? (int)$document['patient_id'] === (int)$user['id']
    : ($doctor && db_doctor_can_access_patient((int)$doctor['id'], (int)$document['patient_id']));
if (!$authorized) { http_response_code(403); exit('Access denied.'); }
$patient = $_SESSION['users'][(int)$document['patient_id']] ?? ['name'=>'Patient'];
$issuer = $document['doctor_id'] ? doctor_by_id((int)$document['doctor_id']) : null;
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Prescription · <?= htmlspecialchars($document['title']) ?></title>
<script>
try {
    const portalLanguage = localStorage.getItem('medibook-language') === 'ar' ? 'ar' : 'en';
    document.documentElement.lang = portalLanguage;
    document.documentElement.dir = portalLanguage === 'ar' ? 'rtl' : 'ltr';
} catch (error) {}
</script>
<style>
*{box-sizing:border-box}body{margin:0;background:#eef5f8;color:#173047;font:16px/1.6 Arial,sans-serif}.sheet{max-width:760px;margin:44px auto;padding:48px;background:#fff;border:1px solid #dbe8ef;border-radius:18px;box-shadow:0 16px 45px #153e5a12}.brand{display:flex;align-items:center;gap:12px;color:#075985;font-size:22px;font-weight:700}.brand-mark{display:grid;place-items:center;width:42px;height:42px;border-radius:14px;background:#e0f2fe;color:#0284c7;font-size:26px}.eyebrow{margin:32px 0 4px;color:#0284c7;font-size:12px;font-weight:700;letter-spacing:.12em}.sheet h1{margin:0 0 24px;font-size:30px}.meta{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:18px;border-radius:12px;background:#f4f9fc}.meta span{display:block;color:#72879a;font-size:12px}.meta strong{display:block}.medicine{margin:22px 0;padding:24px;border-left:4px solid #0ea5e9;border-radius:8px;background:#f0f9ff}.medicine h2{margin:0 0 8px;font-size:21px}.details{white-space:pre-wrap}.footer{margin-top:54px;padding-top:18px;border-top:1px solid #dbe8ef;color:#72879a;font-size:12px}.actions{display:flex;justify-content:center;margin:22px auto}.actions button{padding:11px 18px;border:0;border-radius:10px;background:#0284c7;color:white;font-weight:700;cursor:pointer}@media print{body{background:#fff}.sheet{max-width:none;margin:0;padding:20px;border:0;box-shadow:none}.actions{display:none}}
</style></head><body><main class="sheet"><div class="brand"><span class="brand-mark">+</span>AuraHealth</div><p class="eyebrow">DIGITAL PRESCRIPTION</p><h1><?= htmlspecialchars($document['title']) ?></h1><div class="meta"><div><span>Patient</span><strong><?= htmlspecialchars($patient['name']) ?></strong></div><div><span>Issued by</span><strong><?= htmlspecialchars($issuer['name'] ?? 'AuraHealth clinician') ?></strong></div><div><span>Issue date</span><strong><?= htmlspecialchars(date('d M Y', strtotime($document['created_at']))) ?></strong></div><div><span>Prescription ID</span><strong>RX-<?= (int)$document['id'] ?></strong></div></div><section class="medicine"><h2>Medication &amp; dosage</h2><div class="details"><?= htmlspecialchars($document['details']) ?></div></section><p>Follow the instructions provided by your clinician. Contact the clinic if you have questions about this prescription.</p><div class="footer">This document is provided through AuraHealth for the named patient.</div><div class="actions"><button onclick="window.print()">Print / Save as PDF</button></div></main></body></html>
