<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('patient');
$pageTitle = 'Dashboard';
$user = current_user();
$mine = array_values(array_filter($_SESSION['appointments'], fn($a) => (int)$a['user_id'] === (int)$user['id']));
$upcoming = array_values(array_filter($mine, fn($a) => $a['appointment_date'] >= date('Y-m-d') && $a['status'] !== 'cancelled'));
usort($upcoming, fn($a, $b) => [$a['appointment_date'], $a['appointment_time']] <=> [$b['appointment_date'], $b['appointment_time']]);
$pastVisits = count(array_filter($mine, fn($a) => $a['status'] === 'completed'));
$notifications = db_notifications_for_user((int)$user['id']);
$unread = count(array_filter($notifications, fn($n) => empty($n['is_read'])));
$recentActivity = $mine;
usort($recentActivity, fn($a, $b) => [$b['created_at'], $b['id']] <=> [$a['created_at'], $a['id']]);
$recommendedDoctors = array_slice(doctors(), 0, 3);
$articleCatalog = [
    [
        'id' => 'checkups', 'tag' => 'Clinic Guides', 'minutes' => 4, 'art' => 'checkup', 'specialty' => 'cardio',
        'title' => 'A practical guide to routine checkups',
        'summary' => 'Know what to bring and what to ask at your next visit.',
        'body' => 'Routine checkups are a chance to review your health, vaccinations, and screening needs with a clinician. The right tests and timing depend on your age, health history, and personal risk factors. Bring a list of medicines, questions, and relevant family health history.',
        'equipment' => 'Your clinician may recommend an examination or specific screening tests after reviewing your history. Tests vary by person and should be selected with a qualified clinician.',
        'source' => 'https://www.cdc.gov/chronic-disease/prevention/preventive-care.html',
    ],
    [
        'id' => 'dental-clinic-guide', 'tag' => 'Clinic Guides', 'minutes' => 5, 'art' => 'dental', 'specialty' => 'dent',
        'title' => 'Digital tools you may see at a dental clinic',
        'summary' => 'A quick introduction to imaging and digital scans.',
        'body' => 'Digital imaging and intraoral scanning can help dental teams assess specific conditions and plan care. These tools support the dentist’s examination; the choice of technology depends on the clinical need and the clinic’s available equipment.',
        'equipment' => 'Examples include digital radiography, intraoral scanners, and 3D printing workflows. Ask the clinic which tools are available and appropriate for your visit.',
        'source' => 'https://www.ada.org/resources/practice/practice-management/digital-dentistry-and-technology/',
    ],
    [
        'id' => 'healthy-recipe', 'tag' => 'Health Recipes', 'minutes' => 3, 'art' => 'wellbeing', 'specialty' => 'nutrition',
        'title' => 'Quick fruit, yogurt & oats bowl',
        'summary' => 'A simple breakfast idea with flexible ingredients.',
        'body' => "Ingredients: plain yogurt, a handful of oats, seasonal fruit, and a small spoonful of chopped nuts or seeds if suitable for you.\n\nMethod: add the yogurt to a bowl, top with oats and sliced fruit, then finish with nuts or seeds. Choose ingredients that fit your preferences, allergies, and clinician's advice.",
        'equipment' => 'Preparation: a bowl, spoon, and knife. This is a general food idea, not a treatment or a special immune-boosting remedy. Adapt it to your dietary needs.',
        'source' => 'https://www.who.int/news-room/fact-sheets/detail/healthy-diet',
    ],
    [
        'id' => 'daily-wellness', 'tag' => 'Daily Wellness Habits', 'minutes' => 3, 'art' => 'checkup', 'specialty' => 'nutrition',
        'title' => 'One small habit: add variety to a meal',
        'summary' => 'Try including a different fruit, vegetable, or whole grain.',
        'body' => 'A varied eating pattern is one practical way to work toward balanced meals. Pick one change that feels manageable today, such as adding a fruit or vegetable you enjoy. Needs differ from person to person, so ask your clinician for guidance if you have a health condition or dietary restrictions.',
        'equipment' => 'Try it today: choose one meal and add a food from a different group. Keep the habit realistic and suited to your needs.',
        'source' => 'https://www.who.int/news-room/fact-sheets/detail/healthy-diet',
    ],
];
$allDoctors = doctors();
foreach ($articleCatalog as &$article) {
    $matchedDoctor = null;
    foreach ($allDoctors as $availableDoctor) {
        $specialization = strtolower($availableDoctor['specialization']);
        if (($article['specialty'] === 'cardio' && str_contains($specialization, 'cardio'))
            || ($article['specialty'] === 'dent' && str_contains($specialization, 'dent'))
            || ($article['specialty'] === 'nutrition' && (str_contains($specialization, 'nutrition') || str_contains($specialization, 'diet')))) {
            $matchedDoctor = $availableDoctor;
            break;
        }
    }
    $article['doctor'] = $matchedDoctor;
}
unset($article);
include __DIR__ . '/../includes/header.php';
?>
<section class="hero dashboard-hero scroll-reveal">
    <div class="hero-copy">
        <span class="eyebrow" style="color:#9de4d9">YOUR HEALTH, WELL ORGANIZED</span>
        <h2>Hello, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?> 👋</h2>
        <p>Stay on top of your care, find the right specialist, and keep every visit in one place.</p>
        <div class="hero-actions"><a class="btn hero-cta" href="doctors.php">Explore doctors <span aria-hidden="true">&rarr;</span></a></div>
    </div>
    <aside class="health-tip" id="heroTipSwitcher" aria-live="polite">
        <span class="tip-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 21s-8-4.4-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 6.6-8 11-8 11Z"/><path d="M7 12h3l1.2-2.2L13 15l1.4-3H17"/></svg></span>
        <span class="tip-label">QUICK HEALTH TIP <span class="tip-count" id="tipCount">1 / 3</span></span>
        <strong id="tipTitle">Keep a note of questions for your next checkup.</strong>
        <span class="tip-copy" id="tipCopy">Your clinician can help decide which preventive services fit your health history.</span>
        <div class="tip-controls"><button type="button" data-tip-prev aria-label="Previous health tip">&#8249;</button><button type="button" data-tip-next aria-label="Next health tip">&#8250;</button></div>
    </aside>
