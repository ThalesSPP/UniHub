<?php
    $titulo = "UniHub - Início";
    ob_start();
?>

<section class="hero">
    <div class="container py-5">
        <div class="row align-items-center py-lg-5">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-3">
                    Encontre a moradia ideal para sua vida acadêmica
                </h1>

                <p class="lead text-secondary mb-4">
                    Encontre casas, apartamentos, kitnets,
                    quartos e repúblicas voltadas ao público
                    estudantil do IFES Campus de Alegre.
                </p>

                <a hhref="/UniHub/pages/anuncios/imoveis.php" class="btn btn-primary btn-lg">
                    Ver imóveis
                </a>

            </div>

            <div class="col-lg-6 mt-5 mt-lg-0">
                <div class="hero-image">
                    Imagem do UniHub
                </div>
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

                            <option value="casa">
                                Casa
                            </option>

                            <option value="apartamento">
                                Apartamento
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

        <a href="imoveis.php" class="text-decoration-none">
            Ver todos
        </a>

    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="imagem-anuncio">
                    Foto do imóvel
                </div>
                <div class="card-body">
                    <span class="badge text-bg-primary mb-2">
                        Kitnet
                    </span>

                    <h5 class="card-title">
                        Kitnet próxima ao IFES
                    </h5>

                    <p class="text-secondary">
                        Alegre - ES
                    </p>

                    <h5 class="fw-bold">
                        R$ 750,00
                    </h5>

                    <a href="/UniHub/pages/anuncios/detalhes-anuncio.php?id=1" class="btn btn-outline-primary w-100 mt-2">
                        Ver detalhes
                    </a>

                </div>
            </div>
        </div>
    </div>
</section>

<?php
    $conteudo = ob_get_clean();
    require __DIR__ . '/layouts/master.php';
?>