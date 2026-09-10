<?php
require_once __DIR__ . '/config/database.php';

$unitId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$unit = null;
$apartment = null;
$gallery = [];
$dbError = null;

try {
    $pdo = db();
    $unitStatement = $pdo->prepare('SELECT * FROM units WHERE id = :id LIMIT 1');
    $unitStatement->execute(['id' => $unitId ?: 0]);
    $unit = $unitStatement->fetch();

    if ($unit) {
        $apartmentStatement = $pdo->prepare('SELECT * FROM apartments WHERE id = :id LIMIT 1');
        $apartmentStatement->execute(['id' => $unit['apartment_id']]);
        $apartment = $apartmentStatement->fetch();

        $galleryStatement = $pdo->prepare('SELECT * FROM unit_images WHERE unit_id = :unit_id ORDER BY sort_order ASC');
        $galleryStatement->execute(['unit_id' => $unit['id']]);
        $gallery = $galleryStatement->fetchAll();
        
        // Add main image to gallery
        array_unshift($gallery, [
            'image_path' => $unit['image_url'],
            'caption' => 'Main View'
        ]);
    }
} catch (Throwable $exception) {
    $dbError = 'The unit details need the MySQL connection. Import sql/staylocal.sql in XAMPP, then try again.';
}

if (!$unit && $dbError === null) {
    http_response_code(404);
}

$pageTitle = $unit ? "Unit " . $unit['unit_number'] . " at " . $apartment['name'] : 'Unit details';
$currentPage = 'homes';
require __DIR__ . '/partials/header.php';
?>
<div class="page-shell unit-detail-page">
    <?php if ($dbError !== null): ?>
        <div class="database-notice detail-notice" role="status">
            <strong>Connect your local directory.</strong>
            <span><?= e($dbError) ?></span>
        </div>
        <a class="text-link" href="index.php">← Back to apartments</a>
    <?php elseif (!$unit): ?>
        <div class="empty-state detail-empty">
            <p class="eyebrow">UNIT NOT FOUND</p>
            <h1>This unit has moved on.</h1>
            <p>Return to the apartment listing to see other available units.</p>
            <a class="button button-dark" href="index.php">Back to apartments</a>
        </div>
    <?php else: ?>
        <a class="back-link" href="apartment.php?id=<?= (int)$apartment['id'] ?>">← Back to <?= e($apartment['name']) ?></a>
        
        <section class="unit-gallery">
            <div class="gallery-main">
                <img src="<?= e($gallery[0]['image_path']) ?>" alt="<?= e($gallery[0]['caption']) ?>">
            </div>
            <div class="gallery-grid">
                <?php for ($i = 1; $i < min(5, count($gallery)); $i++): ?>
                    <div class="gallery-item">
                        <img src="<?= e($gallery[$i]['image_path']) ?>" alt="<?= e($gallery[$i]['caption']) ?>">
                    </div>
                <?php endfor; ?>
                <?php if (count($gallery) > 5): ?>
                    <div class="gallery-more">
                        <span>+<?= count($gallery) - 5 ?> photos</span>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="unit-info-grid">
            <div class="unit-main-info">
                <div class="unit-header">
                    <div>
                        <p class="eyebrow"><?= e($apartment['area']) ?> · Floor <?= str_pad((string)$unit['floor_number'], 2, '0', STR_PAD_LEFT) ?></p>
                        <h1>Unit <?= e($unit['unit_number']) ?></h1>
                        <p class="unit-subtitle"><?= e($unit['unit_type']) ?> · <?= (int)$unit['size_sqm'] ?> sqm</p>
                    </div>
                    <div class="unit-status-tag <?= unitStatusClass((string) $unit['status']) ?>">
                        <?= e(unitStatusLabel((string) $unit['status'])) ?>
                    </div>
                </div>

                <div class="unit-description">
                    <h3>About this unit</h3>
                    <p>Located in the <?= e($apartment['name']) ?> building at <?= e($apartment['address']) ?>. This <?= e(strtolower($unit['unit_type'])) ?> offers a practical layout designed for long-term comfort in the heart of <?= e($apartment['area']) ?>.</p>
                </div>

                <div class="unit-features">
                    <h3>Features & Amenities</h3>
                    <div class="features-list">
                        <?php 
                        $features = explode(',', (string)$unit['features']);
                        foreach ($features as $feature): 
                        ?>
                            <div class="feature-item">
                                <span class="feature-icon">✓</span>
                                <span><?= e(trim($feature)) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <aside class="unit-sidebar">
                <div class="price-card">
                    <div class="price-header">
                        <span class="price-amount"><?= pesos((float)$unit['monthly_rent']) ?></span>
                        <span class="price-period">/ month</span>
                    </div>
                    <div class="price-meta">
                        <div class="meta-row"><span>Security Deposit</span><strong>2 Months</strong></div>
                        <div class="meta-row"><span>Advance Rent</span><strong>1 Month</strong></div>
                        <div class="meta-row"><span>Pet Policy</span><strong><?= (int)$apartment['pet_friendly'] ? 'Allowed' : 'Not Allowed' ?></strong></div>
                    </div>
                    <?php if ($unit['status'] === 'available'): ?>
                        <a href="reservation.php?unit_id=<?= (int)$unit['id'] ?>" class="button button-dark full-width">Reserve this unit →</a>
                        <a href="visit.php?unit_id=<?= (int)$unit['id'] ?>" class="button button-light full-width secondary-action">Schedule in-person visit</a>
                        <p class="visit-disclaimer">Reserve online, then schedule your viewing. Final contract signing is completed in person.</p>
                    <?php else: ?>
                        <button class="button button-disabled full-width" disabled><?= e(unitStatusLabel((string) $unit['status'])) ?></button>
                    <?php endif; ?>
                </div>
            </aside>
        </section>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
