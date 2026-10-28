<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu do Funcionário</title>

<style>

:root {
    --cor-principal: #b061fa;
    --cor-secundaria: #925fc5;
    --cor-fundo: #0f172a;
    --cor-texto: #ffffff;
    --transicao: 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    min-height: 100vh;
    color: var(--cor-texto);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 60px 20px;
}

h1 {
    font-size: 2.5rem;
    margin-bottom: 50px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
    text-align: center;
}

/* OPÇÕES EM UMA ÚNICA LINHA */
.menu-grid {
    display: flex;
    flex-wrap: nowrap;
    justify-content: center;
    gap: 20px;
    width: 100%;
    max-width: 1200px;
}

.menu-card {
    flex: 1;
    min-width: 0;
    background: rgba(30, 41, 59, 0.8);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(99, 102, 241, 0.2);
    border-radius: 16px;
    padding: 30px 20px;
    text-align: center;
    text-decoration: none;
    color: var(--cor-texto);
    transition: var(--transicao);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.menu-card:hover {
    transform: translateY(-6px);
    border-color: var(--cor-principal);
    box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
}

.menu-card .icone {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 15px;
}

.menu-card .titulo {
    font-size: 1.15rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.menu-card .desc {
    font-size: 0.85rem;
    color: #cbd5e1;
}

/* Em telas menores, permite quebra para não apertar demais */
@media (max-width: 850px) {
    .menu-grid {
        flex-wrap: wrap;
    }

    .menu-card {
        flex: 1 1 220px;
    }
}

</style>
</head>

<body>

<h1>Menu do Funcionário</h1>

<div class="menu-grid">

    <a href="cad_livros.php" class="menu-card">
        <span class="icone">📚</span>
        <div class="titulo">Cadastrar Livro</div>
        <div class="desc">Adicionar um novo título ao acervo</div>
    </a>

    <a href="gerenciamento.php" class="menu-card">
        <span class="icone">🗂️</span>
        <div class="titulo">Gerenciamento</div>
        <div class="desc">Ver acervo, quantidades e empréstimos ativos</div>
    </a>

    <a href="emprestar_livro.php" class="menu-card">
        <span class="icone">📖</span>
        <div class="titulo">Emprestar Livro</div>
        <div class="desc">Registrar um novo empréstimo</div>
    </a>

    <a href="devolver_livro.php" class="menu-card">
        <span class="icone">↩️</span>
        <div class="titulo">Devolver Livro</div>
        <div class="desc">Registrar devolução e calcular multa</div>
    </a>

</div>

</body>
</html>
