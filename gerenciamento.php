<?php
include "conexao.php";

if ($conexao->connect_error) {
    die("<p style='color:red;font-family:sans-serif;'>Erro ao conectar ao banco: " . $conexao->connect_error . "</p>");
}

// ========================
// BUSCAR TODOS OS LIVROS
// ========================
$livros = $conexao->query("SELECT id, titulo, autor, genero, quantidade FROM livros ORDER BY titulo");

if ($livros === false) {
    die("<p style='color:red;font-family:sans-serif;'>Erro na consulta de livros: " . $conexao->error .
        "<br>Provavelmente a tabela <b>livros</b> ainda não tem a coluna <b>id</b>. " .
        "Rode o arquivo <b>alter_tabelas.sql</b> no phpMyAdmin.</p>");
}

// ========================
// BUSCAR TODOS OS EMPRÉSTIMOS ATIVOS, JÁ AGRUPADOS POR LIVRO
// ========================
$emprestimosPorLivro = [];

$resEmprestimos = $conexao->query("
    SELECT e.id_livro, le.nome, e.data_emprestimo, e.data_prevista
    FROM emprestimos e
    JOIN leitores le ON le.id = e.id_leitor
    WHERE e.status = 'emprestado'
");

if ($resEmprestimos === false) {
    die("<p style='color:red;font-family:sans-serif;'>Erro na consulta de empréstimos: " . $conexao->error .
        "<br>Provavelmente a tabela <b>emprestimos</b> ainda não existe. " .
        "Rode o arquivo <b>alter_tabelas.sql</b> no phpMyAdmin.</p>");
}

while ($linha = $resEmprestimos->fetch_assoc()) {
    $emprestimosPorLivro[$linha['id_livro']][] = $linha;
}

if ($livros->num_rows === 0) {
    $semLivros = true;
} else {
    $semLivros = false;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Gerenciamento do Acervo</title>
<style>
    body { font-family: Arial, sans-serif; padding: 30px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 40px; }
    th, td { border: 1px solid #999; padding: 8px 12px; text-align: left; vertical-align: top; }
    th { background: #eee; }
    .disponivel { color: green; font-weight: bold; }
    .indisponivel { color: red; font-weight: bold; }
    .lista-emprestimo { margin: 0; padding-left: 18px; }
    .atrasado { color: #b00020; }
</style>
</head>
<body>

<h2>Gerenciamento do Acervo</h2>
<p><a href="menu_fun.php">&larr; Voltar ao menu</a></p>

<?php if ($semLivros): ?>
    <p><em>Nenhum livro cadastrado ainda. Cadastre um livro primeiro.</em></p>
<?php else: ?>

<table>
    <tr>
        <th>Título</th>
        <th>Autor</th>
        <th>Gênero</th>
        <th>Qtd. Total</th>
        <th>Qtd. Disponível</th>
        <th>Emprestado com</th>
    </tr>

    <?php while ($livro = $livros->fetch_assoc()):

        $emprestados = $emprestimosPorLivro[$livro['id']] ?? [];
        $qtdEmprestada = count($emprestados);
        $qtdDisponivel = $livro['quantidade'] - $qtdEmprestada;
    ?>
    <tr>
        <td><?php echo htmlspecialchars($livro['titulo']); ?></td>
        <td><?php echo htmlspecialchars($livro['autor']); ?></td>
        <td><?php echo htmlspecialchars($livro['genero']); ?></td>
        <td><?php echo $livro['quantidade']; ?></td>
        <td class="<?php echo $qtdDisponivel > 0 ? 'disponivel' : 'indisponivel'; ?>">
            <?php echo $qtdDisponivel; ?>
        </td>
        <td>
            <?php if ($qtdEmprestada === 0): ?>
                <em>Nenhum exemplar emprestado</em>
            <?php else: ?>
                <ul class="lista-emprestimo">
                    <?php foreach ($emprestados as $e):
                        $atrasado = strtotime($e['data_prevista']) < strtotime(date('Y-m-d'));
                    ?>
                        <li class="<?php echo $atrasado ? 'atrasado' : ''; ?>">
                            <?php echo htmlspecialchars($e['nome']); ?>
                            (emprestado em <?php echo date('d/m/Y', strtotime($e['data_emprestimo'])); ?>,
                            devolução prevista <?php echo date('d/m/Y', strtotime($e['data_prevista'])); ?><?php echo $atrasado ? ' - ATRASADO' : ''; ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<?php endif; ?>

</body>
</html>
