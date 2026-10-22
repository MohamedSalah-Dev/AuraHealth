<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('patient');
$pageTitle = 'Medical Records';
$user = current_user();
if (empty($_SESSION['medical_records_csrf'])) $_SESSION['medical_records_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['medical_records_csrf'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!in_array($_POST['category'] ?? '', ['lab', 'report', 'other'], true)) {
        $error = 'Choose a valid document category.';
    } elseif (trim($_POST['title'] ?? '') === '') {
        $error = 'Enter a name for this document.';
    } elseif (!isset($_FILES['medical_file']) || $_FILES['medical_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Choose a file to upload.';
    } elseif ($_FILES['medical_file']['size'] > 8 * 1024 * 1024) {
        $error = 'The file must be 8 MB or smaller.';
    } else {
        $tmpFile = $_FILES['medical_file']['tmp_name'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpFile);
        $allowedTypes = ['application/pdf'=>'pdf', 'image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
        if (!isset($allowedTypes[$mime]) || !is_uploaded_file($tmpFile)) {
            $error = 'Upload a PDF, JPG, PNG, or WEBP file.';
        } else {
            $uploadDir = __DIR__ . '/../uploads/medical-records';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0750, true);
            $fileName = bin2hex(random_bytes(20)) . '.' . $allowedTypes[$mime];
            if (move_uploaded_file($tmpFile, $uploadDir . '/' . $fileName)) {
                db_create_medical_document([
                    'patient_id'=>(int)$user['id'], 'doctor_id'=>null, 'appointment_id'=>null,
                    'category'=>$_POST['category'], 'title'=>mb_substr(trim($_POST['title']), 0, 190),
                    'details'=>trim($_POST['details'] ?? ''), 'file_path'=>'uploads/medical-records/' . $fileName,
                ]);
                header('Location: records.php?uploaded=1');
                exit;
            }
            $error = 'The file could not be saved. Please try again.';
        }
    }
}

$documents = db_medical_documents_for_patient((int)$user['id']);
$notes = db_clinical_notes_for_patient((int)$user['id']);
$history = db_patient_medical_history((int)$user['id']) ?? ['allergies'=>'', 'chronic_conditions'=>'', 'surgeries'=>'', 'updated_at'=>null];
$patientAppointments = array_filter($_SESSION['appointments'], fn($appointment) => (int)$appointment['user_id'] === (int)$user['id']);
include __DIR__ . '/../includes/header.php';
?>
<section class="records-heading">
    <div><span class="eyebrow">YOUR CARE, IN ONE PLACE</span><h2>Your medical records</h2><p>Prescriptions, test results, medical history, and visit notes.</p></div>
    <a class="btn primary" href="#upload-record">+ Upload medical document</a>
</section>
<?php if ($error): ?><div class="alert danger records-alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (isset($_GET['uploaded'])): ?><div class="alert success records-alert">Your document was uploaded successfully.</div><?php endif; ?>

<form class="records-toolbar" id="upload-record" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <div class="records-upload-title"><strong>Upload a medical document</strong><span>PDF, JPG, PNG, or WEBP · up to 8 MB</span></div>
    <label>Document name<input type="text" name="title" maxlength="190" placeholder="e.g. Blood test — October 2026" required></label>
    <label>Category<select name="category" required><option value="lab">Lab &amp; test result</option><option value="report">Medical report</option><option value="other">Other document</option></select></label>
    <label class="records-file-label">Choose file<input type="file" name="medical_file" accept="application/pdf,image/jpeg,image/png,image/webp" required></label>
    <label class="records-notes-field">Notes (optional)<input type="text" name="details" maxlength="1000" placeholder="Add a short note"></label>
    <button class="btn primary" type="submit" name="upload_document" value="1">Upload document</button>
</form>

