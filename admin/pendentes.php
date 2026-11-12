<?php
// =====================================================
// admin/pendentes.php  —  Lote G + Lote H
// PEDIDOS PENDENTES: reservas aguardando o Pix.
//
// NO LOTE H cada pedido ganhou dois botões:
//   "Recebi o sinal" → confirma e pinta o horário de
//     vermelho (você vê o Pix cair no app do banco)
//   "Cancelar pedido" → libera o horário na hora, sem
//     esperar o prazo vencer (cliente desistiu)
//
// A confirmação é manual, igual à barbearia — simples.
// =====================================================

session_start();

// ---- Guarda: sem login, volta para a porta ----
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/conexao.php';

$tituloPagina = 'Pedidos Pendentes | Studio Bem Garota';

// ---- Faxina: vencidas viram 'expirado' e liberam o horário ----
mysqli_query(
    $conexao,
    "UPDATE agendamentos SET status = 'expirado'
      WHERE status = 'pendente' AND expira_em < NOW()"
);

$mensagem = '';   // mensagem de sucesso
$erro = '';       // mensagem de erro

// ---------- Recebeu um pedido? (confirmar ou cancelar) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0 && ($acao === 'confirmar' || $acao === 'cancelar')) {

        // ====== AÇÃO 1: "RECEBI O SINAL" ======
        if ($acao === 'confirmar') {

            // Caminho normal: o pedido ainda está pendente.
            // O "WHERE status = 'pendente'" é o cinto de segurança:
            // só confirma quem ainda está esperando mesmo.
            $comando = mysqli_prepare(
                $conexao,
                "UPDATE agendamentos
                    SET status = 'confirmado', pago_em = NOW()
                  WHERE id = ? AND status = 'pendente'"
            );
            mysqli_stmt_bind_param($comando, 'i', $id);
            mysqli_stmt_execute($comando);
            // LER o número de linhas ANTES de fechar o comando:
            // depois do mysqli_stmt_close, a conexão "esquece"
            // quantas linhas mudaram (devolve -1) e o if erraria.
            $afetados = mysqli_stmt_affected_rows($comando);
            mysqli_stmt_close($comando);

            if ($afetados > 0) {
                $mensagem = 'Sinal confirmado! O horário agora está garantido '
                          . '(vermelho no calendário das clientes).';

            } else {
                // Não achou pendente... mas você JÁ VIU o Pix cair.
                // Será que venceu faz pouquinho? Vamos resgatar:
                $consulta = mysqli_prepare(
                    $conexao,
                    "SELECT `data`, `hora`, status FROM agendamentos
                      WHERE id = ?"
                );
                mysqli_stmt_bind_param($consulta, 'i', $id);
                mysqli_stmt_execute($consulta);
                $pedido = mysqli_stmt_get_result($consulta)->fetch_assoc();
                mysqli_stmt_close($consulta);

                if ($pedido && $pedido['status'] === 'expirado') {

                    // Resgate só se o horário segue LIVRE
                    // (ninguém escolheu depois do vencimento)
                    $checagem = mysqli_prepare(
                        $conexao,
                        "SELECT id FROM agendamentos
                          WHERE `data` = ? AND `hora` = ?
                            AND status IN ('pendente','confirmado')
                            AND id <> ?"
                    );
                    mysqli_stmt_bind_param($checagem, 'ssi', $pedido['data'], $pedido['hora'], $id);
                    mysqli_stmt_execute($checagem);
                    mysqli_stmt_store_result($checagem);
                    $conflito = mysqli_stmt_num_rows($checagem) > 0;
                    mysqli_stmt_close($checagem);

                    if (!$conflito) {
                        $resgate = mysqli_prepare(
                            $conexao,
                            "UPDATE agendamentos
                                SET status = 'confirmado', pago_em = NOW()
                              WHERE id = ? AND status = 'expirado'"
                        );
                        mysqli_stmt_bind_param($resgate, 'i', $id);
                        mysqli_stmt_execute($resgate);
                        mysqli_stmt_close($resgate);

                        $mensagem = 'O sinal chegou um pouquinho atrasado, '
                                  . 'mas o horário seguia livre: pedido resgatado '
                                  . 'e confirmado!';
                    } else {
                        $erro = 'Este pedido venceu e OUTRA cliente já escolheu '
                              . 'este horário. Combine com as duas e devolva o '
                              . 'sinal pelo app do banco, se precisar.';
                    }

                } else {
                    $erro = 'Este pedido já não estava pendente (já foi tratado). '
                          . 'Dê uma olhada na Minha Agenda.';
                }
            }

        // ====== AÇÃO 2: "CANCELAR PEDIDO" ======
        } elseif ($acao === 'cancelar') {

            // Libera o horário na hora, sem esperar vencer
            $comando = mysqli_prepare(
                $conexao,
                "UPDATE agendamentos
                    SET status = 'cancelado'
                  WHERE id = ? AND status = 'pendente'"
            );
            mysqli_stmt_bind_param($comando, 'i', $id);
            mysqli_stmt_execute($comando);
            // LER o número de linhas ANTES de fechar o comando:
            // depois do mysqli_stmt_close, a conexão "esquece"
            // quantas linhas mudaram (devolve -1) e o if erraria.
            $afetados = mysqli_stmt_affected_rows($comando);
            mysqli_stmt_close($comando);

            if ($afetados > 0) {
                $mensagem = 'Pedido cancelado — o horário voltou a ficar livre '
                          . '(verde no calendário).';
            } else {
                $erro = 'Este pedido já não estava pendente.';
            }
        }
    }
}

