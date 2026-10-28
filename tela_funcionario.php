<?php

session_start();

require_once "conexao.php";


/* =========================================================
   PROTEÇÃO DO LOGIN
========================================================= */

if (
    !isset($_SESSION['tipo']) ||
    $_SESSION['tipo'] !== 'funcionario'
) {

    header("Location: login_funcionario.php");
    exit();

}


/* =========================================================
   BUSCAR LIVROS
========================================================= */

$sql = "
    SELECT
        status,
        titulo,
        autor,
        genero,
        quantidade,
        descricao
    FROM livros
    ORDER BY titulo ASC
";


$resultado = mysqli_query(
    $conexao,
    $sql
);


if (!$resultado) {

    die(
        "Erro ao buscar livros: "
        . mysqli_error($conexao)
    );

}


/* =========================================================
   TRANSFORMAR RESULTADO EM ARRAY
========================================================= */

$livros = [];

while (
    $livro = mysqli_fetch_assoc($resultado)
) {

    $livros[] = $livro;

}


/* =========================================================
   CONTADORES
========================================================= */

$totalTitulos = count($livros);

$totalExemplares = 0;

$totalDisponiveis = 0;

$totalIndisponiveis = 0;


foreach ($livros as $livro) {

    $quantidade =
        (int)$livro['quantidade'];

    $totalExemplares +=
        $quantidade;


    $status =
        strtolower(
            trim($livro['status'])
        );


    if (
        $status === 'ativo' ||
        $status === 'dispon' ||
        $status === 'livre'
    ) {

        $totalDisponiveis +=
            $quantidade;

    } else {

        $totalIndisponiveis +=
            $quantidade;

    }

}


/* =========================================================
   FUNCIONÁRIOS
========================================================= */

$funcionarios = [];


$sqlFuncionarios = "
    SELECT nome
    FROM funcionario
    ORDER BY nome ASC
";


$resultadoFuncionarios =
    mysqli_query(
        $conexao,
        $sqlFuncionarios
    );


if ($resultadoFuncionarios) {

    while (
        $funcionario =
        mysqli_fetch_assoc(
            $resultadoFuncionarios
        )
    ) {

        $funcionarios[] =
            $funcionario;

    }

}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<title>
Fichário — Painel do Bibliotecário
</title>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>


<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>


<link
    href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;1,0,0,500&family=IBM+Plex+Mono:wght@400;500;600&family=Source+Sans+3:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>


<style>

/* =========================================================
   CORES
========================================================= */

:root {

    --paper:#efe7d6;

    --paper-deep:#e6dcc4;

    --ink-green:#1f3b2c;

    --ink-green-2:#16291d;

    --burgundy:#7a2331;

    --burgundy-dark:#5e1a25;

    --brass:#a9843c;

    --ink:#2b241b;

    --ink-soft:#5c5240;

    --rule:#c9bfa0;

    --ok-green:#3c6b46;

    --white-card:#faf7ee;

}


/* =========================================================
   RESET
========================================================= */

* {

    box-sizing:border-box;

}


html,
body {

    margin:0;

    padding:0;

}


body {

    background:var(--paper);

    background-image:

        repeating-linear-gradient(
            0deg,
            rgba(0,0,0,0.015) 0px,
            rgba(0,0,0,0.015) 1px,
            transparent 1px,
            transparent 3px
        );

    color:var(--ink);

    font-family:'Source Sans 3',sans-serif;

    min-height:100vh;

}


/* =========================================================
   APP
========================================================= */

.app {

    display:flex;

    min-height:100vh;

}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    width:240px;

    flex-shrink:0;

    background:

        linear-gradient(
            180deg,
            var(--ink-green) 0%,
            var(--ink-green-2) 100%
        );

    color:#e9e2cd;

    padding:28px 0 20px;

    display:flex;

    flex-direction:column;

    position:sticky;

    top:0;

    height:100vh;

}


.brand {

    padding:0 24px 22px;

    border-bottom:

        1px solid
        rgba(233,226,205,0.15);

    margin-bottom:18px;

}


