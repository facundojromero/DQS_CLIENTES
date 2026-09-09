<?php
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only.\n"); exit(2); }
require_once dirname(__DIR__) . '/includes/install/install_cli_executor.php';

$valueFlags=array('operation','connection-file','bootstrap-file','admin-template-dir','target-root','admin-config-file','admin-slug','mode');
$boolFlags=array('json','no-db','using-current-connection','include-default-content','apply','confirm-empty-install','confirm-bootstrap','confirm-admin-publish','confirm-finalize');
$raw=array(); $parseError=null; $jsonRequested=in_array('--json',$argv,true);
foreach(array_slice($argv,1) as $arg){
    if(substr($arg,0,2)!=='--'){ $parseError='Positional arguments are forbidden.'; break; }
    $part=substr($arg,2); $pair=explode('=',$part,2); $name=$pair[0];
    if(in_array($name,$valueFlags,true) && count($pair)===2 && $pair[1]!=='') $raw[$name]=$pair[1];
    elseif(in_array($name,$boolFlags,true) && count($pair)===1) $raw[$name]=true;
    else { $parseError='Unknown or malformed option.'; break; }
}
$operation=isset($raw['operation'])?$raw['operation']:'';
if($parseError!==null) $result=dqs_install_executor_result($operation,'BLOCKED',$parseError);
elseif(isset($raw['apply'])||isset($raw['confirm-empty-install'])||isset($raw['confirm-bootstrap'])||isset($raw['confirm-admin-publish'])||isset($raw['confirm-finalize'])) $result=dqs_install_executor_result($operation,'BLOCKED','Apply and confirmation flags are disabled in UNI-049.2.');
else {
    $params=array(); foreach($raw as $key=>$value) if(!in_array($key,array('operation','json'),true)) $params[str_replace('-','_',$key)]=$value;
    $policy=array('target_roots'=>isset($raw['target-root'])?array($raw['target-root']):array());
    $result=dqs_install_execute($operation,$params,$policy);
}
if($jsonRequested) echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
else { echo 'DQS executor '.$result['operation'].': '.$result['status'].PHP_EOL; echo 'Checks: '.$result['summary']['check_count'].'; duration: '.$result['duration_ms'].' ms'.PHP_EOL; foreach($result['errors'] as $error)echo 'Error: '.$error.PHP_EOL; }
exit(in_array($result['status'],array('OK','WARN'),true)?0:1);