</section>

<div class="grid grid-3 dashboard-stats">
    <a class="card stat stat-card" href="appointments.php">
        <div><span>Total visits</span><strong><?= $pastVisits ?></strong><small>Completed appointments</small></div>
        <div class="stat-icon stat-icon-olive" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h3"/></svg></div>
        <span class="stat-link">View history <span aria-hidden="true">&rarr;</span></span>
    </a>
    <a class="card stat stat-card" href="appointments.php">
        <div><span>Upcoming appointments</span><strong><?= count($upcoming) ?></strong><small><?= $upcoming ? 'Next: ' . date('d M', strtotime($upcoming[0]['appointment_date'])) : 'No visit currently scheduled' ?></small></div>
        <div class="stat-icon stat-icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
        <span class="stat-link">See appointments <span aria-hidden="true">&rarr;</span></span>
    </a>
    <a class="card stat stat-card" href="records.php">
        <div><span>Medical records</span><strong>0</strong><small>No reports added yet</small></div>
        <div class="stat-icon stat-icon-mint" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h6"/></svg></div>
        <span class="stat-link">Open records <span aria-hidden="true">&rarr;</span></span>
    </a>
</div>

<div class="section-head">
    <div><h3>Upcoming appointments</h3><p>Your next scheduled visits.</p></div>
    <a class="btn secondary small" href="appointments.php">View all</a>
