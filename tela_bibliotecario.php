<?php
/**
 * Fichário — Painel do Bibliotecário (PHP + MySQL)
 * Requer: conexao.php (mesma pasta) e as tabelas livros, funcionarios e emprestimos já existentes no banco.
 */
session_start();
require __DIR__ . '/conexao.php';

/* ---------- helpers ---------- */
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function flash(string $msg): void { $_SESSION['flash'] = $msg; }
function redirect(string $view): void {
    header('Location: ?view=' . urlencode($view));
    exit;
}
/** Executa uma query preparada e devolve o statement. */
function q(mysqli $c, string $sql, string $types = '', array $params = []): mysqli_stmt {
    $st = $c->prepare($sql);
    if ($types !== '') { $st->bind_param($types, ...$params); }
    $st->execute();
    return $st;
}

if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

/* ---------- ações (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Requisição inválida.');
    }
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_book':
            $titulo = trim($_POST['titulo'] ?? '');
            $autor  = trim($_POST['autor'] ?? '');
            $genero = trim($_POST['genero'] ?? '');
            $isbn   = trim($_POST['isbn'] ?? '');
            $qtd    = max(1, (int)($_POST['quantidade'] ?? 1));
            if ($titulo === '' || $autor === '') { flash('Preencha título e autor.'); redirect('cadastro-livro'); }
            q($conexao, "INSERT INTO livros (titulo, autor, genero, isbn, quantidade) VALUES (?,?,?,?,?)",
              'ssssi', [$titulo, $autor, $genero, $isbn, $qtd]);
            flash('"' . $titulo . '" cadastrado no acervo.');
            redirect('acervo');

        case 'add_staff':
            $nome  = trim($_POST['nome'] ?? '');
            $cargo = trim($_POST['cargo'] ?? '');
            if ($nome === '' || $cargo === '') { flash('Preencha nome e cargo.'); redirect('cadastro-funcionario'); }
            q($conexao, "INSERT INTO funcionarios (nome, cargo, matricula, telefone, email) VALUES (?,?,?,?,?)",
              'sssss', [$nome, $cargo, trim($_POST['matricula'] ?? ''), trim($_POST['telefone'] ?? ''), trim($_POST['email'] ?? '')]);
            flash($nome . ' cadastrado(a) com sucesso.');
            redirect('funcionarios');

        case 'remove_staff':
            q($conexao, "DELETE FROM funcionarios WHERE id = ?", 'i', [(int)($_POST['id'] ?? 0)]);
            flash('Funcionário removido.');
            redirect('funcionarios');

        case 'remove_book':
            q($conexao, "DELETE FROM livros WHERE id = ?", 'i', [(int)($_POST['id'] ?? 0)]); // empréstimos saem por CASCADE
            flash('Livro removido do acervo.');
            redirect('acervo');

        case 'lend':
            $id   = (int)($_POST['id'] ?? 0);
            $nome = mb_substr(trim($_POST['nome'] ?? ''), 0, 80);
            $conexao->begin_transaction();
            $livro = q($conexao,
                "SELECT titulo, quantidade, (SELECT COUNT(*) FROM emprestimos WHERE livro_id = livros.id) AS emp
                 FROM livros WHERE id = ? FOR UPDATE", 'i', [$id])->get_result()->fetch_assoc();
            if (!$livro)                                   { flash('Livro não encontrado.'); }
            elseif ($nome === '')                          { flash('Informe quem está pegando o livro.'); }
            elseif ((int)$livro['emp'] >= (int)$livro['quantidade']) { flash('Sem exemplares disponíveis.'); }
            else {
                q($conexao, "INSERT INTO emprestimos (livro_id, nome_leitor) VALUES (?,?)", 'is', [$id, $nome]);
                flash('"' . $livro['titulo'] . '" emprestado para ' . $nome . '.');
            }
            $conexao->commit();
            redirect('acervo');

        case 'return':
            $id     = (int)($_POST['id'] ?? 0);
            $loanId = (int)($_POST['loan_id'] ?? 0);
            $conexao->begin_transaction();
            $del = q($conexao, "DELETE FROM emprestimos WHERE id = ? AND livro_id = ?", 'ii', [$loanId, $id]);
            if ($del->affected_rows > 0) {
                q($conexao, "UPDATE livros SET devolvidos = devolvidos + 1 WHERE id = ?", 'i', [$id]);
                flash('Livro devolvido.');
            }
            $conexao->commit();
            redirect('acervo');
    }
    redirect('acervo');
}

/* ---------- view atual ---------- */
$views = ['acervo', 'cadastro-livro', 'cadastro-funcionario', 'funcionarios'];
$view  = $_GET['view'] ?? 'acervo';
if (!in_array($view, $views, true)) { $view = 'acervo'; }

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$csrf = $_SESSION['csrf'];

