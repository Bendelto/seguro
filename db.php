<?php
$host = 'localhost';
$dbname = 'seguro_bd'; // CAMBIAR
$user = 'seguro_user'; // CAMBIAR
$pass = 'Dc@6691400'; // CAMBIAR

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>