.brand .mark {

    font-family:'Fraunces',serif;

    font-weight:600;

    font-size:27px;

    color:#f2ecda;

}


.brand .sub {

    font-family:'IBM Plex Mono',monospace;

    font-size:10.5px;

    letter-spacing:1.5px;

    text-transform:uppercase;

    color:#a9b8a3;

    margin-top:6px;

}


nav {

    padding:0 12px;

    display:flex;

    flex-direction:column;

    gap:3px;

}


.nav-btn {

    display:flex;

    align-items:center;

    gap:12px;

    background:none;

    border:none;

    color:#cfd6bf;

    font-family:'Source Sans 3',sans-serif;

    font-size:15px;

    font-weight:500;

    text-align:left;

    padding:11px 12px;

    border-radius:3px;

    cursor:pointer;

    text-decoration:none;

}


.nav-btn:hover {

    background:
        rgba(233,226,205,0.08);

    color:#f2ecda;

}


.nav-btn.active {

    background:
        rgba(233,226,205,0.1);

    color:#f7f1de;

}


.sidebar-foot {

    margin-top:auto;

    padding:16px 24px 0;

    border-top:
        1px solid
        rgba(233,226,205,0.15);

    font-family:'IBM Plex Mono',monospace;

    font-size:10.5px;

    color:#8b9a85;

}


/* =========================================================
   MAIN
========================================================= */

main {

    flex:1;

    padding:40px 48px 60px;

    max-width:1200px;

}


.page-head {

    display:flex;

    justify-content:space-between;

    align-items:flex-end;

    margin-bottom:30px;

}


.eyebrow {

    font-family:'IBM Plex Mono',monospace;

    font-size:11px;

    letter-spacing:2px;

    text-transform:uppercase;

    color:var(--brass);

    margin-bottom:8px;

}


.page-head h1 {

    font-family:'Fraunces',serif;

    font-weight:600;

    font-size:32px;

    margin:0 0 6px;

    color:var(--ink-green);

}


.page-head p {

    margin:0;

    color:var(--ink-soft);

    font-size:14.5px;

}


/* =========================================================
   CARDS
========================================================= */

.stats {

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:16px;

    margin-bottom:32px;

}


.stat-card {

    background:var(--white-card);

    border:1px solid var(--rule);

    border-radius:4px;

    padding:16px 18px;

    position:relative;

}


.stat-card::after {

    content:"";

    position:absolute;

    top:0;

    left:0;

    bottom:0;

    width:3px;

    background:var(--stat-color);

}


.stat-card .num {

    font-family:'IBM Plex Mono',monospace;

    font-size:30px;

    font-weight:600;

    color:var(--ink-green);

}


.stat-card .lbl {

    margin-top:8px;

    font-size:12.5px;

    color:var(--ink-soft);

    text-transform:uppercase;

    letter-spacing:.6px;

}


/* =========================================================
   CARD
========================================================= */

.card {

    background:var(--white-card);

    border:1px solid var(--rule);

    border-radius:4px;

}


/* =========================================================
   TABELA
========================================================= */

.table-wrap {

    overflow-x:auto;

}


table {

    width:100%;

    border-collapse:collapse;

    min-width:850px;

}


thead th {

    text-align:left;

    font-family:'IBM Plex Mono',monospace;

    font-size:11px;

    letter-spacing:1px;

    text-transform:uppercase;

    color:var(--ink-green);

    padding:14px 16px;

    border-bottom:2px solid var(--ink-green);

    background:#e9e1cb;

}


tbody td {

    padding:14px 16px;

    border-bottom:1px solid var(--rule);

    font-size:14.5px;

    vertical-align:middle;

}


tbody tr:hover {

    background:
        rgba(122,35,49,0.04);

}


.book-title {

    font-weight:600;

    color:var(--ink);

}


.book-author {

    display:block;

    font-size:12.5px;

    color:var(--ink-soft);

    margin-top:2px;

}


.description {

    max-width:260px;

    color:var(--ink-soft);

    font-size:13px;

}


/* =========================================================
   STATUS
========================================================= */

