<?php

 $record=SQLSelectOne("SELECT * FROM megaddevices WHERE ID='".(int)$id."'");


 $url='http://'.$record['IP'].'/'.$record['PASSWORD'].'/?cmd=all';
 if ($all) {
  $stateData=$all;
 } else {
  $stateData=getURL($url, 0);
 }

 //echo $stateData;exit;

 $states=explode(';', $stateData);

 $total=count($states);
 for($i=0;$i<$total;$i++) {
  

     $prop=SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='".$record['ID']."' AND NUM='".$i."'");

     $type=(int)$prop['TYPE'];
     $mode=(int)$prop['MODE'];

     if (!$prop['ID']) {
      continue;
     }

     //echo $type.' '.$states[$i]."<br/>";
     $old_value=$prop['CURRENT_VALUE_STRING'];
     $old_value2=$prop['CURRENT_VALUE_STRING2'];

     if ($states[$i]!=='') {
      if ($type==0) {
       $tmp=explode('/', $states[$i]);
       if ($tmp[0]=='ON') {
        $prop['CURRENT_VALUE_STRING']=1;
       } else {
        $prop['CURRENT_VALUE_STRING']=0;
       }
       if (isset($tmp[1])) {
        $prop['CURRENT_VALUE_STRING2']=$tmp[1];
        $prop['COUNTER']=$tmp[1];
       }
      } elseif ($type==1) {

       if($mode==1){
        $prop['CURRENT_VALUE_STRING']=intval($states[$i]);
       } else {
         if ($states[$i]=='ON') {
          $prop['CURRENT_VALUE_STRING']=1;
         } else {
          $prop['CURRENT_VALUE_STRING']=0;
         }
       }


      } elseif ($type==3 && preg_match('/temp:([\d\.]+)\/hum:([\d\.]+)/', $states[$i], $m)) {
       $prop['CURRENT_VALUE_STRING']=$m[1];
       $prop['CURRENT_VALUE_STRING2']=$m[2];
      } else {
       $tmp=explode('/', $states[$i]);
       $tmp[0]=str_replace("temp:", "", $tmp[0]);
       $tmp[0]=str_replace("hum:", "", $tmp[0]);
       $prop['CURRENT_VALUE_STRING']=$tmp[0];
       if (isset($tmp[1])) {
        $tmp[1]=str_replace("temp:", "", $tmp[1]);
        $tmp[1]=str_replace("hum:", "", $tmp[1]);
        $prop['CURRENT_VALUE_STRING2']=$tmp[1];
       }
      }
     }

     $prop['UPDATED']=date('Y-m-d H:i:s');
     SQLUpdate('megadproperties', $prop);

     //echo $stateData;exit;


    if ($prop['LINKED_OBJECT'] && $prop['LINKED_PROPERTY']) {
     if ($old_value!=$prop['CURRENT_VALUE_STRING'] || $prop['CURRENT_VALUE_STRING']!=gg($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'])) {
      setGlobal($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'], $prop['CURRENT_VALUE_STRING'], array($this->name=>'0'));
     }
    }

    if ($prop['LINKED_OBJECT'] && $prop['LINKED_METHOD'] && ($old_value!=$prop['CURRENT_VALUE_STRING'])) {
     $params=array();
     $params['TITLE']=$record['TITLE'];
     $params['VALUE']=$prop['CURRENT_VALUE_STRING'];
     $params['value']=$params['VALUE'];
     $params['port']=$i;
     $params['m']=$m; 
     callMethod($prop['LINKED_OBJECT'].'.'.$prop['LINKED_METHOD'], $params);
    }

    if ($prop['LINKED_OBJECT2'] && $prop['LINKED_PROPERTY2']) {
     if ($old_value2!=$prop['CURRENT_VALUE_STRING2'] || $prop['CURRENT_VALUE_STRING2']!=gg($prop['LINKED_OBJECT2'].'.'.$prop['LINKED_PROPERTY2'])) {
      setGlobal($prop['LINKED_OBJECT2'].'.'.$prop['LINKED_PROPERTY2'], $prop['CURRENT_VALUE_STRING2'], array($this->name=>'0'));
     }
    }

    if ($prop['LINKED_OBJECT2'] && $prop['LINKED_METHOD2'] && ($old_value2!=$prop['CURRENT_VALUE_STRING2'])) {
     $params=array();
     $params['TITLE']=$record['TITLE'];
     $params['VALUE']=$prop['CURRENT_VALUE_STRING2'];
     $params['value']=$params['VALUE'];
     $params['port']=$i;
     $params['m']=$m; 
     callMethod($prop['LINKED_OBJECT2'].'.'.$prop['LINKED_METHOD2'], $params);
    }


 }


//internal temp sensor data
$prop=SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='".$record['ID']."' AND TYPE='100'");
if ($prop['ID']) {
 $stateData=getURL('http://'.$record['IP'].'/'.$record['PASSWORD'].'/?tget=1', 0);
 $old_value=$prop['CURRENT_VALUE_STRING'];
 if ($stateData!='') {
  $prop['CURRENT_VALUE_STRING']=$stateData;
  $prop['UPDATED']=date('Y-m-d H:i:s');
  SQLUpdate('megadproperties', $prop);

    if ($prop['LINKED_OBJECT'] && $prop['LINKED_PROPERTY']) {
     if ($old_value!=$prop['CURRENT_VALUE_STRING'] || $prop['CURRENT_VALUE_STRING']!=gg($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'])) {
      setGlobal($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'], $prop['CURRENT_VALUE_STRING'], array($this->name=>'0'));
     }
    }

 }
}


