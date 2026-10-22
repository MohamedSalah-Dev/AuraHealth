<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('doctor');
$pageTitle = 'Patient Records';
$user = current_user();
$doctor = null;
foreach ($_SESSION['doctors'] as $candidate) if ((int)$candidate['user_id'] === (int)$user['id']) { $doctor = $candidate; break; }
if (!$doctor) { http_response_code(403); exit('Doctor profile not found.'); }
if (empty($_SESSION['doctor_records_csrf'])) $_SESSION['doctor_records_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['doctor_records_csrf'];
$search = trim($_GET['q'] ?? '');
$patientId = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!$patientId || !db_doctor_can_access_patient((int)$doctor['id'], $patientId)) {
        http_response_code(403);
        exit('You do not have access to this patient record.');
    } else {
        $action = $_POST['record_action'] ?? '';
        if ($action === 'prescription') {
            $medicine = trim($_POST['medicine'] ?? '');
            $dosage = trim($_POST['dosage'] ?? '');
            $instructions = trim($_POST['instructions'] ?? '');
            if ($medicine === '' || $dosage === '') $error = 'Enter the medicine name and dosage.';
            else {
                $details = "Dosage: {$dosage}" . ($instructions !== '' ? "\nInstructions: {$instructions}" : '');
                db_create_medical_document(['patient_id'=>$patientId, 'doctor_id'=>(int)$doctor['id'], 'appointment_id'=>null, 'category'=>'prescription', 'title'=>$medicine, 'details'=>$details, 'file_path'=>'']);
            }
        } elseif ($action === 'clinical_note') {
            $symptoms = trim($_POST['symptoms'] ?? '');
            $diagnosis = trim($_POST['diagnosis'] ?? '');
            $recommendations = trim($_POST['recommendations'] ?? '');
            $bloodPressure = trim($_POST['blood_pressure'] ?? '');
            $weight = trim($_POST['weight_kg'] ?? '');
            $glucose = trim($_POST['glucose_mg_dl'] ?? '');
            $heartRate = trim($_POST['heart_rate'] ?? '');
            $numericVitalsValid = ($weight === '' || (is_numeric($weight) && (float)$weight > 0 && (float)$weight < 500))
                && ($glucose === '' || (is_numeric($glucose) && (float)$glucose > 0 && (float)$glucose < 3000))
                && ($heartRate === '' || (ctype_digit($heartRate) && (int)$heartRate > 0 && (int)$heartRate < 400));
            if ($diagnosis === '' && $symptoms === '' && $recommendations === '') $error = 'Add symptoms, a diagnosis, or recommendations.';
            elseif (!$numericVitalsValid) $error = 'Check the entered vital values.';
            else db_create_clinical_note(['patient_id'=>$patientId, 'doctor_id'=>(int)$doctor['id'], 'appointment_id'=>null, 'symptoms'=>$symptoms, 'diagnosis'=>$diagnosis, 'recommendations'=>$recommendations, 'blood_pressure'=>$bloodPressure, 'weight_kg'=>$weight, 'glucose_mg_dl'=>$glucose, 'heart_rate'=>$heartRate]);
        } elseif ($action === 'medical_history') {
            db_save_patient_medical_history($patientId, (int)$doctor['id'], trim($_POST['allergies'] ?? ''), trim($_POST['chronic_conditions'] ?? ''), trim($_POST['surgeries'] ?? ''));
        } else {
            $error = 'Choose a valid record action.';
        }
        if (!$error) {
            header('Location: records.php?patient_id=' . $patientId . '&saved=1');
            exit;
        }
    }
}

$patients = db_medical_patients_for_doctor((int)$doctor['id'], $search);
$patient = null;
if ($patientId && db_doctor_can_access_patient((int)$doctor['id'], $patientId)) {
    $patient = $_SESSION['users'][$patientId] ?? null;
    if (!$patient || $patient['role'] !== 'patient') $patient = null;
}
$documents = $patient ? db_medical_documents_for_patient($patientId) : [];
$notes = $patient ? db_clinical_notes_for_patient($patientId) : [];
$history = $patient ? (db_patient_medical_history($patientId) ?? ['allergies'=>'','chronic_conditions'=>'','surgeries'=>'','updated_at'=>null]) : null;
include __DIR__ . '/../includes/header.php';
?>
<section class="records-heading doctor-records-heading">
    <div><span class="eyebrow">CLINICAL WORKSPACE</span><h2>Patient medical records</h2><p>Search for a patient you've seen to review and update their clinical record.</p></div>
