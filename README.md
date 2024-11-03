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
- T81A0 Solar Wall Light Cam S120
- T8111 Camera
- T8113 Camera 2C 
- T8114 Camera 2
- T8134 S220 (requires image v1.7.0+)
- T8140 S221 Camera 2 Pro
- T8142 Camera 2C Pro
- T8160 Camera 3
- T8170 SoloCam S340
- T8161 Camera 3C
- T8210 Battery Doorbell (RTSP not supported)
- T8400 Indoor Cam
- T8410 Indoor Cam Pan&Tilt
- T8423 Floodlight Cam 2 Pro
- T8424 Floodlight Cam 2
- T8425 Floodlight Cam E340
- T8441 Outdoor Cam Pro
- T84A1 Wired Wall Light Cam S100
- T8600 Eufycam E330
- T8910 Motion Sensor. `motionDetection` events not working, 
see [this thread](https://github.com/fuatakgun/eufy_security/issues/22)
- T8960 Keypad

## Untested devices
Other Eufy models should also work but might not be recognized incompletely or require a config file.
See the full list [here](https://bropat.github.io/eufy-security-client/#/supported_devices)
<br>If your model is not listed above or some controls are missing please send me the results of these commands:

    $ cd /var/www/html/plugins/eufy
    $ ./resources/eufy -d device_serial test

where `device_serial` is your device serial number, starting by `Txxx`
<br>

## Troubleshooting
* Prerequisites not found
<br> `docker` prerequisites are now installed automatically using the `dependencies` button when the mode is set to `local`
<br> `python_venv` prerequisite is now installed automatically on `debian 12`
* Eufy deamon fails to start
<br>Check the docker image, container and cloud login status indicators (see below).
<br>If the docker image is properly installed its version will be displayed on the `installed version` line.
<br>Both container and cloud login status indicators must be green (see below).
<br>Use the `eufy` script the script in `resources` to get more info. 
See the [Local mode (expert)](docs/en_US/index.md#configuration) documentation section for details.
```
        eufy status # check the image and container status
        eufy info # check the image and container status (extended info)
        eufy test # check if the container is connected to the eufy cloud
        eufy logs # get the container logs
```
* Docker container indicator (left) is red
<br> Docker image install failed or container fails to start. Make sure your image is property installed
<br> Note: @bropat does not provide armv7 (Pi3) images anymore, latest one is 1.7.1
* Eufy Cloud indicator (right) is red
<br> A red Cloud indicator means the Eufy cloud authentication failed or Eufy prevents you from connecting for security reasons.
Verify that your Eufy login, password and device name are correct.
* Synchronization failed, devices are not found
<br> Apostrophes and quotes are not supported in equipment names
* Incompatible schema error, commands not working
<br> Make sure you're using the correct `eufy-security-ws` [release](https://github.com/bropat/eufy-security-ws/releases).
<br> Your installed image version is displayed in the plugin config page when hitting the `Tester` button.
<br> If you've upgraded the plugin you might try to uninstall/reinstall the image and restart the container.
* Some actions change the corresponding property but there's no change on the device (eg flash on/off)
<br> These are not actions but device settings that will only impact its behavior for the next detection 
(eg the flash will light at the next event)
* Cam snapshots don't update
<br> Enable snapshots in the Eufy app: `Device > Parameters > Notification`

## Known issues
* Some commands don't [work as expected](https://github.com/bropat/eufy-security-ws/issues/212) on some devices
* 2FA is not supported yet, please use a secondary account with 2FA disabled
* P2P streaming is not supported, use RTSP when available instead

Also check the [Jeedom community blog](https://community.jeedom.com/tag/plugin-eufy)
tag: `#plugin-eufy`

## ChangeLog
* v2.14 [lxrootard](https://github.com/lxrootard)
<br> - Added support for T8425 and T8600
<br> - Added Value column in device commands tab
<br> - Fix for aarch64 docker install
* v2.13 [lxrootard](https://github.com/lxrootard)
<br> - Fix to support lib v1.9.1 and upcoming releases
<br> - Fix in the docker detection
* v2.12 [lxrootard](https://github.com/lxrootard)
<br> - Fix to solve [this issue](https://community.jeedom.com/t/pi4-et-docker-quelle-difference-entre-aarch64-et-arm64/130481/15)
* v2.11 [lxrootard](https://github.com/lxrootard)
<br> - Added restart docker image button
* v2.10 [lxrootard](https://github.com/lxrootard)
<br> - Improved image management
<br> - Added documentation and community links
* v2.9 [lxrootard](https://github.com/lxrootard)
<br> - debian12 compatibility fixes
* v2.8 [lxrootard](https://github.com/lxrootard)
<br> - Support for debian12
<br> - Automatic docker prerequisites installation
<br> - Improved eufy image management logging
<br> - Added command motionDetectionType for T844x
* v2.7 [lxrootard](https://github.com/lxrootard)
<br> - Fix in the installation procedure
<br> - eufy script improvements
<br> - Added support for T81A0
* v2.6 [lxrootard](https://github.com/lxrootard)
<br> - Added new commands for T801x
<br> - New installer using `docker-compose`
* v2.5 [lxrootard](https://github.com/lxrootard)
<br> - Added support for T8170
<br> - Bugfix
* v2.4 [lxrootard](https://github.com/lxrootard)
<br> - Upgrade schema version to v21 for `eufy-security-ws` v1.8 support
<br> - Bugfix for list values 
* v2.3 [lxrootard](https://github.com/lxrootard)
<br> - Added color support for capable devices (eg T84A1). Waiting for 
[fix](https://github.com/bropat/eufy-security-ws/issues/293)
<br> - Added support for T8960
<br> - Added commands filter in equipment view. thanks @phpvarious
<br> - Minor optimisations
* v2.2 [lxrootard](https://github.com/lxrootard)
<br> - Added support for T84A1 and T8134 + T842x fix
<br> - Added container version display in config page
<br> - Improved eufy_test.py script
* v2.1 [lxrootard](https://github.com/lxrootard)
<br> - Fixed update issue for dual interface devices
<br> - Container logs debug level management
<br> - json files updates
* v2.0 [lxrootard](https://github.com/lxrootard)
<br> - Added calibrate command for pan & tilt cams (eg T8410)
<br> - Fixed a dependency issue
* v1.9 [lxrootard](https://github.com/lxrootard)
<br> - Support of pan & tilt commands for capable devices (eg T8410)
* v1.8 [lxrootard](https://github.com/lxrootard)
<br> - Support of cam snapshots
<br> - Documentation update
* v1.7 [lxrootard](https://github.com/lxrootard)
<br> - Added support for T8001, T8111
* v1.6 [lxrootard](https://github.com/lxrootard)
<br> - Simplified config files, type on 3 chars
<br> - Added support for snapshots 
<br> - Added support for T8140, T8160
* v1.5 [lxrootard](https://github.com/lxrootard)
<br> - Improved container readiness checking. Added support for fixed T8142 and T8423
* v1.4 [lxrootard](https://github.com/lxrootard)
<br> - Fixed serialNumber error at startup
* v1.3 [lxrootard](https://github.com/lxrootard)
<br> - Added support for model name + sendEvent bug fix
* v1.2 [lxrootard](https://github.com/lxrootard)
<br> - Added support for T8424, T8441, T8910. fixed T8210
* v1.1 [lxrootard](https://github.com/lxrootard)
<br> - Fix install script permissions
* v1.0 [lxrootard](https://github.com/lxrootard)
<br> - Fix dependencies install
* v0.9 [lxrootard](https://github.com/lxrootard)
<br> - Metadata for market publication
* v0.8 [lxrootard](https://github.com/lxrootard)
<br> - Minor json fixes and icon enhancements
* v0.7 [lxrootard](https://github.com/lxrootard)
<br> - Added support for T8400 and T8161
* v0.6 [lxrootard](https://github.com/lxrootard)
<br> - Changed json format
<br> - Added T8030 suport
* v0.5 [lxrootard](https://github.com/lxrootard)
<br> - Fix to sync for devices supporting both Station and Device interface 
* v0.4 [lxrootard](https://github.com/lxrootard)
<br> - Added new infos and commands for T8010 T8113 T8114 and T8210 devices
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
* Implement p2p streaming
* Improve error checking

## Credits
Project fork of [alexandreberton/eufy](https://github.com/alexandreberton/eufy)
<br>Uses [bropat/eufy-security-ws](https://github.com/bropat/eufy-security-ws) lib (docker prerequisite)

