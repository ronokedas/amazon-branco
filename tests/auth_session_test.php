<?php

require_once __DIR__ . '/../includes/auth.php';

final class FailingSessionDatabase
{
    public function prepare(string $sql): never
    {
        throw new RuntimeException('falha temporaria simulada');
    }
}

$_SESSION = [
    'usuario_logado' => true,
    'usuario_id' => 'usuario-de-teste',
    'login_time' => 1,
];
$pdo = new FailingSessionDatabase();

verificarSessao();

if (!estaLogado() || $_SESSION['usuario_id'] !== 'usuario-de-teste') {
    throw new RuntimeException('Uma falha temporaria do banco encerrou a sessao valida.');
}

if (($_SESSION['login_time'] ?? 0) <= 1) {
    throw new RuntimeException('A atividade da sessao nao foi renovada.');
}

echo "OK: falha temporaria do banco nao encerra uma sessao valida.\n";
