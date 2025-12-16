<?php
session_start();


/* VALIDATION  */

if (!isset($_POST['cartData'])) {
    header("Location: menu.php");
    exit;
}

$cartItems = json_decode($_POST['cartData'], true);
if (!$cartItems || count($cartItems) === 0) {
    header("Location: menu.php");
    exit;
}


/* SUBTOTAL  */

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['unitPrice'] * $item['qty'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #2b1b17, #4e342e);
            min-height: 100vh;
        }

        .table-success {
            font-size: 1.1rem;
        }

        .total-amount {
            font-weight: 700;
            transition: background-color 0.25s ease, transform 0.2s ease;
        }

        .total-amount.flash {
            background-color: #d1e7dd;
            transform: scale(1.05);
        }

        .discard-btn {
            background: linear-gradient(135deg, #6c757d, #495057);
            border: none;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .discard-btn:hover {
            background: linear-gradient(135deg, #dc3545, #b02a37);
            color: #fff;
        }

        .btn-success {
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Modal */
        #discardModal .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 15px 40px rgba(0,0,0,0.35);
            overflow: hidden;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        #discardModal .modal-header {
            background: linear-gradient(135deg, #dc3545, #a71d2a);
            color: #fff;
            border-bottom: none;
            padding: 20px 24px;
        }

        #discardModal .modal-title {
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        #discardModal .modal-body {
            padding: 28px 24px;
            font-size: 1.05rem;
            color: #333;
        }

        #discardModal .modal-body i {
            font-size: 2.5rem;
            color: #dc3545;
            margin-bottom: 15px;
        }

        #discardModal .modal-footer {
            border-top: none;
            padding: 20px;
            gap: 12px;
        }

        /* Buttons */
        #discardModal .btn-secondary {
            background: #e9ecef;
            color: #333;
            border: none;
            padding: 10px 22px;
            font-weight: 600;
            border-radius: 8px;
        }

        #discardModal .btn-secondary:hover {
            background: #ced4da;
        }

        #discardModal .btn-danger {
            background: linear-gradient(135deg, #dc3545, #b02a37);
            border: none;
            padding: 10px 26px;
            font-weight: 700;
            border-radius: 8px;
            box-shadow: 0 6px 15px rgba(220,53,69,0.35);
        }

        #discardModal .btn-danger:hover {
            background: linear-gradient(135deg, #bb2d3b, #842029);
        }

        .modal.fade .modal-dialog {
            transform: scale(0.95);
            transition: transform 0.2s ease-out;
        }

        .modal.show .modal-dialog {
            transform: scale(1);
        }

    </style>
</head>

<body>

