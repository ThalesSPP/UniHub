<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    require_once __DIR__ . '/../../includes/verificar-admin.php';
    require_once __DIR__ . '/../../config/database.php';

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Location: /UniHub/pages/admin/usuarios.php');
        exit;
    }

    $idUsuario = $_POST['id_usuario'] ?? '';
    $acao = strtolower(trim($_POST['acao'] ?? ''));

    if($idUsuario === '' || !ctype_digit($idUsuario)){
        $_SESSION['erro'] = 'Usuário inválido.';

        header('Location: /UniHub/pages/admin/usuarios.php');
        exit;
    }

    if(!in_array($acao, ['ativar', 'desativar'], true)){
        $_SESSION['erro'] = 'Ação inválida.';

        header('Location: /UniHub/pages/admin/usuarios.php');
        exit;
    }

    $sql = "
        SELECT
            u.id_usuario,
            u.nome,
            u.ativo
        FROM usuario u
        INNER JOIN perfil p
            ON p.id_perfil = u.id_perfil
        WHERE u.id_usuario = ?
        AND p.nome = 'ANUNCIANTE'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idUsuario]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$usuario){
        $_SESSION['erro'] = 'O anunciante informado não foi encontrado.';

        header('Location: /UniHub/pages/admin/usuarios.php');
        exit;
    }

    try{
        $pdo->beginTransaction();

        if($acao === 'desativar'){

            $sql = "
                UPDATE usuario
                SET ativo = FALSE
                WHERE id_usuario = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idUsuario]);

            $sql = "
                UPDATE anuncio
                SET status = 'INATIVO'
                WHERE id_usuario = ?
                AND status = 'ATIVO'
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idUsuario]);

            $_SESSION['sucesso'] = 'O usuário foi desativado e seus anúncios ativos foram desativados.';
        }

        else{
            $sql = "
                UPDATE usuario
                SET ativo = TRUE
                WHERE id_usuario = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idUsuario]);

            $_SESSION['sucesso'] = 'O usuário foi ativado com sucesso.';
        }

        $pdo->commit();
    }

    catch(Exception $e){
        if($pdo->inTransaction()){
            $pdo->rollBack();
        }

        $_SESSION['erro'] = 'Não foi possível alterar o status do usuário.';
    }

    header('Location: /UniHub/pages/admin/usuarios.php');
    exit;
?>