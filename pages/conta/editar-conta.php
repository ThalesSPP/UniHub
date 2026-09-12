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
            id_usuario,
            nome,
            email,
            telefone
        FROM usuario
        WHERE id_usuario = ?
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

    $dadosEdicao = $_SESSION['dados_edicao'] ?? $usuario;
    unset($_SESSION['dados_edicao']);

    $titulo = 'UniHub - Editar Conta';
    ob_start();

?>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-6">
            <div class="mb-4">
                <h1 class="h2 fw-bold mb-1">
                    Editar conta
                </h1>

                <p class="text-body-secondary mb-0">
                    Atualize suas informações pessoais.
                </p>
            </div>

            <div class="card auth-card">
                <div class="card-body p-4 p-md-5">
                    <?php if (isset($_SESSION['erro'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($_SESSION['erro']) ?>
                        </div>
                        <?php unset($_SESSION['erro']); ?>
                    <?php endif; ?>

                    <form action="/UniHub/actions/conta/atualizar-conta.php" method="POST">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($dadosEdicao['nome']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label" >E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($dadosEdicao['email']) ?>" required autocomplete="email">
                        </div>

                        <div class="mb-3">
                            <label for="telefone" class="form-label"> Telefone</label>
                            <input type="tel" class="form-control" id="telefone" name="telefone" value="<?= htmlspecialchars($dadosEdicao['telefone']) ?>" required autocomplete="tel">
                        </div>

                        <hr class="my-4">

                        <h2 class="h5 fw-bold mb-1">
                            Alterar senha
                        </h2>

                        <p class="text-body-secondary mb-3">
                            Preencha os campos abaixo somente se desejar alterar sua senha.
                        </p>

                        <div class="mb-3">
                            <label for="senha_atual" class="form-label">Senha atual</label>
                            <input type="password" class="form-control" id="senha_atual" name="senha_atual" placeholder="Digite sua senha atual" autocomplete="current-password">
                        </div>

                        <div class="mb-3">
                            <label for="nova_senha" class="form-label" >Nova senha</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha" placeholder="Digite a nova senha" autocomplete="new-password">
                        </div>

                        <div class="mb-3">
                            <label for="confirmar_nova_senha" class="form-label">Confirmar nova senha</label>
                            <input type="password" class="form-control" id="confirmar_nova_senha" name="confirmar_nova_senha" placeholder="Digite novamente a nova senha" autocomplete="new-password">
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">Salvar alterações</button>
                            <a href="/UniHub/pages/conta/minha-conta.php" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
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