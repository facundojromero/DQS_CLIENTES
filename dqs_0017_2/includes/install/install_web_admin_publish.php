<?php
/** Controlled Admin publish dry-run/apply web helpers (UNI-049.7). */

require_once __DIR__ . '/install_web_bootstrap.php';

function dqs_install_web_admin_publish_blocked($message)
{
    return dqs_install_web_preflight_blocked($message);
}

/** Exactly one root-level, real, non-symlink admin template is an unambiguous policy. */
function dqs_install_web_admin_publish_template($repo)
{
    $repoReal=realpath($repo); if($repoReal===false)return null; $candidates=array();
    foreach(new DirectoryIterator($repoReal) as $entry){
        if($entry->isDot()||$entry->isLink()||!$entry->isDir())continue;
        $name=$entry->getFilename();
        if($name==='admin_tmp'||!preg_match('/\Aadmin[A-Za-z0-9]{4,28}\z/',$name))continue;
        $real=realpath($entry->getPathname());
        if($real!==false&&dirname($real)===$repoReal)$candidates[]=$real;
    }
    return count($candidates)===1?$candidates[0]:null;
}

/** Target roots are internal policy values, never request values. */
function dqs_install_web_admin_publish_target($repo,$runtime)
{
    $repoReal=realpath($repo);$runtimeReal=realpath($runtime);
    if($repoReal===false||$runtimeReal===false||dqs_install_executor_inside($repoReal,$runtimeReal)
        ||is_link($repo)||!is_dir($repoReal)||!is_writable($repoReal))return null;
    // This checkout is the public document root. The value remains entirely
    // server-derived, so a request cannot redirect publication elsewhere.
    return $repoReal;
}

function dqs_install_web_admin_publish_slug($target)
{
    for($i=0;$i<8;$i++){$slug='admin'.bin2hex(random_bytes(8));if(!file_exists($target.DIRECTORY_SEPARATOR.$slug)&&!is_link($target.DIRECTORY_SEPARATOR.$slug))return $slug;}
    return null;
}

function dqs_install_web_admin_publish_cleanup($path,$runtime)
{
    if(!is_string($path)||$path===''||is_link($path))return;$sessions=realpath($runtime.DIRECTORY_SEPARATOR.'web_sessions');$parent=realpath(dirname($path));
    if($sessions!==false&&$parent!==false&&dirname($parent)===$sessions&&basename($path)==='admin_publish.json'&&is_file($path))@unlink($path);
}

function dqs_install_web_admin_publish_create($runtime,$connection,$slug)
{
    $connection=dqs_install_web_schema_connection($connection,$runtime);
    if($connection===null||!is_string($slug)||!preg_match('/\Aadmin[A-Za-z0-9]{4,28}\z/',$slug))return null;
    $path=dirname($connection).DIRECTORY_SEPARATOR.'admin_publish.json';dqs_install_web_admin_publish_cleanup($path,$runtime);$old=umask(0177);$h=@fopen($path,'x');umask($old);
    if($h===false)return null;if(is_link($path)||!@chmod($path,0600)){fclose($h);@unlink($path);return null;}
    $json=json_encode(array('admin_slug'=>$slug,'admin_config'=>new stdClass()),JSON_UNESCAPED_SLASHES);$written=fwrite($h,$json);fflush($h);fclose($h);clearstatcache(true,$path);
    if($written!==strlen($json)||is_link($path)||(fileperms($path)&0777)!==0600){@unlink($path);return null;}return $path;
}

function dqs_install_web_admin_publish_path($path,$runtime,$connection)
{
    if(!is_string($path)||$path===''||is_link($path)||!is_file($path)||!is_readable($path))return null;
    $real=realpath($path);$connectionReal=realpath($connection);$sessions=realpath($runtime.DIRECTORY_SEPARATOR.'web_sessions');
    return $real!==false&&$connectionReal!==false&&$sessions!==false&&basename($real)==='admin_publish.json'&&dirname($real)===dirname($connectionReal)&&dirname(dirname($real))===$sessions&&preg_match('/\A[a-f0-9]{48}\z/',basename(dirname($real)))?$real:null;
}

function dqs_install_web_admin_publish_fingerprint($connection,$config,$template,$target,$slug,$at)
{
    return hash('sha256',"admin-publish\0".hash_file('sha256',$connection)."\0".hash_file('sha256',$config)."\0".hash('sha256',$template)."\0".hash('sha256',$target)."\0".$slug."\0".$at);
}

function dqs_install_web_admin_publish_run($mode,$connection,$template,$target,$config,$slug)
{
    $params=array('mode'=>$mode,'connection_file'=>$connection,'admin_template_dir'=>$template,'target_root'=>$target,'admin_config_file'=>$config,'admin_slug'=>$slug);
    $policy=array('target_roots'=>array($target),'allow_repo_target'=>true,'timeout_seconds'=>$mode==='apply'?120:60,'max_output_bytes'=>262144);
    if($mode==='apply'){$params['confirm_admin_publish']=true;$policy['allow_admin_publish_apply']=true;}
    $safe=dqs_install_web_preflight_safe_result(dqs_install_execute('admin_publish',$params,$policy));
    // The CLI intentionally reports basenames; the web contract must hide even those path hints.
    $hide=function($value)use(&$hide,$template,$target){
        if(is_array($value)){foreach($value as $key=>$item)$value[$key]=$hide($item);return $value;}
        return is_string($value)?str_ireplace(array(basename($template),basename($target)),array('[admin-template]','[admin-target]'),$value):$value;
    };
    return $hide($safe);
}
