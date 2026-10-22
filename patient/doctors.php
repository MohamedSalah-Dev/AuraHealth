<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('patient');
$pageTitle = 'Find Doctors';
$q = trim($_GET['q'] ?? '');
$specialty = trim($_GET['specialty'] ?? '');
$list = array_filter(doctors(), function ($d) use ($q, $specialty) {
    $text = strtolower($d['name'] . ' ' . $d['specialization'] . ' ' . $d['address']);
    return (!$q || str_contains($text, strtolower($q))) && (!$specialty || $d['specialization'] === $specialty);
});
$specialties = array_values(array_unique(array_map(fn($d) => $d['specialization'], doctors())));
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <form class="search-bar" method="get"><input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by doctor, specialty or location"><select name="specialty">
            <option value="">All specialties</option><?php foreach ($specialties as $s): ?><option value="<?= htmlspecialchars($s, ENT_QUOTES) ?>" <?= $specialty === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option><?php endforeach; ?>
        </select><button class="btn primary" type="submit">Search</button></form>
</div>
<div class="section-head">
    <div>
        <h3>Available doctors</h3>
        <p><?= count($list) ?> doctors found</p>
    </div>
</div>
<div class="grid grid-2">
    <?php foreach ($list as $d): ?>
        <div class="card doctor-card">
            <img class="doctor-avatar" src="<?= htmlspecialchars($d['image']) ?>" alt="">
            <div class="meta">
                <h3><?= htmlspecialchars($d['name']) ?></h3>
                <div class="specialty"><?= htmlspecialchars($d['specialization']) ?></div>
                <div class="address">⌖ <?= htmlspecialchars($d['address']) ?></div>
                <div class="actions"><a class="btn secondary small" href="doctor.php?id=<?= $d['id'] ?>">View profile</a><a class="btn primary small book-link" href="book.php?doctor_id=<?= $d['id'] ?>">Book now</a></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php if (!$list): ?><div class="card empty">No doctors match your search.</div><?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
