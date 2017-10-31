<?php
/**
* megad 
*
* megad
*
* @package project
* @author Serge J. <jey@tut.by>
* MegaD API: http://ab-log.ru/smart-house/ethernet/megad-328-api
* @copyright http://www.atmatic.eu/ (c)
* @version 0.1 (wizard, 12:04:34 [Apr 09, 2015])
*/
//
//
class megad extends module {
/**
* megad
*
* Module class constructor
*
* @access private
*/
function megad() {
  $this->name="megad";
  $this->title="MegaD";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  if (IsSet($this->device_id)) {
   $out['IS_SET_DEVICE_ID']=1;
  }
  if ($this->single_rec) {
   $out['SINGLE_REC']=1;
  }
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }

 $this->getConfig();
 $out['API_IP']=$this->config['API_IP'];
 if (!$out['API_IP']) {
  $out['API_IP']=$this->get_local_ip();
 }
 $out['API_DEBUG']=$this->config['API_DEBUG'];

 if ($this->view_mode=='update_settings') {
   global $api_ip;
   $this->config['API_IP']=$api_ip;
   global $api_debug;
   $this->config['API_DEBUG']=(int)$api_debug;
   $this->saveConfig();
   $this->redirect("?");
 }


 if ($this->data_source=='megaddevices' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_megaddevices') {
   $this->search_megaddevices($out);
  }
  if ($this->view_mode=='edit_megaddevices') {
   $this->edit_megaddevices($out, $this->id);
  }
  if ($this->view_mode=='delete_megaddevices') {
   $this->delete_megaddevices($this->id);
   $this->redirect("?data_source=megaddevices");
  }

