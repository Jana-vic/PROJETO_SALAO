<?php
// =====================================================
// cadastro.php  —  Lote C
// Formulário da cliente: nome + WhatsApp (obrigatórios)
// e recadinho (opcional). Grava no banco MESMO QUE ela
// não conclua a reserva — como combinado no planejamento.
// =====================================================

session_start();   // abre a "fichinha" desta visitante
require_once 'includes/conexao.php';

$tituloPagina = 'Agende seu horário | Studio Bem Garota';

// Valores que reaparecem preenchidos se der erro
$nome = '';
$whatsapp = '';
$recadinho = '';
$erros = [];

// ---------- Chegou um formulário preenchido? ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) Pega o que foi digitado e ajeita
    $nome = trim($_POST['nome'] ?? '');
    // A "borracha" \D: apaga tudo que NÃO é número (parênteses, traço, espaço)
    $whatsapp = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');
    $recadinho = trim($_POST['recadinho'] ?? '');

    // 2) Conferências do lado do SERVIDOR (as que valem de verdade)
    if ($nome === '') {
        $erros[] = 'Escreva seu nome completo.';
    } elseif (mb_strlen($nome) < 3) {
        $erros[] = 'O nome parece curto demais.';
    }
    if ($whatsapp === '') {
        $erros[] = 'Escreva seu WhatsApp com DDD.';
    } elseif (strlen($whatsapp) < 10) {
        $erros[] = 'WhatsApp incompleto: precisa do DDD + número.';
    } elseif (strlen($whatsapp) > 13) {
        $erros[] = 'WhatsApp longo demais: use só DDD + número.';
    }
    if (mb_strlen($recadinho) > 2000) {
        $erros[] = 'O recadinho está muito grande, reduza um pouco.';
    }

    // 3) Tudo certo? Grava e segue para o calendário
    if (empty($erros)) {

        // Já é cliente? (mesmo WhatsApp = mesma pessoa)
        $consulta = mysqli_prepare(
            $conexao,
            'SELECT id FROM clientes WHERE whatsapp = ?'
        );
        mysqli_stmt_bind_param($consulta, 's', $whatsapp);
        mysqli_stmt_execute($consulta);
        $resultado = mysqli_stmt_get_result($consulta);
        $cliente = mysqli_fetch_assoc($resultado);
        mysqli_stmt_close($consulta);

        if ($cliente) {
            // Cliente antiga: reaproveita o cadastro e atualiza o nome
            $cliente_id = $cliente['id'];

            $atualiza = mysqli_prepare(
                $conexao,
                'UPDATE clientes SET nome = ? WHERE id = ?'
            );
            mysqli_stmt_bind_param($atualiza, 'si', $nome, $cliente_id);
            mysqli_stmt_execute($atualiza);
            mysqli_stmt_close($atualiza);
        } else {
            // Cliente nova: cria o cadastro
            $insere = mysqli_prepare(
                $conexao,
                'INSERT INTO clientes (nome, whatsapp) VALUES (?, ?)'
            );
            mysqli_stmt_bind_param($insere, 'ss', $nome, $whatsapp);
            mysqli_stmt_execute($insere);
            $cliente_id = mysqli_insert_id($conexao);
            mysqli_stmt_close($insere);
        }

        // Recadinho é opcional: grava só se ela escreveu algo
        if ($recadinho !== '') {
            $recado = mysqli_prepare(
                $conexao,
                'INSERT INTO recadinhos (cliente_id, mensagem) VALUES (?, ?)'
            );
            mysqli_stmt_bind_param($recado, 'is', $cliente_id, $recadinho);
            mysqli_stmt_execute($recado);
            mysqli_stmt_close($recado);
        }

        // Guarda quem é ela nesta visita e segue para o calendário
        $_SESSION['cliente_id'] = $cliente_id;
        $_SESSION['cliente_nome'] = $nome;

        header('Location: calendario.php');
        exit;
    }
}

include 'includes/cabecalho.php';
?>

<section class="formulario">
    <h2>Vamos agendar seu horário</h2>
    <p>Preencha rapidinho. Depois você escolhe o dia e a hora.</p>

    <div class="aviso-dados">
        <strong>Como usamos seus dados:</strong>
        seu nome e seu WhatsApp servem apenas para confirmar e lembrar
        do seu horário — e seu recadinho, para a nail designer se preparar
        para você. Não mandamos propaganda e não repassamos seus dados
        para ninguém. Para apagá-los a qualquer momento, é só pedir no
        WhatsApp do studio.
    </div>

    <?php if (!empty($erros)) { ?>
        <div class="mensagem mensagem-erro">
            <?php foreach ($erros as $erro) { ?>
                <p><?php echo $erro; ?></p>
            <?php } ?>
        </div>
    <?php } ?>

    <form method="post" action="cadastro.php">

        <div class="campo">
            <label for="nome">Nome completo *</label>
            <input type="text" id="nome" name="nome" required
                   placeholder="Ex.: Maria Clara Souza"
                   value="<?php echo htmlspecialchars($nome); ?>">
        </div>

        <div class="campo">
            <label for="whatsapp">WhatsApp (com DDD) *</label>
            <input type="tel" id="whatsapp" name="whatsapp" required
                   inputmode="numeric" maxlength="20"
                   placeholder="(11) 99999-9999"
                   value="<?php echo htmlspecialchars($whatsapp); ?>">
        </div>

        <div class="campo">
            <label for="recadinho">Recadinho (opcional)</label>
            <textarea id="recadinho" name="recadinho"
                placeholder="Ex.: quero alongamento em fibra, formato quadrado, tom nude..."><?php echo htmlspecialchars($recadinho); ?></textarea>
            <small>Avisos importantes: alergias, formato, cor e referências que você ama.</small>
        </div>

        <button type="submit" class="botao-principal">
            Continuar para escolher o horário
        </button>
    </form>

    <p class="voltar">
        <a class="botao-secundario" href="index.php">&larr; Voltar para a página inicial</a>
    </p>
</section>

<?php include 'includes/rodape.php'; ?>
