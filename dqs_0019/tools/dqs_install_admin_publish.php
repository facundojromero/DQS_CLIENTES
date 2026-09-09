<?php
/** DQS admin publisher (UNI-048.5). CLI-only and dry-run by default. */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "This tool is available from CLI only.\n"); exit(2); }

$usage = 'Usage: php tools/dqs_install_admin_publish.php (--connection-file=/path/file.php|--using-current-connection) --admin-template-dir=/path/template --target-root=/path/root [--admin-slug=adminName] [--admin-config-file=/path/data.json] [--print-template|--json] [--apply --confirm-admin-publish]';
$o = getopt('', array('connection-file:', 'using-current-connection', 'admin-template-dir:', 'target-root:', 'admin-slug:', 'admin-config-file:', 'print-template', 'json', 'apply', 'confirm-admin-publish', 'help'));
$json = isset($o['json']); $print = isset($o['print-template']); $apply = isset($o['apply']); $confirm = isset($o['confirm-admin-publish']);
$root = dirname(__DIR__); $checks = array();
$usageError = static function ($message) use ($usage, $json) { if ($json) echo json_encode(array('status'=>'BLOCKED','usage_error'=>$message), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n"; else fwrite(STDERR, $message."\n".$usage."\n"); exit(2); };
if (isset($o['help'])) { echo $usage."\n"; exit(0); }
$hasFile = array_key_exists('connection-file', $o); $current = isset($o['using-current-connection']);
if (($hasFile ? 1 : 0) + ($current ? 1 : 0) !== 1) $usageError('Choose exactly one connection source.');
foreach (array('admin-template-dir','target-root') as $required) if (!isset($o[$required]) || !is_string($o[$required]) || trim($o[$required]) === '') $usageError("--{$required} is required.");
if ($hasFile && (!is_string($o['connection-file']) || trim($o['connection-file']) === '')) $usageError('--connection-file requires a path.');
if ($apply !== $confirm) $usageError('Writes require both --apply and --confirm-admin-publish; confirmation alone is invalid.');
if ($print && ($apply || $confirm || $json || isset($o['admin-config-file']))) $usageError('--print-template cannot be combined with config input, JSON output, or apply.');
if (!$print && (!isset($o['admin-config-file']) || !is_string($o['admin-config-file']) || trim($o['admin-config-file']) === '')) $usageError('--admin-config-file is required unless --print-template is used.');

$add = static function ($id, $status, $message, array $details=array()) use (&$checks) { $checks[] = array('id'=>$id,'status'=>$status,'message'=>$message,'details'=>$details); };
$isBlocked = static function () use (&$checks) { foreach ($checks as $c) if ($c['status'] === 'BLOCKED' || $c['status'] === 'FAILED') return true; return false; };
$safeSlug = static function ($slug) { return is_string($slug) && preg_match('/^admin[A-Za-z0-9]{4,28}$/', $slug) && strtolower($slug) !== 'admin_tmp'; };
$generatedSlug = 'admin' . bin2hex(random_bytes(6));
$slug = isset($o['admin-slug']) ? $o['admin-slug'] : $generatedSlug;
if (!$safeSlug($slug)) $usageError('--admin-slug must start with admin, contain only letters/numbers, and be 9-33 characters long.');

// Load config before path validation so its slug can be reused after --print-template.
$input = null;
if (!$print) {
    $p = $o['admin-config-file']; $rp = realpath($p);
    if (!$rp || !is_file($rp) || !is_readable($rp)) $add('input.file','BLOCKED','Admin config input is missing or unreadable ('.basename($p).').');
    elseif (($rr=realpath($root)) && ($rp === $rr || strpos($rp, $rr.DIRECTORY_SEPARATOR) === 0)) $add('input.location','BLOCKED','Admin config input must be outside the repository ('.basename($rp).').');
    else {
        $input = json_decode((string)file_get_contents($rp), true);
        if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) $add('input.json','BLOCKED','Admin config input is not valid JSON ('.basename($rp).').');
        else {
            $add('input.json','OK','Admin config input loaded; values are hidden ('.basename($rp).').');
            $unknown = array_diff(array_keys($input), array('admin_slug','admin_config'));
            if ($unknown) $add('input.shape','BLOCKED','Unknown top-level keys: '.implode(', ', $unknown).'.');
            if (!isset($input['admin_slug']) || !is_string($input['admin_slug']) || !$safeSlug($input['admin_slug']) || !isset($input['admin_config']) || !is_array($input['admin_config'])) $add('input.shape','BLOCKED','Input requires valid admin_slug and admin_config objects.');
            elseif (isset($o['admin-slug']) && $input['admin_slug'] !== $slug) $add('input.slug','BLOCKED','JSON admin_slug does not match --admin-slug.');
            elseif (!isset($o['admin-slug'])) $slug = $input['admin_slug'];
        }
    }
}

// Resolve paths without ever displaying their full value.
$source = realpath($o['admin-template-dir']); $targetRoot = realpath($o['target-root']); $repoReal = realpath($root);
if (!$source || !is_dir($source) || !is_readable($source)) $add('template.path','BLOCKED','Template is missing, unreadable, or not a directory ('.basename($o['admin-template-dir']).').');
elseif (strtolower(basename($source)) === 'admin_tmp') $add('template.path','BLOCKED','admin_tmp can never be used as a template.');
elseif (!$repoReal || !($source === $repoReal || strpos($source, $repoReal.DIRECTORY_SEPARATOR) === 0)) $add('template.path','BLOCKED','Template must resolve inside the project tree ('.basename($source).').');
else $add('template.path','OK','Read-only template validated ('.basename($source).').');
if (!$targetRoot || !is_dir($targetRoot) || !is_writable($targetRoot)) $add('target.root','BLOCKED','Target root is missing, not a directory, or not writable ('.basename($o['target-root']).').');
elseif ($repoReal && $targetRoot !== $repoReal && strpos($targetRoot, $repoReal.DIRECTORY_SEPARATOR) === 0) $add('target.root','BLOCKED','A nested project directory cannot be a publication target.');
elseif ($source && ($targetRoot === $source || strpos($targetRoot, $source.DIRECTORY_SEPARATOR) === 0)) $add('target.separation','BLOCKED','The target cannot be the template or one of its descendants.');
else $add('target.root','OK','Writable publication root validated ('.basename($targetRoot).').');
$destination = $targetRoot ? $targetRoot.DIRECTORY_SEPARATOR.$slug : null; $staging = $targetRoot ? $targetRoot.DIRECTORY_SEPARATOR.'.'.$slug.'.pending' : null;
if ($destination && (file_exists($destination) || is_link($destination))) $add('target.destination','BLOCKED','Destination already exists ('.$slug.').'); else $add('target.destination','OK','Destination slug is available ('.$slug.').');
if ($staging && (file_exists($staging) || is_link($staging))) $add('target.staging','BLOCKED','Staging name already exists; it will not be overwritten.');

// Parse four literal assignments. The selected PHP file is never included or executed.
$db = null; $connectionPath = $current ? $root.'/conexion.php' : $o['connection-file'];
if (!is_file($connectionPath) || !is_readable($connectionPath)) $add('connection.file','BLOCKED','Selected connection file is missing or unreadable (path hidden).');
else {
    $values=array(); $tokens=token_get_all((string)file_get_contents($connectionPath));
    for ($i=0,$n=count($tokens);$i<$n;$i++) { if (!is_array($tokens[$i]) || $tokens[$i][0]!==T_VARIABLE) continue; $name=substr($tokens[$i][1],1); if (!in_array($name,array('servername','username','password','dbname'),true)) continue; $j=$i+1; while ($j<$n && is_array($tokens[$j]) && $tokens[$j][0]===T_WHITESPACE) $j++; if (($tokens[$j]??null)!=='=') continue; do {$j++;} while ($j<$n && is_array($tokens[$j]) && $tokens[$j][0]===T_WHITESPACE); if (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0]===T_CONSTANT_ENCAPSED_STRING) $values[$name]=stripcslashes(substr($tokens[$j][1],1,-1)); }
    if (array_diff(array('servername','username','password','dbname'),array_keys($values))) $add('connection.file','BLOCKED','Connection file must contain four literal assignments (values hidden).');
    elseif (!extension_loaded('mysqli')) $add('connection.mysqli','BLOCKED','Required mysqli extension is unavailable.');
    else { mysqli_report(MYSQLI_REPORT_OFF); $db=@new mysqli($values['servername'],$values['username'],$values['password'],$values['dbname']); if ($db->connect_errno) { $add('connection.db','BLOCKED','Could not establish selected database connection (credentials hidden).'); $db=null; } elseif (!$db->set_charset('utf8mb4')) $add('connection.charset','BLOCKED','Could not set utf8mb4.'); else $add('connection.db','OK','Connection established from parsed literals; credentials and database name are hidden.'); }
    unset($values);
}

