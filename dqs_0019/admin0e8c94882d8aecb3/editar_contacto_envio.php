<?php
/** Edición Oro + FORM, limitada por diseño al staging pre_*. */
if (!isset($conn) || !$conn instanceof mysqli) { http_response_code(500); echo '<p>No se pudo abrir la edición.</p>'; return; }
require_once __DIR__ . '/../includes/admin_feature_guard.php';
require_once __DIR__ . '/includes/guest_create_shared.php';
dqs_require_admin_contactos_envio($conn);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['guest_create_csrf'])) $_SESSION['guest_create_csrf'] = bin2hex(random_bytes(32));

$rawId = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['id'] ?? null) : ($_GET['id'] ?? null);
$id = filter_var($rawId, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if ($id === false) { http_response_code(400); echo '<div role="alert">El identificador del contacto no es válido.</div>'; return; }
$contract = dqs_guest_contract($conn, 'contacto_envio');
if (isset($contract['error'])) { echo '<div role="alert">'.htmlspecialchars($contract['error'],ENT_QUOTES,'UTF-8').'</div>'; return; }

$select = array_values(array_intersect(['id','nombre','apellido','nombre_invitado','acompanado','cantidad_mayores','cantidad_menores','id_prioridad','ingreso','cantidad_acompanantes','total_personas'], $contract['main_columns']));
$stmt=$conn->prepare('SELECT `'.implode('`,`',$select).'` FROM `pre_invitados` WHERE `id` = ? LIMIT 1'); $stmt->bind_param('i',$id); $stmt->execute(); $contact=$stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$contact) { http_response_code(404); echo '<div role="alert">El contacto de envío solicitado no existe.</div>'; return; }

$phones=[]; $stmt=$conn->prepare('SELECT `'.$contract['phone_value'].'` AS value FROM `pre_invitados_tel` WHERE `'.$contract['phone_fk'].'` = ? ORDER BY `id` ASC'); $stmt->bind_param('i',$id); $stmt->execute(); $r=$stmt->get_result(); while($row=$r->fetch_assoc()) if(trim((string)$row['value'])!=='')$phones[]=$row['value']; $stmt->close();
$memberSelect=array_values(array_intersect(['nombre','apellido','nombre_invitado','nombre2','apellido2','apodo','es_menor','orden'], $contract['member_columns']));
$members=[]; $order=in_array('orden',$contract['member_columns'],true)?'`orden` ASC, `id` ASC':'`id` ASC';
$stmt=$conn->prepare('SELECT `'.implode('`,`',$memberSelect).'` FROM `pre_invitados_listado_mesa` WHERE `'.$contract['member_fk'].'` = ? ORDER BY '.$order); $stmt->bind_param('i',$id); $stmt->execute(); $r=$stmt->get_result(); while($row=$r->fetch_assoc())$members[]=$row; $stmt->close();
$queue=dqs_guest_contact_queue_status($conn,$id);
$blockedMessage=$queue['error'] ?: 'Este contacto ya está en una cola de envío o fue enviado. Para evitar inconsistencias, no puede editarse en esta fase.';
if ($queue['blocked']) { echo '<div role="alert"><strong>'.htmlspecialchars($blockedMessage,ENT_QUOTES,'UTF-8').'</strong></div><p><a href="?new=contactos_envio">Volver</a></p>'; return; }
if (count($phones) > max(1,count($members))) { echo '<div role="alert"><strong>No se puede editar sin riesgo de pérdida: existen teléfonos adicionales que el formulario no puede asociar a integrantes.</strong></div><p><a href="?new=contactos_envio">Volver</a></p>'; return; }

