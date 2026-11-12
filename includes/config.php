<?php
// =====================================================
// config.php  —  painel de configuração do sistema
// Valores do serviço, prazo da reserva e horário de
// funcionamento. Mude aqui UMA vez e o sistema inteiro
// obedece (é o "painel de controle da máquina").
// =====================================================

// ---- Dinheiro ----
define('VALOR_SERVICO', 180.00);  // valor do procedimento principal (alongamento em fibra)
define('VALOR_SINAL', 20.00);     // sinal pago no Pix
define('VALOR_RESTANTE', VALOR_SERVICO - VALOR_SINAL); // calculado sozinho

// ---- Reserva temporária ----
// Minutos que o horário fica "segurado" (amarelo)
// enquanto a cliente não paga o Pix. Depois disso, expira.
define('PRAZO_RESERVA_MINUTOS', 30);

// ---- Funcionamento do studio ----
define('HORA_ABERTURA', '09:00');
define('HORA_FECHAMENTO', '18:00');
define('INTERVALO_MINUTOS', 60);  // cada vaga do calendário dura 1 hora

// ---- Dias de folga (o studio NÃO abre) ----
// Cada dia da semana tem um número:
//   0 = domingo | 1 = segunda | 2 = terça | 3 = quarta
//   4 = quinta  | 5 = sexta   | 6 = sábado
// Domingo e segunda fechados: [0, 1]
// Sem folga nenhuma: []   |   Só domingo: [0]
define('DIAS_FECHADOS', [0, 1]);

// ---- Fuso horário (datas e prazos certos) ----
date_default_timezone_set('America/Sao_Paulo');

// ---- Endereço base do site (para CSS, imagens e links) ----
// No XAMPP o projeto mora dentro de /Sites_Jana/studio-bemgarota.
// NA HOSPEDAGEM (domínio próprio): troque para define('BASE_URL', '');
define('BASE_URL', '/Sites_Jana/studio-bemgarota');

// ---- Conexão com o banco de dados (Lote J: agora TUDO aqui!) ----
// No XAMPP: usuário "root", senha vazia e PORTA 3307 (esta máquina
// tem outro MySQL ocupando a 3306). NA HOSPEDAGEM, troque aqui:
//   DB_SERVIDOR 'localhost' (a hospedagem quase sempre é esse mesmo)
//   DB_USUARIO  o usuário que a hospedagem criar pra você
//   DB_SENHA    a senha desse usuário
//   DB_NOME     o nome do banco criado no painel da hospedagem
//   DB_PORTA    3306 (o padrão de quase todas as hospedagens)
define('DB_SERVIDOR', 'localhost');
define('DB_USUARIO',  'root');
define('DB_SENHA',    '');
define('DB_NOME',     'agendamento_bemgarota');
define('DB_PORTA',    3307);

// ---- Pix (Lote H — modo barbearia) — VALORES DE TESTE DO PILOTO ----
// O sinal de R$ 20 é pago direto na SUA chave Pix: sem CPF das
// clientes, sem provedor, sem taxa. O código "copia e cola" é
// montado pelo includes/pix_estatico.php a partir destas 3 linhas.
// PILOTO: os valores abaixo são de TESTE. Quando você fechar as
// informações de verdade, me passe (ou troque você mesma): mude
// AQUI, em um lugar só, e o sistema inteiro obedece.
define('PIX_CHAVE',  '+5513988314481');          // chave = telefone com +55
define('PIX_NOME',   'STUDIO BEM GAROTA');       // até 25 letras, SEM acentos
define('PIX_CIDADE', 'PRAIA GRANDE');            // até 15 letras, SEM acentos

// ATENÇÃO: mudando qualquer linha aqui, o código copia-e-cola se
// atualiza sozinho; só a IMAGEM do QR (img/qr-pix.png) precisa ser
// refeita — salve o QR novo direto do app do seu banco.
