<?php 
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }
 
    require_once __DIR__ . '/../../config/database.php';
    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $_SESSION['email_login'] = $email;

    if($email === '' || $senha === '') {
        $_SESSION['erro'] = 'Informe o e-mail e a senha.';

        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $_SESSION['erro'] = 'Informe um endereço de e-mail válido.';

        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    $sql = "
        SELECT
            u.id_usuario,
            u.id_perfil,
            u.nome,
            u.email,
            u.senha,
            u.telefone,
            u.ativo,
            p.nome AS perfil,
            p.ativo AS perfil_ativo

        FROM usuario u

        INNER JOIN perfil p
            ON p.id_perfil = u.id_perfil

        WHERE u.email = ?

        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $email
    ]);

    $usuario = $stmt->fetch();

    if(!$usuario){
        $_SESSION['erro'] = 'E-mail ou senha inválidos.';

        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    if(!$usuario['ativo']){
        $_SESSION['erro'] = 'Esta conta está desativada.';

        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    if(!$usuario['perfil_ativo']){
        $_SESSION['erro'] = 'O perfil desta conta está desativado.';

        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    if(!password_verify($senha, $usuario['senha'])){
        $_SESSION['erro'] = 'E-mail ou senha inválidos.';

        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['id_perfil'] = $usuario['id_perfil'];
    $_SESSION['perfil'] = $usuario['perfil'];
    $_SESSION['nome'] = $usuario['nome'];
    $_SESSION['email'] = $usuario['email'];

    unset($_SESSION['email_login']);

    header('Location: /UniHub/index.php');
    exit;
?>