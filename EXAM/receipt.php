<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();

/* Guard  */

if (!isset($_POST['cartData'])) {
    die("No cart data received.");
}

$cartItems = json_decode($_POST['cartData'], true);
if (!$cartItems || count($cartItems) === 0) {
    die("Cart empty.");
}


/* INPUTS */

$servedBy   = $_SESSION['user_name'] ?? 'Cashier';
$servedRole = $_SESSION['user_role'] ?? 'Cashier';

$customerName = trim($_POST['customer_name'] ?? '');
$customerName = $customerName === '' ? 'Average Joe' : $customerName;

$paymentMethod = $_POST['payment_method'] ?? 'Cash';
$cashGiven     = floatval($_POST['cash_given'] ?? 0);
$discountType  = $_POST['discount_type'] ?? 'None';


/* CALCULATIONS */

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['unitPrice'] * $item['qty'];
}

$discountRate   = in_array($discountType, ['PWD','Senior']) ? 0.20 : 0;
$discountAmount = $subtotal * $discountRate;
$finalAmount    = $subtotal - $discountAmount;

/*Block of Code for Change */

$changeAmount = 0;
if ($paymentMethod === 'Cash') {
    $changeAmount = max(0, $cashGiven - $finalAmount);
}


/* conn */

$conn = sqlsrv_connect(
    "Tys-PC\\SQLEXPRESS",
    ["Database"=>"DLSU"]
);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}



/* INSERT ORDER  */

$sqlOrder = "
INSERT INTO ORDERS
(
 ORDER_DATE,
 SERVED_BY,
 SERVED_ROLE,
 CUSTOMER_NAME,
 PAYMENT_METHOD,
 CASH_RECEIVED,
 TOTAL_AMOUNT,
 DISCOUNT_TYPE,
 DISCOUNT_RATE,
 DISCOUNT_AMOUNT,
 FINAL_AMOUNT
)
OUTPUT INSERTED.ORDER_ID
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";


$paramsOrder = [
    date('Y-m-d H:i:s'),
    $servedBy,
    $servedRole,
    $customerName,
    $paymentMethod,
    $cashGiven,
    $subtotal,
    $discountType,
    $discountRate,
    $discountAmount,
    $finalAmount
];

$stmt = sqlsrv_query($conn, $sqlOrder, $paramsOrder);
if (!$stmt) {
    die(print_r(sqlsrv_errors(), true));
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$orderId = $row['ORDER_ID'] ?? null;

if (!$orderId) {
    die("Failed to retrieve ORDER_ID.");
}


/* INSERT ORDER ITEMS  */


$sqlItem = "
INSERT INTO ORDER_ITEMS
(
 ORDER_ID,
 PRODUCT_ID,
 PRODUCT_NAME,
 CATEGORY_ID,
 SIZE,
 UNIT_PRICE,
 QUANTITY,
 LINE_TOTAL
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
";

$sqlGetCategory = "
SELECT CATEGORY_ID
FROM MENU_ITEMS
WHERE PRODUCT_ID = ?
";

foreach ($cartItems as $item) {

    $stmtCat = sqlsrv_query($conn, $sqlGetCategory, [$item['productId']]);
    $catRow = sqlsrv_fetch_array($stmtCat, SQLSRV_FETCH_ASSOC);

    if (!$catRow) {
        die("Invalid PRODUCT_ID: " . $item['productId']);
    }

    $paramsItem = [
        $orderId,
        $item['productId'],  
        $item['name'],
        $catRow['CATEGORY_ID'],
        $item['size'],
        $item['unitPrice'],
        $item['qty'],
        $item['unitPrice'] * $item['qty']
    ];

    $stmtItem = sqlsrv_query($conn, $sqlItem, $paramsItem);
    if (!$stmtItem) {
        die(print_r(sqlsrv_errors(), true));
    }
}

sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
            font-family: 'Poppins', 'Segoe UI', sans-serif;
        }

        .receipt-header {
            background: #212529;
            color: white;
            padding: 15px 30px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
        }

        .header-left {
            /* empty spacer */
        }

        .header-center {
            display: flex;
            justify-content: center;
        }

        .header-right {
            display: flex;
            justify-content: flex-end;
        }

        .logo-icon {
            height: 80px;
            width: auto;
        }

        .receipt-box {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .receipt-title {
            text-align: center;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 30px;
        }

        .receipt-info p {
            margin: 0;
        }

        .table thead th {
            background: #212529;
            color: white;
        }

        .total-row {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .btn-action {
            min-width: 180px;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .table td, .table th {
            vertical-align: middle;
        }

        .table-success.total-row {
            font-size: 1.25rem;
        }

        .table-success.total-row th {
            color: #155724;
        }

        .table-danger.total-row {
            font-size: 1.1rem;
        }

        .logo-icon{

            height: 90px;
            width: auto;
        }

    </style>
</head>

<body>

<!-- HEADER -->
<div class="receipt-header">
    <div class="header-left"></div>

    <div class="header-center">
        <img src="images/Escapade_Logo.png" alt="Escapade Cafe Logo" class="logo-icon">
    </div>

    <div class="header-right">
        <button class="btn btn-outline-light btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#logoutModal">
            Logout
        </button>
    </div>
</div>

<!-- RECEIPT -->
<div class="receipt-box">

    <h3 class="receipt-title">Official Receipt</h3>

    <div class="row mb-4 receipt-info">
        <div class="col-md-6">
            <p><strong>Order #:</strong> <?= $orderId ?></p>
            <p><strong>Date:</strong> <?= date("Y-m-d H:i") ?></p>
        </div>
        <div class="col-md-6 text-md-end">
            <p><strong>Served By:</strong> <?= htmlspecialchars($servedBy) ?></p>
            <p><strong>Customer:</strong> <?= htmlspecialchars($customerName) ?></p>
        </div>
    </div>

    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Item</th>
                <th>Size</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Price</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cartItems as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= $item['size'] ?? '-' ?></td>
                <td class="text-center"><?= $item['qty'] ?></td>
                <td class="text-end">₱ <?= number_format($item['unitPrice'], 2) ?></td>
                <td class="text-end">₱ <?= number_format($item['unitPrice'] * $item['qty'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-end">Subtotal</th>
                <th class="text-end">₱ <?= number_format($subtotal, 2) ?></th>
            </tr>
            <tr>
                <th colspan="4" class="text-end">Discount</th>
                <th class="text-end">− ₱ <?= number_format($discountAmount, 2) ?></th>
            </tr>
            <tr class="table-success total-row">
                <th colspan="4" class="text-end">Final Total</th>
                <th class="text-end">₱ <?= number_format($finalAmount, 2) ?></th>
            </tr>
            <?php if ($paymentMethod === 'Cash'): ?>
            <tr class="table-danger total-row">
                <th colspan="4" class="text-end">Change</th>
                <th class="text-end">₱ <?= number_format($changeAmount, 2) ?></th>
            </tr>
            <?php endif; ?>
        </tfoot>
    </table>

    <div class="text-center mt-4">
        <a href="menu.php" class="btn btn-success btn-action">New Transaction</a>
    </div>

</div>

<!-- LOGOUT MODAL -->
<div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                Are you sure you want to log out?
            </div>

            <div class="modal-footer justify-content-center">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="Landing_Page.html" class="btn btn-danger">Yes, Logout</a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
