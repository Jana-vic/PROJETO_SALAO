-- =====================================================
-- demo.sql  —  dados de exemplo para a apresentação
-- Rode DEPOIS do banco.sql, só no banco de demonstração.
-- As datas usam CURDATE(), então os horários ficam sempre
-- nos próximos dias (bom para mostrar o calendário cheio).
-- =====================================================

USE agendamento_bemgarota;

SET NAMES utf8mb4;

-- ---------- Clientes de exemplo ----------
INSERT IGNORE INTO clientes (id, nome, whatsapp) VALUES
(1, 'Ana Beatriz Souza',   '13991110001'),
(2, 'Bianca Martins',      '13991110002'),
(3, 'Carla Nogueira',      '13991110003'),
(4, 'Daniela Rocha',       '13991110004'),
(5, 'Eduarda Lima',        '13991110005'),
(6, 'Fernanda Alves',      '13991110006');

-- ---------- Agendamentos de exemplo ----------
-- confirmado = sinal já pago | pendente = aguardando Pix
INSERT IGNORE INTO agendamentos
    (id, cliente_id, `data`, `hora`, status,
     valor_servico, valor_sinal, valor_restante, expira_em, pago_em)
VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'confirmado', 180.00, 20.00, 160.00, NULL, NOW()),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', 'confirmado', 180.00, 20.00, 160.00, NULL, NOW()),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '09:00:00', 'confirmado', 180.00, 20.00, 160.00, NULL, NOW()),
(4, 4, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '16:00:00', 'confirmado', 180.00, 20.00, 160.00, NULL, NOW()),
(5, 5, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:00:00', 'pendente',   180.00, 20.00, 160.00, DATE_ADD(NOW(), INTERVAL 45 MINUTE), NULL),
(6, 6, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '11:00:00', 'pendente',   180.00, 20.00, 160.00, DATE_ADD(NOW(), INTERVAL 45 MINUTE), NULL);

-- ---------- Horários bloqueados de exemplo ----------
INSERT IGNORE INTO bloqueios (id, `data`, `hora`, motivo) VALUES
(1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:00:00', 'Compromisso pessoal'),
(2, DATE_ADD(CURDATE(), INTERVAL 6 DAY), '17:00:00', 'Curso de atualização');

-- ---------- Recadinhos de exemplo ----------
INSERT IGNORE INTO recadinhos (id, cliente_id, mensagem, lida) VALUES
(1, 1, 'Oi! Quero alongamento em fibra no formato amendoado, tom nude. Pode ser?', 'nao'),
(2, 3, 'Tenho alergia a esmalte com formol. Pode usar outro produto?', 'nao');