</div>
<div class="card dashboard-appointments scroll-reveal">
    <?php if (!$upcoming): ?>
        <div class="empty-state">
            <div class="empty-art" aria-hidden="true"><svg viewBox="0 0 120 100" fill="none"><rect x="20" y="22" width="72" height="63" rx="12" fill="#E6F5F3" stroke="#9BD8CE" stroke-width="2"/><path d="M38 17v13M74 17v13M20 39h72" stroke="#157A78" stroke-width="4" stroke-linecap="round"/><path d="M43 59h26M56 46v26" stroke="#62BFAF" stroke-width="4" stroke-linecap="round"/><circle cx="91" cy="70" r="17" fill="#fff" stroke="#D9ECE8" stroke-width="3"/><path d="M84 70h14M91 63v14" stroke="#157A78" stroke-width="3" stroke-linecap="round"/></svg></div>
            <h3>No upcoming visits scheduled</h3>
            <p>Take control of your health today by finding a doctor who fits your needs.</p>
            <a class="btn primary empty-cta" href="doctors.php"><span aria-hidden="true">+</span> Book new appointment</a>
        </div>
    <?php else: foreach (array_slice($upcoming, 0, 4) as $a): $d = doctor_by_id((int)$a['doctor_id']); ?>
        <div class="appointment-row">
            <div class="patient-mini"><div class="date-box"><strong><?= date('d', strtotime($a['appointment_date'])) ?></strong><span><?= date('M', strtotime($a['appointment_date'])) ?></span></div><div><strong><?= htmlspecialchars($d['name'] ?? 'Doctor') ?></strong><div class="muted"><?= htmlspecialchars($d['specialization'] ?? '') ?></div></div></div>
            <div><?= date('l', strtotime($a['appointment_date'])) ?></div><div><?= date('g:i A', strtotime($a['appointment_time'])) ?></div><div><span class="badge <?= status_class($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></div><div><a class="btn secondary small" href="ticket.php?id=<?= (int)$a['id'] ?>">Ticket</a></div>
        </div>
    <?php endforeach; endif; ?>
</div>

<div class="section-head"><div><h3>Recommended doctors</h3><p>Explore clinicians currently available on AuraHealth.</p></div><a class="btn secondary small" href="doctors.php">Browse all</a></div>
<div class="grid grid-3 recommended-grid">
    <?php foreach ($recommendedDoctors as $d): ?>
        <article class="recommended-card">
            <?php if (!empty($d['image'])): ?><img class="doctor-avatar" src="<?= htmlspecialchars($d['image']) ?>" alt="Portrait of <?= htmlspecialchars($d['name']) ?>"><?php else: ?><div class="doctor-avatar doctor-avatar-placeholder" aria-hidden="true"><?= default_avatar_icon() ?></div><?php endif; ?>
            <div class="recommended-info"><h3><?= htmlspecialchars($d['name']) ?></h3><span><?= htmlspecialchars($d['specialization']) ?></span><p><?= htmlspecialchars($d['address']) ?></p><a class="btn primary small book-link" href="book.php?doctor_id=<?= (int)$d['id'] ?>">Book</a></div>
        </article>
    <?php endforeach; ?>
    <?php if (!$recommendedDoctors): ?><div class="card empty">Doctor profiles will appear here when the clinic adds them.</div><?php endif; ?>
</div>

<div class="section-head article-section-heading"><div><span class="eyebrow">THE AURAHEALTH JOURNAL</span><h3>Health recipes, clinic guides &amp; daily habits</h3><p>Small, useful reads for everyday care.</p></div></div>
<div class="article-filters" role="group" aria-label="Filter articles by topic">
    <button type="button" class="article-filter is-active" data-article-filter="all" aria-pressed="true">All topics</button>
    <button type="button" class="article-filter" data-article-filter="Health Recipes" aria-pressed="false">Health recipes</button>
    <button type="button" class="article-filter" data-article-filter="Clinic Guides" aria-pressed="false">Clinic guides</button>
    <button type="button" class="article-filter" data-article-filter="Daily Wellness Habits" aria-pressed="false">Daily wellness</button>
