<?php
require_once __DIR__ . '/config/database.php';

$apartmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$apartment = null;
$unitsByFloor = [];
$dbError = null;

try {
    $pdo = db();
    $apartmentStatement = $pdo->prepare('SELECT * FROM apartments WHERE id = :id LIMIT 1');
    $apartmentStatement->execute(['id' => $apartmentId ?: 0]);
    $apartment = $apartmentStatement->fetch();

    $availableCount = 0;

    if ($apartment) {
        $unitStatement = $pdo->prepare("SELECT * FROM units WHERE apartment_id = :apartment_id ORDER BY floor_number ASC, unit_number ASC");
        $unitStatement->execute(['apartment_id' => $apartment['id']]);
        foreach ($unitStatement->fetchAll() as $unit) {
            $unitsByFloor[(int) $unit['floor_number']][] = $unit;
            if ($unit['status'] === 'available') {
                $availableCount++;
            }
        }
    }
} catch (Throwable $exception) {
    $dbError = 'The apartment details need the MySQL connection. Import sql/staylocal.sql in XAMPP, then try again.';
}

if (!$apartment && $dbError === null) {
    http_response_code(404);
}

$pageTitle = $apartment ? (string) $apartment['name'] : 'Apartment details';
$currentPage = 'homes';
require __DIR__ . '/partials/header.php';
?>
<div class="page-shell detail-page">
    <?php if ($dbError !== null): ?>
        <div class="database-notice detail-notice" role="status">
            <strong>Connect your local directory.</strong>
            <span><?= e($dbError) ?></span>
        </div>
        <a class="text-link" href="index.php">← Back to apartments</a>
    <?php elseif (!$apartment): ?>
        <div class="empty-state detail-empty">
            <p class="eyebrow">LISTING NOT FOUND</p>
            <h1>This apartment has moved on.</h1>
            <p>Return to the directory to browse the other buildings currently listed on StayLocal.</p>
            <a class="button button-dark" href="index.php">Back to apartments</a>
        </div>
    <?php else: ?>
        <a class="back-link" href="index.php">← All apartments</a>
        <section class="detail-hero">
            <div class="detail-visual">
                <img src="<?= e($apartment['image_url']) ?>" alt="<?= e($apartment['name']) ?> apartment">
            </div>
            <div class="detail-intro">
                <div class="card-topline">
                    <p class="eyebrow"><?= e($apartment['area']) ?></p>
                    <?php if ((int) $apartment['pet_friendly'] === 1): ?>
                        <span class="tag tag-pets">Pets welcome</span>
                    <?php else: ?>
                        <span class="tag tag-neutral">No pets</span>
                    <?php endif; ?>
                </div>
                <h1><?= e($apartment['name']) ?></h1>
                <p class="detail-address"><?= e($apartment['address']) ?></p>
                <p class="detail-description"><?= e($apartment['description']) ?></p>
                <div class="detail-facts">
                    <div><span>Availability</span><strong><?= $availableCount ?> units open</strong></div>
                    <div><span>Pet policy</span><strong><?= (int) $apartment['pet_friendly'] === 1 ? 'Friendly' : 'Not allowed' ?></strong></div>
                    <div><span>Leasing</span><strong>In person</strong></div>
                </div>
            </div>
        </section>

        <section class="units-section">
            <div class="section-heading unit-heading">
                <div>
                    <p class="eyebrow">UNIT AVAILABILITY</p>
                    <h2>Choose your floor.</h2>
                </div>
               
            </div>
            <?php foreach ($unitsByFloor as $floor => $units): ?>
                <div class="floor-row">
                    <div class="floor-label"><span>Floor</span><strong><?= str_pad((string) $floor, 2, '0', STR_PAD_LEFT) ?></strong></div>
                    <div class="unit-list">
                        <?php foreach ($units as $unit): ?>
                            <a href="unit.php?id=<?= (int)$unit['id'] ?>" class="unit-card-link">
                                <article class="unit-card <?= $unit['status'] !== 'available' ? 'unit-unavailable' : '' ?>">
                                    <?php if (!empty($unit['image_url'])): ?>
                                        <div class="unit-photo">
                                            <img src="<?= e($unit['image_url']) ?>" alt="Unit <?= e($unit['unit_number']) ?> interior">
                                            <div class="unit-photo-overlay"></div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="unit-card-main">
                                        <div>
                                            <p class="unit-number">Unit <?= e($unit['unit_number']) ?></p>
                                            <p class="unit-type"><?= e($unit['unit_type']) ?> · <?= (int) $unit['size_sqm'] ?> sqm</p>
                                        </div>
                                        <div class="unit-status <?= unitStatusClass((string) $unit['status']) ?>">
                                            <?= e(unitStatusLabel((string) $unit['status'])) ?>
                                        </div>
                                    </div>
                                    <div class="unit-card-bottom">
                                        <span class="unit-price"><?= pesos((float) $unit['monthly_rent']) ?><small> / month</small></span>
                                        <?php if ($unit['status'] === 'available'): ?>
                                            <span class="unit-note">View details ↗</span>
                                        <?php else: ?>
                                            <span class="unit-note">Check back later</span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
