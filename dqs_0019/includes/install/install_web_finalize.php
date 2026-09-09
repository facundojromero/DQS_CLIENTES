<?php
/** Controlled Finalize dry-run/apply web helpers (UNI-049.8). */

require_once __DIR__ . '/install_web_admin_publish.php';

function dqs_install_web_finalize_blocked($message)
{
    return dqs_install_web_preflight_blocked($message);
}

/** Revalidate the private Admin publish state without accepting request paths. */
function dqs_install_web_finalize_inputs($repo, $runtime, $connection, $slug)
{
    $connection=dqs_install_web_schema_connection($connection,$runtime);
    $target=dqs_install_web_admin_publish_target($repo,$runtime);
    if($connection===null||$target===null||!is_string($slug)||!preg_match('/\Aadmin[A-Za-z0-9]{4,28}\z/',$slug))return null;
    $admin=$target.DIRECTORY_SEPARATOR.$slug;
    if(is_link($admin)||!is_dir($admin)||!is_readable($admin))return null;
    $real=realpath($admin);
    if($real===false||dirname($real)!==$target||basename($real)!==$slug)return null;
    return array('connection'=>$connection,'target'=>$target,'slug'=>$slug);
}

function dqs_install_web_finalize_fingerprint(array $inputs,$at,$adminApplyAt)
{
    if(!is_string($at)||!is_string($adminApplyAt))return null;
    return hash('sha256',"finalize\0".hash_file('sha256',$inputs['connection'])."\0".hash('sha256',$inputs['target'])."\0".$inputs['slug']."\0".$adminApplyAt."\0".$at);
}

function dqs_install_web_finalize_run($mode,array $inputs)
{
    $params=array('mode'=>$mode,'connection_file'=>$inputs['connection'],'target_root'=>$inputs['target'],'admin_slug'=>$inputs['slug']);
    $policy=array('target_roots'=>array($inputs['target']),'allow_repo_target'=>true,'timeout_seconds'=>$mode==='apply'?120:60,'max_output_bytes'=>262144);
    if($mode==='apply'){$params['confirm_finalize']=true;$policy['allow_finalize_apply']=true;}
    return dqs_install_web_preflight_safe_result(dqs_install_execute('finalize',$params,$policy));
}