/* ---------- carrega dados do banco ---------- */
$livros = [];
$res = $conexao->query("SELECT * FROM livros ORDER BY titulo");
while ($row = $res->fetch_assoc()) {
    $row['emprestimos'] = [];
    $livros[(int)$row['id']] = $row;
}
$res = $conexao->query("SELECT livro, leitor, emprestimo FROM emprestimos ORDER BY data_emprestimo");
while ($e = $res->fetch_assoc()) {
    if (isset($livros[(int)$e['livro_id']])) {
        $livros[(int)$e['livro_id']]['emprestimos'][] = [
            'id' => $e['id'], 'nome' => $e['nome_leitor'], 'data' => $e['data_emprestimo'],
        ];
    }
}
$livros = array_values($livros);

$staff = [];
$res = $conexao->query("SELECT * FROM funcionarios ORDER BY nome");
while ($row = $res->fetch_assoc()) { $staff[] = $row; }

/* stats */
$totalTitulos     = count($livros);
$totalExemplares  = array_sum(array_map(fn($b) => (int)$b['quantidade'], $livros));
$totalEmprestados = array_sum(array_map(fn($b) => count($b['emprestimos']), $livros));
$totalDisponiveis = array_sum(array_map(fn($b) => max(0, (int)$b['quantidade'] - count($b['emprestimos'])), $livros));

function nav_btn(string $target, string $ico, string $label, string $current): string {
    $cls = $target === $current ? 'active' : '';
    return '<a class="' . $cls . '" href="?view=' . h($target) . '"><i>' . $ico . '</i>' . h($label) . '</a>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Fichário — Painel do Bibliotecário</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root{
  --bg:#f6f8fc;
  --card:#fff;
  --ink:#182230;
  --muted:#718096;
  --line:#e6eaf1;
  --primary:#315bea;
  --primary-dark:#2047c9;
  --purple:#7657dc;
  --green:#17a673;
  --orange:#e79a2f;
  --danger:#dc5d67;
  --shadow:0 12px 34px rgba(34,52,93,.08);
}
*{box-sizing:border-box}
body{
  margin:0;
  background:var(--bg);
  color:var(--ink);
  font-family:Inter,ui-sans-serif,system-ui,-apple-system,Segoe UI,sans-serif;
}
.app{display:flex;min-height:100vh}

