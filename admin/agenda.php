<?php
// =====================================================
// admin/agenda.php  —  Lote E
// MINHA AGENDA: os próximos 14 dias com horários
// confirmados (vermelho), aguardando Pix (amarelo)
// e bloqueados (cinza). Feita para caber no celular.
// =====================================================

session_start();

// ---- Guarda: sem login, volta para a porta ----
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/conexao.php';

$tituloPagina = 'Minha Agenda | Studio Bem Garota';

// ---- Faxina: reservas vencidas liberam o horário ----
mysqli_query(
    $conexao,
    "UPDATE agendamentos SET status = 'expirado'
      WHERE status = 'pendente' AND expira_em < NOW()"
);

// ---- Janela de dias: hoje + 13 ----
$hoje = new DateTime('today');
$de = $hoje->format('Y-m-d');
$ate = (clone $hoje)->modify('+13 days')->format('Y-m-d');

// ---- Busca os agendamentos ativos do período ----
$agendaDias = [];
$nConfirmados = 0;
$nPendentes = 0;

$consulta = mysqli_prepare(
    $conexao,
    "SELECT a.`data`, a.`hora`, a.status, a.valor_restante, a.expira_em,
            c.nome, c.whatsapp
       FROM agendamentos a
       INNER JOIN clientes c ON c.id = a.cliente_id
      WHERE a.`data` BETWEEN ? AND ?
        AND a.status IN ('confirmado', 'pendente')
      ORDER BY a.`data`, a.`hora`"
);
mysqli_stmt_bind_param($consulta, 'ss', $de, $ate);
mysqli_stmt_execute($consulta);
$resultado = mysqli_stmt_get_result($consulta);

while ($linha = mysqli_fetch_assoc($resultado)) {
    $agendaDias[$linha['data']][] = [
        'tipo'     => 'agendamento',
        'hora'     => substr($linha['hora'], 0, 5),
        'status'   => $linha['status'],
        'nome'     => $linha['nome'],
        'whatsapp' => $linha['whatsapp'],
        'restante' => $linha['valor_restante'],
        'expira'   => $linha['expira_em'],
    ];
    if ($linha['status'] === 'confirmado') { $nConfirmados++; }
    else { $nPendentes++; }
}
mysqli_stmt_close($consulta);

// ---- Busca os bloqueios do período ----
$consulta = mysqli_prepare(
    $conexao,
    "SELECT `data`, `hora`, motivo FROM bloqueios
      WHERE `data` BETWEEN ? AND ? ORDER BY `data`, `hora`"
);
mysqli_stmt_bind_param($consulta, 'ss', $de, $ate);
mysqli_stmt_execute($consulta);
$resultado = mysqli_stmt_get_result($consulta);

while ($linha = mysqli_fetch_assoc($resultado)) {
    $agendaDias[$linha['data']][] = [
        'tipo'  => 'bloqueio',
        'hora'  => substr($linha['hora'], 0, 5),
        'motivo' => $linha['motivo'],
    ];
}
mysqli_stmt_close($consulta);

// ---- Organiza os dias em ordem ----
ksort($agendaDias);

$diasSemana = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];

include '../includes/cabecalho.php';
?>

<h2>Minha Agenda</h2>

<!-- Menu do painel (as outras seções nascem nos próximos lotes) -->
<nav class="admin-menu">
    <a class="atual" href="agenda.php">Minha Agenda</a>
    <a href="bloquear.php">Bloquear Horário</a>
    <a href="clientes.php">Minhas Clientes</a>
    <a href="pendentes.php">Pedidos Pendentes</a>
    <a href="recadinhos.php">Recadinhos</a>
    <a href="login.php?sair=1">Sair</a>
</nav>

<!-- Resumo rapidinho -->
<div class="mensagem mensagem-sucesso">
    <?php echo $nConfirmados; ?> confirmado(s) e
    <?php echo $nPendentes; ?> aguardando Pix nos próximos 14 dias.
</div>

<?php if (empty($agendaDias)) { ?>
    <div class="mensagem mensagem-aviso">
        Nenhum horário marcado ainda. Quando uma cliente agendar,
        ela aparece aqui automaticamente.
    </div>
<?php } ?>

<?php foreach ($agendaDias as $data => $itens) { ?>
    <?php
        $d = new DateTime($data);
        $tituloDia = $diasSemana[(int)$d->format('w')] . ', ' . $d->format('d/m/Y');
        if ($data === $de) { $tituloDia .= ' — HOJE'; }
    ?>

    <h3><?php echo $tituloDia; ?></h3>

    <div class="tabela-rolagem">
        <table class="tabela">
            <tr>
                <th>Hora</th><th>Cliente</th><th>Situação</th><th>Valores</th>
            </tr>

            <?php foreach ($itens as $item) { ?>
                <?php if ($item['tipo'] === 'bloqueio') { ?>
                    <tr>
                        <td><?php echo $item['hora']; ?></td>
                        <td><em>Bloqueado por você</em></td>
                        <td><span class="badge fechado">Fechado</span></td>
                        <td>
                            <?php echo htmlspecialchars($item['motivo'] ?? ''); ?>
                        </td>
                    </tr>
                <?php } elseif ($item['status'] === 'confirmado') { ?>
                    <tr>
                        <td><?php echo $item['hora']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($item['nome']); ?><br>
                            <small><?php echo htmlspecialchars($item['whatsapp']); ?></small>
                        </td>
                        <td><span class="badge ocupado">Confirmado</span></td>
                        <td>Falta receber:
                            R$ <?php echo number_format($item['restante'], 2, ',', '.'); ?>
                        </td>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <td><?php echo $item['hora']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($item['nome']); ?><br>
                            <small><?php echo htmlspecialchars($item['whatsapp']); ?></small>
                        </td>
                        <td><span class="badge aguardando">Aguardando Pix</span></td>
                        <td>
                            Sinal R$ 20,00<br>
                            <small>expira <?php echo date('H:i', strtotime($item['expira'])); ?></small>
                        </td>
                    </tr>
                <?php } ?>
            <?php } ?>
        </table>
    </div>
<?php } ?>

<?php include '../includes/rodape.php'; ?>
