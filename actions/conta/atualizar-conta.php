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

    $idUsuario = $_SESSION['id_usuario'];
    $nome = trim($_POST['nome'] ?? '');
    $cpfInformado = trim($_POST['cpf'] ?? '');
    $cpf = preg_replace('/\D/', '', $cpfInformado);
    $rg = trim($_POST['rg'] ?? '');
    $nacionalidade = trim($_POST['nacionalidade'] ?? '');
    $estadoCivil = trim($_POST['estado_civil'] ?? '');
    $profissao = trim($_POST['profissao'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarNovaSenha = $_POST['confirmar_nova_senha'] ?? '';

    $_SESSION['dados_edicao'] = [
        'nome' => $nome,
        'cpf' => $cpfInformado,
        'rg' => $rg,
        'nacionalidade' => $nacionalidade,
        'estado_civil' => $estadoCivil,
        'profissao' => $profissao,
        'email' => $email,
        'telefone' => $telefone
    ];

    if($nome === '' || $email === '' || $telefone === ''){
        $_SESSION['erro'] = 'Preencha todos os dados obrigatórios da conta.';

        header('Location: /UniHub/pages/conta/editar-conta.php');
        exit;
    }

    if($cpf !== '' && strlen($cpf) !== 11){
        $_SESSION['erro'] = 'Informe um CPF válido com 11 números.';

        header('Location: /UniHub/pages/conta/editar-conta.php');
        exit;
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $_SESSION['erro'] = 'Informe um endereço de e-mail válido.';

        header('Location: /UniHub/pages/conta/editar-conta.php');
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
        $_SESSION['erro'] = 'Este e-mail já está sendo utilizado por outra conta.';

        header('Location: /UniHub/pages/conta/editar-conta.php');
        exit;
    }

    if($cpf !== ''){

        $sql = "
            SELECT id_usuario
            FROM usuario
            WHERE cpf = ?
            AND id_usuario <> ?
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $cpf,
            $idUsuario
        ]);

        if($stmt->fetch()){
            $_SESSION['erro'] = 'Este CPF já está sendo utilizado por outra conta.';

            header('Location: /UniHub/pages/conta/editar-conta.php');
            exit;
        }
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

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$usuario){
        session_destroy();

        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    $alterarSenha = $senhaAtual !== '' || $novaSenha !== '' || $confirmarNovaSenha !== '';

    if($alterarSenha){
        if($senhaAtual === '' || $novaSenha === '' || $confirmarNovaSenha === ''){
            $_SESSION['erro'] = 'Para alterar a senha, preencha todos os campos de senha.';

            header('Location: /UniHub/pages/conta/editar-conta.php');
            exit;
        }

        if(!password_verify($senhaAtual, $usuario['senha'])){
            $_SESSION['erro'] = 'A senha atual está incorreta.';

            header('Location: /UniHub/pages/conta/editar-conta.php');
            exit;
        }

        if(strlen($novaSenha) < 8){
            $_SESSION['erro'] = 'A nova senha deve possuir no mínimo 8 caracteres.';

            header('Location: /UniHub/pages/conta/editar-conta.php');
            exit;
        }

        if(!preg_match('/[A-Z]/', $novaSenha)){
            $_SESSION['erro'] = 'A nova senha deve possuir pelo menos uma letra maiúscula.';

            header('Location: /UniHub/pages/conta/editar-conta.php');
            exit;
        }

        if(!preg_match('/[0-9]/', $novaSenha)){
            $_SESSION['erro'] = 'A nova senha deve possuir pelo menos um número.';

            header('Location: /UniHub/pages/conta/editar-conta.php');
            exit;
        }

        if(!preg_match('/[^A-Za-z0-9]/', $novaSenha)){
            $_SESSION['erro'] = 'A nova senha deve possuir pelo menos um caractere especial.';

            header('Location: /UniHub/pages/conta/editar-conta.php');
            exit;
        }

        if($novaSenha !== $confirmarNovaSenha){
            $_SESSION['erro'] = 'A nova senha e a confirmação não coincidem.';

            header('Location: /UniHub/pages/conta/editar-conta.php');
            exit;
        }
    }

    try{
        if($alterarSenha){
            $novaSenhaHash = password_hash(
                $novaSenha,
                PASSWORD_DEFAULT
            );

            $sql = "
                UPDATE usuario
                SET
                    nome = ?,
                    cpf = ?,
                    rg = ?,
                    nacionalidade = ?,
                    estado_civil = ?,
                    profissao = ?,
                    email = ?,
                    telefone = ?,
                    senha = ?
                WHERE id_usuario = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nome,
                $cpf !== '' ? $cpf : null,
                $rg !== '' ? $rg : null,
                $nacionalidade !== '' ? $nacionalidade : null,
                $estadoCivil !== '' ? $estadoCivil : null,
                $profissao !== '' ? $profissao : null,
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
                    cpf = ?,
                    rg = ?,
                    nacionalidade = ?,
                    estado_civil = ?,
                    profissao = ?,
                    email = ?,
                    telefone = ?
                WHERE id_usuario = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nome,
                $cpf !== '' ? $cpf : null,
                $rg !== '' ? $rg : null,
                $nacionalidade !== '' ? $nacionalidade : null,
                $estadoCivil !== '' ? $estadoCivil : null,
                $profissao !== '' ? $profissao : null,
                $email,
                $telefone,
                $idUsuario
            ]);
        }

        $_SESSION['nome'] = $nome;
        $_SESSION['email'] = $email;

        unset($_SESSION['dados_edicao']);

        if($alterarSenha){
            $_SESSION['sucesso'] = 'Dados e senha atualizados com sucesso.';
        }
        
        else{
            $_SESSION['sucesso'] = 'Dados da conta atualizados com sucesso.';
        }

        header('Location: /UniHub/pages/conta/minha-conta.php');
        exit;
    }
    
    catch(Throwable $erro){
        error_log('Erro ao atualizar conta: ' . $erro->getMessage());

        $_SESSION['erro'] = 'Não foi possível atualizar os dados da conta. Tente novamente.';

        header('Location: /UniHub/pages/conta/editar-conta.php');
        exit;
    }
?>