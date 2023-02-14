# Jeedom Plugin for Eufy Devices/Stations

![Logo Jeedom](docs/images/jeedom.png)
![Logo Plugin](docs/images/eufy.png)

## Tested devices
- T8010 homebase2
- T8030 homebase3
- T8113 Eufycam 2C 
- T8114 Eufycam 2
- T8161 Eufycam 3C
- T8210 Battery Doorbell
- T8400 Indoor Cam
- T8410 Indoor Cam Pan&Tilt

## Untested devices
Other Eufy models should also work but might not be recognized incompletely or require a config file.
<br>See the full list [here](https://bropat.github.io/eufy-security-client/#/supported_devices)
<br>If your model is not listed above please send me the results of these commands:

    $ python3 resources/test_eufy.py -s station_serial

and/or (depending on your model):

    $ python3 resources/test_eufy.py -d device_serial

help: 

    $ python3 resources/test_eufy.py -h
    usage: test_eufy.py [-h] [-s STATION] [-d DEVICE] [-u URL]

    optional arguments:
	-h, --help            show this help message and exit
	-s STATION, --station STATION
                        station serial number
	-d DEVICE, --device DEVICE
                        device serial number
	-u URL, --url URL     eufy service URL, default 127.0.0.1:3000

## Documentation

- User Documentation [(en)](docs/en_US/index.md)
- User Documentation [(fr)](docs/fr_FR/index.md)

## Credits
Project fork of [alexandreberton/eufy](https://github.com/alexandreberton/eufy)
<br>Uses [bropat/eufy-security-ws](https://github.com/bropat/eufy-security-ws) lib (docker prerequisite)

## ChangeLog
* v0.8 [lxrootard](https://github.com/lxrootard)
<br> minor json fixes and icon enhancements
* v0.7 [lxrootard](https://github.com/lxrootard)
<br> added support for T8400 and T8161
* v0.6 [lxrootard](https://github.com/lxrootard)
<br> - Changed json format
<br> - Added T8030 suport
* v0.5 [lxrootard](https://github.com/lxrootard)
<br> - Fix to sync for devices supporting both Station and Device interface 
* v0.4 [lxrootard](https://github.com/lxrootard)
<br> - Added new infos and commands for T8010 T8113 T8114 T8210 T8210 and T8210 devices
* v0.3 [lxrootard](https://github.com/lxrootard)
<br> - Added generic support for integer command values, lists and sliders
<br> - Added support for T8410 and T8210 devices
<br> - Added metadata check to test_eufy.py script
* v0.2 [lxrootard](https://github.com/lxrootard)
<br> - Added station and webcam extended properties support
<br> - Added new infos and commands to T8113, T8114 and T8010 devices
<br> - Added test_eufy.py diagnostic script
<br> - Added install script with dependencies
<br> - Added local and remote container modes with docker image and container setup options
<br> - Added checks for Eufy service at startup: container listening and driver connected
<br> - Added health screen
<br> - Added generic support for binary commands

* v0.1 [lxrootard](https://github.com/lxrootard) 
<br> - Created separate equipement types for Station and Webcam 
<br> - Added support for T8010 base, updated other devices types (T8113 OK, other types to be tested, see core/config/*.json)
<br> - Added support for Webcam commands: activation, motionDetection, LED, antiTheft, refresh
<br> - Moved guard command to Station type, added IP, MAC address and status infos
<br> - Corrected guardMode update bug

* v0 [a.berton](https://github.com/alexandreberton)

## Troubleshooting and known issues
* Quotes are not supported in equipment names
* 2FA is not supported yet, please use a secondary account with 2FA disabled
<p>
Also see the [Jeedom community blog](https://community.jeedom.com/t/integration-de-materiel-eufy/76603)

## Todo
* Improve error checking
