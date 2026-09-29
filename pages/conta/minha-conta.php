<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if(!isset($_SESSION['id_usuario'])){
        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    require_once __DIR__ . '/../../config/database.php';

    $sql = "
        SELECT
            u.id_usuario,
            u.nome,
            u.email,
            u.telefone,
            u.ativo,
            u.data_cadastro,
            u.data_atualizacao,
            p.nome AS perfil
        FROM usuario u
        INNER JOIN perfil p
            ON p.id_perfil = u.id_perfil
        WHERE u.id_usuario = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_SESSION['id_usuario']
    ]);

    $usuario = $stmt->fetch();
    if(!$usuario){
        session_destroy();

        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    $sql = "
        SELECT
            a.id_anuncio,
            a.titulo,
            a.valor,
            a.status,
            a.data_cadastro,
            t.nome AS tipo,
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
            ) AS imagem,
            (
                SELECT cc.usar_contrato
                FROM configuracao_contrato cc
                WHERE cc.id_anuncio = a.id_anuncio
                LIMIT 1
            ) AS usar_contrato
        FROM anuncio a
        INNER JOIN tipo_anuncio t
            ON t.id_tipo = a.id_tipo
        INNER JOIN endereco e
            ON e.id_endereco = a.id_endereco
        WHERE a.id_usuario = ?
        ORDER BY a.data_cadastro DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_SESSION['id_usuario']
    ]);

    $anuncios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $titulo = 'UniHub - Minha Conta';
    ob_start();
