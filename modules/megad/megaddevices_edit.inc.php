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

   global $mdid;
   $rec['MDID']=$mdid;


  //updating 'IP' (varchar)
   global $ip;
   $rec['IP']=$ip;

   global $type_main;
   $rec['TYPE_MAIN']=$type_main;

   global $type;
   $rec['TYPE']=$type;

   global $password;
   if (!$password) {
    $password='sec';
   }
   $rec['PASSWORD']=$password;

   global $update_period;
   $rec['UPDATE_PERIOD']=(int)$update_period;

   $rec['NEXT_UPDATE']=date('Y-m-d H:i:s',time()+$rec['UPDATE_PERIOD']);

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
  // step: config
  if ($this->tab=='config') {


   if ($this->mode=='upgrade_firmware') {
    $url=BASE_URL.'/modules/megad/megad-cfg.php';
    $url.='?ip='.urlencode($rec['IP']).'&p='.urlencode($rec['PASSWORD']).'&w=1';
    if ($this->config['API_IP']) {
     $url.='&local-ip='.$this->config['API_IP'];
    }
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
    $url='http://'.$rec['IP'].'/'.$rec['PASSWORD'].'/?cf=1&sip='.$server_ip."&sct=".urlencode($server_script);
    if ($this->config['API_IP']) {
     $url.='&local-ip='.$this->config['API_IP'];
    }
    $data=getURL($url, 0);
    $data='OK';
    $this->redirect("?view_mode=".$this->view_mode."&tab=config"."&id=".$rec['ID']."&result=".urlencode($data));
   }

   if ($this->mode=='write_config') {
    global $config;

    SaveFile(ROOT.'cached/megad.cfg', $config);
    $url=BASE_URL.'/modules/megad/megad-cfg.php';
    $url.='?ip='.urlencode($rec['IP']).'&write-conf='.urlencode(ROOT.'cached/megad.cfg').'&p='.urlencode($rec['PASSWORD']);
    if ($this->config['API_IP']) {
     $url.='&local-ip='.$this->config['API_IP'];
    }
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
     $url='http://'.$rec['IP'].'/'.$rec['PASSWORD'].'/?cf=1&eip='.$ip;
     if ($this->config['API_IP']) {
      $url.='&local-ip='.$this->config['API_IP'];
     }
     $data=getURL($url, 0);
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
   $properties=SQLSelect("SELECT * FROM megadproperties WHERE DEVICE_ID='".$rec['ID']."' ORDER BY NUM, TYPE");
   $total=count($properties);
   for($i=0;$i<$total;$i++) {
    if ($this->mode=='update' && $this->tab=='data') {

     global ${"skip_default".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     $properties[$i]['SKIP_DEFAULT']=(int)${"skip_default".$properties[$i]['TYPE'].$properties[$i]['NUM']};

     global ${"linked_object".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     global ${"linked_property".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     global ${"linked_method".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     $properties[$i]['LINKED_OBJECT']=${"linked_object".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     $properties[$i]['LINKED_PROPERTY']=${"linked_property".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     $properties[$i]['LINKED_METHOD']=${"linked_method".$properties[$i]['TYPE'].$properties[$i]['NUM']};

     global ${"linked_object2_".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     global ${"linked_property2_".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     global ${"linked_method2_".$properties[$i]['TYPE'].$properties[$i]['NUM']};

     $properties[$i]['LINKED_OBJECT2']=${"linked_object2_".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     $properties[$i]['LINKED_PROPERTY2']=${"linked_property2_".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     $properties[$i]['LINKED_METHOD2']=${"linked_method2_".$properties[$i]['TYPE'].$properties[$i]['NUM']};


     SQLUpdate('megadproperties', $properties[$i]);
     if ($properties[$i]['LINKED_OBJECT']) {
      addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
     }

     if ($properties[$i]['LINKED_OBJECT2']) {
      addLinkedProperty($properties[$i]['LINKED_OBJECT2'], $properties[$i]['LINKED_PROPERTY2'], $this->name);
     }

     $this->readValues($rec['ID']);

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
