<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Find an apartment that fits your life';
$currentPage = 'homes';
$search = trim((string) ($_GET['q'] ?? ''));
$petPolicy = (string) ($_GET['pets'] ?? 'all');
$apartments = [];
$dbError = null;

try {
    $pdo = db();
    $sql = "SELECT a.id, a.name, a.area, a.address, a.description, a.pet_friendly, a.image_url,
                   COUNT(CASE WHEN u.status = 'available' THEN 1 END) AS available_units,
                   MIN(CASE WHEN u.status = 'available' THEN u.monthly_rent END) AS lowest_rent
            FROM apartments a
            LEFT JOIN units u ON u.apartment_id = a.id
            WHERE 1 = 1";
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (a.name LIKE :search OR a.area LIKE :search OR a.address LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    if ($petPolicy === 'yes') {
        $sql .= ' AND a.pet_friendly = 1';
    } elseif ($petPolicy === 'no') {
        $sql .= ' AND a.pet_friendly = 0';
    }

    $sql .= ' GROUP BY a.id ORDER BY a.featured DESC, a.name ASC';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $apartments = $statement->fetchAll();
} catch (Throwable $exception) {
    $dbError = 'The directory is waiting for its MySQL connection. Import the supplied SQL file in XAMPP, then refresh this page.';
}

require __DIR__ . '/partials/header.php';
?>
<section class="hero-band">
    <div class="page-shell hero-grid">
        <div class="hero-copy">
            <p class="eyebrow">LOCAL RENTALS, WITHOUT THE GUESSWORK</p>
            <h1>Find a place that feels <em>right.</em></h1>
            <p class="hero-intro">Compare apartment buildings, see which floors have openings, and understand the monthly cost before you make the trip.</p>
            <div class="hero-rule" aria-hidden="true"></div>
            <p class="hero-caption">StayLocal is a directory for long-term renting. Browse online. Decide in person.</p>
        </div>
        <div class="hero-aside" aria-label="StayLocal overview">
            <div class="hero-stamp">01 <span>/</span> browse locally</div>
            <div class="hero-stamp">02 <span>/</span> compare openly</div>
            <div class="hero-stamp">03 <span>/</span> visit in person</div>
        </div>
    </div>
</section>

<section class="search-panel page-shell" aria-label="Apartment search">
    <div class="search-heading">
        <p class="eyebrow">START HERE</p>
        <h2>Which part of the city feels like yours?</h2>
    </div>
    <form class="search-form" method="get" action="index.php">
        <label class="search-input-wrap">
            <span class="sr-only">Search by apartment, neighborhood, or address</span>
            <span class="search-glyph" aria-hidden="true">⌕</span>
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search a building, area, or street" autocomplete="off">
        </label>
        <label class="select-wrap">
            <span class="sr-only">Pet policy</span>
            <select name="pets">
                <option value="all" <?= $petPolicy === 'all' ? 'selected' : '' ?>>Any pet policy</option>
                <option value="yes" <?= $petPolicy === 'yes' ? 'selected' : '' ?>>Pet friendly</option>
                <option value="no" <?= $petPolicy === 'no' ? 'selected' : '' ?>>No pets</option>
            </select>
        </label>
        <button class="button button-dark" type="submit">Search homes</button>
    </form>
</section>

<section class="listing-section page-shell" id="homes">
    <div class="section-heading">
        <div>
            <p class="eyebrow">THE DIRECTORY</p>
            <h2><?= $search !== '' ? 'Results for “' . e($search) . '”' : 'Apartments worth a closer look' ?></h2>
        </div>
        <p class="result-note"><?= count($apartments) ?> <?= count($apartments) === 1 ? 'building' : 'buildings' ?> listed</p>
    </div>

    <?php if ($dbError !== null): ?>
        <div class="database-notice" role="status">
            <strong>Connect your local directory.</strong>
            <span><?= e($dbError) ?></span>
        </div>
    <?php elseif (count($apartments) === 0): ?>
        <div class="empty-state">
            <p class="eyebrow">NO MATCHES YET</p>
            <h3>Try a wider search.</h3>
            <p>Search by a neighborhood or building name, or clear the pet policy filter to see every listing.</p>
            <a class="text-link" href="index.php">Clear search <span>→</span></a>
        </div>
    <?php else: ?>
        <div class="listing-grid">
            <?php foreach ($apartments as $apartment): ?>
                <article class="apartment-card">
                    <a class="apartment-visual" href="apartment.php?id=<?= (int) $apartment['id'] ?>" aria-label="View <?= e($apartment['name']) ?>">
                        <img src="<?= e($apartment['image_url']) ?>" alt="<?= e($apartment['name']) ?> apartment">
                        <span class="visual-arrow" aria-hidden="true">↗</span>
                    </a>
                    <div class="apartment-card-body">
                        <div class="card-topline">
                            <p class="card-kicker"><?= e($apartment['area']) ?></p>
                            <?php if ((int) $apartment['pet_friendly'] === 1): ?>
                                <span class="tag tag-pets">Pets welcome</span>
                            <?php else: ?>
                                <span class="tag tag-neutral">No pets</span>
                            <?php endif; ?>
                        </div>
                        <h3><a href="apartment.php?id=<?= (int) $apartment['id'] ?>"><?= e($apartment['name']) ?></a></h3>
                        <p class="card-address"><?= e($apartment['address']) ?></p>
                        <div class="card-bottomline">
                            <span><strong><?= (int) $apartment['available_units'] ?></strong> available units</span>
                            <span>from <strong><?= pesos((float) $apartment['lowest_rent']) ?></strong> / mo</span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="process-section" id="how-it-works">
    <div class="page-shell process-grid">
        <div class="process-intro">
            <p class="eyebrow">THE STAYLOCAL WAY</p>
            <h2>Less scrolling in circles. More knowing what to do next.</h2>
        </div>
        <div class="process-steps">
            <div class="process-step"><span>01</span><div><h3>Browse buildings</h3><p>Start with the neighborhoods and apartment buildings that match your everyday route.</p></div></div>
            <div class="process-step"><span>02</span><div><h3>Compare actual units</h3><p>Open a listing to see available units organized by floor, with clear monthly pricing.</p></div></div>
            <div class="process-step"><span>03</span><div><h3>Visit before you commit</h3><p>Online browsing helps you shortlist. The final viewing and contract signing happen in person.</p></div></div>
        </div>
    </div>
</section>

<section class="visit-section page-shell" id="visit">
    <div class="visit-callout">
        <div>
            <p class="eyebrow">A NOTE BEFORE YOU BOOK</p>
            <h2>Every contract is signed face to face.</h2>
        </div>
        <p>StayLocal does not collect online deposits or signatures. After you find a unit you like, contact the property and arrange an in-person visit to review the space, requirements, and contract together.</p>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