</div>
<div class="article-grid" aria-label="Medical tips and clinic articles">
    <?php foreach ($articleCatalog as $article): ?>
        <div class="article-cell">
        <article class="article-card" data-category="<?= htmlspecialchars($article['tag'], ENT_QUOTES) ?>">
            <div class="article-art article-art-<?= htmlspecialchars($article['art']) ?>" aria-hidden="true">
                <?php if ($article['art'] === 'checkup'): ?><svg viewBox="0 0 320 180"><circle cx="260" cy="35" r="65" fill="#DBEAFE" opacity=".65"/><rect x="104" y="28" width="118" height="127" rx="18" fill="#fff" opacity=".94"/><path d="M139 22v18M187 22v18" stroke="#0284C7" stroke-width="8" stroke-linecap="round"/><path d="M104 62h118" stroke="#93C5FD" stroke-width="5"/><path d="M133 91h57M133 111h36" stroke="#38BDF8" stroke-width="7" stroke-linecap="round"/><circle cx="226" cy="121" r="36" fill="#EFF6FF"/><path d="m209 121 12 12 22-25" fill="none" stroke="#0284C7" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?php elseif ($article['art'] === 'dental'): ?><svg viewBox="0 0 320 180"><circle cx="50" cy="35" r="72" fill="#C9E6F0" opacity=".7"/><path d="M130 35c-32 0-42 23-36 55l15 59c4 16 17 18 23 1l13-34c4-11 13-11 17 0l13 34c6 17 19 15 23-1l15-59c6-32-4-55-36-55-13 0-22 7-32 7s-19-7-32-7Z" fill="#fff" stroke="#7DD3FC" stroke-width="6"/><path d="M107 63c14-10 25-8 39-3" stroke="#BAE6FD" stroke-width="7" stroke-linecap="round"/><circle cx="229" cy="112" r="39" fill="#EFF6FF"/><path d="M229 87v50M204 112h50" stroke="#0284C7" stroke-width="8" stroke-linecap="round"/></svg>
                <?php else: ?><svg viewBox="0 0 320 180"><circle cx="269" cy="38" r="68" fill="#DBEAFE" opacity=".8"/><path d="M160 145c-47-27-66-57-48-80 15-20 39-14 48 3 9-17 33-23 48-3 18 23-1 53-48 80Z" fill="#fff" stroke="#93C5FD" stroke-width="6"/><path d="M160 120V86M143 103h34" stroke="#0284C7" stroke-width="8" stroke-linecap="round"/><circle cx="80" cy="125" r="21" fill="#F4D888"/><path d="M80 92v-9M80 167v-9M47 125h-9M122 125h-9M57 102l-7-7M110 155l-7-7M103 102l7-7M50 155l7-7" stroke="#D5A93A" stroke-width="5" stroke-linecap="round"/></svg><?php endif; ?>
            </div>
            <div class="article-copy"><div class="article-meta"><span class="article-tag"><?= htmlspecialchars($article['tag']) ?></span><span><?= (int)$article['minutes'] ?> min read</span></div><h4><?= htmlspecialchars($article['title']) ?></h4><p><?= htmlspecialchars($article['summary']) ?></p><button class="article-open" type="button" data-article-open data-title="<?= htmlspecialchars($article['title'], ENT_QUOTES) ?>" data-tag="<?= htmlspecialchars($article['tag'], ENT_QUOTES) ?>" data-minutes="<?= (int)$article['minutes'] ?>" data-body="<?= htmlspecialchars($article['body'], ENT_QUOTES) ?>" data-equipment="<?= htmlspecialchars($article['equipment'], ENT_QUOTES) ?>" data-doctor="<?= htmlspecialchars($article['doctor']['name'] ?? '', ENT_QUOTES) ?>" data-book-url="<?= $article['doctor'] ? 'book.php?doctor_id=' . (int)$article['doctor']['id'] : 'doctors.php' ?>" data-source="<?= htmlspecialchars($article['source'], ENT_QUOTES) ?>"><?= $article['doctor'] ? 'Read article &amp; book a visit' : 'Read article &amp; explore doctors' ?> <span aria-hidden="true">&rarr;</span></button></div>
        </article>
        </div>
    <?php endforeach; ?>
