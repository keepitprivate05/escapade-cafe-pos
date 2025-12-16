<?php
session_start();

// Save logged-in user
$clerkName = $_SESSION['user_name'] ?? 'Cashier';
$userRole = $_SESSION['user_role'] ?? 'Cashier';

// DB connection
$serverName = "Tys-PC\\SQLEXPRESS";
$connectionOptions = ["Database" => "DLSU"];
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Fetch menu
$sql = "
SELECT M.*, C.IS_DRINK_TYPE
FROM dbo.MENU_ITEMS M
JOIN dbo.MENU_CATEGORIES C ON M.CATEGORY_ID = C.CATEGORY_ID
ORDER BY M.CATEGORY_ID, M.PRODUCT_ID
";

$stmt = sqlsrv_query($conn, $sql);
$menuItems = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $menuItems[] = $row;
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <title>Escapade Cafe - Take Order</title>

    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="menu.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <script src="https://kit.fontawesome.com/6d7d3494f0.js"></script>
    <style>
        
        .menu-top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #2b1b17, #4e342e);
            padding: 14px 28px;
            border-radius: 18px;
            margin-bottom: 22px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }

        .menu-top-left {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
        }

        .menu-top-left img {
            height: 42px;
        }

        .menu-top-title {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .menu-top-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .menu-user-badge {
            color: #fff;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .menu-back-btn {
            background: linear-gradient(135deg, #c89b3c, #8d6e63);
            color: #fff;
            border-radius: 25px;
            padding: 8px 18px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .menu-back-btn:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(200,155,60,0.45);
        }
    </style>

</head>

<body class="menu-body">

<div class="menu-page-wrapper">
    <header class="menu-top-header">

        <div class="menu-top-left">
            <span class="menu-top-title">WELCOME!</span>
        </div>

        <div class="menu-top-right">
            <span class="menu-user-badge">
                <i class="fas fa-user me-1"></i>
                <?= htmlspecialchars($clerkName) ?> (<?= htmlspecialchars($userRole) ?>)
            </span>

            <a href="Landing_Page.html" class="menu-back-btn">
                <i class="fas fa-arrow-left me-1"></i> Home
            </a>
        </div>

    </header>

<header class="menu-banner">
    <div class="banner-content">
        <h1 class="banner-title">MENU</h1>
    </div>
</header>

<div class="menu-nav-bar-container">

    <div class="search-bar-static-group">
        <i class="fas fa-search search-icon-static"></i>
        <input type="text" id="searchBox" class="form-control menu-search-input-static" placeholder="Search menu items...">
        <div id="searchSuggestions" class="search-suggestion-box"></div>
    </div>

    <div class="category-nav-container-static">
        <div class="category-nav">
            <button class="category-btn active" data-category="all">All</button>
            <button class="category-btn" data-category="1">Lattes</button>
            <button class="category-btn" data-category="2">Frappes</button>
            <button class="category-btn" data-category="3">Pastas</button>
            <button class="category-btn" data-category="4">Snacks & Pastries</button>
        </div>
    </div>
</div>

<main class="order-interface-container">

    <section class="menu-content-panel">

        <h2 class="category-header">All</h2>

        <!--Pag no items match the search, show this message-->
        <div id="noResultBox" class="no-result-box">
            <p class="no-result-text">
                That item is not available.
            </p>
            <button id="backToMenuBtn" class="no-result-btn">
                Go back to Menu
            </button>
        </div>

        <div class="item-display-grid">

            <?php foreach ($menuItems as $item): ?>
            <div class="menu-item-card"

                    data-productid="<?= $item['PRODUCT_ID'] ?>"
                    data-category="<?= $item['CATEGORY_ID'] ?>"
                    data-name="<?= htmlspecialchars($item['PRODUCT_NAME']) ?>"
                    data-description="<?= htmlspecialchars($item['DESCRIPTION']) ?>"
                    data-sprice="<?= $item['S_PRICE'] ?>"
                    data-mprice="<?= $item['M_PRICE'] ?>"
                    data-lprice="<?= $item['L_PRICE'] ?>"
                    data-img="<?= $item['IMG_PATH'] ?>"
                    data-isdrink="<?= $item['IS_DRINK_TYPE'] ?>"

                    onclick="openItemModal(this)"
                >

                <div class="card-image-box">
                    <img src="<?= $item['IMG_PATH'] ?>" class="item-image">
                </div>

                <div class="card-content">
                    <p class="item-name"><?= $item['PRODUCT_NAME'] ?></p>
                    <p class="item-price-label">₱ <?= number_format($item['S_PRICE'], 2) ?> (Tap to order)</p>
                </div>

            </div>
            <?php endforeach; ?>

        </div>

    </section>


        <!-- CART SIDEBAR -->
        <aside class="cart-sidebar-panel">

            <h3 class="cart-title">Your Cart</h3>

            <!-- Cart Content Area -->
            <div id="cartBox" class="cart-content-area">
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart large-cart-icon"></i>
                    <p class="empty-cart-text">Your cart is empty.<br>Add some items.</p>
                </div>
            </div>

            <!-- Subtotal -->
            <div class="cart-subtotal-area">
                <p class="subtotal-label">Subtotal:</p>
                <p class="subtotal-value">₱ <span id="cartSubtotal">0.00</span></p>
            </div>

            <form action="checkout.php" method="POST" onsubmit="return allowCheckout();">
                <input type="hidden" name="cartData" id="cartDataHidden">
                <button class="btn btn-checkout" type="submit">Go to Checkout ></button>
            </form>

        </aside>
    </main>

    </div>

        <!-- REMOVE ITEM CONFIRM MODAL -->
        <div class="modal fade" id="removeItemModal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content item-modal">

                    <div class="modal-header">
                        <h5 class="modal-title">Remove Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body text-center">
                        <p class="modal-item-desc">
                            Remove <span id="removeItemLabel" style="font-weight:700;"></span> from cart?
                        </p>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button id="confirmRemoveBtn" class="btn btn-danger">Remove</button>
                    </div>

                </div>
            </div>
        </div>
    </main>

</div>

<!-- ============================ -->
<!-- DRINK MODAL -->
<!-- ============================ -->
<div class="modal fade" id="drinkModal">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content item-modal">

            <div class="modal-header">
                <h5 class="modal-title" id="drinkTitle"></h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body modal-flex">
                
                <div class="modal-left">
                    <img id="drinkImg" class="modal-image">
                </div>

                <div class="modal-right">

                    <p id="drinkDesc" class="modal-item-desc"></p>

                    <div class="modal-section">
                        <h6 class="modal-label">Select Size</h6>
                        <div class="size-btn-group">
                            <button class="size-btn" data-size="S_PRICE">Small</button>
                            <button class="size-btn" data-size="M_PRICE">Medium</button>
                            <button class="size-btn" data-size="L_PRICE">Large</button>
                        </div>
                    </div>

                    <div class="modal-section">
                        <h6 class="modal-label">Quantity</h6>
                        <div class="qty-box">
                            <button class="qty-btn" id="drinkMinusBtn">−</button>
                            <span id="drinkQty" class="qty-value">1</span>
                            <button class="qty-btn" id="drinkPlusBtn">+</button>
                        </div>
                    </div>

                    <p class="modal-price">Price: ₱ <span id="drinkPrice">0.00</span></p>

                    <button class="btn modal-add-cart" id="addDrinkCartBtn">Add to Cart</button>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ============================ -->
<!-- FOOD MODAL -->
<!-- ============================ -->
<div class="modal fade" id="foodModal">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content item-modal">

            <div class="modal-header">
                <h5 class="modal-title" id="foodTitle"></h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body modal-flex">

                <div class="modal-left">
                    <img id="foodImg" class="modal-image">
                </div>

                <div class="modal-right">

                    <p id="foodDesc" class="modal-item-desc"></p>

                    <div class="modal-section">
                        <h6 class="modal-label">Quantity</h6>
                        <div class="qty-box">
                            <button class="qty-btn" id="foodMinusBtn">−</button>
                            <span id="foodQty" class="qty-value">1</span>
                            <button class="qty-btn" id="foodPlusBtn">+</button>
                        </div>
                    </div>

                    <p class="modal-price">Price: ₱ <span id="foodPrice">0.00</span></p>

                    <button class="btn modal-add-cart" id="addFoodCartBtn">Add to Cart</button>

                </div>

            </div>
        </div>
    </div>
</div>

<!-- ============================ -->
<!-- JAVASCRIPT -->
<!-- ============================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<script>

/* global variables */

let itemCards = [];
let cartItems = [];
let itemToDeleteIndex = null;
let suggestionPicked = false;
let currentProductId = null;

/* ----------------------------- */
/* INITIAL LOAD */
/* ----------------------------- */
document.addEventListener("DOMContentLoaded", () => {
    itemCards = Array.from(document.querySelectorAll(".menu-item-card"));
    document.querySelector('.category-btn[data-category="all"]').click();
});

/* ----------------------------- */
/* CATEGORY FILTERING */
/* ----------------------------- */
function loadAllCategories() {
    const grid = document.querySelector(".item-display-grid");
    grid.innerHTML = "";

    const categoryNames = {
        1: "Lattes",
        2: "Frappes",
        3: "Pastas",
        4: "Snacks & Pastries"
    };

    Object.keys(categoryNames).forEach(cat => {
        grid.insertAdjacentHTML("beforeend", `<h2 class="category-subtitle">${categoryNames[cat]}</h2>`);
        itemCards.forEach(card => {
            if (card.dataset.category == cat) grid.appendChild(card);
        });
        grid.insertAdjacentHTML("beforeend", `<div class="category-spacer"></div>`);
    });
}

document.querySelectorAll(".category-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        document.querySelectorAll(".category-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        const chosen = btn.dataset.category;
        const grid = document.querySelector(".item-display-grid");
        const title = document.querySelector(".category-header");

        if (chosen === "all") {
            title.textContent = "All";
            loadAllCategories();
            return;
        }

        title.textContent = btn.textContent;
        grid.innerHTML = "";

        itemCards.forEach(card => {
            if (card.dataset.category === chosen) grid.appendChild(card);
        });
    });
});