$acompananteOpciones=[]; $result=$conn->query('SELECT id, categoria_acompanante FROM intivados_acompanante'); while($result&&$row=$result->fetch_assoc())$acompananteOpciones[]=$row;
$prioridadOpciones=[]; $result=$conn->query('SELECT id, categoria_prioridad FROM invitados_prioridad'); while($result&&$row=$result->fetch_assoc())$prioridadOpciones[]=$row;
$initial=['nombre'=>$contact['nombre']??'', 'apellido'=>$contact['apellido']??'', 'nombre_invitado'=>'', 'telefonos'=>$phones,
 'acompanado'=>$contact['acompanado']??($acompananteOpciones[0]['id']??''), 'cantidad_mayores'=>$contact['cantidad_mayores']??($contact['total_personas']??1), 'cantidad_menores'=>$contact['cantidad_menores']??0,
 'ingreso'=>$contact['ingreso']??'Inicio', 'id_prioridad'=>$contact['id_prioridad']??($prioridadOpciones[0]['id']??''), 'titular_es_menor'=>0, 'acompanantes'=>[]];
if ($members) {
    $holder=array_shift($members);
    if (trim((string)($holder['nombre2']??''))!=='') $initial['nombre']=$holder['nombre2'];
    elseif ($initial['nombre']==='') $initial['nombre']=$holder['nombre']??'';
    if (trim((string)($holder['apellido2']??''))!=='') $initial['apellido']=$holder['apellido2'];
    elseif ($initial['apellido']==='') $initial['apellido']=$holder['apellido']??'';
    $initial['nombre_invitado']=trim((string)($holder['nombre_invitado']??''))!==''?$holder['nombre_invitado']:($holder['apodo']??$initial['nombre']);
    $initial['titular_es_menor']=(int)($holder['es_menor']??0);
}
foreach($members as $i=>$member) $initial['acompanantes'][]=['nombre'=>trim((string)($member['nombre2']??''))!==''?$member['nombre2']:($member['nombre']??''),'apellido'=>trim((string)($member['apellido2']??''))!==''?$member['apellido2']:($member['apellido']??''),'apodo'=>trim((string)($member['nombre_invitado']??''))!==''?$member['nombre_invitado']:($member['apodo']??($member['nombre']??'')),'telefono'=>$phones[$i+1]??'','es_menor'=>(int)($member['es_menor']??0)];

$formMessage='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    dqs_require_admin_contactos_envio($conn);
    if (!hash_equals($_SESSION['guest_create_csrf'],(string)($_POST['csrf_token']??''))) $formMessage='Error: la sesión del formulario venció.';
    else { $saved=dqs_guest_update_contact($conn,$id,$_POST); $formMessage=$saved['error']; if($formMessage===''){$_SESSION['contactos_envio_mensaje']='El contacto de envío se actualizó correctamente.'; header('Location: ?new=contactos_envio'); exit();} }
    $initial=['nombre'=>$_POST['nombre']??'','apellido'=>$_POST['apellido']??'','nombre_invitado'=>$_POST['nombre_invitado']??'','telefonos'=>is_array($_POST['telefonos']??null)?$_POST['telefonos']:[], 'acompanado'=>$_POST['acompanado']??'', 'cantidad_mayores'=>$_POST['cantidad_mayores']??0,'cantidad_menores'=>$_POST['cantidad_menores']??0,'ingreso'=>$_POST['ingreso']??'Inicio','id_prioridad'=>$_POST['id_prioridad']??'','titular_es_menor'=>isset($_POST['titular_es_menor'])?1:0,'acompanantes'=>[]];
    foreach((array)($_POST['acompanante_nombre']??[]) as $i=>$name)$initial['acompanantes'][]=['nombre'=>$name,'apellido'=>$_POST['acompanante_apellido'][$i]??'','apodo'=>$_POST['acompanante_apodo'][$i]??'','telefono'=>$_POST['telefonos'][$i+1]??'','es_menor'=>(int)($_POST['acompanante_es_menor'][$i]??0)];
}
$formTitle='Editar contacto de envío'; $formNotice='Este contacto se usará para envío de invitaciones. No confirma asistencia RSVP.';
$formAction='?new=contactos_envio&accion=editar&id='.$id; $cancelUrl='?new=contactos_envio'; $csrfToken=$_SESSION['guest_create_csrf']; $formInitial=$initial; $formHidden=['id'=>$id];
require __DIR__.'/includes/guest_form.php';