</div>

<div class="section-head"><div><h3>Recent activity</h3><p>Updates from your appointments.</p></div><a class="btn secondary small" href="notifications.php">Notifications<?= $unread ? ' · ' . $unread : '' ?></a></div>
<div class="card activity-list">
    <?php foreach (array_slice($recentActivity, 0, 4) as $a): $d = doctor_by_id((int)$a['doctor_id']); ?>
        <div class="activity-item"><span class="activity-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 7 10 17l-5-5"/></svg></span><div><strong><?= $a['status'] === 'confirmed' ? 'Appointment confirmed' : ($a['status'] === 'cancelled' ? 'Appointment cancelled' : ($a['status'] === 'completed' ? 'Visit completed' : 'Appointment requested')) ?></strong><p><?= htmlspecialchars($d['name'] ?? 'Doctor') ?> · <?= htmlspecialchars($d['specialization'] ?? '') ?></p></div><time><?= htmlspecialchars(date('d M Y', strtotime($a['created_at']))) ?></time></div>
    <?php endforeach; ?>
    <?php if (!$recentActivity): ?><div class="activity-empty">Your appointment updates will appear here.</div><?php endif; ?>
</div>

<div class="article-drawer-backdrop" data-drawer-close hidden></div>
<aside class="article-drawer" id="articleDrawer" role="dialog" aria-modal="true" aria-labelledby="drawerTitle" aria-hidden="true" inert>
    <button class="drawer-close" type="button" data-drawer-close aria-label="Close article">×</button>
    <span class="article-tag" id="drawerTag"></span><span class="drawer-reading" id="drawerReading"></span>
    <h2 id="drawerTitle"></h2><p class="drawer-body" id="drawerBody"></p>
    <section class="drawer-equipment"><h3>Practical details</h3><p id="drawerEquipment"></p></section>
    <a class="btn primary drawer-book" id="drawerBook" href="doctors.php">Find a doctor</a>
    <a class="drawer-source" id="drawerSource" href="#" target="_blank" rel="noopener noreferrer">Read the source</a>
