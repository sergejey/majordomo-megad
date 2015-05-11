<?php

 $url=BASE_URL.'/modules/megad/megad-cfg.php';
 $url.='?scan=1';

 $data=getURL($url, 0);

 if ($data!='') {
  $lines=explode("\n", $data);
  $total=count($lines);
  for($i=0;$i<$total;$i++) {
   $ip=trim($lines[$i]);
   if ($ip) {
    $rec=SQLSelectOne("SELECT * FROM megaddevices WHERE IP='".DBSafe($ip)."'");
    if (!$rec['ID']) {
      $rec=array();
      $rec['IP']=$ip;
      $rec['TITLE']='MegaD '.$rec['IP'];
      $rec['PASSWORD']='sec';
      $rec['ID']=SQLInsert('megaddevices', $rec);
      $this->readConfig($rec['ID']);
    }
   }
  }
 }
