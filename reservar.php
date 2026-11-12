<?php
// =====================================================
// reservar.php  —  Lote D
// 1) Recebe o dia/hora escolhidos (POST do calendário)
// 2) CONFERE TUDO DE NOVO no banco (regra anti-duplicata)
// 3) Se estiver livre: cria reserva PENDENTE com prazo
//    de expiração e manda para a página do Pix.
// Este arquivo não mostra nada: ele só trabalha e redireciona.
// =====================================================

session_start();
require_once 'includes/conexao.php';

// ---------- Guardas de entrada ----------

// 1) Só reserva quem se cadastrou antes
if (empty($_SESSION['cliente_id'])) {
    header('Location: cadastro.php');
    exit;
}

// 2) Só aceitamos o POST (ninguém digita este endereço na barra)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: calendario.php');
    exit;
}

// ---------- A mesma faxina do calendário ----------
// (se uma reserva antiga venceu agora, libera o horário antes de conferir)
mysqli_query(
    $conexao,
    "UPDATE agendamentos
        SET status = 'expirado'
      WHERE status = 'pendente'
        AND expira_em < NOW()"
);

// ---------- Leitura e conferência do que chegou ----------
$data = $_POST['data'] ?? '';
$hora = $_POST['hora'] ?? '';

// 3) O formato é mesmo uma data e uma hora válidas?
//    createFromFormat devolve false se o texto não bate com o molde
$momento = DateTime::createFromFormat('Y-m-d H:i', $data . ' ' . $hora);
$formatoOk = ($momento !== false)
    && ($momento->format('Y-m-d') === $data)
    && ($momento->format('H:i') === $hora);

if (!$formatoOk) {
    header('Location: calendario.php');
    exit;
}

// 3b) É dia de folga do studio (domingo/segunda)?
//     O calendário nem mostra esses dias, mas conferimos aqui
//     também: nunca confiamos só na tela — é a regra do Lote D.
if (in_array((int)$momento->format('w'), DIAS_FECHADOS, true)) {
    header('Location: calendario.php?fechado=1');
    exit;
}

// 4) Este horário existe mesmo na tabela do studio?
//    (ninguém reserva "03:37" num studio que abre 09:00)
$alinhado = false;
$primeiro = new DateTime($data . ' ' . HORA_ABERTURA);
$ultimo = new DateTime($data . ' ' . HORA_FECHAMENTO);
for ($h = clone $primeiro; $h < $ultimo; $h->modify('+' . INTERVALO_MINUTOS . ' minutes')) {
    if ($h->format('H:i') === $hora) {
        $alinhado = true;
        break;
    }
}

// 5) E não é um horário que já passou?
if (!$alinhado || $momento <= new DateTime()) {
    header('Location: calendario.php?ocupado=1');
    exit;
}

// ---------- 6) A CONFERÊNCIA DUPLA (o coração da regra) ----------
// "Conferir de novo no banco ao reservar": mesmo que o calendário
// mostrasse verde, outra pessoa pode ter reservado 1 segundo antes.

$livre = true;

// 6a) Existe agendamento ativo neste dia/hora?
$consulta = mysqli_prepare(
    $conexao,
    "SELECT id FROM agendamentos
      WHERE `data` = ? AND `hora` = ?
        AND status IN ('pendente', 'confirmado')
      LIMIT 1"
);
mysqli_stmt_bind_param($consulta, 'ss', $data, $hora);
mysqli_stmt_execute($consulta);
mysqli_stmt_store_result($consulta);
if (mysqli_stmt_num_rows($consulta) > 0) {
    $livre = false;
}
mysqli_stmt_close($consulta);

// 6b) O studio bloqueou este dia/hora?
if ($livre) {
    $consulta = mysqli_prepare(
        $conexao,
        "SELECT id FROM bloqueios WHERE `data` = ? AND `hora` = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($consulta, 'ss', $data, $hora);
    mysqli_stmt_execute($consulta);
    mysqli_stmt_store_result($consulta);
    if (mysqli_stmt_num_rows($consulta) > 0) {
        $livre = false;
    }
    mysqli_stmt_close($consulta);
}

// Não deu: o horário sumiu na frente dela
if (!$livre) {
    header('Location: calendario.php?ocupado=1');
    exit;
}

// ---------- 7) SEGURA O HORÁRIO: reserva pendente com prazo ----------
$cliente_id = (int) $_SESSION['cliente_id'];

// bind_param só aceita VARIÁVEIS (não aceita constantes direto),
// então copiamos os valores do config.php para variáveis
$valor_servico = VALOR_SERVICO;
$valor_sinal = VALOR_SINAL;
$valor_restante = VALOR_RESTANTE;

$expiraEm = (new DateTime())
    ->modify('+' . PRAZO_RESERVA_MINUTOS . ' minutes')
    ->format('Y-m-d H:i:s');

$insere = mysqli_prepare(
    $conexao,
    "INSERT INTO agendamentos
        (cliente_id, `data`, `hora`, status,
         valor_servico, valor_sinal, valor_restante, expira_em)
     VALUES (?, ?, ?, 'pendente', ?, ?, ?, ?)"
);
mysqli_stmt_bind_param(
    $insere,
    'issddds',
    $cliente_id,
    $data,
    $hora,
    $valor_servico,
    $valor_sinal,
    $valor_restante,
    $expiraEm
);
mysqli_stmt_execute($insere);

// Guardamos qual é "a reserva da vez" desta cliente
$_SESSION['agendamento_id'] = mysqli_insert_id($conexao);
mysqli_stmt_close($insere);

// ---------- 8) Segue para o pagamento do sinal ----------
header('Location: pagamento.php');
exit;
