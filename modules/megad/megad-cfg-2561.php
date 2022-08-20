<?php
/*
* Copyright (c) 2013-2020, Andrey_B
* https://ab-log.ru
* Подробнее см. LICENSE.txt или http://www.gnu.org/licenses/
*/

error_reporting(E_ALL & ~E_NOTICE);

if( !function_exists('hex2bin') )
{
	function hex2bin($hex)
	{
		return pack('H*', $hex);
	}
}

# Parsing options
$options = getopt("sp:fewb", array("scan", "ip:", "new-ip:", "fw:", "local-ip:", "ee", "read-conf:", "write-conf:"));
#print_r($options);

if ($_SERVER['REQUEST_METHOD']=='GET') {
    foreach($_GET as $k=>$v) {
        if (get_magic_quotes_gpc()) {
            $v=stripslashes($v);
        }
        $options[$k]=$v;
    }
}

# Prepearing sockets
if ( !empty($options['local-ip']) )
$local_ip = $options['local-ip'];
else
{
	$local_ip = gethostbyname(gethostname());
	if ( empty($local_ip) || preg_match("/127\./", $local_ip) )
	{
		if ( preg_match("/^WIN/", PHP_OS) )
		$local_ip = get_local_ip_win();
		else
		{
			$find_ip = get_local_ip();
			foreach ( $find_ip as $iface => $iface_ip)
			{
				if ( preg_match("/^192\.168/", $find_ip[$iface]) || preg_match("/^10\./", $find_ip[$iface]) )
				{
					$local_ip = $find_ip[$iface];
					break;
				}
			}
		}
	}

	if ( !preg_match("/^192\.168\./", $local_ip) && !preg_match("/^10\./", $local_ip) && !preg_match("/^172\.16\./", $local_ip) )
	{
		echo "Unable to detect local network\nPlase, specify local IP-address with --local-ip\n";
		exit;
	}
}

$broadcast_ip = preg_replace("/(\d+)\.(\d+)\.(\d+)\.(\d+)/", "$1.$2.$3.255", $local_ip);

$socket = stream_socket_server("udp://$local_ip:42000", $errno, $errstr, STREAM_SERVER_BIND);
if (!$socket)
die("$errstr ($errno)");
stream_set_timeout($socket, 0, 300000);

$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); 
socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1); 


function get_local_ip()
{
	$out = explode(PHP_EOL,shell_exec("/sbin/ifconfig 2> /dev/null"));
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

	if ( count($local_addrs) == 0 )
	{
		$out = explode(PHP_EOL,shell_exec("/sbin/ip a 2> /dev/null"));
		foreach($out as $str)
		{
			if ( preg_match("/:\s(.+):/", $str, $matches) )
			$ifname = $matches[1];
			else if ( preg_match("/inet\s(\d+\.\d+\.\d+\.\d+)\//", $str, $matches) )
			$local_addrs[$ifname] = $matches[1];
		}
	}

	//print_r($local_addrs);

	return $local_addrs;
}

function get_local_ip_win()
{
	$out = explode(PHP_EOL,shell_exec("ipconfig"));
	$local_addrs = array();
	foreach($out as $str)
	{
		if (preg_match('/IP-/',$str))
		$local_addrs = preg_replace("/.*:\s(\d+)\.(\d+)\.(\d+)\.(\d+)/", "$1.$2.$3.$4", $str);
	}
	return $local_addrs;
}

$conf_flag = 0;

