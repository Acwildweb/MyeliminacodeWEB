<?php
$testo = $_GET['t'] ?? 'Turno A, numero 1.';
$testo = trim(preg_replace('/[^\w\s.,]/u', ' ', $testo));
echo 'debug TTS: ' . htmlspecialchars($testo);
