<?php
/**
 * CSRF Protection Utility
 * Provides functions to generate and validate CSRF tokens
 */

function csrf_token(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function validate_csrf(string $token): bool {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_check(): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token'])) {
            return false;
        }
        return validate_csrf($_POST['csrf_token']);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_GET['csrf_token'])) {
            return false;
        }
        return validate_csrf($_GET['csrf_token']);
    }
    return true;
}