/* ----------------------------- */
/* ITEM MODALS */
/* ----------------------------- */
let drinkUnitPrice = 0;
let foodUnitPrice = 0;

function resetModals() {
    document.querySelectorAll(".size-btn").forEach(b => b.classList.remove("active"));
    drinkQty.textContent = 1;
    foodQty.textContent = 1;
}

function openItemModal(card) {
    resetModals();

    currentProductId = card.dataset.productid; 

    const isDrink = card.dataset.isdrink === "1";
    const name = card.dataset.name;
    const desc = card.dataset.description;
    const img = card.dataset.img;

    const priceS = parseFloat(card.dataset.sprice);
    const priceM = parseFloat(card.dataset.mprice);
    const priceL = parseFloat(card.dataset.lprice);

    if (isDrink) {
        drinkTitle.textContent = name;
        drinkDesc.textContent = desc;
        drinkImg.src = img;

        document.querySelectorAll(".size-btn").forEach(btn => {
            btn.onclick = () => {
                document.querySelectorAll(".size-btn").forEach(b => b.classList.remove("active"));
                btn.classList.add("active");

                drinkUnitPrice =
                    btn.dataset.size === "S_PRICE" ? priceS :
                    btn.dataset.size === "M_PRICE" ? priceM :
                    priceL;

                drinkPrice.textContent = (drinkUnitPrice * +drinkQty.textContent).toFixed(2);
            };
        });

        document.querySelector('.size-btn[data-size="S_PRICE"]').click();

        new bootstrap.Modal(document.getElementById("drinkModal")).show();
        return;
    }

    foodTitle.textContent = name;
    foodDesc.textContent = desc;
    foodImg.src = img;

    foodUnitPrice = priceS;
    foodPrice.textContent = priceS.toFixed(2);

    new bootstrap.Modal(document.getElementById("foodModal")).show();
}

