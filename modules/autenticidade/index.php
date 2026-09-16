<?php
/**
 * ERP SISTEMA DE GESTÃO NAVAL
 * Módulo de Autenticidade: Página Principal / Consulta de Autenticidade
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();

$titulo = 'Autenticidade e Validação Documental - Amazon Naval';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-shield-alt text-success"></i> Autenticidade e Validação Documental</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= APP_URL ?>dashboard">Início</a></li>
                        <li class="breadcrumb-item active">Autenticidade</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-qrcode mr-1"></i> Validador de Certificados e Assinaturas</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Este módulo centraliza a autenticidade jurídica, criptográfica (SHA-256) e validação pública de todos os documentos navais, certificados técnicos e assinaturas digitais emitidos pela Amazon Naval.
                    </p>
                    <form method="get" action="<?= APP_URL ?>validar/" onsubmit="if(this.token.value.trim()!==''){window.location.href='<?= APP_URL ?>validar/'+encodeURIComponent(this.token.value.trim()); return false;}">
                        <div class="form-group">
                            <label for="token">Consultar Documento por Hash ou Token de Validação:</label>
                            <div class="input-group">
                                <input type="text" name="token" id="token" class="form-control" placeholder="Cole o token SHA-256 de 64 caracteres presente no QR Code do documento..." required minlength="64" maxlength="64">
                                <span class="input-group-append">
                                    <button type="submit" class="btn btn-success"><i class="fas fa-search mr-1"></i> Validar Documento</button>
                                </span>
                            </div>
                            <small class="text-muted">A validação de autenticidade documental confere a integridade matemática do PDF original e a conformidade com as normas da Marinha do Brasil (DPC/NORMAM).</small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
