<?php
declare(strict_types=1);

session_start();

$products = [
    1 => ['name' => 'Notebook', 'price' => 45.00, 'icon' => '📓'],
    2 => ['name' => 'Ballpen', 'price' => 12.50, 'icon' => '🖊️'],
    3 => ['name' => 'Pencil', 'price' => 8.00, 'icon' => '✏️'],
    4 => ['name' => 'Eraser', 'price' => 10.00, 'icon' => '◻️'],
    5 => ['name' => 'Ruler', 'price' => 25.00, 'icon' => '📏'],
    6 => ['name' => 'Highlighter', 'price' => 35.00, 'icon' => '🖍️'],
    7 => ['name' => 'Mouse', 'price' => 450.00, 'icon' => '🖱️'],
];

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (!isset($_SESSION['receipt'])) {
    $_SESSION['receipt'] = null;
}

function money(float $amount): string
{
    return '₱' . number_format($amount, 2);
}

function redirect(): never
{
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

function cartTotal(array $cart, array $products): float
{
    $total = 0.0;
    foreach ($cart as $productId => $quantity) {
        if (isset($products[$productId])) {
            $total += $products[$productId]['price'] * $quantity;
        }
    }
    return $total;
}

$message = null;
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        if (isset($products[$productId])) {
            $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
            $message = $products[$productId]['name'] . ' added to the cart.';
        }
        redirect();
    }

    if ($action === 'increase' || $action === 'decrease') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $action === 'increase' ? 1 : -1;
            if ($_SESSION['cart'][$productId] < 1) {
                unset($_SESSION['cart'][$productId]);
            }
        }
        redirect();
    }

    if ($action === 'remove') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        unset($_SESSION['cart'][$productId]);
        redirect();
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        $_SESSION['receipt'] = null;
        redirect();
    }

    if ($action === 'checkout') {
        $total = cartTotal($_SESSION['cart'], $products);
        $paymentInput = trim((string) ($_POST['payment'] ?? ''));

        if ($total <= 0) {
            $message = 'Add at least one item before completing the transaction.';
            $messageType = 'warning';
        } elseif ($paymentInput === '') {
            $message = 'Please enter a payment amount.';
            $messageType = 'danger';
        } elseif (!is_numeric($paymentInput)) {
            $message = 'Payment must be a valid number.';
            $messageType = 'danger';
        } else {
            $payment = (float) $paymentInput;
            if ($payment < 0) {
                $message = 'Payment cannot be negative.';
                $messageType = 'danger';
            } elseif ($payment < $total) {
                $message = 'Payment is not enough. The total is ' . money($total) . '.';
                $messageType = 'danger';
            } else {
                $_SESSION['receipt'] = [
                    'items' => $_SESSION['cart'],
                    'total' => $total,
                    'payment' => $payment,
                    'change' => $payment - $total,
                    'date' => date('M d, Y h:i A'),
                ];
                $_SESSION['cart'] = [];
                redirect();
            }
        }
    }
}

