<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS partidas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pontuacao INTEGER NOT NULL,
    duracao INTEGER NOT NULL,
    data_hora TEXT NOT NULL
)");

if (isset($_GET['pdf'])) {
    $partidas = $db->query("SELECT * FROM partidas ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $total = count($partidas);
    $media = $total ? round(array_sum(array_column($partidas,'pontuacao'))/$total,2) : 0;
    $recorde = $total ? max(array_column($partidas,'pontuacao')) : 0;

    $rows='';
    foreach($partidas as $p){
        $dur = gmdate('i:s',$p['duracao']);
        $rows .= '<tr><td>'.htmlspecialchars($p['id']).'</td><td>'.htmlspecialchars($p['pontuacao']).'</td><td>'.$dur.'</td><td>'.htmlspecialchars($p['data_hora']).'</td></tr>';
    }

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>
    body{font-family:DejaVu Sans,Arial,sans-serif;color:#222}
    h1{font-size:22px;margin-bottom:5px}
    .info{margin:15px 0;padding:10px;background:#f2f2f2}
    table{width:100%;border-collapse:collapse}
    th,td{border:1px solid #bbb;padding:7px;font-size:11px}
    th{background:#ddd}
    </style></head><body>
    <h1>Relatório de Partidas — Jogo da Cobrinha</h1>
    <div class="info"><b>Total de partidas:</b> '.$total.' &nbsp;&nbsp;
    <b>Média de pontos:</b> '.$media.' &nbsp;&nbsp;
    <b>Recorde:</b> '.$recorde.'</div>
    <table><thead><tr><th>ID</th><th>Pontuação</th><th>Duração</th><th>Data/Hora</th></tr></thead><tbody>'.$rows.'</tbody></table>
    </body></html>';

    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html,'UTF-8');
    $dompdf->setPaper('A4','portrait');
    $dompdf->render();
    $dompdf->stream('relatorio-partidas.pdf',['Attachment'=>true]);
    exit;
}

$partidas = $db->query("SELECT * FROM partidas ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$total = count($partidas);
$recorde = $total ? max(array_column($partidas,'pontuacao')) : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatórios</title>
<style>
body{font-family:Arial;background:#f4f6f8;margin:0;padding:30px;color:#222}
.box{max-width:900px;margin:auto;background:#fff;padding:25px;border-radius:12px}
a{display:inline-block;padding:10px 15px;background:#198754;color:white;text-decoration:none;border-radius:7px;margin-bottom:20px}
table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left}
</style>
</head>
<body><div class="box">
<h1>📊 Relatório das Partidas</h1>
<p>Total: <b><?= $total ?></b> | Recorde: <b><?= $recorde ?></b></p>
<a href="report.php?pdf=1">Gerar PDF</a> <a href="index.php">Voltar ao jogo</a>
<table><tr><th>ID</th><th>Pontos</th><th>Duração</th><th>Data/Hora</th></tr>
<?php foreach($partidas as $p): ?>
<tr><td><?=htmlspecialchars($p['id'])?></td><td><?=htmlspecialchars($p['pontuacao'])?></td><td><?=gmdate('i:s',$p['duracao'])?></td><td><?=htmlspecialchars($p['data_hora'])?></td></tr>
<?php endforeach; ?>
</table>
</div></body>
</html>
