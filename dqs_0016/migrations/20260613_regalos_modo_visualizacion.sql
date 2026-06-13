-- Agrega configuración para elegir cómo se muestra la sección pública de Regalos.
-- No modifica ni borra productos/regalos existentes.

ALTER TABLE cliente
    ADD COLUMN regalos_modo_visualizacion VARCHAR(30) NOT NULL DEFAULT 'productos',
    ADD COLUMN regalos_titulo VARCHAR(255) DEFAULT '¿NOS QUERÉS HACER UN REGALO?';
