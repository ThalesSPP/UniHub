CREATE DATABASE IF NOT EXISTS UniHub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE UniHub;

CREATE TABLE perfil (
    id_perfil INT UNSIGNED AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT pk_perfil PRIMARY KEY (id_perfil),
    CONSTRAINT uq_perfil_nome UNIQUE (nome)
);

CREATE TABLE usuario (
    id_usuario INT UNSIGNED AUTO_INCREMENT,
    id_perfil INT UNSIGNED NOT NULL,

    nome VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20) NOT NULL,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_usuario PRIMARY KEY (id_usuario),
    CONSTRAINT uq_usuario_email UNIQUE (email),
    CONSTRAINT fk_usuario_perfil FOREIGN KEY (id_perfil) REFERENCES perfil(id_perfil)
);

CREATE TABLE tipo_anuncio (
    id_tipo INT UNSIGNED AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT pk_tipo_anuncio PRIMARY KEY (id_tipo),
    CONSTRAINT uq_tipo_anuncio_nome UNIQUE (nome)
);

CREATE TABLE endereco (
    id_endereco INT UNSIGNED AUTO_INCREMENT,

    logradouro VARCHAR(100) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    complemento VARCHAR(100) NULL,
    bairro VARCHAR(50) NOT NULL,
    cidade VARCHAR(50) NOT NULL,
    estado CHAR(2) NOT NULL DEFAULT 'ES',
    CEP VARCHAR(10) NULL,

    CONSTRAINT pk_endereco PRIMARY KEY (id_endereco)
);

CREATE TABLE anuncio(
    id_anuncio INT UNSIGNED NOT NULL AUTO_INCREMENT,

    id_usuario INT UNSIGNED NOT NULL,
    id_tipo INT UNSIGNED NOT NULL,
    id_endereco INT UNSIGNED NOT NULL,

    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,

    valor DECIMAL(10,2) NOT NULL,

    status ENUM(
        'ATIVO',
        'INATIVO',
        'REMOVIDO'
    ) NOT NULL DEFAULT 'ATIVO',

    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_anuncio PRIMARY KEY (id_anuncio),
    CONSTRAINT fk_anuncio_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
    CONSTRAINT fk_anuncio_tipo FOREIGN KEY (id_tipo) REFERENCES tipo_anuncio(id_tipo),
    CONSTRAINT fk_anuncio_endereco FOREIGN KEY (id_endereco) REFERENCES endereco(id_endereco)
);

CREATE TABLE imagem_anuncio (
    id_imagem INT UNSIGNED AUTO_INCREMENT,
    id_anuncio INT UNSIGNED NOT NULL,
    
    caminho_arquivo VARCHAR(255) NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 1,
    principal BOOLEAN NOT NULL DEFAULT FALSE,

    CONSTRAINT pk_imagem_anuncio PRIMARY KEY (id_imagem),
    CONSTRAINT fk_imagem_anuncio FOREIGN KEY (id_anuncio) REFERENCES anuncio(id_anuncio) ON DELETE CASCADE
);

CREATE TABLE modelo_contrato (
    id_modelo INT UNSIGNED AUTO_INCREMENT,

    nome VARCHAR(100) NOT NULL,
    arquivo VARCHAR(255) NOT NULL,
    versao VARCHAR(255) NOT NULL,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_modelo_contrato PRIMARY KEY (id_modelo)
);

CREATE TABLE configuracao_contrato (
    id_configuracao INT UNSIGNED AUTO_INCREMENT,

    id_anuncio INT UNSIGNED NOT NULL,
    id_modelo INT UNSIGNED NOT NULL,

    usar_contrato BOOLEAN NOT NULL DEFAULT FALSE,

    clausula_animais BOOLEAN NOT NULL DEFAULT FALSE,
    clausula_caucao BOOLEAN NOT NULL DEFAULT FALSE,
    clausula_agua_energia BOOLEAN NOT NULL DEFAULT FALSE,
    clausula_rescisao BOOLEAN NOT NULL DEFAULT FALSE,
    clausula_visitas BOOLEAN NOT NULL DEFAULT FALSE,
    clausula_manutencao BOOLEAN NOT NULL DEFAULT FALSE,
    clausula_multa BOOLEAN NOT NULL DEFAULT FALSE,

    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    data_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_configuracao_contrato PRIMARY KEY (id_configuracao),
    CONSTRAINT uq_configuracao_anuncio UNIQUE (id_anuncio),
    CONSTRAINT fk_configuracao_anuncio FOREIGN KEY (id_anuncio) REFERENCES anuncio(id_anuncio) ON DELETE CASCADE,
    CONSTRAINT fk_configuracao_modelo FOREIGN KEY (id_modelo) REFERENCES modelo_contrato(id_modelo)
);

CREATE TABLE clausula_extra (
    id_clausula_extra INT UNSIGNED AUTO_INCREMENT,
    id_configuracao INT UNSIGNED NOT NULL,

    texto TEXT NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 1,

    CONSTRAINT pk_clausula_extra PRIMARY KEY (id_clausula_extra),
    CONSTRAINT fk_clausula_extra_configuracao FOREIGN KEY (id_configuracao) REFERENCES configuracao_contrato(id_configuracao) ON DELETE CASCADE
);

CREATE TABLE contrato_gerado (
    id_contrato_gerado INT UNSIGNED AUTO_INCREMENT,
    id_configuracao INT UNSIGNED NOT NULL,

    arquivo VARCHAR(255) NOT NULL,

    formato ENUM(
        'DOCX',
        'PDF'
    ) NOT NULL,

    data_geracao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_contrato_gerado PRIMARY KEY (id_contrato_gerado),
    CONSTRAINT fk_contrato_gerado_configuracao FOREIGN KEY (id_configuracao) REFERENCES configuracao_contrato(id_configuracao) ON DELETE CASCADE
);

INSERT INTO tipo_anuncio (nome, ativo)
VALUES
    ('CASA', TRUE),
    ('APARTAMENTO', TRUE),
    ('KITNET', TRUE),
    ('QUARTO', TRUE),
    ('REPUBLICA', TRUE),
    ('OUTRO', TRUE);