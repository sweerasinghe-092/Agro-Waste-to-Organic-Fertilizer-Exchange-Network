<?php
// Shared database connection. Start sessions on pages that need login.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli("localhost", "root", "", "agro_exchange_db");
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Database connection failed. Import database.sql in phpMyAdmin and check config/db.php.");
}