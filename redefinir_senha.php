<?php
session_start();
require_once __DIR__ . "/conexao.php";

if (!isset($_SESSION["csrf_redefinicao"])) {
    $_SESSION["csrf_redefinicao"] = bin2hex(random_bytes(32));
}

$token = isset($_POST["token"]) && is_string($_POST["token"])
    ? $_POST["token"]
    : (isset($_GET["token"]) && is_string($_GET["token"]) ? $_GET["token"] : "");
$mensagem = "";
$sucesso = false;
$tokenValido = preg_match('/\A[a-f0-9]{64}\z/i', $token) === 1;

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    $csrf = isset($_POST["csrf_token"]) && is_string($_POST["csrf_token"])
        ? $_POST["csrf_token"]
        : "";
    $senha = isset($_POST["senha"]) && is_string($_POST["senha"])
        ? trim($_POST["senha"])
        : "";
    $confirmacao = isset($_POST["confirmacao"]) && is_string($_POST["confirmacao"])
        ? trim($_POST["confirmacao"])
        : "";

    if (!hash_equals($_SESSION["csrf_redefinicao"], $csrf)) {
        $mensagem = "Sua sessão expirou. Abra novamente o link enviado por e-mail.";
    } elseif (!$tokenValido) {
        $mensagem = "Este link de redefinição é inválido ou expirou.";
    } elseif ($senha !== $confirmacao) {
        $mensagem = "As senhas não coincidem.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $senha)) {
        $mensagem = "A senha deve ter ao menos 8 caracteres, com maiúscula, minúscula, número e símbolo.";
    } elseif (!($conexao instanceof mysqli) || $conexao->connect_errno) {
        $mensagem = "Não foi possível redefinir a senha agora. Tente novamente mais tarde.";
    } else {
        try {
            $conexao->begin_transaction();
            $hashToken = hash("sha256", $token);
            $stmt = $conexao->prepare(
                "SELECT leitor_id FROM redefinicao_senha
                 WHERE token_hash = ? AND expira_em > UTC_TIMESTAMP() FOR UPDATE"
            );
            $stmt->bind_param("s", $hashToken);
            $stmt->execute();
            $registro = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$registro) {
                $conexao->rollback();
                $mensagem = "Este link de redefinição é inválido ou expirou.";
            } else {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $conexao->prepare("UPDATE leitores SET senha = ? WHERE id = ?");
                $stmt->bind_param("si", $senhaHash, $registro["leitor_id"]);
                $stmt->execute();
                $stmt->close();

                $stmt = $conexao->prepare("DELETE FROM redefinicao_senha WHERE leitor_id = ?");
                $stmt->bind_param("i", $registro["leitor_id"]);
                $stmt->execute();
                $stmt->close();
                $conexao->commit();

                $sucesso = true;
                $mensagem = "Senha redefinida. Agora você já pode entrar com a nova senha.";
            }
        } catch (Throwable $erro) {
            try {
                $conexao->rollback();
            } catch (Throwable $erroRollback) {
                error_log("Falha ao reverter redefinição de senha: " . $erroRollback->getMessage());
            }
            error_log("Erro ao redefinir senha: " . $erro->getMessage());
            $mensagem = "Não foi possível redefinir a senha agora. Tente novamente mais tarde.";
        }
    }
} elseif (!$tokenValido) {
    $mensagem = "Este link de redefinição é inválido ou expirou.";
} elseif (!($conexao instanceof mysqli) || $conexao->connect_errno) {
    $mensagem = "Não foi possível validar o link agora. Tente novamente mais tarde.";
} else {
    try {
        $hashToken = hash("sha256", $token);
        $stmt = $conexao->prepare(
            "SELECT id FROM redefinicao_senha
             WHERE token_hash = ? AND expira_em > UTC_TIMESTAMP() LIMIT 1"
        );
        $stmt->bind_param("s", $hashToken);
        $stmt->execute();
        $tokenValido = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$tokenValido) {
            $mensagem = "Este link de redefinição é inválido ou expirou.";
        }
    } catch (Throwable $erro) {
        error_log("Erro ao validar link de redefinição: " . $erro->getMessage());
        $tokenValido = false;
        $mensagem = "Não foi possível validar o link agora. Tente novamente mais tarde.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha</title>
    <style>
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #0f172a, #1e293b, #0f172a);
            color: #fff;
            font-family: "Segoe UI", Tahoma, sans-serif;
        }
        main {
            width: min(100%, 450px);
            padding: 40px;
            border: 1px solid rgba(99, 102, 241, .25);
            border-radius: 16px;
            background: rgba(30, 41, 59, .92);
            box-shadow: 0 25px 50px rgba(0, 0, 0, .4);
        }
        h1 { margin: 0 0 24px; text-align: center; }
        label { display: block; margin: 18px 0 8px; color: #cbd5e1; }
        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #475569;
            border-radius: 8px;
            background: #1e293b;
            color: #fff;
            font-size: 1rem;
        }
        input:focus { outline: 2px solid #818cf8; outline-offset: 2px; }
        button {
            width: 100%;
            margin-top: 24px;
            padding: 14px;
            border: 0;
            border-radius: 8px;
            background: #4f46e5;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .mensagem { margin: 0 0 20px; line-height: 1.5; }
        a { display: block; margin-top: 20px; color: #a5b4fc; text-align: center; }
        @media (max-width: 480px) { main { padding: 28px 22px; } }
    </style>
</head>
<body>
    <main>
        <h1>Redefinir senha</h1>
        <?php if ($mensagem !== ""): ?>
            <p class="mensagem" role="alert"><?= htmlspecialchars($mensagem, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") ?></p>
        <?php endif; ?>

        <?php if ($tokenValido && !$sucesso): ?>
            <form method="post" action="redefinir_senha.php" autocomplete="on">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, "UTF-8") ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_redefinicao"], ENT_QUOTES, "UTF-8") ?>">
                <label for="senha">Nova senha</label>
                <input type="password" id="senha" name="senha" autocomplete="new-password" required>
                <label for="confirmacao">Confirme a nova senha</label>
                <input type="password" id="confirmacao" name="confirmacao" autocomplete="new-password" required>
                <button type="submit">Salvar nova senha</button>
            </form>
        <?php endif; ?>

        <a href="login_leitor.php">Voltar ao login</a>
    </main>
</body>
</html>