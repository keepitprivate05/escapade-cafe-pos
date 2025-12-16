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
$adName = trim($_POST['admin_name']);
$adPass = $_POST['admin_password'];


$sql_adminGetter = "SELECT ADMIN_NAME, ADMIN_PASSWORD FROM ADMINS_TABLE WHERE ADMIN_NAME = '$adName'";

$stmt = sqlsrv_query($conn, $sql_adminGetter);

if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Query failed: ' . print_r(sqlsrv_errors(), true)]);
    sqlsrv_close($conn);
    exit;
}


if (sqlsrv_fetch($stmt) !== false) { 
    
    
    // Get pass
    $PassGet = sqlsrv_get_field($stmt, 1); 
    
    // Compare passwords
    if ($adPass === $PassGet) {
   
        session_start();
        $_SESSION['loggedin'] = true;
        $_SESSION['user_name'] = $adName;
        $_SESSION['user_role'] = 'Admin';
        
        echo json_encode([
        'status' => 'success', 
        'message' => 'Sign-in Successful!', 
        'name' => $adName 
    ]);
        
    } else {
        // DENY ENTRY: Password mismatch
        echo json_encode(['status' => 'failure', 'reason' => 'password']);
    }
} else {
    // Admin username not found
    echo json_encode(['status' => 'failure', 'reason' => 'user']);
}

// Cleanup
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>