// ---------- Busca os pedidos pendentes ----------
$pendentes = [];
$consulta = mysqli_prepare(
    $conexao,
    "SELECT a.id, a.`data`, a.`hora`, a.expira_em, a.valor_sinal,
            c.nome, c.whatsapp
       FROM agendamentos a
       INNER JOIN clientes c ON c.id = a.cliente_id
      WHERE a.status = 'pendente'
      ORDER BY a.expira_em"
);
mysqli_stmt_execute($consulta);
$resultado = mysqli_stmt_get_result($consulta);
while ($linha = mysqli_fetch_assoc($resultado)) {
    $pendentes[] = $linha;
}
mysqli_stmt_close($consulta);

$agora = new DateTime();

include '../includes/cabecalho.php';
?>

<h2>Pedidos Pendentes</h2>

<nav class="admin-menu">
    <a href="agenda.php">Minha Agenda</a>
    <a href="bloquear.php">Bloquear Horário</a>
    <a href="clientes.php">Minhas Clientes</a>
    <a class="atual" href="pendentes.php">Pedidos Pendentes</a>
    <a href="recadinhos.php">Recadinhos</a>
    <a href="login.php?sair=1">Sair</a>
</nav>

<?php if ($mensagem !== '') { ?>
    <div class="mensagem mensagem-sucesso"><?php echo $mensagem; ?></div>
<?php } ?>
<?php if ($erro !== '') { ?>
    <div class="mensagem mensagem-erro"><?php echo $erro; ?></div>
<?php } ?>

<p><strong>Como funciona:</strong> quando o Pix de R$ 20 cair no seu
   banco, toque em <strong>Recebi o sinal</strong> — o horário pinta
   de vermelho na hora. Cliente desistiu? Toque em
   <strong>Cancelar pedido</strong> para liberar o horário.</p>

<?php if (empty($pendentes)) { ?>
    <div class="mensagem mensagem-aviso">
        Nenhum pedido aguardando pagamento agora.
    </div>
<?php } ?>

<?php foreach ($pendentes as $pedido) { ?>
    <?php
        $prazo = new DateTime($pedido['expira_em']);
        $minutos = max(0, (int) ceil(($prazo->getTimestamp() - $agora->getTimestamp()) / 60));

        $classe = 'mensagem-aviso';
        $aviso = "expira em $minutos minuto(s)";
        if ($minutos <= 5) {
            $classe = 'mensagem-erro';
            $aviso = "expira em $minutos minuto(s — vencendo!";
        }
    ?>
    <div class="mensagem <?php echo $classe; ?>">
        <strong><?php echo htmlspecialchars($pedido['nome']); ?></strong>
        (<?php echo htmlspecialchars($pedido['whatsapp']); ?>)
        pediu
        <?php echo date('d/m/Y', strtotime($pedido['data'])); ?>
        às <?php echo substr($pedido['hora'], 0, 5); ?>
        — sinal de R$ <?php echo number_format($pedido['valor_sinal'], 2, ',', '.'); ?> <?php echo $aviso; ?>

        <div class="acoes-pedido">
            <form method="post" action="pendentes.php">
                <input type="hidden" name="acao" value="confirmar">
                <input type="hidden" name="id" value="<?php echo $pedido['id']; ?>">
                <button type="submit" class="botao-principal">&#9989; Recebi o sinal</button>
            </form>
            <form method="post" action="pendentes.php">
                <input type="hidden" name="acao" value="cancelar">
                <input type="hidden" name="id" value="<?php echo $pedido['id']; ?>">
                <button type="submit" class="botao-perigo">Cancelar pedido</button>
            </form>
        </div>
    </div>
<?php } ?>

<?php include '../includes/rodape.php'; ?>
