<?php
// =====================================================
// calendario.php  —  Lote D
// Mostra os próximos 14 dias e os horários coloridos:
//   verde = livre | amarelo = aguardando Pix
//   vermelho = ocupado | cinza = fechado/bloqueado
// O PHP monta os dados; o js/calendario.js desenha.
// =====================================================

session_start();
require_once 'includes/conexao.php';

// Guarda: só escolhe horário quem se cadastrou antes
if (empty($_SESSION['cliente_id'])) {
    header('Location: cadastro.php');
    exit;
}

$tituloPagina = 'Escolha seu horário | Studio Bem Garota';

// ---------- 1) FAXINA: reservas vencidas viram 'expirado' ----------
// Toda vez que alguém abre o calendário, o próprio banco "varre" as
// reservas pendentes cujo prazo acabou. O horário volta a ficar livre.
mysqli_query(
    $conexao,
    "UPDATE agendamentos
        SET status = 'expirado'
      WHERE status = 'pendente'
        AND expira_em < NOW()"
);

// ---------- 2) A JANELA DE DIAS: hoje + 13 dias seguintes ----------
$diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
$hoje = new DateTime('today');
$ultimoDia = (clone $hoje)->modify('+13 days');
$de = $hoje->format('Y-m-d');
$ate = $ultimoDia->format('Y-m-d');

// ---------- 3) QUEM JÁ ESTÁ MARCADO NESSE PERÍODO? ----------
// Duas listas: ocupados (Pix pago) e pendentes (aguardando pagamento)
$ocupados = [];
$pendentes = [];
$consulta = mysqli_prepare(
    $conexao,
    "SELECT `data`, `hora`, status FROM agendamentos
      WHERE `data` BETWEEN ? AND ?
        AND status IN ('confirmado', 'pendente')"
);
mysqli_stmt_bind_param($consulta, 'ss', $de, $ate);
mysqli_stmt_execute($consulta);
$resultado = mysqli_stmt_get_result($consulta);
while ($linha = mysqli_fetch_assoc($resultado)) {
    // A hora vem "14:00:00"; cortamos para "14:00"
    $chave = $linha['data'] . '|' . substr($linha['hora'], 0, 5);
    if ($linha['status'] === 'confirmado') {
        $ocupados[$chave] = true;
    } else {
        $pendentes[$chave] = true;
    }
}
mysqli_stmt_close($consulta);

// ---------- 4) BLOQUEIOS da nail designer no período ----------
$bloqueados = [];
$consulta = mysqli_prepare(
    $conexao,
    "SELECT `data`, `hora` FROM bloqueios WHERE `data` BETWEEN ? AND ?"
);
mysqli_stmt_bind_param($consulta, 'ss', $de, $ate);
mysqli_stmt_execute($consulta);
$resultado = mysqli_stmt_get_result($consulta);
while ($linha = mysqli_fetch_assoc($resultado)) {
    $bloqueados[$linha['data'] . '|' . substr($linha['hora'], 0, 5)] = true;
}
mysqli_stmt_close($consulta);

// ---------- 5) MONTA O ÁLBUM: cada dia com seus horários ----------
$agora = new DateTime();   // relógio deste instante
$album = [];

