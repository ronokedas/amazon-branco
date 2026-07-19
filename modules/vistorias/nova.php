<?php
/** Vistorias são iniciadas exclusivamente por um agendamento. */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
setMensagem('info', 'Para iniciar uma vistoria, abra o relatório do agendamento atribuído.');
redirecionar(APP_URL . 'agendamentos');
