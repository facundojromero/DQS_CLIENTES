<?php
/** UNI-022 CLI read-only probe de persistencia final RSVP formulario. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
if (function_exists('mysqli_report')) { mysqli_report(MYSQLI_REPORT_OFF); }
require_once __DIR__ . '/../includes/plan_config.php';
require_once __DIR__ . '/../includes/rsvp_form_contract.php';
require_once __DIR__ . '/../includes/rsvp_form_final_persistence.php';

function dqs_uni022_usage(): string { return implode(PHP_EOL, [
    'Uso: php tools/dqs_rsvp_form_final_persistence_probe.php [opciones]', '',
    '  --help                 Muestra esta ayuda.',
    '  --status               Config efectiva, schema resumido y conteos.',
    '  --schema               Diagnóstico de schema invitados*.',
    '  --sample=empty         Valida payload vacío sin escritura.',
    '  --sample=valid         Preview válido sin escritura.',
    '  --sample=no            Preview No asiste sin escritura.',
    '  --sample=companions    Preview con acompañantes adultos sin escritura.',
    '  --sample=minors        Preview con menores sin escritura.',
    '  --sample=adults_minors Preview con adultos y menores sin escritura.',
    '  --sample=food_other    Preview alimento Otros con comentario.',
    '  --sample=over_limit    Error esperado por superar límites.',
    '  --sample=disabled_minors Error esperado por menores deshabilitados.', '',
    'UNI-022: read-only; no inserta, no actualiza, no borra, no envía WhatsApp/emails.'
]) . PHP_EOL; }
function dqs_uni022_samples(): array { return [
    'empty' => [],
    'valid' => ['nombre'=>'Ana','apellido'=>'García','telefono'=>'+5491100000000','confirmacion'=>'Si','alimento'=>'No','mensaje_general'=>'Preview UNI-032','cantidad_adultos'=>1,'cantidad_menores'=>0,'adultos'=>[],'menores'=>[]],
    'no' => ['nombre'=>'Luis','apellido'=>'Pérez','telefono'=>'','confirmacion'=>'No','alimento'=>'No','mensaje_general'=>'No podré asistir.','cantidad_adultos'=>3,'cantidad_menores'=>2,'adultos'=>[['nombre'=>'Ignorado','apellido'=>'Uno']], 'menores'=>[['nombre'=>'Ignorado','apellido'=>'Menor']]],
    'companions' => ['nombre'=>'María','apellido'=>'López','telefono'=>'+5491122222222','confirmacion'=>'Si','alimento'=>'Vegetariano','alimento_comentario'=>'','cantidad_adultos'=>3,'cantidad_menores'=>0,'adultos'=>[ ['nombre'=>'Carlos','apellido'=>'López','alimento'=>'No','alimento_comentario'=>''], ['nombre'=>'Sofía','apellido'=>'López','alimento'=>'Celíaco','alimento_comentario'=>'Sin gluten.']]],
    'minors' => ['nombre'=>'Julia','apellido'=>'Núñez','telefono'=>'','confirmacion'=>'Si','alimento'=>'No','cantidad_adultos'=>1,'cantidad_menores'=>2,'menores'=>[ ['nombre'=>'Mateo','apellido'=>'Núñez','alimento'=>'No'], ['nombre'=>'Emma','apellido'=>'Núñez','alimento'=>'Vegano']]],
    'adults_minors' => ['nombre'=>'Pedro','apellido'=>'Rossi','telefono'=>'','confirmacion'=>'Si','alimento'=>'No','cantidad_adultos'=>2,'cantidad_menores'=>2,'acompanantes_adultos'=>[ ['nombre'=>'Laura','apellido'=>'Rossi','alimento'=>'Vegetariano'] ],'menores'=>[ ['nombre'=>'Tomás','apellido'=>'Rossi','alimento'=>'No'], ['nombre'=>'Mora','apellido'=>'Rossi','alimento'=>'Celíaco','alimento_comentario'=>'Sin TACC']]],
    'food_other' => ['nombre'=>'Olivia','apellido'=>'Suárez','telefono'=>'','confirmacion'=>'Si','alimento'=>'Otros','alimento_comentario'=>'Alergia a frutos secos.','cantidad_adultos'=>1,'cantidad_menores'=>0],
    'over_limit' => ['nombre'=>'Max','apellido'=>'Límite','telefono'=>'','confirmacion'=>'Si','alimento'=>'No','cantidad_adultos'=>4,'cantidad_menores'=>0,'adultos'=>[ ['nombre'=>'A','apellido'=>'Uno'], ['nombre'=>'B','apellido'=>'Dos'], ['nombre'=>'C','apellido'=>'Tres']]],
    'disabled_minors' => ['nombre'=>'Mina','apellido'=>'SinMenores','telefono'=>'','confirmacion'=>'Si','alimento'=>'No','cantidad_adultos'=>1,'cantidad_menores'=>1,'menores'=>[ ['nombre'=>'Niño','apellido'=>'Uno']]],
    'legacy' => ['nombre'=>'Bruno','apellido'=>'Legacy','telefono'=>'','confirmacion'=>'Si','restriccion_alimentaria'=>'No','comentario'=>'','cantidad_acompanantes'=>1,'acompanantes'=>[1=>['nombre'=>'Lucía','apellido'=>'Legacy','restriccion_alimentaria'=>'No']]],
]; }
function dqs_uni022_conn() { $settings = ['servername'=>'','username'=>'','password'=>'','dbname'=>'']; $c=@file_get_contents(__DIR__.'/../conexion.php'); if ($c!==false) { foreach (array_keys($settings) as $n) { if (preg_match('/\$'.preg_quote($n,'/').'\s*=\s*(["\'])(.*?)\1\s*;/', $c, $m)) { $settings[$n]=$m[2]; } } } $conn=mysqli_init(); if (!$conn) return null; @$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT,3); @$conn->real_connect($settings['servername'],$settings['username'],$settings['password'],$settings['dbname']); if ($conn->connect_errno) return null; @$conn->set_charset('utf8mb4'); return $conn; }
function dqs_uni022_counts($conn): array { $out=[]; if (!$conn instanceof mysqli) return $out; foreach (['invitados','invitados_listado_mesa','invitados_tel','pre_invitados','pre_invitados_listado_mesa','pre_invitados_tel'] as $t) { $r=@$conn->query('SELECT COUNT(*) AS c FROM `'.$t.'`'); $row=$r?$r->fetch_assoc():['c'=>'ERROR']; $out[$t]=$row['c']; } return $out; }
function dqs_uni022_reason(array $config, bool $schemaReady, bool $valid): string { if (!$valid) return 'payload_invalid'; if (($config['rsvp_modo'] ?? '') !== 'form') return 'persistence_disabled_by_mode'; if (($config['rsvp_form_persist_enabled'] ?? '0') !== '1') return 'persistence_feature_disabled'; if (!$schemaReady) return 'final_persistence_schema_not_ready'; return 'final_persist_enabled_but_probe_read_only'; }
function dqs_uni022_print(array $data): void { echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL; }

$options = getopt('', ['help','status','schema','sample:']);
if (isset($options['help']) || $options === []) { echo dqs_uni022_usage(); exit(0); }
$conn = dqs_uni022_conn();
$config = dqs_get_effective_plan_config($conn instanceof mysqli ? $conn : null);
$schema = $conn instanceof mysqli ? dqs_rsvp_form_final_persistence_schema_ready($conn) : ['ready'=>false,'target'=>'invitados*','warnings'=>[['message'=>'Sin conexión DB.']]];
$schemaReady = (bool)($schema['ready'] ?? false);
$base = ['read_only'=>true,'effective_config'=>$config,'target'=>'invitados*','schema_ready'=>$schemaReady,'codigo_column'=>dqs_rsvp_form_final_persistence_code_column_diagnostic($schema),'dedupe'=>dqs_rsvp_form_final_persistence_dedupe_diagnostic($schema),'counts'=>dqs_uni022_counts($conn)];
if (isset($options['status'])) { dqs_uni022_print($base + ['would_persist'=>false,'reason'=>dqs_uni022_reason($config,$schemaReady,true)]); exit(0); }
if (isset($options['schema'])) { dqs_uni022_print($base + ['schema'=>$schema,'would_persist'=>false,'reason'=>dqs_uni022_reason($config,$schemaReady,true)]); exit(0); }
if (isset($options['sample'])) { $samples=dqs_uni022_samples(); $sample=(string)$options['sample']; if (!isset($samples[$sample])) { fwrite(STDERR,dqs_uni022_usage()); exit(1); } $sampleConfig = $config; if ($sample === 'disabled_minors') { $sampleConfig['rsvp_form_minors_enabled'] = '0'; $sampleConfig['rsvp_form_max_minors'] = '0'; } $validation=dqs_rsvp_form_validate_payload($samples[$sample], $sampleConfig); $persistable=dqs_rsvp_form_final_persistence_validate_persistable_payload($validation['normalized'] ?? []); $valid=(bool)($validation['valid'] ?? false) && (bool)($persistable['valid'] ?? false); dqs_uni022_print($base + ['sample'=>$sample,'valid'=>(bool)($validation['valid'] ?? false),'persistable'=>(bool)($persistable['valid'] ?? false),'errors'=>array_merge($validation['errors'] ?? [], $persistable['errors'] ?? []),'warnings'=>$validation['warnings'] ?? [],'normalized'=>$validation['normalized'] ?? [],'insert_preview'=>dqs_rsvp_form_final_persistence_build_insert_preview($validation['normalized'] ?? [], $schema),'would_persist'=>false,'reason'=>dqs_uni022_reason($config,$schemaReady,$valid)]); exit(0); }
fwrite(STDERR, dqs_uni022_usage()); exit(1);
