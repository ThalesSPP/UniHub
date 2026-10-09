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
            a.estadia_minima_meses,
            a.status,
            a.data_cadastro,
            t.nome AS tipo,
            e.logradouro,
            e.numero,
            e.complemento,
            e.bairro,
            e.cidade,
            e.estado,
            e.cep,
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

    $estados = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF',
        'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS',
        'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
    ];

    $contratoGeradoUrl = $_SESSION['contrato_gerado_url'] ?? null;

    if($contratoGeradoUrl !== null){
        unset($_SESSION['contrato_gerado_url']);
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

            <?php if(isset($_SESSION['sucesso'])): ?>
                <div class="alert alert-success d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2" role="alert">
                    <span>
                        <?= htmlspecialchars($_SESSION['sucesso']) ?>
                    </span>

                    <?php if($contratoGeradoUrl): ?>
                        <a href="<?= htmlspecialchars($contratoGeradoUrl) ?>" class="btn btn-success btn-sm" download>
                            Baixar contrato
                        </a>
                    <?php endif; ?>
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
                                                        <button
                                                            type="button"
                                                            class="btn btn-success btn-sm botao-emitir-contrato"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modalEmitirContrato"
                                                            data-id-anuncio="<?= $anuncio['id_anuncio'] ?>"
                                                            data-titulo="<?= htmlspecialchars($anuncio['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-estadia-minima="<?= (int) $anuncio['estadia_minima_meses'] ?>"
                                                            data-logradouro="<?= htmlspecialchars($anuncio['logradouro'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-numero="<?= htmlspecialchars($anuncio['numero'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-complemento="<?= htmlspecialchars($anuncio['complemento'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                            data-bairro="<?= htmlspecialchars($anuncio['bairro'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-cidade="<?= htmlspecialchars($anuncio['cidade'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-estado="<?= htmlspecialchars($anuncio['estado'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-cep="<?= htmlspecialchars($anuncio['cep'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                        >
                                                            Emitir contrato
                                                        </button>

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


<div class="modal fade" id="modalEmitirContrato" tabindex="-1" aria-labelledby="tituloModalEmitirContrato" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form action="/UniHub/pages/contratos/gerar-contrato.php" method="POST" id="formEmitirContrato" class="form-emitir-contrato" novalidate>
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-5" id="tituloModalEmitirContrato">
                            Emitir contrato
                        </h2>

                        <p class="text-body-secondary small mb-0">
                            Preencha os dados necessários para gerar o contrato deste imóvel.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body modal-contrato-scroll">
                    <input type="hidden" name="id_anuncio" id="contrato_id_anuncio">

                    <div class="alert alert-info mb-4">
                        <div class="fw-semibold" id="contrato_titulo_anuncio">
                            Imóvel selecionado
                        </div>

                        <div class="small mt-1" id="contrato_endereco_imovel"></div>

                        <div class="small mt-1">
                            Estadia mínima:
                            <strong id="contrato_estadia_minima"></strong>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h3 class="h5 mb-1">
                            Endereço do locador
                        </h3>

                        <p class="text-body-secondary small mb-3">
                            Os demais dados do locador serão preenchidos com as informações da sua conta.
                        </p>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="locador_logradouro" class="form-label">
                                    Logradouro <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="locador_logradouro" name="locador_logradouro" required>
                                <div class="invalid-feedback">Informe o logradouro do locador.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="locador_numero" class="form-label">
                                    Número <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="locador_numero" name="locador_numero" required>
                                <div class="invalid-feedback">Informe o número ou S/N.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="locador_complemento" class="form-label">
                                    Complemento
                                </label>

                                <input type="text" class="form-control" id="locador_complemento" name="locador_complemento">
                            </div>

                            <div class="col-md-6">
                                <label for="locador_bairro" class="form-label">
                                    Bairro <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="locador_bairro" name="locador_bairro" required>
                                <div class="invalid-feedback">Informe o bairro.</div>
                            </div>

                            <div class="col-md-5">
                                <label for="locador_cidade" class="form-label">
                                    Cidade <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="locador_cidade" name="locador_cidade" required>
                                <div class="invalid-feedback">Informe a cidade.</div>
                            </div>

                            <div class="col-md-3">
                                <label for="locador_estado" class="form-label">
                                    Estado <span class="text-danger">*</span>
                                </label>

                                <select class="form-select" id="locador_estado" name="locador_estado" required>
                                    <option value="">Selecione</option>

                                    <?php foreach($estados as $uf): ?>
                                        <option value="<?= $uf ?>">
                                            <?= $uf ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <div class="invalid-feedback">Selecione o estado.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="locador_cep" class="form-label">
                                    CEP
                                </label>

                                <input type="text" class="form-control" id="locador_cep" name="locador_cep" maxlength="9" placeholder="00000-000">
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-4">
                        <h3 class="h5 mb-1">
                            Dados do inquilino / aluno
                        </h3>

                        <p class="text-body-secondary small mb-3">
                            O endereço do inquilino será preenchido automaticamente com o endereço do imóvel alugado.
                        </p>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="locatario_nome" class="form-label">
                                    Nome completo <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="locatario_nome" name="locatario_nome" required>
                                <div class="invalid-feedback">Informe o nome do inquilino.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="locatario_nacionalidade" class="form-label">
                                    Nacionalidade <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="locatario_nacionalidade" name="locatario_nacionalidade" value="Brasileiro(a)" required>
                                <div class="invalid-feedback">Informe a nacionalidade.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="locatario_estado_civil" class="form-label">
                                    Estado civil <span class="text-danger">*</span>
                                </label>

                                <select class="form-select" id="locatario_estado_civil" name="locatario_estado_civil" required>
                                    <option value="">Selecione</option>
                                    <option value="Solteiro(a)">Solteiro(a)</option>
                                    <option value="Casado(a)">Casado(a)</option>
                                    <option value="Divorciado(a)">Divorciado(a)</option>
                                    <option value="Viúvo(a)">Viúvo(a)</option>
                                    <option value="União estável">União estável</option>
                                    <option value="Outro">Outro</option>
                                </select>

                                <div class="invalid-feedback">Selecione o estado civil.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="locatario_profissao" class="form-label">
                                    Profissão <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="locatario_profissao" name="locatario_profissao" required>
                                <div class="invalid-feedback">Informe a profissão.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="locatario_cpf" class="form-label">
                                    CPF <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="locatario_cpf" name="locatario_cpf" maxlength="14" required>
                                <div class="invalid-feedback">Informe o CPF.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="locatario_rg" class="form-label">
                                    RG <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="locatario_rg" name="locatario_rg" required>
                                <div class="invalid-feedback">Informe o RG.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="locatario_telefone" class="form-label">
                                    Telefone <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="locatario_telefone" name="locatario_telefone" required>
                                <div class="invalid-feedback">Informe o telefone.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="locatario_email" class="form-label">
                                    E-mail <span class="text-danger">*</span>
                                </label>

                                <input type="email" class="form-control" id="locatario_email" name="locatario_email" required>
                                <div class="invalid-feedback">Informe um e-mail válido.</div>
                            </div>

                            <div class="col-md-8">
                                <label for="aluno_instituicao" class="form-label">
                                    Instituição de ensino <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="aluno_instituicao" name="aluno_instituicao" value="IFES Campus de Alegre" required>
                                <div class="invalid-feedback">Informe a instituição de ensino.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="aluno_matricula" class="form-label">
                                    Matrícula <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="aluno_matricula" name="aluno_matricula" required>
                                <div class="invalid-feedback">Informe a matrícula.</div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-4">
                        <h3 class="h5 mb-3">
                            Dados da locação
                        </h3>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="prazo_locacao_meses" class="form-label">
                                    Prazo da locação <span class="text-danger">*</span>
                                </label>

                                <div class="input-group">
                                    <input type="number" class="form-control" id="prazo_locacao_meses" name="prazo_locacao_meses" min="1" step="1" required>
                                    <span class="input-group-text">meses</span>
                                    <div class="invalid-feedback" id="erro_prazo_locacao">
                                        Informe um prazo válido.
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="data_inicio" class="form-label">
                                    Data de início <span class="text-danger">*</span>
                                </label>

                                <input type="date" class="form-control" id="data_inicio" name="data_inicio" required>
                                <div class="invalid-feedback">Informe a data de início.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="dia_vencimento" class="form-label">
                                    Dia do vencimento <span class="text-danger">*</span>
                                </label>

                                <input type="number" class="form-control" id="dia_vencimento" name="dia_vencimento" min="1" max="31" step="1" required>
                                <div class="invalid-feedback">Informe um dia entre 1 e 31.</div>
                            </div>

                            <div class="col-12">
                                <label for="forma_pagamento" class="form-label">
                                    Forma de pagamento <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="forma_pagamento" name="forma_pagamento" placeholder="Ex.: PIX, transferência bancária, dinheiro..." required>
                                <div class="invalid-feedback">Informe a forma de pagamento.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h3 class="h5 mb-3">
                            Dados do contrato
                        </h3>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="cidade_assinatura" class="form-label">
                                    Cidade da assinatura <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="cidade_assinatura" name="cidade_assinatura" required>
                                <div class="invalid-feedback">Informe a cidade da assinatura.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="data_assinatura" class="form-label">
                                    Data da assinatura <span class="text-danger">*</span>
                                </label>

                                <input type="date" class="form-control" id="data_assinatura" name="data_assinatura" value="<?= date('Y-m-d') ?>" required>
                                <div class="invalid-feedback">Informe a data da assinatura.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="numero_vias" class="form-label">
                                    Número de vias <span class="text-danger">*</span>
                                </label>

                                <input type="number" class="form-control" id="numero_vias" name="numero_vias" min="1" step="1" value="2" required>
                                <div class="invalid-feedback">Informe a quantidade de vias.</div>
                            </div>

                            <div class="col-md-8">
                                <label for="foro_comarca" class="form-label">
                                    Foro - comarca <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="foro_comarca" name="foro_comarca" required>
                                <div class="invalid-feedback">Informe a comarca.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="foro_estado" class="form-label">
                                    Foro - estado <span class="text-danger">*</span>
                                </label>

                                <select class="form-select" id="foro_estado" name="foro_estado" required>
                                    <option value="">Selecione</option>

                                    <?php foreach($estados as $uf): ?>
                                        <option value="<?= $uf ?>">
                                            <?= $uf ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <div class="invalid-feedback">Selecione o estado do foro.</div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div>
                        <h3 class="h5 mb-1">
                            Testemunhas
                        </h3>

                        <p class="text-body-secondary small mb-3">
                            Preencha os dados das testemunhas que constarão no contrato.
                        </p>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h4 class="h6 mb-3">
                                        1ª Testemunha
                                    </h4>

                                    <div class="mb-3">
                                        <label for="testemunha1_nome" class="form-label">
                                            Nome completo <span class="text-danger">*</span>
                                        </label>

                                        <input type="text" class="form-control" id="testemunha1_nome" name="testemunha1_nome" required>
                                        <div class="invalid-feedback">Informe o nome da 1ª testemunha.</div>
                                    </div>

                                    <div>
                                        <label for="testemunha1_cpf" class="form-label">
                                            CPF <span class="text-danger">*</span>
                                        </label>

                                        <input type="text" class="form-control" id="testemunha1_cpf" name="testemunha1_cpf" maxlength="14" required>
                                        <div class="invalid-feedback">Informe o CPF da 1ª testemunha.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h4 class="h6 mb-3">
                                        2ª Testemunha
                                    </h4>

                                    <div class="mb-3">
                                        <label for="testemunha2_nome" class="form-label">
                                            Nome completo <span class="text-danger">*</span>
                                        </label>

                                        <input type="text" class="form-control" id="testemunha2_nome" name="testemunha2_nome" required>
                                        <div class="invalid-feedback">Informe o nome da 2ª testemunha.</div>
                                    </div>

                                    <div>
                                        <label for="testemunha2_cpf" class="form-label">
                                            CPF <span class="text-danger">*</span>
                                        </label>

                                        <input type="text" class="form-control" id="testemunha2_cpf" name="testemunha2_cpf" maxlength="14" required>
                                        <div class="invalid-feedback">Informe o CPF da 2ª testemunha.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-success">
                        Gerar contrato
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const modalEmitirContrato = document.getElementById('modalEmitirContrato');
    const formEmitirContrato = document.getElementById('formEmitirContrato');
    const idAnuncioContrato = document.getElementById('contrato_id_anuncio');
    const tituloAnuncioContrato = document.getElementById('contrato_titulo_anuncio');
    const enderecoImovelContrato = document.getElementById('contrato_endereco_imovel');
    const estadiaMinimaContrato = document.getElementById('contrato_estadia_minima');
    const prazoLocacao = document.getElementById('prazo_locacao_meses');
    const erroPrazoLocacao = document.getElementById('erro_prazo_locacao');
    const cidadeAssinatura = document.getElementById('cidade_assinatura');
    const foroComarca = document.getElementById('foro_comarca');
    const foroEstado = document.getElementById('foro_estado');

    let estadiaMinimaAtual = 1;

    function montarEnderecoImovel(botao){
        const logradouro = botao.dataset.logradouro || '';
        const numero = botao.dataset.numero || '';
        const complemento = botao.dataset.complemento || '';
        const bairro = botao.dataset.bairro || '';
        const cidade = botao.dataset.cidade || '';
        const estado = botao.dataset.estado || '';
        const cep = botao.dataset.cep || '';

        const partes = [];

        if(logradouro || numero){
            partes.push(
                [logradouro, numero ? 'nº ' + numero : '']
                    .filter(Boolean)
                    .join(', ')
            );
        }

        if(complemento){
            partes.push(complemento);
        }

        if(bairro){
            partes.push(bairro);
        }

        if(cidade || estado){
            partes.push(
                [cidade, estado]
                    .filter(Boolean)
                    .join('/')
            );
        }

        if(cep){
            partes.push('CEP ' + cep);
        }

        return partes.join(' - ');
    }

    function validarPrazoLocacao(){
        const valor = parseInt(prazoLocacao.value, 10);

        if(Number.isNaN(valor) || valor < estadiaMinimaAtual){
            prazoLocacao.setCustomValidity(
                'O prazo não pode ser inferior à estadia mínima do imóvel.'
            );

            erroPrazoLocacao.textContent =
                'O prazo do contrato não pode ser inferior à estadia mínima de ' +
                estadiaMinimaAtual +
                (estadiaMinimaAtual === 1 ? ' mês.' : ' meses.');

            return false;
        }

        prazoLocacao.setCustomValidity('');
        erroPrazoLocacao.textContent = 'Informe um prazo válido.';

        return true;
    }

    modalEmitirContrato.addEventListener('show.bs.modal', function(evento){
        const botao = evento.relatedTarget;

        if(!botao){
            return;
        }

        formEmitirContrato.reset();
        formEmitirContrato.classList.remove('was-validated');

        estadiaMinimaAtual = parseInt(botao.dataset.estadiaMinima, 10) || 1;

        idAnuncioContrato.value = botao.dataset.idAnuncio || '';
        tituloAnuncioContrato.textContent = botao.dataset.titulo || 'Imóvel selecionado';
        enderecoImovelContrato.textContent = montarEnderecoImovel(botao);

        estadiaMinimaContrato.textContent =
            estadiaMinimaAtual +
            (estadiaMinimaAtual === 1 ? ' mês' : ' meses');

        prazoLocacao.min = estadiaMinimaAtual;
        prazoLocacao.value = estadiaMinimaAtual;
        prazoLocacao.setCustomValidity('');

        cidadeAssinatura.value = botao.dataset.cidade || '';
        foroComarca.value = botao.dataset.cidade || '';
        foroEstado.value = botao.dataset.estado || '';
    });

    prazoLocacao.addEventListener('input', validarPrazoLocacao);

    formEmitirContrato.addEventListener('submit', function(evento){
        validarPrazoLocacao();

        if(!formEmitirContrato.checkValidity()){
            evento.preventDefault();
            evento.stopPropagation();
        }

        formEmitirContrato.classList.add('was-validated');
    });
</script>

<?php if($contratoGeradoUrl): ?>
<script>
    window.addEventListener('load', function(){
        const link = document.createElement('a');

        link.href = <?= json_encode($contratoGeradoUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        link.download = '';
        link.style.display = 'none';

        document.body.appendChild(link);
        link.click();
        link.remove();
    });
</script>
<?php endif; ?>

<?php
    $conteudo = ob_get_clean();
    require __DIR__ . '/../../layouts/master.php';
?>