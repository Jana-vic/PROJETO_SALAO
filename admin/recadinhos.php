<?php
// =====================================================
// admin/recadinhos.php  —  Lote G
// RECADINHOS DAS CLIENTES: ver mensagem, nome, telefone,
// data e hora; marcar como lida; consultar antigas.
// =====================================================

session_start();

// ---- Guarda: sem login, volta para a porta ----
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/conexao.php';

$tituloPagina = 'Recadinhos das Clientes | Studio Bem Garota';

$mensagem = '';

// ---------- Ação: marcar como lida ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'ler') {

    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        $marca = mysqli_prepare(
            $conexao,
            "UPDATE recadinhos SET lida = 'sim' WHERE id = ? AND lida = 'nao'"
        );
        mysqli_stmt_bind_param($marca, 'i', $id);
        mysqli_stmt_execute($marca);

        if (mysqli_stmt_affected_rows($marca) > 0) {
            $mensagem = 'Recadinho marcado como lido.';
        }
        mysqli_stmt_close($marca);
    }
}

// ---------- Filtro: novas / todas ----------
// ?filtro=todas mostra também as já lidas (consultar antigas)
$filtro = ($_GET['filtro'] ?? '') === 'todas' ? 'todas' : 'novas';

$condicao = ($filtro === 'todas') ? '' : "WHERE r.lida = 'nao'";

$recadinhos = [];
$consulta = mysqli_prepare(
    $conexao,
    "SELECT r.id, r.mensagem, r.lida, r.criado_em,
            c.nome, c.whatsapp
       FROM recadinhos r
       INNER JOIN clientes c ON c.id = r.cliente_id
       $condicao
      ORDER BY r.criado_em DESC"
);
mysqli_stmt_execute($consulta);
$resultado = mysqli_stmt_get_result($consulta);
while ($linha = mysqli_fetch_assoc($resultado)) {
    $recadinhos[] = $linha;
}
mysqli_stmt_close($consulta);

include '../includes/cabecalho.php';
?>

<h2>Recadinhos das Clientes</h2>

<nav class="admin-menu">
    <a href="agenda.php">Minha Agenda</a>
    <a href="bloquear.php">Bloquear Horário</a>
    <a href="clientes.php">Minhas Clientes</a>
    <a href="pendentes.php">Pedidos Pendentes</a>
    <a class="atual" href="recadinhos.php">Recadinhos</a>
    <a href="login.php?sair=1">Sair</a>
</nav>

<?php if ($mensagem !== '') { ?>
    <div class="mensagem mensagem-sucesso"><?php echo $mensagem; ?></div>
<?php } ?>

<!-- Filtro: novas x todas -->
<div class="admin-menu">
    <?php if ($filtro === 'novas') { ?>
        <a class="atual" href="recadinhos.php">Não lidas</a>
        <a href="recadinhos.php?filtro=todas">Todas (ver antigas)</a>
    <?php } else { ?>
        <a href="recadinhos.php">Não lidas</a>
        <a class="atual" href="recadinhos.php?filtro=todas">Todas (ver antigas)</a>
    <?php } ?>
</div>

<?php if (empty($recadinhos)) { ?>
    <div class="mensagem mensagem-aviso">
        <?php if ($filtro === 'novas') { ?>
            Nenhum recadinho novo. Use "Todas" para consultar os antigos.
        <?php } else { ?>
            Nenhum recadinho no sistema ainda.
        <?php } ?>
    </div>
<?php } ?>

<?php foreach ($recadinhos as $rec) { ?>
    <div class="recadinho-item <?php echo $rec['lida'] === 'nao' ? 'nao-lida' : ''; ?>">
        <p><?php echo nl2br(htmlspecialchars($rec['mensagem'])); ?></p>
        <small>
            <strong><?php echo htmlspecialchars($rec['nome']); ?></strong>
            (<?php echo htmlspecialchars($rec['whatsapp']); ?>)
            em <?php echo (new DateTime($rec['criado_em']))->format('d/m/Y \à\s H:i'); ?>
            <?php if ($rec['lida'] === 'nao') { ?>— não lido<?php } ?>
        </small>

        <?php if ($rec['lida'] === 'nao') { ?>
            <form method="post" action="recadinhos.php">
                <input type="hidden" name="acao" value="ler">
                <input type="hidden" name="id" value="<?php echo $rec['id']; ?>">
                <button type="submit" class="botao-secundario">Marcar como lida</button>
            </form>
        <?php } ?>
    </div>
<?php } ?>

<?php include '../includes/rodape.php'; ?>
