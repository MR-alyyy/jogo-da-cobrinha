<?php
include "conexao.php";

$mensagem = "";

if (isset($_POST['inserir'])) {
    $titulo = trim($_POST['titulo']);
    $autor = trim($_POST['autor']);
    $genero = trim($_POST['genero']);
    $quantidade = (int) trim($_POST['quantidade']);
    $descricao = trim($_POST['descricao']);

    $stmt = $conexao->prepare("INSERT INTO livros (titulo, autor, genero, quantidade, descricao) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $titulo, $autor, $genero, $quantidade, $descricao);

    if ($stmt->execute()) {
        $mensagem = "<p class='sucesso'>Cadastro realizado com sucesso!</p>";
    } else {
        $mensagem = "<p class='erro'>Erro ao cadastrar: " . $stmt->error . "</p>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro de Livro</title>
<style>
:root { --cor-principal:#b061fa; --cor-texto:#fff; --transicao:.3s ease; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:linear-gradient(135deg,#0f172a 0%,#1e293b 50%,#0f172a 100%); min-height:100vh; display:flex; justify-content:center; align-items:center; color:var(--cor-texto); overflow:hidden; }
body::before,body::after { content:''; position:fixed; width:200%; height:200%; pointer-events:none; z-index:-1; }
body::before { top:-50%; left:-50%; background:radial-gradient(circle,rgba(99,102,241,.1) 0%,transparent 70%); animation:float 20s ease-in-out infinite; }
body::after { bottom:-50%; right:-50%; background:radial-gradient(circle,rgba(139,92,246,.1) 0%,transparent 70%); animation:float 25s ease-in-out infinite reverse; }
@keyframes float { 0%,100%{transform:translate(0,0)} 33%{transform:translate(30px,-30px)} 66%{transform:translate(-20px,20px)} }
.container { width:100%; max-width:450px; padding:20px; }
.container form { background:rgba(30,41,59,.8); backdrop-filter:blur(10px); border:1px solid rgba(99,102,241,.2); border-radius:20px; padding:50px 40px; box-shadow:0 25px 50px rgba(0,0,0,.5),inset 0 1px 0 rgba(255,255,255,.1); animation:slideUp .6s ease; }
@keyframes slideUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
.container h2 { text-align:center; font-size:2.2rem; margin-bottom:15px; background:linear-gradient(135deg,#6366f1,#8b5cf6); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; font-weight:700; }
.links-footer { margin-bottom:10px; }
.links-footer a { display:block; width:100%; text-align:center; color:#6366f1; text-decoration:none; font-size:.9rem; padding:10px; border:1px solid rgba(99,102,241,.3); border-radius:8px; transition:var(--transicao); }
.links-footer a:hover { background:rgba(99,102,241,.1); border-color:#6366f1; box-shadow:0 0 15px rgba(99,102,241,.2); }
.container label { display:block; margin:20px 0 10px; font-size:.95rem; font-weight:500; color:#cbd5e1; text-transform:uppercase; letter-spacing:.5px; }
.container input { width:100%; padding:14px 16px; background:rgba(51,65,85,.5); border:2px solid rgba(99,102,241,.2); border-radius:12px; color:var(--cor-texto); font-size:1rem; transition:var(--transicao); backdrop-filter:blur(10px); }
.container input:focus { outline:none; border-color:var(--cor-principal); background:rgba(51,65,85,.8); box-shadow:0 0 20px rgba(99,102,241,.3),inset 0 0 10px rgba(99,102,241,.1); }
.btn-cadastrar { width:100%; padding:14px; margin-top:25px; background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%); border:none; border-radius:12px; color:white; font-size:1.05rem; font-weight:600; cursor:pointer; transition:var(--transicao); text-transform:uppercase; letter-spacing:.5px; box-shadow:0 10px 30px rgba(99,102,241,.3); }
.btn-cadastrar:hover { transform:translateY(-3px); box-shadow:0 15px 40px rgba(99,102,241,.5); }
.erro { background:rgba(239,68,68,.15); border:1px solid rgba(239,68,68,.5); color:#fca5a5; padding:12px; border-radius:8px; margin-bottom:20px; font-size:.9rem; text-align:center; }
.sucesso { background:rgba(34,197,94,.15); border:1px solid rgba(34,197,94,.5); color:#86efac; padding:12px; border-radius:8px; margin-bottom:20px; font-size:.9rem; text-align:center; }
@media(max-width:600px){ .container form{padding:35px 25px} .container h2{font-size:2rem} }
</style>
</head>
<body>
<div class="container">
<form method="post">
    <h2>Cadastro</h2>

    <?php echo $mensagem; ?>

    <label>Título</label>
    <input name="titulo" type="text">

    <label>Autor</label>
    <input name="autor" type="text">

    <label>Gênero</label>
    <input name="genero" type="text">

    <label>Quantidade</label>
    <input name="quantidade" type="text">

    <label>Descrição</label>
    <input name="descricao" type="text">

    <button type="submit" name="inserir" class="btn-cadastrar">Cadastrar</button>

    <div class="links-footer" style="margin-top:12px; margin-bottom:0;">
        <a href="menu_fun.php">&larr; Voltar ao menu</a>
    </div>
</form>
</div>
</body>
</html>
