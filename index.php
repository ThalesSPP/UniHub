<?php
    require_once __DIR__ . '/config/database.php';
    $titulo = "UniHub - Início";

    $sql = "
        SELECT
            a.id_anuncio,
            a.titulo,
            a.valor,
            t.nome AS tipo,
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
        ORDER BY a.data_cadastro DESC
        LIMIT 3
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $anuncios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ob_start();
?>

<section class="hero">
    <div class="container py-5">
        <div class="row py-lg-5">
            <div class="col-12">

                <h1 class="display-4 fw-bold mb-3">
                    Encontre a moradia ideal para sua vida acadêmica
                </h1>

                <p class="lead text-secondary mb-4">
                    O UniHub conecta estudantes a opções de moradia de forma simples e prática.
                    Encontre casas, apartamentos, kitnets, quartos e repúblicas voltadas ao
                    público estudantil do IFES Campus de Alegre.
                </p>

                <p class="text-body-secondary mb-4">
                    Consulte informações sobre os imóveis, localização, valores e condições
                    oferecidas pelos anunciantes. Tudo em um único lugar para facilitar sua
                    busca por uma nova moradia durante a vida acadêmica.
                </p>

                <a href="/UniHub/pages/anuncios/imoveis.php" class="btn btn-primary btn-lg">
                    Ver imóveis
                </a>
            </div>
        </div>
    </div>
</section>

<section class="container">
    <div class="card border-0 shadow-sm busca-home">
        <div class="card-body p-4">

            <form action="/UniHub/pages/anuncios/imoveis.php" method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            Tipo de imóvel
                        </label>
                        <select name="tipo" class="form-select">
                            <option value="">
                                Todos os tipos
                            </option>

                            <option value="apartamento">
                                Apartamento
                            </option>

                            <option value="casa">
                                Casa
                            </option>

                            <option value="kitnet">
                                Kitnet
                            </option>

                            <option value="quarto">
                                Quarto
                            </option>

                            <option value="republica">
                                República
                            </option>

                            <option value="outro">
                                Outro
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">
                            Localização
                        </label>
                        <input
                            type="text"
                            name="localizacao"
                            class="form-control"
                            placeholder="Ex.: Centro">

                    </div>


                    <div class="col-md-2">

                        <label class="form-label">
                            Valor máximo
                        </label>

                        <input
                            type="number"
                            name="valor"
                            class="form-control"
                            placeholder="R$">

                    </div>
                    <div class="col-md-2 d-flex align-items-end">

                        <button
                            type="submit"
                            class="btn btn-primary w-100">
                            Buscar
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</section>

<section class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                Imóveis disponíveis
            </h2>

            <p class="text-secondary mb-0">
                Confira algumas opções de moradia.
            </p>
        </div>

        <a href="/UniHub/pages/anuncios/imoveis.php" class="text-decoration-none">
            Ver todos
        </a>

    </div>

    <div class="row g-4">
        <?php if(!empty($anuncios)): ?>
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

                        <div class="card-body">
                            <span class="badge text-bg-primary mb-2">
                                <?= htmlspecialchars(ucfirst(strtolower($anuncio['tipo']))) ?>
                            </span>

                            <h5 class="card-title">
                                <?= htmlspecialchars($anuncio['titulo']) ?>
                            </h5>

                            <p class="text-secondary">
                                <?= htmlspecialchars($anuncio['cidade']) ?> - <?= htmlspecialchars($anuncio['estado']) ?>
                            </p>

                            <h5 class="fw-bold">
                                R$ <?= number_format($anuncio['valor'], 2, ',', '.') ?>
                            </h5>

                            <a href="/UniHub/pages/anuncios/detalhes-anuncio.php?id=<?= $anuncio['id_anuncio'] ?>" class="btn btn-outline-primary w-100 mt-2">
                                Ver detalhes
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-secondary text-center mb-0">
                    Nenhum imóvel disponível no momento.
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
    $conteudo = ob_get_clean();
    require __DIR__ . '/layouts/master.php';
?>