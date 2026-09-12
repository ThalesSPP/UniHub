<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if(!isset($_SESSION['id_usuario'])){
        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Location: /UniHub/pages/anuncios/novo-anuncio.php');
        exit;
    }

    require_once __DIR__ . '/../../config/database.php';

    $idUsuario = $_SESSION['id_usuario'];
    $titulo = trim($_POST['titulo'] ?? '');
    $idTipo = $_POST['id_tipo'] ?? '';
    $valor = $_POST['valor'] ?? '';
    $descricao = trim($_POST['descricao'] ?? '');
    $logradouro = trim($_POST['logradouro'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $complemento = trim($_POST['complemento'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = strtoupper(trim($_POST['estado'] ?? ''));
    $cep = trim($_POST['cep'] ?? '');

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

    $_SESSION['dados_anuncio'] = [
        'titulo' => $titulo,
        'id_tipo' => $idTipo,
        'valor' => $valor,
        'descricao' => $descricao,
        'logradouro' => $logradouro,
        'numero' => $numero,
        'complemento' => $complemento,
        'bairro' => $bairro,
        'cidade' => $cidade,
        'estado' => $estado,
        'cep' => $cep,
        'usar_contrato' => $usarContrato,
        'clausula_animais' => $clausulaAnimais,
        'clausula_caucao' => $clausulaCaucao,
        'clausula_agua_energia' => $clausulaAguaEnergia,
        'clausula_rescisao' => $clausulaRescisao,
        'clausula_visitas' => $clausulaVisitas,
        'clausula_manutencao' => $clausulaManutencao,
        'clausula_multa' => $clausulaMulta,
        'clausulas_extras' => $clausulasExtras
    ];

    if($titulo === '' || $idTipo === '' || $valor === '' || $descricao === '' || $logradouro === '' || $numero === '' || $bairro === '' || $cidade === '' || $estado === ''){
        $_SESSION['erro'] = 'Preencha todos os campos obrigatórios.';

        header('Location: /UniHub/pages/anuncios/novo-anuncio.php');
        exit;
    }

    if(!is_numeric($valor) || $valor < 0){
        $_SESSION['erro'] = 'Informe um valor de aluguel válido.';

        header('Location: /UniHub/pages/anuncios/novo-anuncio.php');
        exit;
    }

    $sql = "
        SELECT id_tipo
        FROM tipo_anuncio
        WHERE id_tipo = ?
        AND ativo = TRUE
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idTipo]);

    if(!$stmt->fetch()){
        $_SESSION['erro'] = 'O tipo de imóvel selecionado é inválido.';

        header('Location: /UniHub/pages/anuncios/novo-anuncio.php');
        exit;
    }

    $idModelo = null;

    if($usarContrato){
        $sql = "
            SELECT id_modelo
            FROM modelo_contrato
            WHERE ativo = TRUE
            ORDER BY id_modelo DESC
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $modeloContrato = $stmt->fetch();

        if(!$modeloContrato){
            $_SESSION['erro'] = 'Não existe um modelo de contrato ativo cadastrado.';

            header('Location: /UniHub/pages/anuncios/novo-anuncio.php');
            exit;
        }
        $idModelo = $modeloContrato['id_modelo'];
    }

    $imagens = [];

    if(isset($_FILES['imagens']) && isset($_FILES['imagens']['name']) && is_array($_FILES['imagens']['name'])){
        $quantidadeImagens = 0;

        foreach($_FILES['imagens']['name'] as $nomeArquivo){
            if($nomeArquivo !== ''){
                $quantidadeImagens++;
            }
        }

        if($quantidadeImagens > 10){
            $_SESSION['erro'] = 'Você pode enviar no máximo 10 imagens por anúncio.';

            header('Location: /UniHub/pages/anuncios/novo-anuncio.php');
            exit;
        }

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

                header('Location: /UniHub/pages/anuncios/novo-anuncio.php');
                exit;
            }

            $arquivoTemporario = $_FILES['imagens']['tmp_name'][$indice];
            $tamanho = $_FILES['imagens']['size'][$indice];

            if($tamanho > 5 * 1024 * 1024){
                $_SESSION['erro'] = 'Cada imagem deve possuir no máximo 5 MB.';

                header('Location: /UniHub/pages/anuncios/novo-anuncio.php');
                exit;
            }

            if(!is_uploaded_file($arquivoTemporario)){
                $_SESSION['erro'] = 'Uma das imagens enviadas é inválida.';

                header('Location: /UniHub/pages/anuncios/novo-anuncio.php');
                exit;
            }

            $tipoMime = $finfo->file($arquivoTemporario);

            if(!isset($tiposPermitidos[$tipoMime])){
                $_SESSION['erro'] = 'Envie apenas imagens JPG, PNG ou WEBP.';

                header('Location: /UniHub/pages/anuncios/novo-anuncio.php');
                exit;
            }

            $imagens[] = [
                'temporario' => $arquivoTemporario,
                'extensao' => $tiposPermitidos[$tipoMime]
            ];
        }
    }

    $arquivosSalvos = [];
    $pastaAnuncio = null;

    try{
        $pdo->beginTransaction();
        $sql = "
            SELECT id_endereco
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
            INSERT INTO anuncio (
                id_usuario,
                id_tipo,
                id_endereco,
                titulo,
                descricao,
                valor,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, 'ATIVO')
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idUsuario,
            $idTipo,
            $idEndereco,
            $titulo,
            $descricao,
            $valor
        ]);

        $idAnuncio = $pdo->lastInsertId();

        if(!empty($imagens)){
            $pastaAnuncio = __DIR__ . '/../../uploads/anuncios/' . $idAnuncio;

            if(!is_dir($pastaAnuncio)){
                if(!mkdir($pastaAnuncio, 0755, true) && !is_dir($pastaAnuncio)){
                    throw new Exception('Não foi possível criar a pasta das imagens.');
                }
            }

            $sql = "
                INSERT INTO imagem_anuncio (
                    id_anuncio,
                    caminho_arquivo,
                    ordem,
                    principal
                )
                VALUES (?, ?, ?, ?)
            ";

            $stmtImagem = $pdo->prepare($sql);

            foreach($imagens as $indice => $imagem){
                $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $imagem['extensao'];
                $caminhoFisico = $pastaAnuncio . '/' . $nomeArquivo;

                if(!move_uploaded_file($imagem['temporario'], $caminhoFisico)){
                    throw new Exception('Não foi possível salvar uma das imagens.');
                }

                $arquivosSalvos[] = $caminhoFisico;

                $caminhoBanco = 'uploads/anuncios/' . $idAnuncio . '/' . $nomeArquivo;
                $ordem = $indice + 1;
                $principal = $indice === 0 ? 1 : 0;
                $stmtImagem->execute([
                    $idAnuncio,
                    $caminhoBanco,
                    $ordem,
                    $principal
                ]);
            }
        }

        if($usarContrato){
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
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $idAnuncio,
                $idModelo,
                $usarContrato,
                $clausulaAnimais,
                $clausulaCaucao,
                $clausulaAguaEnergia,
                $clausulaRescisao,
                $clausulaVisitas,
                $clausulaManutencao,
                $clausulaMulta
            ]);

            $idConfiguracao = $pdo->lastInsertId();

            if(!empty($clausulasExtras)){
                $sql = "
                    INSERT INTO clausula_extra (
                        id_configuracao,
                        texto,
                        ordem
                    )
                    VALUES (?, ?, ?)
                ";

                $stmtClausula = $pdo->prepare($sql);

                foreach($clausulasExtras as $indice => $clausula){
                    $stmtClausula->execute([
                        $idConfiguracao,
                        $clausula,
                        $indice + 1

                    ]);
                }
            }
        }

        $pdo->commit();
        unset($_SESSION['dados_anuncio']);
        $_SESSION['sucesso'] = 'Anúncio publicado com sucesso.';

        header('Location: /UniHub/pages/anuncios/detalhes-anuncio.php?id=' . $idAnuncio);
        exit;

    }
    
    catch(Throwable $erro){
        if($pdo->inTransaction()){

            $pdo->rollBack();
        }

        foreach($arquivosSalvos as $arquivo){
            if(file_exists($arquivo)){
                unlink($arquivo);
            }
        }

        if($pastaAnuncio !== null && is_dir($pastaAnuncio)){
            $arquivosRestantes = array_diff(scandir($pastaAnuncio), ['.', '..']);

            if(empty($arquivosRestantes)){
                rmdir($pastaAnuncio);
            }
        }

        $_SESSION['erro'] = 'Não foi possível cadastrar o anúncio. Tente novamente.';

        header('Location: /UniHub/pages/anuncios/novo-anuncio.php');
        exit;
    }
?>