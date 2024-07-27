# Jeedom Plugin pour Devices/Stations Eufy

![Logo Jeedom](../images/jeedom.png)
![Logo Plugin](../images/eufy.png)

## Documentation
- [Configuration](#configuration)
- [Synchronisation](#synchronisation)
- [Santé](#health)
- [Equipements](#equipments)
- [Bugs et dépannage](#troubleshooting)

### Configuration
![Configuration](../images/eufy3.png)

Installer le plugin et ses dépendances.
<br>Vous avez le choix entre les modes local et distant pour docker.
<br>Ne pas oublier de sauver la configuration AVANT de lancer les dépendances.
<br>Note: L'installation des dépendances n'installe PAS l'image `eufy-security-ws`.

#### 1. Paramètres communs
- IP Docker: adresse IP du container `eufy-security-ws`, `127.0.0.1` par défaut
- Port Docker: port du container `eufy-security-ws`, `3000` par défaut
- Tester: Vérifier la présence du container `eufy-security-ws` et sa connexion au service Cloud Eufy

Notes:
- Le daemon Eufy ne démarrera pas si le container `eufy-security-ws` ne peut pas se connecter au service Cloud Eufy.
- La version du container est indiquée dans le champ `Version`

#### 2. Mode local
L'installation du mode local a pour prérequis `docker` et le plugin `docker compose` déjà installés et configurés.
Si ce n'est pas le cas installer le plugin officiel docker management ou en ligne de commande 
cf le [site officiel](https://docs.docker.com/engine/install/debian). 
Pour plus de détails voir [ici](../../README.md#Troubleshooting).

Paramètres:
- Device: nom de votre téléphone dans l'app Eufy, utilisé pour se connecter au serveur Cloud Eufy
- Utilisateur et mot de passe: il est conseillé de créer un utilisateur dédié

Configuration post-installation:

- Installer/désinstaller Eufy: installer/désinstaller l'image `eufy-security-ws`
- Démarrer/arrêter Eufy: démarrer/arrêter le container `eufy-security-ws`

#### 3. Mode local en ligne de commande (expert)
Une fois les dépendances installées vous pouvez aussi utiliser le script `eufy` disponible dans `resources`
pour installer, vérifier et gérer l'image `eufy-security-ws` en ligne de commande:

`eufy start|stop|restart|status|info|test|logs`

#### 4. Mode distant (expert)

Voici les fichiers à adapter et copier sur le docker distant:
```
resources/docker-compose.yml
resources/eufy
```

####  5. Soucis de connexion
En cas de problème vérifier la connexion avec le container via la commande `eufy` disponible dans `resources`:
<br>`eufy test`
<br>Ou directement:
<br>`python3 resources/test_eufy.py -u server:port`
<br>
<br> Vous devriez obtenir l'output suivant:
```
{"type":"result","success":true,"result":{"state":{"driver":{"version":"2.4.0","connected":true,"pushConnected":true}
```

Note: `connected` et `pushConnected` doivent être à `true`

### Synchronisation
![Configuration](../images/eufy2.png)

Note: pour l'instant seuls certains modèles ont été testés. Si votre modèle n'est pas suporté vous pouvez 
m'envoyer le résutat du programme `test_eufy.py`. Voir [ici](../../README.md#Tested) pour plus d'infos.

### Santé
![Configuration](../images/eufy1.png)

Liste et statut des devices reconnus 

### Equipements
![Equipments](../images/eufy4.png)
![Equipments](../images/eufy5.png)
![Equipments](../images/eufy6.png)

### Snapshots
La commande `URL snaphot` contient l'URL de l'image sur le serveur
<br> Vous pouvez utiliser mon widget [ImageViewer](https://github.com/lxrootard/widgets_v4)
<br> Pensez à activer les notifications d'image dans les paramètres de vos devices depuis l'application Eufy sans quoi les snapshots ne seront pas mis à jour


### Video stream RTSP
Le flux RTSP fonctionne dans le plugin [Camera](https://doc.jeedom.com/fr_FR/plugins/security/camera) ou une application comme VLC
<br>Sur les caméras à batterie il doit être activé/désactivé via les commandes de l'équipement `start_rtsp` et `stop_rtsp`.
<br>Selon les devices le login/passwd est celui du compte Eufy ou celui généré par l'application sur la page
`Paramètres > General > Stockage > NAS(RTSP)`
<br>L'IP est celle de la base ou celle de la caméra 
<br>`live0..liven` indiquent le numéro de la caméra, cf ce screenshot:

![Video stream RTSP](../images/camera_plugin.jpg)

<br>Voir [ici](https://camlytics.com/camera/eufy) et [la](https://support.eufy.com/s/article/Using-NAS-Storage-Step-by-Step) selon votre modèle

### Bugs et dépannage
Voir [ici](../../README.md#Troubleshooting)
