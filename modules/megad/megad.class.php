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
        $device = $_GET['device'];
        $command = $_GET['command'];

        if ($this->ajax) {
            if ($_GET['op'] == 'processCycle') {
                $this->processCycle();
            }
            if ($_GET['op'] == 'readvalues') {
                $this->readValues($device);
                echo "OK";
                exit;
            }

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

        $config = getURL($ip . $cmd, 0);
        $new = $config;

        $new = preg_replace('/<a href=(.+?)>/i', '<a href="?data_source=&view_mode=edit_megaddevices&id=' . $this->id . '&tab=config2&address=' . $ip . '&par=$1">', $new);

        $new = preg_replace('/<form action=(.+?)>/i', '<form action="?" method="post" class="form" enctype="multipart/form-data" name="frmEdit">', $new);

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

        $new = str_replace('<input type=submit value=Save>', '<input type=submit class="btn btn-default btn-primary" value=Save><input type="hidden" name="sourceurl" value="' . $cmd . '"><input type="hidden" name="sourceip" value="' . $ip . '">', $new);

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

        global $mdid;
        global $st;

        $ip = $_SERVER['REMOTE_ADDR'];

        $ecmd = '';

        if ($mdid) {
            $rec = SQLSelectOne("SELECT * FROM megaddevices WHERE MDID LIKE '" . trim($mdid) . "%'");
        }

        if ($st == '1' && $rec['ID']) {
            //restore on start
            $this->restoreDeviceStatus($rec['ID']);
            return;
        }

        if (!$rec['ID']) {
            $rec = SQLSelectOne("SELECT * FROM megaddevices WHERE IP='" . $ip . "'");
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

            if (isset($_GET['all'])) {
                $this->readValues($rec['ID'], $_GET['all']);
                return;
            }

            $commands = array();
            //input data changed
            if (isset($pt)) {
                $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID=" . $rec['ID'] . " AND NUM='" . DBSafe($pt) . "' AND COMMAND='input'");
                if (!$prop['ID']) {
                    $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID=" . $rec['ID'] . " AND NUM='" . DBSafe($pt) . "' AND COMMAND!='counter'");
                }
                if ($prop['ID']) {
                    if ($prop['ECMD'] && !($prop['SKIP_DEFAULT'])) {
                        $ecmd = $prop['ECMD'];
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
            setTimeOut('mega_refresh_' . $mega_id, $code, 1);
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
//debmes($prop, 'megad');

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

        $channel = $prop['NUM'];
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
