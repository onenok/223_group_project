<?php
// DB info
$servername = "localhost";
$username = "root";
$password = "";
$db_name = "lab6";
 
// create connection to DB
$conn = new mysqli($servername, $username, $password, $db_name);
 
// check if connection works
if ($conn->connect_error) {
    die("connect failed: " . $conn->connect_error);
}

// Ensure a per-session CSRF token exists when session is active
if (session_status() === PHP_SESSION_ACTIVE) {
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // fallback to less-preferred method if random_bytes fails
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
}

// prepared statement for safe queries
function safeQuery($sql, $types = null, $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    $response = new stdClass(); // Create empty object to pack data

    // if SQL prepare fails
    if (!$stmt) {
        $response->success = false;
        $response->error = $conn->error;
        return $response;
    }

    // bind data if needed
    if ($types && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    // run the query
    $response->success = $stmt->execute();
    $response->affected_rows = $stmt->affected_rows; // 1 if change/insert, 0 if nothing
    $response->insert_id = $stmt->insert_id;         // get new ID after INSERT
    $response->error = $stmt->error;
    
    // try to get result (for SELECT)
    $result = $stmt->get_result();
    $response->result = $result; 
    
    return $response; // pack everything back
}
?>
