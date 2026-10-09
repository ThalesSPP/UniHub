<?php
    require_once __DIR__ . '/../../includes/verificar-admin.php';
    require_once __DIR__ . '/../../config/database.php';

    $busca = trim($_GET['busca'] ?? '');
    $status = strtoupper(trim($_GET['status'] ?? 'TODOS'));
    $idTipo = trim($_GET['id_tipo'] ?? '');

    $statusPermitidos = [
        'TODOS',
        'ATIVO',
        'INATIVO',
        'REMOVIDO'
    ];

    if(!in_array($status, $statusPermitidos, true)){
        $status = 'TODOS';
    }

    if($idTipo !== '' && !ctype_digit($idTipo)){
        $idTipo = '';
    }

    $sql = "
        SELECT
            id_tipo,
            nome
        FROM tipo_anuncio
        WHERE ativo = TRUE
        ORDER BY
            CASE WHEN nome = 'OUTRO' THEN 1 ELSE 0 END,
            nome
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "
        SELECT
            a.id_anuncio,
            a.titulo,
            a.valor,
            a.estadia_minima_meses,
            a.status,
            a.data_cadastro,
            t.nome AS tipo,
            e.bairro,
            e.cidade,
            e.estado,
            u.id_usuario,
            u.nome AS anunciante,
            u.email AS email_anunciante,
            u.ativo AS usuario_ativo
        FROM anuncio a
        INNER JOIN tipo_anuncio t
            ON t.id_tipo = a.id_tipo
        INNER JOIN endereco e
            ON e.id_endereco = a.id_endereco
        INNER JOIN usuario u
            ON u.id_usuario = a.id_usuario
        WHERE 1 = 1
    ";

    $parametros = [];

    if($busca !== ''){
        $sql .= "
            AND (
                a.titulo LIKE :busca
                OR u.nome LIKE :busca
                OR u.email LIKE :busca
                OR e.bairro LIKE :busca
                OR e.cidade LIKE :busca
            )
        ";

        $parametros['busca'] = '%' . $busca . '%';
    }

    if($status !== 'TODOS'){
        $sql .= "
            AND a.status = :status
        ";

        $parametros['status'] = $status;
    }

    if($idTipo !== ''){
        $sql .= "
            AND a.id_tipo = :id_tipo
        ";

        $parametros['id_tipo'] = $idTipo;
    }

    $sql .= "
        ORDER BY a.data_cadastro DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    $anuncios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $titulo = 'UniHub - Gerenciar Anúncios';

    ob_start();
?>

