<?php
require_once __DIR__ . "/conexao.php";

$mensagem = "";
$tipoMensagem = "erro";
$prazoDias = 7;

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    $idLivro = isset($_POST["id_livro"]) && is_string($_POST["id_livro"])
        ? filter_var($_POST["id_livro"], FILTER_VALIDATE_INT)
        : false;
    $idLeitor = isset($_POST["id_leitor"]) && is_string($_POST["id_leitor"])
        ? filter_var($_POST["id_leitor"], FILTER_VALIDATE_INT)
        : false;

    if (!$idLivro || $idLivro < 1 || !$idLeitor || $idLeitor < 1) {
        $mensagem = "Selecione um livro e um leitor válidos.";
    } elseif (!($conexao instanceof mysqli) || $conexao->connect_errno) {
        error_log("Falha na conexão com o banco ao registrar empréstimo.");
        $mensagem = "Não foi possível registrar o empréstimo agora.";
    } else {
        try {
            $conexao->begin_transaction();

            $stmt = $conexao->prepare("SELECT quantidade, status FROM livros WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $idLivro);
            $stmt->execute();
            $livro = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$livro) {
                throw new DomainException("Livro não encontrado.");
            }
            if ($livro["status"] !== "ativo") {
                throw new DomainException("Este livro não está disponível para empréstimo.");
            }

            $stmt = $conexao->prepare(
                "SELECT COUNT(*) AS total FROM emprestimos WHERE id_livro = ? AND status = 'emprestado'"
            );
            $stmt->bind_param("i", $idLivro);
            $stmt->execute();
            $emprestados = (int) $stmt->get_result()->fetch_assoc()["total"];
            $stmt->close();

            if ((int) $livro["quantidade"] - $emprestados < 1) {
                throw new DomainException("Não há exemplares disponíveis para empréstimo.");
            }

            $stmt = $conexao->prepare("SELECT id FROM leitores WHERE id = ?");
            $stmt->bind_param("i", $idLeitor);
            $stmt->execute();
            $leitorExiste = (bool) $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$leitorExiste) {
                throw new DomainException("Leitor não encontrado.");
            }

            $dataEmprestimo = date("Y-m-d");
            $dataPrevista = date("Y-m-d", strtotime("+{$prazoDias} days"));
            $stmt = $conexao->prepare(
                "INSERT INTO emprestimos
                    (id_livro, id_leitor, data_emprestimo, data_prevista, status)
                 VALUES (?, ?, ?, ?, 'emprestado')"
            );
            $stmt->bind_param("iiss", $idLivro, $idLeitor, $dataEmprestimo, $dataPrevista);
            $stmt->execute();
            $stmt->close();
            $conexao->commit();

            $mensagem = "Empréstimo registrado. Devolução prevista para " . date("d/m/Y", strtotime($dataPrevista)) . ".";
            $tipoMensagem = "sucesso";
        } catch (DomainException $erro) {
            $conexao->rollback();
            $mensagem = $erro->getMessage();
        } catch (Throwable $erro) {
            $conexao->rollback();
            error_log("Erro ao registrar empréstimo: " . $erro->getMessage());
            $mensagem = "Não foi possível registrar o empréstimo agora. Tente novamente.";
        }
    }
}

$livros = false;
$leitores = false;
if (isset($conexao) && $conexao instanceof mysqli && !$conexao->connect_errno) {
    try {
        $livros = $conexao->query(
            "SELECT l.id, l.titulo,
                    l.quantidade - (
                        SELECT COUNT(*) FROM emprestimos e
                        WHERE e.id_livro = l.id AND e.status = 'emprestado'
                    ) AS disponivel
             FROM livros l
             WHERE l.status = 'ativo'
             HAVING disponivel > 0
             ORDER BY l.titulo"
        );
        $leitores = $conexao->query("SELECT id, nome FROM leitores ORDER BY nome");
    } catch (Throwable $erro) {
        error_log("Erro ao carregar formulário de empréstimo: " . $erro->getMessage());
        $mensagem = "Não foi possível carregar os dados agora.";
        $tipoMensagem = "erro";
    }
} else {
    $mensagem = "Não foi possível conectar ao banco de dados.";
    $tipoMensagem = "erro";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Emprestar Livro</title>
</head>


<body>

<div class="container">

    <h2>Emprestar Livro</h2>

    <p><a href="menu_fun.php">&larr; Voltar ao menu</a></p>

    <?php if ($mensagem !== ""): ?>
        <p class="<?= htmlspecialchars($tipoMensagem, ENT_QUOTES, "UTF-8") ?>" role="alert"><?= htmlspecialchars($mensagem, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") ?></p>
    <?php endif; ?>

    <form method="post">

        <label>Livro</label>
        <select name="id_livro" required>
            <option value="">Selecione o livro</option>
            <?php while ($livros && ($l = $livros->fetch_assoc())): ?>
                <option value="<?php echo $l['id']; ?>">
                    <?php echo htmlspecialchars($l['titulo'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?> (<?php echo (int) $l['disponivel']; ?> disponível(is))
                </option>
            <?php endwhile; ?>
        </select>

        <label>Leitor</label>
        <select name="id_leitor" required>
            <option value="">Selecione o leitor</option>
            <?php while ($leitores && ($le = $leitores->fetch_assoc())): ?>
                <option value="<?php echo (int) $le['id']; ?>"><?php echo htmlspecialchars($le['nome'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit" name="inserir">Registrar Empréstimo</button>

    </form>


</div>

</body>
</html>