if ( array_key_exists('read-conf', $options) || array_key_exists('write-conf', $options) )
{
	if ( array_key_exists('p', $options) && ( strlen($options['p']) > 5 || empty($options['p']) ) )
	{
		echo "Error: incorrect password!\n";
		exit;
	}

	elseif ( array_key_exists('ip', $options) && empty($options['ip']) )
	{
		echo "Error: incorrect IP!\n";
		exit;
	}

	elseif ( empty($options['read-conf']) && empty($options['write-conf']) )
	{
		echo "Error: incorrect filename!\n";
		exit;
	}
	elseif ( isset($options['write-conf']) && !isset($options['read-conf']) && !file_exists($options['write-conf']) )
	{
		echo "Filename '".$options['write-conf']."' doesn't exist!\n";
		exit;
	}

	if ( array_key_exists('read-conf', $options) )
	{
		echo "Reading configuration... ";

		$pages = array("cf=1", "cf=2", "cf=7", "cf=8",);

		for ( $i = 0; $i < 10; $i++ )
		$pages[] = "cf=10&prn=$i";

		//$pages = array();
		$page = file_get_contents("http://".$options['ip']."/".$options['p']);

		if ( preg_match("/IN\/OUT/", $page) )
		$ports = 37;
		else
		$ports = preg_replace("/.*\?pt=(\d+).*/", "$1", $page);

		for ( $i = 0; $i <= $ports; $i++ )
		$pages[] = "pt=$i";
	
		$fh = fopen($options['read-conf'], "w");
		$dom = new DOMDocument();
		$preset_flag = 0;

		for ( $i = 0; $i < count($pages); $i++ )
		{
			if ( $preset_flag == 1 )
			{
				//echo "Setting preset 0\n";
				$page = file_get_contents("http://".$options['ip']."/".$options['p']."/?cf=1&pr=0");
				sleep(1);
				$preset_flag = 2;
			}
			$page = file_get_contents("http://".$options['ip']."/".$options['p']."/?".$pages[$i]);

            if (preg_match('/value=<br>/is',$page)) {
                //echo "skipping $pages[$i]<Br/>";
                continue;
            } elseif (preg_match('/name=cf value=1>/is',$page) && preg_match('/cf=10/',$pages[$i])) {
                //echo "skipping $pages[$i]<Br/>";
                continue;
            } else {
                //echo "reading ".$pages[$i]."<br/>";
                //echo htmlspecialchars($page)."<hr>";
            }

			$page = str_replace("<<", "<", $page);
            @$dom->loadHTML($page);

			//$url = "http://".$options['ip']."/".$options['p']."/?";
			$url = "";
			$els=$dom->getelementsbytagname('input');
			foreach($els as $inp)
			{
				if ( $inp->getAttribute('type') != "submit" )
				{
					$name=$inp->getAttribute('name');
					//$value=urlencode($inp->getAttribute('value'));
					if ( $inp->getAttribute('type') == "checkbox" )
					{
						if ( $inp->hasAttribute('checked') )
						$value=1;
						else
						$value='';
					}
					else
					$value=$inp->getAttribute('value');

					$value = str_replace("&", "%26", $value); // &

					if ( $name != "pt" )
					{
						if ( $name == "sl" && empty($value));
						else
						$url .= "$name=$value&";
					}
				}
			}

			$select = $dom->getelementsbytagname('select');

			foreach($select as $elem)
			{
				$name=$elem->getAttribute('name');
				$els=$elem->getelementsbytagname('option');
	
				$sel_flag = 0;
				foreach($els as $inp)
				{
					if ( $inp->hasAttribute('selected') )
					{
						//$name=$inp->getAttribute('name');
						$value=urlencode($inp->getAttribute('value'));
						$value=$inp->getAttribute('value');
						$url .= "$name=$value&";
						$sel_flag = 1;

						if ( $pages[$i] == "cf=1" && $name == "pr" && !empty($value) )
						{
							$preset_flag = 1;
							$stored_preset = $value;
						}

					}
				}
				// Хак ввиду того, что PHP DOM почему-то не может распарсить значение <> поля "Mode"
				if ( $sel_flag == 0 && $name == "m" )
				$url .= "m=3&";
			
			}

			$url = preg_replace("/&$/", "", $url);
			if ( !preg_match("/^cf=1&/", $url) && $i < count($pages) - 1 )
			//if ( !preg_match("/^cf=1&/", $url) )
			$url .= "&nr=1";
			fwrite($fh, "$url\n");
		}

		fclose($fh);

		if ( $preset_flag == 2 )			
		{
			//echo "Setting preset 1\n";
			$page = file_get_contents("http://".$options['ip']."/".$options['p']."/?cf=1&pr=$stored_preset");
			sleep(1);
		}


		echo "OK\n";
	}

	$conf_flag = 1;

}

$chip_type = 0;
$chip_type_t = 0;

