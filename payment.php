<?php
require_once __DIR__ . '/config/database.php';

$reservationId = filter_input(INPUT_GET, 'reservation_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
$reservation = null;
$error = null;
$success = false;
$dbError = null;

try {
    $pdo = db();
    $statement = $pdo->prepare('SELECT r.*, u.unit_number, u.unit_type, u.monthly_rent, u.image_url, a.name AS apartment_name, a.area FROM reservations r JOIN units u ON u.id = r.unit_id JOIN apartments a ON a.id = u.apartment_id WHERE r.id = :id LIMIT 1');
    $statement->execute(['id' => $reservationId ?: 0]);
    $reservation = $statement->fetch();

    if ($reservation && $_SERVER['REQUEST_METHOD'] === 'POST' && $reservation['status'] === 'pending_payment') {
        $method = (string)($_POST['payment_method'] ?? 'card');
        $allowedMethods = ['card', 'gcash', 'bank_transfer'];
        $reference = trim((string)($_POST['transaction_reference'] ?? ''));
        if (!in_array($method, $allowedMethods, true)) {
            $error = 'Please choose a supported payment method.';
        } elseif ($reference === '') {
            $error = 'Enter a payment reference or confirmation number.';
        } else {
            $pdo->beginTransaction();
            $payment = $pdo->prepare("INSERT INTO payments (reservation_id, payment_method, transaction_reference, amount, status, paid_at) VALUES (:reservation_id, :payment_method, :transaction_reference, :amount, 'paid', NOW())");
            $payment->execute([
                'reservation_id' => $reservation['id'],
                'payment_method' => $method,
                'transaction_reference' => $reference,
                'amount' => $reservation['amount_due'],
            ]);
            $updateReservation = $pdo->prepare("UPDATE reservations SET status = 'paid' WHERE id = :id");
            $updateReservation->execute(['id' => $reservation['id']]);
            $updateUnit = $pdo->prepare("UPDATE units SET status = 'reserved' WHERE id = (SELECT unit_id FROM reservations WHERE id = :id)");
            $updateUnit->execute(['id' => $reservation['id']]);
            $pdo->commit();
            $reservation['status'] = 'paid';
            $success = true;
        }
    }
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $dbError = 'The payment step needs the MySQL connection and the updated SQL schema. Import sql/staylocal.sql in XAMPP, then try again.';
}

$pageTitle = 'Online payment';
$currentPage = 'homes';
require __DIR__ . '/partials/header.php';
?>
<div class="page-shell checkout-page">
    <?php if ($dbError): ?>
        <div class="database-notice" role="status"><strong>Connect your local directory.</strong><span><?= e($dbError) ?></span></div>
    <?php elseif (!$reservation): ?>
        <div class="empty-state detail-empty"><p class="eyebrow">PAYMENT NOT FOUND</p><h1>We could not find that reservation.</h1><p>Start again from an available unit listing.</p><a class="button button-dark" href="index.php">Browse apartments</a></div>
    <?php elseif ($success): ?>
        <div class="success-panel"><p class="eyebrow">RESERVATION CONFIRMED</p><h1>Your place is held.</h1><p>Reservation #<?= (int)$reservation['id'] ?> for Unit <?= e($reservation['unit_number']) ?> is marked paid. The property team will contact <?= e($reservation['email']) ?> to confirm the next in-person steps.</p><div class="success-actions"><a class="button button-dark" href="visit.php?unit_id=<?= (int)$reservation['unit_id'] ?>">Schedule your in-person visit</a><a class="text-link" href="index.php">Return to apartments</a></div></div>
    <?php else: ?>
        <div class="checkout-layout">
            <main>
                <p class="eyebrow">STEP 2 OF 2 · ONLINE PAYMENT</p>
                <h1 class="checkout-title">Complete your reservation.</h1>
                <p class="checkout-intro">Choose a payment method and enter the confirmation reference. This demo checkout records the payment locally; connect a payment gateway before accepting live funds.</p>
                <?php if ($error): ?><div class="form-alert" role="alert"><?= e($error) ?></div><?php endif; ?>
                <form method="post" class="form-card">
                    <input type="hidden" name="reservation_id" value="<?= (int)$reservation['id'] ?>">
                    <div class="form-section"><h2>Payment method</h2><div class="payment-options"><label class="payment-option"><input type="radio" name="payment_method" value="card" checked><span><strong>Card</strong><small>Visa, Mastercard, or debit card</small></span></label><label class="payment-option"><input type="radio" name="payment_method" value="gcash"><span><strong>GCash</strong><small>Mobile wallet confirmation</small></span></label><label class="payment-option"><input type="radio" name="payment_method" value="bank_transfer"><span><strong>Bank transfer</strong><small>Upload or enter your reference</small></span></label></div></div>
                    <div class="form-section"><label>Payment / transaction reference<input type="text" name="transaction_reference" required placeholder="e.g. STAYLOCAL-12345"></label></div>
                    <div class="form-consent"><input id="payment-consent" type="checkbox" required><label for="payment-consent">I confirm this payment is for the reservation amount shown and understand that final documents and lease signing are handled in person.</label></div>
                    <button class="button button-dark" type="submit">Pay <?= pesos((float)$reservation['amount_due']) ?> and reserve →</button>
                </form>
            </main>
            <aside class="checkout-summary"><img src="<?= e($reservation['image_url']) ?>" alt="Unit <?= e($reservation['unit_number']) ?> interior"><div class="summary-body"><p class="eyebrow"><?= e($reservation['apartment_name']) ?> · <?= e($reservation['area']) ?></p><h2>Unit <?= e($reservation['unit_number']) ?></h2><p><?= e($reservation['unit_type']) ?></p><div class="summary-total"><span>Total due today</span><strong><?= pesos((float)$reservation['amount_due']) ?></strong><small>Reservation amount for the selected home.</small></div></div></aside>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
