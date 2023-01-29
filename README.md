# Jeedom Plugin for Eufy WebCams/Stations

![Logo Jeedom](docs/images/jeedom.png)
![Logo Plugin](docs/images/eufy.png)

## Documentation

- User Documentation [(en)](docs/en_US/index.md)
- User Documentation [(fr)](docs/fr_FR/index.md)

## Credits
Project fork of [alexandreberton/eufy](https://github.com/alexandreberton/eufy)
<br>Uses [bropat/eufy-security-ws](https://github.com/bropat/eufy-security-ws) lib (docker prerequisite)

## ChangeLog
* v0.2 [lxrootard](https://github.com/lxrootard)
<br> - Added station and webcam extended properties
<br> - Added new infos and commands to T8113, T8114 and T8010 devices
<br> - Added diagnostic script
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
* Add generic support for numeric properties
* Improve error checking