<div class="records-tabs" role="tablist" aria-label="Medical record sections">
    <button class="records-tab is-active" type="button" role="tab" aria-selected="true" aria-controls="records-prescriptions" data-record-tab="prescriptions">Prescriptions <span><?= count(array_filter($documents, fn($doc) => $doc['category'] === 'prescription')) ?></span></button>
    <button class="records-tab" type="button" role="tab" aria-selected="false" aria-controls="records-results" data-record-tab="results">Labs &amp; reports <span><?= count(array_filter($documents, fn($doc) => in_array($doc['category'], ['lab','report','other'], true))) ?></span></button>
    <button class="records-tab" type="button" role="tab" aria-selected="false" aria-controls="records-history" data-record-tab="history">Medical history</button>
    <button class="records-tab" type="button" role="tab" aria-selected="false" aria-controls="records-visits" data-record-tab="visits">Visit summaries <span><?= count($notes) ?></span></button>
</div>

<section class="records-panel is-active" id="records-prescriptions" role="tabpanel" data-record-panel="prescriptions">
    <div class="records-panel-heading"><div><h3>Digital prescriptions</h3><p>Prescriptions issued by your clinicians.</p></div></div>
    <div class="records-list">
    <?php $prescriptions = array_values(array_filter($documents, fn($doc) => $doc['category'] === 'prescription')); foreach ($prescriptions as $document): ?>
        <article class="medical-record-card" data-record-category="prescription">
            <span class="record-type-icon record-type-prescription" aria-hidden="true">Rx</span>
            <div class="medical-record-main"><div class="record-meta"><span class="record-category-pill">Prescription</span><time><?= htmlspecialchars(date('d M Y', strtotime($document['created_at']))) ?></time></div><h4><?= htmlspecialchars($document['title']) ?></h4><p><?= nl2br(htmlspecialchars($document['details'])) ?></p><small><?= htmlspecialchars($document['doctor_name'] ?: 'AuraHealth clinician') ?></small></div>
            <div class="medical-record-actions"><a class="btn secondary small" target="_blank" href="print-record.php?id=<?= (int)$document['id'] ?>">Print / Save PDF</a><?php if ($document['file_path']): ?><a class="record-text-link" href="download-record.php?id=<?= (int)$document['id'] ?>">Download file</a><?php endif; ?></div>
        </article>
    <?php endforeach; if (!$prescriptions): ?><div class="records-empty-state"><span class="records-empty-icon" aria-hidden="true">Rx</span><h4>No prescriptions yet</h4><p>Prescriptions issued by your doctor will appear here.</p></div><?php endif; ?>
    </div>
</section>

<section class="records-panel" id="records-results" role="tabpanel" data-record-panel="results" hidden>
    <div class="records-panel-heading"><div><h3>Lab &amp; test results</h3><p>Reports and documents uploaded to your account.</p></div><label class="records-filter">Filter by category<select id="recordCategoryFilter"><option value="all">All documents</option><option value="lab">Lab results</option><option value="report">Medical reports</option><option value="other">Other</option></select></label></div>
    <div class="records-list" id="medicalDocumentList">
    <?php $reports = array_values(array_filter($documents, fn($doc) => in_array($doc['category'], ['lab','report','other'], true))); foreach ($reports as $document): ?>
        <article class="medical-record-card" data-record-category="<?= htmlspecialchars($document['category']) ?>">
            <span class="record-type-icon record-type-report" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8M8 17h6"/></svg></span>
            <div class="medical-record-main"><div class="record-meta"><span class="record-category-pill"><?= htmlspecialchars(match ($document['category']) {'lab'=>'Lab result','report'=>'Medical report',default=>'Other document'}) ?></span><time><?= htmlspecialchars(date('d M Y', strtotime($document['created_at']))) ?></time></div><h4><?= htmlspecialchars($document['title']) ?></h4><?php if ($document['details']): ?><p><?= nl2br(htmlspecialchars($document['details'])) ?></p><?php endif; ?></div>
            <div class="medical-record-actions"><a class="btn secondary small" target="_blank" href="download-record.php?id=<?= (int)$document['id'] ?>&amp;view=1">View report</a><a class="record-text-link" href="download-record.php?id=<?= (int)$document['id'] ?>">Download</a></div>
        </article>
    <?php endforeach; if (!$reports): ?><div class="records-empty-state"><span class="records-empty-icon" aria-hidden="true">↗</span><h4>No test results or reports yet</h4><p>Upload a document above to keep your medical records together.</p></div><?php endif; ?>
    </div>
</section>

