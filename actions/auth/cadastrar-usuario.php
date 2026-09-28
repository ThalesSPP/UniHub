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
    $cpfInformado = trim($_POST['cpf'] ?? '');
    $cpf = preg_replace('/\D/', '', $cpfInformado);
    $rg = trim($_POST['rg'] ?? '');
    $nacionalidade = trim($_POST['nacionalidade'] ?? '');
    $estadoCivil = trim($_POST['estado_civil'] ?? '');
    $profissao = trim($_POST['profissao'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    $_SESSION['dados_cadastro'] = [
        'nome' => $nome,
        'cpf' => $cpfInformado,
        'rg' => $rg,
        'nacionalidade' => $nacionalidade,
        'estado_civil' => $estadoCivil,
        'profissao' => $profissao,
        'email' => $email,
        'telefone' => $telefone
    ];

    if( $nome === '' || $cpf === '' || $rg === '' || $nacionalidade === '' || $estadoCivil === '' || $profissao === '' || $email === '' || $telefone === '' || $senha === '' || $confirmarSenha === ''){
        $_SESSION['erro'] = 'Preencha todos os campos.';

        header('Location: /UniHub/pages/auth/cadastro.php');
        exit;
    }

    if(strlen($cpf) !== 11){
        $_SESSION['erro'] = 'Informe um CPF válido com 11 números.';

        header('Location: /UniHub/pages/auth/cadastro.php');
        exit;
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $_SESSION['erro'] = 'Informe um endereço de e-mail válido.';

        header('Location: /UniHub/pages/auth/cadastro.php');
        exit;
    }

    if(strlen($senha) < 8){
        $_SESSION['erro'] = 'A senha deve possuir no mínimo 8 caracteres.';

        header('Location: /UniHub/pages/auth/cadastro.php');
        exit;
    }

    if(!preg_match('/[A-Z]/', $senha)){
        $_SESSION['erro'] = 'A senha deve possuir pelo menos uma letra maiúscula.';

        header('Location: /UniHub/pages/auth/cadastro.php');
        exit;
    }

    if(!preg_match('/[0-9]/', $senha)){
        $_SESSION['erro'] = 'A senha deve possuir pelo menos um número.';

        header('Location: /UniHub/pages/auth/cadastro.php');
        exit;
    }

    if(!preg_match('/[^A-Za-z0-9]/', $senha)){
        $_SESSION['erro'] = 'A senha deve possuir pelo menos um caractere especial.';

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
        SELECT id_usuario
        FROM usuario
        WHERE cpf = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $cpf
    ]);

    if($stmt->fetch()){
        $_SESSION['erro'] =
            'Já existe uma conta cadastrada com este CPF.';

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
            cpf,
            rg,
            nacionalidade,
            estado_civil,
            profissao,
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
        $cpf,
        $rg,
        $nacionalidade,
        $estadoCivil,
        $profissao,
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