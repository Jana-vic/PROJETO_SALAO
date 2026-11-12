<?php
// =====================================================
// index.php  —  página inicial que a cliente recebe
// pelo WhatsApp. Aqui ela conhece o studio, vê os
// trabalhos e clica em "Agendar".
// =====================================================

$tituloPagina = 'Studio Bem Garota | Nails Design em Praia Grande';

include 'includes/cabecalho.php';
?>

<!-- ========= APRESENTAÇÃO DO STUDIO ========= -->
<section class="hero">
    <div class="hero-texto">
        <p class="hero-overline">Praia Grande &middot; Nails Design</p>

        <h1 class="hero-titulo">
            <span class="hero-script">Studio</span>
            <span class="hero-nome">BEM GAROTA</span>
        </h1>

        <div class="divisor" aria-hidden="true">
            <span></span><i>&#10022;</i><span></span>
        </div>

        <p class="frase-impacto">Unhas que contam a sua história.</p>

        <p class="texto-apresentacao">
            Alongamento em fibra, banho em gel, postiça realista e manicure
            com acabamento impecável. Cada atendimento é reservado só para
            você &mdash; com calma, capricho e aquele brilho que dura.
        </p>

        <div class="hero-acoes">
            <a href="cadastro.php" class="botao-principal">Agendar meu horário</a>
            <a href="https://instagram.com/_studio.bemgarota" target="_blank" rel="noopener" class="botao-secundario">Ver Instagram</a>
        </div>

        <p class="hero-nota">
            Sinal de R$ <?php echo number_format(VALOR_SINAL, 2, ',', '.'); ?> no Pix
            &middot; horário segurado só para você
        </p>
    </div>

    <div class="hero-foto">
        <div class="hero-moldura">
            <img src="img/unhas-hero.jpg" alt="Alongamento de unhas em fibra feito no Studio Bem Garota">
        </div>
        <span class="hero-selo">Alongamento em fibra</span>
    </div>
</section>

<!-- ==================== SERVIÇOS ==================== -->
<section class="secao-servicos">
    <div class="secao-topo">
        <span class="secao-overline">Serviços</span>
        <h2>Cuidado completo para as suas mãos</h2>
        <p>Do clássico ao alongamento: escolha o que combina com você.</p>
    </div>

    <div class="grade-servicos">
        <article class="servico-card">
            <span class="servico-num">01</span>
            <h3>Manicure</h3>
            <p>Cutilagem, formato e esmaltação tradicional com acabamento delicado.</p>
            <span class="servico-preco">a partir de R$ 45</span>
        </article>

        <article class="servico-card">
            <span class="servico-num">02</span>
            <h3>Pedicure</h3>
            <p>Cuidado completo dos pés, cutilagem e esmaltação impecável.</p>
            <span class="servico-preco">a partir de R$ 55</span>
        </article>

        <article class="servico-card">
            <span class="servico-num">03</span>
            <h3>Esmaltação em gel</h3>
            <p>Brilho de vidro e durabilidade de até três semanas sem lascar.</p>
            <span class="servico-preco">a partir de R$ 70</span>
        </article>

        <article class="servico-card">
            <span class="servico-num">04</span>
            <h3>Banho em gel</h3>
            <p>Camada de gel que fortalece a unha natural e ajuda no crescimento.</p>
            <span class="servico-preco">a partir de R$ 90</span>
        </article>

        <article class="servico-card">
            <span class="servico-num">05</span>
            <h3>Alongamento em fibra</h3>
            <p>Extensão leve, resistente e no formato que você sempre quis.</p>
            <span class="servico-preco">a partir de R$ 180</span>
        </article>

        <article class="servico-card">
            <span class="servico-num">06</span>
            <h3>Postiça realista</h3>
            <p>Unhas postiças com aparência natural, prontas para arrasar.</p>
            <span class="servico-preco">a partir de R$ 150</span>
        </article>
    </div>
</section>

<!-- ==================== GALERIA ==================== -->
<section class="galeria">
    <div class="secao-topo">
        <span class="secao-overline">Portfólio</span>
        <h2>Alguns dos nossos trabalhos</h2>
        <p>Fotos reais do feed @_studio.bemgarota.</p>
    </div>

    <div class="galeria-fotos">
        <figure class="foto-item ativo">
            <img src="img/unhas1.jpg" alt="Alongamento de unhas com esmaltação delicada">
            <figcaption>Alongamento em fibra</figcaption>
        </figure>

        <figure class="foto-item">
            <img src="img/unhas2.jpg" alt="Unhas com nail art e acabamento em gel">
            <figcaption>Nail art delicada</figcaption>
        </figure>

        <figure class="foto-item">
            <img src="img/unhas3.jpg" alt="Esmaltação em gel com brilho intenso">
            <figcaption>Esmaltação em gel</figcaption>
        </figure>

        <figure class="foto-item">
            <img src="img/unhas4.jpg" alt="Unhas postiças realistas com formato amendoado">
            <figcaption>Postiça realista</figcaption>
        </figure>

        <figure class="foto-item">
            <img src="img/unhas5.jpg" alt="Manicure com acabamento natural e elegante">
            <figcaption>Acabamento natural</figcaption>
        </figure>

        <figure class="foto-item">
            <img src="img/unhas6.jpg" alt="Alongamento com cor nude e detalhe dourado">
            <figcaption>Nude &amp; dourado</figcaption>
        </figure>
    </div>
</section>

<!-- ==================== COMO FUNCIONA ==================== -->
<section class="passos">
    <div class="secao-topo">
        <span class="secao-overline">Como funciona</span>
        <h2>Agendar é simples</h2>
    </div>

    <div class="grade-passos">
        <div class="passo">
            <span class="passo-num">1</span>
            <h3>Escolha o horário</h3>
            <p>Veja os dias disponíveis no calendário e toque no horário verde.</p>
        </div>

        <div class="passo">
            <span class="passo-num">2</span>
            <h3>Pague o sinal</h3>
            <p>Um sinal de R$ <?php echo number_format(VALOR_SINAL, 2, ',', '.'); ?> pelo Pix
               garante o seu horário. O restante você paga no dia.</p>
        </div>

        <div class="passo">
            <span class="passo-num">3</span>
            <h3>Pronto, é seu</h3>
            <p>Assim que o sinal cai, seu horário fica confirmado no studio.</p>
        </div>
    </div>
</section>

<!-- ==================== FAIXA DE INFORMAÇÕES ==================== -->
<section class="faixa-info">
    <div>
        <span class="faixa-rotulo">Horário</span>
        <strong>Terça a sábado &middot; 9h às 18h</strong>
    </div>
    <div>
        <span class="faixa-rotulo">Studio</span>
        <strong>Praia Grande &middot; SP</strong>
    </div>
    <div>
        <span class="faixa-rotulo">Instagram</span>
        <a href="https://instagram.com/_studio.bemgarota" target="_blank" rel="noopener">@_studio.bemgarota</a>
    </div>
</section>

<!-- ==================== CHAMADA FINAL ==================== -->
<section class="chamada-final">
    <span class="chamada-script">Seu horário é só seu</span>
    <h2>Vamos cuidar das suas unhas?</h2>
    <p>Reserve agora e deixe o resto com a gente.</p>
    <a href="cadastro.php" class="botao-principal">Agendar agora</a>
</section>

<!-- carrossel da galeria -->
<script src="js/galeria.js"></script>

<?php
// Encaixa o rodapé pronto no fim da página
include 'includes/rodape.php';
?>
