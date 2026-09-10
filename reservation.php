<?php
require_once __DIR__ . '/config/database.php';

$unitId = filter_input(INPUT_GET, 'unit_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'unit_id', FILTER_VALIDATE_INT);
$unit = null;
$apartment = null;
$error = null;
$dbError = null;

try {
    $pdo = db();
    $statement = $pdo->prepare('SELECT u.*, a.name AS apartment_name, a.area, a.address, a.pet_friendly FROM units u JOIN apartments a ON a.id = u.apartment_id WHERE u.id = :id LIMIT 1');
    $statement->execute(['id' => $unitId ?: 0]);
    $unit = $statement->fetch();
    if ($unit) {
        $apartment = [
            'name' => $unit['apartment_name'],
            'area' => $unit['area'],
            'address' => $unit['address'],
            'pet_friendly' => $unit['pet_friendly'],
        ];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $unit) {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $moveInDate = trim((string)($_POST['move_in_date'] ?? ''));
        $occupants = max(1, min(20, (int)($_POST['occupants'] ?? 1)));
        $notes = trim((string)($_POST['notes'] ?? ''));

        if ($unit['status'] !== 'available') {
            $error = 'This unit is no longer available for reservation.';
        } elseif ($fullName === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide your full name, a valid email address, and a phone number.';
        } elseif (!$moveInDate || strtotime($moveInDate) === false || $moveInDate < date('Y-m-d')) {
            $error = 'Please choose a move-in date from today onward.';
        } else {
            $insert = $pdo->prepare("INSERT INTO reservations (unit_id, full_name, email, phone, move_in_date, occupants, notes, amount_due, status) VALUES (:unit_id, :full_name, :email, :phone, :move_in_date, :occupants, :notes, :amount_due, 'pending_payment')");
            $insert->execute([
                'unit_id' => $unit['id'],
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'move_in_date' => $moveInDate,
                'occupants' => $occupants,
                'notes' => $notes ?: null,
                'amount_due' => $unit['monthly_rent'],
            ]);
            header('Location: payment.php?reservation_id=' . (int)$pdo->lastInsertId());
            exit;
        }
    }
} catch (Throwable $exception) {
    $dbError = 'The reservation flow needs the MySQL connection. Import sql/staylocal.sql in XAMPP, then try again.';
}

$pageTitle = $unit ? 'Reserve Unit ' . $unit['unit_number'] : 'Reserve a unit';
$currentPage = 'homes';
require __DIR__ . '/partials/header.php';
?>
<div class="page-shell checkout-page">
    <?php if ($dbError): ?>
        <div class="database-notice" role="status"><strong>Connect your local directory.</strong><span><?= e($dbError) ?></span></div>
    <?php elseif (!$unit): ?>
        <div class="empty-state detail-empty"><p class="eyebrow">RESERVATION NOT FOUND</p><h1>Choose a unit first.</h1><p>Return to the listings and open the unit you want to reserve.</p><a class="button button-dark" href="index.php">Browse apartments</a></div>
    <?php else: ?>
        <a class="back-link" href="unit.php?id=<?= (int)$unit['id'] ?>">← Back to Unit <?= e($unit['unit_number']) ?></a>
        <div class="checkout-layout">
            <main>
                <p class="eyebrow">STEP 1 OF 2 · RESERVATION</p>
                <h1 class="checkout-title">Reserve your place.</h1>
                <p class="checkout-intro">Tell us who will be renting. You will continue to online payment after submitting this form.</p>
                <?php if ($error): ?><div class="form-alert" role="alert"><?= e($error) ?></div><?php endif; ?>
                <form method="post" class="form-card">
                    <input type="hidden" name="unit_id" value="<?= (int)$unit['id'] ?>">
                    <div class="form-section"><h2>Renter details</h2><div class="form-grid"><label>Full name<input type="text" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>"></label><label>Email address<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label><label>Phone number<input type="tel" name="phone" required value="<?= e($_POST['phone'] ?? '') ?>"></label><label>Number of occupants<input type="number" name="occupants" min="1" max="20" value="<?= e($_POST['occupants'] ?? '1') ?>"></label></div></div>
                    <div class="form-section"><h2>Move-in details</h2><div class="form-grid"><label>Preferred move-in date<input type="date" name="move_in_date" min="<?= date('Y-m-d') ?>" required value="<?= e($_POST['move_in_date'] ?? '') ?>"></label><label>Pets or accessibility notes<textarea name="notes" rows="4" placeholder="Optional"><?= e($_POST['notes'] ?? '') ?></textarea></label></div></div>
                    <div class="form-consent"><input id="reservation-consent" type="checkbox" required><label for="reservation-consent">I understand this reservation starts the online payment step. Property viewing, document review, and final contract signing are completed in person.</label></div>
                    <button class="button button-dark" type="submit">Continue to online payment →</button>
                </form>
            </main>
            <aside class="checkout-summary"><img src="<?= e($unit['image_url']) ?>" alt="Unit <?= e($unit['unit_number']) ?> interior"><div class="summary-body"><p class="eyebrow"><?= e($unit['apartment_name']) ?> · <?= e($unit['area']) ?></p><h2>Unit <?= e($unit['unit_number']) ?></h2><p><?= e($unit['unit_type']) ?> · <?= (int)$unit['size_sqm'] ?> sqm</p><div class="summary-total"><span>Reservation amount</span><strong><?= pesos((float)$unit['monthly_rent']) ?></strong><small>One month rent, applied to your move-in balance.</small></div></div></aside>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
