<?php

include "conexao.php";

$mensagem = "";

function validarCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }

        $d = ((10 * $d) % 11) % 10;

        if ($cpf[$c] != $d) {
            return false;
        }
    }

    return true;
}

function criptografarCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    $chave = "minha_chave_secreta_123";
    $metodo = "AES-256-CBC";
    $iv = substr(hash('sha256', $chave), 0, 16);

    return openssl_encrypt($cpf, $metodo, $chave, 0, $iv);
}

if (isset($_POST['inserir'])) {

    $nome = trim($_POST['nome']);
    $senha = trim($_POST['senha']);
    $telefone = trim($_POST['telefone']);
    $cpf = trim($_POST['cpf']);

    $erro = false;

    $senhaForte = preg_match(
        '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
        $senha
    );

    if (!$senhaForte) {
        $mensagem .= "<p class='erro'>A senha deve ter no mínimo 8 caracteres, com letra maiúscula, minúscula, número e símbolo.</p>";
        $erro = true;
    }

    if (!validarCPF($cpf)) {
        $mensagem .= "<p class='erro'>CPF inválido.</p>";
        $erro = true;
    }

    if (!$erro) {

        $cpf = criptografarCPF($cpf);
        $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $conexao->prepare("
            INSERT INTO funcionario
            (nome, senha, telefone, cpf)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssss",
            $nome,
            $senhaCriptografada,
            $telefone,
            $cpf
        );

        if ($stmt->execute()) {
            $mensagem = "<p class='sucesso'>Cadastro realizado com sucesso!</p>";
        } else {
            $mensagem = "<p class='erro'>Erro ao cadastrar: {$stmt->error}</p>";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>

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
            background: linear-gradient(
                135deg,
                #0f172a 0%,
                #1e293b 50%,
                #0f172a 100%
            );
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--cor-texto);
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(
                circle,
                rgba(99, 102, 241, 0.1) 0%,
                transparent 70%
            );
            animation: float 20s ease-in-out infinite;
            pointer-events: none;
            z-index: -1;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(
                circle,
                rgba(139, 92, 246, 0.1) 0%,
                transparent 70%
            );
            animation: float 25s ease-in-out infinite reverse;
            pointer-events: none;
            z-index: -1;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0);
            }

            33% {
                transform: translate(30px, -30px);
            }

            66% {
                transform: translate(-20px, 20px);
            }
        }

        .container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .container form {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow:
                0 25px 50px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .container h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 35px;
            background: linear-gradient(
                135deg,
                #6366f1,
                #8b5cf6
            );
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }

        .container label {
            display: block;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 0.95rem;
            font-weight: 500;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .container input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(51, 65, 85, 0.5);
            border: 2px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            color: var(--cor-texto);
            font-size: 1rem;
            transition: var(--transicao);
            backdrop-filter: blur(10px);
        }

        .container input:focus {
            outline: none;
            border-color: var(--cor-principal);
            background: rgba(51, 65, 85, 0.8);
            box-shadow:
                0 0 20px rgba(99, 102, 241, 0.3),
                inset 0 0 10px rgba(99, 102, 241, 0.1);
        }

        .botoes {
            margin-top: 10px;
        }

        .btn-cadastrar,
        .btn-login {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicao);
            letter-spacing: 0.5px;
        }

        .btn-cadastrar {
            margin-top: 15px;
            background: linear-gradient(
                135deg,
                #6366f1 0%,
                #8b5cf6 100%
            );
            border: none;
            color: white;
            text-transform: uppercase;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }

        .btn-cadastrar:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.5);
        }

        .btn-cadastrar:active {
            transform: translateY(-1px);
        }

        .btn-login {
            margin-top: 12px;
            background: transparent;
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #6366f1;
        }

        .btn-login:hover {
            background: rgba(99, 102, 241, 0.1);
            border-color: #6366f1;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.2);
            transform: translateY(-2px);
        }

        .botoes a {
            display: block;
            text-decoration: none;
        }

        .erro {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            text-align: center;
        }

        .sucesso {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.5);
            color: #86efac;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            text-align: center;
        }

        @media (max-width: 600px) {
            .container form {
                padding: 35px 25px;
            }

            .container h2 {
                font-size: 2rem;
                margin-bottom: 30px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <form method="post">

        <h2>Cadastro</h2>

        <?php echo $mensagem; ?>

        <label>Nome</label>
        <input
            type="text"
            name="nome"
            autocomplete="off"
            required
        >

        <label>Telefone</label>
        <input
            type="text"
            name="telefone"
            autocomplete="off"
            required
        >

        <label>CPF</label>
        <input
            type="text"
            name="cpf"
            autocomplete="off"
            required
        >

        <label>Senha</label>
        <input
            type="password"
            name="senha"
            required
        >

        <div class="botoes">

            <button
                type="submit"
                name="inserir"
                class="btn-cadastrar"
            >
                Cadastrar
            </button>

            <a href="login_funcionario.php">
                <button
                    type="button"
                    class="btn-login"
                >
                    Já fez login?
                </button>
            </a>

        </div>

    </form>

</div>

</body>
</html>