</aside>
<script>
(() => {
    const drawer = document.getElementById('articleDrawer');
    const backdrop = document.querySelector('.article-drawer-backdrop');
    const closeButton = drawer.querySelector('.drawer-close');
    const fields = {
        title: document.getElementById('drawerTitle'), tag: document.getElementById('drawerTag'),
        reading: document.getElementById('drawerReading'), body: document.getElementById('drawerBody'),
        equipment: document.getElementById('drawerEquipment'), book: document.getElementById('drawerBook'),
        source: document.getElementById('drawerSource')
    };
    let previousFocus;
    const tipTitle = document.getElementById('tipTitle');
    const tipCopy = document.getElementById('tipCopy');
    const tipCount = document.getElementById('tipCount');
    const tipSwitcher = document.getElementById('heroTipSwitcher');
    const healthTips = [
        { title: 'Keep a note of questions for your next checkup.', copy: 'Your clinician can help decide which preventive services fit your health history.' },
        { title: 'Choose variety when planning a meal.', copy: 'Try adding a fruit, vegetable, or whole grain that you enjoy.' },
        { title: 'Ask before a dental scan or image.', copy: 'Your dentist can explain why a digital tool is useful for your specific visit.' }
    ];
    let tipIndex = 0;
    let tipTimer;
    const showTip = index => {
        tipIndex = (index + healthTips.length) % healthTips.length;
        tipTitle.textContent = healthTips[tipIndex].title;
        tipCopy.textContent = healthTips[tipIndex].copy;
        tipCount.textContent = `${tipIndex + 1} / ${healthTips.length}`;
    };
    const stopTipRotation = () => window.clearInterval(tipTimer);
    const startTipRotation = () => {
        stopTipRotation();
        if (!document.hidden) tipTimer = window.setInterval(() => showTip(tipIndex + 1), 7000);
    };
    tipSwitcher.querySelector('[data-tip-prev]').addEventListener('click', () => { showTip(tipIndex - 1); startTipRotation(); });
    tipSwitcher.querySelector('[data-tip-next]').addEventListener('click', () => { showTip(tipIndex + 1); startTipRotation(); });
    tipSwitcher.addEventListener('mouseenter', stopTipRotation);
    tipSwitcher.addEventListener('mouseleave', startTipRotation);
    tipSwitcher.addEventListener('focusin', stopTipRotation);
    tipSwitcher.addEventListener('focusout', event => { if (!tipSwitcher.contains(event.relatedTarget)) startTipRotation(); });
    document.addEventListener('visibilitychange', () => document.hidden ? stopTipRotation() : startTipRotation());
    startTipRotation();
    const closeDrawer = () => {
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        drawer.inert = true;
        backdrop.hidden = true;
        document.body.classList.remove('article-drawer-open');
        if (previousFocus) previousFocus.focus();
    };
    document.querySelectorAll('[data-article-open]').forEach(button => button.addEventListener('click', () => {
        previousFocus = button;
        fields.title.textContent = button.dataset.title;
        fields.tag.textContent = button.dataset.tag;
        fields.reading.textContent = `${button.dataset.minutes} min read`;
        fields.body.textContent = button.dataset.body;
        fields.equipment.textContent = button.dataset.equipment;
        fields.book.href = button.dataset.bookUrl;
        fields.book.textContent = button.dataset.doctor ? `Book with ${button.dataset.doctor}` : 'Explore available doctors';
        fields.source.href = button.dataset.source;
        drawer.inert = false;
        drawer.setAttribute('aria-hidden', 'false');
        backdrop.hidden = false;
        document.body.classList.add('article-drawer-open');
        requestAnimationFrame(() => drawer.classList.add('is-open'));
        closeButton.focus();
    }));
    document.querySelectorAll('[data-drawer-close]').forEach(button => button.addEventListener('click', closeDrawer));
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && drawer.classList.contains('is-open')) closeDrawer(); });

    const articleCards = [...document.querySelectorAll('.article-card')];
    document.querySelectorAll('[data-article-filter]').forEach(filter => filter.addEventListener('click', () => {
        document.querySelectorAll('[data-article-filter]').forEach(button => {
            const active = button === filter;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        });
        articleCards.forEach(card => {
            card.hidden = filter.dataset.articleFilter !== 'all' && card.dataset.category !== filter.dataset.articleFilter;
        });
    }));

    const revealItems = document.querySelectorAll('.patient-dashboard .dashboard-hero, .patient-dashboard .dashboard-stats > *, .patient-dashboard .section-head, .patient-dashboard .dashboard-appointments, .patient-dashboard .recommended-grid > *, .patient-dashboard .article-grid .article-card, .patient-dashboard .activity-list');
    if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        revealItems.forEach((item, index) => {
            item.classList.add('scroll-reveal-item');
            const cardIndex = articleCards.indexOf(item);
            if (cardIndex >= 0) {
                item.classList.add('from-right');
                item.style.setProperty('--reveal-delay', `${cardIndex * 110}ms`);
            } else {
                item.classList.add(index % 2 ? 'from-left' : 'from-right');
            }
        });
        document.documentElement.classList.add('scroll-reveal-ready');
        const revealObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -35px 0px' });
        revealItems.forEach(item => revealObserver.observe(item));
    }
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
