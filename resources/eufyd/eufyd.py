import asyncio
import json
import logging
import os
import websockets


from jeedomdaemon.base_daemon import BaseDaemon
from jeedomdaemon.base_config import BaseConfig

class EufyConfig(BaseConfig):
    def __init__(self):
        super().__init__()
        self.add_argument("--host", type=str, default='localhost')
        self.add_argument("--port", type=int, default=3000)
        self.add_argument("--schemaversion", type=str)


class Eufyd(BaseDaemon):

    def __init__(self) -> None:
        super().__init__(config=EufyConfig(),
		on_start_cb=self.on_start,
		on_message_cb=self.on_message,
		on_stop_cb=self.on_stop)
        self._websocket = None
        self._url = "ws://" + str(self._config.host) + ":" + str(self._config.port) + "/"


    async def on_start(self):
        logging.info("[on_start] args: " + str(vars(self._config._args)))
        logging.getLogger("websockets").setLevel(logging.WARNING)
        try:
             async with websockets.connect(self._url) as ws:
                  logging.info('Eufy Websocket connected')
#                  await ws.send(json.dumps({"command": "driver.set_log_level", "level": self._config.loglevel}))
#                  await asyncio.sleep(0.3)
                  await ws.send(json.dumps({"command": "start_listening"}))
                  await asyncio.sleep(0.3)
                  await ws.send(json.dumps({"command": "set_api_schema", "schemaVersion": self._config.schemaversion}))
                  await asyncio.sleep(0.3)
                  async for msg in ws:
                       await self.upd_jeedom(msg)
                       # logging.info("> received msg: " + msg)
        except Exception as err:
             logging.exception('[on_start] unexpected error from eufy-security-ws: '  + type(err).__name__ + ": " + str(err))


    async def on_message(self, message: dict):
        logging.debug('[on_message] sent: ' + str(message))
        try:
            async with websockets.connect(self._url) as ws:
                 await ws.send(json.dumps(message))
                 await asyncio.sleep(0.3)
                 async for msg in ws:
                     await self.upd_jeedom(msg)
                     # logging.info("> received msg: " + msg)

        except Exception as err:
            logging.exception("[on_message] error sending message: " + type(err).__name__+ ": " + str(err))

    async def on_stop(self):
        logging.debug("Shutdown")
        try:
            self._websocket.cancel()
        except Exception as e:
            logging.warning('[on_stop] error closing websocket: %s', e)
        logging.debug("Exit 0")
        os._exit(0)


    async def upd_jeedom(self, msg: dict):
        logging.info("[upd_jeedom] received msg: " + msg)
        jsonMsg = json.loads(msg)

       	if jsonMsg['type'] == 'version':
            logging.debug('Version message received')

        if jsonMsg['type'] == 'result':
            logging.debug('Result message received')
            if (not jsonMsg['success']):
                logging.warning('[upd_jeedom] unexpected error: ' + jsonMsg['messageId']+ ': ' + jsonMsg['errorCode'])
            await self.send_to_jeedom(jsonMsg)
            await asyncio.sleep(0.3)

        if jsonMsg['type'] == 'event':
            logging.debug('Event message received')
            await self.send_to_jeedom(jsonMsg)
            await asyncio.sleep(0.3)

###########

Eufyd().run()