/* ===== SIDEBAR ===== */
.sidebar{
  width:252px;
  background:#fff;
  border-right:1px solid var(--line);
  padding:25px 17px;
  display:flex;
  flex-direction:column;
  gap:28px;
  position:sticky;
  top:0;
  height:100vh;
  flex-shrink:0;
}
.brand{padding:0 12px}
.brand .mark{
  font-size:22px;
  font-weight:850;
  letter-spacing:-.7px;
  background:linear-gradient(135deg,#315bea,#8a5be7);
  -webkit-background-clip:text;
  background-clip:text;
  color:transparent;
}
.brand .sub{
  font-size:10px;
  text-transform:uppercase;
  letter-spacing:1.3px;
  color:#a3adbd;
  font-weight:800;
  margin-top:4px;
}
.nav{display:flex;flex-direction:column;gap:4px}
.nav a{
  display:flex;
  align-items:center;
  gap:12px;
  padding:11px 13px;
  color:#68768a;
  border-radius:10px;
  text-decoration:none;
  font-size:13px;
  font-weight:650;
  transition:.18s ease;
}
.nav a:hover,.nav a.active{
  background:#edf1ff;
  color:var(--primary);
  transform:translateX(2px);
}
.nav i{font-style:normal;font-size:18px;width:20px;text-align:center}
.sidebar-bottom{
  margin-top:auto;
  background:linear-gradient(145deg,#edf2ff,#f7f2ff);
  border-radius:15px;
  padding:16px;
  color:#69758a;
  font-size:11px;
  line-height:1.5;
}
.sidebar-bottom b{color:var(--ink);font-size:12px}

/* ===== MAIN ===== */
.main{flex:1;min-width:0;max-width:1540px;margin:auto;padding:27px 39px 48px}
.page-head{margin-bottom:24px}
.eyebrow{
  text-transform:uppercase;
  letter-spacing:1.5px;
  color:var(--primary);
  font-size:10px;
  font-weight:800;
}
.page-head h1{
  font-size:30px;
  line-height:1.15;
  letter-spacing:-1.1px;
  margin:9px 0 8px;
}
.page-head p{margin:0;font-size:13px;color:var(--muted)}

/* stats */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:26px}
.stat-card{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:16px;
  padding:18px 20px;
  box-shadow:var(--shadow);
  position:relative;
  overflow:hidden;
}
.stat-card::after{
  content:"";
  position:absolute;top:0;left:0;bottom:0;width:4px;
  background:var(--stat-color,var(--primary));
}
.stat-card .num{font-size:28px;font-weight:850;letter-spacing:-1px;line-height:1}
.stat-card .lbl{
  margin-top:8px;
  font-size:10px;
  color:var(--muted);
  text-transform:uppercase;
  letter-spacing:1px;
  font-weight:700;
}

/* card */
.card{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:16px;
  box-shadow:var(--shadow);
}

/* table */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:980px}
thead th{
  text-align:left;
  font-size:10px;
  letter-spacing:1.2px;
  text-transform:uppercase;
  color:#a3adbd;
  font-weight:800;
  padding:16px;
  border-bottom:1px solid var(--line);
  background:#fafbfe;
}
thead th:first-child{border-top-left-radius:16px}
thead th:last-child{border-top-right-radius:16px}
tbody td{
  padding:14px 16px;
  border-bottom:1px solid var(--line);
  font-size:13px;
  vertical-align:middle;
}
tbody tr:hover{background:#fafbff}
tbody tr:last-child td{border-bottom:none}
td.num,th.num{text-align:center;font-variant-numeric:tabular-nums}
.book-title{font-weight:800;font-size:13px;display:block}
.book-author{display:block;font-size:11px;color:var(--muted);margin-top:3px}

.stamp{
  display:inline-block;
  font-size:10px;
  font-weight:750;
  padding:5px 9px;
  border-radius:6px;
  white-space:nowrap;
}
.stamp.avail{background:#e8f8f1;color:var(--green)}
.stamp.low{background:#fff3df;color:var(--orange)}
.stamp.none{background:#fdecee;color:var(--danger)}

/* quem pegou */
.borrowers{display:flex;flex-direction:column;gap:6px}
.borrower{
  display:flex;
  align-items:center;
  gap:8px;
  background:#f4f7ff;
  border:1px solid #dfe6fb;
  border-radius:8px;
  padding:5px 6px 5px 10px;
  font-size:12px;
}
.borrower b{font-weight:750}
.borrower span{color:var(--muted);font-size:10px}
.borrower form{margin-left:auto}
.none-txt{color:#a3adbd}

/* botões */
.mini-btn{
  font-family:inherit;
  font-size:11px;
  font-weight:750;
  padding:7px 11px;
  border-radius:8px;
  border:1px solid #cfd8f7;
  background:#fff;
  color:var(--primary);
  cursor:pointer;
  transition:.18s ease;
  white-space:nowrap;
}
.mini-btn:hover{background:#edf1ff}
.mini-btn:disabled{opacity:.4;cursor:not-allowed}
.mini-btn.danger{border-color:#f3cdd1;color:var(--danger)}
.mini-btn.danger:hover{background:#fdecee}
.mini-btn.sm{padding:4px 8px;font-size:10px}

.btn-primary{
  border:0;
  background:var(--primary-dark);
  color:#fff;
  border-radius:8px;
  padding:0 22px;
  height:42px;
  font-family:inherit;
  font-size:12px;
  font-weight:750;
  cursor:pointer;
  transition:.18s ease;
}
.btn-primary:hover{background:var(--primary);transform:translateY(-1px)}

.lend-form{display:flex;gap:6px;align-items:center}
.lend-form input{
  font-family:inherit;
  font-size:12px;
  padding:7px 10px;
  border:1px solid var(--line);
  border-radius:8px;
  width:130px;
}
.lend-form input:focus{outline:2px solid #cfd8f7;border-color:var(--primary)}
.row-actions{display:flex;flex-direction:column;gap:8px;align-items:flex-start}
.row-actions .inline{display:flex;gap:6px}

/* forms */
.form-card{padding:28px 32px;max-width:680px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px 20px;margin-bottom:22px}
.field{display:flex;flex-direction:column;gap:6px}
.field.full{grid-column:1 / -1}
.field label{
  font-size:10px;
  letter-spacing:1.2px;
  text-transform:uppercase;
  color:#a3adbd;
  font-weight:800;
}
.field input,.field select{
  font-family:inherit;
  font-size:13px;
  padding:11px 13px;
  border:1px solid var(--line);
  border-radius:10px;
  background:#fff;
  color:var(--ink);
}
.field input:focus,.field select:focus{outline:2px solid #cfd8f7;border-color:var(--primary)}
.form-actions{display:flex;align-items:center;gap:14px}

/* staff */
.staff-row{display:flex;align-items:center;gap:16px;padding:16px 24px;border-bottom:1px solid var(--line)}
.staff-row:last-child{border-bottom:none}
.avatar{
  width:42px;height:42px;border-radius:50%;
  background:linear-gradient(135deg,#f7b36e,#dd79a7);
  color:#fff;font-size:13px;font-weight:800;
  display:grid;place-items:center;flex-shrink:0;
}
.staff-info{flex:1}
.staff-info .name{font-weight:800;font-size:13px}
.staff-info .role{font-size:11px;color:var(--muted);margin-top:3px}
.staff-meta{font-size:11px;color:var(--muted);text-align:right;line-height:1.5}

.empty-state{padding:60px 24px;text-align:center;color:var(--muted)}
.empty-state .glyph{font-size:34px;margin-bottom:10px;opacity:.5}
.empty-state p{margin:4px 0;font-size:13px}

.toast{
  position:fixed;right:24px;bottom:24px;z-index:50;
  background:#182230;color:#fff;
  padding:12px 16px;border-radius:10px;
  box-shadow:0 12px 30px #18223030;
  font-size:12px;
  opacity:0;transform:translateY(10px);
  pointer-events:none;transition:.2s;
}
.toast.show{opacity:1;transform:translateY(0)}

@media(max-width:1050px){
  .sidebar{width:215px}
  .main{padding:24px}
}
@media(max-width:880px){
  .app{flex-direction:column}
  .sidebar{width:100%;height:auto;position:relative;flex-direction:row;align-items:center;padding:14px;overflow-x:auto;gap:16px}
  .nav{flex-direction:row}
  .sidebar-bottom{display:none}
  .main{padding:20px 16px 40px}
  .stats{grid-template-columns:1fr 1fr}
  .form-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<div class="app">
  <aside class="sidebar">
    <div class="brand">
      <div class="mark">Fichário</div>
      <div class="sub">Painel do bibliotecário</div>
    </div>
    <nav class="nav">
      <?= nav_btn('acervo', '&#9776;', 'Acervo', $view) ?>
      <?= nav_btn('cadastro-livro', '&#128214;', 'Cadastrar livro', $view) ?>
      <?= nav_btn('cadastro-funcionario', '&#128100;', 'Cadastrar funcionário', $view) ?>
      <?= nav_btn('funcionarios', '&#9776;', 'Funcionários', $view) ?>
    </nav>
    <div class="sidebar-bottom"><b>Dados salvos</b><br>automaticamente</div>
  </aside>

  <main class="main">

<?php if ($view === 'acervo'): ?>
    <!-- ===== ACERVO ===== -->
    <div class="page-head">
      <div class="eyebrow">Catálogo geral</div>
      <h1>Acervo da biblioteca</h1>
      <p>Consulte quantidade, empréstimos, devoluções e disponibilidade de cada título.</p>
    </div>

    <div class="stats">
      <div class="stat-card" style="--stat-color:var(--primary)"><div class="num"><?= $totalTitulos ?></div><div class="lbl">Títulos no acervo</div></div>
      <div class="stat-card" style="--stat-color:var(--purple)"><div class="num"><?= $totalExemplares ?></div><div class="lbl">Exemplares totais</div></div>
      <div class="stat-card" style="--stat-color:var(--danger)"><div class="num"><?= $totalEmprestados ?></div><div class="lbl">Emprestados agora</div></div>
      <div class="stat-card" style="--stat-color:var(--green)"><div class="num"><?= $totalDisponiveis ?></div><div class="lbl">Disponíveis agora</div></div>
    </div>

    <!-- Planilha de status dos livros -->
    <div class="card">
      <?php if (!$livros): ?>
        <div class="empty-state">
          <div class="glyph">&#128218;</div>
          <p><strong>O acervo está vazio.</strong></p>
          <p>Cadastre o primeiro livro para começar a controlar empréstimos.</p>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Título</th>
              <th class="num">Total</th>
              <th class="num">Emprestados</th>
              <th class="num">Devolvidos</th>
              <th class="num">Disponíveis</th>
              <th>Status</th>
              <th>Quem pegou</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($livros as $b):
              $qtd = (int)$b['quantidade'];
              $emp = count($b['emprestimos']);
              $disp = max(0, $qtd - $emp);
              if ($disp === 0)                                  { $st = '<span class="stamp none">Esgotado</span>'; }
              elseif ($disp <= max(1, (int)ceil($qtd * 0.25)))  { $st = '<span class="stamp low">Poucas cópias</span>'; }
              else                                              { $st = '<span class="stamp avail">Disponível</span>'; }
          ?>
            <tr>
              <td>
                <span class="book-title"><?= h($b['titulo']) ?></span>
                <span class="book-author"><?= h($b['autor'] ?: 'Autor não informado') ?></span>
              </td>
              <td class="num"><?= $qtd ?></td>
              <td class="num"><?= $emp ?></td>
              <td class="num"><?= (int)$b['devolvidos'] ?></td>
              <td class="num"><?= $disp ?></td>
              <td><?= $st ?></td>
              <td>
                <?php if (!$b['emprestimos']): ?>
                  <span class="none-txt">—</span>
                <?php else: ?>
                  <div class="borrowers">
                  <?php foreach ($b['emprestimos'] as $e): ?>
                    <div class="borrower">
                      <div><b><?= h($e['nome']) ?></b> <span>desde <?= h(date('d/m/Y', strtotime($e['data']))) ?></span></div>
                      <form method="post">
                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                        <input type="hidden" name="action" value="return">
                        <input type="hidden" name="id" value="<?= h($b['id']) ?>">
                        <input type="hidden" name="loan_id" value="<?= h($e['id']) ?>">
                        <button class="mini-btn sm" type="submit">Devolver</button>
                      </form>
                    </div>
                  <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <div class="row-actions">
                  <form method="post" class="lend-form">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="lend">
                    <input type="hidden" name="id" value="<?= h($b['id']) ?>">
                    <input type="text" name="nome" placeholder="Nome de quem pegou" maxlength="80" required <?= $disp <= 0 ? 'disabled' : '' ?>>
                    <button class="mini-btn" type="submit" <?= $disp <= 0 ? 'disabled' : '' ?>>Emprestar</button>
                  </form>
                  <form method="post" onsubmit="return confirm('Remover este livro do acervo?');">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="remove_book">
                    <input type="hidden" name="id" value="<?= h($b['id']) ?>">
                    <button class="mini-btn danger" type="submit">Remover</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

<?php elseif ($view === 'cadastro-livro'): ?>
    <!-- ===== CADASTRO LIVRO ===== -->
    <div class="page-head">
      <div class="eyebrow">Novo título</div>
      <h1>Cadastrar livro</h1>
      <p>Adicione um título ao acervo com a quantidade de exemplares disponíveis.</p>
    </div>
    <div class="card form-card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="action" value="add_book">
        <div class="form-grid">
          <div class="field full">
            <label for="l-titulo">Título</label>
            <input id="l-titulo" name="titulo" type="text" placeholder="Ex.: Dom Casmurro" required>
          </div>
          <div class="field">
            <label for="l-autor">Autor</label>
            <input id="l-autor" name="autor" type="text" placeholder="Ex.: Machado de Assis" required>
          </div>
          <div class="field">
            <label for="l-genero">Gênero</label>
            <input id="l-genero" name="genero" type="text" placeholder="Ex.: Romance">
          </div>
          <div class="field">
            <label for="l-isbn">ISBN (opcional)</label>
            <input id="l-isbn" name="isbn" type="text" placeholder="978-0-000000-0-0">
          </div>
          <div class="field">
            <label for="l-quantidade">Quantidade de exemplares</label>
            <input id="l-quantidade" name="quantidade" type="number" min="1" step="1" value="1" required>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-primary">Cadastrar livro</button>
        </div>
      </form>
    </div>

<?php elseif ($view === 'cadastro-funcionario'): ?>
    <!-- ===== CADASTRO FUNCIONÁRIO ===== -->
    <div class="page-head">
      <div class="eyebrow">Novo colaborador</div>
      <h1>Cadastrar funcionário</h1>
      <p>Registre quem atua no balcão de atendimento e controle de acervo.</p>
    </div>
    <div class="card form-card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="action" value="add_staff">
        <div class="form-grid">
          <div class="field full">
            <label for="f-nome">Nome completo</label>
            <input id="f-nome" name="nome" type="text" placeholder="Ex.: Maria Oliveira" required>
          </div>
          <div class="field">
            <label for="f-cargo">Cargo</label>
            <select id="f-cargo" name="cargo" required>
              <option value="">Selecione</option>
              <option>Bibliotecário(a)</option>
              <option>Auxiliar de biblioteca</option>
              <option>Atendente</option>
              <option>Estagiário(a)</option>
              <option>Coordenador(a)</option>
            </select>
          </div>
          <div class="field">
            <label for="f-matricula">Matrícula</label>
            <input id="f-matricula" name="matricula" type="text" placeholder="Ex.: 00123">
          </div>
          <div class="field">
            <label for="f-telefone">Telefone</label>
            <input id="f-telefone" name="telefone" type="text" placeholder="(00) 00000-0000">
          </div>
          <div class="field">
            <label for="f-email">E-mail</label>
            <input id="f-email" name="email" type="email" placeholder="nome@biblioteca.com">
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-primary">Cadastrar funcionário</button>
        </div>
      </form>
    </div>

<?php else: ?>
    <!-- ===== LISTA DE FUNCIONÁRIOS ===== -->
    <div class="page-head">
      <div class="eyebrow">Equipe</div>
      <h1>Funcionários cadastrados</h1>
      <p>Todos os colaboradores com acesso ao balcão da biblioteca.</p>
    </div>
    <div class="card">
      <?php if (!$staff): ?>
        <div class="empty-state">
          <div class="glyph">&#128100;</div>
          <p><strong>Nenhum funcionário cadastrado.</strong></p>
          <p>Use "Cadastrar funcionário" no menu lateral.</p>
        </div>
      <?php else: foreach ($staff as $f):
          $parts = preg_split('/\s+/', trim($f['nome']));
          $ini = '';
          foreach (array_slice($parts, 0, 2) as $p) { $ini .= mb_strtoupper(mb_substr($p, 0, 1)); }
      ?>
        <div class="staff-row">
          <div class="avatar"><?= h($ini ?: '?') ?></div>
          <div class="staff-info">
            <div class="name"><?= h($f['nome']) ?></div>
            <div class="role"><?= h($f['cargo'] ?: 'Cargo não informado') ?><?= $f['matricula'] ? ' · Mat. ' . h($f['matricula']) : '' ?></div>
          </div>
          <div class="staff-meta">
            <?= $f['telefone'] ? h($f['telefone']) . '<br>' : '' ?><?= $f['email'] ? h($f['email']) : '' ?>
          </div>
          <form method="post" onsubmit="return confirm('Remover este funcionário?');">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="remove_staff">
            <input type="hidden" name="id" value="<?= h($f['id']) ?>">
            <button class="mini-btn danger" type="submit">Remover</button>
          </form>
        </div>
      <?php endforeach; endif; ?>
    </div>
<?php endif; ?>

  </main>
</div>

<div class="toast" id="toast"></div>

<?php if ($flash !== ''): ?>
<script>
(function(){
  var t = document.getElementById('toast');
  t.textContent = <?= json_encode($flash, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  t.classList.add('show');
  setTimeout(function(){ t.classList.remove('show'); }, 2800);
})();
</script>
<?php endif; ?>
</body>
</html>
