<?php
$db = new mysqli("localhost", "root", "", "checkSiti");
if ($db->connect_error) {
    die("Connessione fallita: " . $db->connect_error);
}

$filter_anno = isset($_GET['anno']) && $_GET['anno'] !== '' ? intval($_GET['anno']) : null;
$filter_mese = isset($_GET['mese']) && $_GET['mese'] !== '' ? intval($_GET['mese']) : null;

$anni_res = $db->query("SELECT DISTINCT anno FROM disservizi_mensili ORDER BY anno DESC");
$anni = [];
while ($row = $anni_res->fetch_assoc()) {
    $anni[] = $row['anno'];
}

$mesi_nomi = [
    1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
    5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
    9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
];

// Valori da passare al COALESCE (se filtro assente, mostriamo anno/mese attuale)
$anno_coalesce = $filter_anno ?: date('Y');
$mese_coalesce = $filter_mese ?: date('n');

$query = "SELECT 
            s.url,
            COALESCE(d.anno, ?) AS anno,
            COALESCE(d.mese, ?) AS mese,
            COALESCE(d.contatore, 0) AS contatore
          FROM sitiMonitorati s
          LEFT JOIN disservizi_mensili d 
            ON s.id = d.idSito
            AND (? IS NULL OR d.anno = ?)
            AND (? IS NULL OR d.mese = ?)
          ORDER BY s.url, anno DESC, mese DESC";

$stmt = $db->prepare($query);
if ($stmt === false) {
    die("Errore nella preparazione della query: " . $db->error);
}

// Bind dei parametri
// 6 placeholder: 
// 1,2 = anno_coalesce, mese_coalesce per COALESCE
// 3,4 = filtro anno (o NULL)
// 5,6 = filtro mese (o NULL)
$anno_bind = $filter_anno !== null ? $filter_anno : null;
$mese_bind = $filter_mese !== null ? $filter_mese : null;

$stmt->bind_param(
    "iiiiii",
    $anno_coalesce,
    $mese_coalesce,
    $anno_bind,
    $anno_bind,
    $mese_bind,
    $mese_bind
);

$stmt->execute();
$res = $stmt->get_result();

$table_data = [];
while ($row = $res->fetch_assoc()) {
    $table_data[] = $row;
}

$stmt->close();
$db->close();
?>

<!-- Form filtro -->
<form method="GET" action="">
    <label for="anno">Anno:</label>
    <select name="anno" id="anno">
        <option value="">Tutti</option>
        <?php foreach ($anni as $anno): ?>
            <option value="<?= $anno ?>" <?= ($anno == $filter_anno) ? 'selected' : '' ?>><?= $anno ?></option>
        <?php endforeach; ?>
    </select>

    <label for="mese">Mese:</label>
    <select name="mese" id="mese">
        <option value="">Tutti</option>
        <?php foreach ($mesi_nomi as $num => $nome): ?>
            <option value="<?= $num ?>" <?= ($num == $filter_mese) ? 'selected' : '' ?>><?= $nome ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Filtra</button>
    <a href="<?= $_SERVER['PHP_SELF'] ?>">Reset</a>
</form>

<!-- Tabella dati -->
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th>Sito</th>
            <th>Anno</th>
            <th>Mese</th>
            <th>Minuti Totali di Disservizio</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($table_data)): ?>
            <tr><td colspan="4" style="text-align:center;">Nessun dato trovato per il filtro selezionato.</td></tr>
        <?php else: ?>
            <?php foreach ($table_data as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['url']) ?></td>
                    <td><?= $row['anno'] ?></td>
                    <td><?= $mesi_nomi[(int)$row['mese']] ?></td>
                    <td><?= $row['contatore'] ?> minuti</td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
