<?php 

header('Content-Type: application/json');

$serverName="Tys-PC\SQLEXPRESS";
$connectionOptions=[
    "Database"=>"DLSU",
    "Uid"=>"",
    "PWD"=>""
];
$conn=sqlsrv_connect($serverName, $connectionOptions);

if($conn===false){ 
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
} 

// Get Submitted Data
$cashName = trim($_POST['cashier_name']);
$cashPass = $_POST['cashier_password'];


$sql_cashGetter = "SELECT CASHIER_NAME, CASHIER_PASSWORD FROM CASHIERS_TABLE WHERE CASHIER_NAME = '$cashName'";

$stmt = sqlsrv_query($conn, $sql_cashGetter);

if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Query failed: ' . print_r(sqlsrv_errors(), true)]);
    sqlsrv_close($conn);
    exit;
}


if (sqlsrv_fetch($stmt) !== false) { 
    
    
    // Get pass
    $PassGet = sqlsrv_get_field($stmt, 1); 
    
    // Compare passwords
    if ($cashPass === $PassGet) {
        
        session_start();
        $_SESSION['loggedin'] = true;
        $_SESSION['user_name'] = $cashName;
        $_SESSION['user_role'] = 'Cashier';

        
        echo json_encode([
        'status' => 'success', 
        'message' => 'Sign-in Successful!', 
        'name' => $cashName 
    ]);
        
    } else {
        // Password mismatch
        echo json_encode(['status' => 'failure', 'reason' => 'password']);
    }
} else {
    // Cashier username not found
    echo json_encode(['status' => 'failure', 'reason' => 'user']);
}

// Cleanup
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>