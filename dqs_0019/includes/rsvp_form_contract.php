<?php
/**
 * UNI-032: contrato puro para RSVP por formulario adaptable a configuración admin.
 */

declare(strict_types=1);

const DQS_RSVP_FORM_MAX_ACOMPANANTES = 20;
const DQS_RSVP_FORM_MAX_COMENTARIO = 500;

function dqs_rsvp_form_allowed_restrictions(): array
{
    return ['No', 'Ninguna', 'Vegetariano', 'Vegano', 'Celíaco', 'Otros', 'Otro'];
}

function dqs_rsvp_form_contract_schema(): array
{
    return [
        'version' => 'UNI-032',
        'description' => 'Contrato interno puro para normalizar y validar payloads RSVP por formulario con configuración admin.',
        'side_effects' => ['opens_database_connection'=>false,'reads_database'=>false,'writes_database'=>false,'calls_endpoints'=>false,'executes_whatsapp_or_node'=>false],
        'principal_fields' => [
            'nombre'=>['type'=>'string','required'=>true], 'apellido'=>['type'=>'string','required'=>true],
            'telefono'=>['type'=>'string','required'=>false], 'confirmacion'=>['type'=>'enum','allowed'=>['Si','No'],'required'=>true],
            'alimento/restriccion_alimentaria'=>['type'=>'enum','allowed'=>dqs_rsvp_form_allowed_restrictions()],
            'alimento_comentario'=>['type'=>'string','required_when'=>'alimento=Otros','max_length'=>DQS_RSVP_FORM_MAX_COMENTARIO],
            'mensaje_general/comentario'=>['type'=>'string','required'=>false,'max_length'=>DQS_RSVP_FORM_MAX_COMENTARIO],
            'cantidad_adultos'=>['type'=>'integer','min'=>1,'max'=>'1 + rsvp_form_max_adult_companions'],
            'cantidad_menores'=>['type'=>'integer','min'=>0,'max'=>'rsvp_form_max_minors'],
            'cantidad_acompanantes'=>['type'=>'integer','legacy'=>true,'meaning'=>'adult companions only'],
            'adultos/acompanantes_adultos/acompanantes'=>['type'=>'array','effective_only_when'=>'confirmacion=Si'],
            'menores'=>['type'=>'array','effective_only_when'=>'confirmacion=Si'],
        ],
        'person_fields' => ['nombre'=>['type'=>'string','required'=>true],'apellido'=>['type'=>'string','required'=>true],'alimento/restriccion_alimentaria'=>['type'=>'enum','allowed'=>dqs_rsvp_form_allowed_restrictions()],'alimento_comentario'=>['type'=>'string','required_when'=>'alimento=Otros']],
        'when_confirmacion_no' => 'Cantidades efectivas 0; se ignoran adultos/menores y solo se inserta titular con asiste=0.',
    ];
}

function dqs_rsvp_form_default_config(): array
{
    return defined('DQS_PLAN_CONFIG_DEFAULTS') ? DQS_PLAN_CONFIG_DEFAULTS : [
        'rsvp_form_adult_companions_enabled'=>'1','rsvp_form_max_adult_companions'=>'1','rsvp_form_minors_enabled'=>'0','rsvp_form_max_minors'=>'0','rsvp_form_food_enabled'=>'1','rsvp_form_phone_visible'=>'0','rsvp_form_general_message_enabled'=>'0',
    ];
}

function dqs_rsvp_form_effective_limits(array $config = []): array
{
    $config = array_merge(dqs_rsvp_form_default_config(), $config);
    $adultMax = ($config['rsvp_form_adult_companions_enabled'] ?? '1') === '1' ? max(0, min(DQS_RSVP_FORM_MAX_ACOMPANANTES, (int)($config['rsvp_form_max_adult_companions'] ?? 0))) : 0;
    $minorMax = ($config['rsvp_form_minors_enabled'] ?? '0') === '1' ? max(0, min(DQS_RSVP_FORM_MAX_ACOMPANANTES, (int)($config['rsvp_form_max_minors'] ?? 0))) : 0;
    return ['adult_companions_enabled'=>$adultMax > 0,'max_adult_companions'=>$adultMax,'max_adults'=>1 + $adultMax,'minors_enabled'=>$minorMax > 0,'max_minors'=>$minorMax,'food_enabled'=>($config['rsvp_form_food_enabled'] ?? '1') === '1','phone_visible'=>($config['rsvp_form_phone_visible'] ?? '0') === '1','general_message_enabled'=>($config['rsvp_form_general_message_enabled'] ?? '0') === '1'];
}

