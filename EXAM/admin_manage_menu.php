<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- Database Connection ---
$conn = sqlsrv_connect("Tys-PC\\SQLEXPRESS", ["Database" => "DLSU"]);
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// --- Image Upload Function ---
function uploadImage($key = 'product_image') {
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $allowed = ['image/jpeg', 'image/png'];
    if (!in_array($_FILES[$key]['type'], $allowed)) {
        die("Invalid image type.");
    }

    $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
    $name = uniqid('menu_', true) . '.' . $ext;
    $path = "images/" . $name;

    if (!move_uploaded_file($_FILES[$key]['tmp_name'], $path)) {
        die("Upload failed.");
    }

    return $path;
}

/* --- ADD Item Logic --- */
if (isset($_POST['add_item'])) {
    $img = uploadImage();
    sqlsrv_query(
        $conn,
        "
            INSERT INTO MENU_ITEMS
            (CATEGORY_ID, PRODUCT_NAME, S_PRICE, M_PRICE, L_PRICE, DESCRIPTION, IMG_PATH)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ",
        [
            $_POST['category_id'],
            $_POST['product_name'],
            $_POST['s_price'],
            $_POST['m_price'] ?: null,
            $_POST['l_price'] ?: null,
            $_POST['description'],
            $img
        ]
    );
}

/* --- EDIT Item Logic --- */
if (isset($_POST['edit_item'])) {
    $img = uploadImage();

    if ($img) {
        $sql = "UPDATE MENU_ITEMS SET CATEGORY_ID=?, PRODUCT_NAME=?, S_PRICE=?, M_PRICE=?, L_PRICE=?, DESCRIPTION=?, IMG_PATH=? WHERE PRODUCT_ID=?";
        $params = [
            $_POST['category_id'],
            $_POST['product_name'],
            $_POST['s_price'],
            $_POST['m_price'] ?: null,
            $_POST['l_price'] ?: null,
            $_POST['description'],
            $img,
            $_POST['product_id']
        ];
    } else {
        $sql = "UPDATE MENU_ITEMS SET CATEGORY_ID=?, PRODUCT_NAME=?, S_PRICE=?, M_PRICE=?, L_PRICE=?, DESCRIPTION=? WHERE PRODUCT_ID=?";
        $params = [
            $_POST['category_id'],
            $_POST['product_name'],
            $_POST['s_price'],
            $_POST['m_price'] ?: null,
            $_POST['l_price'] ?: null,
            $_POST['description'],
            $_POST['product_id']
        ];
    }
    sqlsrv_query($conn, $sql, $params);
}

/* --- DELETE Item Logic --- */
if (isset($_POST['delete_item'])) {
    sqlsrv_query($conn, "DELETE FROM MENU_ITEMS WHERE PRODUCT_ID=?", [$_POST['product_id']]);
}

/* --- FETCH Data --- */
$items = sqlsrv_query(
    $conn,
    "
        SELECT M.*, C.CATEGORY_NAME
        FROM MENU_ITEMS M
        JOIN MENU_CATEGORIES C ON M.CATEGORY_ID = C.CATEGORY_ID
        ORDER BY PRODUCT_ID DESC
    "
);

