Claude
















<?php
require_once __DIR__ . "/conexao.php";

$mensagem = "";
$tipoMensagem = "erro";

function validarCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
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

    $chaveHex = getenv("CPF_ENCRYPTION_KEY") ?: "";
    $chave = ctype_xdigit($chaveHex) && strlen($chaveHex) === 64
        ? hex2bin($chaveHex)
        : false;
    if ($chave === false) {
        throw new RuntimeException("Configure CPF_ENCRYPTION_KEY com 64 caracteres hexadecimais.");
    }

    $iv = random_bytes(12);
    $tag = "";
    $cifrado = openssl_encrypt($cpf, "aes-256-gcm", $chave, OPENSSL_RAW_DATA, $iv, $tag);
    if ($cifrado === false) {
        throw new RuntimeException("Não foi possível proteger o CPF.");
    }

    return base64_encode($iv . $tag . $cifrado);
}

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    $nome = isset($_POST["nome"]) && is_string($_POST["nome"]) ? trim($_POST["nome"]) : "";
    $email = isset($_POST["email"]) && is_string($_POST["email"]) ? trim($_POST["email"]) : "";
    $senha = isset($_POST["senha"]) && is_string($_POST["senha"]) ? trim($_POST["senha"]) : "";
    $telefone = isset($_POST["telefone"]) && is_string($_POST["telefone"]) ? trim($_POST["telefone"]) : "";
    $cpf = isset($_POST["cpf"]) && is_string($_POST["cpf"]) ? trim($_POST["cpf"]) : "";
    $endereco = isset($_POST["endereco"]) && is_string($_POST["endereco"]) ? trim($_POST["endereco"]) : "";

    $tamanhoNome = function_exists("mb_strlen") ? mb_strlen($nome, "UTF-8") : strlen($nome);
    $tamanhoEndereco = function_exists("mb_strlen") ? mb_strlen($endereco, "UTF-8") : strlen($endereco);
    if ($nome === "" || $tamanhoNome > 30 || $telefone === "" || strlen($telefone) > 15 || $tamanhoEndereco > 60) {
        $mensagem = "Confira o nome, telefone e endereço informados.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $mensagem = "Digite um e-mail válido.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $senha)) {
        $mensagem = "A senha deve ter no mínimo 8 caracteres, com letra maiúscula, minúscula, número e símbolo.";
    } elseif (!validarCPF($cpf)) {
        $mensagem = "CPF inválido.";
    } elseif (!($conexao instanceof mysqli) || $conexao->connect_errno) {
        error_log("Falha na conexão com o banco durante o cadastro do leitor.");
        $mensagem = "Não foi possível concluir o cadastro agora.";
    } else {
        try {
            $cpfProtegido = criptografarCPF($cpf);
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conexao->prepare(
                "INSERT INTO leitores (nome, email, telefone, cpf, endereco, senha)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("ssssss", $nome, $email, $telefone, $cpfProtegido, $endereco, $senhaHash);
            $stmt->execute();
            $stmt->close();
            $mensagem = "Cadastro realizado com sucesso.";
            $tipoMensagem = "sucesso";
        } catch (mysqli_sql_exception $erro) {
            error_log("Erro no cadastro do leitor: " . $erro->getMessage());
            $mensagem = $erro->getCode() === 1062
                ? "Este e-mail já está cadastrado."
                : "Não foi possível concluir o cadastro agora.";
        } catch (Throwable $erro) {
            error_log("Erro no cadastro do leitor: " . $erro->getMessage());
            $mensagem = "Não foi possível concluir o cadastro agora.";
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="pt-br">
 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Leitor</title>
 
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
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--cor-texto);
            overflow-x: hidden;
            overflow-y: auto;
        }
 
        body::before,
        body::after {
            content: '';
            position: fixed;
            width: 200%;
            height: 200%;
            pointer-events: none;
            z-index: -1;
        }
 
        body::before {
            top: -50%;
            left: -50%;
            background: radial-gradient(circle, rgba(99, 102, 241, .1) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }
 
        body::after {
            bottom: -50%;
            right: -50%;
            background: radial-gradient(circle, rgba(139, 92, 246, .1) 0%, transparent 70%);
            animation: float 25s ease-in-out infinite reverse;
        }
 
        @keyframes float {
 
            0%,
            100% {
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
            background: rgba(30, 41, 59, .8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(99, 102, 241, .2);
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, .5),
                inset 0 1px 0 rgba(255, 255, 255, .1);
            animation: slideUp .6s ease;
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
            font-size: 2.2rem;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }
 
        .container label {
            display: block;
            margin: 20px 0 10px;
            font-size: .95rem;
            font-weight: 500;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
 
        .container input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(51, 65, 85, .5);
            border: 2px solid rgba(99, 102, 241, .2);
            border-radius: 12px;
            color: var(--cor-texto);
            font-size: 1rem;
            transition: var(--transicao);
            backdrop-filter: blur(10px);
        }
 
        .container input:focus {
            outline: none;
            border-color: var(--cor-principal);
            background: rgba(51, 65, 85, .8);
            box-shadow: 0 0 20px rgba(99, 102, 241, .3),
                inset 0 0 10px rgba(99, 102, 241, .1);
        }
 
        .btn-cadastrar {
            width: 100%;
            padding: 14px;
            margin-top: 25px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicao);
            text-transform: uppercase;
            letter-spacing: .5px;
            box-shadow: 0 10px 30px rgba(99, 102, 241, .3);
        }
 
        .btn-cadastrar:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, .5);
        }
 
        .links-footer {
            margin-top: 12px;
        }
 
        .links-footer a {
            display: block;
            width: 100%;
            text-align: center;
            color: #6366f1;
            text-decoration: none;
            font-size: .9rem;
            padding: 12px;
            border: 1px solid rgba(99, 102, 241, .3);
            border-radius: 8px;
            transition: var(--transicao);
        }
 
        .links-footer a:hover {
            background: rgba(99, 102, 241, .1);
            border-color: #6366f1;
            box-shadow: 0 0 15px rgba(99, 102, 241, .2);
        }
 
        .erro {
            background: rgba(239, 68, 68, .15);
            border: 1px solid rgba(239, 68, 68, .5);
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: .9rem;
            text-align: center;
        }
 
        .sucesso {
            background: rgba(34, 197, 94, .15);
            border: 1px solid rgba(34, 197, 94, .5);
            color: #86efac;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: .9rem;
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
 
            <h2>Cadastro de Leitor</h2>
 
            <?php if ($mensagem !== ""): ?>
                <p class="<?= htmlspecialchars($tipoMensagem, ENT_QUOTES, "UTF-8") ?>" role="alert"><?= htmlspecialchars($mensagem, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") ?></p>
            <?php endif; ?>
 
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" maxlength="30" autocomplete="name" required>
 
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" maxlength="190" autocomplete="email" required>
 
            <label for="telefone">Telefone</label>
            <input type="tel" id="telefone" name="telefone" maxlength="15" autocomplete="tel" required>
 
            <label for="cpf">CPF</label>
            <input type="text" id="cpf" name="cpf" maxlength="14" inputmode="numeric" autocomplete="off" required>
 
            <label for="endereco">Endereço</label>
            <input type="text" id="endereco" name="endereco" maxlength="60" autocomplete="street-address" required>
 
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" autocomplete="new-password" required>
 
            <button type="submit" name="inserir" class="btn-cadastrar">
                Cadastrar
            </button>
 
            <div class="links-footer">
                <a href="login_leitor.php">Já fez login?</a>
            </div>
 
        </form>
 
    </div>
 
</body>
 
</html>
 
