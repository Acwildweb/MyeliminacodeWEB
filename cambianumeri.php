<?php

include 'connect.php';

$sql = "select c.ID_contatore, c.id_turno, c.numero, t.turno 
        from contatori c 
        join turni t on c.id_turno = t.ID_turno 
        where c.tipo = 'NUMERO'
        order by t.turno";
$rs = mysqli_query($conn, $sql);
if (mysqli_num_rows($rs) == 0) {
    exit;
}
$contatori = [];
while ($row = mysqli_fetch_assoc($rs)) {
    $contatori[] = [
        'ID_contatore' => $row['ID_contatore'],
        'id_turno' => $row['id_turno'],
        'numero' => $row['numero'],
        'turno' => $row['turno']
    ];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Tabella Turni</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f6fb;
            margin: 40px;
        }
        table {
            border-collapse: collapse;
            width: 70%;
            margin: auto;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 16px 20px;
            text-align: center;
        }
        th {
            background: #1976d2;
            color: #fff;
            font-size: 1.1em;
            letter-spacing: 1px;
        }
        tr:nth-child(even) {
            background: #f0f4fa;
        }
        tr:hover {
            background: #e3f2fd;
        }
        .btn {
            background: #43a047;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95em;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #388e3c;
        }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Gestione Turni</h2>
    <table>
        <thead>
            <tr>
                <th>Turno</th>
                <th>Chiamati</th>
                <th>In coda</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contatori as $contatore): ?>
<?php
    $sql = "select numero 
        from contatori
        where tipo = 'CODA' and id_turno = " . $contatore['id_turno'];
    $rs = mysqli_query($conn, $sql);
    if (mysqli_num_rows($rs) == 0) {
        exit;
    }
    $coda = 0;
    if ($row = mysqli_fetch_assoc($rs)) {
        $coda = $row['numero'];
    }
?>
            <tr>
                <td><?php echo htmlspecialchars($contatore['turno']); ?></td>
                <td>
                    <input type="number" id="numero_<?php echo $contatore['ID_contatore']; ?>" step="1" min="<?php echo htmlspecialchars($contatore['numero']); ?>" max="<?php echo $coda; ?>" value="<?php echo htmlspecialchars($contatore['numero']); ?>">
                    <input type="hidden" value="<?php echo $contatore['ID_contatore']; ?>" id="id_contatore_<?php echo $contatore['ID_contatore']; ?>"  >
                </td>
                <td><?php echo $coda; ?></td>
                <td>
                    <button class="btn" onclick="aggiorna(<?php echo $contatore['ID_contatore']; ?>)">Aggiorna numero</button>
                </td>
            </tr>
            <?php endforeach; ?>            </tbody>
    </table>

    <script>
        function aggiorna(id) {
            const nomecampo = "numero_" + id;
            const numero = document.getElementById(nomecampo).value;
            const formData = new FormData();
            formData.append('id_contatore', id);
            formData.append('numero', numero);

            fetch('salvanumero.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                alert(data);
                //location.reload();
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Si è verificato un errore durante l\'aggiornamento del numero.');
            });
        }
    </script>
</body>
</html>