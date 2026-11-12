// =====================================================
// galeria.js — Etapa 4: vitrine de fotos automática
// Mostra 1 foto por vez e troca sozinha a cada 4 segundos.
// Setas e bolinhas deixam a cliente navegar manualmente.
// =====================================================

// 1) Espera o HTML terminar de carregar antes de mexer nele
document.addEventListener('DOMContentLoaded', function () {

    // 2) Junta todas as fotos da galeria numa lista
    var fotos = document.querySelectorAll('.galeria-fotos .foto-item');
    var album = document.querySelector('.galeria-fotos');

    // Se a página não tem galeria, o JavaScript simplesmente para
    if (!album || fotos.length === 0) {
        return;
    }

    var atual = 0;                 // número da foto sendo exibida
    var tempoTroca = 4000;          // 4 segundos entre as fotos
    var relogio = null;            // guardará o "timer" da troca automática

    // ----- 3) Bolinhas indicadoras (criadas aqui pelo JS) -----
    var nav = document.createElement('div');
    nav.className = 'galeria-nav';

    var bolinhas = [];
    fotos.forEach(function (foto, indice) {
        var bolinha = document.createElement('button');
        bolinha.className = 'bolinha';
        bolinha.setAttribute('aria-label', 'Ver foto ' + (indice + 1));
        bolinha.addEventListener('click', function () {
            mostrar(indice);
            reiniciar();
        });
        nav.appendChild(bolinha);
        bolinhas.push(bolinha);
    });

    // Insere as bolinhas logo abaixo da galeria
    album.parentNode.insertBefore(nav, album.nextSibling);

    // ----- 4) Setas de anterior / próxima -----
    var setaAnterior = document.createElement('button');
    setaAnterior.className = 'seta seta-anterior';
    setaAnterior.textContent = '\u2039'; // ‹
    setaAnterior.setAttribute('aria-label', 'Foto anterior');

    var setaProxima = document.createElement('button');
    setaProxima.className = 'seta seta-proxima';
    setaProxima.textContent = '\u203A'; // ›
    setaProxima.setAttribute('aria-label', 'Próxima foto');

    setaAnterior.addEventListener('click', function () {
        mostrar(atual - 1);
        reiniciar();
    });
    setaProxima.addEventListener('click', function () {
        mostrar(atual + 1);
        reiniciar();
    });

    album.appendChild(setaAnterior);
    album.appendChild(setaProxima);

    // ----- 5) Mostra a foto pedida e apaga a atual -----
    function mostrar(indice) {
        fotos[atual].classList.remove('ativo');
        bolinhas[atual].classList.remove('ativa');

        // O truque do "%": se passar da última foto, volta para a 0
        atual = (indice + fotos.length) % fotos.length;

        fotos[atual].classList.add('ativo');
        bolinhas[atual].classList.add('ativa');
    }

    // ----- 6) Relógio da troca automática -----
    function reiniciar() {
        clearInterval(relogio);
        relogio = setInterval(function () {
            mostrar(atual + 1);
        }, tempoTroca);
    }

    // 7) Pausa o carrossel enquanto o dedo/mouse está sobre a foto
    album.addEventListener('mouseenter', function () { clearInterval(relogio); });
    album.addEventListener('mouseleave', reiniciar);
    album.addEventListener('touchstart', function () { clearInterval(relogio); });
    album.addEventListener('touchend', reiniciar);

    // 8) Larga a primeira foto e começa a rodar
    mostrar(0);
    reiniciar();
});