# Scanning network for Mega-cool MegaD-devices ;)
if ( array_key_exists('scan', $options) || array_key_exists('s', $options) )
{

	$broadcast_string = chr(0xAA).chr(0).chr(12);

	socket_sendto($sock, $broadcast_string, strlen($broadcast_string), 0, $broadcast_ip, 52000);
	usleep(100);
	do
	{
		$pkt = fread($socket, 10);
		if ( !empty($pkt) && ord($pkt[0]) == 0xAA )
		{
			if ( ord($pkt[2]) == 12 )
			{
				if ( ord($pkt[3]) == 255 && ord($pkt[4]) == 255 && ord($pkt[5]) == 255 && ord($pkt[6]) == 255 )
				echo "192.168.0.14 (default ip-address, ";
				else
				echo ord($pkt[3]).".".ord($pkt[4]).".".ord($pkt[5]).".".ord($pkt[6])." (";
				echo " bootloader mode)\n";
			}
			else
			echo ord($pkt[1]).".".ord($pkt[2]).".".ord($pkt[3]).".".ord($pkt[4])."\n";

		}
	}
	while ( $pkt != false );
}
# Upgrading firmware
else if ( (array_key_exists('ip', $options) || array_key_exists('f', $options) ) && ( array_key_exists('fw', $options) || array_key_exists('w', $options) ) )
{

	if ( isset($options['fw']) && !file_exists($options['fw']) )
	{
		echo "Error: file '".$options['fw']."' doesn't exist!\n";
		exit;
	}

	if ( array_key_exists('p', $options) || array_key_exists('f', $options) )
	{
		if ( array_key_exists('p', $options) && strlen($options['p']) > 5 )
		echo "Error: incorrect password!\n";
		else
		{
			echo "Connecting... ";

			$megad_check = 0;
			if ( !array_key_exists('e', $options) )
			{
				@$fp = fsockopen($options['ip'],80,$errno,$errstr,1);
				if ( $fp )
				$megad_check = 1;
				else
				echo "FAULT\n";
				@fclose($fp);
			}

			if ( $megad_check == 0 && !array_key_exists('e', $options) )
			exit;
	
			if ( $megad_check == 1 || array_key_exists('f', $options) || array_key_exists('e', $options) )
			{
				if ( array_key_exists('ip', $options) )
				{
					usleep(1000);
					$ctx = stream_context_create(array('http' => array('timeout' => 1) ) ); 
					@file_get_contents("http://".$options['ip']."/".$options['p']."/?fwup=1", 0, $ctx);
					usleep(10000);
				}
				sleep(1);

				$broadcast_string = chr(0xAA).chr(0).chr(0x00);
				if ( array_key_exists('e', $options) )
				{
					stream_set_timeout($socket, 0, 30000);
					$pkt = "";
					while ( empty($pkt) )
					{
						//echo ".";
						socket_sendto($sock, $broadcast_string, strlen($broadcast_string), 0, $broadcast_ip, 52000);
						$pkt = fread($socket, 200);
					}
					stream_set_timeout($socket, 0, 300000);
					socket_sendto($sock, $broadcast_string, strlen($broadcast_string), 0, $broadcast_ip, 52000);
					$pkt = stream_socket_recvfrom($socket, 200, 0, $peer);

				}
				else
				{
					socket_sendto($sock, $broadcast_string, strlen($broadcast_string), 0, $broadcast_ip, 52000);
					$pkt = stream_socket_recvfrom($socket, 200, 0, $peer);
				}

				if ( ord($pkt[0]) == 0xAA && ord($pkt[1]) == 0x00 )
				{
					echo "OK";
					if ( ord($pkt[2]) == 0x99 || ord($pkt[2]) == 0x9A )
					{
						echo " (chip type: atmega2561)\n";
						if ( ord($pkt[2]) == 0x99 )
						{
							echo "WARNING! Please upgrade bootloader!\n";
							$chip_type_t = 1;
						}
						$chip_type = 2561;
					}
					else
					echo " (chip type: atmega328)\n";

					if ( array_key_exists('w', $options) )
					{
						if ( $chip_type == 2561 )
						{
							if ( array_key_exists('b', $options) )
							$dl_fw_fname = "megad-2561-beta.hex";
							else
							$dl_fw_fname = "megad-2561.hex";
						}
						else
						{
							if ( array_key_exists('b', $options) )
							$dl_fw_fname = "megad-328-beta.hex";
							else
							$dl_fw_fname = "megad-328.hex";
						}

						echo "Downloading firmware... ";
						if ( $chip_type == 2561 )
						$dl_fw = file_get_contents("http://ab-log.ru/files/File/megad-firmware-2561/latest/$dl_fw_fname");
						else
						$dl_fw = file_get_contents("http://ab-log.ru/files/File/megad-firmware/latest/$dl_fw_fname");
						$dl_fw_fh = fopen($dl_fw_fname, "w");
						fwrite($dl_fw_fh, $dl_fw);
						fclose($dl_fw_fh);
						echo "OK\n";
						$options['fw'] = $dl_fw_fname;
					}

					echo "Checking firmware... ";

					$fh = fopen($options['fw'], "r");
					$firmware = "";
					while (!feof($fh))
					{
						$data = fgets($fh);
						if ( strlen($data) > 0 && $data[8] == 0 )
						{
							$byte_count = $data[1].$data[2];
							for ( $i = 0; $i < base_convert($byte_count, 16, 10); $i++ )
							{
								$pos = $i * 2 + 9;
								$byte = hex2bin($data[$pos].$data[$pos + 1]);
								//fwrite($fh2, $byte);
								$firmware .= $byte;
							}

						}
					}

					// 2561
					if ( (strlen($firmware) > 28670 && $chip_type == 0 ) || ( strlen($firmware) > 258046 && $chip_type == 2561 )  )
					//if ( (strlen($firmware) > 28670 && $chip_type == 0 ) || ( strlen($firmware) > 558046 && $chip_type == 2561 )  )
					{
						echo "FAULT! Firmware is too large!\n";

						// Restaring
						$broadcast_string = chr(0xAA).chr(0).chr(0x03);
						socket_sendto($sock, $broadcast_string, strlen($broadcast_string), 0, $broadcast_ip, 52000);
						$pkt = stream_socket_recvfrom($socket, 200, 0, $peer);

						exit;
					}
					else if ( strlen($firmware) < 1000  )
					{
						echo "FAULT! Firmware length is zero or file is corrupted!\n";

						// Restarting
						$broadcast_string = chr(0xAA).chr(0).chr(0x03);
						socket_sendto($sock, $broadcast_string, strlen($broadcast_string), 0, $broadcast_ip, 52000);
						$pkt = stream_socket_recvfrom($socket, 200, 0, $peer);

						exit;
					}
					elseif ( (strlen($firmware) > 32768 && $chip_type == 2561 && $chip_type_t == 1 )  )
					{
						echo "FAULT! You have to upgrade bootloader!\n";

						// Restaring
						$broadcast_string = chr(0xAA).chr(0).chr(0x03);
						socket_sendto($sock, $broadcast_string, strlen($broadcast_string), 0, $broadcast_ip, 52000);
						$pkt = stream_socket_recvfrom($socket, 200, 0, $peer);

						exit;
					}
					else
					echo "OK\n";

					echo "Erasing firmware... ";
					$broadcast_string = chr(0xAA).chr(0).chr(0x02);
					socket_sendto($sock, $broadcast_string, strlen($broadcast_string), 0, $broadcast_ip, 52000);
					$pkt = stream_socket_recvfrom($socket, 200, 0, $peer);
					//$pkt[0] = chr(0xAA);
					//$pkt[1] = chr(0x00);
					if ( ord($pkt[0]) == 0xAA && ord($pkt[1]) == 0x00 )
					{
						echo "OK\n";
						echo "Writing firmware... ";

						if ( $chip_type == 2561 )
						$block = 256;
						else
						$block = 128;

						$fault_flag = 0;
						$prev_id = 0;
						$err_cnt = 0;
						$fw_block = str_split($firmware, $block);
						stream_set_timeout($socket, 2);
						$msg_id = 0;
						for ( $i = 0; $i < ceil(strlen($firmware) / $block); $i++ )
						{
							//echo "$msg_id\n";
							$broadcast_string = chr(0xAA).chr($i).chr(0x01).$fw_block[$i];
							socket_sendto($sock, $broadcast_string, strlen($broadcast_string), 0, $broadcast_ip, 52000);
							//echo " ->";
							usleep(400);
							//$pkt = stream_socket_recvfrom($socket, 200, 0, $peer);
							unset($pkt);
							$pkt = fread($socket, 10);

							if ( ( isset($pkt[0]) && ord($pkt[0]) == 0xAA && ord($pkt[1]) != $msg_id ) || !isset($pkt[0]) )
							{
								echo "FAULT\nPlease update firmware in recovery mode (-f -e)\n";
								break;
							}

							/*
							if ( ( isset($pkt[0]) && ord($pkt[0]) == 0xAA && ord($pkt[1]) != $msg_id ) || !isset($pkt[0]) )
							{
								$i = $prev_id + 1;
								$err_cnt++;
								//echo "* ($i)".$pkt[1]." ";
								echo "* ";
				
								if ( $err_cnt == 50 )
								{
									echo "FAULT\n";
									$fault_flag = 1;
									break;
								}			
							}
							else
							{
								//echo ". ($i) ";
								$prev_id = $i;
							}
							*/

							$msg_id++;
							if ( $msg_id == 256 )
							$msg_id = 0;
						}

						if ( $fault_flag == 0 )
						echo "OK\n";

					}
					else
					echo "FAULT\n";

					if ( array_key_exists('ee', $options) )
					{
	                                        echo "Erasing EEPROM... ";
						$broadcast_string = chr(0xAA).chr(0).chr(9);
						socket_sendto($sock, $broadcast_string, strlen($broadcast_string), 0, $broadcast_ip, 52000);
						// 2561
						stream_set_timeout($socket, 30);
						$pkt = stream_socket_recvfrom($socket, 200, 0, $peer);
						//echo ord($pkt[0])." ".ord($pkt[1])." ".ord($pkt[2])."\n";

						if ( $chip_type == 2561 )
						{
        						$broadcast_string = chr(0xAA).chr(0x01).chr(9);
							socket_sendto($sock, $broadcast_string, strlen($broadcast_string), 0, $broadcast_ip, 52000);
							$pkt = stream_socket_recvfrom($socket, 200, 0, $peer);
							//echo ord($pkt[0])." ".ord($pkt[1])." ".ord($pkt[2])."\n";

							if ( ord($pkt[0]) == 0xAA && ord($pkt[1]) == 0x01 )
							echo "OK\n";
							else
							echo "FAULT\n";
						}
						else
						{
							if ( ord($pkt[0]) == 0xAA && ord($pkt[1]) == 0x00 )
							echo "OK\n";
							else
							echo "FAULT\n";
						}

					}


					echo "Restarting device... ";
					//$broadcast_string = chr(0xAA).chr(0).chr(11);
					//socket_sendto($sock, $broadcast_string, strlen($broadcast_string), 0, $broadcast_ip, 52000);
					//$pkt = stream_socket_recvfrom($socket, 200, 0, $peer);

					$broadcast_string = chr(0xAA).chr(0).chr(0x03);
					socket_sendto($sock, $broadcast_string, strlen($broadcast_string), 0, $broadcast_ip, 52000);
					$pkt = stream_socket_recvfrom($socket, 200, 0, $peer);
					//echo ord($pkt[0])." ".ord($pkt[1])." ".ord($pkt[2])."\n";

					if ( ord($pkt[0]) == 0xAA && ord($pkt[1]) == 0x00 )
					echo "OK\n";
					else
					echo "FAULT\n";

				}
				else
				echo "FAULT\n";

			} 
		}
	}
	else
	echo "Error: empty password!\n";
}
elseif ( $conf_flag == 1 || ( array_key_exists('ip', $options) && array_key_exists('new-ip', $options) ) )
{}
else
{
	echo "MegaD-2561 management script Ver 2.12\n";
	echo "Available options:\n";
	echo "--scan (Scanning network for MegaD-328/2561 devices)\n";
	echo "--ip [current IP address] --new-ip [new IP address] -p [password] (Changing IP-address)\n";
	echo "--ip [IP address] --fw [HEX-file] -p [password] (Upgrade firmware. Normal mode)\n";
	echo "--ip [IP address] -w -p [password] (Upgrade firmware from ab-log.ru. Add -b for beta. Normal mode)\n";
	echo "--fw [HEX-file] -f (Upload firmware. Empty flash, bootloader mode)\n";
	echo "--fw [HEX-file] -f -e (Upload firmware. Broken firmware)\n";
	echo "--ee (Optional! Erase EEPROM)\n";
	echo "--read-conf [filename] (Read configuration: from device to file)\n";
	echo "--write-conf [filename] (Write configuration: from file to device)\n";
}

