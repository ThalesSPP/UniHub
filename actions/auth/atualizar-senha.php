<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Location: /UniHub/pages/auth/esqueci-senha.php');
        exit;
    }

    require_once __DIR__ . '/../../config/database.php';

    $token = trim($_POST['token'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if($token === ''){
        $_SESSION['erro'] = 'Link de recuperação inválido.';

        header('Location: /UniHub/pages/auth/esqueci-senha.php');
        exit;
    }

    $urlRetorno = '/UniHub/pages/auth/redefinir-senha.php?token=' . urlencode($token);

    if(strlen($senha) < 8){
        $_SESSION['erro'] = 'A senha deve possuir no mínimo 8 caracteres.';

        header('Location: ' . $urlRetorno);
        exit;
    }

    if(!preg_match('/[A-Z]/', $senha)){
        $_SESSION['erro'] = 'A senha deve possuir pelo menos uma letra maiúscula.';

        header('Location: ' . $urlRetorno);
        exit;
    }

    if(!preg_match('/[0-9]/', $senha)){
        $_SESSION['erro'] = 'A senha deve possuir pelo menos um número.';

        header('Location: ' . $urlRetorno);
        exit;
    }

    if(!preg_match('/[^A-Za-z0-9]/', $senha)){
        $_SESSION['erro'] = 'A senha deve possuir pelo menos um caractere especial.';

        header('Location: ' . $urlRetorno);
        exit;
    }

    if($senha !== $confirmarSenha){
        $_SESSION['erro'] = 'As senhas informadas não coincidem.';

        header('Location: ' . $urlRetorno);
        exit;
    }

    try{
        $tokenHash = hash('sha256', $token);
        $pdo->beginTransaction();

        $sql = "
            SELECT
                rs.id_recuperacao,
                rs.id_usuario
            FROM recuperacao_senha rs
            INNER JOIN usuario u
                ON u.id_usuario = rs.id_usuario
            WHERE rs.token_hash = ?
            AND rs.utilizado = FALSE
            AND rs.data_expiracao >= NOW()
            AND u.ativo = TRUE
            LIMIT 1
            FOR UPDATE
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $tokenHash
        ]);

        $recuperacao = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$recuperacao){

            $pdo->rollBack();

            $_SESSION['erro'] =
                'Este link de recuperação é inválido, expirou ou já foi utilizado.';

            header('Location: /UniHub/pages/auth/esqueci-senha.php');
            exit;
        }

        $senhaHash = password_hash(
            $senha,
            PASSWORD_DEFAULT
        );

        $sql = "
            UPDATE usuario
            SET senha = ?
            WHERE id_usuario = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $senhaHash,
            $recuperacao['id_usuario']
        ]);

        $sql = "
            UPDATE recuperacao_senha
            SET utilizado = TRUE
            WHERE id_usuario = ?
            AND utilizado = FALSE
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $recuperacao['id_usuario']
        ]);

        $pdo->commit();

        $_SESSION['sucesso'] =
            'Senha redefinida com sucesso. Você já pode entrar com sua nova senha.';

        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }
    
    catch(Throwable $erro){
        if($pdo->inTransaction()){
            $pdo->rollBack();
        }

        error_log(
            'Erro ao atualizar senha: ' . $erro->getMessage()
        );

        $_SESSION['erro'] =
            'Não foi possível redefinir sua senha. Tente novamente.';

        header('Location: ' . $urlRetorno);
        exit;
    }
?>