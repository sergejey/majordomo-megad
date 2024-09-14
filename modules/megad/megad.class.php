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
class megad extends module
{
    /**
     * megad
     *
     * Module class constructor
     *
     * @access private
     */
    function megad()
    {
        $this->name = "megad";
        $this->title = "MegaD";
        $this->module_category = "<#LANG_SECTION_DEVICES#>";
        $this->checkInstalled();
    }

    /**
     * saveParams
     *
     * Saving module parameters
     *
     * @access public
     */
    function saveParams($data = 0)
    {
        $p = array();
        if (IsSet($this->id)) {
            $p["id"] = $this->id;
        }
        if (IsSet($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (IsSet($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (IsSet($this->data_source)) {
            $p["data_source"] = $this->data_source;
        }
        if (IsSet($this->tab)) {
            $p["tab"] = $this->tab;
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
    function getParams()
    {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $data_source;
        global $tab;
        if (isset($id)) {
            $this->id = $id;
        }
        if (isset($mode)) {
            $this->mode = $mode;
        }
        if (isset($view_mode)) {
            $this->view_mode = $view_mode;
        }
        if (isset($edit_mode)) {
            $this->edit_mode = $edit_mode;
        }
        if (isset($data_source)) {
            $this->data_source = $data_source;
        }
        if (isset($tab)) {
            $this->tab = $tab;
        }
    }

    /**
     * Run
     *
     * Description
     *
     * @access public
     */
    function run()
    {
        global $session;
        $out = array();
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (IsSet($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (IsSet($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        $out['DATA_SOURCE'] = $this->data_source;
        $out['TAB'] = $this->tab;
        if (IsSet($this->device_id)) {
            $out['IS_SET_DEVICE_ID'] = 1;
        }
        if ($this->single_rec) {
            $out['SINGLE_REC'] = 1;
        }
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }

    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out)
    {
        if ($_POST['md'] == 'megad' && $_POST['inst'] == 'adm' && $_POST['sourceip']) {


            $par = $_POST;
            unset($par['sourceurl']);
            unset($par['sourceip']);
            unset($par['pd']);
            unset($par['md']);
            unset($par['inst']);


            $sourceip = $_POST['sourceip'];
            $sourceurl = $_POST['sourceurl'];

            $pwd = SQLSelectOne('SELECT * FROM megaddevices WHERE IP="' . $sourceip . '"')['PASSWORD'];


            $url2 = $_GET['par'];

            $cmd = '';
            foreach ($par as $name => $value) {
                $cmd = $cmd . '&' . $name . '=' . urlencode(trim($value));
            }
            $newurl = 'http://' . $sourceip . '/' . $pwd . '/' . $cmd;

            $config = getURL($newurl, 0);
            echo $config;
            $redirect = "?&data_source=&view_mode=edit_megaddevices&id=2&tab=config2&address=" . $par['eip'] . '&par=' . urlencode($sourceurl);
            $this->redirect($redirect);


        }

        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }

        $this->getConfig();
        $out['API_IP'] = $this->config['API_IP'];
        if (!$out['API_IP']) {
            $out['API_IP'] = $this->get_local_ip();
        }
        $out['API_DEBUG'] = $this->config['API_DEBUG'];


        if ($this->view_mode == 'update_settings') {
            global $api_ip;
            $this->config['API_IP'] = $api_ip;
            global $api_debug;
            $this->config['API_DEBUG'] = (int)$api_debug;
            $this->saveConfig();
            $this->redirect("?");
        }


        if ($this->data_source == 'megaddevices' || $this->data_source == '') {
            if ($this->view_mode == '' || $this->view_mode == 'search_megaddevices') {
                $this->search_megaddevices($out);
            }
            if ($this->view_mode == 'edit_megaddevices') {
                $this->edit_megaddevices($out, $this->id);
            }
            if ($this->view_mode == 'delete_megaddevices') {
                $this->delete_megaddevices($this->id);
                $this->redirect("?data_source=megaddevices");
            }


            if ($this->view_mode == 'scan') {
                $this->scan();
                $this->redirect("?data_source=megaddevices");
            }


        }
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        if ($this->data_source == 'megadproperties') {
            if ($this->view_mode == '' || $this->view_mode == 'search_megadproperties') {
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
    function usual(&$out)
    {
        $device = gr('device');
        $command = gr('command');

        if ($this->ajax) {
            $op = gr('op');
            if ($op == 'processCycle') {
                $this->processCycle();
            }
            if ($op == 'readvalues') {
                $this->readValues($device);
                echo "OK";
                exit;
            }

        }

 	if ($device && gr('clearalarmwrn')) {
            $result = $this->clearalarmwrn($device);
		}



        if ($device && $command) {



            if (explode(':', $command)[0] == '100') {
                $result = $this->sendAlarm($device, explode(':', $command)[1]);
            } else {
                $result = $this->sendCommand($device, $command);
            }

            $this->readValues($device, '', 1);
            if ($result) {
                echo "OK: " . $result;
            } else {
                echo "Error";
            }
            exit;
        }

    }

    /**
     * megaddevices search
     *
     * @access public
     */
    function search_megaddevices(&$out)
    {
        require(DIR_MODULES . $this->name . '/megaddevices_search.inc.php');
    }

    function readConfig($id)
    {
        require(DIR_MODULES . $this->name . '/readconfig.inc.php');
    }

    function readValues($id, $all = '', $quick = 0)
    {
        require(DIR_MODULES . $this->name . '/readvalues.inc.php');
    }

    function gethttpmessage($ip, $cmd)
    {

        $device = SQLSelectOne("SELECT * FROM megaddevices WHERE IP='".$ip."'");

        $config = getURL($ip . $cmd, 0);
        $new = '';
        //$new = 'URL: <b>'.$ip. $cmd.'</b><br><br/>';
        $new.= $config;

        if (preg_match_all('/<a href=(.+?)>/i',$new,$m)) {
            $total = count($m[0]);
            for($i=0;$i<$total;$i++) {
                $new=str_replace($m[0][$i],'<a href="?data_source=&view_mode=edit_megaddevices&id=' . $this->id . '&tab=config2&address=' . $ip . '&par='.urlencode($m[1][$i]).'">',$new);
            }
        }

        if ($device['ID'] && preg_match_all('/\WP(\d+).*? - (.+?)<\/a>/',$new,$m)) {
            //dprint($m);
            $total = count($m[0]);
            for($i=0;$i<$total;$i++) {
                $line = $m[0][$i];
                $num = $m[1][$i];
                $pin = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID=".$device['ID']." AND NUM=".(int)$num. " AND LINKED_OBJECT!='' ORDER BY COMMAND_INDEX");
                if ($pin['ID']) {
                    //$line.=' '.$pin['ID'];
                    if ($pin['LINKED_OBJECT']) {
                        $object_rec=SQLSelectOne("SELECT TITLE,DESCRIPTION FROM objects WHERE TITLE='".DBSafe($pin['LINKED_OBJECT'])."'");
                        $title = $object_rec['TITLE'];
                        if ($object_rec['DESCRIPTION']) {
                            $title.=' - '.$object_rec['DESCRIPTION'];
                        }
                        $line.=' [<a href="?view_mode=edit_megaddevices&tab=data&id='.$device['ID'].'&property_id='.$pin['ID'].'">'.$title.'</a>]';
                    }
                    $new = str_replace($m[0][$i],$line,$new);
                }
            }
        }

        //$new = preg_replace('/<a href=(.+?)>/i', '<a href="?data_source=&view_mode=edit_megaddevices&id=' . $this->id . '&tab=config2&address=' . $ip . '&par=$1">', $new);

        if (preg_match_all('/<form([^<>]+)action=(.+?)>/is',$new,$m)) {
            $total = count($m[0]);
            for($i=0;$i<$total;$i++) {
                $src = $m[0][$i];
                $cmd = $m[2][$i];
                $new = str_replace($src,'<form '.$m[1][$i].' action="?" method="get" class="form" enctype="multipart/form-data" name="frmEdit"><input type="hidden" name="par" value="' . $cmd . '"><input type="hidden" name="address" value="' . $ip . '"><input type="hidden" name="view_mode" value="'.$this->view_mode.'"><input type="hidden" name="tab" value="'.$this->tab.'"><input type="hidden" name="id" value="'.$device['ID'].'">',$new);
            }
            //$cmd = $m[1];
            //$new = preg_replace('/<form action=(.+?)>/i', '<form action="?" method="get" class="form" enctype="multipart/form-data" name="frmEdit">', $new);
            //$new = str_replace('<input type=submit value=Save>', '<input type=submit name="submit" class="btn btn-default btn-primary" value=Save><input type="hidden" name="par" value="' . $cmd . '"><input type="hidden" name="address" value="' . $ip . '"><input type="hidden" name="view_mode" value="'.$this->view_mode.'"><input type="hidden" name="tab" value="'.$this->tab.'"><input type="hidden" name="id" value="'.$device['ID'].'">', $new);
        }

        $new = preg_replace('/type=submit/is', 'type=submit name=submit class="btn btn-default"', $new);

        $new = preg_replace('/<input name=/is', '<input class="form-control" name=', $new);
        $new = preg_replace('/<input size=/is', '<input class="form-control" size=', $new);
        $new = preg_replace('/<select name=/is', '<select class="form-control" name=', $new);
        $new = preg_replace('/>ON</is', ' class="btn btn-default">ON<', $new);
        $new = preg_replace('/>OFF</is', ' class="btn btn-default">OFF<', $new);

        $new = str_replace('Act <input', 'Act <a href="#" onclick="return showMegaDHelp(\'act\');"><i class="glyphicon glyphicon-info-sign"></i></a> <input', $new);
        $new = str_replace('Net <input', 'Net <a href="#" onclick="return showMegaDHelp(\'net\');"><i class="glyphicon glyphicon-info-sign"></i></a> <input', $new);
        $new = str_replace('Raw <input', 'Raw <a href="#" onclick="return showMegaDHelp(\'raw\');"><i class="glyphicon glyphicon-info-sign"></i></a> <input', $new);
        $new = str_replace('Mode <select', 'Mode <a href="#" onclick="return showMegaDHelp(\'mode\');"><i class="glyphicon glyphicon-info-sign"></i></a> <select', $new);
        $new = str_replace('Type <select', '<hr/>Type <a href="#" onclick="return showMegaDHelp(\'type\');"><i class="glyphicon glyphicon-info-sign"></i></a> <select', $new);
        $new = str_replace('Def <select', 'Def <a href="#" onclick="return showMegaDHelp(\'def\');"><i class="glyphicon glyphicon-info-sign"></i></a> <select', $new);

        $new = preg_replace('/checkbox name=af.+?>/is','\0 Af <a href="#" onclick="return showMegaDHelp(\'af\');"><i class="glyphicon glyphicon-info-sign"></i></a><br/>',$new);
        $new = preg_replace('/checkbox name=naf.+?>/is','\0 Naf <a href="#" onclick="return showMegaDHelp(\'naf\');"><i class="glyphicon glyphicon-info-sign"></i></a><br/>',$new);
        $new = preg_replace('/checkbox name=misc.+?>/is','\0 Misc <a href="#" onclick="return showMegaDHelp(\'misc\');"><i class="glyphicon glyphicon-info-sign"></i></a><br/>',$new);


        return $new;


    }


    function scan()
    {
        require(DIR_MODULES . $this->name . '/scan.inc.php');
    }


    /**
     * megaddevices edit/add
     *
     * @access public
     */
    function edit_megaddevices(&$out, $id)
    {
        require(DIR_MODULES . $this->name . '/megaddevices_edit.inc.php');
    }

    /**
     * Title
     *
     * Description
     *
     * @access public
     */
    function refreshDevice($id)
    {
        $rec = SQLSelectOne("SELECT * FROM megaddevices WHERE ID='" . $id . "'");
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
    function delete_megaddevices($id)
    {
        $rec = SQLSelectOne("SELECT * FROM megaddevices WHERE ID='$id'");
        // some action for related tables
        SQLExec("DELETE FROM megadproperties WHERE DEVICE_ID='" . $rec['ID'] . "'");
        SQLExec("DELETE FROM megaddevices WHERE ID='" . $rec['ID'] . "'");

    }

    function propertySetHandle($object, $property, $value)
    {
        $properties = SQLSelect("SELECT ID FROM megadproperties WHERE LINKED_OBJECT LIKE '" . DBSafe($object) . "' AND LINKED_PROPERTY LIKE '" . DBSafe($property) . "'");
        $total = count($properties);
        if ($total) {
            $this->getConfig();
            for ($i = 0; $i < $total; $i++) {
                $this->setProperty($properties[$i]['ID'], $value);
            }
        }
    }


    function processCycle()
    {
        $this->getConfig();
        $this->updateDevices();
    }

    /**
     * Title
     *
     * Description
     *
     * @access public
     */
    function processRequest()
    {


        $mdid = gr('mdid');
        $st = gr('st');
        $ip = $_SERVER['REMOTE_ADDR'];

        $ecmd = '';

        if ($mdid) {
            $rec = SQLSelectOne("SELECT * FROM megaddevices WHERE MDID='" . trim($mdid) . "'");
        }

        if ($st == '1' && $rec['ID'] && $rec['RESTORE_ON_REBOOT']) {
            //restore on start
            $this->restoreDeviceStatus($rec['ID']);
            return;
        }

        if (!$rec['ID']) {
            $rec = SQLSelectOne("SELECT * FROM megaddevices WHERE IP='" . $ip . "'");
            if ($this->config['API_DEBUG']) {
                debmes('found by ip '.$rec['ID'],'megad');
            }
        }


        if (!$rec['ID']) {
            $rec = array();
            $rec['IP'] = $ip;
            $rec['TITLE'] = 'MegaD ' . $rec['IP'];
            $rec['MDID'] = trim($mdid);
            $rec['PASSWORD'] = 'sec';
            $rec['ID'] = SQLInsert('megaddevices', $rec);
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
                $v = $_GET['v'];
            }

            $m = $_GET['m'];
            $pt = $_GET['pt'];
            $at = $_GET['at'];
            $dir = $_GET['dir'];
            $cnt = $_GET['cnt'];



            if (isset($_GET['sms'])==1) {
/*                debmes('sms:'.$_GET['sms'],'megad');
                $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID=" . $rec['ID'] . " AND NUM=100 AND COMMAND='alarmwrn'");
                $prop['COMMAND'] = 'alarmwrn';
                $prop['NUM'] = '100';
                $prop['COMMENT'] = 'GSM WARNING ALARM STATUS';
                $prop['DEVICE_ID'] = $rec['ID'];

                $prop['CURRENT_VALUE_STRING'] ='1';
*/
                   $prop['CURRENT_VALUE'] = '1';
                    $cmd = array('NUM' => 100, 'VALUE' => 1, 'COMMAND' => 'alarmwrn');
                    $commands[] = $cmd;
 
                    foreach ($commands as $command) {
                    $this->processCommand($rec['ID'], $command, 1); }


/*                if ($prop['ID']) {
		SQLUpdate('megadproperties',$prop );
		debmes('sqlupdate', 'megad');
		debmes($prop, 'megad');
		} else 
		{
		debmes('sqlinsert', 'megad');
		debmes($prop, 'megad');
		SQLInsert('megadproperties',$prop );
		}
*/




}


            if (isset($_GET['all'])) {
                $this->readValues($rec['ID'], $_GET['all']);
                return;
            }


            $commands = array();
            //input data changed
            if (isset($pt) && preg_match('/ext(\d+)=(\d+)/',$_SERVER['REQUEST_URI'],$matches)) {
                // extender port input
                $ext_prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID=" . $rec['ID'] . " AND ADD_INT='" . (int)$pt . "'");
                if ($ext_prop['ID']) {
                    $idx = (int)$matches[1];
                    $value = $matches[2];
                    $cmd = array('NUM' => $ext_prop['NUM'], 'INDEX' => $idx + 1, 'VALUE' => $value, 'COMMAND' => 'input');
                    $commands[] = $cmd;
                }
                $ecmd = 'd';
            } elseif (isset($pt)) {
                $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID=" . $rec['ID'] . " AND NUM='" . DBSafe($pt) . "' AND COMMAND='input'");
                if (!$prop['ID']) {
                    $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID=" . $rec['ID'] . " AND NUM='" . DBSafe($pt) . "' AND COMMAND!='counter'");
                }
                if ($prop['ID']) {
                    if ($prop['ECMD'] && !($prop['SKIP_DEFAULT'])) {
                        if ($rec['DEFAULT_BEHAVIOR']==0) {
                            $ecmd = $prop['ECMD'];
                        }
                        if ($rec['DEFAULT_BEHAVIOR']==1) {
                            $ecmd = 'd';
                        }
                    }
                    if (isset($v)) {
                        $cmd = array('NUM' => $pt, 'VALUE' => $v, 'COMMAND' => $prop['COMMAND']);
                        $commands[] = $cmd;
                        $prop['CURRENT_VALUE_STRING'] = $v;
                    } else {
                        if ($m == 2) {
                            $value = 1;
                            $cmd = array('NUM' => $pt, 'VALUE' => $value, 'COMMAND' => 'long_press');
                            $commands[] = $cmd;
                        } elseif ($m == 1) {
                            $value = 1;
                            $cmd = array('NUM' => $pt, 'VALUE' => $value, 'COMMAND' => 'release');
                            $commands[] = $cmd;
                            $value = 0;
                            $cmd = array('NUM' => $pt, 'VALUE' => $value, 'COMMAND' => $prop['COMMAND']);
                            $prop['CURRENT_VALUE_STRING'] = $value;
                            $commands[] = $cmd;
                        } else {
                            $value = 1;
                            $cmd = array('NUM' => $pt, 'VALUE' => $value, 'COMMAND' => $prop['COMMAND']);
                            $prop['CURRENT_VALUE_STRING'] = $value;
                            $commands[] = $cmd;
                        }
                    }
                }

                if ($_GET['wg']) {
                    $value = $_GET['wg'];
                    $cmd = array('NUM' => $pt, 'VALUE' => $value, 'COMMAND' => 'wiegand');
                    $commands[] = $cmd;
                } elseif ($_GET['ib']) {
                    $value = $_GET['ib'];
                    $cmd = array('NUM' => $pt, 'VALUE' => $value, 'COMMAND' => 'ibutton');
                    $commands[] = $cmd;
                } elseif ($_GET['click']) {
                    $value = $_GET['click'];
                    $command = 'click';
                    if ($value == 2) {
                        $command = 'double_click';
                        $value = 1;
                    }
                    $cmd = array('NUM' => $pt, 'VALUE' => $value, 'COMMAND' => $command);

                    $commands[] = $cmd;
                }
                if (isset($cnt)) {
                    $cmd = array('NUM' => $pt, 'VALUE' => $cnt, 'COMMAND' => 'counter');
                    $commands[] = $cmd;
                }
            }

            // internal temp sensor data
            if (isset($at)) {
                $commands[] = array('NUM' => 0, 'COMMAND' => 'inttemp', 'VALUE' => $at);
            }
            foreach ($commands as $command) {
                $this->processCommand($rec['ID'], $command, 1);
            }
        }

        if ($ecmd) {
            header_remove();
            header('Content-Type:text/html;charset=windows-1251');
            if (preg_match('/(\d+):3/is', $ecmd, $m)) {
                $ecmd = $m[1] . ':' . (int)$prop['CURRENT_VALUE_STRING'];
            }
            if (preg_match('/(\d+):4/is', $ecmd, $m)) {
                if ((int)$prop['CURRENT_VALUE_STRING']) {
                    $ecmd = $m[1] . ':0';
                } else {
                    $ecmd = $m[1] . ':1';
                }
            }
            $mega_id = $rec['ID'];
            $url = BASE_URL . '/ajax/megad.html?op=readvalues&device=' . $mega_id;
            $code = 'getURL("' . $url . '",0);';
            setTimeOut('mega_refresh_' . $mega_id, $code, 10);
        }
        return $ecmd;
    }

    /**
     * Title
     *
     * Description
     *
     * @access public
     */
    function updateDevices()
    {
        $devices = SQLSelect("SELECT * FROM megaddevices WHERE UPDATE_PERIOD>0 AND NEXT_UPDATE<=NOW()");
        $total = count($devices);
        for ($i = 0; $i < $total; $i++) {
            $devices[$i]['NEXT_UPDATE'] = date('Y-m-d H:i:s', time() + $devices[$i]['UPDATE_PERIOD']);
            SQLUpdate('megaddevices', $devices[$i]);
            $this->refreshDevice($devices[$i]['ID']);
        }
    }


    function processCommand($device_id, $command, $force = 0)
    {
        if (!isset($command['INDEX'])) {
            $command['INDEX'] = 0;
        }
        if ($command['COMMAND'] != 'inttemp') {
            $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $device_id . "' AND NUM='" . $command['NUM'] . "' AND COMMAND='" . $command['COMMAND'] . "' AND COMMAND_INDEX=" . (int)$command['INDEX']);
        } else {
            $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $device_id . "' AND COMMAND='" . $command['COMMAND'] . "' AND COMMAND_INDEX=" . (int)$command['INDEX']);
        }

        /*
        if ($this->config['API_DEBUG']) {
            debmes('Processing command: '.json_encode($command),'megad');
            if ($prop['ID']) {
                debmes('Prop ID: '.($prop['ID']),'megad');
            } else {
                debmes('New property','megad');
            }
        }
        */

        if ($prop['REVERSE']) {
            if ($command['VALUE']) {
                $command['VALUE']=0;
            } else {
                $command['VALUE']=1;
            }
        }

        $old_value = $prop['CURRENT_VALUE_STRING'];
        if (!$prop['ID']) {
            $prop = array();
            $prop['DEVICE_ID'] = $device_id;
            $prop['NUM'] = $command['NUM'];
            $prop['COMMAND'] = $command['COMMAND'];
            $prop['COMMAND_INDEX'] = $command['INDEX'];
            $prop['CURRENT_VALUE_STRING'] = $command['VALUE'];
            $old_value = $prop['CURRENT_VALUE_STRING'];
            $prop['ID'] = SQLInsert('megadproperties', $prop);

            /*
            $other_prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $device_id . "' AND NUM='" . $command['NUM'] . "' AND COMMAND_INDEX=" . (int)$command['INDEX']." AND ID!=".$prop['ID']);
            if ($other_prop['ID']) {
                SQLExec("DELETE FROM megadproperties WHERE ID=".$other_prop['ID']);
            }
            */

        }
        $prop['CURRENT_VALUE_STRING'] = $command['VALUE'];
        if ($old_value != $prop['CURRENT_VALUE_STRING']) {
            $prop['UPDATED'] = date('Y-m-d H:i:s');
            SQLUpdate('megadproperties', $prop);
        }
        if ($prop['LINKED_OBJECT'] && $prop['LINKED_PROPERTY']) {
            if ($force || $old_value != $prop['CURRENT_VALUE_STRING'] || $prop['CURRENT_VALUE_STRING'] != gg($prop['LINKED_OBJECT'] . '.' . $prop['LINKED_PROPERTY'])) {
                setGlobal($prop['LINKED_OBJECT'] . '.' . $prop['LINKED_PROPERTY'], $prop['CURRENT_VALUE_STRING'], array($this->name => '0'));
            }
        }
        if ($force || $prop['LINKED_OBJECT'] && $prop['LINKED_METHOD'] && ($old_value != $prop['CURRENT_VALUE_STRING'])) {
            $params = array();
            $params['TITLE'] = $record['TITLE'];
            $params['VALUE'] = $prop['CURRENT_VALUE_STRING'];
            $params['value'] = $prop['CURRENT_VALUE_STRING'];
            $params['port'] = $prop['NUM'];
            $params['m'] = $m;
            callMethod($prop['LINKED_OBJECT'] . '.' . $prop['LINKED_METHOD'], $params);
        }
    }

    /**
     * Title
     *
     * Description
     *
     * @access public
     */
    function sendCommand($id, $command, $custom = false)
    {
        $device = SQLSelectOne("SELECT * FROM megaddevices WHERE ID='" . $id . "'");
        if (!$device['ID']) {
            $device = SQLSelectOne("SELECT * FROM megaddevices WHERE TITLE LIKE '" . DBSafe($id) . "'");
        }
        if (!$device['ID']) {
            $device = SQLSelectOne("SELECT * FROM megaddevices WHERE IP='" . DBSafe($id) . "'");
        }
        if ($device['ID']) {
            $url = 'http://' . $device['IP'] . '/' . $device['PASSWORD'] . '/?' . ($custom ? '' : 'cmd=') . $command;
            if ($this->config['API_DEBUG']) {
                DebMes("Sending command: $url", 'megad');
            }
            $response = getURL($url, 0);
            if ($this->config['API_DEBUG']) {
                DebMes("Command response: $response", 'megad');
            }
            return $response;
        } else {
            return 0;
        }
    }



    function clearalarmwrn($id)
    {

            $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $id . "' AND COMMAND='alarmwrn' and NUM=100");

//                   $prop['CURRENT_VALUE'] = '1';
//                    $cmd = array('NUM' => 100, 'VALUE' => 0, 'COMMAND' => 'alarmwrn');
//                    $commands[] = $cmd;
 
//                    foreach ($commands as $command) {
//                    $this->processCommand($id, $command, 0); }
//                       return 'Cleared';


        $old_value = $prop['CURRENT_VALUE_STRING'];
        if (!$prop['ID']) {
            $prop = array();
            $prop['DEVICE_ID'] = $id;
            $prop['NUM'] = 100;
            $prop['COMMAND'] = 'alarmwrn';
            $prop['COMMAND_INDEX'] = '';
            $prop['CURRENT_VALUE'] = 0;
            $prop['CURRENT_VALUE_STRING'] = 0;
            $prop['COMMENT'] = 'GSM WARNING ALARM STATUS';
            $old_value = $prop['CURRENT_VALUE_STRING'];
            $prop['UPDATED'] = date('Y-m-d H:i:s');
            $prop['ID'] = SQLInsert('megadproperties', $prop);
        }

        $prop['CURRENT_VALUE_STRING'] = $command['VALUE'];
        if ($old_value != $prop['CURRENT_VALUE_STRING']) {
            $prop['UPDATED'] = date('Y-m-d H:i:s');
            $prop['CURRENT_VALUE'] = 0;
            $prop['CURRENT_VALUE_STRING'] = 0;

            SQLUpdate('megadproperties', $prop);
        }
        if ($prop['LINKED_OBJECT'] && $prop['LINKED_PROPERTY']) {
            if ($old_value != $prop['CURRENT_VALUE_STRING'] || $prop['CURRENT_VALUE_STRING'] != gg($prop['LINKED_OBJECT'] . '.' . $prop['LINKED_PROPERTY'])) {
                setGlobal($prop['LINKED_OBJECT'] . '.' . $prop['LINKED_PROPERTY'], $prop['CURRENT_VALUE_STRING'], array($this->name => '0'));
            }
        }


    }

    function sendAlarm($id, $command, $custom = false)
    {

        $device = SQLSelectOne("SELECT * FROM megaddevices WHERE ID='" . $id . "'");
        if (!$device['ID']) {
            $device = SQLSelectOne("SELECT * FROM megaddevices WHERE TITLE LIKE '" . DBSafe($id) . "'");
        }
        if (!$device['ID']) {
            $device = SQLSelectOne("SELECT * FROM megaddevices WHERE IP='" . DBSafe($id) . "'");
        }
        if ($device['ID']) {
            $url = 'http://' . $device['IP'] . '/' . $device['PASSWORD'] . '/?cmd=S:' . $command;
            if ($this->config['API_DEBUG']) {
                DebMes("Sending alarm command: $url", 'megad');
            }
            $response = getURL($url, 0);
            if ($this->config['API_DEBUG']) {
                DebMes("Alarm command response: $response", 'megad');
            }
            return $response;
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
    function setProperty($property_id, $value)
    {
        $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE ID='" . $property_id . "'");
        $prop['CURRENT_VALUE_STRING'] = $value;
        SQLUpdate('megadproperties', $prop);

        if ($prop['REVERSE']) {
            if ($value) {
                $value=0;
            } else {
                $value=1;
            }
        }

        if ($prop['COMMAND_INDEX']>0) {
            $channel=$prop['NUM'].'e'.((int)$prop['COMMAND_INDEX']-1);
        } else {
            $channel=$prop['NUM'];
        }

        if ($prop['COMMAND'] == 'output') { // output
            $this->sendCommand($prop['DEVICE_ID'], $channel . ':' . $value);
        } elseif ($prop['COMMAND'] == 'raw') { // raw command
            $this->sendCommand($prop['DEVICE_ID'], $value);
        } elseif ($prop['COMMAND'] == 'alarm') { // raw command
            $this->sendAlarm($prop['DEVICE_ID'], $value);
        }
        //$this->readValues($prop['DEVICE_ID'],'',1);
    }

    function restoreDeviceStatus($device_id)
    {
        $properties = SQLSelect("SELECT * FROM megadproperties WHERE DEVICE_ID=" . $device_id);
        $total = count($properties);
        for ($i = 0; $i < $total; $i++) {
            if ($properties[$i]['COMMAND'] == 'output' && $properties[$i]['CURRENT_VALUE_STRING']) {
                $this->sendCommand($properties[$i]['DEVICE_ID'], $properties[$i]['NUM'] . ':' . $properties[$i]['CURRENT_VALUE_STRING']);
                if ($i < ($total - 1)) {
                    usleep(5000);
                }
            }
        }
    }


    /**
     * megadproperties search
     *
     * @access public
     */
    function search_megadproperties(&$out)
    {
        require(DIR_MODULES . $this->name . '/megadproperties_search.inc.php');
    }

    /**
     * Title
     *
     * Description
     *
     * @access public
     */
    function get_local_ip()
    {

        if (preg_match("/^WIN/", PHP_OS))
            $find_ip = $this->get_local_ip_win();
        else {
            $find_ip = $this->get_local_ip_linux();
        }

        $local_ip = '';
        foreach ($find_ip as $iface => $iface_ip) {
            if ((preg_match("/^192\.168/", $find_ip[$iface]) || preg_match("/^10\./", $find_ip[$iface]))) {
                $local_ip = $find_ip[$iface];
                break;
            }
        }

        return $local_ip;

    }

    function get_local_ip_linux()
    {
        $out = explode(PHP_EOL, shell_exec("/sbin/ifconfig"));
        $local_addrs = array();
        $ifname = 'unknown';
        foreach ($out as $str) {
            $matches = array();
            if (preg_match('/^([a-z0-9]+)(:\d{1,2})?(\s)+Link/', $str, $matches)) {
                $ifname = $matches[1];
                if (strlen($matches[2]) > 0)
                    $ifname .= $matches[2];
            } elseif (preg_match('/inet addr:((?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3})\s/', $str, $matches))
                $local_addrs[$ifname] = $matches[1];
        }
        return $local_addrs;
    }

    function get_local_ip_win()
    {
        $out = explode("\n", shell_exec("ipconfig"));

        $local_addrs = array();
        foreach ($out as $str) {
            if (preg_match('/IPv4/', $str))
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
    function install($data = '')
    {
        parent::install();

        SQLExec("UPDATE megadproperties SET COMMAND='input' WHERE COMMAND='' AND TYPE=0");
        SQLExec("UPDATE megadproperties SET COMMAND='output' WHERE COMMAND='' AND TYPE=1");
        SQLExec("UPDATE megadproperties SET COMMAND='adc' WHERE COMMAND='' AND TYPE=2");
        SQLExec("UPDATE megadproperties SET COMMAND='dsen' WHERE COMMAND='' AND TYPE=3");
        SQLExec("UPDATE megadproperties SET COMMAND='inttemp' WHERE COMMAND='' AND TYPE=100");


        $devices = SQLSelect("SELECT ID FROM megaddevices");
        if ($devices[0]['ID']) {
            foreach ($devices as $device) {
                $this->readValues($device['ID']);
            }
        }

        setGlobal('cycle_megadControl', 'restart');

    }

    /**
     * Uninstall
     *
     * Module uninstall routine
     *
     * @access public
     */
    function uninstall()
    {
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
    function dbInstall($data)
    {
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
 megaddevices: I2C_VERSION int(1) NOT NULL DEFAULT '0' 
 megaddevices: DEFAULT_BEHAVIOR int(1) NOT NULL DEFAULT '0' 
 megaddevices: RESTORE_ON_REBOOT int(1) NOT NULL DEFAULT '0'
 megaddevices: UPDATE_PERIOD int(10) NOT NULL DEFAULT '0'
 megaddevices: NEXT_UPDATE datetime
 megaddevices: CONFIG text
 megaddevices: COMMENT varchar(255) NOT NULL DEFAULT ''
 
 megadproperties: ID int(10) unsigned NOT NULL auto_increment
 megadproperties: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 megadproperties: TYPE int(3) NOT NULL DEFAULT '0'
 megadproperties: COMMAND varchar(20) NOT NULL DEFAULT ''
 megadproperties: COMMAND_INDEX int(3) NOT NULL DEFAULT '0'
 megadproperties: NUM int(3) NOT NULL DEFAULT '0'
 megadproperties: ADD_NUM int(3) NOT NULL DEFAULT '0' 
 megadproperties: ADD_INT int(3) NOT NULL DEFAULT '0'
 megadproperties: CURRENT_VALUE int(10) NOT NULL DEFAULT '0'
 megadproperties: CURRENT_VALUE_STRING varchar(255) NOT NULL DEFAULT ''
 megadproperties: CURRENT_VALUE_STRING2 varchar(255) NOT NULL DEFAULT ''
 megadproperties: COUNTER int(10) NOT NULL DEFAULT '0'
 megadproperties: REVERSE int(10) NOT NULL DEFAULT '0' 
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
 megadproperties: COMMENT varchar(255) NOT NULL DEFAULT ''
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
