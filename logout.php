<?php
// Start the session
session_start();


// Finally, destroy the session
session_destroy();

// Redirect to login page
header("Location: ./");
exit();
