<?php
include "conexao.php";

$mensagem = "";

if (isset($_POST['inserir'])) {

    $nome = trim($_POST['nomeG']);
    $senha = trim($_POST['senha']);

    $erro = false;

    /* VALIDAÇÃO DE SENHA */
    $senhaForte = preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $senha);

    if (!$senhaForte) {
        $mensagem .= "<p class='erro'>A senha deve ter no mínimo 8 caracteres, com letra maiúscula, minúscula, número e símbolo.</p>";
        $erro = true;
    }

    if (!$erro) {

        $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $conexao->prepare("INSERT INTO bibliotecario (nome, senha) VALUES (?, ?)");

        $stmt->bind_param("ss", $nome, $senhaCriptografada);

        if ($stmt->execute()) {
            $mensagem = "<p class='sucesso'>Cadastro realizado com sucesso!</p>";
        } else {
            $mensagem = "<p class='erro'>Erro ao cadastrar: " . $stmt->error . "</p>";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastro Bibliotecário</title>

    <style>

        :root {
            --cor-principal: #b061fa;
            --cor-secundaria: #925fc5;
            --cor-fundo: #0f172a;
            --cor-texto: #ffffff;
            --cor-borda: #1e293b;
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

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .login-box {
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

        .login-box h1 {
            text-align: center;

            font-size: 2.5rem;

            margin-bottom: 40px;

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

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;

            margin-bottom: 10px;

            font-size: 0.95rem;

            font-weight: 500;

            color: #cbd5e1;

            text-transform: uppercase;

            letter-spacing: 0.5px;
        }

        .form-group input {
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

        .form-group input::placeholder {
            color: rgba(203, 213, 225, 0.5);
        }

        .form-group input:focus {
            outline: none;

            border-color: var(--cor-principal);

            background: rgba(51, 65, 85, 0.8);

            box-shadow:
                0 0 20px rgba(99, 102, 241, 0.3),
                inset 0 0 10px rgba(99, 102, 241, 0.1);
        }

        .password-field {
            position: relative;

            display: flex;

            align-items: center;
        }

        .password-field input {
            flex: 1;

            padding-right: 45px;
        }

        .btn-view {
            position: absolute;

            right: 15px;

            background: none;

            border: none;

            font-size: 1.2rem;

            cursor: pointer;

            transition: var(--transicao);

            padding: 8px;
        }

        .btn-view:hover {
            transform: scale(1.2);

            filter: drop-shadow(
                0 0 8px rgba(99, 102, 241, 0.5)
            );
        }

        .btn-entrar {
            width: 100%;

            padding: 14px;

            background: linear-gradient(
                135deg,
                #6366f1 0%,
                #8b5cf6 100%
            );

            border: none;

            border-radius: 12px;

            color: white;

            font-size: 1.05rem;

            font-weight: 600;

            cursor: pointer;

            transition: var(--transicao);

            text-transform: uppercase;

            letter-spacing: 0.5px;

            margin-top: 10px;

            margin-bottom: 20px;

            box-shadow:
                0 10px 30px rgba(99, 102, 241, 0.3);

            position: relative;

            overflow: hidden;
        }

        .btn-entrar::before {
            content: '';

            position: absolute;

            top: 0;
            left: -100%;

            width: 100%;
            height: 100%;

            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );

            transition: var(--transicao);
        }

        .btn-entrar:hover {
            transform: translateY(-3px);

            box-shadow:
                0 15px 40px rgba(99, 102, 241, 0.5);
        }

        .btn-entrar:hover::before {
            left: 100%;
        }

        .btn-entrar:active {
            transform: translateY(-1px);
        }

        .links-footer {
            display: flex;

            justify-content: space-between;

            gap: 15px;
        }

        .links-footer a {
            flex: 1;

            text-align: center;

            color: #6366f1;

            text-decoration: none;

            font-size: 0.9rem;

            padding: 10px;

            border: 1px solid rgba(99, 102, 241, 0.3);

            border-radius: 8px;

            transition: var(--transicao);
        }

        .links-footer a:hover {
            background: rgba(99, 102, 241, 0.1);

            border-color: #6366f1;

            box-shadow:
                0 0 15px rgba(99, 102, 241, 0.2);
        }

        /* MENSAGENS DO PHP */

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

            .login-box {
                padding: 35px 25px;
            }

            .login-box h1 {
                font-size: 2rem;

                margin-bottom: 30px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .links-footer {
                flex-direction: column;
            }

            .links-footer a {
                width: 100%;
            }
        }

    </style>
</head>

<body>

    <div class="login-container">

        <div class="login-box">

            <h1>Cadastro</h1>

            <?php echo $mensagem; ?>

            <form method="post">

                <div class="form-group">

                    <label for="nomeG">Nome</label>

                    <input
                        name="nomeG"
                        id="nomeG"
                        type="text"
                        placeholder="Digite seu nome"
                        autocomplete="off"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="senha">Senha</label>

                    <div class="password-field">

                        <input
                            name="senha"
                            id="senha"
                            type="password"
                            placeholder="Digite sua senha"
                            required
                        >

                        <button
                            type="button"
                            class="btn-view"
                            onclick="mostrarSenha()"
                        >
                            👁️
                        </button>

                    </div>

                </div>

                <button
                    type="submit"
                    name="inserir"
                    class="btn-entrar"
                >
                    Cadastrar
                </button>

            </form>

            <div class="links-footer">

                <a href="login_bibliotecario.php">
                    Fazer login
                </a>

            </div>

        </div>

    </div>

    <script>

        function mostrarSenha() {

            const campo = document.getElementById('senha');
            const botao = document.querySelector('.btn-view');

            if (campo.type === 'password') {

                campo.type = 'text';

                botao.textContent = '👁️‍🗨️';

            } else {

                campo.type = 'password';

                botao.textContent = '👁️';

            }
        }

    </script>

</body>
</html>
