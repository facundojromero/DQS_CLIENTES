<?php
/** DQS safe installation finalizer (UNI-048.6). CLI-only and dry-run by default. */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "This tool is available from CLI only.\n"); exit(2); }

$usage = 'Usage: php tools/dqs_install_finalize.php (--connection-file=/path/file.php|--using-current-connection) --target-root=/path/root --admin-slug=adminName [--apply --confirm-finalize] [--json]';
$allowedFlags = array('--using-current-connection','--apply','--confirm-finalize','--json','--help');
$connectionFile = null; $targetArg = null; $slug = null;
foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--connection-file=') === 0) { if ($connectionFile !== null) usageError('An option was repeated.'); $connectionFile = substr($arg, 18); }
    elseif (strpos($arg, '--target-root=') === 0) { if ($targetArg !== null) usageError('An option was repeated.'); $targetArg = substr($arg, 14); }
    elseif (strpos($arg, '--admin-slug=') === 0) { if ($slug !== null) usageError('An option was repeated.'); $slug = substr($arg, 13); }
    elseif (!in_array($arg, $allowedFlags, true)) usageError('Unknown option.');
}
function usageError($message) { global $usage, $argv; $asJson=in_array('--json',$argv,true); if($asJson)echo json_encode(array('status'=>'BLOCKED','usage_error'=>$message),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";else fwrite(STDERR,$message."\n".$usage."\n");exit(2); }
$has = static function($flag) use ($argv) { return in_array($flag,$argv,true); };
if ($has('--help')) { echo $usage."\n"; exit(0); }
$json=$has('--json'); $apply=$has('--apply'); $confirm=$has('--confirm-finalize'); $current=$has('--using-current-connection');
if (($current?1:0)+($connectionFile!==null?1:0)!==1) usageError('Choose exactly one connection source.');
if ($connectionFile!==null && trim($connectionFile)==='') usageError('--connection-file requires a path.');
if (!is_string($targetArg)||trim($targetArg)===''||!is_string($slug)||trim($slug)==='') usageError('--target-root and --admin-slug are required.');
if ($apply !== $confirm) usageError('Finalization requires both --apply and --confirm-finalize; confirmation alone is invalid.');
if (!preg_match('/^admin[A-Za-z0-9]{4,28}$/',$slug)||strtolower($slug)==='admin_tmp') usageError('--admin-slug must start with admin, contain only letters/numbers, and be 9-33 characters long.');

$root=dirname(__DIR__); $checks=array();
$add=static function($id,$status,$message,array $details=array())use(&$checks){$checks[]=array('id'=>$id,'status'=>$status,'message'=>$message,'details'=>$details);};
$blocked=static function()use(&$checks){foreach($checks as $c)if(in_array($c['status'],array('BLOCKED','FAILED'),true))return true;return false;};

// Validate the destination without exposing absolute paths.
$target=realpath($targetArg); $repo=realpath($root); $admin=null; $lock=null; $pending=null;
if(!$target||!is_dir($target)||!is_readable($target))$add('target.root','BLOCKED','Target root is missing, unreadable, or not a directory ('.basename($targetArg).').');
elseif($repo&&$target!==$repo&&strpos($target,$repo.DIRECTORY_SEPARATOR)===0)$add('target.root','BLOCKED','Target root cannot be nested inside the active repository ('.basename($target).').');
elseif($apply&&!is_writable($target))$add('target.root','BLOCKED','Target root is not writable for apply ('.basename($target).').');
else $add('target.root','OK','Target root validated ('.basename($target).').');
if($target){$admin=$target.DIRECTORY_SEPARATOR.$slug;$lock=$target.DIRECTORY_SEPARATOR.'install.lock';$pending=$target.DIRECTORY_SEPARATOR.'.install.lock.pending';}
if($lock&&(file_exists($lock)||is_link($lock)))$add('lock.absent','BLOCKED','install.lock already exists; rerun is blocked and it will not be overwritten.');
else $add('lock.absent','OK','install.lock does not exist.');
if($pending&&(file_exists($pending)||is_link($pending)))$add('lock.pending','BLOCKED','A pending lock already exists; it will not be overwritten.');

// The selected connection PHP is tokenized, never included or executed.
$db=null;$connectionValues=null;$connectionPath=$current?$root.'/conexion.php':$connectionFile;
if(!is_file($connectionPath)||!is_readable($connectionPath))$add('connection.file','BLOCKED','Selected connection file is missing or unreadable (path hidden).');
else{
    $values=array();$tokens=token_get_all((string)file_get_contents($connectionPath));
    for($i=0,$n=count($tokens);$i<$n;$i++){if(!is_array($tokens[$i])||$tokens[$i][0]!==T_VARIABLE)continue;$name=substr($tokens[$i][1],1);if(!in_array($name,array('servername','username','password','dbname'),true))continue;$j=$i+1;while($j<$n&&is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)$j++;if(($tokens[$j]??null)!=='=')continue;do{$j++;}while($j<$n&&is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE);if(isset($tokens[$j])&&is_array($tokens[$j])&&$tokens[$j][0]===T_CONSTANT_ENCAPSED_STRING)$values[$name]=stripcslashes(substr($tokens[$j][1],1,-1));}
    if(array_diff(array('servername','username','password','dbname'),array_keys($values)))$add('connection.file','BLOCKED','Connection file must contain four literal assignments (values hidden).');
    elseif(!extension_loaded('mysqli'))$add('connection.mysqli','BLOCKED','Required mysqli extension is unavailable.');
    else{mysqli_report(MYSQLI_REPORT_OFF);$db=@new mysqli($values['servername'],$values['username'],$values['password'],$values['dbname']);if($db->connect_errno){$add('connection.db','BLOCKED','Could not establish selected database connection (credentials hidden).');$db=null;}elseif(!$db->set_charset('utf8mb4'))$add('connection.charset','BLOCKED','Could not set utf8mb4.');else{$connectionValues=$values;$add('connection.db','OK','Connection established from parsed literals; credentials and database name are hidden.');}}
    unset($values);
}

// Only the canonical installation placeholder may be replaced. Existing real
// configuration (and symlinks) are deliberately immutable.
$publicConnection=$target?$target.DIRECTORY_SEPARATOR.'conexion.php':null;
$connectionPlaceholder="<?php\ndie(\"Instalación pendiente. Configurar conexion.php final después de crear la DB del cliente.\");\n";
if(!$publicConnection||is_link($publicConnection)||!is_file($publicConnection)||!is_readable($publicConnection))$add('public_connection.placeholder','BLOCKED','Public connection file is missing, unreadable, or a symlink.');
else{$existingConnection=@file_get_contents($publicConnection);$add('public_connection.placeholder',$existingConnection===$connectionPlaceholder?'OK':'BLOCKED',$existingConnection===$connectionPlaceholder?'Recognized installation placeholder is ready for atomic replacement.':'Existing public connection is not the recognized installation placeholder and will not be changed.');}

$counts=array();$tables=array();$manifestTables=array();$userId=null;$clienteId=null;
$countTable=static function(mysqli $db,$table){$r=$db->query('SELECT COUNT(*) FROM `'.$table.'`');if(!$r)return null;$row=$r->fetch_row();$r->free();return(int)$row[0];};
if($db instanceof mysqli){
    $manifestPath=$root.'/database/install/manifest.json';$manifest=is_file($manifestPath)&&is_readable($manifestPath)?json_decode((string)file_get_contents($manifestPath),true):null;
    if(!is_array($manifest)||json_last_error()!==JSON_ERROR_NONE||!isset($manifest['tables'])||!is_array($manifest['tables']))$add('manifest','BLOCKED','manifest.json is missing, unreadable, or invalid.');
    else{$manifestTables=$manifest['tables'];$add('manifest','OK','manifest.json is readable and valid.',array('table_count'=>count($manifestTables)));}
    $r=$db->query('SHOW TABLES');if($r){while($row=$r->fetch_row())$tables[]=(string)$row[0];$r->free();}
    $missing=array_values(array_diff($manifestTables,$tables));$add('schema.tables',$missing?'BLOCKED':'OK',$missing?'Installed schema is incomplete; missing tables: '.implode(', ',$missing).'.':'All manifest tables exist.',array('manifest_count'=>count($manifestTables),'installed_manifest_count'=>count(array_intersect($manifestTables,$tables))));
    $r=$db->query("SHOW TRIGGERS WHERE `Trigger`='generar_codigo_invitado'");$trigger=$r&&$r->num_rows===1;if($r)$r->free();$add('schema.trigger',$trigger?'OK':'BLOCKED',$trigger?'Required trigger generar_codigo_invitado exists.':'Required trigger generar_codigo_invitado is missing.');
    foreach(array('info_mostrar'=>8,'intivados_acompanante'=>5,'invitados_prioridad'=>4,'user'=>1,'cliente'=>1,'admin_config'=>1,'invitados'=>0,'productos'=>0,'regalos'=>0)as$table=>$expected){$actual=in_array($table,$tables,true)?$countTable($db,$table):null;$counts[$table]=$actual;$add('count.'.$table,$actual===$expected?'OK':'BLOCKED',"{$table} count is ".($actual===null?'unavailable':$actual)."; required {$expected}.",array('count'=>$actual,'expected'=>$expected));}
    $requiredKeys=array('plan_servicio','rsvp_modo','fuente_envios_whatsapp','whatsapp_enabled','regalos_enabled','rsvp_form_persist_enabled');$found=array();
    if(in_array('site_settings',$tables,true)){$r=$db->query('SELECT `setting_key`, `setting_value` FROM `site_settings`');if($r){while($row=$r->fetch_assoc())$found[]=$row['setting_key'];$r->free();}}
    $missingKeys=array_values(array_diff($requiredKeys,$found));$add('site_settings.technical_keys',$missingKeys?'BLOCKED':'OK',$missingKeys?'Missing technical site_settings keys: '.implode(', ',$missingKeys).'.':'All required technical site_settings keys exist.');
    foreach(array('user'=>&$userId,'cliente'=>&$clienteId)as$table=>&$id){if(in_array($table,$tables,true)){$r=$db->query('SELECT `id` FROM `'.$table.'` LIMIT 1');if($r){$row=$r->fetch_assoc();$id=$row?$row['id']:null;$r->free();}}}unset($id);
    if(in_array('admin_config',$tables,true)){
        $columns=array();$r=$db->query('SHOW FULL COLUMNS FROM `admin_config`');if($r){while($column=$r->fetch_assoc())$columns[]=$column['Field'];$r->free();}
        $slugColumns=array_values(array_filter($columns,static function($name){return preg_match('/(?:nombre_?carpeta|carpeta_?admin|admin_?(?:slug|folder)|folder_?admin|ruta_?admin)/i',$name); }));
        $inspectionColumns=array_values(array_unique(array_merge($slugColumns,array_intersect($columns,array('user_id','id_user','usuario_id','cliente_id','id_cliente')))));$row=array();
        if($inspectionColumns){$quoted=array_map(static function($name){return '`'.str_replace('`','``',$name).'`';},$inspectionColumns);$r=$db->query('SELECT '.implode(', ',$quoted).' FROM `admin_config` LIMIT 1');if($r){$row=$r->fetch_assoc()?:array();$r->free();}}
        if(!$slugColumns)$add('admin_config.slug','WARN','No clear admin folder/slug column was detected; slug linkage could not be verified.');else foreach($slugColumns as$name)$add('admin_config.slug.'.$name,isset($row[$name])&&(string)$row[$name]===$slug?'OK':'BLOCKED',isset($row[$name])&&(string)$row[$name]===$slug?'Detected admin slug matches the published folder.':'Detected admin slug does not match the requested folder.');
        foreach(array('user_id'=>$userId,'id_user'=>$userId,'usuario_id'=>$userId,'cliente_id'=>$clienteId,'id_cliente'=>$clienteId)as$name=>$expected)if(in_array($name,$columns,true))$add('admin_config.link.'.$name,isset($row[$name])&&(string)$row[$name]===(string)$expected?'OK':'BLOCKED',isset($row[$name])&&(string)$row[$name]===(string)$expected?'Detected admin_config relation references the unique parent row.':'Detected admin_config relation does not reference the unique parent row.');
        $add('admin_config.columns','OK','admin_config columns were inspected with SHOW FULL COLUMNS; values remain hidden.',array('column_count'=>count($columns)));
    }
}

// Audit the already-published tree and build a path-free deterministic inventory.
$folder=array('file_count'=>0,'dir_count'=>0,'fingerprint_sha256'=>null);$parts=array();$folderSafe=true;
if(!$admin||!file_exists($admin)||!is_dir($admin)||is_link($admin)||!is_readable($admin)){$add('admin.folder','BLOCKED','Published admin folder is missing, unreadable, not a directory, or a symlink ('.$slug.').');$folderSafe=false;}
else{
    try{$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($admin,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);foreach($it as$item){$rel=substr($item->getPathname(),strlen($admin)+1);$segments=preg_split('~[\\\\/]~',$rel);if($item->isLink()){$add('admin.symlink','BLOCKED','Published admin folder contains a symbolic link (name hidden).');$folderSafe=false;continue;}foreach($segments as$segment)if(in_array(strtolower($segment),array('.git','node_modules','admin_tmp'),true)||strpos(strtolower($segment),'.env')===0){$add('admin.forbidden','BLOCKED','Published admin folder contains forbidden content (name hidden).');$folderSafe=false;break;}if($item->isDir())$folder['dir_count']++;elseif($item->isFile()){$folder['file_count']++;$parts[]=$rel.'|'.$item->getSize().'|'.$item->getMTime();}}}catch(Throwable $e){$add('admin.scan','BLOCKED','Published admin folder could not be scanned safely.');$folderSafe=false;}
    sort($parts,SORT_STRING);$folder['fingerprint_sha256']=hash('sha256',implode("\n",$parts));if($folder['file_count']<1){$add('admin.contents','BLOCKED','Published admin folder is empty.');$folderSafe=false;}else $add('admin.contents',$folderSafe?'OK':'BLOCKED',$folderSafe?'Published admin folder is non-empty and contains no forbidden content.':'Published admin folder audit failed.',array('file_count'=>$folder['file_count'],'dir_count'=>$folder['dir_count'],'fingerprint_sha256'=>$folder['fingerprint_sha256']));
    if(!is_file($admin.'/index.php')&&!is_file($admin.'/login.php'))$add('admin.entrypoint','WARN','Neither optional index.php nor login.php was found in the published admin folder.');else $add('admin.entrypoint','OK','A conventional admin entry point was found.');
}

$payload=array('format'=>'dqs_install_lock','version'=>'UNI-050','created_at_utc'=>gmdate('c'),'admin_slug'=>$slug,'schema_tables'=>count($manifestTables),'trigger'=>'generar_codigo_invitado','counts'=>array('user'=>$counts['user']??null,'cliente'=>$counts['cliente']??null,'admin_config'=>$counts['admin_config']??null,'invitados'=>$counts['invitados']??null,'productos'=>$counts['productos']??null,'regalos'=>$counts['regalos']??null),'folder'=>$folder);
if(!$apply&&!$blocked())$add('plan','OK','Dry-run audit passed; no files or database rows were written.');
if($apply&&!$blocked()){
    $encoded=json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";$created=false;$connectionTemporary=$target.DIRECTORY_SEPARATOR.'.conexion.php.'.bin2hex(random_bytes(8)).'.pending';
    try{
        if(!is_array($connectionValues)||file_exists($lock)||is_link($lock)||file_exists($pending)||is_link($pending)||file_exists($connectionTemporary)||is_link($connectionTemporary))throw new RuntimeException();
        $finalConnection="<?php\n";
        foreach(array('servername','username','password','dbname')as$name)$finalConnection.='$'.$name.' = '.var_export($connectionValues[$name],true).";\n";
        $finalConnection.="\n\$conn = new mysqli(\$servername, \$username, \$password, \$dbname);\nmysqli_set_charset(\$conn, \"utf8mb4\");\n";
        $cf=@fopen($connectionTemporary,'x');if(!$cf)throw new RuntimeException();$cw=fwrite($cf,$finalConnection);$cc=fclose($cf);if($cw!==strlen($finalConnection)||!$cc||!chmod($connectionTemporary,0644))throw new RuntimeException();
        $fh=@fopen($pending,'x');if(!$fh)throw new RuntimeException();$written=fwrite($fh,$encoded);$closed=fclose($fh);if($written!==strlen($encoded)||!$closed||!chmod($pending,0600))throw new RuntimeException();
        clearstatcache(true,$publicConnection);if(is_link($publicConnection)||@file_get_contents($publicConnection)!==$connectionPlaceholder||!rename($connectionTemporary,$publicConnection))throw new RuntimeException();
        if(file_exists($lock)||is_link($lock)||!rename($pending,$lock)){// restore the harmless placeholder atomically when lock publication fails
            $restore=$target.DIRECTORY_SEPARATOR.'.conexion.php.restore.'.bin2hex(random_bytes(8));
            if(file_put_contents($restore,$connectionPlaceholder)===strlen($connectionPlaceholder)){@chmod($restore,0644);@rename($restore,$publicConnection);}@unlink($restore);throw new RuntimeException();
        }
        $created=true;$add('apply.connection','OK','Public connection was replaced atomically with mode 0644; values remain hidden.');$add('apply.lock','OK','install.lock was created atomically with restrictive permissions where supported.');
    }
    catch(Throwable $e){if($pending&&file_exists($pending))@unlink($pending);if(file_exists($connectionTemporary))@unlink($connectionTemporary);$add('apply.lock','FAILED','Final publication failed; pending files were cleaned and the placeholder was restored when necessary.');}
    if($created){$raw=@file_get_contents($lock);$decoded=is_string($raw)?json_decode($raw,true):null;$valid=is_array($decoded)&&json_last_error()===JSON_ERROR_NONE;$add('post.lock_json',$valid?'OK':'FAILED',$valid?'install.lock exists, is readable, and contains valid JSON.':'install.lock post-check failed.');$sensitive=is_string($raw)&&preg_match('/password|dbname|username|servername|u[0-9]{4,}_|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',$raw);$add('post.lock_sensitive',$sensitive?'FAILED':'OK',$sensitive?'install.lock contains a forbidden sensitive pattern.':'install.lock contains no forbidden sensitive patterns.');$connectionMode=fileperms($publicConnection)&0777;$connectionOk=is_file($publicConnection)&&!is_link($publicConnection)&&$connectionMode===0644&&strpos((string)@file_get_contents($publicConnection),'Instalación pendiente')===false;$add('post.connection',$connectionOk?'OK':'FAILED',$connectionOk?'Final public connection exists with mode 0644 and no pending marker.':'Final public connection post-check failed.');foreach(array('user'=>1,'cliente'=>1,'admin_config'=>1,'invitados'=>0,'productos'=>0,'regalos'=>0)as$table=>$expected){$actual=$countTable($db,$table);$add('post.db.'.$table,$actual===$expected?'OK':'FAILED',"Post-check {$table} count is {$actual}; expected {$expected}.",array('count'=>$actual,'expected'=>$expected));}$nonempty=is_dir($admin)&&!is_link($admin)&&count(glob($admin.DIRECTORY_SEPARATOR.'*')?:array())>0;$add('post.admin',$nonempty?'OK':'FAILED',$nonempty?'Published admin folder remains present and non-empty.':'Published admin folder post-check failed.');}
}
$status='OK';foreach($checks as$c)if($c['status']==='FAILED'){$status='FAILED';break;}elseif($c['status']==='BLOCKED')$status='BLOCKED';elseif($c['status']==='WARN'&&$status==='OK')$status='WARN';
$report=array('status'=>$status,'mode'=>$apply?'apply':'dry-run','admin_slug'=>$slug,'target_root'=>basename((string)$target),'checks'=>$checks);
if($json)echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";else{echo "UNI-048.6 install finalize: {$status}\nMode: ".($apply?'APPLY':'DRY-RUN (no writes)')."\nAdmin slug: {$slug}\n";foreach($checks as$c)echo '['.$c['status'].'] '.$c['message']."\n";}
if($db instanceof mysqli)$db->close();exit(in_array($status,array('OK','WARN'),true)?0:1);
