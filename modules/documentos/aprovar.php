<?php
/**
 * ERP SISTEMA DE GESTÃO NAVAL
 * Proxy de Compatibilidade Retroativa para modules/autenticidade/aprovar.php
 * Restrições e verificações de segurança:
 * - getCargo() !== 'ADMIN'
 * - strtoupper(trim((string)($_POST['documento_tipo'] ?? ''))) === 'RELATORIO'
 */

require_once __DIR__ . '/../autenticidade/aprovar.php';
