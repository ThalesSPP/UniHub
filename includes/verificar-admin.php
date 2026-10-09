<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if(!isset($_SESSION['id_usuario'])){
        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    if(($_SESSION['perfil'] ?? '') !== 'ADMINISTRADOR'){
        $_SESSION['erro'] = 'Você não possui permissão para acessar esta página.';

        header('Location: /UniHub/index.php');
        exit;
    }
?>