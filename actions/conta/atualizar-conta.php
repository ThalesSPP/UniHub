<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if(!isset($_SESSION['id_usuario'])){
        header('Location: /UniHub/login.php');
        exit;
    }

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Location: /UniHub/pages/conta/minha-conta.php');
        exit;
    }

    require_once __DIR__ . '/../../config/database.php';

    $idUsuario = $_SESSION['id_usuario'];
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarNovaSenha = $_POST['confirmar_nova_senha'] ?? '';

    $_SESSION['dados_edicao'] = [
        'nome' => $nome,
        'email' => $email,
        'telefone' => $telefone
    ];

    if($nome === '' || $email === '' || $telefone === ''){
        $_SESSION['erro'] =
            'Preencha todos os dados da conta.';

        header('Location: /UniHub/editar-conta.php');
        exit;
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $_SESSION['erro'] =
            'Informe um endereço de e-mail válido.';

        header('Location: /UniHub/editar-conta.php');
        exit;
    }

    $sql = "
        SELECT id_usuario
        FROM usuario
        WHERE email = ?
        AND id_usuario <> ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $email,
        $idUsuario
    ]);

    if($stmt->fetch()){
        $_SESSION['erro'] =
            'Este e-mail já está sendo utilizado por outra conta.';

        header('Location: /UniHub/editar-conta.php');
        exit;
    }

    $sql = "
        SELECT senha
        FROM usuario
        WHERE id_usuario = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $idUsuario
    ]);

    $usuario = $stmt->fetch();
    if(!$usuario){
        session_destroy();

        header('Location: /UniHub/login.php');
        exit;
    }

    $alterarSenha =
        $senhaAtual !== '' ||
        $novaSenha !== '' ||
        $confirmarNovaSenha !== '';

    if ($alterarSenha) {

        if ($senhaAtual === '' || $novaSenha === '' || $confirmarNovaSenha === ''){
            $_SESSION['erro'] =
                'Para alterar a senha, preencha todos os campos de senha.';

            header('Location: /UniHub/editar-conta.php');
            exit;
        }

        if (!password_verify($senhaAtual, $usuario['senha'])){
            $_SESSION['erro'] =
                'A senha atual está incorreta.';

            header('Location: /UniHub/editar-conta.php');
            exit;
        }

        if($novaSenha !== $confirmarNovaSenha){
            $_SESSION['erro'] =
                'A nova senha e a confirmação não coincidem.';

            header('Location: /UniHub/editar-conta.php');
            exit;
        }
    }

    if($alterarSenha){
        $novaSenhaHash = password_hash(
            $novaSenha,
            PASSWORD_DEFAULT
        );

        $sql = "
            UPDATE usuario
            SET
                nome = ?,
                email = ?,
                telefone = ?,
                senha = ?
            WHERE id_usuario = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nome,
            $email,
            $telefone,
            $novaSenhaHash,
            $idUsuario
        ]);
    } 
    
    else{
        $sql = "
            UPDATE usuario
            SET
                nome = ?,
                email = ?,
                telefone = ?
            WHERE id_usuario = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nome,
            $email,
            $telefone,
            $idUsuario
        ]);
    }

    $_SESSION['nome'] = $nome;
    $_SESSION['email'] = $email;
    unset($_SESSION['dados_edicao']);

    if($alterarSenha){
        $_SESSION['sucesso'] =
            'Dados e senha atualizados com sucesso.';
    } 
    
    else {
        $_SESSION['sucesso'] =
            'Dados da conta atualizados com sucesso.';
    }

    header('Location: /UniHub/pages/conta/minha-conta.php');
    exit;
?>