function dqs_rsvp_form_normalize_payload(array $payload, array $config = []): array
{
    $limits = dqs_rsvp_form_effective_limits($config);
    $confirmacion = dqs_rsvp_form_normalize_confirmacion($payload['confirmacion'] ?? '');
    $legacyCount = array_key_exists('cantidad_acompanantes', $payload);
    $rawAdults = $legacyCount ? 1 + dqs_rsvp_form_normalize_count($payload['cantidad_acompanantes'] ?? 0) : dqs_rsvp_form_normalize_count($payload['cantidad_adultos'] ?? 1);
    $rawMinors = dqs_rsvp_form_normalize_count($payload['cantidad_menores'] ?? 0);
    if ($confirmacion === 'No') { $rawAdults = 0; $rawMinors = 0; }
    $adultCompanionsCount = $confirmacion === 'Si' ? max(0, $rawAdults - 1) : 0;
    $adultSource = dqs_rsvp_form_first_array($payload, ['adultos','acompanantes_adultos','acompanantes']);
    $minorSource = isset($payload['menores']) && is_array($payload['menores']) ? $payload['menores'] : [];
    $adults = $confirmacion === 'Si' ? dqs_rsvp_form_normalize_people($adultSource, $adultCompanionsCount, $limits) : [];
    $minors = $confirmacion === 'Si' ? dqs_rsvp_form_normalize_people($minorSource, $rawMinors, $limits) : [];
    $principalFood = dqs_rsvp_form_normalize_food($payload, $limits);
    return [
        'principal'=>['nombre'=>dqs_rsvp_form_trim_string($payload['nombre'] ?? ''),'apellido'=>dqs_rsvp_form_trim_string($payload['apellido'] ?? ''),'telefono'=>$limits['phone_visible'] ? dqs_rsvp_form_trim_string($payload['telefono'] ?? '') : '','confirmacion'=>$confirmacion,'restriccion_alimentaria'=>$principalFood['alimento'],'alimento'=>$principalFood['alimento'],'alimento_comentario'=>$principalFood['alimento_comentario'],'comentario'=>$limits['general_message_enabled'] ? dqs_rsvp_form_trim_string($payload['mensaje_general'] ?? ($payload['comentario'] ?? '')) : ''],
        'cantidad_adultos'=>$rawAdults,'cantidad_menores'=>$rawMinors,'cantidad_acompanantes'=>$adultCompanionsCount,
        'adultos'=>$adults,'acompanantes'=>$adults,'menores'=>$minors,
        'totales'=>['total_personas'=>$confirmacion === 'Si' ? $rawAdults + $rawMinors : 0,'total_adultos'=>$confirmacion === 'Si' ? $rawAdults : 0,'total_menores'=>$confirmacion === 'Si' ? $rawMinors : 0,'total_acompanantes'=>$adultCompanionsCount + count($minors)],
        'config'=>$limits,
    ];
}

function dqs_rsvp_form_validate_payload(array $payload, array $config = []): array
{
    $n = dqs_rsvp_form_normalize_payload($payload, $config); $e=[]; $w=[]; $limits=$n['config']; $p=$n['principal'];
    if (!in_array($p['confirmacion'], ['Si','No'], true)) $e[]=['field'=>'confirmacion','message'=>'La confirmación debe ser Si o No.'];
    foreach (['nombre','apellido'] as $f) if ($p[$f] === '') $e[]=['field'=>$f,'message'=>'El '.($f==='nombre'?'nombre':'apellido').' del invitado principal es requerido.'];
    foreach (['cantidad_adultos','cantidad_menores'] as $f) if (isset($payload[$f]) && !dqs_rsvp_form_is_integer_like($payload[$f])) $e[]=['field'=>$f,'message'=>'La cantidad debe ser un entero.'];
    if (isset($payload['cantidad_acompanantes']) && !dqs_rsvp_form_is_integer_like($payload['cantidad_acompanantes'])) $e[]=['field'=>'cantidad_acompanantes','message'=>'La cantidad de acompañantes debe ser un entero.'];
    if ($p['confirmacion'] === 'Si') {
        if ($n['cantidad_adultos'] < 1 || $n['cantidad_adultos'] > $limits['max_adults']) $e[]=['field'=>'cantidad_adultos','message'=>'La cantidad de adultos debe estar entre 1 y '.$limits['max_adults'].'.'];
        if (!$limits['adult_companions_enabled'] && $n['cantidad_acompanantes'] > 0) $e[]=['field'=>'adultos','message'=>'Los acompañantes adultos no están habilitados.'];
        if ($n['cantidad_menores'] < 0 || $n['cantidad_menores'] > $limits['max_minors']) $e[]=['field'=>'cantidad_menores','message'=>'La cantidad de menores debe estar entre 0 y '.$limits['max_minors'].'.'];
        if (!$limits['minors_enabled'] && $n['cantidad_menores'] > 0) $e[]=['field'=>'menores','message'=>'Los menores no están habilitados.'];
        dqs_rsvp_form_validate_person_food($p, 'principal', $limits, $e);
        foreach (['adultos'=>$n['adultos'],'menores'=>$n['menores']] as $group=>$people) foreach ($people as $i=>$person) { $prefix=$group.'.'.($i+1); if ($person['nombre']==='') $e[]=['field'=>$prefix.'.nombre','message'=>'Nombre requerido.']; if ($person['apellido']==='') $e[]=['field'=>$prefix.'.apellido','message'=>'Apellido requerido.']; dqs_rsvp_form_validate_person_food($person, $prefix, $limits, $e); }
    }
    if (mb_strlen($p['comentario']) > DQS_RSVP_FORM_MAX_COMENTARIO) $e[]=['field'=>'mensaje_general','message'=>'El mensaje general no puede superar 500 caracteres.'];
    if (!$limits['phone_visible'] && ($payload['telefono'] ?? '') !== '') $w[]=['field'=>'telefono','message'=>'El teléfono enviado se ignora porque el campo está oculto.'];
    if ($p['confirmacion'] === 'No' && ((isset($payload['acompanantes']) && is_array($payload['acompanantes']) && count($payload['acompanantes'])>0) || (isset($payload['menores']) && is_array($payload['menores']) && count($payload['menores'])>0))) $w[]=['field'=>'acompanantes','message'=>'Los acompañantes/menores enviados se ignoran porque la confirmación es No.'];
    return ['valid'=>count($e)===0,'errors'=>$e,'warnings'=>$w,'normalized'=>$n];
}