  if ($this->view_mode=='scan') {
   $this->scan();
   $this->redirect("?data_source=megaddevices");
  }



 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='megadproperties') {
  if ($this->view_mode=='' || $this->view_mode=='search_megadproperties') {
   $this->search_megadproperties($out);
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 //$this->admin($out);
 $device=$_GET['device'];
 $command=$_GET['command'];

 if ($this->ajax) {
  if ($_GET['op']=='processCycle') {
   $this->processCycle();
  }
 }
 
 
 if ($device && $command) {

  if ($this->sendCommand($device, $command)) {
   echo "OK";
  } else {
   echo "Error";
  }
 }

}
/**
* megaddevices search
*
* @access public
*/
 function search_megaddevices(&$out) {
  require(DIR_MODULES.$this->name.'/megaddevices_search.inc.php');
 }

 function readConfig($id) {
  require(DIR_MODULES.$this->name.'/readconfig.inc.php');
 }

 function readValues($id, $all='') {
  require(DIR_MODULES.$this->name.'/readvalues.inc.php');
 }

 function scan() {
  require(DIR_MODULES.$this->name.'/scan.inc.php');
 }




/**
* megaddevices edit/add
*
* @access public
*/
 function edit_megaddevices(&$out, $id) {
  require(DIR_MODULES.$this->name.'/megaddevices_edit.inc.php');
 }

/**
* Title
*
* Description
*
* @access public
*/
 function refreshDevice($id) {
  $rec=SQLSelectOne("SELECT * FROM megaddevices WHERE ID='".$id."'");
  if (!$rec['ID']) {
   return;
  }

  $this->readValues($rec['ID']);

 }

/**
* megaddevices delete record
*
* @access public
*/
 function delete_megaddevices($id) {
  $rec=SQLSelectOne("SELECT * FROM megaddevices WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM megadproperties WHERE DEVICE_ID='".$rec['ID']."'");
  SQLExec("DELETE FROM megaddevices WHERE ID='".$rec['ID']."'");
  
 }

 function propertySetHandle($object, $property, $value) {
   $properties=SQLSelect("SELECT ID FROM megadproperties WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."' AND TYPE=1");
   $total=count($properties);
   if ($total) {
      for($i=0;$i<$total;$i++) {
         $this->setProperty($properties[$i]['ID'], $value);
      }
   }
 }


 function processCycle() {
   $this->updateDevices();
 }

/**
* Title
*
* Description
*
* @access public
*/
 function processRequest() {

  global $mdid;
  global $st;

  $ip=$_SERVER['REMOTE_ADDR'];

  $ecmd='';

  if ($mdid) {
   $rec=SQLSelectOne("SELECT * FROM megaddevices WHERE MDID LIKE '".trim($mdid)."%'");
  }

  if ($st=='1' && $rec['ID']) {
   //restore on start
   $this->restoreDeviceStatus($rec['ID']);
   return;
  }

  if (!$rec['ID']) {
   $rec=SQLSelectOne("SELECT * FROM megaddevices WHERE IP='".$ip."'");
  }


  if (!$rec['ID']) {
   $rec=array();
   $rec['IP']=$ip;
   $rec['TITLE']='MegaD '.$rec['IP'];
   $rec['MDID']=trim($mdid);
   $rec['PASSWORD']='sec';
   $rec['ID']=SQLInsert('megaddevices', $rec);
   $this->readConfig($rec['ID']);
  } else {
   //processing
   /*
   global $pt; //port
   global $at; // internal temperature
   global $v; // value for ADC
   global $dir; //direction 1/0
   global $cnt; //counter
   global $all;
   */

   if (isset($_GET['v'])) {
    $v=$_GET['v'];
   }

   $m=$_GET['m'];
   $pt=$_GET['pt'];
   $at=$_GET['at'];
   $dir=$_GET['dir'];
   $cnt=$_GET['cnt'];

   if (isset($_GET['all'])) {
    $this->readValues($rec['ID'], $_GET['all']);
   }

   //input data changed
   if (isset($pt)) {
    $prop=SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID=".$rec['ID']." AND NUM='".DBSafe($pt)."'");
    if ($prop['ID']) {
     //
     if ($prop['ECMD'] && !($prop['SKIP_DEFAULT'])) {
      $ecmd=$prop['ECMD'];
     }
     unset($value2);
     if (isset($v)) {
      $value=$v;
     } elseif ($_GET['wg']) {
      $value=$_GET['wg'];
     } elseif ($_GET['ib']) {
      $value=$_GET['ib'];
     } elseif ($_GET['click']) {
      $value=$_GET['click'];
     } else {
      if ($m=='1') {
       $value=0;
      //} elseif ($m=='2') {
      // $value=2;
      } else {
       $value=1;
      }
     }
     if (isset($cnt)) {
      $prop['COUNTER']=$cnt;
      $value2=$prop['COUNTER'];
     }
     $old_value=$prop['CURRENT_VALUE_STRING'];
     $old_value2=$prop['CURRENT_VALUE_STRING2'];

     $prop['CURRENT_VALUE_STRING']=$value;
     $prop['UPDATED']=date('Y-m-d H:i:s');
     SQLUpdate('megadproperties', $prop);

     if ($prop['LINKED_OBJECT'] && $prop['LINKED_PROPERTY']) {
      if ($old_value!=$prop['CURRENT_VALUE_STRING'] || $prop['CURRENT_VALUE_STRING']!=gg($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'])) {
       setGlobal($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'], $prop['CURRENT_VALUE_STRING'], array($this->name=>'0'));
      }
     }

     if ($prop['LINKED_OBJECT'] && $prop['LINKED_METHOD']) { // && $old_value!=$prop['CURRENT_VALUE_STRING']
      $params=array();
      $params=$_REQUEST;
      $params['TITLE']=$rec['TITLE'];
      $params['VALUE']=$prop['CURRENT_VALUE_STRING'];
      $params['value']=$params['VALUE'];
      $params['port']=$prop['NUM'];
      $methodRes=callMethod($prop['LINKED_OBJECT'].'.'.$prop['LINKED_METHOD'], $params);

      if (is_string($methodRes)) {
       $ecmd=$methodRes;
      }

     }

     if (isset($value2)) {
      $prop['CURRENT_VALUE_STRING2']=$value2;
      $prop['UPDATED']=date('Y-m-d H:i:s');
      SQLUpdate('megadproperties', $prop);

      if ($prop['LINKED_OBJECT2'] && $prop['LINKED_PROPERTY2']) {
       if ($old_value!=$prop['CURRENT_VALUE_STRING2'] || $prop['CURRENT_VALUE_STRING2']!=gg($prop['LINKED_OBJECT2'].'.'.$prop['LINKED_PROPERTY2'])) {
        setGlobal($prop['LINKED_OBJECT2'].'.'.$prop['LINKED_PROPERTY2'], $prop['CURRENT_VALUE_STRING2'], array($this->name=>'0'));
       }
      }

      if ($prop['LINKED_OBJECT2'] && $prop['LINKED_METHOD2']) { // && $old_value2!=$prop['CURRENT_VALUE_STRING2']
       $params=array();
       $params=$_REQUEST;
       $params['TITLE']=$rec['TITLE'];
       $params['VALUE']=$prop['CURRENT_VALUE_STRING2'];
       $params['value']=$params['VALUE'];
       $params['port']=$prop['NUM'];
       callMethod($prop['LINKED_OBJECT2'].'.'.$prop['LINKED_METHOD2'], $params);
      }
     }



    }
   }

   // internal temp sensor data
   if (isset($at)) {
    $prop=SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='".$rec['ID']."' AND TYPE='100'");
    $value=$at;

    if ($prop['ID']) {
     $old_value=$prop['CURRENT_VALUE_STRING'];
     $prop['CURRENT_VALUE_STRING']=$value;
     $prop['UPDATED']=date('Y-m-d H:i:s');
     SQLUpdate('megadproperties', $prop);

     if ($prop['LINKED_OBJECT'] && $prop['LINKED_PROPERTY']) {
      if ($old_value!=$prop['CURRENT_VALUE_STRING'] || $prop['CURRENT_VALUE_STRING']!=gg($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'])) {
       setGlobal($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'], $prop['CURRENT_VALUE_STRING'], array($this->name=>'0'));
      }
     }
    }


   }


  }

  if ($ecmd) {
   header_remove();
   header ('Content-Type:text/html;charset=windows-1251');

   if (preg_match('/(\d+):3/is', $ecmd, $m)) {
    $ecmd=$m[1].':'.(int)$prop['CURRENT_VALUE_STRING'];
   }
   if (preg_match('/(\d+):4/is', $ecmd, $m)) {
    if ((int)$prop['CURRENT_VALUE_STRING']) {
     $ecmd=$m[1].':0';
    } else {
     $ecmd=$m[1].':1';
    }
   }
   echo trim(utf2win($ecmd));

   $mega_id=$rec['ID'];
   $code='';
   $code.='include_once(DIR_MODULES."megad/megad.class.php");';
   $code.='$mega=new megad();';
   $code.='$mega->readValues('.(int)$mega_id.');';
   setTimeOut('mega_refresh_'.$mega_id, $code, 1);

  }

 }

 /**
 * Title
 *
 * Description
 *
 * @access public
 */
  function updateDevices() {
   $devices=SQLSelect("SELECT * FROM megaddevices WHERE UPDATE_PERIOD>0 AND NEXT_UPDATE<=NOW()");
   $total=count($devices);
   for($i=0;$i<$total;$i++) {
    $devices[$i]['NEXT_UPDATE']=date('Y-m-d H:i:s', time()+$devices[$i]['UPDATE_PERIOD']);
    SQLUpdate('megaddevices',$devices[$i]);
    $this->refreshDevice($devices[$i]['ID']);
   }
  }


/**
* Title
*
* Description
*
* @access public
*/
 function sendCommand($id, $command, $custom=false) {
  $device=SQLSelectOne("SELECT * FROM megaddevices WHERE ID='".$id."'");  
  if (!$device['ID']) {
   $device=SQLSelectOne("SELECT * FROM megaddevices WHERE TITLE LIKE '".DBSafe($id)."'");  
  }
  if (!$device['ID']) {
   $device=SQLSelectOne("SELECT * FROM megaddevices WHERE IP='".DBSafe($id)."'");  
  }
  if ($device['ID']) {
   $url='http://'.$device['IP'].'/'.$device['PASSWORD'].'/?'.($custom ? '' : 'cmd=').$command;
   getURL($url, 0);
   return 1;
  } else {
   return 0;
  }
 }


/**
* Title
*
* Description
*
* @access public
*/
 function setProperty($property_id, $value) {
  $prop=SQLSelectOne("SELECT * FROM megadproperties WHERE ID='".$property_id."'");
  $prop['CURRENT_VALUE_STRING']=$value;
  SQLUpdate('megadproperties', $prop);

  $channel=$prop['NUM'];
  if ($prop['TYPE']==1) { // output
   $this->sendCommand($prop['DEVICE_ID'], $channel.':'.$value);
  } elseif ($prop['TYPE']==101) { // raw command
   $this->sendCommand($prop['DEVICE_ID'], $value);
  }
  $this->readValues($prop['DEVICE_ID']);
 }

 function restoreDeviceStatus($device_id) {
  $properties=SQLSelect("SELECT * FROM megadproperties WHERE DEVICE_ID=".$device_id);
  $total = count($properties);
  for ($i = 0; $i < $total; $i++) {
   if ($properties[$i]['TYPE']==1) {
    $this->sendCommand($properties[$i]['DEVICE_ID'], $properties[$i]['NUM'].':'.$properties[$i]['CURRENT_VALUE_STRING']);
    if ($i<($total-1)) {
     usleep(500);
    }
   }
  }
 }


/**
* megadproperties search
*
* @access public
*/
 function search_megadproperties(&$out) {
  require(DIR_MODULES.$this->name.'/megadproperties_search.inc.php');
 }

/**
* Title
*
* Description
*
* @access public
*/
 function get_local_ip() {

                if ( preg_match("/^WIN/", PHP_OS) )
                 $find_ip = $this->get_local_ip_win();
                else
                {
                        $find_ip = $this->get_local_ip_linux();
                }

                        $local_ip='';
                        foreach ( $find_ip as $iface => $iface_ip)
                        {
                                if ( (preg_match("/^192\.168/", $find_ip[$iface]) || preg_match("/^10\./", $find_ip[$iface])) ) {
                                  $local_ip = $find_ip[$iface];
                                  break;
                                }
                        }

                return $local_ip;
  
 }

function get_local_ip_linux()
{
        $out = explode(PHP_EOL,shell_exec("/sbin/ifconfig"));
        $local_addrs = array();
        $ifname = 'unknown';
        foreach($out as $str)
        {
                $matches = array();
                if(preg_match('/^([a-z0-9]+)(:\d{1,2})?(\s)+Link/',$str,$matches))
                {
                        $ifname = $matches[1];
                        if(strlen($matches[2])>0)
                        $ifname .= $matches[2];
                }
                elseif(preg_match('/inet addr:((?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3})\s/',$str,$matches))
                $local_addrs[$ifname] = $matches[1];
        }
        return $local_addrs;
}

function get_local_ip_win()
{
        $out = explode("\n",shell_exec("ipconfig"));

        $local_addrs = array();
        foreach($out as $str)
        {
                if (preg_match('/IPv4/',$str))
                $local_addrs[trim($str)] = preg_replace("/.*:\s(\d+)\.(\d+)\.(\d+)\.(\d+)/", "$1.$2.$3.$4", $str);
        }
        return $local_addrs;
}


/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  setGlobal('cycle_megadControl', 'restart');
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS megaddevices');
  SQLExec('DROP TABLE IF EXISTS megadproperties');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data) {
/*
megaddevices - megad Devices
megadproperties - megad Properties
*/
  $data = <<<EOD
 megaddevices: ID int(10) unsigned NOT NULL auto_increment
 megaddevices: TITLE varchar(255) NOT NULL DEFAULT ''
 megaddevices: MDID varchar(255) NOT NULL DEFAULT ''
 megaddevices: TYPE_MAIN varchar(255) NOT NULL DEFAULT '' 
 megaddevices: TYPE varchar(255) NOT NULL DEFAULT ''
 megaddevices: CONNECTION_TYPE int(3) NOT NULL DEFAULT '0'
 megaddevices: PORT int(10) NOT NULL DEFAULT '0'
 megaddevices: IP varchar(255) NOT NULL DEFAULT ''
 megaddevices: PASSWORD varchar(255) NOT NULL DEFAULT ''
 megaddevices: ADDRESS int(3) NOT NULL DEFAULT '0'
 megaddevices: UPDATE_PERIOD int(10) NOT NULL DEFAULT '0'
 megaddevices: NEXT_UPDATE datetime
 megaddevices: CONFIG text
 megadproperties: ID int(10) unsigned NOT NULL auto_increment
 megadproperties: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 megadproperties: TYPE int(3) NOT NULL DEFAULT '0'
 megadproperties: NUM int(3) NOT NULL DEFAULT '0'
 megadproperties: CURRENT_VALUE int(10) NOT NULL DEFAULT '0'
 megadproperties: CURRENT_VALUE_STRING varchar(255) NOT NULL DEFAULT ''
 megadproperties: CURRENT_VALUE_STRING2 varchar(255) NOT NULL DEFAULT ''
 megadproperties: COUNTER int(10) NOT NULL DEFAULT '0'
 megadproperties: LINKED_OBJECT varchar(255) NOT NULL DEFAULT ''
 megadproperties: LINKED_PROPERTY varchar(255) NOT NULL DEFAULT ''
 megadproperties: LINKED_METHOD varchar(255) NOT NULL DEFAULT ''
 megadproperties: LINKED_OBJECT2 varchar(255) NOT NULL DEFAULT ''
 megadproperties: LINKED_PROPERTY2 varchar(255) NOT NULL DEFAULT ''
 megadproperties: LINKED_METHOD2 varchar(255) NOT NULL DEFAULT ''
 megadproperties: ETH varchar(255) NOT NULL DEFAULT ''
 megadproperties: ECMD varchar(255) NOT NULL DEFAULT ''
 megadproperties: PWM varchar(255) NOT NULL DEFAULT ''
 megadproperties: MODE varchar(255) NOT NULL DEFAULT ''
 megadproperties: DEF varchar(255) NOT NULL DEFAULT ''
 megadproperties: MISC varchar(255) NOT NULL DEFAULT ''
 megadproperties: SKIP_DEFAULT int(3) NOT NULL DEFAULT '0'
 megadproperties: UPDATED datetime
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgQXByIDA5LCAyMDE1IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