</section>
<form class="patient-record-search" method="get">
    <label for="patient-record-query">Search patient records</label>
    <div><input id="patient-record-query" type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by patient name, phone, or ID"><button class="btn primary" type="submit">Search patients</button></div>
    <small>Only patients with an appointment with you are shown.</small>
</form>
<?php if ($error): ?><div class="alert danger records-alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="alert success records-alert">The patient record was updated successfully.</div><?php endif; ?>

<?php if (!$patient): ?>
    <section class="patient-directory"><div class="records-panel-heading"><div><h3>Patients you've seen</h3><p>Select a patient to open their medical record.</p></div></div>
        <?php if ($patients): ?><div class="patient-record-list"><?php foreach ($patients as $listedPatient): ?><a class="patient-record-row" href="records.php?patient_id=<?= (int)$listedPatient['id'] ?><?= $search !== '' ? '&amp;q=' . rawurlencode($search) : '' ?>"><span class="patient-record-avatar"><?= default_avatar_icon() ?></span><span><strong><?= htmlspecialchars($listedPatient['name']) ?></strong><small><?= htmlspecialchars($listedPatient['phone'] ?: 'No phone added') ?> · Patient ID <?= (int)$listedPatient['id'] ?></small></span><span class="patient-row-arrow" aria-hidden="true">→</span></a><?php endforeach; ?></div>
        <?php else: ?><div class="records-empty-state"><span class="records-empty-icon" aria-hidden="true">⌕</span><h4>No matching patients</h4><p>Patient records become available after an appointment has been booked with you.</p></div><?php endif; ?>
    </section>
<?php else: ?>
<section class="selected-patient-banner"><a class="back-link" href="records.php<?= $search !== '' ? '?q=' . rawurlencode($search) : '' ?>">← Patient list</a><div class="selected-patient-identity"><span class="patient-record-avatar large"><?= default_avatar_icon() ?></span><div><span class="eyebrow">PATIENT RECORD · ID <?= (int)$patient['id'] ?></span><h3><?= htmlspecialchars($patient['name']) ?></h3><p><?= htmlspecialchars($patient['phone'] ?: 'No phone added') ?></p></div></div></section>

<div class="doctor-record-actions"><button class="btn primary" type="button" data-open-clinical="prescription">+ Issue new prescription</button><button class="btn secondary" type="button" data-open-clinical="note">+ Add clinical note</button></div>
<div class="records-tabs" role="tablist" aria-label="Medical record sections">
    <button class="records-tab is-active" type="button" role="tab" aria-selected="true" aria-controls="doctor-records-prescriptions" data-record-tab="prescriptions">Prescriptions <span><?= count(array_filter($documents, fn($doc) => $doc['category'] === 'prescription')) ?></span></button>
    <button class="records-tab" type="button" role="tab" aria-selected="false" aria-controls="doctor-records-results" data-record-tab="results">Labs &amp; reports <span><?= count(array_filter($documents, fn($doc) => $doc['category'] !== 'prescription')) ?></span></button>
    <button class="records-tab" type="button" role="tab" aria-selected="false" aria-controls="doctor-records-history" data-record-tab="history">Medical history</button>
    <button class="records-tab" type="button" role="tab" aria-selected="false" aria-controls="doctor-records-visits" data-record-tab="visits">Visit summaries <span><?= count($notes) ?></span></button>
