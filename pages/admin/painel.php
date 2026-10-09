<?php
    require_once __DIR__ . '/../../includes/verificar-admin.php';
    require_once __DIR__ . '/../../config/database.php';

    $sql = "
        SELECT COUNT(*)
        FROM usuario u
        INNER JOIN perfil p
            ON p.id_perfil = u.id_perfil
        WHERE p.nome = 'ANUNCIANTE'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $totalAnunciantes = $stmt->fetchColumn();

    $sql = "
        SELECT COUNT(*)
        FROM usuario u
        INNER JOIN perfil p
            ON p.id_perfil = u.id_perfil
        WHERE p.nome = 'ANUNCIANTE'
        AND u.ativo = TRUE
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $anunciantesAtivos = $stmt->fetchColumn();

    $sql = "
        SELECT COUNT(*)
        FROM anuncio
        WHERE status != 'REMOVIDO'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $totalAnuncios = $stmt->fetchColumn();

    $sql = "
        SELECT COUNT(*)
        FROM anuncio
        WHERE status = 'ATIVO'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $anunciosAtivos = $stmt->fetchColumn();

    $sql = "
        SELECT COUNT(*)
        FROM anuncio
        WHERE status = 'INATIVO'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $anunciosInativos = $stmt->fetchColumn();

    $sql = "
        SELECT
            u.id_usuario,
            u.nome,
            u.email,
            u.ativo,
            u.data_cadastro
        FROM usuario u
        INNER JOIN perfil p
            ON p.id_perfil = u.id_perfil
        WHERE p.nome = 'ANUNCIANTE'
        ORDER BY u.data_cadastro DESC
        LIMIT 5
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $usuariosRecentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "
        SELECT
            a.id_anuncio,
            a.titulo,
            a.valor,
            a.status,
            a.data_cadastro,
            t.nome AS tipo,
            e.cidade,
            e.estado,
            u.nome AS anunciante
        FROM anuncio a
        INNER JOIN tipo_anuncio t
            ON t.id_tipo = a.id_tipo
        INNER JOIN endereco e
            ON e.id_endereco = a.id_endereco
        INNER JOIN usuario u
            ON u.id_usuario = a.id_usuario
        WHERE a.status != 'REMOVIDO'
        ORDER BY a.data_cadastro DESC
        LIMIT 5
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $anunciosRecentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $titulo = 'UniHub - Painel Administrativo';

    ob_start();
?>

<section class="container py-5">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">
                Painel Administrativo
            </h1>

            <p class="text-body-secondary mb-0">
                Acompanhe usuários e anúncios cadastrados no UniHub.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="/UniHub/pages/admin/usuarios.php" class="btn btn-outline-primary">
                Usuários
            </a>

            <a href="/UniHub/pages/admin/anuncios.php" class="btn btn-primary">
                Anúncios
            </a>
        </div>
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

    <div class="row g-4 mb-5">
        <div class="col-12 col-md-6 col-xl">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-body-secondary mb-2">
                        Anunciantes
                    </p>

                    <h2 class="display-6 fw-bold mb-0">
                        <?= $totalAnunciantes ?>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-body-secondary mb-2">
                        Anunciantes ativos
                    </p>

                    <h2 class="display-6 fw-bold mb-0">
                        <?= $anunciantesAtivos ?>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-body-secondary mb-2">
                        Anúncios
                    </p>

                    <h2 class="display-6 fw-bold mb-0">
                        <?= $totalAnuncios ?>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-body-secondary mb-2">
                        Anúncios ativos
                    </p>

                    <h2 class="display-6 fw-bold mb-0 text-success">
                        <?= $anunciosAtivos ?>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-body-secondary mb-2">
                        Anúncios inativos
                    </p>

                    <h2 class="display-6 fw-bold mb-0 text-secondary">
                        <?= $anunciosInativos ?>
                    </h2>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="h5 fw-bold mb-1">
                                Usuários recentes
                            </h2>

                            <p class="text-body-secondary mb-0">
                                Últimos anunciantes cadastrados.
                            </p>
                        </div>

                        <a href="/UniHub/pages/admin/usuarios.php" class="text-decoration-none">
                            Ver todos
                        </a>
                    </div>

                    <?php if(!empty($usuariosRecentes)): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach($usuariosRecentes as $usuario): ?>
                                <div class="border rounded p-3">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div>
                                            <p class="fw-semibold mb-1">
                                                <?= htmlspecialchars($usuario['nome']) ?>
                                            </p>

                                            <p class="text-body-secondary small mb-1">
                                                <?= htmlspecialchars($usuario['email']) ?>
                                            </p>

                                            <p class="text-body-secondary small mb-0">
                                                <?= date('d/m/Y', strtotime($usuario['data_cadastro'])) ?>
                                            </p>
                                        </div>

                                        <div>
                                            <?php if($usuario['ativo']): ?>
                                                <span class="badge text-bg-success">
                                                    Ativo
                                                </span>

                                            <?php else: ?>
                                                <span class="badge text-bg-secondary">
                                                    Inativo
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <p class="text-body-secondary mb-0">
                            Nenhum anunciante cadastrado.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="h5 fw-bold mb-1">
                                Anúncios recentes
                            </h2>

                            <p class="text-body-secondary mb-0">
                                Últimos imóveis publicados.
                            </p>
                        </div>

                        <a href="/UniHub/pages/admin/anuncios.php" class="text-decoration-none">
                            Ver todos
                        </a>
                    </div>

                    <?php if(!empty($anunciosRecentes)): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach($anunciosRecentes as $anuncio): ?>
                                <div class="border rounded p-3">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div>
                                            <div class="d-flex flex-wrap gap-2 mb-2">
                                                <span class="badge text-bg-primary">
                                                    <?= htmlspecialchars(ucfirst(strtolower($anuncio['tipo']))) ?>
                                                </span>

                                                <?php if($anuncio['status'] === 'ATIVO'): ?>
                                                    <span class="badge text-bg-success">
                                                        Ativo
                                                    </span>

                                                <?php else: ?>
                                                    <span class="badge text-bg-secondary">
                                                        Inativo
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <p class="fw-semibold mb-1">
                                                <?= htmlspecialchars($anuncio['titulo']) ?>
                                            </p>

                                            <p class="text-body-secondary small mb-1">
                                                <?= htmlspecialchars($anuncio['cidade']) ?> -
                                                <?= htmlspecialchars($anuncio['estado']) ?>
                                            </p>

                                            <p class="text-body-secondary small mb-0">
                                                Anunciante:
                                                <?= htmlspecialchars($anuncio['anunciante']) ?>
                                            </p>
                                        </div>

                                        <div class="text-end">
                                            <p class="fw-bold mb-2">
                                                R$ <?= number_format($anuncio['valor'], 2, ',', '.') ?>
                                            </p>

                                            <a href="/UniHub/pages/anuncios/detalhes-anuncio.php?id=<?= $anuncio['id_anuncio'] ?>" class="btn btn-outline-primary btn-sm">Ver</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <p class="text-body-secondary mb-0">
                            Nenhum anúncio cadastrado.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
    $conteudo = ob_get_clean();
    require __DIR__ . '/../../layouts/master.php';
?>