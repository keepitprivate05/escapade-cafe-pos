<?php
session_start();

/* DB CONNECTION */
$conn = sqlsrv_connect("Tys-PC\\SQLEXPRESS", ["Database" => "DLSU"]);
if (!$conn) die(print_r(sqlsrv_errors(), true));

/* FILTERS */
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate   = $_GET['end_date'] ?? date('Y-m-d');
$period    = $_GET['period'] ?? 'daily';

if (!strtotime($startDate) || !strtotime($endDate)) {
    $startDate = date('Y-m-d', strtotime('-30 days'));
    $endDate   = date('Y-m-d');
}

/* PERIOD LOGIC */
switch ($period) {
    case 'weekly':
        $groupBy = "
            DATEPART(YEAR, ORDER_DATE),
            DATEPART(WEEK, ORDER_DATE)
        ";
        $label = "
            CAST(DATEPART(YEAR, MIN(ORDER_DATE)) AS VARCHAR)
            + '-W'
            + RIGHT('0' + CAST(DATEPART(WEEK, MIN(ORDER_DATE)) AS VARCHAR), 2)
        ";
        break;

    case 'monthly':
        $groupBy = "
            DATEPART(YEAR, ORDER_DATE),
            DATEPART(MONTH, ORDER_DATE)
        ";
        $label = "
            CAST(DATEPART(YEAR, MIN(ORDER_DATE)) AS VARCHAR)
            + '-'
            + RIGHT('0' + CAST(DATEPART(MONTH, MIN(ORDER_DATE)) AS VARCHAR), 2)
        ";
        break;

    default: // daily
        $groupBy = "CAST(ORDER_DATE AS DATE)";
        $label   = "CONVERT(VARCHAR(10), MIN(ORDER_DATE), 23)";
}

/* SALES DATA */
$sales = [];
$q1 = sqlsrv_query($conn, "
    SELECT 
        {$label} AS label,
        SUM(FINAL_AMOUNT) AS total
    FROM ORDERS
    WHERE CAST(ORDER_DATE AS DATE) BETWEEN ? AND ?
    GROUP BY {$groupBy}
    ORDER BY MIN(ORDER_DATE)
", [$startDate, $endDate]);

if ($q1 === false) die(print_r(sqlsrv_errors(), true));
while ($r = sqlsrv_fetch_array($q1, SQLSRV_FETCH_ASSOC)) $sales[] = $r;

/* TOP PRODUCTS */
$products = [];
$q2 = sqlsrv_query($conn, "
    SELECT TOP 5 PRODUCT_NAME, SUM(QUANTITY) qty
    FROM ORDER_ITEMS OI
    JOIN ORDERS O ON OI.ORDER_ID = O.ORDER_ID
    WHERE CAST(O.ORDER_DATE AS DATE) BETWEEN ? AND ?
    GROUP BY PRODUCT_NAME
    ORDER BY qty DESC
", [$startDate, $endDate]);

if ($q2 === false) die(print_r(sqlsrv_errors(), true));
while ($r = sqlsrv_fetch_array($q2, SQLSRV_FETCH_ASSOC)) $products[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sales Report</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{
    background:linear-gradient(135deg,#2b1b17,#4e342e);
    font-family:Poppins,sans-serif;
}
.container{max-width:1300px;}
.card{
    border-radius:22px;
    border:none;
    box-shadow:0 25px 60px rgba(0,0,0,.2);
}
.page-title{
    text-align:center;
    font-weight:700;
    color:#4e342e;
}
.page-title::after{
    content:'';
    display:block;
    width:80px;
    height:3px;
    background:#c89b3c;
    margin:8px auto 0;
}
.filter-box{
    background:#fafafa;
    border-radius:16px;
    padding:16px 18px;
    margin-bottom:20px;
}
.chart-box{
    background:#fbf7f4;
    border-radius:16px;
    padding:18px 20px;
    height:100%;
}
.chart-wrap{height:260px;}
.chart-wrap-sm{height:230px;}
.btn-primary{
    background:#c89b3c;
    border:none;
    border-radius:25px;
}
.btn-secondary{
    background:#6b4f4f;
    border:none;
    border-radius:25px;
}
</style>
</head>

<body>
<div class="container my-5">
<div class="card p-4">

<h4 class="page-title mb-4">
    <i class="fas fa-chart-line me-2"></i>Sales Report
</h4>

<!-- FILTERS -->
<div class="filter-box">
<form method="GET" class="row g-3 align-items-end">
    <div class="col-md-3">
        <label class="form-label">Start Date</label>
        <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">End Date</label>
        <input type="date" name="end_date" class="form-control" value="<?= $endDate ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Period</label>
        <select name="period" class="form-select">
            <option value="daily" <?= $period=='daily'?'selected':'' ?>>Daily</option>
            <option value="weekly" <?= $period=='weekly'?'selected':'' ?>>Weekly</option>
            <option value="monthly" <?= $period=='monthly'?'selected':'' ?>>Monthly</option>
        </select>
    </div>
    <div class="col-md-3">
        <button class="btn btn-primary w-100">
            <i class="fas fa-filter me-1"></i>Apply Filters
        </button>
    </div>
</form>
</div>

<!-- CHARTS -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="chart-box">
            <h6 class="fw-semibold text-muted mb-2">SALES OVERVIEW</h6>
            <div class="chart-wrap">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="chart-box">
            <h6 class="fw-semibold text-muted mb-2">TOP 5 PRODUCTS SOLD</h6>
            <div class="chart-wrap-sm">
                <canvas id="productChart"></canvas>
            </div>
        </div>
    </div>
</div>

<a href="Landing_Page.html" class="btn btn-secondary w-100 mt-4">
    <i class="fas fa-arrow-left me-2"></i>Go Back
</a>

</div>
</div>

<script>
const salesLabels = <?= json_encode(array_column($sales,'label')) ?>;
const salesData   = <?= json_encode(array_column($sales,'total')) ?>;

new Chart(document.getElementById('salesChart'),{
    type:'bar',
    data:{
        labels:salesLabels,
        datasets:[{
            label:'Total Sales (PHP)',
            data:salesData,
            backgroundColor:'#c89b3c'
        }]
    },
    options:{
        animation:{duration:1200,easing:'easeOutQuart'},
        responsive:true,
        maintainAspectRatio:false,
        scales:{
            y:{
                beginAtZero:true,
                ticks:{callback:v=>'₱'+v.toLocaleString()}
            }
        }
    }
});

new Chart(document.getElementById('productChart'),{
    type:'doughnut',
    data:{
        labels:<?= json_encode(array_column($products,'PRODUCT_NAME')) ?>,
        datasets:[{
            data:<?= json_encode(array_column($products,'qty')) ?>,
            backgroundColor:['#c89b3c','#8d6e63','#4e342e','#2b1b17','#6b4f4f']
        }]
    },
    options:{
        cutout:'70%',
        animation:{duration:1200},
        responsive:true,
        maintainAspectRatio:false,
        plugins:{legend:{position:'bottom'}}
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