for ($dia = clone $hoje; $dia <= $ultimoDia; $dia->modify('+1 day')) {

    // Dia de folga (definido no config.php)? Nem entra no álbum:
    // a cliente nem vê que o dia existe.
    if (in_array((int)$dia->format('w'), DIAS_FECHADOS, true)) {
        continue; // pula este dia e segue para o próximo
    }

    $dataTxt = $dia->format('Y-m-d');
    $horarios = [];

    // Da abertura ao fechamento, de INTERVALO_MINUTOS em INTERVALO_MINUTOS
    $abertura = new DateTime($dataTxt . ' ' . HORA_ABERTURA);
    $fechamento = new DateTime($dataTxt . ' ' . HORA_FECHAMENTO);

    for ($hora = clone $abertura; $hora < $fechamento; $hora->modify('+' . INTERVALO_MINUTOS . ' minutes')) {
        $horaTxt = $hora->format('H:i');
        $chave = $dataTxt . '|' . $horaTxt;

        if (isset($bloqueados[$chave])) {
            $status = 'fechado';      // cinza: studio fechou
        } elseif (isset($ocupados[$chave])) {
            $status = 'ocupado';      // vermelho: Pix pago
        } elseif (isset($pendentes[$chave])) {
            $status = 'aguardando';   // amarelo: alguém segurando
        } elseif ($hora <= $agora) {
            $status = 'fechado';      // horário que já passou
        } else {
            $status = 'livre';        // verde: pode reservar!
        }

        $horarios[] = ['hora' => $horaTxt, 'status' => $status];
    }

    $album[] = [
        'data'    => $dataTxt,
        'rotulo'  => $diasSemana[(int)$dia->format('w')] . ' ' . $dia->format('d/m'),
        'hoje'    => ($dataTxt === $de),
        'horarios' => $horarios,
    ];
}

// Pacote de dados que o JavaScript vai ler
$dadosJson = json_encode($album, JSON_UNESCAPED_UNICODE);

include 'includes/cabecalho.php';
?>

<section>
    <h2>Escolha o dia e o horário</h2>
    <!-- A frase "terça a sábado" acompanha o DIAS_FECHADOS do
         config.php (domingo e segunda de folga). Se um dia você
         mudar os dias de folga lá, mude esta frase também. -->
    <p>Olá, <?php echo htmlspecialchars($_SESSION['cliente_nome']); ?>!
       Atendemos de <strong>terça a sábado</strong>.
       Toque num horário <strong>verde</strong> para reservar.</p>

    <?php if (isset($_GET['ocupado'])) { ?>
        <div class="mensagem mensagem-erro">
            Ops! Este horário acabou de ser reservado por outra pessoa.
            Escolha outro, por favor.
        </div>
    <?php } ?>

    <?php if (isset($_GET['expirado'])) { ?>
        <div class="mensagem mensagem-erro">
            O prazo de pagamento venceu e este horário voltou a ficar livre.
            Sem drama: escolha o horário de novo (ou outro), por favor.
        </div>
    <?php } ?>

    <?php if (isset($_GET['fechado'])) { ?>
        <div class="mensagem mensagem-aviso">
            O studio não abre neste dia — é dia de folga.
            Escolha outro dia, por favor.
        </div>
    <?php } ?>

    <!-- Os botões de dia nascem aqui, desenhados pelo JS -->
    <div class="dias-nav" id="dias"></div>

    <!-- A grade de horários do dia escolhido -->
    <div class="grade-horarios" id="horarios"></div>

    <ul class="legenda">
        <li><span class="quadrado" style="background: var(--verde);"></span> Livre</li>
        <li><span class="quadrado" style="background: var(--amarelo);"></span> Aguardando pagamento</li>
        <li><span class="quadrado" style="background: var(--vermelho);"></span> Ocupado</li>
        <li><span class="quadrado" style="background: var(--cinza);"></span> Fechado</li>
    </ul>

    <!-- O pacote de dados do PHP entregue ao JavaScript -->
    <script id="dados-calendario" type="application/json"><?php echo $dadosJson; ?></script>

    <!-- Formulário invisível: o JS preenche dia/hora e envia ao reservar.php -->
    <form id="form-reserva" method="post" action="reservar.php">
        <input type="hidden" name="data" id="reserva-data">
        <input type="hidden" name="hora" id="reserva-hora">
    </form>

    <p class="voltar">
        <a class="botao-secundario" href="index.php">&larr; Voltar ao início</a>
    </p>
</section>

<script src="js/calendario.js"></script>

<?php include 'includes/rodape.php'; ?>
