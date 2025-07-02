<?php
session_start();

function isLoggedIn()
{
    return isset($_SESSION['technical_officer_id']);
}

function redirectIfNotLoggedIn()
{
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}
