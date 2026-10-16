<?php
/**
 * Restaurant Management System
 * Public Portal & Executive Showcase Landing Page
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/role_check.php';

$currency = getSetting('currency_symbol', '$');
$featuredDishes = fetchAll("SELECT m.*, c.name as category_name FROM menu_items m JOIN categories c ON m.category_id = c.id WHERE m.is_available = 1 LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Pro &mdash; Enterprise Culinary & POS Ecosystem</title>
    <meta name="description" content="Next-Generation Web-Based Restaurant Management System with Real-Time Kitchen Display, Recipe Bill-of-Materials Deduction and Smart Table Allocation.">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <style>
        .hero-banner {
            background: linear-gradient(180deg, rgba(11, 15, 25, 0.7) 0%, #0b0f19 100%),
                        radial-gradient(circle at 50% 20%, rgba(249, 115, 22, 0.18) 0%, transparent 60%);
            padding: 5rem 1.5rem 3rem 1.5rem;
            text-align: center;
        }
        .portal-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 1.5rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }
        .portal-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 12px 25px -5px rgba(249, 115, 22, 0.25);
        }
    </style>
</head>
<body>

<!-- Global Top Navbar -->
<nav class="navbar navbar-expand-lg border-bottom border-secondary border-opacity-25 sticky-top" style="background: rgba(11, 15, 25, 0.9); backdrop-filter: blur(16px);">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 text-white fw-bold" href="index.php">
            <div class="brand-icon" style="width: 36px; height: 36px; font-size: 1.1rem;">
                <i class="fa-solid fa-utensils"></i>
            </div>
            <span class="d-flex align-items-center gap-2">RESTAURANT <span class="brand-highlight">PRO</span></span>
        </a>
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-3">
                <li class="nav-item"><a class="nav-link text-light fw-semibold" href="customer/menu.php"><i class="fa-solid fa-book-open me-1 text-primary"></i> Digital Menu</a></li>
                <li class="nav-item"><a class="nav-link text-light fw-semibold" href="customer/reservation.php"><i class="fa-regular fa-calendar-check me-1 text-warning"></i> Table Reservation</a></li>
                <li class="nav-item"><a class="nav-link text-light fw-semibold" href="auth/login.php"><i class="fa-solid fa-users-gear me-1 text-info"></i> Staff Portals</a></li>
            </ul>
            <div class="d-flex gap-2">
                <?php if (isLoggedIn()): ?>
                    <a href="<?= getRoleHomeUrl() ?>" class="btn btn-primary-saas btn-sm">
                        <i class="fa-solid fa-chart-pie me-1"></i> My Dashboard (<?= strtoupper(currentUserRole()) ?>)
                    </a>
                    <a href="auth/logout.php" class="btn btn-secondary-saas btn-sm">Sign Out</a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-secondary-saas btn-sm">Sign In</a>
                    <a href="auth/register.php" class="btn btn-primary-saas btn-sm">Create Account</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-banner">
    <div class="container" style="max-width: 900px;">
        <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill fw-bold mb-3">
            <i class="fa-solid fa-bolt me-1"></i> Enterprise Restaurant Management System
        </span>
        <h1 class="display-4 fw-extrabold text-white mb-3" style="font-weight: 800; letter-spacing: -0.03em;">
            Precision Dining, Live Kitchen Flow & Automated Recipe Inventory
        </h1>
        <p class="lead text-muted mb-4">
            A state-of-the-art web platform engineered with PHP 8, MariaDB/MySQL, and REST APIs. Features zero-refresh Kitchen Display (KDS), automated recipe portion stock deduction, and AI smart table allocation.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="customer/menu.php" class="btn btn-primary-saas px-4 py-3 fs-6">
                <i class="fa-solid fa-utensils me-2"></i> Explore Digital Menu
            </a>
            <a href="customer/reservation.php" class="btn btn-secondary-saas px-4 py-3 fs-6">
                <i class="fa-regular fa-calendar-plus me-2"></i> Book VIP Table
            </a>
            <button type="button" class="btn btn-warning-saas px-4 py-3 fs-6 fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#workflowPlayerModal">
                <i class="fa-solid fa-play-circle me-2"></i> Play Step-by-Step Flow
            </button>
        </div>
    </div>
</section>

<!-- 6 Dedicated Role Workstation Portals (Instant 1-Click Evaluation) -->
<section class="py-5" style="background: rgba(17, 24, 39, 0.4);">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h3 fw-bold text-white mb-2">Role-Based Workstations (RBAC)</h2>
            <p class="text-muted small">Instant test access to all 6 operational role terminals with automated credential routing.</p>
        </div>

        <div class="row g-4">
            <!-- 1. Admin -->
            <div class="col-md-6 col-lg-4">
                <a href="auth/login.php?switch=admin" class="portal-card">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-crown me-1"></i> Administrator</span>
                            <span class="text-muted small">Full Control</span>
                        </div>
                        <h4 class="text-white fw-bold mb-2">Executive Dashboard</h4>
                        <p class="text-muted small mb-3">Revenue analytics with Chart.js, menu catalog, recipe bill-of-materials, audit trails and user access.</p>
                    </div>
                    <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center text-primary fw-semibold small">
                        <span>Launch Admin Portal</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </a>
            </div>

            <!-- 2. Waiter -->
            <div class="col-md-6 col-lg-4">
                <a href="auth/login.php?switch=waiter" class="portal-card">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-success text-white fw-bold"><i class="fa-solid fa-utensils me-1"></i> Waitstaff</span>
                            <span class="text-muted small">Floor Station</span>
                        </div>
                        <h4 class="text-white fw-bold mb-2">Touch-Friendly POS</h4>
                        <p class="text-muted small mb-3">Interactive floor plan, 1-click dish selection, table notes, promo coupons and runner alerts.</p>
                    </div>
                    <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center text-primary fw-semibold small">
                        <span>Launch Waiter Station</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </a>
            </div>

            <!-- 3. Kitchen -->
            <div class="col-md-6 col-lg-4">
                <a href="auth/login.php?switch=kitchen" class="portal-card">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-danger text-white fw-bold"><i class="fa-solid fa-fire-burner me-1"></i> Kitchen Brigade</span>
                            <span class="text-muted small">Zero-Refresh</span>
                        </div>
                        <h4 class="text-white fw-bold mb-2">Kitchen Display (KDS)</h4>
                        <p class="text-muted small mb-3">Live streaming order Kanban: Pending &rarr; Preparing &rarr; Ready at Pass with audible chime notifications.</p>
                    </div>
                    <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center text-primary fw-semibold small">
                        <span>Launch Kitchen Display</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </a>
            </div>

            <!-- 4. Cashier -->
            <div class="col-md-6 col-lg-4">
                <a href="auth/login.php?switch=cashier" class="portal-card">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary text-white fw-bold"><i class="fa-solid fa-cash-register me-1"></i> Cashier</span>
                            <span class="text-muted small">Checkout Desk</span>
                        </div>
                        <h4 class="text-white fw-bold mb-2">Billing & Invoicing</h4>
                        <p class="text-muted small mb-3">Unsettled checks, tip calculations, multiple tender types (Cash/Card/UPI), and printable receipts.</p>
                    </div>
                    <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center text-primary fw-semibold small">
                        <span>Launch Billing Desk</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </a>
            </div>

            <!-- 5. Manager -->
            <div class="col-md-6 col-lg-4">
                <a href="auth/login.php?switch=manager" class="portal-card">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-info text-dark fw-bold"><i class="fa-solid fa-user-tie me-1"></i> General Manager</span>
                            <span class="text-muted small">Operations</span>
                        </div>
                        <h4 class="text-white fw-bold mb-2">Manager Control</h4>
                        <p class="text-muted small mb-3">Daily service checklist, staff roster, daily financial reconciliation, and inventory replenishment.</p>
                    </div>
                    <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center text-primary fw-semibold small">
                        <span>Launch Manager Suite</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </a>
            </div>

            <!-- 6. Customer -->
            <div class="col-md-6 col-lg-4">
                <a href="auth/login.php?switch=customer" class="portal-card">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-secondary text-light fw-bold"><i class="fa-solid fa-user me-1"></i> Diner Portal</span>
                            <span class="text-muted small">Customer App</span>
                        </div>
                        <h4 class="text-white fw-bold mb-2">Dining Experience</h4>
                        <p class="text-muted small mb-3">Browse menu with dietary filters, book online tables with AI fit scoring, and track order status live.</p>
                    </div>
                    <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center text-primary fw-semibold small">
                        <span>Launch Diner Portal</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Chef's Featured Dishes Showcase -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="badge bg-primary text-white mb-2">Culinary Masterpieces</span>
                <h2 class="h3 fw-bold text-white mb-0">Featured Signature Dishes</h2>
            </div>
            <a href="customer/menu.php" class="text-primary fw-semibold">View Entire Menu <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredDishes as $dish): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="saas-card h-100 p-3 d-flex flex-column justify-content-between">
                        <div>
                            <!-- Mouth-Watering Dish Photography -->
                            <div class="food-card-img-wrap">
                                <img src="assets/images/<?= htmlspecialchars($dish['image'] ?: 'default_food.png') ?>" 
                                     alt="<?= htmlspecialchars($dish['name']) ?>" 
                                     class="food-card-img"
                                     onerror="this.src='assets/images/default_food.png'">
                                <span class="price-floating-badge"><?= $currency . number_format((float)$dish['price'], 2) ?></span>
                                <span class="category-floating-badge">
                                    <i class="fa-solid fa-utensils"></i> <?= htmlspecialchars($dish['category_name']) ?>
                                </span>
                            </div>

                            <h5 class="text-white fw-bold mb-2"><?= htmlspecialchars($dish['name']) ?></h5>
                            <p class="food-card-desc mb-3"><?= htmlspecialchars($dish['description'] ?? '') ?></p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary border-opacity-10">
                            <span class="text-light small fw-semibold"><i class="fa-regular fa-clock me-1 text-warning"></i> <?= $dish['preparation_time'] ?> mins prep</span>
                            <a href="customer/menu.php" class="btn btn-sm btn-primary-saas px-3">
                                <i class="fa-solid fa-cart-shopping me-1"></i> Order Online
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="py-4 border-top border-secondary border-opacity-25 text-center text-muted small" style="background: var(--bg-surface);">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div>&copy; <?= date('Y') ?> <strong class="text-light">Restaurant Pro Management System</strong> &mdash; Built with PHP 8 & MariaDB</div>
        <div class="d-flex gap-3">
            <a href="README.md" class="text-muted">Documentation</a>
            <a href="auth/login.php" class="text-muted">Staff Sign In</a>
        </div>
    </div>
</footer>

<?php require_once __DIR__ . '/includes/workflow_player_modal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/workflow_player.js"></script>
</body>
</html>