<section class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">
                Gerenciar anúncios
            </h1>

            <p class="text-body-secondary mb-0">
                Consulte e gerencie os imóveis anunciados no UniHub.
            </p>
        </div>

        <a href="/UniHub/pages/admin/painel.php" class="btn btn-outline-secondary">
            Voltar ao painel
        </a>
    </div>

    <?php if(isset($_SESSION['sucesso'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['sucesso']) ?>
        </div>
        <?php unset($_SESSION['sucesso']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['erro'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['erro']) ?>
        </div>
        <?php unset($_SESSION['erro']); ?>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="/UniHub/pages/admin/anuncios.php">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-5">
                        <label for="busca" class="form-label">
                            Buscar anúncio
                        </label>

                        <input type="text" class="form-control" id="busca" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Título, anunciante, bairro ou cidade">
                    </div>

                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="id_tipo" class="form-label">
                            Tipo de imóvel
                        </label>

                        <select class="form-select" id="id_tipo" name="id_tipo">
                            <option value="">
                                Todos os tipos
                            </option>

                            <?php foreach($tipos as $tipo): ?>
                                <option value="<?= $tipo['id_tipo'] ?>" <?= $idTipo == $tipo['id_tipo'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucfirst(strtolower($tipo['nome']))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-4 col-lg-2">
                        <label for="status" class="form-label">
                            Status
                        </label>

                        <select class="form-select" id="status" name="status">
                            <option value="TODOS" <?= $status === 'TODOS' ? 'selected' : '' ?>>
                                Todos
                            </option>

                            <option value="ATIVO" <?= $status === 'ATIVO' ? 'selected' : '' ?>>
                                Ativos
                            </option>

                            <option value="INATIVO" <?= $status === 'INATIVO' ? 'selected' : '' ?>>
                                Inativos
                            </option>

                            <option value="REMOVIDO" <?= $status === 'REMOVIDO' ? 'selected' : '' ?>>
                                Removidos
                            </option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4 col-lg-2 d-grid">
                        <button type="submit" class="btn btn-primary">
                            Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 fw-bold mb-0">
            Anúncios
        </h2>

        <span class="text-body-secondary">
            <?= count($anuncios) ?>
            <?= count($anuncios) === 1 ? 'anúncio encontrado' : 'anúncios encontrados' ?>
        </span>
    </div>

    <?php if(!empty($anuncios)): ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">
                                    Anúncio
                                </th>

                                <th class="py-3">
                                    Anunciante
                                </th>

                                <th class="py-3">
                                    Localização
                                </th>

                                <th class="py-3">
                                    Valor
                                </th>

                                <th class="py-3 text-center">
                                    Estadia mínima
                                </th>

                                <th class="py-3">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-end">
                                    Ações
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach($anuncios as $anuncio): ?>
                                <tr>
                                    <td class="px-4">
                                        <div class="d-flex flex-column gap-1">
                                            <div class="fw-semibold">
                                                <?= htmlspecialchars($anuncio['titulo']) ?>
                                            </div>

                                            <div>
                                                <span class="badge text-bg-primary">
                                                    <?= htmlspecialchars(ucfirst(strtolower($anuncio['tipo']))) ?>
                                                </span>
                                            </div>

                                            <div class="text-body-secondary small">
                                                ID #<?= $anuncio['id_anuncio'] ?>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div>
                                            <?= htmlspecialchars($anuncio['anunciante']) ?>
                                        </div>

                                        <div class="text-body-secondary small">
                                            <?= htmlspecialchars($anuncio['email_anunciante']) ?>
                                        </div>

                                        <?php if(!$anuncio['usuario_ativo']): ?>
                                            <span class="badge text-bg-danger mt-1">
                                                Usuário inativo
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div>
                                            <?= htmlspecialchars($anuncio['bairro']) ?>
                                        </div>

                                        <div class="text-body-secondary small">
                                            <?= htmlspecialchars($anuncio['cidade']) ?> -
                                            <?= htmlspecialchars($anuncio['estado']) ?>
                                        </div>
                                    </td>

                                    <td class="fw-semibold">
                                        R$ <?= number_format($anuncio['valor'], 2, ',', '.') ?>
                                    </td>

                                    <td class="text-center">
                                        <?= $anuncio['estadia_minima_meses'] ?>
                                        <?= $anuncio['estadia_minima_meses'] == 1 ? 'mês' : 'meses' ?>
                                    </td>

                                    <td>
                                        <?php if($anuncio['status'] === 'ATIVO'): ?>
                                            <span class="badge text-bg-success">
                                                Ativo
                                            </span>

                                        <?php elseif($anuncio['status'] === 'INATIVO'): ?>
                                            <span class="badge text-bg-secondary">
                                                Inativo
                                            </span>

                                        <?php else: ?>
                                            <span class="badge text-bg-danger">
                                                Removido
                                            </span>

                                        <?php endif; ?>
                                    </td>

                                    <td class="px-4">
                                        <div class="d-flex justify-content-end flex-wrap gap-2">
                                            <?php if($anuncio['status'] !== 'REMOVIDO'): ?>
                                                <a href="/UniHub/pages/anuncios/detalhes-anuncio.php?id=<?= $anuncio['id_anuncio'] ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                                    Ver
                                                </a>
                                            <?php endif; ?>

                                            <?php if($anuncio['status'] === 'ATIVO'): ?>
                                                <form action="/UniHub/actions/admin/alterar-status-anuncio.php" method="POST" onsubmit="return confirm('Deseja realmente desativar este anúncio?');">
                                                    <input type="hidden" name="id_anuncio" value="<?= $anuncio['id_anuncio'] ?>">

                                                    <input type="hidden" name="acao" value="desativar">

                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        Desativar
                                                    </button>
                                                </form>

                                            <?php elseif($anuncio['status'] === 'INATIVO' && $anuncio['usuario_ativo']): ?>
                                                <form action="/UniHub/actions/admin/alterar-status-anuncio.php" method="POST">
                                                    <input type="hidden" name="id_anuncio" value="<?= $anuncio['id_anuncio'] ?>">

                                                    <input type="hidden" name="acao" value="ativar">

                                                    <button type="submit" class="btn btn-outline-success btn-sm">
                                                        Ativar
                                                    </button>
                                                </form>

                                            <?php elseif($anuncio['status'] === 'INATIVO' && !$anuncio['usuario_ativo']): ?>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Ative primeiro o anunciante responsável por este anúncio">
                                                    Ativar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <h2 class="h5 mb-2">
                    Nenhum anúncio encontrado
                </h2>

                <p class="text-body-secondary mb-3">
                    Não encontramos anúncios com os filtros informados.
                </p>

                <a href="/UniHub/pages/admin/anuncios.php" class="btn btn-outline-primary">
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