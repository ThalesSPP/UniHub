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
    $senha = $_POST['senha'] ?? '';

    if($senha === ''){
        $_SESSION['erro'] = 'Informe sua senha para excluir a conta.';

        header('Location: /UniHub/pages/conta/minha-conta.php');
        exit;
    }

    $sql = "
        SELECT
            id_usuario,
            senha
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

    if(!password_verify($senha, $usuario['senha'])){
        $_SESSION['erro'] = 'Senha incorreta.';

        header('Location: /UniHub/pages/conta/minha-conta.php');
        exit;
    }

    $sql = "
        SELECT
            id_anuncio,
            id_endereco
        FROM anuncio
        WHERE id_usuario = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $idUsuario
    ]);

    $anuncios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $imagens = [];

    foreach($anuncios as $anuncio){
        $sql = "
            SELECT
                caminho_arquivo
            FROM imagem_anuncio
            WHERE id_anuncio = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $anuncio['id_anuncio']
        ]);

        $imagensAnuncio = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach($imagensAnuncio as $imagem){
            $imagens[] = $imagem['caminho_arquivo'];
        }
    }

    try{
        $pdo->beginTransaction();

        $sql = "
            DELETE cg
            FROM contrato_gerado cg
            INNER JOIN configuracao_contrato cc
                ON cc.id_configuracao = cg.id_configuracao
            INNER JOIN anuncio a
                ON a.id_anuncio = cc.id_anuncio
            WHERE a.id_usuario = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idUsuario
        ]);

        $sql = "
            DELETE ce
            FROM clausula_extra ce
            INNER JOIN configuracao_contrato cc
                ON cc.id_configuracao = ce.id_configuracao
            INNER JOIN anuncio a
                ON a.id_anuncio = cc.id_anuncio
            WHERE a.id_usuario = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idUsuario
        ]);

        $sql = "
            DELETE cc
            FROM configuracao_contrato cc
            INNER JOIN anuncio a
                ON a.id_anuncio = cc.id_anuncio
            WHERE a.id_usuario = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idUsuario
        ]);

        $sql = "
            DELETE ia
            FROM imagem_anuncio ia
            INNER JOIN anuncio a
                ON a.id_anuncio = ia.id_anuncio
            WHERE a.id_usuario = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idUsuario
        ]);

        $sql = "
            DELETE FROM anuncio
            WHERE id_usuario = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idUsuario
        ]);

        foreach($anuncios as $anuncio){
            $sql = "
                SELECT COUNT(*) AS total
                FROM anuncio
                WHERE id_endereco = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $anuncio['id_endereco']
            ]);

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if($resultado['total'] == 0){

                $sql = "
                    DELETE FROM endereco
                    WHERE id_endereco = ?
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $anuncio['id_endereco']
                ]);
            }
        }

        $sql = "
            DELETE FROM usuario
            WHERE id_usuario = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idUsuario
        ]);

        $pdo->commit();

    }
    
    catch(Throwable $erro){
        if($pdo->inTransaction()){
            $pdo->rollBack();
        }

        $_SESSION['erro'] = 'Não foi possível excluir sua conta. Tente novamente.';

        header('Location: /UniHub/pages/conta/minha-conta.php');
        exit;
    }

    foreach($imagens as $imagem){
        $caminho = __DIR__ . '/../../' . $imagem;

        if(is_file($caminho)){
            unlink($caminho);
        }
    }

    foreach($anuncios as $anuncio){
        $diretorio = __DIR__ . '/../../uploads/anuncios/' . $anuncio['id_anuncio'];

        if(is_dir($diretorio)){
            @rmdir($diretorio);
        }
    }

    $_SESSION = [];

    if(ini_get('session.use_cookies')){
        $parametros = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $parametros['path'],
            $parametros['domain'],
            $parametros['secure'],
            $parametros['httponly']
        );
    }

    session_destroy();
    session_start();

    $_SESSION['sucesso'] = 'Sua conta foi excluída com sucesso.';

    header('Location: /UniHub/index.php');
    exit;
?>