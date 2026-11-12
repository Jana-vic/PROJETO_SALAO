<?php
// =====================================================
// cabecalho.php  —  topo que se repete em todas as páginas
// Guardado dentro de includes/ porque é código de apoio.
// =====================================================

// Garante que BASE_URL exista (páginas sem banco também usam o topo)
require_once __DIR__ . '/config.php';

// Páginas do PAINEL usam o manifest do painel (o app abre no login);
// o resto do site usa o manifest do site (o app abre na home).
$ePaginaDoPainel = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tituloPagina ?? 'Studio Bem Garota | Nails Design'; ?></title>

    <meta name="description" content="Studio Bem Garota — manicure, pedicure, alongamentos e nail design em Praia Grande. Agende seu horário pelo celular.">

    <!-- O site virando aplicativo no celular -->
    <meta name="theme-color" content="#F6EDE4">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/icone-192.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/img/icone-192.png">
    <?php if ($ePaginaDoPainel) { ?>
        <link rel="manifest" href="<?php echo BASE_URL; ?>/admin/manifest-painel.json">
    <?php } else { ?>
        <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    <?php } ?>

    <!-- Prévia bonita quando o link é enviado no WhatsApp -->
    <meta property="og:title" content="Studio Bem Garota | Nails Design">
    <meta property="og:description" content="Manicure, pedicure, alongamentos e nail design. Agende seu horário.">
    <meta property="og:image" content="<?php echo BASE_URL; ?>/img/logo-horizontal.png">
    <meta property="og:type" content="website">

    <!-- O "guarda-roupa" de estilos do site -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/estilo.css">
</head>
<body>

    <!-- Topo do site: logotipo e nome do studio -->
    <header class="topo">
        <div class="topo-inner">
            <a href="<?php echo BASE_URL; ?>/index.php" class="area-logo">
                <img src="<?php echo BASE_URL; ?>/img/logo.png" alt="Studio Bem Garota" class="logo">
                <span class="marca-topo">
                    <span class="nome-studio">Studio Bem Garota</span>
                    <span class="subtitulo-studio">Nails Design</span>
                </span>
            </a>

            <?php if ($ePaginaDoPainel) { ?>
                <a href="<?php echo BASE_URL; ?>/index.php" class="botao-topo botao-topo-claro">Ver site</a>
            <?php } else { ?>
                <a href="<?php echo BASE_URL; ?>/cadastro.php" class="botao-topo">Agendar</a>
            <?php } ?>
        </div>
    </header>

    <!-- Envelope do conteúdo: abre aqui e fecha no rodape.php -->
    <main class="conteudo">
