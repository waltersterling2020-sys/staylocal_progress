<?php
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$stats = ['apartments' => 0, 'units' => 0, 'available' => 0, 'reservations' => 0, 'pending' => 0, 'visits' => 0];
$recentReservations = [];
$dbError = null;
try {
    $pdo = db();
    $stats['apartments'] = (int) $pdo->query('SELECT COUNT(*) FROM apartments')->fetchColumn();
    $stats['units'] = (int) $pdo->query('SELECT COUNT(*) FROM units')->fetchColumn();
    $stats['available'] = (int) $pdo->query("SELECT COUNT(*) FROM units WHERE status = 'available'")->fetchColumn();
    $stats['reservations'] = (int) $pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
    $stats['pending'] = (int) $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending_payment'")->fetchColumn();
    $stats['visits'] = (int) $pdo->query("SELECT COUNT(*) FROM visits WHERE status = 'scheduled' AND visit_date >= CURDATE()")->fetchColumn();
    $recentReservations = $pdo->query("SELECT r.id, r.full_name, r.status, u.unit_number, a.name AS apartment_name FROM reservations r JOIN units u ON u.id = r.unit_id JOIN apartments a ON a.id = u.apartment_id ORDER BY r.id DESC LIMIT 6")->fetchAll();
} catch (Throwable $exception) {
    $dbError = 'Import the updated sql/staylocal.sql file to load dashboard data.';
}

$pageTitle = 'Overview';
$currentAdminPage = 'dashboard';
require __DIR__ . '/partials/header.php';
?>
<section class="admin-intro-row">
    <div>
        <p class="admin-kicker">GOOD DAY, <?= e(strtoupper((string) ($_SESSION['admin_name'] ?? 'ADMIN'))) ?></p>
        <p class="admin-lede">A clear view of what is happening across your homes today.</p>
    </div><a class="button button-dark" href="apartments.php">Manage listings →</a>
</section>
<?php if ($dbError): ?>
    <div class="database-notice" role="status"><strong>Dashboard data unavailable.</strong><span><?= e($dbError) ?></span>
    </div><?php endif; ?>
<section class="stat-grid" aria-label="Dashboard metrics">
    <article class="stat-card stat-card-blue"><span class="stat-label">Total
            apartments</span><strong><?= $stats['apartments'] ?></strong><small>Active buildings in directory</small>
    </article>
    <article class="stat-card"><span class="stat-label">Available units</span><strong><?= $stats['available'] ?><em>/
                <?= $stats['units'] ?></em></strong><small>Ready for new renters</small></article>
    <article class="stat-card stat-card-coral"><span class="stat-label">Pending
            reservations</span><strong><?= $stats['pending'] ?></strong><small>Needs staff follow-up</small></article>
    <article class="stat-card"><span class="stat-label">Scheduled
            visits</span><strong><?= $stats['visits'] ?></strong><small>Upcoming in-person tours</small></article>
</section>
<section class="admin-content-grid">
    <div class="admin-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">RECENT ACTIVITY</p>
                <h2>Latest reservations</h2>
            </div><a class="text-link" href="reservations.php">View all →</a>
        </div><?php if (!$recentReservations): ?>
            <div class="admin-empty">No reservations yet. They will appear here after a renter completes the reservation
                form.</div><?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Renter</th>
                            <th>Home</th>
                            <th>Status</th>
                            <th>Reservation</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($recentReservations as $reservation): ?>
                            <tr>
                                <td><strong><?= e($reservation['full_name']) ?></strong></td>
                                <td>Unit
                                    <?= e($reservation['unit_number']) ?><small><?= e($reservation['apartment_name']) ?></small>
                                </td>
                                <td><span
                                        class="status-pill status-<?= e($reservation['status']) ?>"><?= e(str_replace('_', ' ', $reservation['status'])) ?></span>
                                </td>
                                <td>#<?= (int) $reservation['id'] ?></td>
                            </tr><?php endforeach; ?>
                    </tbody>
                </table>
            </div><?php endif; ?>
    </div>

    <?php require __DIR__ . '/partials/footer.php'; ?>