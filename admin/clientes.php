<?php
// =====================================================
// admin/clientes.php  —  Lote G
// MINHAS CLIENTES: nome, telefone, histórico de
// agendamentos e recadinhos de cada cliente.
// =====================================================

session_start();

// ---- Guarda: sem login, volta para a porta ----
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/conexao.php';

$tituloPagina = 'Minhas Clientes | Studio Bem Garota';

// ---------- Lista todas as clientes (mais recentes primeiro) ----------
$clientes = [];
$consulta = mysqli_prepare(
    $conexao,
    'SELECT id, nome, whatsapp, criado_em FROM clientes ORDER BY nome'
);
mysqli_stmt_execute($consulta);
$resultado = mysqli_stmt_get_result($consulta);
while ($linha = mysqli_fetch_assoc($resultado)) {
    $linha['total'] = 0;
    $linha['futuros'] = 0;
    $clientes[$linha['id']] = $linha;
}
mysqli_stmt_close($consulta);

// ---------- Conta agendamentos ativos de cada cliente ----------
$consulta = mysqli_prepare(
    $conexao,
    "SELECT cliente_id, COUNT(*) AS total,
            SUM(CASE WHEN `data` >= CURDATE() AND status IN ('pendente','confirmado') THEN 1 ELSE 0 END) AS futuros
       FROM agendamentos
      WHERE status IN ('pendente','confirmado')
      GROUP BY cliente_id"
);
mysqli_stmt_execute($consulta);
$resultado = mysqli_stmt_get_result($consulta);
while ($linha = mysqli_fetch_assoc($resultado)) {
    if (isset($clientes[$linha['cliente_id']])) {
        $clientes[$linha['cliente_id']]['total'] = (int) $linha['total'];
        $clientes[$linha['cliente_id']]['futuros'] = (int) $linha['futuros'];
    }
}
mysqli_stmt_close($consulta);

// ---------- Conta recadinhos não lidos de cada cliente ----------
$consulta = mysqli_prepare(
    $conexao,
    "SELECT cliente_id, COUNT(*) AS nao_lidas
       FROM recadinhos
      WHERE lida = 'nao'
      GROUP BY cliente_id"
);
mysqli_stmt_execute($consulta);
$resultado = mysqli_stmt_get_result($consulta);
while ($linha = mysqli_fetch_assoc($resultado)) {
    if (isset($clientes[$linha['cliente_id']])) {
        $clientes[$linha['cliente_id']]['nao_lidas'] = (int) $linha['nao_lidas'];
    }
}
mysqli_stmt_close($consulta);

include '../includes/cabecalho.php';
?>

<h2>Minhas Clientes</h2>

<nav class="admin-menu">
    <a href="agenda.php">Minha Agenda</a>
    <a href="bloquear.php">Bloquear Horário</a>
    <a class="atual" href="clientes.php">Minhas Clientes</a>
    <a href="pendentes.php">Pedidos Pendentes</a>
    <a href="recadinhos.php">Recadinhos</a>
    <a href="login.php?sair=1">Sair</a>
</nav>

<?php if (empty($clientes)) { ?>
    <div class="mensagem mensagem-aviso">
        Nenhuma cliente cadastrada ainda. Quando alguém preencher o
        formulário do site, ela aparece aqui automaticamente.
    </div>
<?php } ?>

<?php foreach ($clientes as $cliente) { ?>
    <?php
        // Histórico: agendamentos desta cliente (novos primeiro)
        $historico = [];
        $consulta = mysqli_prepare(
            $conexao,
            "SELECT `data`, `hora`, status, valor_restante
               FROM agendamentos
              WHERE cliente_id = ?
              ORDER BY `data` DESC, `hora` DESC
              LIMIT 10"
        );
        mysqli_stmt_bind_param($consulta, 'i', $cliente['id']);
        mysqli_stmt_execute($consulta);
        $r2 = mysqli_stmt_get_result($consulta);
        while ($h = mysqli_fetch_assoc($r2)) { $historico[] = $h; }
        mysqli_stmt_close($consulta);

        // Recadinhos desta cliente (novos primeiro)
        $recadinhos = [];
        $consulta = mysqli_prepare(
            $conexao,
            "SELECT id, mensagem, lida, criado_em
               FROM recadinhos
              WHERE cliente_id = ?
              ORDER BY criado_em DESC"
        );
        mysqli_stmt_bind_param($consulta, 'i', $cliente['id']);
        mysqli_stmt_execute($consulta);
        $r2 = mysqli_stmt_get_result($consulta);
        while ($rec = mysqli_fetch_assoc($r2)) { $recadinhos[] = $rec; }
        mysqli_stmt_close($consulta);

        // Data de cadastro no formato brasileiro
        $desde = (new DateTime($cliente['criado_em']))->format('d/m/Y');
    ?>

    <section class="cliente-card">
        <h3>
            <?php echo htmlspecialchars($cliente['nome']); ?>
            <?php if (($cliente['nao_lidas'] ?? 0) > 0) { ?>
                <span class="badge nao-lida"><?php echo $cliente['nao_lidas']; ?> recadinho(s) novo(s)</span>
            <?php } ?>
        </h3>
        <p>
            <strong>WhatsApp:</strong> <?php echo htmlspecialchars($cliente['whatsapp']); ?><br>
            <strong>Cliente desde:</strong> <?php echo $desde; ?><br>
            <strong>Próximos horários:</strong> <?php echo $cliente['futuros']; ?>
        </p>

        <?php if (!empty($historico)) { ?>
            <div class="tabela-rolagem">
                <table class="tabela">
                    <tr><th>Data</th><th>Hora</th><th>Situação</th><th>Falta receber</th></tr>
                    <?php foreach ($historico as $h) { ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($h['data'])); ?></td>
                            <td><?php echo substr($h['hora'], 0, 5); ?></td>
                            <td>
                                <?php if ($h['status'] === 'confirmado') { ?>
                                    <span class="badge ocupado">Confirmado</span>
                                <?php } elseif ($h['status'] === 'pendente') { ?>
                                    <span class="badge aguardando">Aguardando Pix</span>
                                <?php } elseif ($h['status'] === 'expirado') { ?>
                                    <span class="badge fechado">Expirado (não pagou o sinal)</span>
                                <?php } else { ?>
                                    <span class="badge fechado">Cancelado</span>
                                <?php } ?>
                            </td>
                            <td>R$ <?php echo number_format($h['valor_restante'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        <?php } else { ?>
            <p><em>Ainda não agendou nenhum horário.</em></p>
        <?php } ?>

        <?php if (!empty($recadinhos)) { ?>
            <h4>Recadinhos</h4>
            <?php foreach ($recadinhos as $rec) { ?>
                <div class="recadinho-item <?php echo $rec['lida'] === 'nao' ? 'nao-lida' : ''; ?>">
                    <p>
                        <?php echo htmlspecialchars($rec['mensagem']); ?>
                    </p>
                    <small>
                        <?php echo (new DateTime($rec['criado_em']))->format('d/m/Y \à\s H:i'); ?>
                        <?php if ($rec['lida'] === 'nao') { ?> — não lido<?php } ?>
                    </small>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p><em>Sem recadinhos.</em></p>
        <?php } ?>
    </section>
<?php } ?>

<?php include '../includes/rodape.php'; ?>