$columns=array(); $userRow=null; $clientRow=null;
$countTable = static function(mysqli $db,$table) { $r=$db->query('SELECT COUNT(*) FROM `'.$table.'`'); if (!$r) return null; $v=$r->fetch_row(); $r->free(); return (int)$v[0]; };
if ($db instanceof mysqli) {
    $tables=array(); $r=$db->query('SHOW TABLES'); if ($r) { while($x=$r->fetch_row())$tables[]=(string)$x[0]; $r->free(); }
    $manifest=json_decode((string)@file_get_contents($root.'/database/install/manifest.json'),true); $missing=!is_array($manifest)||!isset($manifest['tables'])?array('manifest'):array_values(array_diff($manifest['tables'],$tables));
    $add('schema.tables',$missing?'BLOCKED':'OK',$missing?'Installed schema is incomplete; missing: '.implode(', ',$missing).'.':'DB bootstrapped: all manifest tables are installed.');
    $r=$db->query("SHOW TRIGGERS WHERE `Trigger`='generar_codigo_invitado'"); $trigger=$r&&$r->num_rows===1; if($r)$r->free(); $add('schema.trigger',$trigger?'OK':'BLOCKED',$trigger?'Required trigger generar_codigo_invitado exists.':'Required trigger generar_codigo_invitado is missing.');
    foreach(array('user'=>1,'cliente'=>1,'admin_config'=>0,'invitados'=>0,'productos'=>0,'regalos'=>0) as $table=>$expected) { $actual=in_array($table,$tables,true)?$countTable($db,$table):null; $add('count.'.$table,$actual===$expected?'OK':'BLOCKED',"{$table} count is ".($actual===null?'unavailable':$actual)."; required {$expected}.",array('count'=>$actual,'expected'=>$expected)); }
    if (in_array('user',$tables,true)) { $r=$db->query('SELECT * FROM `user` LIMIT 1'); if($r){$userRow=$r->fetch_assoc();$r->free();} }
    if (in_array('cliente',$tables,true)) { $r=$db->query('SELECT * FROM `cliente` LIMIT 1'); if($r){$clientRow=$r->fetch_assoc();$r->free();} }
    if (in_array('admin_config',$tables,true)) { $r=$db->query('SHOW FULL COLUMNS FROM `admin_config`'); if($r){while($x=$r->fetch_assoc())$columns[$x['Field']]=$x;$r->free();} }
}

