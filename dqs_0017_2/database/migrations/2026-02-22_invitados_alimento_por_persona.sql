ALTER TABLE invitados_listado_mesa
  ADD COLUMN alimento VARCHAR(30) NOT NULL DEFAULT 'No' AFTER confirm_date,
  ADD COLUMN alimento_comentario VARCHAR(255) NULL DEFAULT NULL AFTER alimento;
