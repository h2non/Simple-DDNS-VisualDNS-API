# Simple DDNS PHP script for VisualDNS WS SOAP based API

## ABOUT
This is a very very simple (and for personal purposes) PHP based script to implement an easy "DDNS" (non-protocol explicit) for VisualDNS.net via its SOAP based API Web Service.
Your PHP version need SOAP support. 
Feel free to improve it for any purpouses.

## USAGE

1. Register and get your API key from visualdns.net
2. Edit ddns.php file and define your API key hash.
3. Set execution permissions (*UNIX like)
`$ chmod +x ddns.php` 
4. Run 
`$ ./ddns.php >> /var/log/ddns.log`

Also you can automatize it adding this job to the crontab (e.g running it each hour)

```
$ crontab -e
```

And add the following line:

```
1 * * * * /path/to/file/ddns.php >> /var/log/ddns.log
``` 

The output will be stored in `/var/log/ddns.log`

## LICENSE

GNU GPL 3.0

Copyright (C) 2011  Tomas Aparicio

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