</div>
<section class="records-panel is-active" id="doctor-records-prescriptions" role="tabpanel" data-record-panel="prescriptions">
    <div class="records-panel-heading"><div><h3>Digital prescriptions</h3><p>Prescriptions issued to this patient.</p></div></div>
    <div class="records-list"><?php $prescriptions = array_values(array_filter($documents, fn($doc) => $doc['category'] === 'prescription')); foreach ($prescriptions as $document): ?><article class="medical-record-card"><span class="record-type-icon record-type-prescription" aria-hidden="true">Rx</span><div class="medical-record-main"><div class="record-meta"><span class="record-category-pill">Prescription</span><time><?= htmlspecialchars(date('d M Y', strtotime($document['created_at']))) ?></time></div><h4><?= htmlspecialchars($document['title']) ?></h4><p><?= nl2br(htmlspecialchars($document['details'])) ?></p><small>Issued by <?= htmlspecialchars($document['doctor_name'] ?: $user['name']) ?></small></div><div class="medical-record-actions"><a class="btn secondary small" target="_blank" href="../patient/print-record.php?id=<?= (int)$document['id'] ?>">Print / Save PDF</a></div></article><?php endforeach; if (!$prescriptions): ?><div class="records-empty-state"><span class="records-empty-icon">Rx</span><h4>No prescriptions yet</h4><p>Use the form below to issue the first prescription.</p></div><?php endif; ?></div>
</section>
<section class="records-panel" id="doctor-records-results" role="tabpanel" data-record-panel="results" hidden><div class="records-panel-heading"><div><h3>Lab &amp; test results</h3><p>Documents uploaded by the patient.</p></div></div><div class="records-list"><?php $reports = array_values(array_filter($documents, fn($doc) => $doc['category'] !== 'prescription')); foreach ($reports as $document): ?><article class="medical-record-card"><span class="record-type-icon record-type-report" aria-hidden="true">↗</span><div class="medical-record-main"><div class="record-meta"><span class="record-category-pill"><?= htmlspecialchars(ucfirst($document['category'])) ?></span><time><?= htmlspecialchars(date('d M Y', strtotime($document['created_at']))) ?></time></div><h4><?= htmlspecialchars($document['title']) ?></h4><p><?= nl2br(htmlspecialchars($document['details'])) ?></p></div><div class="medical-record-actions"><a class="btn secondary small" target="_blank" href="../patient/download-record.php?id=<?= (int)$document['id'] ?>&amp;view=1">View report</a><a class="record-text-link" href="../patient/download-record.php?id=<?= (int)$document['id'] ?>">Download</a></div></article><?php endforeach; if (!$reports): ?><div class="records-empty-state"><h4>No reports uploaded</h4><p>Files uploaded by the patient will appear here.</p></div><?php endif; ?></div></section>
<section class="records-panel" id="doctor-records-history" role="tabpanel" data-record-panel="history" hidden>
    <div class="records-panel-heading"><div><h3>Medical history &amp; allergies</h3><p>Update the patient's key health history.</p></div></div>
    <form class="clinical-form history-editor" method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="patient_id" value="<?= $patientId ?>"><input type="hidden" name="record_action" value="medical_history"><label>Allergies<textarea name="allergies" rows="3" placeholder="Known allergies and reactions"><?= htmlspecialchars($history['allergies']) ?></textarea></label><label>Chronic conditions<textarea name="chronic_conditions" rows="3" placeholder="Ongoing conditions"><?= htmlspecialchars($history['chronic_conditions']) ?></textarea></label><label>Previous surgeries<textarea name="surgeries" rows="3" placeholder="Previous surgeries and dates"><?= htmlspecialchars($history['surgeries']) ?></textarea></label><button class="btn primary" type="submit">Save medical history</button></form>
