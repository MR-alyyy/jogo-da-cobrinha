<!DOCTYPE html>
<html>
<head>
    <title>Consulta de Livros</title>
</head>

<body>

<form method="post" action="">
    Título:
    <input name="Buscar" type="text">
    <input type="submit" value="Pesquisar" />
    <a href="Exemplo2-PhpComBancodeDados.php">Voltar</a>
</form>

<?php

if (isset($_POST['Buscar'])) {

    include "conexao.php";

    $buscar = $_POST['Buscar'];

    $sql = mysqli_query($conexao, "SELECT * FROM livros WHERE titulo LIKE '%$buscar%'");

    $row = mysqli_num_rows($sql);

    if ($row > 0) {

        while ($linha = mysqli_fetch_array($sql)) {

            $status = $linha['status'];
            $titulo = $linha['titulo'];
            $autor = $linha['autor'];
            $genero = $linha['genero'];
            $quantidade = $linha['quantidade'];
            $descricao = $linha['descricao'];

            echo "<strong>Status:</strong> $status <br />";
            echo "<strong>Título:</strong> $titulo <br />";
            echo "<strong>Autor:</strong> $autor <br />";
            echo "<strong>Gênero:</strong> $genero <br />";
            echo "<strong>Quantidade:</strong> $quantidade <br />";
            echo "<strong>Descrição:</strong> $descricao <br />";
            echo "<hr />";

        }

    } else {

        echo "Livro não encontrado...";

    }
}

?>

</body>
</html>
