<?php
/*
* @version 0.1 (wizard)
*/
if ($this->owner->name == 'panel') {
    $out['CONTROLPANEL'] = 1;
}

$all_devices = SQLSelect("SELECT ID,TITLE FROM megaddevices WHERE 1 ORDER BY TITLE");
$out['ALL_DEVICES'] = $all_devices;

$table_name = 'megaddevices';
$rec = SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
if ($this->mode == 'update') {
    $ok = 1;
    // step: default
    if ($this->tab == '') {
        //updating 'TITLE' (varchar, required)
        global $title;
        $rec['TITLE'] = $title;
        if ($rec['TITLE'] == '') {
            $out['ERR_TITLE'] = 1;
            $ok = 0;
        }

        $rec['COMMENT'] = gr('comment');

        global $mdid;
        $rec['MDID'] = $mdid;

        $rec['RESTORE_ON_REBOOT'] = gr('restore_on_reboot', 'int');

        //updating 'IP' (varchar)
        global $ip;
        $rec['IP'] = $ip;

        global $type_main;
        $rec['TYPE_MAIN'] = $type_main;

        global $type;
        $rec['TYPE'] = $type;

        global $password;
        if (!$password) {
            $password = 'sec';
        }
        $rec['PASSWORD'] = $password;

        global $update_period;
        $rec['UPDATE_PERIOD'] = (int)$update_period;

        $rec['I2C_VERSION'] = gr('i2c_version', 'int');
        $rec['DEFAULT_BEHAVIOR'] = gr('default_behavior', 'int');

        $rec['NEXT_UPDATE'] = date('Y-m-d H:i:s', time() + $rec['UPDATE_PERIOD']);

    }
    // step: config
    if ($this->tab == 'config') {
        //updating 'CONFIG' (text)
        //global $config;
        $config = array();
        $rec['CONFIG'] = serialize($config);
    }
    //UPDATING RECORD
    if ($ok) {
        if ($rec['ID']) {
            SQLUpdate($table_name, $rec); // update

        } else {
            $new_rec = 1;
            $rec['ID'] = SQLInsert($table_name, $rec); // adding new record

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
        $out['OK'] = 1;
    } else {
        $out['ERR'] = 1;
    }
}
// step: config

if ($this->tab == 'config2') {

    $address = $_GET['address'];
    $par = $_GET['par'];

    if (preg_match('/^http/', $par)) {
        header("Location:" . $par);
        exit;
    }

    if ($_GET['submit']) {
        $new_url = '';
        foreach ($_GET as $k => $v) {
            if (!in_array($k, array('submit', 'par', 'address', 'view_mode', 'tab', 'id', 'pd', 'md', 'inst'))) {
                $new_url .= '&' . $k . '=' . urlencode($v);
            }
        }
        if ($new_url != '') {
            $par .= '?' . $new_url;
        }
    }

    if ($address == '') $address = $rec['IP'];
    if ($par == '') $par = '/' . $rec['PASSWORD'];

    $data = $this->gethttpmessage($address, $par);


//$data=$this->gethttpmessage($rec['IP'], '/'. $rec['PASSWORD']);
    $out['TEST'] = $data;
}

if ($this->tab == 'config') {

    if ($this->mode == 'upgrade_firmware') {
        $url = BASE_URL . '/modules/megad/megad-cfg.php';
        $url .= '?ip=' . urlencode($rec['IP']) . '&p=' . urlencode($rec['PASSWORD']) . '&w=1';
        if ($this->config['API_IP']) {
            $url .= '&local-ip=' . $this->config['API_IP'];
        }
        global $beta;
        if ($beta) {
            $url .= "&b=1";
        }

        global $clear;
        if ($clear) {
            $url .= "&ee=1";
        }

        //echo $url;exit;

        $data = getURL($url, 0);
        if (!$data) {
            $data = 'OK';
        }
        $this->redirect("?view_mode=" . $this->view_mode . "&tab=config" . "&id=" . $rec['ID'] . "&result=" . urlencode($data));
    }

    if ($this->mode == 'set_server') {
        global $server_ip;
        global $server_script;
        $url = 'http://' . $rec['IP'] . '/' . $rec['PASSWORD'] . '/?cf=1&sip=' . $server_ip . "&sct=" . urlencode($server_script);
        if ($this->config['API_IP']) {
            $url .= '&local-ip=' . $this->config['API_IP'];
        }
        $data = getURL($url, 0);
        $data = 'OK';
        $this->redirect("?view_mode=" . $this->view_mode . "&tab=config" . "&id=" . $rec['ID'] . "&result=" . urlencode($data));
    }

    if ($this->mode == 'write_config') {
        global $config;

        SaveFile(ROOT . 'cached/megad.cfg', $config);
        $url = BASE_URL . '/modules/megad/megad-cfg.php';
        $url .= '?ip=' . urlencode($rec['IP']) . '&write-conf=' . urlencode(ROOT . 'cached/megad.cfg') . '&p=' . urlencode($rec['PASSWORD']);
        if ($this->config['API_IP']) {
            $url .= '&local-ip=' . $this->config['API_IP'];
        }
        $data = getURL($url, 0);

        $this->readConfig($rec['ID']);
        $data = 'OK';
        $this->redirect("?view_mode=" . $this->view_mode . "&tab=config" . "&id=" . $rec['ID'] . "&result=" . urlencode($data));
    }

    if ($this->mode == 'read_config') {
        $this->readConfig($rec['ID']);
        $data = 'OK';
        $this->redirect("?view_mode=" . $this->view_mode . "&tab=config" . "&id=" . $rec['ID'] . "&result=" . urlencode($data));
    }

    if ($this->mode == 'set_address') {
        global $ip;
        if ($ip != $rec['IP']) {
            $url = 'http://' . $rec['IP'] . '/' . $rec['PASSWORD'] . '/?cf=1&eip=' . $ip;
            if ($this->config['API_IP']) {
                $url .= '&local-ip=' . $this->config['API_IP'];
            }
            $data = getURL($url, 0);
            if (preg_match('/Back/is', $data)) {
                $rec['IP'] = $ip;
                SQLUpdate('megaddevices', $rec);
                $data = 'OK';
            }
            $this->redirect("?view_mode=" . $this->view_mode . "&tab=config" . "&id=" . $rec['ID'] . "&result=" . urlencode($data));
        }
    }

    $out['SERVER_IP'] = $this->get_local_ip();

}
if (is_array($rec)) {
    foreach ($rec as $k => $v) {
        if (!is_array($v)) {
            $rec[$k] = htmlspecialchars($v);
        }
    }
}
outHash($rec, $out);

if ($rec['ID'] && $this->tab == 'data') {
    $property_id = gr('property_id');
    if ($property_id) {
        $property = SQLSelectOne("SELECT * FROM megadproperties WHERE ID=" . (int)$property_id);
        if ($this->mode == 'delete') {
            if ($property['INDEX'] == 0) {
                SQLExec("DELETE FROM megadproperties WHERE NUM=" . $property['NUM'] . " AND DEVICE_ID=" . (int)$property['DEVICE_ID']);
            }
            SQLExec("DELETE FROM megadproperties WHERE ID=" . $property['ID']);
            $this->redirect("?view_mode=" . $this->view_mode . "&tab=" . $this->tab . "&id=" . $rec['ID']);
        }
        if ($this->mode == 'update') {
            if (preg_match('/,/', gr('num'))) {
                $tmp = explode(',', gr('num'));
                $num1 = (int)trim($tmp[0]);
                $num2 = (int)trim($tmp[1]);
                $property['NUM'] = $num1;
                $property['ADD_NUM'] = $num2;
            } else {
                $property['NUM'] = gr('num', 'int');
                $property['ADD_NUM'] = gr('add_num', 'int');
            }
            $property['ADD_INT'] = gr('add_int', 'int');
            $property['REVERSE'] = gr('reverse', 'int');
            $property['SKIP_DEFAULT'] = gr('skip_default', 'int');
            $property['COMMENT'] = gr('comment');
            $property['COMMAND'] = gr('command');
            if (!$property['ID']) {
                $property['DEVICE_ID'] = $rec['ID'];
                $property['ID'] = SQLInsert('megadproperties', $property);
                $property_id = $property['ID'];
            } else {
                $property['LINKED_OBJECT'] = gr('linked_object');
                $property['LINKED_PROPERTY'] = gr('linked_property');
                $property['LINKED_METHOD'] = gr('linked_method');
                SQLUpdate('megadproperties', $property);
            }

            if ($property['ADD_NUM'] != '') {
                SQLExec("UPDATE megadproperties SET COMMAND='i2c' WHERE DEVICE_ID=" . $rec['ID'] . " AND NUM=" . $property['ADD_NUM']);
            }

        }
        if (preg_match('/i2c/', $property['COMMAND'])) {
            $out['NEED_ADD_PORT'] = 1;
            $out['I2C'] = 1;
        }
        if ($property['COMMAND'] == 'i2c_16i_xt' || $property['COMMAND'] == 'i2c_16i_xt_sda') {
            $out['NEED_ADD_INT'] = 1;
        }

        if ($property['COMMAND_INDEX'] > 0) {
            $out['PROPERTY_PORT'] = $property['NUM'] . 'e' . ((int)$property['COMMAND_INDEX'] - 1);
        } else {
            $out['PROPERTY_PORT'] = $property['NUM'];
        }


        //
        if ($property['LINKED_OBJECT']) {
            addLinkedProperty($property['LINKED_OBJECT'], $property['LINKED_PROPERTY'], $this->name);
        }
        if (is_array($property)) {
            foreach ($property as $k => $v) {
                $out['PROPERTY_' . $k] = $v;
            }
        } else {
            $out['NEW_PROPERTY'] = 1;
        }
        $out['PROPERTY_ID'] = $property_id;
        if ($this->mode == 'update' && $out['I2C']) {
            $this->readValues($rec['ID']);
        }
    }
    $properties = SQLSelect("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $rec['ID'] . "' ORDER BY NUM, COMMAND_INDEX, COMMAND");
    $total = count($properties);
    for ($i = 0; $i < $total; $i++) {
        if ($properties[$i]['COMMAND_INDEX'] > 0) {
            $properties[$i]['PROPERTY_PORT'] = $properties[$i]['NUM'] . 'e' . ((int)$properties[$i]['COMMAND_INDEX'] - 1);
        } else {
            $properties[$i]['PROPERTY_PORT'] = $properties[$i]['NUM'];
        }
        if ($properties[$i]['ID'] == $out['PROPERTY_ID']) {
            $properties[$i]['SELECTED'] = 1;
        }
        if ($properties[$i]['LINKED_OBJECT'] != '') {
            $object_rec = SQLSelectOne("SELECT * FROM objects WHERE TITLE='" . $properties[$i]['LINKED_OBJECT'] . "'");
            if ($object_rec['DESCRIPTION'] != '') {
                $properties[$i]['LINKED_OBJECT'] .= ' - ' . $object_rec['DESCRIPTION'];
            }
        }
    }
    $out['PROPERTIES'] = $properties;

}

if ($this->mode == 'clear') {
    SQLExec("DELETE FROM megadproperties WHERE DEVICE_ID=" . $rec['ID']);
    $this->readValues($rec['ID']);
    $this->readConfig($rec['ID']);
    $this->redirect("?view_mode=" . $this->view_mode . "&tab=" . $this->tab . "&id=" . $rec['ID']);
}

if ($this->mode == 'getdata') {
    $this->readValues($rec['ID']);
    $this->readConfig($rec['ID']);
    $this->redirect("?view_mode=" . $this->view_mode . "&tab=" . $this->tab . "&id=" . $rec['ID']);
}

global $result;
$out['RESULT'] = $result;
