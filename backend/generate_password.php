<?php
// Generar hash para contraseña "admin123"
$password = "admin123";
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Hash para '$password':\n";
echo $hash . "\n";
?>