$cart = $_SESSION['cart'];
$total = cartTotal($cart, $products);
$itemCount = array_sum($cart);
$receipt = $_SESSION['receipt'];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Supplies Ordering System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bs-primary-rgb: 18, 78, 120;
            --bs-body-color: #243b53;
            --bs-secondary-color: #526779;
            --bs-border-color: #d5e2ed;
            --supply-blue: #124e78;
            --supply-teal: #0b7285;
        }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #edf5ff 0%, #f6f9fc 50%, #e9f7f2 100%);
        }
        h1, h2 { color: var(--supply-blue); }
        .brand-gradient { background: linear-gradient(135deg, var(--supply-blue), var(--supply-teal)); }
        .navbar .text-white-50 { color: #d7edf5 !important; }
        .card-header.bg-white { background: #eaf2fb !important; }
        .cart-sticky > .card-header.bg-white { background: #e4f3ef !important; }
        .product-card {
            --product-tint: #eef4ff;
            --product-accent: #315f9c;
            background: var(--product-tint);
            border-color: var(--product-accent) !important;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .row > :nth-child(2) > .product-card { --product-tint: #edf8f4; --product-accent: #26745e; }
        .row > :nth-child(3) > .product-card { --product-tint: #fff7e5; --product-accent: #886013; }
        .row > :nth-child(4) > .product-card { --product-tint: #fff0f3; --product-accent: #a34363; }
        .row > :nth-child(5) > .product-card { --product-tint: #f3efff; --product-accent: #7152a3; }
        .row > :nth-child(6) > .product-card { --product-tint: #eaf8fc; --product-accent: #246c83; }
        .product-card:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(18,78,120,.12); }
        .product-card h3 { color: var(--product-accent); }
        .product-card .badge.text-bg-light {
            color: var(--product-accent) !important;
            background: #fff !important;
            border-color: var(--product-accent) !important;
        }
        .product-icon { font-size: 2.25rem; }
        .btn-primary {
            --bs-btn-bg: var(--supply-blue);
            --bs-btn-border-color: var(--supply-blue);
            --bs-btn-hover-bg: #0d3d60;
            --bs-btn-hover-border-color: #0d3d60;
            --bs-btn-active-bg: #092f4b;
            --bs-btn-active-border-color: #092f4b;
            --bs-btn-focus-shadow-rgb: 18, 78, 120;
        }
        .btn-success {
            --bs-btn-bg: #16715a;
            --bs-btn-border-color: #16715a;
            --bs-btn-hover-bg: #105a47;
            --bs-btn-hover-border-color: #105a47;
            --bs-btn-active-bg: #0d493a;
            --bs-btn-active-border-color: #0d493a;
            --bs-btn-focus-shadow-rgb: 22, 113, 90;
        }
        .form-control:focus {
            border-color: var(--supply-teal);
            box-shadow: 0 0 0 .2rem rgba(11,114,133,.2);
        }
        .input-group-text { color: var(--supply-blue); background: #eaf2fb; }
        .alert-success { color: #14543f; background: #e4f3e9; border-color: #add6bb; }
        .cart-sticky { position: sticky; top: 1rem; }
        .summary-total { font-size: 1.55rem; }
        @media (prefers-reduced-motion: reduce) {
            .product-card { transition: none; }
            .product-card:hover { transform: none; }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-dark brand-gradient shadow-sm">
    <div class="container py-2">
        <span class="navbar-brand fw-bold">🎒 Student Supplies</span>
        <span class="text-white-50">Ordering System</span>
    </div>
</nav>

<main class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h2 mb-1">Build your order</h1>
            <p class="text-secondary mb-0">Choose supplies, adjust quantities, and pay when ready.</p>
        </div>
        <span class="badge text-bg-primary fs-6">Items in cart: <?= $itemCount ?></span>
    </div>

    <?php if ($message !== null): ?>
        <div class="alert alert-<?= e($messageType) ?> alert-dismissible fade show" role="alert">
            <?= e($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($receipt !== null): ?>
        <div class="alert alert-success shadow-sm">
            <h2 class="h5">Transaction completed successfully ✅</h2>
            <p class="mb-1">Receipt date: <?= e($receipt['date']) ?></p>
            <p class="mb-1">Total paid: <strong><?= money($receipt['payment']) ?></strong></p>
            <p class="mb-3">Change: <strong><?= money($receipt['change']) ?></strong></p>
            <form method="post">
                <input type="hidden" name="action" value="clear">
                <button class="btn btn-success">Start a new transaction</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <section class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0">Available supplies</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($products as $id => $product): ?>
                            <div class="col-sm-6">
                                <div class="card h-100 product-card border">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span class="product-icon" aria-hidden="true"><?= $product['icon'] ?></span>
                                            <span class="badge text-bg-light border"><?= money($product['price']) ?></span>
                                        </div>
                                        <h3 class="h6 mt-3 mb-3"><?= e($product['name']) ?></h3>
                                        <form method="post" class="mt-auto">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="product_id" value="<?= $id ?>">
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="quantity" value="1" min="1" aria-label="Quantity for <?= e($product['name']) ?>">
                                                <button class="btn btn-primary">Add to cart</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <aside class="col-lg-5">
            <div class="card border-0 shadow-sm cart-sticky">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Your cart</h2>
                    <?php if ($cart): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="clear">
                            <button class="btn btn-sm btn-outline-secondary">Clear cart</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!$cart): ?>
                        <div class="text-center text-secondary py-5">
                            <div class="display-6 mb-2">🛒</div>
                            <p class="mb-0">Your cart is empty.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cart as $id => $quantity): ?>
                            <?php $product = $products[$id]; $subtotal = $product['price'] * $quantity; ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between gap-2">
                                    <div>
                                        <div class="fw-semibold"><?= e($product['name']) ?></div>
                                        <small class="text-secondary"><?= money($product['price']) ?> each</small>
                                    </div>
                                    <strong><?= money($subtotal) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="btn-group" role="group" aria-label="Change quantity for <?= e($product['name']) ?>">
                                        <form method="post"><input type="hidden" name="action" value="decrease"><input type="hidden" name="product_id" value="<?= $id ?>"><button class="btn btn-sm btn-outline-secondary" aria-label="Decrease quantity">−</button></form>
                                        <span class="btn btn-sm btn-light disabled"><?= $quantity ?></span>
                                        <form method="post"><input type="hidden" name="action" value="increase"><input type="hidden" name="product_id" value="<?= $id ?>"><button class="btn btn-sm btn-outline-secondary" aria-label="Increase quantity">+</button></form>
                                    </div>
                                    <form method="post"><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?= $id ?>"><button class="btn btn-sm btn-link text-danger p-0">Remove</button></form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold">Total amount</span>
                            <span class="fw-bold text-primary summary-total"><?= money($total) ?></span>
                        </div>
                        <form method="post">
                            <input type="hidden" name="action" value="checkout">
                            <label for="payment" class="form-label">Payment amount</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="payment" name="payment" min="0" step="0.01" placeholder="Enter payment">
                            </div>
                            <div class="form-text mb-3">Enter an amount equal to or greater than the total.</div>
                            <button class="btn btn-success w-100">Complete transaction</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
