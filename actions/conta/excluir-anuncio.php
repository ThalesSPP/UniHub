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
    $idUsuario = $_SESSION['id_usuario'];

    if(!$idAnuncio){
        $_SESSION['erro'] = 'Anúncio inválido.';

        header('Location: /UniHub/pages/conta/minha-conta.php');
        exit;
    }

    $sql = "
        SELECT
            id_anuncio,
            id_endereco
        FROM anuncio
        WHERE id_anuncio = ?
        AND id_usuario = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $idAnuncio,
        $idUsuario
    ]);

    $anuncio = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$anuncio){
        $_SESSION['erro'] = 'Você não possui permissão para excluir este anúncio.';

        header('Location: /UniHub/pages/conta/minha-conta.php');
        exit;
    }

    $sql = "
        SELECT
            caminho_arquivo
        FROM imagem_anuncio
        WHERE id_anuncio = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $idAnuncio
    ]);

    $imagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    try{
        $pdo->beginTransaction();

        $sql = "
            DELETE cg
            FROM contrato_gerado cg
            INNER JOIN configuracao_contrato cc
                ON cc.id_configuracao = cg.id_configuracao
            WHERE cc.id_anuncio = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idAnuncio
        ]);

        $sql = "
            DELETE ce
            FROM clausula_extra ce
            INNER JOIN configuracao_contrato cc
                ON cc.id_configuracao = ce.id_configuracao
            WHERE cc.id_anuncio = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idAnuncio
        ]);

        $sql = "
            DELETE FROM configuracao_contrato
            WHERE id_anuncio = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idAnuncio
        ]);

        $sql = "
            DELETE FROM imagem_anuncio
            WHERE id_anuncio = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idAnuncio
        ]);

        $sql = "
            DELETE FROM anuncio
            WHERE id_anuncio = ?
            AND id_usuario = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idAnuncio,
            $idUsuario
        ]);

        $sql = "
            SELECT COUNT(*) AS total
            FROM anuncio
            WHERE id_endereco = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $anuncio['id_endereco']
        ]);

        $enderecoEmUso = $stmt->fetch(PDO::FETCH_ASSOC);

        if($enderecoEmUso['total'] == 0){
            $sql = "
                DELETE FROM endereco
                WHERE id_endereco = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $anuncio['id_endereco']
            ]);
        }

        $pdo->commit();

        foreach($imagens as $imagem){
            $caminho = __DIR__ . '/../../' . $imagem['caminho_arquivo'];

            if(is_file($caminho)){
                unlink($caminho);
            }
        }

        $diretorioImagens = __DIR__ . '/../../uploads/anuncios/' . $idAnuncio;

        if(is_dir($diretorioImagens)){
            @rmdir($diretorioImagens);
        }

        $_SESSION['sucesso'] = 'Anúncio excluído com sucesso.';

    }
    
    catch(Throwable $erro){
        if($pdo->inTransaction()){
            $pdo->rollBack();
        }

        $_SESSION['erro'] = 'Não foi possível excluir o anúncio. Tente novamente.';
    }

    header('Location: /UniHub/pages/conta/minha-conta.php');
    exit;
?>