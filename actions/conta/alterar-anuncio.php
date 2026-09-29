<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if(!isset($_SESSION['id_usuario'])){
        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Location: /UniHub/pages/conta/minha-conta.php');
        exit;
    }

    require_once __DIR__ . '/../../config/database.php';

    $idAnuncio = filter_input(INPUT_POST, 'id_anuncio', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';

    if(!$idAnuncio){
        $_SESSION['erro'] = 'Anúncio inválido.';

        header('Location: /UniHub/pages/conta/minha-conta.php');
        exit;
    }

    if(!in_array($status, ['ATIVO', 'INATIVO'], true)){
        $_SESSION['erro'] = 'Status do anúncio inválido.';

        header('Location: /UniHub/pages/conta/minha-conta.php');
        exit;
    }

    $sql = "
        SELECT
            id_anuncio
        FROM anuncio
        WHERE id_anuncio = ?
        AND id_usuario = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $idAnuncio,
        $_SESSION['id_usuario']
    ]);

    $anuncio = $stmt->fetch();

    if(!$anuncio){
        $_SESSION['erro'] = 'Você não possui permissão para alterar este anúncio.';

        header('Location: /UniHub/pages/conta/minha-conta.php');
        exit;
    }

    $sql = "
        UPDATE anuncio
        SET status = ?
        WHERE id_anuncio = ?
        AND id_usuario = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $status,
        $idAnuncio,
        $_SESSION['id_usuario']
    ]);

    if($status === 'ATIVO'){
        $_SESSION['sucesso'] = 'Anúncio ativado com sucesso.';
    }
    
    else{
        $_SESSION['sucesso'] = 'Anúncio desativado com sucesso.';
    }

    header('Location: /UniHub/pages/conta/minha-conta.php');
    exit;
?>