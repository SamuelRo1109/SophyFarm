<?php
session_start();  // Asegúrate de que la sesión esté iniciada

class AuthMiddleware {
    // Verifica si el usuario tiene el rol adecuado
    public static function verifyRole($roleRequired) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != $roleRequired) {
            die("Acceso no autorizado");
        }
    }
}
?>
