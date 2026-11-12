// =====================================================
// calendario.js — Lote D
// Lê o pacote de dados que o PHP preparou e desenha:
//   1) os botões dos dias (a "régua" de datas)
//   2) a grade de horários coloridos do dia escolhido
// Ao tocar num horário LIVRE, pergunta confirmação e
// envia o formulário invisível para o reservar.php.
// =====================================================

document.addEventListener('DOMContentLoaded', function () {

    // ---- 1) Achando as peças na página ----
    var pacote = document.getElementById('dados-calendario');
    var caixaDias = document.getElementById('dias');
    var caixaHorarios = document.getElementById('horarios');

    if (!pacote || !caixaDias || !caixaHorarios) {
        return; // esta página não tem calendário: nada a fazer
    }

    // O pacote está em JSON (texto organizado); traduzimos para objeto
    var album = JSON.parse(pacote.textContent);

    var diaEscolhido = 0; // começa no primeiro dia (hoje)

    // ---- 2) Desenha a régua de dias ----
    function desenharDias() {
        caixaDias.innerHTML = ''; // limpa antes de redesenhar

        album.forEach(function (dia, indice) {
            var botao = document.createElement('button');
            botao.type = 'button';
            botao.className = 'dia-botao' + (indice === diaEscolhido ? ' selecionado' : '');
            botao.textContent = dia.rotulo + (dia.hoje ? ' · hoje' : '');

            botao.addEventListener('click', function () {
                diaEscolhido = indice;
                desenharDias();
                desenharHorarios();
            });

            caixaDias.appendChild(botao);
        });
    }

    // ---- 3) Desenha a grade de horários do dia escolhido ----
    function desenharHorarios() {
        caixaHorarios.innerHTML = '';

        album[diaEscolhido].horarios.forEach(function (info) {
            var botao = document.createElement('button');
            botao.type = 'button';
            botao.className = 'horario ' + info.status;
            botao.textContent = info.hora;

            if (info.status === 'ocupado') {
                botao.disabled = true;
                botao.title = 'Horário ocupado';
            } else if (info.status === 'aguardando') {
                botao.disabled = true;
                botao.title = 'Alguém está pagando o sinal agora';
            } else if (info.status === 'fechado') {
                botao.disabled = true;
                botao.title = 'Fechado';
            } else {
                // LIVRE: pode reservar!
                botao.addEventListener('click', function () {
                    var dia = album[diaEscolhido];

                    // 2026-11-15  ->  15/11/2026 (brasileirinho)
                    var dataBonita = dia.data.split('-').reverse().join('/');

                    var responder = window.confirm(
                        'Reservar ' + dataBonita + ' às ' + info.hora + '?'
                    );

                    if (responder) {
                        // Preenche o formulário invisível e envia
                        document.getElementById('reserva-data').value = dia.data;
                        document.getElementById('reserva-hora').value = info.hora;
                        document.getElementById('form-reserva').submit();
                    }
                });
            }

            caixaHorarios.appendChild(botao);
        });
    }

    // ---- 4) Primeira pintura da tela ----
    desenharDias();
    desenharHorarios();
});
