import json
import argparse
from websocket import create_connection

argParser = argparse.ArgumentParser()
argParser.add_argument("-s", "--station", help="station serial number")
argParser.add_argument("-w", "--webcam", help="webcam serial number")

args = argParser.parse_args()
#print("args=%s" % args)

#print("args.station=%s" % args.station)
#print("args.webcam=%s" % args.webcam)

print ('\n*** Create connexion ***')
ws = create_connection("ws://127.0.0.1:3000")
print(ws.recv())

print ('\n*** Start listening ***')
ws.send(json.dumps({"command": "start_listening"}))
print(ws.recv())

if args.webcam:
	print ('\n*** WebCam commands: ***')
	ws.send(json.dumps({"command": "device.get_properties","serialNumber": args.webcam}))
	print(ws.recv())

if args.station:
	print ('\n*** Station commands ***')
	ws.send(json.dumps({"command": "station.get_properties","serialNumber": args.station}))
	print(ws.recv())

ws.close()
