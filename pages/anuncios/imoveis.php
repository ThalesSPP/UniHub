<?php
    require_once __DIR__ . '/../../config/database.php';

    $titulo = 'UniHub - Imóveis';
    $tipo = trim($_GET['tipo'] ?? '');
    $localizacao = trim($_GET['localizacao'] ?? '');
    $valor = trim($_GET['valor'] ?? '');

    $sqlTipos = "
        SELECT
            id_tipo,
            nome
        FROM tipo_anuncio
        WHERE ativo = TRUE
        ORDER BY
            CASE WHEN nome = 'OUTRO' THEN 1 ELSE 0 END,
            nome
    ";

    $stmtTipos = $pdo->prepare($sqlTipos);
    $stmtTipos->execute();
    $tipos = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

    $sql = "
        SELECT
            a.id_anuncio,
            a.titulo,
            a.descricao,
            a.valor,
            a.data_cadastro,
            t.nome AS tipo,
            e.logradouro,
            e.bairro,
            e.cidade,
            e.estado,
            (
                SELECT ia.caminho_arquivo
                FROM imagem_anuncio ia
                WHERE ia.id_anuncio = a.id_anuncio
                ORDER BY
                    ia.principal DESC,
                    ia.ordem ASC,
                    ia.id_imagem ASC
                LIMIT 1
            ) AS imagem
        FROM anuncio a
        INNER JOIN tipo_anuncio t
            ON t.id_tipo = a.id_tipo
        INNER JOIN endereco e
            ON e.id_endereco = a.id_endereco
        WHERE a.status = 'ATIVO'
    ";

    $parametros = [];

    if($tipo !== ''){
        $sql .= "
            AND LOWER(t.nome) = LOWER(:tipo)
        ";

        $parametros['tipo'] = $tipo;
    }

    if($localizacao !== ''){
        $sql .= "
            AND (
                e.cidade LIKE :localizacaoCidade
                OR e.bairro LIKE :localizacaoBairro
                OR e.logradouro LIKE :localizacaoLogradouro
            )
        ";

        $parametros['localizacaoCidade'] = '%' . $localizacao . '%';
        $parametros['localizacaoBairro'] = '%' . $localizacao . '%';
        $parametros['localizacaoLogradouro'] = '%' . $localizacao . '%';
    }

    if($valor !== '' && is_numeric($valor)){
        $sql .= "
            AND a.valor <= :valor
        ";

        $parametros['valor'] = $valor;
    }

    $sql .= "
        ORDER BY a.data_cadastro DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    $anuncios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
?>

<section class="container py-5">
    <div class="mb-4">
        <h1 class="fw-bold mb-2">
            Imóveis
        </h1>

        <p class="text-body-secondary mb-0">
            Encontre uma opção de moradia que combine com você.
        </p>
    </div>

    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4">
            <form action="/UniHub/pages/anuncios/imoveis.php" method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="tipo" class="form-label">
                            Tipo de imóvel
                        </label>

                        <select class="form-select" id="tipo" name="tipo">
                            <option value="">
                                Todos os tipos
                            </option>
                            <?php foreach($tipos as $tipoAnuncio): ?>
                                <option value="<?= htmlspecialchars(strtolower($tipoAnuncio['nome'])) ?>" <?= strtolower($tipo) === strtolower($tipoAnuncio['nome']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucfirst(strtolower($tipoAnuncio['nome']))) ?>
                                </option>

                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="localizacao" class="form-label">
                            Localização
                        </label>

                        <input type="text" class="form-control" id="localizacao" name="localizacao" placeholder="Ex.: Centro" value="<?= htmlspecialchars($localizacao) ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="valor" class="form-label">
                            Valor máximo
                        </label>

                        <input type="number" class="form-control" id="valor" name="valor" placeholder="R$" min="0" step="0.01" value="<?= htmlspecialchars($valor) ?>">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            Buscar
                        </button>
                    </div>
                </div>

                <?php if($tipo !== '' || $localizacao !== '' || $valor !== ''): ?>
                    <div class="mt-3">
                        <a href="/UniHub/pages/anuncios/imoveis.php" class="text-decoration-none">
                            Limpar filtros
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-1">
                Imóveis disponíveis
            </h2>

            <p class="text-body-secondary mb-0">
                <?php if(count($anuncios) === 1): ?>
                    1 imóvel encontrado.

                <?php else: ?>
                    <?= count($anuncios) ?> imóveis encontrados.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php if(!empty($anuncios)): ?>
        <div class="row g-4">
            <?php foreach($anuncios as $anuncio): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <?php if(!empty($anuncio['imagem'])): ?>
                            <img src="/UniHub/<?= htmlspecialchars($anuncio['imagem']) ?>" class="card-img-top" style="height: 220px; object-fit: cover;" alt="<?= htmlspecialchars($anuncio['titulo']) ?>">

                        <?php else: ?>
                            <div class="imagem-anuncio">
                                Sem imagem
                            </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <div>
                                <span class="badge text-bg-primary mb-2">
                                    <?= htmlspecialchars(ucfirst(strtolower($anuncio['tipo']))) ?>
                                </span>

                                <h5 class="card-title">
                                    <?= htmlspecialchars($anuncio['titulo']) ?>
                                </h5>

                                <p class="text-body-secondary mb-2">
                                    <?= htmlspecialchars($anuncio['bairro']) ?>,
                                    <?= htmlspecialchars($anuncio['cidade']) ?> -
                                    <?= htmlspecialchars($anuncio['estado']) ?>
                                </p>

                                <h5 class="fw-bold mb-3">
                                    R$ <?= number_format($anuncio['valor'], 2, ',', '.') ?>
                                </h5>
                            </div>

                            <div class="mt-auto">
                                <a href="/UniHub/pages/anuncios/detalhes-anuncio.php?id=<?= $anuncio['id_anuncio'] ?>" class="btn btn-outline-primary w-100">
                                    Ver detalhes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <h2 class="h5 mb-2">
                    Nenhum imóvel encontrado
                </h2>

                <p class="text-body-secondary mb-3">
                    Não encontramos anúncios que correspondam aos filtros informados.
                </p>

                <a href="/UniHub/pages/anuncios/imoveis.php" class="btn btn-outline-primary">
                    Limpar filtros
                </a>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php
    $conteudo = ob_get_clean();
    require __DIR__ . '/../../layouts/master.php';
?>