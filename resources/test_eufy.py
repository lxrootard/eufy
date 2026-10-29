import json
import argparse
from websockets.sync import client

argParser = argparse.ArgumentParser()
argParser.add_argument("-d", "--device", help="device serial number")
argParser.add_argument("-u", "--url", help="eufy service URL, default 127.0.0.1:3000")
argParser.add_argument("-v", "--version",action='store_true',help="check eufy driver version only")
argParser.add_argument("-t", "--test", action='store_true', help="test eufy service status only")

args = argParser.parse_args()
#print("args=%s" % args)
#print("args.device=%s" % args.device)

if args.url:
	url = args.url
else:
	url = '127.0.0.1:3000'

ws = client.connect("ws://" + url)

if args.version:
	print(ws.recv())
	quit()

if args.test:
	ws.recv()
else:
	print ('\n*** Create connexion to ' + url + ' ***')
	print(ws.recv())
	print ('\n*** Start listening ***')

ws.send(json.dumps({"command": "start_listening"}))
print(ws.recv())

if args.device:

	print ('\n*** Station commands ***')
	ws.send(json.dumps({"command": "station.get_properties","serialNumber": args.device}))
	print(ws.recv())

	print ('\n*** Device commands: ***')
	ws.send(json.dumps({"command": "device.get_properties","serialNumber": args.device}))
	print(ws.recv())

	print ('\n*** Station metadata ***')
	ws.send(json.dumps({"command": "station.get_properties_metadata","serialNumber": args.device}))
	print(ws.recv())

	print ('\n*** Device metadata: ***')
	ws.send(json.dumps({"command": "device.get_properties_metadata","serialNumber": args.device}))
	print(ws.recv())

ws.close()
