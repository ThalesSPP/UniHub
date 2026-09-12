<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if (!isset($_SESSION['id_usuario'])) {
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

            <?php if (isset($_SESSION['sucesso'])): ?>
                <div class="alert alert-success" role="alert">
                    <?= htmlspecialchars($_SESSION['sucesso']) ?>
                </div>

                <?php unset($_SESSION['sucesso']); ?>
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

                        <a href="/UniHub/pages/conta/editar-conta.php" class="btn btn-outline-primary" >Editar dados</a>
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
                                <?php if ($usuario['ativo']): ?>
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
                    <div lass="d-flex flex-column flex-md-row justify-content-between  align-items-md-center gap-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1">
                                Meus anúncios
                            </h2>

                            <p class="text-body-secondary mb-0">
                                Consulte e gerencie os imóveis publicados por você.
                            </p>
                        </div>

                        <a href="#"  class="btn btn-outline-primary">Ver meus anúncios  </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
    $conteudo = ob_get_clean();
    require __DIR__ . '/../../layouts/master.php';
?>