?>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="mb-4">
                <h1 class="h2 fw-bold mb-1">
                    Minha Conta
                </h1>

                <p class="text-body-secondary mb-0">
                    Consulte e gerencie as informações da sua conta.
                </p>
            </div>

            <?php if(isset($_SESSION['sucesso'])): ?>
                <div class="alert alert-success" role="alert">
                    <?= htmlspecialchars($_SESSION['sucesso']) ?>
                </div>

                <?php unset($_SESSION['sucesso']); ?>
            <?php endif; ?>

            <?php if(isset($_SESSION['erro'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($_SESSION['erro']) ?>
                </div>
                <?php unset($_SESSION['erro']); ?>
            <?php endif; ?>

            <div class="card auth-card">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                        <div>
                            <h2 class="h4 fw-bold mb-1">
                                <?= htmlspecialchars($usuario['nome']) ?>
                            </h2>

                            <span class="badge text-bg-primary">
                                <?= htmlspecialchars($usuario['perfil']) ?>
                            </span>
                        </div>

                        <a href="/UniHub/pages/conta/editar-conta.php" class="btn btn-outline-primary">
                            Editar dados
                        </a>
                    </div>

                    <hr>

                    <div class="row g-4 mt-1">
                        <div class="col-md-6">
                            <small class="text-body-secondary">
                                E-mail
                            </small>

                            <p class="fw-semibold mb-0">
                                <?= htmlspecialchars($usuario['email']) ?>
                            </p>
                        </div>

                        <div class="col-md-6">
                            <small class="text-body-secondary">
                                Telefone
                            </small>

                            <p class="fw-semibold mb-0">
                                <?= htmlspecialchars($usuario['telefone']) ?>
                            </p>
                        </div>

                        <div class="col-md-6">
                            <small class="text-body-secondary">
                                Tipo de conta
                            </small>

                            <p class="fw-semibold mb-0">
                                <?= htmlspecialchars($usuario['perfil']) ?>
                            </p>
                        </div>

                        <div class="col-md-6">
                            <small class="text-body-secondary">
                                Status da conta
                            </small>

                            <p class="fw-semibold mb-0">
                                <?php if($usuario['ativo']): ?>
                                    <span class="text-success">
                                        Ativa
                                    </span>

                                <?php else: ?>
                                    <span class="text-danger">
                                        Inativa
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="col-md-6">
                            <small class="text-body-secondary">
                                Membro desde
                            </small>

                            <p class="fw-semibold mb-0">
                                <?= date('d/m/Y', strtotime($usuario['data_cadastro'])) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card auth-card mt-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                        <div>
                            <h2 class="h5 fw-bold mb-1">
                                Meus anúncios
                            </h2>

                            <p class="text-body-secondary mb-0">
                                Consulte e gerencie os imóveis publicados por você.
                            </p>
                        </div>

                        <a href="/UniHub/pages/anuncios/novo-anuncio.php" class="btn btn-primary">
                            Novo anúncio
                        </a>
                    </div>

                    <?php if(!empty($anuncios)): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach($anuncios as $anuncio): ?>
                                <div class="card">
                                    <div class="row g-0">
                                        <div class="col-md-4">
                                            <?php if(!empty($anuncio['imagem'])): ?>
                                                <img src="/UniHub/<?= htmlspecialchars($anuncio['imagem']) ?>" class="w-100 h-100 rounded-start" style="min-height: 200px; object-fit: cover;" alt="<?= htmlspecialchars($anuncio['titulo']) ?>">

                                            <?php else: ?>
                                                <div class="imagem-anuncio h-100" style="min-height: 200px;">
                                                    Sem imagem
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-8">
                                            <div class="card-body h-100 d-flex flex-column">
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

                                                    <?php if($anuncio['usar_contrato']): ?>
                                                        <span class="badge text-bg-info">
                                                            Contrato disponível
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <h3 class="h5 fw-bold mb-1">
                                                    <?= htmlspecialchars($anuncio['titulo']) ?>
                                                </h3>

                                                <p class="text-body-secondary mb-2">
                                                    <?= htmlspecialchars($anuncio['bairro']) ?>,
                                                    <?= htmlspecialchars($anuncio['cidade']) ?> -
                                                    <?= htmlspecialchars($anuncio['estado']) ?>
                                                </p>

                                                <p class="fw-bold mb-3">
                                                    R$ <?= number_format($anuncio['valor'], 2, ',', '.') ?>
                                                </p>

                                                <div class="d-flex flex-wrap gap-2 mt-auto">
                                                    <a href="/UniHub/pages/anuncios/detalhes-anuncio.php?id=<?= $anuncio['id_anuncio'] ?>" class="btn btn-outline-secondary btn-sm">
                                                        Ver anúncio
                                                    </a>

                                                    <a href="/UniHub/pages/anuncios/editar-anuncio.php?id=<?= $anuncio['id_anuncio'] ?>" class="btn btn-outline-primary btn-sm">
                                                        Editar
                                                    </a>

                                                    <form action="/UniHub/actions/conta/alterar-anuncio.php" method="POST">
                                                        <input type="hidden" name="id_anuncio" value="<?= $anuncio['id_anuncio'] ?>">

                                                        <?php if($anuncio['status'] === 'ATIVO'): ?>
                                                            <input type="hidden" name="status" value="INATIVO">

                                                            <button type="submit" class="btn btn-outline-warning btn-sm">
                                                                Desativar
                                                            </button>

                                                        <?php else: ?>
                                                            <input type="hidden" name="status" value="ATIVO">
                                                            <button type="submit" class="btn btn-outline-success btn-sm">
                                                                Ativar
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>

                                                    <form action="/UniHub/actions/conta/excluir-anuncio.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este anúncio? Esta ação não poderá ser desfeita.');">
                                                        <input type="hidden" name="id_anuncio" value="<?= $anuncio['id_anuncio'] ?>">

                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            Excluir
                                                        </button>
                                                    </form>

                                                    <?php if($anuncio['usar_contrato']): ?>
                                                        <a href="/UniHub/pages/contratos/gerar-contrato.php?id=<?= $anuncio['id_anuncio'] ?>" class="btn btn-success btn-sm">
                                                            Emitir contrato
                                                        </a>

                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="O contrato não está habilitado para este anúncio.">
                                                            Contrato indisponível
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-body-secondary mb-3">
                                Você ainda não possui anúncios cadastrados.
                            </p>

                            <a href="/UniHub/pages/anuncios/novo-anuncio.php" class="btn btn-outline-primary">
                                Anunciar imóvel
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card auth-card mt-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold text-danger mb-1">
                        Excluir conta
                    </h2>

                    <p class="text-body-secondary mb-3">
                        Ao excluir sua conta, seus anúncios também serão removidos permanentemente.
                    </p>

                    <form action="/UniHub/actions/conta/excluir-conta.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir sua conta? Esta ação não poderá ser desfeita.');">
                        <div class="mb-3">
                            <label for="senha_exclusao" class="form-label">
                                Confirme sua senha
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="senha_exclusao"
                                name="senha"
                                placeholder="Digite sua senha atual"
                                required
                                autocomplete="current-password"
                            >
                        </div>

                        <button type="submit" class="btn btn-outline-danger">
                            Excluir minha conta
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
    $conteudo = ob_get_clean();
    require __DIR__ . '/../../layouts/master.php';
?>