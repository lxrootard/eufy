# Jeedom Plugin pour WebCams/Stations Eufy

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
<br>Note: L'installation des dépendances n'installe PAS l'image `eufy-security-ws`.
<br>Vous avez le choix entre les modes local et distant pour docker:
#### 1. Mode local
L'installation du mode local a pour prérequis docker déjà installé et configuré

- Installer/désinstaller Eufy: installer/désinstaller l'image `eufy-security-ws`
- Démarrer/arrêter Eufy: démarrer/arrêter le container `eufy-security-ws`
- Device: paramètre Eufy-WS `TRUSTED_DEVICE_NAME` utilisé pour se connecter au serveur Cloud Eufy
- User/password: identifiants du service Cloud `Eufy-WS`

#### 2. Mode distant
Le container `eufy-security-ws` doit déjà être installé.
<br>Vous pouvez copier et utiliser le script `resources/eufyctl.sh` pour installer et tester manuellement l'image `eufy-security-ws` sur un serveur distant:

`eufyctl.sh install|uninstall|status|test|stop|start <device> <login> <passwd> [ port ]`
 
####  3. Paramètres communs
- IP Docker: adresse IP du container `eufy-security-ws`, 127.0.0.1 par défaut
- Port Docker: port du container `eufy-security-ws`, 3000 par défaut
- Tester: Vérifier la présence du container `eufy-security-ws` et sa connexion au service Cloud Eufy

Note: Le daemon Eufy ne démarrera pas si le container `eufy-security-ws` ne peut pas se connecter au service Cloud Eufy

####  4. Soucis de connexion
En cas de problème vérifier la connexion avec la commande suivante:
<br>`resources/eufyctl.sh test`
<br>
<br> Vous devriez obtenir l'output suivant:

```
{"type":"result","success":true,"result":{"state":{"driver":{"version":"2.4.0","connected":true,"pushConnected":true}
```

Note: `connected` et `pushConnected` doivent être à `true`
<br>Voir également la log `eufy_service_setup` 

### Synchronisation
![Configuration](../images/eufy2.png)

Note: pour l'instant seule la base T8010 et les webcams T8113 et T8114 ont été testés, les autres modèles peuvent être reconnus ou fonctionner partiellement.
<br>Si votre modèle n'est pas dans cette liste merci de m'envoyer les résultats de la commande `python3 resources/test_eufy.py <device_id>`

### Santé
![Configuration](../images/eufy1.png)

Liste et statut des devices reconnus 

### Equipements
![Equipments](../images/eufy4.png)

### Bugs et dépannage
Voir Jeedom community [ici](https://community.jeedom.com/t/integration-de-materiel-eufy/76603)
