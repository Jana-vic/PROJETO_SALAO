<?php
// =====================================================
// includes/pix_estatico.php  —  Lote H (modo barbearia)
// Monta o código Pix "copia e cola" a partir das três
// informações guardadas no includes/config.php:
// PIX_CHAVE, PIX_NOME e PIX_CIDADE (e o VALOR_SINAL).
//
// É matemática pura, sem internet e sem provedor:
// o código é uma FILEIRA DE ETIQUETAS numeradas, e a
// última etiqueta é uma "conta de verificação" (CRC).
// O banco do app da cliente repete a conta antes de
// aceitar o código — igual ao dígito verificador do CPF.
//
// Quem usa: pagamento.php (mostra o código e a chave).
// =====================================================

// -----------------------------------------------------
// pix_etiqueta — monta UMA etiqueta no formato TLV:
//   2 dígitos do ID + 2 dígitos do tamanho + o conteúdo.
// Ex.: ID 59, conteúdo 'JANA' → '5904JANA'
// ('59' = o ID, '04' = o tamanho, 'JANA' = o texto)
// -----------------------------------------------------
function pix_etiqueta($id, $conteudo)
{
    return $id . sprintf('%02d', strlen($conteudo)) . $conteudo;
}

// -----------------------------------------------------
// pix_crc16 — a "conta de verificação" que fecha o código.
// O Banco Central pede a conta chamada CRC16/CCITT-FALSE:
// uma mistura de letras e bits que sempre dá 4 caracteres.
// Se qualquer letra do código mudar, a conta muda — e o
// banco recusa (é como detectar bilhete de loteria falso).
// -----------------------------------------------------
function pix_crc16($texto)
{
    $crc = 0xFFFF;                        // a "semente" da conta
    for ($i = 0; $i < strlen($texto); $i++) {
        $crc ^= ord($texto[$i]) << 8;     // entra uma letra na conta
        for ($bit = 0; $bit < 8; $bit++) { // 8 voltas de mistura por letra
            if ($crc & 0x8000) {
                $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
            } else {
                $crc = ($crc << 1) & 0xFFFF;
            }
        }
    }
    return strtoupper(sprintf('%04X', $crc));
}

// -----------------------------------------------------
// pix_ajeitar — o padrão do Pix NÃO aceita acentos nem
// letras minúsculas no nome/cidade (para funcionar em
// qualquer banco e qualquer país, é tudo maiúsculo).
// -----------------------------------------------------
function pix_ajeitar($texto)
{
    // troca acentos por letras simples (Á vira A)...
    $semAcentos = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    if ($semAcentos === false) {
        $semAcentos = $texto;   // se o servidor não souber, segue como está
    }
    // ...espaços duplos viram um só, e tudo vira MAIÚSCULO
    $limpo = preg_replace('/\s+/', ' ', trim($semAcentos));
    return mb_strtoupper($limpo);
}

// -----------------------------------------------------
// pix_copia_e_cola — monta o código completo. As etiquetas:
//   00  = versão do padrão (é sempre "01")
//   26  = a chave Pix (o "endereço" do dinheiro)
//   52  = ramo de atividade (0000 = genérico)
//   53  = moeda (986 = Real brasileiro)
//   54  = o valor já embutido (o sinal de R$ 20)
//   58  = país (BR)
//   59  = nome de quem recebe (PIX_NOME do config)
//   60  = cidade de quem recebe (PIX_CIDADE do config)
//   62  = campo extra dos bancos
//   63  = a conta de verificação (CRC) — sempre a última
// -----------------------------------------------------
function pix_copia_e_cola()
{
    $nome   = substr(pix_ajeitar(PIX_NOME), 0, 25);    // limite do padrão: 25
    $cidade = substr(pix_ajeitar(PIX_CIDADE), 0, 15);  // limite do padrão: 15

    $corpo = pix_etiqueta('00', '01')
        . pix_etiqueta('26',
              pix_etiqueta('00', 'br.gov.bcb.pix')
            . pix_etiqueta('01', PIX_CHAVE))
        . pix_etiqueta('52', '0000')
        . pix_etiqueta('53', '986')
        . pix_etiqueta('54', number_format((float) VALOR_SINAL, 2, '.', ''))
        . pix_etiqueta('58', 'BR')
        . pix_etiqueta('59', $nome)
        . pix_etiqueta('60', $cidade)
        . pix_etiqueta('62', pix_etiqueta('05', '***'));

    // A conta de verificação corre por cima de tudo,
    // inclusive sobre o "6304" que anuncia: "vem a conta aí"
    return $corpo . '6304' . pix_crc16($corpo . '6304');
}

// -----------------------------------------------------
// pix_chave_formatada — a chave "crua" (+5513988314481)
// virada em texto de gente: (13) 98831-4481
// -----------------------------------------------------
function pix_chave_formatada()
{
    $numeros = preg_replace('/\D/', '', PIX_CHAVE);   // só sobram os números
    // 5513988314481 → 55 (país) 13 (DDD) 98831 4481
    return '(' . substr($numeros, 2, 2) . ') '
         . substr($numeros, 4, 5) . '-' . substr($numeros, 9);
}
