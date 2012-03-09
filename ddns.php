#!/usr/bin/php
<?php
/**
 * Simple DDNS PHP Script for visualDNS.net WSDL API
 *
 * @description Simple DDNS web based keeping update the main domain type 'A' with the current host WAN IP
 * @author      Tomas Aparicio <tomas@rijndael-project.com>
 * @license     GNU GPL 3.0
 * @version     0.1.3 beta revision 20
 * @see         README.md for how to usage
 * @api		https://visualdns.net/api-documentation
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
$API='set-your-api-key-here';

date_default_timezone_set('Europe/Madrid');
$date = date('Y-m-d H:i');
$IP = 4; // define the IP protocol version - v6 coming soon

// list of domains activated to update
$ddns = array (
	'example.com' => array(
			'home.example.com'
		),
	// add more here like array index
);

if (!class_exists('SoapClient'))
{
	die("Your PHP version seem doesn't SOAP support. Please, install the SOAP extension and try again.\n");
}

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
        if ($IP == 4)
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
                $client = new SoapClient('http://visualdns.net/api/wsdl', array(
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
			// search if should update the current domain @see $domains 
			if (!array_key_exists($host,$ddns)) continue; 
			// get current domain
			$toUpdate = $ddns[$host];

			// if exists proceed to update
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
				// check exists
				if (!in_array(trim($name['name']),$toUpdate)) continue;
				// by default only update the A type registry for the literal domain host
				// improve it according to your needs
                                if (trim($name['type']) == 'A') {
                                	$id = (int)$name['id'];
                                	$type = $name['type'];
                                	$domain = trim($name['name']);

					try {
						$recordUpdate = $client->editRecord($API, $id, $domain, $type, $current, 3600);
					} catch (Exception $e) {
						die($e->getMessage());
					}
                                	echo $date . ' - NOTICE : Update done correctly for domain ' . $host . ' with type "' .$type. '" with value "'.$current.'" ' . "\n";
				}
                        }

                        if (empty($id))
                        {
                        	echo $date . ' - ERROR : Cannot find the record value for ' . $host . "\n";
                        }
                }
        }

} catch (Exception $e) {
        die ( $date . ' -  ERROR : An script error ocurred. Excepcion Error returned : ' . $e );
}