<section class="records-panel" id="records-history" role="tabpanel" data-record-panel="history" hidden>
    <div class="records-panel-heading"><div><h3>Medical history &amp; allergies</h3><p>Information recorded by your care team.</p></div></div>
    <div class="history-grid">
        <article class="history-item"><span class="history-symbol">!</span><div><h4>Allergies</h4><p><?= $history['allergies'] !== '' ? nl2br(htmlspecialchars($history['allergies'])) : 'No allergy information recorded.' ?></p></div></article>
        <article class="history-item"><span class="history-symbol">+</span><div><h4>Chronic conditions</h4><p><?= $history['chronic_conditions'] !== '' ? nl2br(htmlspecialchars($history['chronic_conditions'])) : 'No chronic conditions recorded.' ?></p></div></article>
        <article class="history-item"><span class="history-symbol">↗</span><div><h4>Previous surgeries</h4><p><?= $history['surgeries'] !== '' ? nl2br(htmlspecialchars($history['surgeries'])) : 'No previous surgeries recorded.' ?></p></div></article>
    </div>
    <?php if ($history['updated_at']): ?><p class="records-updated-note">Last updated <?= htmlspecialchars(date('d M Y', strtotime($history['updated_at']))) ?> by your care team.</p><?php endif; ?>
</section>

<section class="records-panel" id="records-visits" role="tabpanel" data-record-panel="visits" hidden>
    <div class="records-panel-heading"><div><h3>Clinical notes &amp; visit summaries</h3><p>Diagnoses, recommendations, and observations from your visits.</p></div></div>
    <div class="visit-note-list">
    <?php foreach ($notes as $note): ?><details class="visit-note"><summary><span><strong><?= htmlspecialchars($note['diagnosis'] ?: 'Visit summary') ?></strong><small><?= htmlspecialchars($note['doctor_name']) ?></small></span><time><?= htmlspecialchars(date('d M Y', strtotime($note['created_at']))) ?></time><span class="visit-note-chevron" aria-hidden="true">⌄</span></summary><div class="visit-note-content">
        <?php if ($note['symptoms']): ?><div><strong>Symptoms</strong><p><?= nl2br(htmlspecialchars($note['symptoms'])) ?></p></div><?php endif; ?>
        <?php if ($note['recommendations']): ?><div><strong>Diagnosis &amp; recommendations</strong><p><?= nl2br(htmlspecialchars($note['recommendations'])) ?></p></div><?php endif; ?>
        <div class="vitals-inline"><strong>Vitals</strong><?php if ($note['blood_pressure']): ?><span>Blood pressure: <?= htmlspecialchars($note['blood_pressure']) ?></span><?php endif; ?><?php if ($note['weight_kg'] !== null): ?><span>Weight: <?= htmlspecialchars((string)$note['weight_kg']) ?> kg</span><?php endif; ?><?php if ($note['glucose_mg_dl'] !== null): ?><span>Glucose: <?= htmlspecialchars((string)$note['glucose_mg_dl']) ?> mg/dL</span><?php endif; ?><?php if ($note['heart_rate'] !== null): ?><span>Heart rate: <?= (int)$note['heart_rate'] ?> bpm</span><?php endif; ?></div>
    </div></details><?php endforeach; if (!$notes): ?><div class="records-empty-state"><span class="records-empty-icon" aria-hidden="true">✚</span><h4>No visit summaries yet</h4><p>Notes added by your doctor will appear here after your visits.</p></div><?php endif; ?>
    </div>
</section>
<script>
(() => {
    const tabs = [...document.querySelectorAll('[data-record-tab]')];
    const panels = [...document.querySelectorAll('[data-record-panel]')];
    tabs.forEach(tab => tab.addEventListener('click', () => {
        tabs.forEach(item => { const active = item === tab; item.classList.toggle('is-active', active); item.setAttribute('aria-selected', String(active)); });
        panels.forEach(panel => { const active = panel.dataset.recordPanel === tab.dataset.recordTab; panel.hidden = !active; panel.classList.toggle('is-active', active); });
    }));
    const categoryFilter = document.getElementById('recordCategoryFilter');
    categoryFilter?.addEventListener('change', () => document.querySelectorAll('#medicalDocumentList [data-record-category]').forEach(card => { card.hidden = categoryFilter.value !== 'all' && card.dataset.recordCategory !== categoryFilter.value; }));
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
