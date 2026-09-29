<?php
    use PHPMailer\PHPMailer\PHPMailer;

    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Location: /UniHub/pages/auth/esqueci-senha.php');
        exit;
    }

    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../vendor/autoload.php';

    $configEmail = require __DIR__ . '/../../config/email.php';
    $configApp = require __DIR__ . '/../../config/app.php';

    $email = trim($_POST['email'] ?? '');

    $_SESSION['email_recuperacao'] = $email;

    if($email === ''){
        $_SESSION['erro'] = 'Informe seu endereço de e-mail.';

        header('Location: /UniHub/pages/auth/esqueci-senha.php');
        exit;
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $_SESSION['erro'] = 'Informe um endereço de e-mail válido.';

        header('Location: /UniHub/pages/auth/esqueci-senha.php');
        exit;
    }

    try{
        $sql = "
            SELECT
                id_usuario,
                nome,
                email
            FROM usuario
            WHERE email = ?
            AND ativo = TRUE
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $email
        ]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if($usuario){
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            $sql = "
                UPDATE recuperacao_senha
                SET utilizado = TRUE
                WHERE id_usuario = ?
                AND utilizado = FALSE
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $usuario['id_usuario']
            ]);

            $sql = "
                INSERT INTO recuperacao_senha (
                    id_usuario,
                    token_hash,
                    data_expiracao,
                    utilizado
                )
                VALUES (
                    ?,
                    ?,
                    DATE_ADD(NOW(), INTERVAL 30 MINUTE),
                    FALSE
                )
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $usuario['id_usuario'],
                $tokenHash
            ]);

            $idRecuperacao = $pdo->lastInsertId();

            $linkRecuperacao =
                $configApp['url_base'] .
                '/pages/auth/redefinir-senha.php?token=' .
                urlencode($token);

            try{
                $mail = new PHPMailer(true);

                $mail->isSMTP();

                $mail->Host = $configEmail['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $configEmail['usuario'];
                $mail->Password = $configEmail['senha'];

                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $configEmail['porta'];

                $mail->CharSet = 'UTF-8';

                $mail->setFrom(
                    $configEmail['remetente_email'],
                    $configEmail['remetente_nome']
                );

                $mail->addAddress(
                    $usuario['email'],
                    $usuario['nome']
                );

                $mail->isHTML(true);

                $nomeUsuario = htmlspecialchars(
                    $usuario['nome'],
                    ENT_QUOTES,
                    'UTF-8'
                );

                $linkSeguro = htmlspecialchars(
                    $linkRecuperacao,
                    ENT_QUOTES,
                    'UTF-8'
                );

                $mail->Subject = 'Recuperação de senha - UniHub';

                $mail->Body = "
                    <h2>Recuperação de senha</h2>
                    <p>
                        Olá, {$nomeUsuario}.
                    </p>

                    <p>
                        Recebemos uma solicitação para redefinir a senha da sua conta no UniHub.
                    </p>

                    <p>
                        Clique no botão abaixo para criar uma nova senha:
                    </p>

                    <p>
                        <a href=\"{$linkSeguro}\" style=\" display: inline-block; padding: 12px 20px; background-color: #0d6efd; color: #ffffff; text-decoration: none; border-radius: 6px; \">
                            Redefinir senha
                        </a>
                    </p>

                    <p>
                        Este link é válido por 30 minutos.
                    </p>

                    <p>
                        Caso você não tenha solicitado a recuperação da senha, ignore este e-mail.
                    </p>

                    <hr>

                    <p>
                        UniHub
                    </p>
                ";

                $mail->AltBody =
                    "Olá, {$usuario['nome']}.\n\n" .
                    "Recebemos uma solicitação para redefinir a senha da sua conta no UniHub.\n\n" .
                    "Acesse o link abaixo:\n" .
                    $linkRecuperacao . "\n\n" .
                    "Este link é válido por 30 minutos.\n\n" .
                    "Caso você não tenha solicitado a recuperação da senha, ignore este e-mail.";

                $mail->send();

            }
            
            catch(Throwable $erroEmail){
                $sql = "
                    UPDATE recuperacao_senha
                    SET utilizado = TRUE
                    WHERE id_recuperacao = ?
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $idRecuperacao
                ]);

                throw $erroEmail;
            }
        }

        unset($_SESSION['email_recuperacao']);

        $_SESSION['sucesso'] = 'Se o e-mail estiver cadastrado, você receberá as instruções para redefinir sua senha.';

        header('Location: /UniHub/pages/auth/esqueci-senha.php');
        exit;

    }
    
    catch(Throwable $erro){
        $_SESSION['erro'] =
            'Erro: ' . $erro->getMessage();

        header('Location: /UniHub/pages/auth/esqueci-senha.php');
        exit;
    }
?>