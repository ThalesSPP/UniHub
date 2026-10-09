<?php
    require_once __DIR__ . '/../../includes/verificar-admin.php';
    require_once __DIR__ . '/../../config/database.php';

    $busca = trim($_GET['busca'] ?? '');
    $status = strtoupper(trim($_GET['status'] ?? 'TODOS'));

    $statusPermitidos = [
        'TODOS',
        'ATIVOS',
        'INATIVOS'
    ];

    if(!in_array($status, $statusPermitidos, true)){
        $status = 'TODOS';
    }

    $sql = "
        SELECT
            u.id_usuario,
            u.nome,
            u.cpf,
            u.email,
            u.telefone,
            u.ativo,
            u.data_cadastro,
            COUNT(a.id_anuncio) AS total_anuncios
        FROM usuario u
        INNER JOIN perfil p
            ON p.id_perfil = u.id_perfil
        LEFT JOIN anuncio a
            ON a.id_usuario = u.id_usuario
            AND a.status != 'REMOVIDO'
        WHERE p.nome = 'ANUNCIANTE'
    ";

    $parametros = [];

    if($busca !== ''){
        $sql .= "
            AND (
                u.nome LIKE :busca
                OR u.email LIKE :busca
                OR u.cpf LIKE :busca
                OR u.telefone LIKE :busca
            )
        ";

        $parametros['busca'] = '%' . $busca . '%';
    }

    if($status === 'ATIVOS'){
        $sql .= "
            AND u.ativo = TRUE
        ";
    }

    if($status === 'INATIVOS'){
        $sql .= "
            AND u.ativo = FALSE
        ";
    }

    $sql .= "
        GROUP BY
            u.id_usuario,
            u.nome,
            u.cpf,
            u.email,
            u.telefone,
            u.ativo,
            u.data_cadastro
        ORDER BY u.data_cadastro DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $titulo = 'UniHub - Gerenciar Usuários';

    ob_start();
?>

<section class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">
                Gerenciar usuários
            </h1>

            <p class="text-body-secondary mb-0">
                Consulte e gerencie os anunciantes cadastrados no UniHub.
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
            <form method="GET" action="/UniHub/pages/admin/usuarios.php">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-7">
                        <label for="busca" class="form-label">
                            Buscar usuário
                        </label>

                        <input type="text" class="form-control" id="busca" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome, e-mail, CPF ou telefone">
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="status" class="form-label">
                            Status
                        </label>

                        <select class="form-select" id="status" name="status">
                            <option value="TODOS" <?= $status === 'TODOS' ? 'selected' : '' ?>>
                                Todos
                            </option>

                            <option value="ATIVOS" <?= $status === 'ATIVOS' ? 'selected' : '' ?>>
                                Ativos
                            </option>

                            <option value="INATIVOS" <?= $status === 'INATIVOS' ? 'selected' : '' ?>>
                                Inativos
                            </option>
                        </select>
                    </div>

                    <div class="col-12 col-md-2 d-grid">
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
            Anunciantes
        </h2>

        <span class="text-body-secondary">
            <?= count($usuarios) ?>
            <?= count($usuarios) === 1 ? 'usuário encontrado' : 'usuários encontrados' ?>
        </span>
    </div>

    <?php if(!empty($usuarios)): ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">
                                    Usuário
                                </th>

                                <th class="py-3">
                                    Contato
                                </th>

                                <th class="py-3">
                                    CPF
                                </th>

                                <th class="py-3 text-center">
                                    Anúncios
                                </th>

                                <th class="py-3">
                                    Cadastro
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
                            <?php foreach($usuarios as $usuario): ?>
                                <tr>
                                    <td class="px-4">
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($usuario['nome']) ?>
                                        </div>

                                        <div class="text-body-secondary small">
                                            ID #<?= $usuario['id_usuario'] ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div>
                                            <?= htmlspecialchars($usuario['email']) ?>
                                        </div>

                                        <div class="text-body-secondary small">
                                            <?= htmlspecialchars($usuario['telefone']) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if(!empty($usuario['cpf'])): ?>
                                            <?= htmlspecialchars($usuario['cpf']) ?>

                                        <?php else: ?>
                                            <span class="text-body-secondary">
                                                Não informado
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge text-bg-primary">
                                            <?= $usuario['total_anuncios'] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= date('d/m/Y', strtotime($usuario['data_cadastro'])) ?>
                                    </td>

                                    <td>
                                        <?php if($usuario['ativo']): ?>
                                            <span class="badge text-bg-success">
                                                Ativo
                                            </span>

                                        <?php else: ?>
                                            <span class="badge text-bg-secondary">
                                                Inativo
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <?php if($usuario['ativo']): ?>
                                                <form action="/UniHub/actions/admin/alterar-status-usuario.php" method="POST" onsubmit="return confirm('Deseja realmente desativar este usuário?');">
                                                    <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">

                                                    <input type="hidden" name="acao" value="desativar">

                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        Desativar
                                                    </button>
                                                </form>

                                            <?php else: ?>
                                                <form action="/UniHub/actions/admin/alterar-status-usuario.php" method="POST">
                                                    <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">

                                                    <input type="hidden" name="acao" value="ativar">

                                                    <button type="submit" class="btn btn-outline-success btn-sm">
                                                        Ativar
                                                    </button>
                                                </form>
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
                    Nenhum usuário encontrado
                </h2>

                <p class="text-body-secondary mb-3">
                    Não encontramos anunciantes com os filtros informados.
                </p>

                <a href="/UniHub/pages/admin/usuarios.php" class="btn btn-outline-primary">
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