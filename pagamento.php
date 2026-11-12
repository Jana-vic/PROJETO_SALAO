<?php
// =====================================================
// pagamento.php  —  Lote H (versão 2 — modo barbearia)
// A página que a cliente vê logo depois de escolher o
// horário: o resumo, o QR Code do Pix, o código "copia
// e cola", a contagem regressiva do prazo e a política.
//
// É uma escadinha de "se"s (if/else), como a Jana desenhou:
//   se a reserva está CONFIRMADA  → mostra a boa notícia
//   se não achou a reserva        → volta pro calendário
//   se VENCEU                     → calendário + aviso
//   se outro status               → calendário + aviso
//   senão (está pendente)         → mostra o Pix!
//
// Como o pagamento é confirmado: a própria nail designer
// vê o Pix cair no app do banco e toca em "Recebi o
// sinal" nos Pedidos Pendentes do painel.
// =====================================================

session_start();
require_once 'includes/conexao.php';
require_once 'includes/pix_estatico.php';   // monta o código Pix

// ---------- Guardas de entrada ----------
if (empty($_SESSION['cliente_id'])) {
    header('Location: cadastro.php');
    exit;
}
if (empty($_SESSION['agendamento_id'])) {
    header('Location: calendario.php');
    exit;
}

// ---------- Faxina: reserva da vez pode ter vencido ----------
mysqli_query(
    $conexao,
    "UPDATE agendamentos
        SET status = 'expirado'
      WHERE status = 'pendente'
        AND expira_em < NOW()"
);

// ---------- Carrega a reserva da vez (só se for desta cliente) ----------
$agendamento_id = (int) $_SESSION['agendamento_id'];
$cliente_id = (int) $_SESSION['cliente_id'];

$consulta = mysqli_prepare(
    $conexao,
    "SELECT `data`, `hora`, status, valor_servico, valor_sinal,
            valor_restante, expira_em
       FROM agendamentos
      WHERE id = ? AND cliente_id = ?
      LIMIT 1"
);
mysqli_stmt_bind_param($consulta, 'ii', $agendamento_id, $cliente_id);
mysqli_stmt_execute($consulta);
$reserva = mysqli_stmt_get_result($consulta)->fetch_assoc();
mysqli_stmt_close($consulta);

// ---------- A escadinha de "se"s ----------

// 1) Já confirmada (a nail designer viu o Pix)? Boa notícia!
if ($reserva && $reserva['status'] === 'confirmado') {

    $tituloPagina = 'Horário confirmado | Studio Bem Garota';
    $dataBrasil = DateTime::createFromFormat('Y-m-d', $reserva['data'])
        ->format('d/m/Y');
    $horaBrasil = substr($reserva['hora'], 0, 5);

    include 'includes/cabecalho.php';
    ?>

    <section class="box-pix">
        <h2>Tudo certo! &#10024;</h2>
        <p>Olá, <?php echo htmlspecialchars($_SESSION['cliente_nome']); ?>!</p>

        <div class="mensagem mensagem-sucesso">
            <strong>Recebemos seu sinal.</strong> Seu horário em
            <?php echo $dataBrasil; ?> às <?php echo $horaBrasil; ?>
            está <strong>garantido</strong>!
        </div>

        <div class="resumo-valores">
            <div>
                <span>Atendimento de unhas em <?php echo $dataBrasil; ?> às <?php echo $horaBrasil; ?></span>
            </div>
            <div>
                <span>Valor do atendimento</span>
                <span>R$ <?php echo number_format($reserva['valor_servico'], 2, ',', '.'); ?></span>
            </div>
            <div>
                <span>Sinal já pago (Pix)</span>
                <span>R$ <?php echo number_format($reserva['valor_sinal'], 2, ',', '.'); ?></span>
            </div>
            <div class="total">
                <span>Falta pagar no dia do atendimento</span>
                <span>R$ <?php echo number_format($reserva['valor_restante'], 2, ',', '.'); ?></span>
            </div>
        </div>

        <p>Nos vemos lá! Qualquer coisa, fale com a gente pelo WhatsApp.</p>

        <p><a class="botao-secundario" href="index.php">Voltar ao início</a></p>
    </section>

    <?php
    include 'includes/rodape.php';
    exit;
}

// 2) Não achou (sessão velha / outro problema)?
if (!$reserva) {
    header('Location: calendario.php');
    exit;
}

// 3) Venceu o prazo sem pagamento?
if ($reserva['status'] === 'expirado') {
    header('Location: calendario.php?expirado=1');
    exit;
}

// 4) Qualquer outro status estranho
if ($reserva['status'] !== 'pendente') {
    header('Location: calendario.php?ocupado=1');
    exit;
}

// ---------- Daqui para baixo: reserva PENDENTE → mostra o Pix ----------

$tituloPagina = 'Pagamento do sinal | Studio Bem Garota';

// 2026-11-11 -> 11/11/2026
$dataBrasil = DateTime::createFromFormat('Y-m-d', $reserva['data'])
    ->format('d/m/Y');
$horaBrasil = substr($reserva['hora'], 0, 5);

