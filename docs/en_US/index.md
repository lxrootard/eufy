# Jeedom Plugin for Eufy Devices/Stations

![Logo Jeedom](../images/jeedom.png)
![Logo Plugin](../images/eufy.png)

## Documentation
- [Configuration](#configuration)
- [Synchronization](#synchronization)
- [Health](#health)
- [Equipments](#equipments)
- [Bugs and troubleshooting](#troubleshooting)

### Configuration
![Configuration](../images/eufy3.png)

Install the plugin and its dependencies first.
<br>Note: This action does NOT install the `eufy-security-ws` image.
<br>Choose either local or remote docker mode:
#### 1. Local mode
Local mode requires docker already installed and configured.
<br>If it's not the case install the `docker management` official plugin or from the command line:

`$ apt-get install docker.io`

Post-installation setup:

- Install/uninstall Eufy: install/uninstall `eufy-security-ws` image
- Start/stop Eufy: start/stop the `eufy-security-ws` container
- Device name: Eufy `TRUSTED_DEVICE_NAME` used to connect to Cloud server
- User/password: `Eufy-WS` Cloud service credentials

#### 2. Remote mode (expert)
Assumes an already running `eufy-security-ws` container.
<br>Optionally you can copy and use the `resources/eufyctl.sh` script to install and test the `eufy-security-ws` image maneually on a remote server:

`eufyctl.sh install|uninstall|status|test|stop|start <device> <login> <passwd> [ port ]`
 
####  3. Common parameters
- Docker IP: `eufy-security-ws` container host IP, 127.0.0.1 by default
- Docker Port: `eufy-security-ws` container port, 3000 by default
- Test communication: Check connexion to `eufy-security-ws` container

Notes: 
- The Eufy daemon won't start if the `eufy-security-ws` container can't connect to the Eufy Cloud service
- The `Version` field displays the container version 

####  4. Connectivity issues
If something goes wrong first run:
<br>`resources/eufyctl.sh test`
<br>
<br> you should get something like this:

```
{"type":"result","success":true,"result":{"state":{"driver":{"version":"2.4.0","connected":true,"pushConnected":true}
```

Note that `connected` and `pushConnected` need to be `true`
<br>Also see see `eufy_service_setup` log 

### Synchronization
![Configuration](../images/eufy2.png)

Note: for now only some devices have been tested. If your device is not supported you can send me the outputs from the `resources/test_eufy.py`
<br>Please see [here](../../README.md#Tested) for details.

### Health
![Configuration](../images/eufy1.png)

Lists the recognized devices and their status. 

### Equipments
![Equipments](../images/eufy4.png)
![Equipments](../images/eufy5.png)
![Equipments](../images/eufy6.png)

### Enabling snapshots
The `URL snapshot` command contains the server's picture URL
<br> You can use my [ImageViewer](https://github.com/lxrootard/widgets_v4) widget
<br> Enable picture notifications in your devices parameters from the Eufy app or the snapshots won't refresh
 
### RTSP video stream
The RTSP stream can be accessed with the [Camera](https://doc.jeedom.com/en_US/plugins/security/camera) plugin
<br>On battery-powered cams it needs to be activated/deactivated using the equipment commands.
<br>The IP is the one from the base and `live0...liven` indicate the camera number, see the screenshot below:

![RTSP video stream](../images/camera_plugin.jpg)

<br>Check the URL  depending your model [here](https://camlytics.com/camera/eufy)

### Bugs and troubleshooting
See [here](../../README.md#Troubleshooting)
