<?php
/**
 * Proxy de retrocompatibilidade para o módulo administrativo renomeado.
 * Módulo oficial: modules/gestao_acessos_portal/index.php
 * Compatibilidade: exigirAcesso('portal_clientes');
 */
require_once __DIR__ . '/../gestao_acessos_portal/index.php';