/* ----------------------------- */
/* QTY BUTTONS */
/* ----------------------------- */
function keepMin1(n) { return Math.max(1, n); }

/* DRINK */
drinkPlusBtn.onclick = () => {
    let qty = keepMin1(+drinkQty.textContent + 1);
    drinkQty.textContent = qty;
    drinkPrice.textContent = (drinkUnitPrice * qty).toFixed(2);
};

drinkMinusBtn.onclick = () => {
    let qty = keepMin1(+drinkQty.textContent - 1);
    drinkQty.textContent = qty;
    drinkPrice.textContent = (drinkUnitPrice * qty).toFixed(2);
};

/* FOOD */
foodPlusBtn.onclick = () => {
    let qty = keepMin1(+foodQty.textContent + 1);
    foodQty.textContent = qty;
    foodPrice.textContent = (foodUnitPrice * qty).toFixed(2);
};

foodMinusBtn.onclick = () => {
    let qty = keepMin1(+foodQty.textContent - 1);
    foodQty.textContent = qty;
    foodPrice.textContent = (foodUnitPrice * qty).toFixed(2);
};

/* ----------------------------- */
/* ADD TO CART */
/* ----------------------------- */
function addItemToCart(productId, name, size, unitPrice, qty) {
    const match = cartItems.find(item => item.name === name && item.size === size);

    if (match) {
        match.qty += qty;
    } else {
        cartItems.push({ productId, name, size, unitPrice, qty });
    }

    renderCart();
}