// Quanto tempo falta para o prazo? Em minutos (para o texto
// inicial) e em segundos (para o JavaScript contar em vivo)
$prazo = new DateTime($reserva['expira_em']);
$agora = new DateTime();
$minutosRestantes = max(0, (int) ceil(($prazo->getTimestamp() - $agora->getTimestamp()) / 60));
$segundosRestantes = max(0, $prazo->getTimestamp() - $agora->getTimestamp());

// O código Pix montado na hora, a partir do config.php
$copiaCola = pix_copia_e_cola();
$chaveBonita = pix_chave_formatada();

// O link "já paguei" abre uma conversa já escrita no WhatsApp do studio
$textoZap = 'Olá! Sou ' . $_SESSION['cliente_nome']
    . ' e acabei de pagar o sinal de R$ '
    . number_format($reserva['valor_sinal'], 2, ',', '.')
    . ' do meu horário em ' . $dataBrasil . ' às ' . $horaBrasil . '.';
$linkZap = 'https://wa.me/' . preg_replace('/\D/', '', PIX_CHAVE)
         . '?text=' . rawurlencode($textoZap);

include 'includes/cabecalho.php';
?>

<section class="box-pix">
    <h2>Confirme seu horário</h2>
    <p>Olá, <?php echo htmlspecialchars($_SESSION['cliente_nome']); ?>!
       Você segurou o horário abaixo — falta só o sinal.</p>

    <div class="resumo-valores">
        <div>
            <span>Atendimento de unhas em <?php echo $dataBrasil; ?> às <?php echo $horaBrasil; ?></span>
        </div>
        <div>
            <span>Valor do atendimento</span>
            <span>R$ <?php echo number_format($reserva['valor_servico'], 2, ',', '.'); ?></span>
        </div>
        <div>
            <span>Sinal pago agora (Pix)</span>
            <span>R$ <?php echo number_format($reserva['valor_sinal'], 2, ',', '.'); ?></span>
        </div>
        <div class="total">
            <span>Falta pagar no dia do atendimento</span>
            <span>R$ <?php echo number_format($reserva['valor_restante'], 2, ',', '.'); ?></span>
        </div>
    </div>

    <h3>Como pagar o sinal</h3>

    <p><strong>1.</strong> Abra o app do seu banco, toque em
       <strong>Pix</strong> e escolha <strong>Ler QR Code</strong> —
       aponte a câmera para o código abaixo.</p>

    <div class="qr-pix">
        <img src="img/qr-pix.png" alt="QR Code Pix do Studio Bem Garota">
    </div>

    <p><strong>2.</strong> Sem câmera? No banco, escolha
       <strong>Pix Copia e Cola</strong> e cole o código abaixo —
       ele já vai com o valor de
       R$ <?php echo number_format($reserva['valor_sinal'], 2, ',', '.'); ?> pronto.</p>

    <div class="copia-cola" id="codigo-pix"><?php echo $copiaCola; ?></div>

    <p>
        <button type="button" class="botao-principal" id="botao-copiar">
            &#128203; Copiar código Pix
        </button>
    </p>

    <p class="aviso-chave">
        Prefere usar a <strong>chave Pix</strong> (o telefone
        <?php echo $chaveBonita; ?>)?
        <button type="button" class="botao-secundario" id="botao-copiar-chave"
                data-chave="<?php echo htmlspecialchars(PIX_CHAVE); ?>">
            Copiar chave
        </button>
    </p>

    <div class="mensagem mensagem-aviso" id="contador-pix"
         data-segundos="<?php echo $segundosRestantes; ?>">
        <strong>&#9200; Seu horário está segurado por
        <span id="tempo-restante"><?php echo $minutosRestantes; ?> minuto(s)</span>.</strong><br>
        Depois desse prazo ele volta a ficar livre para outras clientes
        se o sinal não for pago.
    </div>

    <p>Assim que o sinal cair aqui no studio, confirmamos seu horário —
       ele ficará vermelhinho no calendário (ocupado por você).
       Se quiser, avise a gente:</p>

    <p>
        <a class="botao-secundario" href="<?php echo $linkZap; ?>"
           target="_blank" rel="noopener">
            &#128172; Já paguei — avisar no WhatsApp
        </a>
    </p>

    <div class="politica">
        <strong>Antes de pagar, leia com calma:</strong>
        <ul>
            <li>O sinal de <strong>R$ <?php echo number_format($reserva['valor_sinal'], 2, ',', '.'); ?></strong>
                é <strong>descontado</strong> do valor total — você paga só o que resta no dia.</li>
            <li><strong>Cancelamento com mais de 48 horas de antecedência:</strong>
                sinal devolvido ou remarcado para outro horário.</li>
            <li><strong>Cancelamento com menos de 48 horas ou falta:</strong>
                o sinal não é devolvido, pois o horário ficou bloqueado
                para outras clientes.</li>
            <li>Atrasos de mais de 20 minutos podem encurtar o atendimento
                para não atrasar as próximas clientes.</li>
        </ul>
    </div>

    <p><a class="botao-secundario" href="calendario.php">
        Escolher outro horário
    </a></p>
</section>

<script src="js/pagamento.js"></script>

<?php include 'includes/rodape.php'; ?>