.stamp {

    display:inline-flex;

    align-items:center;

    padding:4px 10px;

    border-radius:999px;

    border:1.5px solid currentColor;

    font-family:'IBM Plex Mono',monospace;

    font-size:10px;

    text-transform:uppercase;

}


.stamp.available {

    color:var(--ok-green);

}


.stamp.unavailable {

    color:var(--burgundy);

}


.stamp.other {

    color:var(--brass);

}


/* =========================================================
   FORM
========================================================= */

.form-card {

    padding:28px 32px;

    max-width:700px;

}


.form-grid {

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:18px 20px;

    margin-bottom:20px;

}


.field {

    display:flex;

    flex-direction:column;

    gap:6px;

}


.field.full {

    grid-column:1 / -1;

}


.field label {

    font-family:'IBM Plex Mono',monospace;

    font-size:11px;

    letter-spacing:1px;

    text-transform:uppercase;

    color:var(--ink-soft);

}


.field input,
.field select,
.field textarea {

    font-family:'Source Sans 3',sans-serif;

    font-size:15px;

    padding:10px 12px;

    border:1px solid var(--rule);

    border-radius:3px;

    background:#fff;

    color:var(--ink);

}


.field textarea {

    min-height:120px;

    resize:vertical;

}


.btn-primary {

    font-family:'Source Sans 3',sans-serif;

    font-weight:700;

    font-size:15px;

    padding:11px 22px;

    border-radius:3px;

    border:none;

    background:var(--burgundy);

    color:#f7f1de;

    cursor:pointer;

}


.btn-primary:hover {

    background:var(--burgundy-dark);

}


/* =========================================================
   FUNCIONÁRIOS
========================================================= */

.staff-list {

    padding:8px 0;

}


.staff-row {

    display:flex;

    align-items:center;

    gap:16px;

    padding:16px 24px;

    border-bottom:1px solid var(--rule);

}


.avatar {

    width:42px;

    height:42px;

    border-radius:50%;

    background:var(--ink-green);

    color:#f2ecda;

    display:flex;

    align-items:center;

    justify-content:center;

    font-family:'Fraunces',serif;

    font-weight:600;

}


.staff-info {

    flex:1;

}


.staff-info .name {

    font-weight:600;

}


.staff-info .role {

    font-size:13px;

    color:var(--ink-soft);

}


/* =========================================================
   RESPONSIVO
========================================================= */

@media(max-width:900px) {

    .app {

        flex-direction:column;

    }


    .sidebar {

        width:100%;

        height:auto;

        position:relative;

        padding:16px;

    }


    nav {

        flex-direction:row;

        overflow-x:auto;

    }


    .sidebar-foot {

        display:none;

    }


    main {

        padding:24px 20px;

    }


    .stats {

        grid-template-columns:
            repeat(2,1fr);

    }

}


@media(max-width:600px) {

    .stats {

        grid-template-columns:1fr;

    }


    .form-grid {

        grid-template-columns:1fr;

    }


    .field.full {

        grid-column:auto;

    }

}

</style>

</head>


<body>


<div class="app">


<!-- =====================================================
   MENU LATERAL
===================================================== -->

<aside class="sidebar">


    <div class="brand">

        <div class="mark">
            Fichário
        </div>

        <div class="sub">
            Painel do bibliotecário
        </div>

    </div>


    <nav>


        <button
            class="nav-btn active"
            onclick="mostrarTela('acervo', this)"
        >
            📚 Acervo
        </button>


        <button
            class="nav-btn"
            onclick="mostrarTela('cadastro-livro', this)"
        >
            📖 Cadastrar livro
        </button>


        <button
            class="nav-btn"
            onclick="mostrarTela('cadastro-funcionario', this)"
        >
            👤 Cadastrar funcionário
        </button>


        <button
            class="nav-btn"
            onclick="mostrarTela('funcionarios', this)"
        >
            👥 Funcionários
        </button>


        <a
            href="logout.php"
            class="nav-btn"
        >
            🚪 Sair
        </a>


    </nav>


    <div class="sidebar-foot">

        Usuário:

        <?= htmlspecialchars(
            $_SESSION['nome'] ?? 'Funcionário'
        ) ?>

        <br><br>

        Sistema da Biblioteca

    </div>