</section>
<section class="records-panel" id="doctor-records-visits" role="tabpanel" data-record-panel="visits" hidden>
    <div class="records-panel-heading"><div><h3>Clinical notes &amp; visit summaries</h3><p>Document symptoms, assessment, recommendations, and vitals.</p></div></div>
    <div class="visit-note-list"><?php foreach ($notes as $note): ?><details class="visit-note"><summary><span><strong><?= htmlspecialchars($note['diagnosis'] ?: 'Visit summary') ?></strong><small><?= htmlspecialchars(date('d M Y', strtotime($note['created_at']))) ?></small></span><span class="visit-note-chevron">⌄</span></summary><div class="visit-note-content"><?php if ($note['symptoms']): ?><div><strong>Symptoms</strong><p><?= nl2br(htmlspecialchars($note['symptoms'])) ?></p></div><?php endif; ?><?php if ($note['recommendations']): ?><div><strong>Diagnosis &amp; recommendations</strong><p><?= nl2br(htmlspecialchars($note['recommendations'])) ?></p></div><?php endif; ?><div class="vitals-inline"><strong>Vitals</strong><?php if ($note['blood_pressure']): ?><span>Blood pressure: <?= htmlspecialchars($note['blood_pressure']) ?></span><?php endif; ?><?php if ($note['weight_kg'] !== null): ?><span>Weight: <?= htmlspecialchars((string)$note['weight_kg']) ?> kg</span><?php endif; ?><?php if ($note['glucose_mg_dl'] !== null): ?><span>Glucose: <?= htmlspecialchars((string)$note['glucose_mg_dl']) ?> mg/dL</span><?php endif; ?><?php if ($note['heart_rate'] !== null): ?><span>Heart rate: <?= (int)$note['heart_rate'] ?> bpm</span><?php endif; ?></div></div></details><?php endforeach; if (!$notes): ?><div class="records-empty-state"><h4>No visit notes yet</h4><p>Add a clinical note below after the consultation.</p></div><?php endif; ?></div>
</section>

<dialog class="clinical-modal" id="prescriptionModal"><form class="clinical-form" method="post"><button class="clinical-modal-close" type="button" aria-label="Close" data-close-clinical>×</button><div class="clinical-form-heading"><span class="record-type-icon record-type-prescription">Rx</span><div><h3>Issue new prescription</h3><p>This prescription will appear in the patient's account.</p></div></div><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="patient_id" value="<?= $patientId ?>"><input type="hidden" name="record_action" value="prescription"><label>Medicine name<input name="medicine" maxlength="190" required></label><label>Dosage<input name="dosage" placeholder="e.g. 1 tablet, twice daily" required></label><label>Instructions<textarea name="instructions" rows="3" placeholder="Duration, timing, and other directions"></textarea></label><button class="btn primary" type="submit">Issue prescription</button></form></dialog>
<dialog class="clinical-modal" id="clinicalNoteModal"><form class="clinical-form" method="post"><button class="clinical-modal-close" type="button" aria-label="Close" data-close-clinical>×</button><div class="clinical-form-heading"><span class="clinical-note-icon" aria-hidden="true">✚</span><div><h3>Add clinical note</h3><p>Save a visit summary and optional observations.</p></div></div><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="patient_id" value="<?= $patientId ?>"><input type="hidden" name="record_action" value="clinical_note"><label>Symptoms<textarea name="symptoms" rows="2"></textarea></label><label>Diagnosis<textarea name="diagnosis" rows="2"></textarea></label><label>Recommendations<textarea name="recommendations" rows="2"></textarea></label><div class="vitals-fields"><label>Blood pressure<input name="blood_pressure" placeholder="e.g. 120/80"></label><label>Weight (kg)<input type="number" name="weight_kg" min="1" max="499" step="0.01"></label><label>Glucose (mg/dL)<input type="number" name="glucose_mg_dl" min="1" max="2999" step="0.1"></label><label>Heart rate (bpm)<input type="number" name="heart_rate" min="1" max="399" step="1"></label></div><button class="btn primary" type="submit">Save clinical note</button></form></dialog>
<script>
(() => {
    const tabs = [...document.querySelectorAll('[data-record-tab]')];
    const panels = [...document.querySelectorAll('[data-record-panel]')];
    tabs.forEach(tab => tab.addEventListener('click', () => {
        tabs.forEach(item => { const active = item === tab; item.classList.toggle('is-active', active); item.setAttribute('aria-selected', String(active)); });
        panels.forEach(panel => { const active = panel.id === tab.getAttribute('aria-controls'); panel.hidden = !active; panel.classList.toggle('is-active', active); });
    }));
    document.querySelectorAll('[data-open-clinical]').forEach(button => button.addEventListener('click', () => {
        const dialog = document.getElementById(button.dataset.openClinical === 'prescription' ? 'prescriptionModal' : 'clinicalNoteModal');
        dialog.showModal();
    }));
    document.querySelectorAll('[data-close-clinical]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
    document.querySelectorAll('.clinical-modal').forEach(dialog => dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); }));
})();
</script>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