$managed=array(); $automatic=array(); $required=array();
foreach($columns as $name=>$meta) {
    $lower=strtolower($name); $type=strtolower($meta['Type']);
    if (stripos($meta['Extra'],'auto_increment')!==false) { $managed[$name]='auto_increment'; continue; }
    if (in_array($lower,array('user_id','id_user','usuario_id'),true)) { $managed[$name]='user'; if (isset($userRow['id'])) $automatic[$name]=(string)$userRow['id']; else $add('config.user_link','BLOCKED','A detected user relation cannot be resolved from user.id.'); }
    elseif (in_array($lower,array('cliente_id','id_cliente'),true)) { $managed[$name]='cliente'; if (isset($clientRow['id'])) $automatic[$name]=(string)$clientRow['id']; else $add('config.client_link','BLOCKED','A detected cliente relation cannot be resolved from cliente.id.'); }
    elseif (preg_match('/(?:nombre_?carpeta|carpeta_?admin|admin_?(?:slug|folder)|folder_?admin|ruta_?admin)/',$lower)) { $managed[$name]='slug'; $automatic[$name]=$slug; }
    elseif (preg_match('/^(?:created_at|created|fecha|fecha_creacion)$/',$lower)) { $managed[$name]='now'; $automatic[$name]=array('sql'=>'NOW()'); }
    elseif (in_array($lower,array('activo','active','status','estado'),true)) {
        $candidate='1';
        if (preg_match('/^enum\((.*)\)$/',$type,$sm)) { preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/",$sm[1],$sv); $allowed=array_map('stripcslashes',$sv[1]); if(in_array('activo',$allowed,true))$candidate='activo'; elseif(!in_array('1',$allowed,true))$candidate=null; }
        if($candidate!==null){$managed[$name]='status';$automatic[$name]=$candidate;} elseif($meta['Null']==='NO'&&$meta['Default']===null)$required[]=$name;
    }
    elseif (in_array($lower,array('email','correo'),true)) { $managed[$name]='email'; $automatic[$name]=(string)($userRow['email']??''); }
    elseif ($meta['Null']==='NO' && $meta['Default']===null) $required[]=$name;
}
if ($print && $db instanceof mysqli && !$isBlocked()) { $template=array('admin_slug'=>$slug,'admin_config'=>array()); foreach($required as $name)$template['admin_config'][$name]='REEMPLAZAR'; echo json_encode($template,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n"; exit(0); }

$provided=array();
if (is_array($input) && isset($input['admin_config']) && is_array($input['admin_config'])) foreach($input['admin_config'] as $name=>$value) {
    if (!is_string($name)||!isset($columns[$name])) { $add('config.unknown','BLOCKED','Unknown admin_config column: '.(string)$name.'.'); continue; }
    if (isset($managed[$name])) { $add('config.managed','BLOCKED',"admin_config.{$name} is managed automatically."); continue; }
    if (is_array($value)||is_object($value)||(is_string($value)&&(preg_match('/<[^>]*>/',$value)||preg_match('~(?:^|[\\/])\.\.(?:[\\/]|$)~',$value)))) { $add('config.unsafe','BLOCKED',"admin_config.{$name} contains an unsupported value."); continue; }
    $s=$value===null?null:(string)$value; $type=strtolower($columns[$name]['Type']); $ok=true;
    if ($s===null && $columns[$name]['Null']!=='YES') $ok=false;
    elseif (preg_match('/^(?:var)?char\((\d+)\)/',$type,$m) && strlen((string)$s)>(int)$m[1]) $ok=false;
    elseif (preg_match('/^(?:tiny|small|medium|big)?int/',$type) && $s!==null && !preg_match('/^-?\d+$/',$s)) $ok=false;
    elseif (preg_match('/^(?:decimal|numeric)\((\d+),(\d+)\)/',$type,$m) && $s!==null && !preg_match('/^-?\d{1,'.((int)$m[1]-(int)$m[2]).'}(?:\.\d{1,'.(int)$m[2].'})?$/',$s)) $ok=false;
    elseif ($type==='date' && $s!==null && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$s)) $ok=false;
    elseif (preg_match('/^(?:datetime|timestamp)/',$type) && $s!==null && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',$s)) $ok=false;
    elseif (preg_match('/^enum\((.*)\)$/',$type,$m) && $s!==null) { preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/",$m[1],$em); if(!in_array($s,array_map('stripcslashes',$em[1]),true))$ok=false; }
    if(!$ok)$add('config.type','BLOCKED',"admin_config.{$name} has an invalid value for its detected type."); else $provided[$name]=$s;
}
foreach($required as $name) if(!array_key_exists($name,$provided)) $add('config.required','BLOCKED','Missing required admin_config column: '.$name.'.');
if ($columns) $add('config.columns','OK','admin_config columns were inspected with SHOW FULL COLUMNS; managed and supplied values are hidden.',array('column_count'=>count($columns)));

$removeTree = static function($path) use (&$removeTree) { if (!file_exists($path)&&!is_link($path)) return; if(is_link($path)||is_file($path)){@unlink($path);return;} foreach(new FilesystemIterator($path,FilesystemIterator::SKIP_DOTS) as $item)$removeTree($item->getPathname()); @rmdir($path); };
$copyTree = static function($from,$to) use (&$copyTree) { if(!mkdir($to,0755))throw new RuntimeException('Could not create staging directory.'); if(!chmod($to,0755))throw new RuntimeException('Could not set directory permissions.'); foreach(new FilesystemIterator($from,FilesystemIterator::SKIP_DOTS) as $item){$name=$item->getFilename(); if($item->isLink())throw new RuntimeException('Template contains a symbolic link.'); if($item->isDir()&&in_array(strtolower($name),array('.git','node_modules','logs','log','cache','tmp'),true))continue; if($item->isFile()&&strpos($name,'.env')===0)continue; $dest=$to.DIRECTORY_SEPARATOR.$name; if($item->isDir())$copyTree($item->getPathname(),$dest); elseif($item->isFile()){if(!copy($item->getPathname(),$dest))throw new RuntimeException('Could not copy a template file.');if(!chmod($dest,0644))throw new RuntimeException('Could not set file permissions.');} } };
$sourceFingerprint = static function($path) { $parts=array(); $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path,FilesystemIterator::SKIP_DOTS)); foreach($it as $f)if($f->isFile()&&!$f->isLink())$parts[]=substr($f->getPathname(),strlen($path)+1).'|'.$f->getSize().'|'.$f->getMTime(); sort($parts); return hash('sha256',implode("\n",$parts)); };
$published=false; $sourceBefore=$source?$sourceFingerprint($source):null;
if ($apply && !$isBlocked()) {
    try {
        $copyTree($source,$staging);
        $db->begin_transaction();
        $valuesToInsert=$automatic+$provided; if(!$valuesToInsert) throw new RuntimeException('No insertable admin_config columns were detected.');
        $names=array_keys($valuesToInsert); $placeholders=array(); $bound=array(); foreach($names as $name){if(is_array($valuesToInsert[$name])&&($valuesToInsert[$name]['sql']??null)==='NOW()')$placeholders[]='NOW()';else{$placeholders[]='?';$bound[]=$valuesToInsert[$name];}}
        $sql='INSERT INTO `admin_config` (`'.implode('`,`',$names).'`) VALUES ('.implode(',',$placeholders).')'; $stmt=$db->prepare($sql); if(!$stmt)throw new RuntimeException('Could not prepare admin_config insert.');
        if($bound){$params=array(str_repeat('s',count($bound)));foreach($bound as $value)$params[]=$value;$refs=array();foreach($params as $k=>&$v)$refs[$k]=&$v;if(!call_user_func_array(array($stmt,'bind_param'),$refs))throw new RuntimeException('Could not bind admin_config insert.');} if(!$stmt->execute())throw new RuntimeException('Could not insert admin_config.'); $stmt->close();
        if(!rename($staging,$destination))throw new RuntimeException('Could not publish staged directory.'); $published=true;
        if(!$db->commit())throw new RuntimeException('Could not commit admin_config.');
        $add('apply.publish','OK','Admin folder published and admin_config inserted atomically ('.$slug.').');
    } catch(Throwable $e) { if($db instanceof mysqli)@$db->rollback(); if($published)$removeTree($destination); $removeTree($staging); $add('apply','FAILED','Apply failed; database and tool-created files were rolled back (details hidden).'); }
}
if (!$apply && !$isBlocked()) $add('plan','OK','Dry-run validated: copy to staging, prepared admin_config insert, rename, and commit; no writes performed.');
if ($apply && !$isBlocked()) {
    foreach(array('user'=>1,'cliente'=>1,'admin_config'=>1,'invitados'=>0,'productos'=>0,'regalos'=>0) as $table=>$expected){$actual=$countTable($db,$table);$add('post.'.$table,$actual===$expected?'OK':'FAILED',"Post-check {$table} count is {$actual}; expected {$expected}.",array('count'=>$actual));}
    $files=0;$permissionsOk=is_dir($destination)&&!is_link($destination)&&(fileperms($destination)&0777)===0755;if(is_dir($destination)){foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($destination,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST) as $f){if($f->isFile())$files++;$expected=$f->isDir()?0755:0644;if($f->isLink()||(!$f->isDir()&&!$f->isFile())||(fileperms($f->getPathname())&0777)!==$expected)$permissionsOk=false;}}
    $add('post.folder',is_dir($destination)&&$files>0?'OK':'FAILED',is_dir($destination)&&$files>0?'Published folder contains expected files.':'Published folder is missing or empty.',array('file_count'=>$files));
    $add('post.permissions',$permissionsOk?'OK':'FAILED',$permissionsOk?'Published directories use 0755 and files use 0644.':'Published admin permissions are not web-readable.');
    $installLock=file_exists($targetRoot.DIRECTORY_SEPARATOR.'install.lock')||file_exists($root.'/install.lock'); $add('post.install_lock',$installLock?'FAILED':'OK',$installLock?'Unexpected install.lock detected.':'No install.lock was created.');
    $sourceAfter=$sourceFingerprint($source); $add('post.template',$sourceBefore===$sourceAfter?'OK':'FAILED',$sourceBefore===$sourceAfter?'Template source remained unchanged.':'Template source changed unexpectedly.');
}

$status='OK'; foreach($checks as $c)if($c['status']==='FAILED'){$status='FAILED';break;}elseif($c['status']==='BLOCKED')$status='BLOCKED';elseif($c['status']==='WARN'&&$status==='OK')$status='WARN';
$report=array('status'=>$status,'mode'=>$apply?'apply':'dry-run','admin_slug'=>$slug,'source'=>basename((string)$source),'target_root'=>basename((string)$targetRoot),'checks'=>$checks);
if($json)echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n"; else { echo "UNI-048.5 admin publish: {$status}\nMode: ".($apply?'APPLY':'DRY-RUN (no writes)')."\nAdmin slug: {$slug}\n"; foreach($checks as $c)echo '['.$c['status'].'] '.$c['message']."\n"; }
if($db instanceof mysqli)$db->close(); exit(in_array($status,array('OK','WARN'),true)?0:1);
