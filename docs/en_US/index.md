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

Install the plugin and its dependencies.
<br>Choose either local or remote docker mode.
<br>Don't forget to save the configuration BEFORE launching the dependencies installation.
<br>Note: This action does NOT install the `eufy-security-ws` image.

#### 1. Common parameters
- Docker IP: `eufy-security-ws` container host IP, `127.0.0.1` by default
- Docker Port: `eufy-security-ws` container port, `3000` by default
- Test communication: Check connexion to `eufy-security-ws` container

Notes:
- The Eufy daemon won't start if the `eufy-security-ws` container can't connect to the Eufy Cloud service
- The `Version` field displays the container version

#### 2. Local mode
Local mode requires `docker` and the `docker compose` plugin already installed and configured.
<br>If it's not the case install the `docker management` official plugin or from the command line.
see the [docker webpage](https://docs.docker.com/engine/install/debian) for details.
<br>For more information see [here](../../README.md#Troubleshooting)

Extra parameters:
- Device: name of your phone in the Eufy app, used to connect the Eufy Cloud server
- User and password: it's strongly advised to create a dedicated user

Post-installation setup:

- Install/uninstall Eufy: install/uninstall `eufy-security-ws` image
- Start/stop Eufy: start/stop the `eufy-security-ws` container

#### 3. Local mode using the command line (expert)
Once the dependencies are installed you can also use the `eufy` script found in `resources` to install,check 
and manage the `eufy-security-ws` image from the command line:

`eufy start|stop|restart|status|info|test|logs` 

#### 4. Remote mode (expert)

Here are the files to adapt and copy on the remote docker:

```
resources/docker-compose.yaml
resources/eufy
```

####  5. Connectivity issues
If something goes wrong first run the `eufy` script available in `resources`:
<br>`eufy test`
<br>Or:
<br>`python3 resources/test_eufy.py -u server:port`
<br>
<br> you should get something like this:

```
{"type":"result","success":true,"result":{"state":{"driver":{"version":"2.4.0","connected":true,"pushConnected":true}
```

Note that `connected` and `pushConnected` need to be `true`

### Synchronization
![Configuration](../images/eufy2.png)

Note: for now only some devices have been tested. If your device is not supported you can send me the output 
from the `test_eufy.py` program. Please see [here](../../README.md#Tested) for details.

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
The RTSP stream can be accessed with the [Camera](https://doc.jeedom.com/en_US/plugins/security/camera) plugin or any player such as VLC.
<br>On battery-powered cams it needs to be activated/deactivated using the `start_rtsp` and `stop_rtsp` equipment commands.
<br>Depending on devices login/passwd is either the Eufy account id or random generated by the application on the
`Parameters > General > Storage > NAS(RTSP)` page
<br>IP is either the one from the base or from the camera 
<br>`live0...liven` indicates the camera number, see the screenshot below:

![RTSP video stream](../images/camera_plugin.jpg)

<br>Check the URL  depending your model [here](https://camlytics.com/camera/eufy) and [here](https://support.eufy.com/s/article/Using-NAS-Storage-Step-by-Step)

### Bugs and troubleshooting
See [here](../../README.md#Troubleshooting)
