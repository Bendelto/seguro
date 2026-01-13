<?php
// Forzar encabezado UTF-8 para evitar rombos negros
header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$dbname = 'seguro_bd'; // CAMBIAR
$user = 'seguro_user'; // CAMBIAR
$pass = 'Dc@6691400'; // CAMBIAR

try {
    // CAMBIO IMPORTANTE: charset=utf8mb4
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>