<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Location: /UniHub/pages/auth/cadastro.php');
        exit;
    }

    require_once __DIR__ . '/../../config/database.php';

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    $_SESSION['dados_cadastro'] = [
        'nome' => $nome,
        'email' => $email,
        'telefone' => $telefone
    ];

    if($nome === '' || $email === '' || $telefone === '' || $senha === '' ||  $confirmarSenha === ''){
        $_SESSION['erro'] = 'Preencha todos os campos.';

        header('Location: /UniHub/pages/auth/cadastro.php');
        exit;
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $_SESSION['erro'] = 'Informe um endereço de e-mail válido.';

        header('Location: /UniHub/pages/auth/cadastro.php');
        exit;
    }

    if($senha !== $confirmarSenha){
        $_SESSION['erro'] = 'As senhas informadas não coincidem.';

        header('Location: /UniHub/pages/auth/cadastro.php');
        exit;
    }

    $sql = "
        SELECT id_usuario
        FROM usuario
        WHERE email = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $email
    ]);

    if($stmt->fetch()){
        $_SESSION['erro'] =
            'Já existe uma conta cadastrada com este e-mail.';

        header('Location: /UniHub/pages/auth/cadastro.php');
        exit;
    }

    $sql = "
        SELECT id_perfil
        FROM perfil
        WHERE nome = 'ANUNCIANTE'
        AND ativo = TRUE
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $perfil = $stmt->fetch();


    if(!$perfil){
        $_SESSION['erro'] =
            'Não foi possível localizar o perfil de anunciante.';

        header('Location: /UniHub/pages/auth/cadastro.php');
        exit;
    }

    $senhaHash = password_hash(
        $senha,
        PASSWORD_DEFAULT
    );

    $sql = "
        INSERT INTO usuario (
            id_perfil,
            nome,
            email,
            senha,
            telefone,
            ativo
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            TRUE
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $perfil['id_perfil'],
        $nome,
        $email,
        $senhaHash,
        $telefone
    ]);

    unset($_SESSION['dados_cadastro']);

    $_SESSION['sucesso'] =
        'Cadastro realizado com sucesso. Agora você pode entrar no UniHub.';

    header('Location: /UniHub/pages/auth/login.php');
    exit;
?>