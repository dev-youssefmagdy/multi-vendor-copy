<?php
require '/var/www/multi-vendor/vendor/autoload.php';
$app = require '/var/www/multi-vendor/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$out=[];
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/var/www/multi-vendor/app/Models'));
foreach($it as $f){ if($f->getExtension()!=='php')continue;
 $rel=str_replace(['/var/www/multi-vendor/app/','.php','/'],['App/','','\\'],$f->getPathname());
 if(!class_exists($rel))continue; $rc=new ReflectionClass($rel); if($rc->isAbstract()||!$rc->isSubclassOf(Illuminate\Database\Eloquent\Model::class))continue;
 try{$m=$rc->newInstanceWithoutConstructor(); $t=(new $rel)->getTable();}catch(Throwable $e){$t='?';}
 $traits=array_map(fn($x)=>basename(str_replace('\\','/',$x)),array_keys(class_uses_recursive($rel)));
 $rels=[];
 foreach($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $mm){ if($mm->class!==$rel||$mm->getNumberOfParameters()>0)continue; $rt=$mm->getReturnType(); $src=implode('',array_slice(file($mm->getFileName()),$mm->getStartLine()-1,$mm->getEndLine()-$mm->getStartLine()+1));
   if(preg_match('/->(belongsTo|hasMany|hasOne|belongsToMany|morphTo|morphMany|morphOne|morphToMany|hasManyThrough|hasOneThrough)\(\s*([A-Za-z\\\\:]+)?/',$src,$mt)) $rels[]=$mm->name.':'.$mt[1].($mt[2]??'' ? '('.str_replace('::class','',basename(str_replace('\\','/',$mt[2]))).')':'');
 }
 $out[]=['class'=>str_replace('App\\Models\\','',$rel),'table'=>$t,'central'=>in_array('CentralConnection',$traits),'traits'=>array_values(array_intersect($traits,['SoftDeletes','CentralConnection','HasTranslations','HasDatabase','HasDomains','HasFactory','HasCatalogRefs'])),'rels'=>$rels];
}
file_put_contents($argv[1],json_encode($out)); echo count($out)."\n";
