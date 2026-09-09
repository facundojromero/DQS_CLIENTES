<?php
/** Controlled Bootstrap dry-run/apply web helpers (UNI-049.6). */

require_once __DIR__ . '/install_web_gate.php';
require_once __DIR__ . '/install_web_schema.php';

function dqs_install_web_bootstrap_blocked($message)
{
    return dqs_install_web_preflight_blocked($message);
}

function dqs_install_web_bootstrap_fields()
{
    return array(
        'nombre'=>array('label'=>'Nombre','required'=>true,'maxlength'=>50),
        'apellido'=>array('label'=>'Apellido','required'=>true,'maxlength'=>50),
        'telefono'=>array('label'=>'Teléfono','required'=>false,'maxlength'=>20),
        'telefono2'=>array('label'=>'Teléfono alternativo','required'=>false,'maxlength'=>20),
        'direccion'=>array('label'=>'Dirección','required'=>false,'maxlength'=>100),
        'ciudad'=>array('label'=>'Ciudad','required'=>false,'maxlength'=>100),
        'provincia'=>array('label'=>'Provincia','required'=>false,'maxlength'=>100),
        'plan'=>array('label'=>'Plan numérico','required'=>false,'maxlength'=>10,'integer'=>true),
    );
}

function dqs_install_web_bootstrap_validate(array $input)
{
    $email=isset($input['admin_email'])&&is_string($input['admin_email'])?strtolower(trim($input['admin_email'])):'';
    $password=$input['admin_password']??null; $confirm=$input['admin_password_confirm']??null;
    if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>254 || !is_string($password) || strlen($password)<6 || strlen($password)>512
        || !is_string($confirm) || !hash_equals($password,$confirm)) return null;
    $cliente=array();
    foreach (dqs_install_web_bootstrap_fields() as $name=>$spec) {
        $value=$input['cliente_'.$name]??'';
        if (!is_string($value) || strlen($value)>$spec['maxlength'] || strpos($value,"\0")!==false || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$value)) return null;
        $value=trim($value);
        if ($spec['required'] && $value==='') return null;
        if (!empty($spec['integer']) && $value!=='' && !preg_match('/^-?\d+$/',$value)) return null;
        $cliente[$name]=$value===''&&!$spec['required']?null:$value;
    }
    return array('admin'=>array('email'=>$email,'password'=>$password,'password_confirm'=>$confirm),'cliente'=>$cliente,
        'settings'=>array('plan_servicio'=>'oro','rsvp_modo'=>'codigo','fuente_envios_whatsapp'=>'invitados','whatsapp_enabled'=>'1','regalos_enabled'=>'1','rsvp_form_persist_enabled'=>'0'));
}

function dqs_install_web_bootstrap_path($path,$runtime,$connection)
{
    if (!is_string($path)||$path===''||is_link($path)||!is_file($path)||!is_readable($path)) return null;
    $real=realpath($path); $connectionReal=realpath($connection); $sessions=realpath($runtime.DIRECTORY_SEPARATOR.'web_sessions');
    if ($real===false||$connectionReal===false||$sessions===false||basename($real)!=='bootstrap.json'||dirname($real)!==dirname($connectionReal)
        ||dirname(dirname($real))!==$sessions||!preg_match('/\A[a-f0-9]{48}\z/',basename(dirname($real)))) return null;
    return $real;
}

function dqs_install_web_bootstrap_cleanup($path,$runtime)
{
    if (!is_string($path)||$path===''||is_link($path)) return;
    $sessions=realpath($runtime.DIRECTORY_SEPARATOR.'web_sessions'); $parent=realpath(dirname($path));
    if ($sessions!==false&&$parent!==false&&dirname($parent)===$sessions&&basename($path)==='bootstrap.json'&&is_file($path)) @unlink($path);
}

function dqs_install_web_bootstrap_create($runtime,$connection,array $data)
{
    $connection=dqs_install_web_schema_connection($connection,$runtime); if($connection===null)return null;
    $path=dirname($connection).DIRECTORY_SEPARATOR.'bootstrap.json'; dqs_install_web_bootstrap_cleanup($path,$runtime);
    $previousUmask=umask(0177);
    $handle=@fopen($path,'x');
    umask($previousUmask);
    if($handle===false)return null;
    if(is_link($path)||!@chmod($path,0600)){fclose($handle);@unlink($path);return null;}
    clearstatcache(true,$path);
    if((fileperms($path)&0777)!==0600){fclose($handle);@unlink($path);return null;}
    $json=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); $written=fwrite($handle,$json); fflush($handle); fclose($handle);
    clearstatcache(true,$path);
    if($written!==strlen($json)||is_link($path)||(fileperms($path)&0777)!==0600){@unlink($path);return null;} return $path;
}

function dqs_install_web_bootstrap_fingerprint($connection,$bootstrap,$createdAt)
{
    return hash('sha256',"bootstrap\0".hash_file('sha256',$connection)."\0".hash_file('sha256',$bootstrap)."\0".$createdAt);
}

function dqs_install_web_bootstrap_run($mode,$connection,$bootstrap)
{
    $params=array('mode'=>$mode,'connection_file'=>$connection,'bootstrap_file'=>$bootstrap);
    $policy=array('timeout_seconds'=>$mode==='apply'?120:60,'max_output_bytes'=>262144);
    if($mode==='apply'){$params['confirm_bootstrap']=true;$policy['allow_bootstrap_apply']=true;}
    return dqs_install_web_preflight_safe_result(dqs_install_execute('bootstrap',$params,$policy));
}
