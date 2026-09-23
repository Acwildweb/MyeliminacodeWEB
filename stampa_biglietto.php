<?php
// Ricevi i parametri
$turno = isset($_GET['turno']) ? htmlspecialchars($_GET['turno']) : '';
$numero = isset($_GET['numero']) ? htmlspecialchars($_GET['numero']) : '';
$data = isset($_GET['data']) ? $_GET['data'] : date('Ymd');

// Formatta data e ora
$dataFormattata = substr($data, 6, 2) . '/' . substr($data, 4, 2) . '/' . substr($data, 0, 4);
$oraFormattata = date('H:i');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stampa Biglietto</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
        }
        
        .ticket {
            width: 80mm;
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 5mm;
        }
        
        .ticket-header {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
        }
        
        .ticket-turno {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 15px 0;
        }
        
        .ticket-numero {
            text-align: center;
            font-size: 48px;
            font-weight: bold;
            margin: 20px 0;
            border: 3px solid #000;
            padding: 15px;
        }
        
        .ticket-footer {
            text-align: center;
            font-size: 10px;
            margin-top: 15px;
            border-top: 2px dashed #000;
            padding-top: 10px;
        }
        
        .ticket-cut {
            text-align: center;
            font-size: 12px;
            margin-top: 10px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .ticket {
                page-break-after: always;
            }
        }
    </style>
</head>
<body onload="window.print(); setTimeout(function(){ window.close(); }, 1000);">
    <div class="ticket">
        <div class="ticket-header">
            BIGLIETTO PRENOTAZIONE
        </div>
        <div class="ticket-turno">
            Turno: <?php echo $turno; ?>
        </div>
        <div class="ticket-numero">
            <?php echo $numero; ?>
        </div>
        <div class="ticket-footer">
            Data: <?php echo $dataFormattata; ?><br>
            Ora: <?php echo $oraFormattata; ?>
        </div>
        <div class="ticket-cut">
            - - - - - - - - - - - -
        </div>
    </div>
</body>
</html>
