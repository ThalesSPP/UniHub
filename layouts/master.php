<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'UniHub' ?></title>
    <link rel="stylesheet" href="/UniHub/assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../includes/navbar.php'; ?>

    <main>
        <?= $conteudo ?? '' ?>
    </main>

    <?php require __DIR__ . '/../includes/footer.php'; ?>

    <button type="button" id="botaoTema" class="btn btn-outline-secondary botao-tema-flutuante" aria-label="Alternar tema">🌙</button>  

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"> </script>
    <script src="/UniHub/assets/js/script.js"></script>
</body>
</html>