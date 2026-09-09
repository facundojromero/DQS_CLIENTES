ALTER TABLE invitados_listado_mesa
  ADD COLUMN es_menor TINYINT(1) NOT NULL DEFAULT 0 AFTER nombre_invitado;

ALTER TABLE invitados_listado_mesa
  ADD COLUMN asiste TINYINT(1) NULL DEFAULT NULL AFTER es_menor,
  ADD COLUMN confirm_date TIMESTAMP NULL DEFAULT NULL AFTER asiste;
