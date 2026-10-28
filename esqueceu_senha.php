
<?php
session_start();
require_once __DIR__ . "/conexao.php";

use PHPMailer\PHPMailer\PHPMailer;

$autoload = dirname(__DIR__) . "/vendor/autoload.php";
$mailerDisponivel = is_file($autoload);
if ($mailerDisponivel) {
    require_once $autoload;
}

if (!isset($_SESSION["csrf_recuperacao"])) {
    $_SESSION["csrf_recuperacao"] = bin2hex(random_bytes(32));
}

$mensagem = "";
$tipoMensagem = "sucesso";

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    $email = isset($_POST["email"]) && is_string($_POST["email"])
        ? trim($_POST["email"])
        : "";
    $csrf = isset($_POST["csrf_token"]) && is_string($_POST["csrf_token"])
        ? $_POST["csrf_token"]
        : "";

    if (!hash_equals($_SESSION["csrf_recuperacao"], $csrf)) {
        $mensagem = "Sua sessão expirou. Atualize a página e tente novamente.";
        $tipoMensagem = "erro";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Digite um e-mail válido.";
        $tipoMensagem = "erro";
    } else {
        try {
            if (!($conexao instanceof mysqli) || $conexao->connect_errno) {
                throw new RuntimeException("Conexão com o banco indisponível.");
            }

            $stmt = $conexao->prepare("SELECT id, nome FROM leitores WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $leitor = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($leitor) {
                $smtpUsuario = getenv("SMTP_USER") ?: "";
                $smtpSenha = getenv("SMTP_PASS") ?: "";
                $smtpHost = getenv("SMTP_HOST") ?: "smtp.gmail.com";
                $smtpPorta = (int) (getenv("SMTP_PORT") ?: 587);
                $urlBase = rtrim(getenv("APP_BASE_URL") ?: "", "/");
                $urlValida = filter_var($urlBase, FILTER_VALIDATE_URL)
                    && in_array(parse_url($urlBase, PHP_URL_SCHEME), ["http", "https"], true);

                if (!$mailerDisponivel || $smtpUsuario === "" || $smtpSenha === "" || !$urlValida) {
                    throw new RuntimeException("Configure o PHPMailer, SMTP_USER, SMTP_PASS e APP_BASE_URL.");
                }

                $token = bin2hex(random_bytes(32));
                $hashToken = hash("sha256", $token);
                $expiraEm = gmdate("Y-m-d H:i:s", time() + 3600);

                $conexao->begin_transaction();
                $stmt = $conexao->prepare("DELETE FROM redefinicao_senha WHERE leitor_id = ?");
                $stmt->bind_param("i", $leitor["id"]);
                $stmt->execute();
                $stmt->close();

                $stmt = $conexao->prepare(
                    "INSERT INTO redefinicao_senha (leitor_id, token_hash, expira_em) VALUES (?, ?, ?)"
                );
                $stmt->bind_param("iss", $leitor["id"], $hashToken, $expiraEm);
                $stmt->execute();
                $stmt->close();
                $conexao->commit();

                $link = $urlBase . "/redefinir_senha.php?token=" . urlencode($token);
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUsuario;
                $mail->Password = $smtpSenha;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;
                $mail->CharSet = "UTF-8";
                $mail->setFrom($smtpUsuario, "Biblioteca");
                $mail->addAddress($email, $leitor["nome"]);
                $mail->isHTML(true);
                $mail->Subject = "Recuperação de senha - Biblioteca";
                $nomeSeguro = htmlspecialchars($leitor["nome"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
                $linkSeguro = htmlspecialchars($link, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
                $mail->Body = "<p>Olá, <strong>{$nomeSeguro}</strong>.</p>
                    <p>Use o link abaixo para redefinir sua senha. Ele expira em uma hora.</p>
                    <p><a href=\"{$linkSeguro}\">Redefinir senha</a></p>
                    <p>Se você não solicitou a recuperação, ignore este e-mail.</p>";
                $mail->AltBody = "Olá, {$leitor["nome"]}. Redefina sua senha neste link: {$link}";
                $mail->send();
            }

            $mensagem = "Se o e-mail estiver cadastrado, você receberá as instruções em instantes.";
        } catch (Throwable $erro) {
            if (isset($conexao) && $conexao instanceof mysqli) {
                try {
                    $conexao->rollback();
                } catch (Throwable $erroRollback) {
                    error_log("Falha ao reverter solicitação de recuperação: " . $erroRollback->getMessage());
                }
            }
            error_log("Erro na recuperação de senha: " . $erro->getMessage());
            $mensagem = "Se o e-mail estiver cadastrado, você receberá as instruções em instantes.";
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="pt-BR">
 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueceu a senha</title>
 
    <style>
        :root {
            --cor-principal: #b061fa;
            --cor-texto: #ffffff;
            --transicao: 0.3s ease;
        }
 
        * { margin: 0; padding: 0; box-sizing: border-box; }
 
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
 
        .login-container { width: 100%; max-width: 450px; padding: 20px; }
 
        .login-box {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
 
        .login-box h1 {
            text-align: center;
            font-size: 2.2rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }
 
        .descricao {
            text-align: center;
            color: #cbd5e1;
            font-size: 0.95rem;
            margin-bottom: 30px;
        }
 
        .form-group { margin-bottom: 25px; }
 
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
        }
 
        .form-group input:focus {
            outline: none;
            border-color: var(--cor-principal);
            background: rgba(51, 65, 85, 0.8);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
        }
 
        .btn-entrar {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicao);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }
 
        .btn-entrar:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.5);
        }
 
        .links-footer a {
            display: block;
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
        }
 
        .erro, .sucesso {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            text-align: center;
        }
 
        .erro {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
        }
 
        .sucesso {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.5);
            color: #86efac;
        }
 
        @media (max-width: 600px) {
            .login-box { padding: 35px 25px; }
            .login-box h1 { font-size: 1.8rem; }
        }
    </style>
</head>
 
<body>
 
    <div class="login-container">
        <div class="login-box">
 
            <h1>Esqueceu a senha?</h1>
            <p class="descricao">Digite o e-mail do seu cadastro e enviaremos as instruções.</p>
 
            <?php if ($mensagem !== ""): ?>
                <p class="<?= htmlspecialchars($tipoMensagem, ENT_QUOTES, "UTF-8") ?>" role="alert"><?= htmlspecialchars($mensagem, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") ?></p>
            <?php endif; ?>
 
            <form method="post" action="esqueceu_senha.php" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_recuperacao"], ENT_QUOTES, "UTF-8") ?>">
 
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" maxlength="190" autocomplete="email" placeholder="Digite seu e-mail" required>
                </div>
 
                <button type="submit" name="enviar" class="btn-entrar">Enviar</button>
 
                <div class="links-footer">
                    <a href="login_leitor.php">Voltar ao login</a>
                </div>
 
            </form>
 
        </div>
    </div>
 
</body>
 
</html>
 
Claude terminou a resposta
Localizar…
