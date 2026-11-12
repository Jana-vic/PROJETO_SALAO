<?php
// =====================================================
// admin/bloquear.php  —  Lote F
// BLOQUEAR HORÁRIO (e desbloquear).
// Quando a nail designer bloqueia, o horário aparece
// CINZA na hora no calendário da cliente (o calendário
// pergunta ao banco a cada visita — nada de "cache").
// =====================================================

session_start();

// ---- Guarda: sem login, volta para a porta ----
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/conexao.php';

$tituloPagina = 'Bloquear Horário | Studio Bem Garota';

$mensagem = '';   // mensagem de sucesso
$erro = '';       // mensagem de erro

// ---------- Recebeu um pedido? ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ====== AÇÃO 1: BLOQUEAR ======
    if (($_POST['acao'] ?? '') === 'bloquear') {

        $data = $_POST['data'] ?? '';
        $hora = $_POST['hora'] ?? '';
        $motivo = trim($_POST['motivo'] ?? '');

        // 1) A data é uma data de verdade e não está no passado?
        $d = DateTime::createFromFormat('Y-m-d', $data);
        $dataOk = ($d !== false) && ($d->format('Y-m-d') === $data);
        if ($dataOk && $d < new DateTime('today')) {
            $dataOk = false;
            $erro = 'Não dá para bloquear um dia que já passou.';
        }

        // 1b) É dia de folga do studio? Nesses dias o studio nem
        //     abre — então não existe horário para bloquear.
        if ($dataOk && in_array((int)$d->format('w'), DIAS_FECHADOS, true)) {
            $dataOk = false;
            $erro = 'O studio não abre neste dia (folga), então não há horário para bloquear.';
        }

        // 2) A hora é uma das vagas reais da tabela do studio?
        $horaOk = false;
        if ($dataOk) {
            $abertura = new DateTime($data . ' ' . HORA_ABERTURA);
            $fechamento = new DateTime($data . ' ' . HORA_FECHAMENTO);
            for ($h = clone $abertura; $h < $fechamento; $h->modify('+' . INTERVALO_MINUTOS . ' minutes')) {
                if ($h->format('H:i') === $hora) { $horaOk = true; break; }
            }
        }

        // Atenção: se o motivo do problema já foi anotado acima
        // (dia que já passou OU dia de folga), mantemos esse aviso
        // — não deixamos outro aviso "atropelar" o primeiro.
        if ($erro !== '') {
            // o motivo já está guardado na variável $erro: só mostrar
        } elseif (!$dataOk) {
            $erro = 'Escolha uma data válida.';
        } elseif (!$horaOk) {
            $erro = 'Escolha um horário da lista.';

        // 3) Já existe agendamento ativo neste dia/hora?
        //    (bloquear em cima de cliente marcada não pode: avise-a antes)
        } else {
            $consulta = mysqli_prepare(
                $conexao,
                "SELECT c.nome FROM agendamentos a
                  INNER JOIN clientes c ON c.id = a.cliente_id
                  WHERE a.`data` = ? AND a.`hora` = ?
                    AND a.status IN ('pendente','confirmado')
                  LIMIT 1"
            );
            $horaBanco = $hora . ':00';
            mysqli_stmt_bind_param($consulta, 'ss', $data, $horaBanco);
            mysqli_stmt_execute($consulta);
            $conflito = mysqli_stmt_get_result($consulta)->fetch_assoc();
            mysqli_stmt_close($consulta);

            if ($conflito) {
                $erro = 'Este horário tem cliente marcada (' .
                        htmlspecialchars($conflito['nome']) .
                        '). Combine a remarcação com ela antes de bloquear aqui.';

            // 4) Já está bloqueado?
            } else {
                $consulta = mysqli_prepare(
                    $conexao,
                    "SELECT id FROM bloqueios WHERE `data` = ? AND `hora` = ? LIMIT 1"
                );
                mysqli_stmt_bind_param($consulta, 'ss', $data, $horaBanco);
                mysqli_stmt_execute($consulta);
                mysqli_stmt_store_result($consulta);
                $jaExiste = mysqli_stmt_num_rows($consulta) > 0;
                mysqli_stmt_close($consulta);

                if ($jaExiste) {
                    $erro = 'Este horário já está bloqueado.';

                // 5) Tudo limpo: bloqueia!
                } else {
                    $insere = mysqli_prepare(
                        $conexao,
                        "INSERT INTO bloqueios (`data`, `hora`, motivo) VALUES (?, ?, ?)"
                    );
                    mysqli_stmt_bind_param($insere, 'sss', $data, $horaBanco, $motivo);
                    mysqli_stmt_execute($insere);
                    mysqli_stmt_close($insere);

                    $mensagem = 'Horário bloqueado: ' . $d->format('d/m/Y') . ' às ' . $hora . '.';
                }
            }
        }
    }

    // ====== AÇÃO 2: DESBLOQUEAR ======
    if (($_POST['acao'] ?? '') === 'desbloquear') {

        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $apaga = mysqli_prepare($conexao, 'DELETE FROM bloqueios WHERE id = ?');
            mysqli_stmt_bind_param($apaga, 'i', $id);
            mysqli_stmt_execute($apaga);
            mysqli_stmt_close($apaga);

            $mensagem = 'Horário liberado! Já aparece livre para as clientes.';
        }
    }
}

