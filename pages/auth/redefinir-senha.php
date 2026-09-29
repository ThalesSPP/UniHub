<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if(isset($_SESSION['id_usuario'])){
        header('Location: /UniHub/index.php');
        exit;
    }

    require_once __DIR__ . '/../../config/database.php';

    $token = trim($_GET['token'] ?? '');
    $tokenValido = false;

    if($token !== ''){
        $tokenHash = hash('sha256', $token);

        $sql = "
            SELECT
                rs.id_recuperacao,
                rs.id_usuario
            FROM recuperacao_senha rs
            INNER JOIN usuario u
                ON u.id_usuario = rs.id_usuario
            WHERE rs.token_hash = ?
            AND rs.utilizado = FALSE
            AND rs.data_expiracao >= NOW()
            AND u.ativo = TRUE
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $tokenHash
        ]);

        $recuperacao = $stmt->fetch(PDO::FETCH_ASSOC);

        if($recuperacao){
            $tokenValido = true;
        }
    }

    $titulo = 'UniHub - Redefinir senha';
    ob_start();
?>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card auth-card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <?php if(!$tokenValido): ?>
                        <div class="text-center mb-4">
                            <h1 class="h3 fw-bold">
                                Link inválido
                            </h1>

                            <p class="text-body-secondary mb-0">
                                Este link de recuperação é inválido, expirou ou já foi utilizado.
                            </p>
                        </div>

                        <div class="d-grid">
                            <a href="/UniHub/pages/auth/esqueci-senha.php" class="btn btn-primary">
                                Solicitar novo link
                            </a>
                        </div>

                        <hr class="my-4">

                        <div class="text-center">
                            <a href="/UniHub/pages/auth/login.php" class="text-decoration-none">
                                Voltar para o login
                            </a>
                        </div>

                    <?php else: ?>

                        <div class="text-center mb-4">
                            <h1 class="h3 fw-bold">
                                Redefinir senha
                            </h1>

                            <p class="text-body-secondary mb-0">
                                Informe sua nova senha para acessar o UniHub.
                            </p>
                        </div>

                        <?php if(isset($_SESSION['erro'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($_SESSION['erro']) ?>
                            </div>

                            <?php unset($_SESSION['erro']); ?>
                        <?php endif; ?>

                        <form action="/UniHub/actions/auth/atualizar-senha.php" method="POST">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                            <div class="mb-3">
                                <label for="senha" class="form-label">
                                    Nova senha
                                </label>

                                <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite sua nova senha" minlength="8"
                                    pattern="(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" title="A senha deve possuir no mínimo 8 caracteres, uma letra maiúscula, um número e um caractere especial." required autocomplete="new-password">

                                <div class="form-text">
                                    A senha deve possuir no mínimo 8 caracteres, uma letra maiúscula, um número e um caractere especial.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="confirmar_senha" class="form-label">
                                    Confirmar nova senha
                                </label>

                                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" placeholder="Digite novamente sua nova senha"
                                    minlength="8" required autocomplete="new-password">
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">
                                    Redefinir senha
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <a href="/UniHub/pages/auth/login.php" class="text-decoration-none">
                                Voltar para o login
                            </a>
                        </div>
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