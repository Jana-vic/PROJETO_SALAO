// =====================================================
// js/pagamento.js  —  Lote H (modo barbearia)
// Duas ajudinhas na página do Pix:
//   1) os botões de copiar (o código e a chave)
//   2) a contagem regressiva do prazo — quando chega a
//      zero, volta para o calendário com o aviso de que
//      o horário foi liberado.
// =====================================================

// ---------- 1) COPIAR ----------
// Copia um texto para a "área de transferência" (o caderninho
// invisível onde o celular/computador guarda o que foi copiado).
async function copiarTexto(texto, botao) {
    let conseguiu = false;

    try {
        // Jeito moderno: o navegador copia sozinho
        await navigator.clipboard.writeText(texto);
        conseguiu = true;
    } catch (erro) {
        // Jeito antigo (navegadores mais velhos): cria uma
        // caixinha invisível, seleciona o texto e manda copiar
        const caixinha = document.createElement('textarea');
        caixinha.value = texto;
        caixinha.style.position = 'fixed';
        caixinha.style.opacity = '0';
        document.body.appendChild(caixinha);
        caixinha.select();
        conseguiu = document.execCommand('copy');
        document.body.removeChild(caixinha);
    }

    // Responde na hora, para a cliente saber que funcionou
    const textoOriginal = botao.textContent;
    botao.textContent = conseguiu ? 'Copiado!' : 'Não deu para copiar :(';
    setTimeout(function () {
        botao.textContent = textoOriginal;
    }, 2500);
}

// ---------- 2) CONTAGEM REGRESSIVA ----------
document.addEventListener('DOMContentLoaded', function () {

    // Liga os dois botões de copiar
    const codigo = document.getElementById('codigo-pix');
    const botaoCodigo = document.getElementById('botao-copiar');
    const botaoChave = document.getElementById('botao-copiar-chave');

    if (codigo && botaoCodigo) {
        botaoCodigo.addEventListener('click', function () {
            copiarTexto(codigo.textContent.trim(), botaoCodigo);
        });
    }
    if (botaoChave) {
        // a chave vem escondida no atributo data-chave do botão
        botaoChave.addEventListener('click', function () {
            copiarTexto(botaoChave.dataset.chave, botaoChave);
        });
    }

    // O contador: lê os segundos que o PHP escreveu e vai tirando 1 a 1
    const contador = document.getElementById('contador-pix');
    const tempo = document.getElementById('tempo-restante');

    if (contador && tempo) {
        let restam = parseInt(contador.dataset.segundos, 10);

        function tique() {
            if (restam <= 0) {
                // Acabou o prazo: volta para o calendário com o aviso
                window.location.href = 'calendario.php?expirado=1';
                return;
            }
            const minutos = Math.floor(restam / 60);
            const segundos = restam % 60;
            // padStart: o "9" vira "09", para o relógio ficar bonito
            tempo.textContent = minutos + ' min e '
                + String(segundos).padStart(2, '0') + ' s';
            restam = restam - 1;
        }

        tique();                    // mostra na hora
        setInterval(tique, 1000);    // e depois, a cada 1 segundo
    }
});
