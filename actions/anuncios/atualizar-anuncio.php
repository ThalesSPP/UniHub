<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if(!isset($_SESSION['id_usuario'])){
        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Location: /UniHub/pages/anuncios/imoveis.php');
        exit;
    }

    require_once __DIR__ . '/../../config/database.php';

    $idUsuario = $_SESSION['id_usuario'];
    $idAnuncio = $_POST['id_anuncio'] ?? '';
    $titulo = trim($_POST['titulo'] ?? '');
    $idTipo = $_POST['id_tipo'] ?? '';
    $valor = trim($_POST['valor'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $logradouro = trim($_POST['logradouro'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $complemento = trim($_POST['complemento'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = strtoupper(trim($_POST['estado'] ?? ''));
    $cep = trim($_POST['cep'] ?? '');

    if(str_contains($valor, ',')){
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    }

    $usarContrato = isset($_POST['usar_contrato']) ? 1 : 0;
    $clausulaAnimais = isset($_POST['clausula_animais']) ? 1 : 0;
    $clausulaCaucao = isset($_POST['clausula_caucao']) ? 1 : 0;
    $clausulaAguaEnergia = isset($_POST['clausula_agua_energia']) ? 1 : 0;
    $clausulaRescisao = isset($_POST['clausula_rescisao']) ? 1 : 0;
    $clausulaVisitas = isset($_POST['clausula_visitas']) ? 1 : 0;
    $clausulaManutencao = isset($_POST['clausula_manutencao']) ? 1 : 0;
    $clausulaMulta = isset($_POST['clausula_multa']) ? 1 : 0;

    $clausulasExtras = $_POST['clausulas_extras'] ?? [];
    $clausulasExtras = array_map('trim', $clausulasExtras);

    $clausulasExtras = array_values(array_filter($clausulasExtras, function($clausula){
        return $clausula !== '';
    }));

    $imagensRemover = $_POST['imagens_remover'] ?? [];

    if(!is_array($imagensRemover)){
        $imagensRemover = [];
    }

    $imagensRemover = array_values(array_unique(array_filter(array_map(function($idImagem){
        $idImagem = (string) $idImagem;

        return ctype_digit($idImagem) ? (int) $idImagem : null;
    }, $imagensRemover))));

    if($idAnuncio === '' || !ctype_digit($idAnuncio)){
        header('Location: /UniHub/pages/anuncios/imoveis.php');
        exit;
    }

    if($titulo === '' || $idTipo === '' || $valor === '' || $descricao === '' || $logradouro === '' || $numero === '' || $bairro === '' || $cidade === '' || $estado === ''){
        $_SESSION['erro'] = 'Preencha todos os campos obrigatórios.';

        header('Location: /UniHub/pages/anuncios/editar-anuncio.php?id=' . $idAnuncio);
        exit;
    }

    if(!is_numeric($valor) || $valor < 0){
        $_SESSION['erro'] = 'Informe um valor de aluguel válido.';

        header('Location: /UniHub/pages/anuncios/editar-anuncio.php?id=' . $idAnuncio);
        exit;
    }

    $sql = "
        SELECT
            id_anuncio,
            id_endereco
        FROM anuncio
        WHERE id_anuncio = ?
        AND id_usuario = ?
        AND status != 'REMOVIDO'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idAnuncio, $idUsuario]);

    $anuncio = $stmt->fetch();

    if(!$anuncio){
        header('Location: /UniHub/pages/anuncios/imoveis.php');
        exit;
    }

    $idEnderecoAntigo = $anuncio['id_endereco'];

    $sql = "
        SELECT
            id_tipo
        FROM tipo_anuncio
        WHERE id_tipo = ?
        AND ativo = TRUE
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idTipo]);

    if(!$stmt->fetch()){
        $_SESSION['erro'] = 'O tipo de imóvel selecionado é inválido.';

        header('Location: /UniHub/pages/anuncios/editar-anuncio.php?id=' . $idAnuncio);
        exit;
    }

    $sql = "
        SELECT
            id_imagem,
            caminho_arquivo,
            ordem,
            principal
        FROM imagem_anuncio
        WHERE id_anuncio = ?
        ORDER BY
            principal DESC,
            ordem ASC,
            id_imagem ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idAnuncio]);

    $imagensAtuais = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $idsImagensAtuais = array_map('intval', array_column($imagensAtuais, 'id_imagem'));

    foreach($imagensRemover as $idImagem){
        if(!in_array($idImagem, $idsImagensAtuais, true)){
            $_SESSION['erro'] = 'Uma das imagens selecionadas para remoção é inválida.';

            header('Location: /UniHub/pages/anuncios/editar-anuncio.php?id=' . $idAnuncio);
            exit;
        }
    }

    $novasImagens = [];

    if(isset($_FILES['imagens']) && isset($_FILES['imagens']['name']) && is_array($_FILES['imagens']['name'])){
        $tiposPermitidos = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        foreach($_FILES['imagens']['name'] as $indice => $nomeOriginal){
            $erroUpload = $_FILES['imagens']['error'][$indice];

            if($erroUpload === UPLOAD_ERR_NO_FILE){
                continue;
            }

            if($erroUpload !== UPLOAD_ERR_OK){
                $_SESSION['erro'] = 'Ocorreu um erro ao enviar uma das imagens.';

                header('Location: /UniHub/pages/anuncios/editar-anuncio.php?id=' . $idAnuncio);
                exit;
            }

            $arquivoTemporario = $_FILES['imagens']['tmp_name'][$indice];
            $tamanho = $_FILES['imagens']['size'][$indice];

            if($tamanho > 5 * 1024 * 1024){
                $_SESSION['erro'] = 'Cada imagem deve possuir no máximo 5 MB.';

                header('Location: /UniHub/pages/anuncios/editar-anuncio.php?id=' . $idAnuncio);
                exit;
            }

            if(!is_uploaded_file($arquivoTemporario)){
                $_SESSION['erro'] = 'Uma das imagens enviadas é inválida.';

                header('Location: /UniHub/pages/anuncios/editar-anuncio.php?id=' . $idAnuncio);
                exit;
            }

            $tipoMime = $finfo->file($arquivoTemporario);

            if(!isset($tiposPermitidos[$tipoMime])){
                $_SESSION['erro'] = 'Envie apenas imagens JPG, PNG ou WEBP.';

                header('Location: /UniHub/pages/anuncios/editar-anuncio.php?id=' . $idAnuncio);
                exit;
            }

            $novasImagens[] = [
                'temporario' => $arquivoTemporario,
                'extensao' => $tiposPermitidos[$tipoMime]
            ];
        }
    }

    $quantidadeFinalImagens =
        count($imagensAtuais) -
        count($imagensRemover) +
        count($novasImagens);

    if($quantidadeFinalImagens > 10){
        $_SESSION['erro'] = 'O anúncio pode possuir no máximo 10 imagens.';

        header('Location: /UniHub/pages/anuncios/editar-anuncio.php?id=' . $idAnuncio);
        exit;
    }

    $arquivosNovosSalvos = [];
    $arquivosRemoverFisico = [];
    $pastaAnuncio = __DIR__ . '/../../uploads/anuncios/' . $idAnuncio;

    try{
        $pdo->beginTransaction();

        $sql = "
            SELECT
                id_endereco
            FROM endereco
            WHERE logradouro = ?
            AND numero = ?
            AND COALESCE(complemento, '') = ?
            AND bairro = ?
            AND cidade = ?
            AND estado = ?
            AND COALESCE(cep, '') = ?
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $logradouro,
            $numero,
            $complemento,
            $bairro,
            $cidade,
            $estado,
            $cep
        ]);

        $endereco = $stmt->fetch();

        if($endereco){
            $idEndereco = $endereco['id_endereco'];
        }
        
        else{
            $sql = "
                INSERT INTO endereco (
                    logradouro,
                    numero,
                    complemento,
                    bairro,
                    cidade,
                    estado,
                    cep
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $logradouro,
                $numero,
                $complemento !== '' ? $complemento : null,
                $bairro,
                $cidade,
                $estado,
                $cep !== '' ? $cep : null
            ]);

            $idEndereco = $pdo->lastInsertId();
        }

        $sql = "
            UPDATE anuncio
            SET
                id_tipo = ?,
                id_endereco = ?,
                titulo = ?,
                descricao = ?,
                valor = ?
            WHERE id_anuncio = ?
            AND id_usuario = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idTipo,
            $idEndereco,
            $titulo,
            $descricao,
            $valor,
            $idAnuncio,
            $idUsuario
        ]);

        $sql = "
            SELECT
                id_configuracao,
                id_modelo
            FROM configuracao_contrato
            WHERE id_anuncio = ?
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idAnuncio]);

        $configuracao = $stmt->fetch();

        if($usarContrato){
            if($configuracao){
                $idConfiguracao = $configuracao['id_configuracao'];
                $sql = "
                    UPDATE configuracao_contrato
                    SET
                        usar_contrato = TRUE,
                        clausula_animais = ?,
                        clausula_caucao = ?,
                        clausula_agua_energia = ?,
                        clausula_rescisao = ?,
                        clausula_visitas = ?,
                        clausula_manutencao = ?,
                        clausula_multa = ?
                    WHERE id_configuracao = ?
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $clausulaAnimais,
                    $clausulaCaucao,
                    $clausulaAguaEnergia,
                    $clausulaRescisao,
                    $clausulaVisitas,
                    $clausulaManutencao,
                    $clausulaMulta,
                    $idConfiguracao
                ]);

            }
            
            else{
                $sql = "
                    SELECT
                        id_modelo
                    FROM modelo_contrato
                    WHERE ativo = TRUE
                    ORDER BY id_modelo DESC
                    LIMIT 1
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute();

                $modeloContrato = $stmt->fetch();

                if(!$modeloContrato){
                    throw new Exception('Não existe um modelo de contrato ativo cadastrado.');
                }

                $sql = "
                    INSERT INTO configuracao_contrato (
                        id_anuncio,
                        id_modelo,
                        usar_contrato,
                        clausula_animais,
                        clausula_caucao,
                        clausula_agua_energia,
                        clausula_rescisao,
                        clausula_visitas,
                        clausula_manutencao,
                        clausula_multa
                    )
                    VALUES (?, ?, TRUE, ?, ?, ?, ?, ?, ?, ?)
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $idAnuncio,
                    $modeloContrato['id_modelo'],
                    $clausulaAnimais,
                    $clausulaCaucao,
                    $clausulaAguaEnergia,
                    $clausulaRescisao,
                    $clausulaVisitas,
                    $clausulaManutencao,
                    $clausulaMulta
                ]);

                $idConfiguracao = $pdo->lastInsertId();
            }

            $sql = "
                DELETE FROM clausula_extra
                WHERE id_configuracao = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idConfiguracao]);

            if(!empty($clausulasExtras)){
                $sql = "
                    INSERT INTO clausula_extra (
                        id_configuracao,
                        texto,
                        ordem
                    )
                    VALUES (?, ?, ?)
                ";

                $stmt = $pdo->prepare($sql);

                foreach($clausulasExtras as $indice => $clausula){
                    $stmt->execute([
                        $idConfiguracao,
                        $clausula,
                        $indice + 1
                    ]);
                }
            }
        }
        
        else{
            if($configuracao){
                $sql = "
                    DELETE FROM configuracao_contrato
                    WHERE id_configuracao = ?
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$configuracao['id_configuracao']]);
            }
        }

        if(!empty($imagensRemover)){
            $placeholders = implode(', ', array_fill(0, count($imagensRemover), '?'));

            $sql = "
                DELETE FROM imagem_anuncio
                WHERE id_anuncio = ?
                AND id_imagem IN ($placeholders)
            ";

            $parametros = array_merge(
                [$idAnuncio],
                $imagensRemover
            );

            $stmt = $pdo->prepare($sql);
            $stmt->execute($parametros);

            foreach($imagensAtuais as $imagem){
                if(in_array((int) $imagem['id_imagem'], $imagensRemover, true)){
                    $arquivosRemoverFisico[] =
                        $pastaAnuncio . '/' . basename($imagem['caminho_arquivo']);
                }
            }
        }

        if(!empty($novasImagens)){
            if(!is_dir($pastaAnuncio)){
                if(!mkdir($pastaAnuncio, 0755, true) && !is_dir($pastaAnuncio)){
                    throw new Exception('Não foi possível criar a pasta das imagens.');
                }
            }

            $sql = "
                SELECT COALESCE(MAX(ordem), 0)
                FROM imagem_anuncio
                WHERE id_anuncio = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idAnuncio]);

            $proximaOrdem = (int) $stmt->fetchColumn();

            $sql = "
                INSERT INTO imagem_anuncio (
                    id_anuncio,
                    caminho_arquivo,
                    ordem,
                    principal
                )
                VALUES (?, ?, ?, FALSE)
            ";

            $stmtImagem = $pdo->prepare($sql);

            foreach($novasImagens as $imagem){
                $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $imagem['extensao'];
                $caminhoFisico = $pastaAnuncio . '/' . $nomeArquivo;

                if(!move_uploaded_file($imagem['temporario'], $caminhoFisico)){
                    throw new Exception('Não foi possível salvar uma das novas imagens.');
                }

                $arquivosNovosSalvos[] = $caminhoFisico;

                $caminhoBanco =
                    'uploads/anuncios/' .
                    $idAnuncio .
                    '/' .
                    $nomeArquivo;

                $proximaOrdem++;

                $stmtImagem->execute([
                    $idAnuncio,
                    $caminhoBanco,
                    $proximaOrdem
                ]);
            }
        }

        $sql = "
            SELECT
                id_imagem
            FROM imagem_anuncio
            WHERE id_anuncio = ?
            ORDER BY
                principal DESC,
                ordem ASC,
                id_imagem ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idAnuncio]);

        $idsImagensFinais = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if(!empty($idsImagensFinais)){
            $sql = "
                UPDATE imagem_anuncio
                SET
                    ordem = ?,
                    principal = ?
                WHERE id_imagem = ?
                AND id_anuncio = ?
            ";

            $stmtImagem = $pdo->prepare($sql);

            foreach($idsImagensFinais as $indice => $idImagem){
                $stmtImagem->execute([
                    $indice + 1,
                    $indice === 0 ? 1 : 0,
                    $idImagem,
                    $idAnuncio
                ]);
            }
        }

        if($idEnderecoAntigo != $idEndereco){
            $sql = "
                SELECT
                    COUNT(*) AS total
                FROM anuncio
                WHERE id_endereco = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idEnderecoAntigo]);

            $usoEndereco = $stmt->fetch();

            if($usoEndereco['total'] == 0){
                $sql = "
                    DELETE FROM endereco
                    WHERE id_endereco = ?
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$idEnderecoAntigo]);
            }
        }

        $pdo->commit();

        foreach($arquivosRemoverFisico as $arquivo){
            if(is_file($arquivo)){
                @unlink($arquivo);
            }
        }

        if(is_dir($pastaAnuncio)){
            $conteudoPasta = array_diff(
                scandir($pastaAnuncio),
                ['.', '..']
            );

            if(empty($conteudoPasta)){
                @rmdir($pastaAnuncio);
            }
        }

        $_SESSION['sucesso'] = 'Anúncio atualizado com sucesso.';

        header('Location: /UniHub/pages/anuncios/detalhes-anuncio.php?id=' . $idAnuncio);
        exit;
    }
    
    catch(Throwable $erro){
        if($pdo->inTransaction()){
            $pdo->rollBack();
        }

        foreach($arquivosNovosSalvos as $arquivo){
            if(is_file($arquivo)){
                @unlink($arquivo);
            }
        }

        $_SESSION['erro'] = 'Erro ao atualizar anúncio: ' . $erro->getMessage();

        header('Location: /UniHub/pages/anuncios/editar-anuncio.php?id=' . $idAnuncio);
        exit;
    }
?>