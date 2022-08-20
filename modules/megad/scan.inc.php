<?php

 $url=BASE_URL.'/modules/megad/megad-cfg-2561.php';
 $url.='?scan=1';

 $local_ip = '';
 if ($this->config['API_IP']) {
     $local_ip = $this->config['API_IP'];
 } else {
     $local_ip = getLocalIp();
 }
 if ($local_ip) {
     $url.='&local-ip='.$local_ip;
 }

 $data=getURL($url, 0);

 if ($data!='') {
  $lines=explode("\n", $data);
  $total=count($lines);
  for($i=0;$i<$total;$i++) {
   $ip=trim($lines[$i]);
   if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $ip)) {
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
