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

        $_SESSION['sucesso'] = 'Anúncio atualizado com sucesso.';

        header('Location: /UniHub/pages/anuncios/detalhes-anuncio.php?id=' . $idAnuncio);
        exit;
    }
    
    catch(Throwable $erro){
        if($pdo->inTransaction()){
            $pdo->rollBack();
        }

        $_SESSION['erro'] = 'Erro ao atualizar anúncio: ' . $erro->getMessage();

        header('Location: /UniHub/pages/anuncios/editar-anuncio.php?id=' . $idAnuncio);
        exit;
    }
?>