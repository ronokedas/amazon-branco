<?php

/** Regras compartilhadas do Financeiro multi-escritorio. */

const ESCRITORIO_MATRIZ_ID = '00000000-0000-4000-8000-000000000100';

function financeiroEhAdmin(): bool {
    return getCargo() === 'ADMIN';
}

function financeiroEscritorios(PDO $pdo, bool $somenteAtivos = true): array {
    $sql = 'SELECT id, nome, cidade, uf, ativo FROM escritorios';
    if ($somenteAtivos) $sql .= ' WHERE ativo = 1';
    $sql .= ' ORDER BY nome, cidade';
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function financeiroEscritoriosUsuario(PDO $pdo, ?string $usuarioId = null, bool $somenteAtivos = true): array {
    $usuarioId = $usuarioId ?: ($_SESSION['usuario_id'] ?? null);
    if (!$usuarioId) return [];
    $sql = 'SELECT e.id, e.nome, e.cidade, e.uf, e.ativo, ue.principal
            FROM usuario_escritorios ue
            JOIN escritorios e ON e.id = ue.escritorio_id
            WHERE ue.usuario_id = :usuario';
    if ($somenteAtivos) $sql .= ' AND e.ativo = 1';
    $sql .= ' ORDER BY ue.principal DESC, e.nome, e.cidade';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':usuario' => $usuarioId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function financeiroEscritoriosPermitidos(PDO $pdo, ?string $usuarioId = null): array {
    return financeiroEhAdmin() && !$usuarioId
        ? financeiroEscritorios($pdo)
        : financeiroEscritoriosUsuario($pdo, $usuarioId);
}

function financeiroEscritorioUsuario(PDO $pdo, ?string $usuarioId = null): ?string {
    $usuarioId = $usuarioId ?: ($_SESSION['usuario_id'] ?? null);
    if (!$usuarioId) return null;
    $stmt = $pdo->prepare('SELECT ue.escritorio_id
                           FROM usuario_escritorios ue
                           JOIN usuarios u ON u.id=ue.usuario_id AND u.excluido_em IS NULL
                           WHERE ue.usuario_id=:id
                           ORDER BY ue.principal DESC, ue.criado_em
                           LIMIT 1');
    $stmt->execute([':id' => $usuarioId]);
    $id = $stmt->fetchColumn();
    if ($id === false) {
        $stmt = $pdo->prepare('SELECT escritorio_id FROM usuarios WHERE id = :id AND excluido_em IS NULL LIMIT 1');
        $stmt->execute([':id' => $usuarioId]);
        $id = $stmt->fetchColumn();
    }
    return $id !== false && $id !== null ? (string)$id : null;
}

/** Retorna "todos" apenas para ADMIN; demais usuarios ficam limitados aos seus vinculos. */
function financeiroResolverEscritorio(PDO $pdo, ?string $solicitado = null): string {
    if (!financeiroEhAdmin()) {
        $permitidos = financeiroEscritoriosUsuario($pdo);
        if (!$permitidos) throw new RuntimeException('Seu usuario nao esta vinculado a um escritorio ativo.');
        $ids = array_column($permitidos, 'id');
        $solicitado = trim((string)$solicitado);
        if ($solicitado !== '' && $solicitado !== 'todos' && in_array($solicitado, $ids, true)) return $solicitado;
        $principal = financeiroEscritorioUsuario($pdo);
        return $principal && in_array($principal, $ids, true) ? $principal : (string)$ids[0];
    }
    $solicitado = trim((string)$solicitado);
    if ($solicitado === '' || $solicitado === 'todos') return 'todos';
    $stmt = $pdo->prepare('SELECT id FROM escritorios WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $solicitado]);
    if (!$stmt->fetchColumn()) throw new RuntimeException('Escritorio invalido.');
    return $solicitado;
}

function financeiroPodeAcessarEscritorio(PDO $pdo, string $escritorioId): bool {
    if (financeiroEhAdmin()) return true;
    return in_array($escritorioId, array_column(financeiroEscritoriosUsuario($pdo), 'id'), true);
}

function financeiroExigirAcessoLancamento(PDO $pdo, string $lancamentoId): array {
    $stmt = $pdo->prepare('SELECT * FROM financeiro_lancamentos WHERE id = :id AND ativo = 1 LIMIT 1');
    $stmt->execute([':id' => $lancamentoId]);
    $lancamento = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lancamento || !financeiroPodeAcessarEscritorio($pdo, (string)$lancamento['escritorio_id'])) {
        throw new RuntimeException('Lancamento nao encontrado ou sem permissao para este escritorio.');
    }
    return $lancamento;
}

function financeiroVendedores(PDO $pdo, string $escritorioId): array {
    $stmt = $pdo->prepare("SELECT DISTINCT u.id, u.nome
                           FROM usuarios u
                           JOIN usuario_escritorios ue ON ue.usuario_id=u.id
                           WHERE u.cargo='VENDEDOR' AND u.ativo=1 AND u.excluido_em IS NULL
                             AND ue.escritorio_id=:escritorio
                           ORDER BY u.nome");
    $stmt->execute([':escritorio' => $escritorioId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function financeiroSalvarVinculosUsuario(PDO $pdo, string $usuarioId, array $escritoriosIds, string $principalId): void {
    $escritoriosIds = array_values(array_unique(array_filter(array_map('strval', $escritoriosIds))));
    if (!$escritoriosIds || !in_array($principalId, $escritoriosIds, true)) {
        throw new RuntimeException('Selecione ao menos um escritorio e defina o escritorio principal.');
    }
    $placeholders = implode(',', array_fill(0, count($escritoriosIds), '?'));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM escritorios WHERE ativo=1 AND id IN ({$placeholders})");
    $stmt->execute($escritoriosIds);
    if ((int)$stmt->fetchColumn() !== count($escritoriosIds)) {
        throw new RuntimeException('Um dos escritorios selecionados e invalido ou esta inativo.');
    }
    $pdo->prepare('DELETE FROM usuario_escritorios WHERE usuario_id=:usuario')->execute([':usuario'=>$usuarioId]);
    $stmt = $pdo->prepare('INSERT INTO usuario_escritorios (usuario_id,escritorio_id,principal) VALUES (:usuario,:escritorio,:principal)');
    foreach ($escritoriosIds as $escritorioId) {
        $stmt->execute([':usuario'=>$usuarioId, ':escritorio'=>$escritorioId, ':principal'=>$escritorioId === $principalId ? 1 : 0]);
    }
    $pdo->prepare('UPDATE usuarios SET escritorio_id=:escritorio WHERE id=:usuario')->execute([':escritorio'=>$principalId, ':usuario'=>$usuarioId]);
}

function financeiroResponsavelVenda(PDO $pdo, ?string $usuarioId): ?string {
    if (!$usuarioId) return null;
    $stmt=$pdo->prepare("SELECT id FROM usuarios WHERE id=:id AND cargo='VENDEDOR' LIMIT 1");
    $stmt->execute([':id'=>$usuarioId]);
    $id=$stmt->fetchColumn();
    return $id ? (string)$id : null;
}

function financeiroCompetencia(string $valor): string {
    if (!preg_match('/^\d{4}-\d{2}$/', $valor)) return date('Y-m-01');
    $data = DateTime::createFromFormat('!Y-m-d', $valor . '-01');
    return $data && $data->format('Y-m') === $valor ? $data->format('Y-m-01') : date('Y-m-01');
}

/** Converte valores como 200.000,00 para o decimal armazenado no banco. */
function financeiroNormalizarMoedaBr(string $valor): float {
    $valor = trim($valor);
    if ($valor === '') return 0.0;

    $normalizado = preg_replace('/[^0-9,.-]/u', '', $valor) ?? '';
    if (str_contains($normalizado, ',')) {
        $normalizado = str_replace('.', '', $normalizado);
        $normalizado = str_replace(',', '.', $normalizado);
    } elseif (substr_count($normalizado, '.') > 1 || preg_match('/^\d{1,3}(?:\.\d{3})+$/', $normalizado)) {
        $normalizado = str_replace('.', '', $normalizado);
    }

    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $normalizado)) {
        throw new RuntimeException('Informe a meta no formato 200.000,00.');
    }

    $numero = (float)$normalizado;
    if (!is_finite($numero) || $numero < 0) {
        throw new RuntimeException('A meta do escritório deve ser um valor positivo.');
    }
    return $numero;
}

function financeiroUrl(array $parametros = []): string {
    $query = http_build_query(array_filter($parametros, static fn($v) => $v !== null && $v !== ''));
    return APP_URL . 'financeiro' . ($query ? '?' . $query : '');
}