<div class="container my-5">
    <div class="card shadow-lg p-4">

        <h3 class="text-center mb-4">
            <i class="fas fa-shopping-cart me-2"></i>Checkout
        </h3>

        <div class="row g-4">

            <!-- ORDER SUMMARY -->
            <div class="col-md-7">
                <table class="table align-middle">
                    <thead class="table-dark">
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
                        <tr id="discountRow" style="display:none">
                            <th colspan="4" class="text-end">Discount</th>
                            <th class="text-end text-danger">
                                − ₱ <span id="discountDisplay">0.00</span>
                            </th>
                        </tr>
                        <tr class="table-success fw-bold">
                            <th colspan="4" class="text-end">Final Total</th>
                            <th class="text-end">
                                ₱ <span id="finalTotalDisplay"><?= number_format($subtotal, 2) ?></span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- PAYMENT -->
            <div class="col-md-5">
                <form method="POST" action="receipt.php" onsubmit="return validatePayment()">

                    <input type="hidden" name="cartData" value="<?= htmlspecialchars($_POST['cartData']) ?>">

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user me-2"></i>Customer Name (optional)
                        </label>
                        <input type="text" name="customer_name" class="form-control" placeholder="Average Joe">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-credit-card me-2"></i>Payment Method
                        </label>
                        <select name="payment_method" id="paymentMethod" class="form-select">
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-percent me-2"></i>Discount
                        </label>
                        <div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="discount_type" value="None" checked>
                                <label class="form-check-label">No Discount</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="discount_type" value="PWD">
                                <label class="form-check-label">PWD (20%)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="discount_type" value="Senior">
                                <label class="form-check-label">Senior Citizen (20%)</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="cashSection">
                        <label class="form-label">
                            <i class="fas fa-money-bill-wave me-2"></i>Cash Given
                        </label>
                        <input type="number" step="0.01" name="cash_given" id="cashGiven" class="form-control">
                    </div>

                    <div class="mb-3" id="changeSection">
                        <label class="form-label">
                            <i class="fas fa-exchange-alt me-2"></i>Change
                        </label>
                        <input type="text" id="changeBox" class="form-control" readonly>
                    </div>

                    <button type="submit" class="btn btn-success w-100 mb-2">
                        <i class="fas fa-receipt me-2"></i>Generate Receipt
                    </button>

                    <button
                        type="button"
                        class="btn btn-secondary w-100 discard-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#discardModal">
                        <i class="fas fa-trash me-2"></i>Discard
                    </button>

                </form>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="discardModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-triangle-exclamation me-2"></i>
                    Trash Current Transaction?
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                <i class="fas fa-trash-alt"></i>
                <p class="mt-3 mb-0">
                    This will permanently discard the current transaction.<br>
                    <strong>This action cannot be undone.</strong>
                </p>
            </div>

            <div class="modal-footer justify-content-center">
                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">
                    No, Keep It
                </button>

                <button
                    type="button"
                    class="btn btn-danger"
                    onclick="discardTransaction()">
                    Yes, Discard
                </button>
            </div>

        </div>
    </div>
</div>

<!--Toast message for discard-->

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="cancelToast" class="toast align-items-center text-bg-success" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                Transaction successfully cancelled.
            </div>
            <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const baseSubtotal = <?= $subtotal ?>;


    /* COMPUTE TOTAL */

    function computeTotal() {

        let discountRate = 0;

        const discount = document.querySelector(
            'input[name="discount_type"]:checked'
        )?.value;

        if (discount === "PWD" || discount === "Senior") {
            discountRate = 0.20;
        }

        const discountAmount = baseSubtotal * discountRate;
        const finalTotal = baseSubtotal - discountAmount;

        document.getElementById("discountDisplay").textContent =
            discountAmount.toFixed(2);

        document.getElementById("finalTotalDisplay").textContent =
            finalTotal.toFixed(2);

        document.getElementById("discountRow").style.display =
            discountRate > 0 ? "" : "none";

        // animate
        const finalEl = document.getElementById("finalTotalDisplay");
        finalEl.classList.add("flash");
        setTimeout(() => finalEl.classList.remove("flash"), 200);

        return finalTotal; 
    }

    function updateChange() {
        const finalTotal = computeTotal();
        const cash = parseFloat(document.getElementById('cashGiven').value || 0);
        const change = Math.max(0, cash - finalTotal);

        document.getElementById('changeBox').value =
            `₱ ${change.toFixed(2)}`;
    }


    /* VALIDATION */

    function validatePayment() {
        const method = document.getElementById('paymentMethod').value;
        const cash = parseFloat(document.getElementById('cashGiven').value || 0);
        const total = computeTotal();

        if (method === 'Cash' && cash < total) {
            alert('Insufficient cash.');
            return false;
        }
        return true;
    }


    /* DISCARD  */

    function discardTransaction() {
        const toast = new bootstrap.Toast(document.getElementById("cancelToast"));
        toast.show();

        setTimeout(() => {
            window.location.href = "menu.php";
        }, 1200);
    }


    /* EVENTS */

    document.getElementById('paymentMethod').addEventListener('change', function () {
        const isCash = this.value === 'Cash';
        document.getElementById('cashSection').style.display = isCash ? 'block' : 'none';
        document.getElementById('changeSection').style.display = isCash ? 'block' : 'none';
        computeTotal();
    });

    document.getElementById('cashGiven').addEventListener('input', updateChange);

    document.querySelectorAll('input[name="discount_type"]').forEach(radio =>
        radio.addEventListener('change', computeTotal)
    );

    // initial render
    computeTotal();
</script>


</body>
</html>