function allowCheckout() {
    if (cartItems.length === 0) {
        alert("Your cart is empty.");
        return false;
    }
    return true;
}

/* DRINK ADD */
document.getElementById("addDrinkCartBtn").onclick = () => {
    const name = drinkTitle.textContent;
    const qty = +drinkQty.textContent;
    const sizeBtn = document.querySelector(".size-btn.active");
    const size = sizeBtn ? sizeBtn.textContent : null;

    addItemToCart(currentProductId, name, size, drinkUnitPrice, qty);

    bootstrap.Modal.getInstance(document.getElementById("drinkModal")).hide();
};

/* FOOD ADD */
document.getElementById("addFoodCartBtn").onclick = () => {
    const name = foodTitle.textContent;
    const qty = +foodQty.textContent;

    addItemToCart(currentProductId, name, null, foodUnitPrice, qty);

    bootstrap.Modal.getInstance(document.getElementById("foodModal")).hide();
};


/* RENDER CART */

function renderCart() {
    const cartBox = document.getElementById("cartBox");
    cartBox.innerHTML = "";
    const subtotalDisplay = document.getElementById("cartSubtotal");

    if (cartItems.length === 0) {
        cartBox.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart large-cart-icon"></i>
                <p class="empty-cart-text">Your cart is empty.<br>Add some items.</p>
            </div>
        `;
        subtotalDisplay.textContent = "0.00";
        return;
    }

    let subtotal = 0;

    cartItems.forEach((item, index) => {
        let fullName = item.size ? `${item.size} ${item.name}` : item.name;
        let shortName = fullName.length > 22 ? fullName.slice(0, 22) + "…" : fullName;

        const lineTotal = item.unitPrice * item.qty;
        subtotal += lineTotal;

        cartBox.insertAdjacentHTML("beforeend", `
            <div class="cart-row">

                <p class="cart-item-name">${shortName}</p>

                <div class="cart-line">

                    <span class="cart-price">₱ ${lineTotal.toFixed(2)}</span>

                    <div class="qty-box-inline">
                        <button class="qty-btn" onclick="changeCartQty(${index}, -1)">−</button>
                        <span class="qty-value">${item.qty}</span>
                        <button class="qty-btn" onclick="changeCartQty(${index}, 1)">+</button>
                    </div>

                    <button class="remove-btn" onclick="openRemoveItem(${index})">
                        <i class="fas fa-trash"></i>
                    </button>

                </div>

            </div>
        `);
    });

    subtotalDisplay.textContent = subtotal.toFixed(2);

    document.getElementById("cartDataHidden").value = JSON.stringify(cartItems);
}


/* ADJUST CART QUANTITY */

function changeCartQty(index, change) {
    cartItems[index].qty += change;

    if (cartItems[index].qty < 1) {
        openRemoveItem(index);
        return;
    }

    renderCart();
}


/* REMOVE ITEM */

function openRemoveItem(index) {
    itemToDeleteIndex = index;

    const item = cartItems[index];
    const fullLabel = item.size ? `${item.size} ${item.name}` : item.name;

    document.getElementById("removeItemLabel").textContent = fullLabel;

    new bootstrap.Modal(document.getElementById("removeItemModal")).show();
}

document.getElementById("confirmRemoveBtn").onclick = () => {
    cartItems.splice(itemToDeleteIndex, 1);
    itemToDeleteIndex = null;

    renderCart();
    bootstrap.Modal.getInstance(document.getElementById("removeItemModal")).hide();
};
</script>
<script>

/* SEARCH FILTER functionality */

const searchInput = document.getElementById("searchBox");
const suggestionBox = document.getElementById("searchSuggestions");
const noResultBox = document.getElementById("noResultBox");
const backToMenuBtn = document.getElementById("backToMenuBtn");

searchInput.addEventListener("input", () => {

    if (suggestionPicked) {
        suggestionPicked = false;
        return;
    }

    const keyword = searchInput.value.trim().toLowerCase();
    const activeCategory = document.querySelector(".category-btn.active").dataset.category;

    suggestionBox.innerHTML = "";
    suggestionBox.style.display = "none";

    if (keyword.length >= 3) {
        const matches = [];

        itemCards.forEach(card => {
            const name = card.dataset.name;
            if (name.toLowerCase().includes(keyword)) {
                matches.push(name);
            }
        });

        const uniqueMatches = [...new Set(matches)].slice(0, 6);

        if (uniqueMatches.length) {
            uniqueMatches.forEach(name => {
                const div = document.createElement("div");
                div.className = "search-suggestion-item";
                div.textContent = name;
                div.onclick = () => {
                    suggestionPicked = true;
                    searchInput.value = name;
                    suggestionBox.innerHTML = "";
                    suggestionBox.style.display = "none";

                    filterMenuResults(
                        name.toLowerCase(),
                        document.querySelector(".category-btn.active").dataset.category
                    );
                };
                suggestionBox.appendChild(div);
            });
            suggestionBox.style.display = "block";
        }
    }

    filterMenuResults(keyword, activeCategory);
});

function filterMenuResults(keyword, activeCategory) {
    const grid = document.querySelector(".item-display-grid");
    grid.innerHTML = "";
    let matchCount = 0;

    noResultBox.style.display = "none";

    const categoryNames = {
        1: "Lattes",
        2: "Frappes",
        3: "Pastas",
        4: "Snacks & Pastries"
    };

    if (activeCategory === "all") {
        Object.keys(categoryNames).forEach(cat => {
            let sectionHasMatch = false;

            itemCards.forEach(card => {
                const name = card.dataset.name.toLowerCase();
            


                    if (
                        card.dataset.category == cat &&
                        name.includes(keyword)
                    ) {
                    if (!sectionHasMatch) {
                        grid.insertAdjacentHTML(
                            "beforeend",
                            `<h2 class="category-subtitle">${categoryNames[cat]}</h2>`
                        );
                        sectionHasMatch = true;
                    }
                    grid.appendChild(card);
                    matchCount++;
                }
            });

            if (sectionHasMatch) {
                grid.insertAdjacentHTML("beforeend", `<div class="category-spacer"></div>`);
            }
        });
    } else {
        itemCards.forEach(card => {
            const name = card.dataset.name.toLowerCase();
            
            if (
                card.dataset.category === activeCategory &&
                name.includes(keyword)
            ) {
                grid.appendChild(card);
                matchCount++;
            }
        });
    }

    if (matchCount === 0 && keyword !== "") {
        noResultBox.style.display = "block";
    }
}

backToMenuBtn.onclick = () => {
    searchInput.value = "";
    suggestionBox.style.display = "none";
    noResultBox.style.display = "none";

    document.querySelector(".item-display-grid").style.display = "grid";
    document.querySelector('.category-btn[data-category="all"]').click();
};
</script>


</body>
</html>
