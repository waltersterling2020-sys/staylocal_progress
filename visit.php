<?php
require_once __DIR__ . '/config/database.php';

$unitId = filter_input(INPUT_GET, 'unit_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'unit_id', FILTER_VALIDATE_INT);
$selectedDate = trim((string)($_GET['date'] ?? $_POST['visit_date'] ?? ''));
$unit = null;
$error = null;
$success = null;
$dbError = null;
$timeSlots = ['10:00:00' => '10:00 AM', '11:30:00' => '11:30 AM', '13:00:00' => '1:00 PM', '14:30:00' => '2:30 PM', '16:00:00' => '4:00 PM'];
$bookedTimes = [];

$today = new DateTimeImmutable('today');
if (!$selectedDate || DateTimeImmutable::createFromFormat('Y-m-d', $selectedDate) === false) {
    $selectedDate = $today->format('Y-m-d');
}
$selectedDateObject = DateTimeImmutable::createFromFormat('Y-m-d', $selectedDate);
if (!$selectedDateObject || $selectedDateObject < $today || (int)$selectedDateObject->format('w') === 0) {
    $selectedDate = $today->format('Y-m-d');
    if ((int)$today->format('w') === 0) {
        $selectedDate = $today->modify('+1 day')->format('Y-m-d');
    }
}

$calendarDays = [];
for ($i = 0; $i < 21; $i++) {
    $day = $today->modify('+' . $i . ' days');
    if ((int)$day->format('w') !== 0) {
        $calendarDays[] = $day;
    }
}

try {
    $pdo = db();
    $unitStatement = $pdo->prepare('SELECT u.*, a.name AS apartment_name, a.area FROM units u JOIN apartments a ON a.id = u.apartment_id WHERE u.id = :id LIMIT 1');
    $unitStatement->execute(['id' => $unitId ?: 0]);
    $unit = $unitStatement->fetch();

    if ($unit) {
        $bookedStatement = $pdo->prepare("SELECT TIME_FORMAT(visit_time, '%H:%i:%s') AS visit_time FROM visits WHERE visit_date = :visit_date AND status = 'scheduled'");
        $bookedStatement->execute(['visit_date' => $selectedDate]);
        $bookedTimes = array_column($bookedStatement->fetchAll(), 'visit_time');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $visitTime = trim((string)($_POST['visit_time'] ?? ''));
            $notes = trim((string)($_POST['notes'] ?? ''));

            if (!$fullName || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$phone) {
                $error = 'Please provide your full name, valid email address, and phone number.';
            } elseif (!array_key_exists($visitTime, $timeSlots)) {
                $error = 'Please choose one of the available visit times.';
            } elseif (in_array($visitTime, $bookedTimes, true)) {
                $error = 'That appointment time was just taken. Please choose another slot.';
            } else {
                try {
                    $insert = $pdo->prepare("INSERT INTO visits (unit_id, full_name, email, phone, visit_date, visit_time, notes, status) VALUES (:unit_id, :full_name, :email, :phone, :visit_date, :visit_time, :notes, 'scheduled')");
                    $insert->execute([
                        'unit_id' => $unit['id'],
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone' => $phone,
                        'visit_date' => $selectedDate,
                        'visit_time' => $visitTime,
                        'notes' => $notes ?: null,
                    ]);
                    $success = 'Your in-person visit is scheduled. We will confirm the details by email.';
                } catch (PDOException $exception) {
                    $error = 'That time was just booked by someone else. Please choose another slot.';
                }
            }
        }
    }
} catch (Throwable $exception) {
    $dbError = 'The calendar needs the MySQL connection and updated SQL schema. Import sql/staylocal.sql in XAMPP, then try again.';
}

$pageTitle = 'Schedule an in-person visit';
$currentPage = 'visit';
require __DIR__ . '/partials/header.php';
?>
<div class="page-shell visit-page">
    <?php if ($dbError): ?>
        <div class="database-notice" role="status"><strong>Connect your local directory.</strong><span><?= e($dbError) ?></span></div>
    <?php elseif (!$unit): ?>
        <div class="empty-state detail-empty"><p class="eyebrow">VISIT BOOKING</p><h1>Choose a unit to visit.</h1><p>Open a unit listing first, then select a date for your in-person tour.</p><a class="button button-dark" href="index.php">Browse apartments</a></div>
    <?php else: ?>
        <a class="back-link" href="unit.php?id=<?= (int)$unit['id'] ?>">← Back to Unit <?= e($unit['unit_number']) ?></a>
        <div class="visit-heading"><div><p class="eyebrow">IN-PERSON VISIT</p><h1 class="checkout-title">Pick a time to see it in person.</h1><p class="checkout-intro">Choose a day, select an open time, and tell us how to reach you. The final contract is signed in person.</p></div><div class="visit-unit-label"><strong><?= e($unit['apartment_name']) ?></strong><span>Unit <?= e($unit['unit_number']) ?> · <?= e($unit['area']) ?></span></div></div>
        <?php if ($success): ?><div class="success-banner" role="status"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="form-alert" role="alert"><?= e($error) ?></div><?php endif; ?>
        <section class="visit-booking-layout">
            <div class="calendar-panel"><div class="calendar-header"><div><p class="eyebrow">NEXT 3 WEEKS</p><h2>Choose a day</h2></div><span>Closed Sundays</span></div><div class="calendar-grid"><?php foreach ($calendarDays as $day): ?><a class="calendar-day <?= $day->format('Y-m-d') === $selectedDate ? 'calendar-day-active' : '' ?>" href="visit.php?unit_id=<?= (int)$unit['id'] ?>&date=<?= $day->format('Y-m-d') ?>"><span><?= $day->format('D') ?></span><strong><?= $day->format('d') ?></strong><small><?= $day->format('M') ?></small></a><?php endforeach; ?></div><div class="selected-date"><span>Selected date</span><strong><?= $selectedDateObject->format('l, F j, Y') ?></strong></div><div class="slot-heading"><h3>Available times</h3><span>30-minute private viewing</span></div><div class="time-slots"><?php foreach ($timeSlots as $value => $label): $isBooked = in_array($value, $bookedTimes, true); ?><span class="time-slot <?= $isBooked ? 'time-slot-booked' : '' ?>"><?= $label ?><?= $isBooked ? ' · Taken' : '' ?></span><?php endforeach; ?></div></div>
            <form method="post" class="form-card visit-form"><input type="hidden" name="unit_id" value="<?= (int)$unit['id'] ?>"><input type="hidden" name="visit_date" value="<?= e($selectedDate) ?>"><div class="form-section"><h2>Your details</h2><div class="form-grid"><label>Full name<input type="text" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>"></label><label>Email address<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label><label>Phone number<input type="tel" name="phone" required value="<?= e($_POST['phone'] ?? '') ?>"></label><label>Choose a time<select name="visit_time" required><option value="">Select a time</option><?php foreach ($timeSlots as $value => $label): if (!in_array($value, $bookedTimes, true)): ?><option value="<?= $value ?>" <?= (($_POST['visit_time'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endif; endforeach; ?></select></label></div><label>Anything we should know?<textarea name="notes" rows="4" placeholder="Optional accessibility, pet, or viewing notes"><?= e($_POST['notes'] ?? '') ?></textarea></label></div><div class="visit-policy-note"><strong>What happens next</strong><p>You will receive a confirmation email from the property team. Viewing, document review, deposit confirmation, and final contract signing are handled face-to-face.</p></div><button class="button button-dark" type="submit">Schedule in-person visit →</button></form>
        </section>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
