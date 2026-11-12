<?php
// =====================================================
// admin/login.php  —  Lote E
// A porta de entrada do painel da nail designer.
// Confere usuário e senha no banco (senha criptografada).
// =====================================================

session_start();
require_once '../includes/conexao.php';

$tituloPagina = 'Entrar | Studio Bem Garota';

// ---------- Pediu para SAIR (?sair=1) ----------
// Encerra a sessão: o "crachá" é devolvido na recepção
if (isset($_GET['sair'])) {
    session_unset();     // esvazia a fichinha
    session_destroy();   // rasga a fichinha
    header('Location: login.php');
    exit;
}

// ---------- Já está logada? Vai direto para a agenda ----------
if (!empty($_SESSION['admin_id'])) {
    header('Location: agenda.php');
    exit;
}

$erro = '';
$usuario_digitado = '';

// ---------- Enviou o formulário? ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario_digitado = trim($_POST['usuario'] ?? '');
    $senha_digitada = $_POST['senha'] ?? '';

    if ($usuario_digitado === '' || $senha_digitada === '') {
        $erro = 'Preencha o usuário e a senha.';
    } else {
        // Procura o usuário no banco
        $consulta = mysqli_prepare(
            $conexao,
            'SELECT id, usuario, senha FROM usuarios WHERE usuario = ? LIMIT 1'
        );
        mysqli_stmt_bind_param($consulta, 's', $usuario_digitado);
        mysqli_stmt_execute($consulta);
        $achado = mysqli_stmt_get_result($consulta)->fetch_assoc();
        mysqli_stmt_close($consulta);

        // password_verify: confere a senha digitada com o hash guardado
        // (nunca comparamos senha em texto puro — é assim que se faz)
        if ($achado && password_verify($senha_digitada, $achado['senha'])) {

            // Troca o "número do crachá" ao logar (proteção contra roubo de sessão)
            session_regenerate_id(true);

            $_SESSION['admin_id'] = (int) $achado['id'];
            $_SESSION['admin_usuario'] = $achado['usuario'];

            header('Location: agenda.php');
            exit;
        }

        $erro = 'Usuário ou senha incorretos.';
    }
}

include '../includes/cabecalho.php';
?>

<section class="formulario">
    <h2>Painel da nail designer</h2>
    <p>Entre para ver sua agenda, clientes e recadinhos.</p>

    <?php if ($erro !== '') { ?>
        <div class="mensagem mensagem-erro"><?php echo $erro; ?></div>
    <?php } ?>

    <form method="post" action="login.php">
        <div class="campo">
            <label for="usuario">Usuário</label>
            <input type="text" id="usuario" name="usuario" required
                   value="<?php echo htmlspecialchars($usuario_digitado); ?>">
        </div>

        <div class="campo">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>
        </div>

        <button type="submit" class="botao-principal">Entrar</button>
    </form>

    <!-- Lote I: convite para transformar o painel em aplicativo -->
    <p class="dica-instalar">
        &#128241; Dica: no seu celular, adicione este painel à
        <strong>Tela de Início</strong> — ele abre como aplicativo,
        com ícone e tudo.
    </p>
</section>

<?php include '../includes/rodape.php'; ?>
