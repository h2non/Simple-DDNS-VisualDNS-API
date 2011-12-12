#!/usr/bin/php
<?php
/**
 * Simple DDNS PHP Script for visualDNS.net WSDL API
 *
 * @description Simple DDNS web based keeping update the main domain type 'A' with the current host WAN IP
 * @author      Tomas Aparicio <tomas@rijndael-project.com>
 * @license     GNU GPL 3.0
 * @version     0.1.1b revision 11
 * @see         README.md
 *
 * Copyright (C) 2011 Tomas Aparicio <tomas@rijndael-project.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// set yout visuanDNS API key
$API='-set-your-api-key-here';

date_default_timezone_set('Europe/Madrid');
$date = date('Y-m-d H:i');
$IPversion = 4;

try {

        // implemented for checkip from DynDNS web service
        // this return a fucking shit HTML (I hope in future support XML or JSON)
        $ip = file_get_contents ('http://checkip.dyndns.org/');

        if (!$ip ||  empty($ip))
        {
                die ( $date . ' - ERROR : Cannot make a request to DynDNS for get current IP or the respones was empty. Check the service.' . "\n");
        }

        $patron = '([:\ ][0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})';
        preg_match($patron, $ip, $current, PREG_OFFSET_CAPTURE);

        if (empty($current) || empty($current[0][0]))
        {
                die ( $date . ' - ERROR : The IP returned value is empty or cannot readadable. Try it out : http://checkip.dyndns.org/' . "\n");
        }

        $current = trim($current[0][0]);

        // check valid IPv4
        if ($IPversion == 4)
        {
                if (ip2long($current) === false) die ( $date . ' - ERROR : the current IPv4 addess is invalid. Check if the returned IP from http://checkip.dyndns.org is valid.');
        }

        file_exists('/tmp/ddns.ip') ? $ip = trim (file_get_contents('/tmp/ddns.ip')) : $ip = '0.0.0.0';

        if ($ip != $current)
        {
                echo $date . ' - NOTICE : The public IP was changed from ' . $ip . ' to ' . $current . ' and the NS will be updated...' . "\n";

                try {
                        $fp = fopen('/tmp/ddns.ip', 'w');
                        fwrite($fp, $current);
                        fclose($fp);
                } catch (Exception $e) {
                        die ( $date . ' ERROR : Cannot update the temporal IP file on /tmp/ddns.ip. Error: ' . $e . "\n");
                }

                // init SOAP
                $client = new SoapClient('https://visualdns.net/api/wsdl', array(
                        'compression' => SOAP_COMPRESSION_ACCEPT
                ));

                try {
                        // Get all domains
                        $domains = $client->getDomains($API);
                } catch (Exception $e) {
                        echo $e->getMessage();
                        die();
                }

                foreach ($domains as $i => $value)
                {
                        $host = $value['name'];
                        sleep(1);

                        try {
                                // Get records for specified domain
                                $records = $client->getRecords($API, $host);
                        } catch (Exception $e) {
                                echo $e->getMessage();
                                die();
                        }

                        foreach ($records as $x => $name)
                        {
                                if (trim($name['name']) == $host && trim($name['type']) == 'A') {
                                        $id = (int)$name['id'];
                                        $type = $name['type'];
                                        $domain = trim($name['name']);
                                        break;
                                }
                        }

                        if (empty($id))
                        {
                                echo $date . ' - ERROR : Cannot find the record value for ' . $host . "\n";
                        } else {
                                try {
                                        //echo (int)$id . ' -> ' . $domain . ' -> ' . $current . ' -> ' . $type ;
                                        $recordUpdate = $client->editRecord($API, $id, $domain, $type, $current, 3600);
                                } catch (Exception $e) {
                                        echo $e->getMessage();
                                        die();
                                }
                                echo $date . ' - NOTICE : Update done correctly for domain ' . $host . ' with type "' .$type. '" with value "'.$current.'" ' . "\n";
                        }
                }
        }

} catch (Exception $e) {
        die ( $date . ' -  ERROR : An script error ocurred. Excepcion Error returned : ' . $e );
}

?>