# Setting IP-address
if ( (array_key_exists('ip', $options) && array_key_exists('new-ip', $options)) || ( array_key_exists('ee', $options) && array_key_exists('write-conf', $options) ) )
{
	if ( array_key_exists('ee', $options) )
	{
		$password = "sec";
		$ip = "192.168.0.14";
		$options['new-ip'] = $options['ip'];
		echo "Waiting...";
		sleep(2);
		echo "OK\n";
	}
	else
	{
		$password = $options['p'];
		$ip = $options['ip'];
	}

	if ( array_key_exists('p', $options) )
	{
		if ( strlen($options['p']) > 5 )
		echo "Error: incorrect password!\n";
		else
		{
			$old_device_ip = explode(".", $ip);
			$new_device_ip = explode(".", $options['new-ip']);

			$wrong_ip = false;
			$broadcast_string = chr(0xAA).chr(0).chr(4);
	
			for ( $i = 0; $i < 5; $i++ )
			{
				if ( empty($password[$i]) )
				$broadcast_string .= "\0";
				else
				$broadcast_string .= $password[$i];
			}

			for ( $i = 0; $i < 4; $i++ )
			{
				if ( $old_device_ip[$i] < 0 || $old_device_ip[$i] > 255 )
				$wrong_ip = true;
				else
				$broadcast_string .= chr($old_device_ip[$i]);
			}

			for ( $i = 0; $i < 4; $i++ )
			{
				if ( $new_device_ip[$i] < 0 || $new_device_ip[$i] > 255 )
				$wrong_ip = true;
				else
				$broadcast_string .= chr($new_device_ip[$i]);
			}
	
			if ( count($old_device_ip) != 4 || count($new_device_ip) != 4 || $wrong_ip == true )
			echo "Error: wrong IP!\n";
			else
			{
				socket_sendto($sock, $broadcast_string, strlen($broadcast_string), 0, $broadcast_ip, 52000);
				usleep(100);
				$pkt = fread($socket, 10);
				if ( ord($pkt[0]) == 0xAA )
				{
					if ( ord($pkt[1]) == 0x01 )
					echo "IP address was successfully changed!\n";
					elseif ( ord($pkt[1]) == 0x02 )
					echo "Wrong password!\n";
				}
				else
				echo "Device with IP-address ".$ip." not found!\n";

				//echo $pkt;
			}
		}
	}
	else
	echo "Error: empty password!\n";
}

