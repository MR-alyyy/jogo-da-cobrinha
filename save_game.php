<?php
header('Content-Type: application/json; charset=utf-8');

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS partidas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pontuacao INTEGER NOT NULL,
    duracao INTEGER NOT NULL,
    data_hora TEXT NOT NULL
)");

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['best'])) {
    $best = (int)$db->query("SELECT COALESCE(MAX(pontuacao),0) FROM partidas")->fetchColumn();
    echo json_encode(['best'=>$best]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$score = isset($input['score']) ? (int)$input['score'] : -1;
$duration = isset($input['duration']) ? (int)$input['duration'] : 0;

if ($score < 0 || $duration < 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Dados inválidos']);
    exit;
}

$stmt = $db->prepare("INSERT INTO partidas (pontuacao,duracao,data_hora) VALUES (?,?,datetime('now','localtime'))");
$stmt->execute([$score,$duration]);

$best = (int)$db->query("SELECT COALESCE(MAX(pontuacao),0) FROM partidas")->fetchColumn();

echo json_encode(['success'=>true,'best'=>$best]);
