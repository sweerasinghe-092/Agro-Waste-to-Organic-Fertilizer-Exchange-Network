<?php

// Start the session.
session_start();

// Clear all session variables.
$_SESSION = [];

// Destroy the current session.
session_destroy();

// Redirect to the index.php page.
header("Location: index.php");

// Stop the PHP execution.
exit;

?>
