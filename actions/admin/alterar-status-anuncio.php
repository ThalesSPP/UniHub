<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    require_once __DIR__ . '/../../includes/verificar-admin.php';
    require_once __DIR__ . '/../../config/database.php';

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Location: /UniHub/pages/admin/anuncios.php');
        exit;
    }

    $idAnuncio = $_POST['id_anuncio'] ?? '';
    $acao = strtolower(trim($_POST['acao'] ?? ''));

    if($idAnuncio === '' || !ctype_digit($idAnuncio)){
        $_SESSION['erro'] = 'Anúncio inválido.';

        header('Location: /UniHub/pages/admin/anuncios.php');
        exit;
    }

    if(!in_array($acao, ['ativar', 'desativar'], true)){
        $_SESSION['erro'] = 'Ação inválida.';

        header('Location: /UniHub/pages/admin/anuncios.php');
        exit;
    }

    $sql = "
        SELECT
            a.id_anuncio,
            a.titulo,
            a.status,
            u.id_usuario,
            u.ativo AS usuario_ativo
        FROM anuncio a
        INNER JOIN usuario u
            ON u.id_usuario = a.id_usuario
        WHERE a.id_anuncio = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idAnuncio]);

    $anuncio = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$anuncio){
        $_SESSION['erro'] = 'O anúncio informado não foi encontrado.';

        header('Location: /UniHub/pages/admin/anuncios.php');
        exit;
    }

    if($anuncio['status'] === 'REMOVIDO'){
        $_SESSION['erro'] = 'Não é possível alterar o status de um anúncio removido.';

        header('Location: /UniHub/pages/admin/anuncios.php');
        exit;
    }

    if($acao === 'ativar' && !$anuncio['usuario_ativo']){
        $_SESSION['erro'] = 'Não é possível ativar o anúncio enquanto o anunciante estiver inativo.';

        header('Location: /UniHub/pages/admin/anuncios.php');
        exit;
    }

    try{
        if($acao === 'ativar'){

            $sql = "
                UPDATE anuncio
                SET status = 'ATIVO'
                WHERE id_anuncio = ?
                AND status = 'INATIVO'
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idAnuncio]);

            $_SESSION['sucesso'] = 'O anúncio foi ativado com sucesso.';
        }

        else{
            $sql = "
                UPDATE anuncio
                SET status = 'INATIVO'
                WHERE id_anuncio = ?
                AND status = 'ATIVO'
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idAnuncio]);

            $_SESSION['sucesso'] = 'O anúncio foi desativado com sucesso.';
        }
    }

    catch(Exception $e){
        $_SESSION['erro'] = 'Não foi possível alterar o status do anúncio.';
    }

    header('Location: /UniHub/pages/admin/anuncios.php');
    exit;
?>