</aside>


<!-- =====================================================
   CONTEÚDO
===================================================== -->

<main>


<!-- =====================================================
   ACERVO
===================================================== -->

<section id="acervo">


    <div class="page-head">

        <div>

            <div class="eyebrow">
                Catálogo geral
            </div>

            <h1>
                Acervo da biblioteca
            </h1>

            <p>
                Consulte os livros cadastrados na biblioteca.
            </p>

        </div>

    </div>


    <!-- ESTATÍSTICAS -->

    <div class="stats">


        <div
            class="stat-card"
            style="--stat-color:var(--ink-green)"
        >

            <div class="num">

                <?= $totalTitulos ?>

            </div>

            <div class="lbl">

                Títulos cadastrados

            </div>

        </div>


        <div
            class="stat-card"
            style="--stat-color:var(--brass)"
        >

            <div class="num">

                <?= $totalExemplares ?>

            </div>

            <div class="lbl">

                Exemplares

            </div>

        </div>


        <div
            class="stat-card"
            style="--stat-color:var(--ok-green)"
        >

            <div class="num">

                <?= $totalDisponiveis ?>

            </div>

            <div class="lbl">

                Disponíveis

            </div>

        </div>


        <div
            class="stat-card"
            style="--stat-color:var(--burgundy)"
        >

            <div class="num">

                <?= $totalIndisponiveis ?>

            </div>

            <div class="lbl">

                Indisponíveis

            </div>

        </div>


    </div>


    <!-- TABELA -->

    <div class="card">


        <div class="table-wrap">


            <table>


                <thead>

                    <tr>

                        <th>
                            Título
                        </th>

                        <th>
                            Autor
                        </th>

                        <th>
                            Gênero
                        </th>

                        <th>
                            Quantidade
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Descrição
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (empty($livros)): ?>


                    <tr>

                        <td
                            colspan="6"
                            style="
                                text-align:center;
                                padding:50px;
                            "
                        >

                            📚 Nenhum livro cadastrado.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach ($livros as $livro): ?>


                        <?php

                        $status =
                            strtolower(
                                trim(
                                    $livro['status']
                                )
                            );


                        if (
                            $status === 'ativo' ||
                            $status === 'livre' ||
                            $status === 'dispon'
                        ) {

                            $classeStatus =
                                'available';

                        } elseif (
                            $status === 'inativo' ||
                            $status === 'indis'
                        ) {

                            $classeStatus =
                                'unavailable';

                        } else {

                            $classeStatus =
                                'other';

                        }

                        ?>


                        <tr>


                            <td>

                                <span class="book-title">

                                    <?= htmlspecialchars(
                                        $livro['titulo']
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $livro['autor']
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $livro['genero']
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $livro['quantidade']
                                ) ?>

                            </td>


                            <td>

                                <span
                                    class="stamp
                                    <?= $classeStatus ?>"
                                >

                                    <?= htmlspecialchars(
                                        $livro['status']
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <div class="description">

                                    <?= htmlspecialchars(
                                        $livro['descricao']
                                    ) ?>

                                </div>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>


</section>


<!-- =====================================================
   CADASTRAR LIVRO
===================================================== -->

<section
    id="cadastro-livro"
    style="display:none;"
>


    <div class="page-head">

        <div>

            <div class="eyebrow">
                Novo título
            </div>

            <h1>
                Cadastrar livro
            </h1>

            <p>
                Adicione um novo livro ao acervo.
            </p>

        </div>

    </div>


    <div class="card form-card">


        <form
            method="post"
            action="cadastrar_livro.php"
        >


            <div class="form-grid">


                <div class="field full">

                    <label>
                        Título
                    </label>

                    <input
                        type="text"
                        name="titulo"
                        maxlength="30"
                        placeholder="Ex.: Dom Casmurro"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Autor
                    </label>

                    <input
                        type="text"
                        name="autor"
                        maxlength="30"
                        placeholder="Nome do autor"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Gênero
                    </label>

                    <input
                        type="text"
                        name="genero"
                        maxlength="15"
                        placeholder="Ex.: Romance"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Quantidade
                    </label>

                    <input
                        type="number"
                        name="quantidade"
                        min="1"
                        value="1"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Status
                    </label>

                    <select
                        name="status"
                        required
                    >

                        <option value="ativo">
                            Ativo
                        </option>

                        <option value="inati">
                            Inativo
                        </option>

                    </select>

                </div>


                <div class="field full">

                    <label>
                        Descrição
                    </label>

                    <textarea
                        name="descricao"
                        maxlength="350"
                        placeholder="Descrição do livro"
                    ></textarea>

                </div>


            </div>


            <button
                type="submit"
                class="btn-primary"
            >

                Cadastrar livro

            </button>


        </form>


    </div>


</section>


<!-- =====================================================
   CADASTRAR FUNCIONÁRIO
===================================================== -->

<section
    id="cadastro-funcionario"
    style="display:none;"
>


    <div class="page-head">

        <div>

            <div class="eyebrow">
                Novo colaborador
            </div>

            <h1>
                Cadastrar funcionário
            </h1>

            <p>
                Registre um novo funcionário.
            </p>

        </div>

    </div>


    <div class="card form-card">


        <form
            method="post"
            action="cad_funcionario.php"
        >


            <div class="form-grid">


                <div class="field full">

                    <label>
                        Nome completo
                    </label>

                    <input
                        type="text"
                        name="nome"
                        placeholder="Nome completo"
                        required
                    >

                </div>


                <div class="field full">

                    <label>
                        Senha
                    </label>

                    <input
                        type="password"
                        name="senha"
                        placeholder="Senha"
                        required
                    >

                </div>


            </div>


            <button
                type="submit"
                class="btn-primary"
            >

                Cadastrar funcionário

            </button>


        </form>


    </div>


</section>


<!-- =====================================================
   FUNCIONÁRIOS
===================================================== -->

<section
    id="funcionarios"
    style="display:none;"
>


    <div class="page-head">

        <div>

            <div class="eyebrow">
                Equipe
            </div>

            <h1>
                Funcionários cadastrados
            </h1>

            <p>
                Funcionários registrados no sistema.
            </p>

        </div>

    </div>


    <div class="card">


        <div class="staff-list">


        <?php if (empty($funcionarios)): ?>


            <div
                style="
                    padding:50px;
                    text-align:center;
                    color:var(--ink-soft);
                "
            >

                👤 Nenhum funcionário cadastrado.

            </div>


        <?php else: ?>


            <?php foreach (
                $funcionarios
                as $funcionario
            ): ?>


                <?php

                $nome =
                    $funcionario['nome'];

                $partes =
                    preg_split(
                        '/\s+/',
                        trim($nome)
                    );

                $iniciais = '';

                foreach (
                    array_slice(
                        $partes,
                        0,
                        2
                    )
                    as $parte
                ) {

                    $iniciais .=
                        strtoupper(
                            substr(
                                $parte,
                                0,
                                1
                            )
                        );

                }

                ?>


                <div class="staff-row">


                    <div class="avatar">

                        <?= htmlspecialchars(
                            $iniciais
                        ) ?>

                    </div>


                    <div class="staff-info">

                        <div class="name">

                            <?= htmlspecialchars(
                                $nome
                            ) ?>

                        </div>


                        <div class="role">

                            Funcionário da biblioteca

                        </div>

                    </div>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


        </div>


    </div>


</section>


</main>


</div>


<script>

function mostrarTela(
    nome,
    botao
) {


    const telas = [

        'acervo',

        'cadastro-livro',

        'cadastro-funcionario',

        'funcionarios'

    ];


    telas.forEach(
        function(tela) {


            const elemento =
                document.getElementById(
                    tela
                );


            if (elemento) {

                elemento.style.display =
                    tela === nome
                    ? ''
                    : 'none';

            }


        }
    );


    document
        .querySelectorAll(
            '.nav-btn'
        )
        .forEach(
            function(item) {

                item.classList.remove(
                    'active'
                );

            }
        );


    if (botao) {

        botao.classList.add(
            'active'
        );

    }

}

</script>


</body>

</html>