$cats = sqlsrv_query($conn, "SELECT * FROM MENU_CATEGORIES");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Menu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body {
    background: linear-gradient(135deg, #2b1b17, #4e342e);
    font-family: 'Poppins', sans-serif;
}
.container { max-width: 1400px; }
.card { border-radius: 20px; }
.page-title { text-align: center; font-weight: 700; color: #4e342e; }
.page-title::after {
    content:''; display:block; width:90px; height:3px;
    background:#c89b3c; margin:10px auto;
}
.toolbar { display: flex; gap: 15px; align-items: center; margin-bottom: 25px; }
.toolbar input { max-width: 260px; }
.thumb { height: 55px; border-radius: 8px; object-fit: cover; }
.table thead th { background: #2b1b17; color: #fff; }
.badge { background: #6b4f4f; }
.btn-success { background: #c89b3c; border: none; }
.btn-warning { background: #f39c12; border: none; }
.btn-danger { background: #e74c3c; border: none; }
</style>
</head>

<body>
<div class="container my-5">
    <div class="card p-4 shadow">

        <h3 class="page-title"><i class="fas fa-utensils me-2"></i>Manage Menu Items</h3>

        <div class="toolbar">
            <select id="categoryFilter" class="form-select w-auto">
                <option value="">All Categories</option>
                <?php
                sqlsrv_fetch($cats, SQLSRV_SCROLL_FIRST);
                while ($c = sqlsrv_fetch_array($cats, SQLSRV_FETCH_ASSOC)):
                ?>
                    <option value="<?= $c['CATEGORY_NAME'] ?>"><?= $c['CATEGORY_NAME'] ?></option>
                <?php endwhile; ?>
            </select>

            <input type="text" id="searchInput" class="form-control" placeholder="Search item...">

            <button class="btn btn-success ms-auto" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> Add Item
            </button>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>S</th>
                        <th>M</th>
                        <th>L</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = sqlsrv_fetch_array($items, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td>
                            <?php if ($row['IMG_PATH']): ?>
                                <img src="<?= $row['IMG_PATH'] ?>" class="thumb">
                            <?php else: ?>
                                <i class="fas fa-image text-muted"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($row['PRODUCT_NAME']) ?></strong><br>
                            <small><?= htmlspecialchars($row['DESCRIPTION']) ?></small>
                        </td>
                        <td><span class="badge"><?= $row['CATEGORY_NAME'] ?></span></td>
                        <td>₱ <?= number_format($row['S_PRICE'], 2) ?></td>
                        <td><?= $row['M_PRICE'] ? '₱ ' . number_format($row['M_PRICE'], 2) : '-' ?></td>
                        <td><?= $row['L_PRICE'] ? '₱ ' . number_format($row['L_PRICE'], 2) : '-' ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm"
                                onclick="openEditModal(
                                    <?= $row['PRODUCT_ID'] ?>,
                                    '<?= addslashes($row['PRODUCT_NAME']) ?>',
                                    <?= $row['CATEGORY_ID'] ?>,
                                    <?= $row['S_PRICE'] ?>,
                                    <?= $row['M_PRICE'] ?: 'null' ?>,
                                    <?= $row['L_PRICE'] ?: 'null' ?>,
                                    '<?= addslashes($row['DESCRIPTION'] ?? '') ?>'
                                )">
                                <i class="fas fa-edit"></i>
                            </button>

                            <button type="button"
                                    class="btn btn-danger btn-sm"
                                    onclick="openDeleteModal(<?= $row['PRODUCT_ID'] ?>, '<?= addslashes($row['PRODUCT_NAME']) ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <a href="Landing_Page.html" class="btn btn-secondary mt-3">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>

    </div>
</div>


<!--Adding an item-->

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>Add Menu Item
                </h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="addItemForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body">

                    <div class="row g-3">

                        <!-- CATEGORY -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Category <span class="text-danger">*</span>
                            </label>
                            <select id="categorySelect" name="category_id" class="form-select" required>
                                <option value="">Select category</option>
                                <option value="1">Lattes</option>
                                <option value="2">Frappes</option>
                                <option value="3">Pastas</option>
                                <option value="4">Snacks & Pastries</option>
                            </select>
                        </div>

                        <!-- PRODUCT NAME -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Product Name <span class="text-danger">*</span>
                            </label>
                            <input name="product_name" class="form-control"
                                   placeholder="e.g. Vanilla Latte"
                                   required>
                        </div>

                        <!-- PRICES -->

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Base Price *</label>
                            <input type="number" step="0.01" id="price_base" name="s_price" class="form-control" placeholder="₱ 0.00" min="0.01">
                        </div>

                        <div class="col-md-4 price-drink">
                            <label class="form-label fw-semibold">Medium Price *</label>
                            <input type="number" step="0.01" id="price_medium" name="m_price" class="form-control" placeholder="₱ 0.00" min="0.01">
                        </div>

                        <div class="col-md-4 price-drink">
                            <label class="form-label fw-semibold">Large Price *</label>
                            <input type="number" step="0.01" id="price_large" name="l_price" class="form-control" placeholder="₱ 0.00" min="0.01">
                        </div>

                        <!-- DESCRIPTION -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Description
                            </label>
                            <textarea name="description"
                                      class="form-control"
                                      rows="2"
                                      placeholder="Short description of the item"></textarea>
                        </div>

                        <!-- IMAGE -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Product Image (JPEG / PNG)
                            </label>
                            <input type="file"
                                   name="product_image"
                                   class="form-control"
                                   accept="image/png, image/jpeg">
                            <small class="text-muted">
                                Leave blank if no image
                            </small>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary px-4"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button name="add_item"
                            class="btn btn-success px-4">
                        <i class="fas fa-save me-1"></i>Save Item
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!--Edit modall-->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Menu Item
                </h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="editItemForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="edit_id">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category *</label>
                            <select name="category_id" id="edit_category" class="form-select" required>
                                <option value="1">Lattes</option>
                                <option value="2">Frappes</option>
                                <option value="3">Pastas</option>
                                <option value="4">Snacks & Pastries</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Product Name *</label>
                            <input name="product_name" id="edit_name" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Base Price *</label>
                            <input type="number" step="0.01" id="edit_base" name="s_price" class="form-control" min="0.01" required>
                        </div>

                        <div class="col-md-4 price-drink-edit">
                            <label class="form-label fw-semibold">Medium Price *</label>
                            <input type="number" step="0.01" id="edit_medium" name="m_price" class="form-control" min="0.01">
                        </div>

                        <div class="col-md-4 price-drink-edit">
                            <label class="form-label fw-semibold">Large Price *</label>
                            <input type="number" step="0.01" id="edit_large" name="l_price" class="form-control" min="0.01">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" id="edit_desc" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Replace Image (optional)</label>
                            <input type="file" name="product_image" class="form-control" accept="image/png, image/jpeg">
                            <small class="text-muted">Leave blank to keep current image</small>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button name="edit_item" class="btn btn-success px-4">
                        <i class="fas fa-save me-1"></i>Update Item
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!--Deleting stuff-->

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-trash me-2"></i>Delete Item
                </h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST">
                <div class="modal-body text-center">
                    <input type="hidden" name="product_id" id="delete_product_id">
                    <p class="mb-1">Are you sure you want to delete:</p>
                    <strong id="delete_product_name"></strong>
                    <p class="text-muted mt-2">This action cannot be undone.</p>
                </div>

                <div class="modal-footer justify-content-center">
                    <button type="button"
                            class="btn btn-secondary px-4"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button name="delete_item"
                            class="btn btn-danger px-4">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    
    const CATEGORY_IS_DRINK = {
        1: true,  // Lattes
        2: true,  // Frappes
        3: false, // Pastas
        4: false  // Snacks & Pastries
    };

    function toggleEditPrices(categoryId) {
        const isDrink = CATEGORY_IS_DRINK[categoryId];
        document.querySelectorAll('.price-drink-edit').forEach(el => {
            el.style.display = isDrink ? 'block' : 'none';
        });

        if (!isDrink) {
            document.getElementById('edit_medium').value = '';
            document.getElementById('edit_large').value = '';
        }
    }

    function openEditModal(id, name, category, base, medium, large, desc) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_category').value = category;
        document.getElementById('edit_base').value = base;
        document.getElementById('edit_medium').value = medium ?? '';
        document.getElementById('edit_large').value = large ?? '';
        document.getElementById('edit_desc').value = desc;

        toggleEditPrices(category);

        new bootstrap.Modal(document.getElementById('editModal')).show();
    }

    document.getElementById('edit_category').addEventListener('change', function () {
        toggleEditPrices(this.value);
    });

    document.getElementById('editItemForm').addEventListener('submit', function (e) {
        const category = document.getElementById('edit_category').value;
        const base = parseFloat(document.getElementById('edit_base').value);
        const medium = parseFloat(document.getElementById('edit_medium').value);
        const large = parseFloat(document.getElementById('edit_large').value);

        if (base <= 0) {
            e.preventDefault();
            alert("Price must be greater than zero.");
            return;
        }

        if (CATEGORY_IS_DRINK[category]) {
            if (medium <= base || large <= medium) {
                e.preventDefault();
                alert("Improper pricing order (Base < Medium < Large).");
            }
        }
    });

    const search = document.getElementById('searchInput');
    const filter = document.getElementById('categoryFilter');
    const rows = document.querySelectorAll('tbody tr');

    //warning toast
    function showToast(message, success = false) {
        const toastEl = document.getElementById('liveToast');
        const body = toastEl.querySelector('.toast-body');

        toastEl.classList.remove('bg-danger', 'bg-success');
        toastEl.classList.add(success ? 'bg-success' : 'bg-danger');

        body.textContent = message;
        new bootstrap.Toast(toastEl).show();
    }

    function applyFilter() {
        const s = search.value.toLowerCase();
        const c = filter.value;
        rows.forEach(r => {
            const name = r.children[1].innerText.toLowerCase();
            const cat = r.querySelector('.badge').innerText;
            r.style.display = (name.includes(s) && (!c || cat === c)) ? '' : 'none';
        });
    }
    search.addEventListener('input', applyFilter);
    filter.addEventListener('change', applyFilter);

    


    document.getElementById('addItemForm').addEventListener('submit', function (e) {

        const category = categorySelect.value;
        const name = document.querySelector('[name="product_name"]').value.trim();
        const base = parseFloat(price_base.value);
        const medium = parseFloat(price_medium.value);
        const large = parseFloat(price_large.value);

        const isDrink = CATEGORY_IS_DRINK[category];

        if (!category || !name || isNaN(base)) {
            e.preventDefault();
            showToast("Complete all required fields!");
            return;
        }

        if (base <= 0) {
            e.preventDefault();
            showToast("Price must be greater than zero.");
            return;
        }

        if (isDrink) {
            if (isNaN(medium) || isNaN(large)) {
                e.preventDefault();
                showToast("All drink prices are required.");
                return;
            }

            if (!(base < medium && medium < large)) {
                e.preventDefault();
                showToast("Improper pricing order (Base < Medium < Large).");
                return;
            }
        }
    });
    const categorySelect = document.getElementById('categorySelect');
    const drinkFields = document.querySelectorAll('.price-drink');

    function toggleDrinkPrices() {
    const isDrink = CATEGORY_IS_DRINK[categorySelect.value];

    drinkFields.forEach(el => {
        el.style.display = isDrink ? 'block' : 'none';
    });

    if (!isDrink) {
        document.getElementById('price_medium').value = '';
        document.getElementById('price_large').value = '';
    }
}

categorySelect.addEventListener('change', toggleDrinkPrices);
toggleDrinkPrices(); 


function openDeleteModal(id, name) {
    document.getElementById('delete_product_id').value = id;
    document.getElementById('delete_product_name').textContent = name;

    new bootstrap.Modal(
        document.getElementById('deleteModal')
    ).show();
}
    
</script>

</body>
</html>