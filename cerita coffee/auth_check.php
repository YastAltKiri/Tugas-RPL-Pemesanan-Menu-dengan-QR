<?php

session_start();

function cekLogin(string $requiredRole): void
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header('Location: /login.php');
        exit;
    }

    if ($_SESSION['role'] !== $requiredRole) {
        header('Location: /login.php?error=invalid');
        exit;
    }
}