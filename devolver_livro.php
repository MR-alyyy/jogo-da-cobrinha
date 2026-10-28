<?php
include "conexao.php";

$mensagem = "";
$valorMultaPorDia = 2.00; // R$ 2,00 por dia de atraso

if (isset($_POST['devolver'])) {

    $id_emprestimo = (int) trim($_POST['id_emprestimo']);

    // ========================
    // BUSCAR DADOS DO EMPRÉSTIMO
    // ========================
    $stmt = $conexao->prepare("SELECT id_livro, data_prevista FROM emprestimos WHERE id = ? AND status = 'emprestado'");
    $stmt->bind_param("i", $id_emprestimo);
    $stmt->execute();
    $emprestimo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$emprestimo) {

        $mensagem = "<p class='erro'>Empréstimo não encontrado ou já devolvido.</p>";

    } else {

        $hoje = new DateTime();
        $dataPrevista = new DateTime($emprestimo['data_prevista']);

        $multa = 0.00;

        if ($hoje > $dataPrevista) {
            $diasAtraso = $hoje->diff($dataPrevista)->days;
            $multa = $diasAtraso * $valorMultaPorDia;
        }

        $dataDevolucao = date("Y-m-d H:i:s");

        // ========================
        // ATUALIZAR EMPRÉSTIMO (não mexe mais em livros.quantidade,
        // pois ela agora representa o total fixo do título)
        // ========================
        $stmt = $conexao->prepare("
            UPDATE emprestimos
            SET data_devolucao = ?, multa = ?, status = 'devolvido'
            WHERE id = ?
        ");
        $stmt->bind_param("sdi", $dataDevolucao, $multa, $id_emprestimo);

        if ($stmt->execute()) {

            if ($multa > 0) {
                $mensagem = "<p class='erro'>Devolução registrada com atraso. Multa: R$ " . number_format($multa, 2, ',', '.') . "</p>";
            } else {
                $mensagem = "<p class='sucesso'>Devolução registrada dentro do prazo. Sem multa.</p>";
            }

        } else {
            $mensagem = "<p class='erro'>Erro ao registrar devolução: " . $stmt->error . "</p>";
        }

        $stmt->close();
    }
}

// ========================
// LISTAR EMPRÉSTIMOS EM ABERTO
// ========================
$emprestimos = $conexao->query("
    SELECT e.id, l.titulo, le.nome, e.data_emprestimo, e.data_prevista
    FROM emprestimos e
    JOIN livros l ON l.id = e.id_livro
    JOIN leitores le ON le.id = e.id_leitor
    WHERE e.status = 'emprestado'
    ORDER BY e.data_prevista ASC
");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Devolver Livro</title>
</head>

<body>

<div class="container">

    <h2>Devolver Livro</h2>

    <p><a href="menu_fun.php">&larr; Voltar ao menu</a></p>

    <?php echo $mensagem; ?>

    <table border="1" cellpadding="8">
        <tr>
            <th>Livro</th>
            <th>Leitor</th>
            <th>Emprestado em</th>
            <th>Devolução prevista</th>
            <th>Ação</th>
        </tr>

        <?php while ($e = $emprestimos->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($e['titulo']); ?></td>
            <td><?php echo htmlspecialchars($e['nome']); ?></td>
            <td><?php echo date("d/m/Y", strtotime($e['data_emprestimo'])); ?></td>
            <td><?php echo date("d/m/Y", strtotime($e['data_prevista'])); ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="id_emprestimo" value="<?php echo $e['id']; ?>">
                    <button type="submit" name="devolver">Registrar devolução</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>


</div>

</body>
</html>
