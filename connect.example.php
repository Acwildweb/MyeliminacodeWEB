<?php
/**
 * Copia questo file come connect.php e inserisci le credenziali locali.
 * connect.php NON va mai committato su GitHub.
 */
$host = 'localhost';
$user = 'root';
$password = 'CAMBIA_PASSWORD';
$dbname = 'mysanitario';

$conn = mysqli_connect($host, $user, $password, $dbname);
if (!$conn) {
    die('Connessione fallita: ' . mysqli_connect_error());
}
