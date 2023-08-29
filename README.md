# Jeedom Plugin for Eufy Devices/Stations

![Logo Jeedom](docs/images/jeedom.png)
![Logo Plugin](docs/images/eufy.png)

## Documentation

- User Documentation [(en)](docs/en_US/index.md)
- User Documentation [(fr)](docs/fr_FR/index.md)

## Tested devices
- T8001 Homebase
- T8002 Homebase E 
- T8010 Homebase 2
- T8030 Homebase 3
- T8111 Camera
- T8113 Camera 2C 
- T8114 Camera 2
- T8140 Camera 2 Pro
- T8142 Camera 2C Pro
- T8160 Camera 3
- T8161 Camera 3C
- T8210 Battery Doorbell. RTSP not supported
- T8400 Indoor Cam
- T8410 Indoor Cam Pan&Tilt
- T8423 Floodlight 8423 
- T8424 Floodlight Cam 2
- T8441 Outdoor Cam Pro
- T8910 Motion Sensor. `motionDetection` events not working, 
see [this thread](https://github.com/fuatakgun/eufy_security/issues/22)

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

## Troubleshooting
* Container install failed or container communication indicator is red
<br> Verify docker is working properly before installing the plugin
* The daemon doesn't start
<br> Make sure both container and Cloud communication indicators are green in the Configuration section 
* Synchronization failed, devices are not found
<br> Quotes are not supported in equipment names
* Cam snapshots don't update
<br> Enable snapshots in the Eufy app: Device > Parameters > Notification

## Known issues
* Image snapshot is implemented but refresh doesn't happen due to a [bug](https://github.com/bropat/eufy-security-ws/issues/217)
* 2FA is not supported yet, please use a secondary account with 2FA disabled
* P2P streaming is not supported, use RTSP when available instead
<p>
Also search for `#plugin-eufy` or see the [Eufy plugin](https://community.jeedom.com/t/plugin-eufy/102453) 
thread in the Jeedom community blog

## ChangeLog
* v1.8 [lxrootard](https://github.com/lxrootard)
<br> support of cam snapshots
<br> documentation update
* v1.7 [lxrootard](https://github.com/lxrootard)
 <br> added support for T8001, T8111
* v1.6 [lxrootard](https://github.com/lxrootard)
<br> simplified config files, type on 3 chars
<br> added support for snapshots 
<br> added support for T8140, T8160
* v1.5 [lxrootard](https://github.com/lxrootard)
<br> improved container readiness checking. Added support for fixed T8142 and T8423
* v1.4 [lxrootard](https://github.com/lxrootard)
<br> fixed serialNumber error at startup
* v1.3 [lxrootard](https://github.com/lxrootard)
<br> added support for model name + sendEvent bug fix
* v1.2 [lxrootard](https://github.com/lxrootard)
<br> added support for T8424, T8441, T8910. fixed T8210
* v1.1 [lxrootard](https://github.com/lxrootard)
<br> fix install script permissions
* v1.0 [lxrootard](https://github.com/lxrootard)
<br> fix dependencies install
* v0.9 [lxrootard](https://github.com/lxrootard)
<br> metadata for market publication
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

## Todo
* Improve error checking

## Credits
Project fork of [alexandreberton/eufy](https://github.com/alexandreberton/eufy)
<br>Uses [bropat/eufy-security-ws](https://github.com/bropat/eufy-security-ws) lib (docker prerequisite)

