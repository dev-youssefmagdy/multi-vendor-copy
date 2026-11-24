<?php
require '/var/www/multi-vendor/vendor/autoload.php';
$app = require '/var/www/multi-vendor/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$out=[];
$dirs=['app/Livewire','app/Http/Controllers'];
foreach($dirs as $dir){
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/var/www/multi-vendor/'.$dir));
foreach($it as $f){ if($f->getExtension()!=='php')continue;
 $cls=str_replace(['/var/www/multi-vendor/app/','.php','/'],['App/','','\\'],$f->getPathname());
 try{ if(!class_exists($cls))continue; $rc=new ReflectionClass($cls);}catch(Throwable $e){continue;}
 if($rc->isAbstract()||$rc->isInterface()||$rc->isTrait())continue;
 $src=file_get_contents($f->getPathname());
 $props=[];foreach($rc->getProperties(ReflectionProperty::IS_PUBLIC) as $p){ if($p->class===$cls && !$p->isStatic()) $props[]=$p->name; }
 $methods=[];foreach($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $m){ if($m->class===$cls && !$m->isStatic() && !str_starts_with($m->name,'__') && !in_array($m->name,['render','boot','booted','hydrate','dehydrate','middleware'])) $methods[]=$m->name; }
 preg_match_all("/'([a-zA-Z_.*]+)'\s*=>\s*(\[[^\]]*\]|'[^']*'|\"[^\"]*\")/",
   preg_match('/function rules\(\)[^{]*\{(.*?)\n    \}/s',$src,$mm)?$mm[1]:'', $rm);
 $rules=[];foreach($rm[1] as $i=>$k){$rules[$k]=trim(preg_replace('/\s+/',' ',$rm[2][$i]),"'\"[] ");}
 preg_match_all("/#\[Validate\(([^)]*)\)\]\s*public\s+[^$]*\\$(\w+)/",$src,$va); foreach($va[2] as $i=>$k)$rules[$k]=trim($va[1][$i],"'\"[] ");
 preg_match_all("/(?:view|layout)\(\s*'([^']+)'/",$src,$vw);
 preg_match_all("/->validate\(\s*\[(.*?)\]\s*\)/s",$src,$vv); foreach($vv[1] as $blk){ preg_match_all("/'([a-zA-Z_.*]+)'\s*=>\s*(\[[^\]]*\]|'[^']*')/",$blk,$r2); foreach($r2[1] as $i=>$k)$rules[$k]=trim(preg_replace('/\s+/',' ',$r2[2][$i]),"'\"[] "); }
 $doc=$rc->getDocComment(); $doc=$doc?trim(preg_replace('/\s*\*\s?/',' ',preg_replace('#^/\*\*|\*/$#','',$doc))):'';
 $out[]=['class'=>$cls,'file'=>str_replace('/var/www/multi-vendor/','',$f->getPathname()),'loc'=>substr_count($src,"\n"),'props'=>$props,'methods'=>$methods,'rules'=>$rules,'views'=>array_values(array_unique($vw[1])),'doc'=>mb_substr($doc,0,300)];
}}
// form requests
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/var/www/multi-vendor/app/Http/Requests'));
foreach($it as $f){ if($f->getExtension()!=='php')continue; $src=file_get_contents($f->getPathname());
 preg_match('/function rules\(\)[^{]*\{(.*?)\n    \}/s',$src,$mm); preg_match_all("/'([a-zA-Z_.*]+)'\s*=>\s*(\[[^\]]*\]|'[^']*'|\"[^\"]*\")/",$mm[1]??'',$rm);
 $rules=[];foreach($rm[1] as $i=>$k){$rules[$k]=trim(preg_replace('/\s+/',' ',$rm[2][$i]),"'\"[] ");}
 $out[]=['class'=>str_replace(['/var/www/multi-vendor/app/','.php','/'],['App/','','\\'],$f->getPathname()),'file'=>str_replace('/var/www/multi-vendor/','',$f->getPathname()),'loc'=>substr_count($src,"\n"),'props'=>[],'methods'=>[],'rules'=>$rules,'views'=>[],'doc'=>''];
}
file_put_contents($argv[1],json_encode($out)); echo count($out),"\n";
