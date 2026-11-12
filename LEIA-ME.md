# Studio Bem Garota — demonstração do app de agendamento

Esta pasta é uma **cópia de demonstração** do app que já existia (`studio-loiro`).
O original continua intacto em `C:\xampp\htdocs\Sites_Jana\studio-loiro`.

## Como abrir

1. Abra o XAMPP e inicie o **Apache**.
2. Inicie o **MySQL** (o XAMPP usa a porta **3307** nesta máquina).
3. Acesse:

   - Site da cliente: <http://localhost/Sites_Jana/studio-bemgarota/>
   - Painel da profissional: <http://localhost/Sites_Jana/studio-bemgarota/admin/login.php>

### Login do painel

| Usuário | Senha |
| --- | --- |
| `admin` | `admin123` |

## O que está pronto

- Home com a identidade do Studio Bem Garota (logo, paleta creme/dourado/vinho e fotos do Instagram).
- Serviços, galeria, explicação do sinal e chamada para agendar.
- Fluxo completo: cadastro → calendário → reserva → Pix com QR Code e contador.
- Painel: agenda, pedidos pendentes (com "Recebi o sinal"), clientes, recadinhos e bloqueio de horários.
- Layout responsivo (celular e computador), fontes locais (sem depender de internet).

## Dados de demonstração

O banco usado é `agendamento_bemgarota` (separado do banco original).
Ele já vem com 6 clientes, 6 agendamentos e 2 pedidos pendentes.

Os pedidos pendentes de exemplo usam `expira_em = NOW() + 45 minutos`.
Se eles desaparecerem do painel, rode novamente o arquivo:

```text
sql/demo.sql
```

no phpMyAdmin ou pela linha de comando.

## O que ainda é placeholder

- **Preços dos serviços** na home (manicure, pedicure, gel, fibra, postiça) são exemplos.
- **Chave Pix** (`+5513988314481`), nome e cidade são valores de teste.
- **Endereço/telefone** não aparecem no site; o contato principal é o Instagram.
- Textos institucionais podem ser ajustados com as palavras da própria cliente.

## Onde está o backup

```text
C:\xampp\htdocs\Sites_Jana\_backup\studio-loiro-2026-11-12-0920.zip
```

## Arquivos principais

| Arquivo | Função |
| --- | --- |
| `includes/config.php` | Valores do serviço, sinal, Pix e banco |
| `index.php` | Página inicial |
| `css/estilo.css` | Visual completo do site e do painel |
| `sql/banco.sql` | Criação do banco e login inicial |
| `sql/demo.sql` | Dados de exemplo da apresentação |
| `img/` | Logo, ícones, fotos e QR Code |
| `assets/fonts/` | Fontes usadas no site |

## Observação para publicar

Quando o site for para um domínio próprio, troque em `includes/config.php`:

```php
define('BASE_URL', '');
```

e ajuste os dados de acesso ao banco (`DB_HOST`, `DB_PORTA`, `DB_USUARIO`, `DB_SENHA`, `DB_NOME`)
para os da hospedagem.
