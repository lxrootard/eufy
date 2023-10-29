import json
import argparse
from websocket import create_connection

argParser = argparse.ArgumentParser()
argParser.add_argument("-s", "--station", help="station serial number")
argParser.add_argument("-d", "--device", help="device serial number")
argParser.add_argument("-u", "--url", help="eufy service URL, default 127.0.0.1:3000")
argParser.add_argument("-n", "--nolog", action='store_true', help="check eufy service status only")

args = argParser.parse_args()
#print("args=%s" % args)

#print("args.station=%s" % args.station)
#print("args.device=%s" % args.device)

if args.url:
	url = args.url
else:
	url = '127.0.0.1:3000'

ws = create_connection("ws://" + url)

if not args.nolog:
	print ('\n*** Create connexion to ' + url + ' ***')
	print(ws.recv())
	print ('\n*** Start listening ***')
else:
	ws.recv()

ws.send(json.dumps({"command": "start_listening"}))
print(ws.recv())

if args.device:
	print ('\n*** Device commands: ***')
	ws.send(json.dumps({"command": "device.get_properties","serialNumber": args.device}))
	print(ws.recv())

	print ('\n*** Device metadata: ***')
	ws.send(json.dumps({"command": "device.get_properties_metadata","serialNumber": args.device}))
	print(ws.recv())

if args.station:
	print ('\n*** Station commands ***')
	ws.send(json.dumps({"command": "station.get_properties","serialNumber": args.station}))
	print(ws.recv())

	print ('\n*** Station metadata ***')
	ws.send(json.dumps({"command": "station.get_properties_metadata","serialNumber": args.station}))
	print(ws.recv())

ws.close()
