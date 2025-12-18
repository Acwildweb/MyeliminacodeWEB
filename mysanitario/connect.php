<?php
    /*$dbPath = 'C:\\Users\\acwild\\AppData\\Roaming\\amgclick.mdb'; // usa doppio backslash!
    $connStr = "Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=$dbPath;Uid=Admin;Pwd=;";

    $conn = odbc_connect($connStr, '', '');
    if (!$conn) {
        die("Connessione fallita: " . odbc_errormsg());
    }*/

    // Parametri di connessione MySQL
    $host = 'localhost';    // o l'indirizzo IP del server MySQL
    $user = 'mysanitario';  // sostituisci con il tuo username MySQL
    $password = 'mysanitario2025'; // sostituisci con la tua password MySQL
    $dbname = 'mysanitario'; // sostituisci con il nome del tuo database

    // Connessione a MySQL
    $conn = mysqli_connect($host, $user, $password, $dbname);

    // Controllo della connessione
    if (!$conn) {
        die("Connessione fallita: " . mysqli_connect_error());
    }
?>