function dqs_rsvp_form_normalize_people(array $rawPeople, int $count, array $limits): array { $out=[]; $zeroBased=array_key_exists(0,$rawPeople); for ($i=1;$i<=$count;$i++){ $raw=[]; $key=$zeroBased ? $i-1 : $i; if(isset($rawPeople[$key])&&is_array($rawPeople[$key]))$raw=$rawPeople[$key]; $food=dqs_rsvp_form_normalize_food($raw,$limits); $out[]=['nombre'=>dqs_rsvp_form_trim_string($raw['nombre'] ?? ''),'apellido'=>dqs_rsvp_form_trim_string($raw['apellido'] ?? ''),'restriccion_alimentaria'=>$food['alimento'],'alimento'=>$food['alimento'],'alimento_comentario'=>$food['alimento_comentario'],'comentario'=>$food['alimento_comentario']]; } return $out; }
function dqs_rsvp_form_normalize_food(array $raw, array $limits): array { if (!$limits['food_enabled']) return ['alimento'=>'No','alimento_comentario'=>'']; $a=dqs_rsvp_form_trim_string($raw['alimento'] ?? ($raw['restriccion_alimentaria'] ?? 'No')); if ($a === 'Ninguna') $a='No'; if ($a === 'Otro') $a='Otros'; return ['alimento'=>$a,'alimento_comentario'=>dqs_rsvp_form_trim_string($raw['alimento_comentario'] ?? ($raw['comentario'] ?? ''))]; }
function dqs_rsvp_form_validate_person_food(array $person, string $prefix, array $limits, array &$errors): void { if (!$limits['food_enabled']) return; if (!in_array($person['alimento'] ?? 'No', dqs_rsvp_form_allowed_restrictions(), true)) $errors[]=['field'=>$prefix.'.alimento','message'=>'La restricción alimentaria no es válida.']; if (($person['alimento'] ?? '') === 'Otros' && ($person['alimento_comentario'] ?? '') === '') $errors[]=['field'=>$prefix.'.alimento_comentario','message'=>'El comentario alimentario es requerido cuando alimento es Otros.']; if (mb_strlen($person['alimento_comentario'] ?? '') > DQS_RSVP_FORM_MAX_COMENTARIO) $errors[]=['field'=>$prefix.'.alimento_comentario','message'=>'El comentario alimentario no puede superar 500 caracteres.']; }
function dqs_rsvp_form_first_array(array $payload, array $keys): array { foreach ($keys as $key) if (isset($payload[$key]) && is_array($payload[$key])) return $payload[$key]; return []; }
function dqs_rsvp_form_trim_string($value): string { if (is_array($value) || is_object($value)) return ''; return trim((string)$value); }
function dqs_rsvp_form_normalize_confirmacion($value): string { $normalized=mb_strtolower(dqs_rsvp_form_trim_string($value)); $normalized=str_replace(['í','ì'],'i',$normalized); if (in_array($normalized,['si','sí','s','yes','1','true'],true)) return 'Si'; if (in_array($normalized,['no','n','0','false'],true)) return 'No'; return dqs_rsvp_form_trim_string($value); }
function dqs_rsvp_form_normalize_count($value): int { if (!dqs_rsvp_form_is_integer_like($value)) return 0; return max(0, min(DQS_RSVP_FORM_MAX_ACOMPANANTES, (int)$value)); }
function dqs_rsvp_form_normalize_companion_count($value): int { return dqs_rsvp_form_normalize_count($value); }
function dqs_rsvp_form_is_integer_like($value): bool { if (is_int($value)) return true; if (is_string($value)) return preg_match('/^-?\d+$/', trim($value)) === 1; return false; }
function dqs_rsvp_form_persistence_plan(): array { return ['executable'=>false,'status'=>'documental_only','summary'=>'El contrato es side-effect free; la persistencia final vive en includes/rsvp_form_final_persistence.php.']; }
