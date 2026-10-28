<?php
session_start();
require_once __DIR__ . "/conexao.php";

$mensagem = "";

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    $nome = isset($_POST["nome"]) && is_string($_POST["nome"])
        ? trim($_POST["nome"])
        : "";
    $senha = isset($_POST["senha"]) && is_string($_POST["senha"])
        ? trim($_POST["senha"])
        : "";

    if ($nome === "" || $senha === "") {
        $mensagem = "Preencha todos os campos.";
    } elseif (!($conexao instanceof mysqli) || $conexao->connect_errno) {
        error_log("Falha na conexão com o banco de dados durante o login do leitor.");
        $mensagem = "Não foi possível realizar o login agora. Tente novamente mais tarde.";
    } else {
        try {
            $stmt = $conexao->prepare(
                "SELECT nome, senha FROM leitores WHERE nome = ?"
            );
            $stmt->bind_param("s", $nome);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $usuario = $resultado->fetch_assoc();
            $stmt->close();

            if ($usuario && password_verify($senha, $usuario["senha"])) {
                session_regenerate_id(true);
                $_SESSION["tipo"] = "leitor";
                $_SESSION["nome"] = $usuario["nome"];

                header("Location: tela_leitor.html");
                exit();
            }

            $mensagem = "Usuário ou senha inválidos.";
        } catch (Throwable $erro) {
            error_log("Erro ao autenticar leitor: " . $erro->getMessage());
            $mensagem = "Não foi possível realizar o login agora. Tente novamente mais tarde.";
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="pt-BR">
 
<head>
 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
 
    <title>Login Leitor</title>
 
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
 
        overflow-x: hidden;
        overflow-y: auto;
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
 
    .btn-entrar:hover {
        transform: translateY(-3px);
 
        box-shadow:
            0 15px 40px rgba(99, 102, 241, 0.5);
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
 
    .erro {
        background: rgba(239, 68, 68, 0.15);
 
        border: 1px solid rgba(239, 68, 68, 0.4);
 
        color: #fca5a5;
 
        padding: 12px 16px;
 
        border-radius: 10px;
 
        margin-bottom: 20px;
 
        font-size: 0.9rem;
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
 
            <h1>Login</h1>
 
            <?php if ($mensagem !== ""): ?>
                <p class="erro" role="alert"><?= htmlspecialchars($mensagem, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>
 
            <form method="post" action="login_leitor.php" autocomplete="on">
 
                <div class="form-group">
 
                    <label for="nome">
                        Nome
                    </label>
 
                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        maxlength="30"
                        autocomplete="username"
                        placeholder="Digite seu nome"
                        required
                    >
 
                </div>
 
                <div class="form-group">
 
                    <label for="senha">
                        Senha
                    </label>
 
                    <div class="password-field">
 
                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            autocomplete="current-password"
                            placeholder="Digite sua senha"
                            required
                        >
 
                        <button
                            type="button"
                            class="btn-view"
                            onclick="mostrarSenha()"
                            aria-label="Mostrar senha"
                            aria-pressed="false"
                        >
                            👁️
                        </button>
 
                    </div>
 
                </div>
 
                <button
                    type="submit"
                    name="entrar"
                    class="btn-entrar"
                >
                    Entrar
                </button>
 
                <div class="links-footer">
 
                    <a href="cad_leitor.php">
                        Cadastrar-se
                    </a>
 
                    <a href="esqueceu_senha.php">
                        Esqueceu a senha?
                    </a>
 
                </div>
 
            </form>
 
        </div>
 
    </div>
 
    <script>
 
        function mostrarSenha() {
 
            const campo = document.getElementById('senha');
 
            const botao = document.querySelector('.btn-view');
 
            if (campo.type === 'password') {
 
                campo.type = 'text';
 
                botao.textContent = '👁️‍🗨️';
                botao.setAttribute('aria-label', 'Ocultar senha');
                botao.setAttribute('aria-pressed', 'true');
 
            } else {
 
                campo.type = 'password';
 
                botao.textContent = '👁️';
                botao.setAttribute('aria-label', 'Mostrar senha');
                botao.setAttribute('aria-pressed', 'false');
 
            }
        }
 
    </script>
 
</body>
 
</html>
 