// ---------- Lista dos bloqueios de hoje em diante ----------
$hoje = new DateTime('today');
$de = $hoje->format('Y-m-d');

$bloqueios = [];
$consulta = mysqli_prepare(
    $conexao,
    "SELECT id, `data`, `hora`, motivo FROM bloqueios
      WHERE `data` >= ? ORDER BY `data`, `hora`"
);
mysqli_stmt_bind_param($consulta, 's', $de);
mysqli_stmt_execute($consulta);
$resultado = mysqli_stmt_get_result($consulta);
while ($linha = mysqli_fetch_assoc($resultado)) {
    $bloqueios[] = $linha;
}
mysqli_stmt_close($consulta);

// ---------- A lista de horários para o formulário ----------
$opcoesHora = [];
$abertura = new DateTime('2000-01-01 ' . HORA_ABERTURA);
$fechamento = new DateTime('2000-01-01 ' . HORA_FECHAMENTO);
for ($h = clone $abertura; $h < $fechamento; $h->modify('+' . INTERVALO_MINUTOS . ' minutes')) {
    $opcoesHora[] = $h->format('H:i');
}

$diasSemana = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];

include '../includes/cabecalho.php';
?>

<h2>Bloquear Horário</h2>

<nav class="admin-menu">
    <a href="agenda.php">Minha Agenda</a>
    <a class="atual" href="bloquear.php">Bloquear Horário</a>
    <a href="clientes.php">Minhas Clientes</a>
    <a href="pendentes.php">Pedidos Pendentes</a>
    <a href="recadinhos.php">Recadinhos</a>
    <a href="login.php?sair=1">Sair</a>
</nav>

<?php if ($mensagem !== '') { ?>
    <div class="mensagem mensagem-sucesso"><?php echo $mensagem; ?></div>
<?php } ?>
<?php if ($erro !== '') { ?>
    <div class="mensagem mensagem-erro"><?php echo $erro; ?></div>
<?php } ?>

<!-- ============ FORMULÁRIO DE BLOQUEIO ============ -->
<section class="formulario">
    <h3>Bloquear um horário</h3>
    <p>Use para pausas, folgas ou compromissos. O horário
       fica cinza para as clientes na hora.</p>

    <form method="post" action="bloquear.php">
        <input type="hidden" name="acao" value="bloquear">

        <div class="campo">
            <label for="data">Dia</label>
            <input type="date" id="data" name="data" required
                   min="<?php echo $de; ?>" value="<?php echo $de; ?>">
        </div>

        <div class="campo">
            <label for="hora">Horário</label>
            <select id="hora" name="hora" required>
                <?php foreach ($opcoesHora as $opcao) { ?>
                    <option value="<?php echo $opcao; ?>"><?php echo $opcao; ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="campo">
            <label for="motivo">Motivo (opcional, só você vê)</label>
            <input type="text" id="motivo" name="motivo" maxlength="200"
                   placeholder="Ex.: consulta no dentista">
        </div>

        <button type="submit" class="botao-principal">Bloquear horário</button>
    </form>
</section>

<!-- ============ LISTA DE BLOQUEIOS ============ -->
<?php if (!empty($bloqueios)) { ?>
    <h3>Horários bloqueados</h3>

    <div class="tabela-rolagem">
        <table class="tabela">
            <tr>
                <th>Dia</th><th>Hora</th><th>Motivo</th><th></th>
            </tr>
            <?php foreach ($bloqueios as $bloqueio) { ?>
                <?php
                    $d = new DateTime($bloqueio['data']);
                    $tituloDia = $diasSemana[(int)$d->format('w')] . ' ' . $d->format('d/m/Y');
                ?>
                <tr>
                    <td><?php echo $tituloDia; ?></td>
                    <td><?php echo substr($bloqueio['hora'], 0, 5); ?></td>
                    <td><?php echo htmlspecialchars($bloqueio['motivo'] ?? ''); ?></td>
                    <td>
                        <form method="post" action="bloquear.php">
                            <input type="hidden" name="acao" value="desbloquear">
                            <input type="hidden" name="id" value="<?php echo $bloqueio['id']; ?>">
                            <button type="submit" class="botao-perigo">Desbloquear</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
<?php } else { ?>
    <div class="mensagem mensagem-aviso">
        Nenhum horário bloqueado no momento.
    </div>
<?php } ?>

<?php include '../includes/rodape.php'; ?>