if ( array_key_exists('write-conf', $options) && $conf_flag == 1 )
{
	echo "Writing configuration... ";

	$wconf = file($options['write-conf']);
	for ( $i = 0; $i < count($wconf); $i++ )
	{
		$wconf[$i] = preg_replace("/\n|\r/", "", $wconf[$i]);
		$result = "";
		if ( array_key_exists('ee', $options) && $i == 0 )
		{
			if ( array_key_exists('new-ip', $options) )
			file_get_contents("http://".$options['new-ip']."/sec/?".$wconf[$i]);
			else
			file_get_contents("http://".$options['ip']."/sec/?".$wconf[$i]);
		}
		else
		{
			$result = file_get_contents("http://".$options['ip']."/".$options['p']."/?".$wconf[$i]);
			//echo "http://".$options['ip']."/".$options['p']."/?".$wconf[$i]."\n";
			if ( $i == 0 && preg_match("/&pwd=/", $wconf[$i]) )
			$options['p'] = trim(preg_replace("/.*&pwd=(.*)&.*?/U", "$1", $wconf[$i]));

		}
		//echo $wconf[$i]."\n";

		if ( preg_match("/&nr=1$/", $wconf[$i]) && preg_match("/Done$/", $result) )
		usleep(10000);
		else
		usleep(100000);

		if ( !preg_match("/^cf/", $wconf[$i]) && ( !preg_match("/&nr=1$/", $wconf[$i]) || !preg_match("/Done$/", $result) ) )
		{
			//echo ".";
			file_get_contents("http://".$options['ip']."/".$options['p']."/?".$wconf[$i]);
			usleep(10000);
		}
	}

	echo "OK\n";
}


# Closing sockets
socket_close($sock);
fclose($socket);

?>