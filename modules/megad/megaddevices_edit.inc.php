<?php
/*
* @version 0.1 (wizard)
*/
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='megaddevices';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  if ($this->mode=='update') {
   $ok=1;
  // step: default
  if ($this->tab=='') {
  //updating 'TITLE' (varchar, required)
   global $title;
   $rec['TITLE']=$title;
   if ($rec['TITLE']=='') {
    $out['ERR_TITLE']=1;
    $ok=0;
   }

  //updating 'IP' (varchar)
   global $ip;
   $rec['IP']=$ip;

   global $password;
   if (!$password) {
    $password='sec';
   }
   $rec['PASSWORD']=$password;

  }
  // step: config
  if ($this->tab=='config') {
  //updating 'CONFIG' (text)
   //global $config;
   $config=array();
   $rec['CONFIG']=serialize($config);
  }
  //UPDATING RECORD
   if ($ok) {
    if ($rec['ID']) {
     SQLUpdate($table_name, $rec); // update

     $this->readConfig($rec['ID']);

    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record

     $this->readConfig($rec['ID']);
     /*
     $total=8;
     for($i=0;$i<$total;$i++) {
      $prop=array();
      $prop['DEVICE_ID']=$rec['ID'];
      $prop['NUM']=$i;
      $prop['TYPE']=0;
      SQLInsert('megadproperties', $prop);

      unset($prop['ID']);
      $prop['TYPE']=1;
      SQLInsert('megadproperties', $prop);
     }
     */

    }
    $out['OK']=1;
   } else {
    $out['ERR']=1;
   }
  }
  // step: default
  if ($this->tab=='') {
  //options for 'TYPE' (select)
  $tmp=explode('|', DEF_TYPE_OPTIONS);
  foreach($tmp as $v) {
   if (preg_match('/(.+)=(.+)/', $v, $matches)) {
    $value=$matches[1];
    $title=$matches[2];
   } else {
    $value=$v;
    $title=$v;
   }
   $out['TYPE_OPTIONS'][]=array('VALUE'=>$value, 'TITLE'=>$title);
   $type_opt[$value]=$title;
  }
  for($i=0;$i<count($out['TYPE_OPTIONS']);$i++) {
   if ($out['TYPE_OPTIONS'][$i]['VALUE']==$rec['TYPE']) {
    $out['TYPE_OPTIONS'][$i]['SELECTED']=1;
    $out['TYPE']=$out['TYPE_OPTIONS'][$i]['TITLE'];
    $rec['TYPE']=$out['TYPE_OPTIONS'][$i]['TITLE'];
   }
  }
  }
  // step: config
  if ($this->tab=='config') {


   if ($this->mode=='upgrade_firmware') {
    $url=BASE_URL.'/modules/megad/megad-cfg.php';
    $url.='?ip='.urlencode($rec['IP']).'&p='.urlencode($rec['PASSWORD']).'&w=1';
    global $beta;
    if ($beta) {
     $url.="&b=1";
    }

    global $clear;
    if ($clear) {
     $url.="&ee=1";
    }

    //echo $url;exit;

    $data=getURL($url, 0);
    if (!$data) {
     $data='OK';
    }
    $this->redirect("?view_mode=".$this->view_mode."&tab=config"."&id=".$rec['ID']."&result=".urlencode($data));
   }

   if ($this->mode=='set_server') {
    global $server_ip;
    global $server_script;
    $data=getURL('http://'.$rec['IP'].'/'.$rec['PASSWORD'].'/?cf=1&sip='.$server_ip."&sct=".urlencode($server_script), 0);
    $data='OK';
    $this->redirect("?view_mode=".$this->view_mode."&tab=config"."&id=".$rec['ID']."&result=".urlencode($data));
   }

   if ($this->mode=='write_config') {
    global $config;

    SaveFile(ROOT.'cached/megad.cfg', $config);
    $url=BASE_URL.'/modules/megad/megad-cfg.php';
    $url.='?ip='.urlencode($rec['IP']).'&write-conf='.urlencode(ROOT.'cached/megad.cfg').'&p='.urlencode($rec['PASSWORD']);
    $data=getURL($url, 0);

    $this->readConfig($rec['ID']);
    $data='OK';
    $this->redirect("?view_mode=".$this->view_mode."&tab=config"."&id=".$rec['ID']."&result=".urlencode($data));
   }

   if ($this->mode=='read_config') {
    $this->readConfig($rec['ID']);
    $data='OK';
    $this->redirect("?view_mode=".$this->view_mode."&tab=config"."&id=".$rec['ID']."&result=".urlencode($data));
   }

   if ($this->mode=='set_address') {
    global $ip;
    if ($ip!=$rec['IP']) {
     $data=getURL('http://'.$rec['IP'].'/'.$rec['PASSWORD'].'/?cf=1&eip='.$ip, 0);
     if (preg_match('/Back/is', $data)) {
      $rec['IP']=$ip;
      SQLUpdate('megaddevices', $rec);
      $data='OK';
     }
     $this->redirect("?view_mode=".$this->view_mode."&tab=config"."&id=".$rec['ID']."&result=".urlencode($data));
    }
   }


   $out['SERVER_IP']=$this->get_local_ip();

  }
  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);

  if ($rec['ID']) {
   $properties=SQLSelect("SELECT * FROM megadproperties WHERE DEVICE_ID='".$rec['ID']."' ORDER BY TYPE, NUM");
   $total=count($properties);
   for($i=0;$i<$total;$i++) {
    if ($this->mode=='update' && $this->tab=='data') {
     global ${"linked_object".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     global ${"linked_property".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     $properties[$i]['LINKED_OBJECT']=${"linked_object".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     $properties[$i]['LINKED_PROPERTY']=${"linked_property".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     SQLUpdate('megadproperties', $properties[$i]);
     if ($properties[$i]['LINKED_OBJECT']) {
      addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
     }
    }
   }
   $out['PROPERTIES']=$properties;

  }



  if ($this->mode=='getdata') {
   $this->readValues($rec['ID']);
   $this->redirect("?view_mode=".$this->view_mode."&tab=".$this->tab."&id=".$rec['ID']);
  }

  global $result;
  $out['RESULT']=$result;
