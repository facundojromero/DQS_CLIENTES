-- DQS canonical required technical seed v0.1.1 (UNI-048.1.1)
-- Deterministic catalogs and configuration for a clean installation only.
-- No users, customer records, guests, gifts, products, credentials, or contact/banking data.

START TRANSACTION;

-- Public feature-section switches.
INSERT INTO `info_mostrar` (`id`, `seccion`, `activo`) VALUES
  (1, 'about', 1),
  (2, 'story', 1),
  (3, 'gallery', 1),
  (4, 'events', 1),
  (5, 'wedding', 1),
  (6, 'contact', 1),
  (7, 'cronometro', 1),
  (8, 'logo', 1);

-- RSVP companion catalog. IDs are part of the historical application contract.
INSERT INTO `intivados_acompanante` (`id`, `categoria_acompanante`) VALUES
  (1, 'Solo/a'),
  (2, 'Flia'),
  (3, 'Novio/a'),
  (4, 'Sr/a'),
  (5, 'Amigo/a');

-- RSVP priority catalog. categoria_precio is required by the canonical snapshot
-- but unused by current application reads; zero is the neutral technical value.
INSERT INTO `invitados_prioridad` (`id`, `categoria_prioridad`, `categoria_precio`) VALUES
  (1, 'Importante', 0),
  (2, 'Medio Importante', 0),
  (3, 'Normal', 0),
  (4, 'No necesario', 0);

-- Defaults confirmed by DQS_PLAN_CONFIG_DEFAULTS and its allowed-value contract
-- in includes/plan_config.php. RSVP form persistence remains disabled by default.
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
  ('plan_servicio', 'oro'),
  ('rsvp_modo', 'codigo'),
  ('fuente_envios_whatsapp', 'invitados'),
  ('whatsapp_enabled', '1'),
  ('regalos_enabled', '1'),
  ('rsvp_form_persist_enabled', '0');

COMMIT;
