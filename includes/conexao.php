<?php
// =====================================================
// conexao.php  —  a "tomada" que liga o PHP ao MySQL
// Toda página que precisa do banco inclui este arquivo.
// =====================================================

// Puxa o painel de configuração (valores, prazos, horários)
require_once __DIR__ . '/config.php';

// ---- Dados da conexão: LÊ do config.php (Lote J) ----
// Assim, na hospedagem, você muda UM arquivo só (o config.php)
// e esta "tomada" se adapta sozinha.
$servidor      = DB_SERVIDOR;
$usuario_banco = DB_USUARIO;
$senha_banco   = DB_SENHA;
$nome_banco    = DB_NOME;
$porta         = DB_PORTA;

// ---- Enfia a tomada na parede ----
$conexao = mysqli_connect($servidor, $usuario_banco, $senha_banco, $nome_banco, $porta);

// ---- Se não ligou, para com uma mensagem clara ----
if (!$conexao) {
    die('Não consegui conectar ao banco de dados. Verifique se o MySQL está '
      . 'rodando no XAMPP. Detalhe do erro: ' . mysqli_connect_error());
}

// ---- Ensina o banco a entender acentos e emojis ----
mysqli_set_charset($conexao, 'utf8mb4');
