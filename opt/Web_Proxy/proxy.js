#!/usr/bin/env node
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
// This software is for use on amateur radio networks only,
// it is to be used for educational purposes only. Its use on
// commercial networks is strictly prohibited.
// Copyright(C) 2020 by Mike Zingman, N4IRR and DVSwitch, INAD.
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

const WebSocket = require('ws');
const fs = require('fs');
var udp = require('dgram');
const { exit } = require('process');

const version = '1.6.0';
const pcm_file = './16bit-8000.raw';
let interval = 0,
    sampleRate = 8000,
    bytePerSample = 2,
    channels = 2,
    bytesChunk = (sampleRate * bytePerSample * channels),
    offset = 0,
    pcmData,
    wss;
var wsPort = 8080, // Proxy lisens for connections on this port.  See index.html socketURL
    pcmPort = 2222; // Match this to AB.ini -> [GENERAL] -> PCMPort

var logStream = fs.createWriteStream('/var/log/dvswitch/proxy.log', {flags: 'a'});

// creating a udp server
var server = udp.createSocket('udp4');

// emits when any error occurs
server.on('error', function(error) {
    Log(1, 'Proxy Error: ' + error);
    server.close();
});

process.on('uncaughtException', function(err) {
    console.error(err.stack);
    console.log("Node NOT Exiting...");
});

// emits on new datagram msg
server.on('message', function(msg, info) {
    wss.clients.forEach(function each(client) {
        if (client.readyState === WebSocket.OPEN) {
            client.send(msg);
        }
    });
});

//emits when socket is ready and listening for datagram msgs
server.on('listening', function() {
    var address = server.address();
    var port = address.port;
    var family = address.family;
    var ipaddr = address.address;
    Log(1, 'PCM client is listening at port : ' + port);
    Log(1, 'Server ip :' + ipaddr);
    Log(1, 'Server is IP4/IP6 : ' + family);
});

//emits after the socket is closed using socket.close();
server.on('close', function() {
    Log(1, 'Socket is closed !');
});

server.on('disconnect', function() {
    Log(1, 'Client disconnected');
});

function openSocket() {
    wss = new WebSocket.Server({
        port: wsPort
    });
    Log(1, `WebSocket server ready on port ${wsPort}...`);
    wss.on('connection', function connection(ws) {
        var addr = ws._socket.remoteAddress;
        Log(1, `Remote client ${ws._socket.remoteAddress} connected. sending data...`);
        logUsers();
        ws.onclose = function(e) {
            Log(1, `Remote client ${addr} disconnected`);
            logUsers();
        };
        ws.on('error', function() {
        });
    });
}

function logUsers() {
    Log(1, "-------- CONNECTIONS --------");
    var count = 0;
    wss.clients.forEach(function each(client) {
        if (client.readyState === WebSocket.OPEN) {
            Log(1, `Connected client: ${client._socket.remoteAddress}`);
            count++;
        }
    });
    if (count == 0)
        Log(1, "No connected clients");
    Log(1, "-----------------------------");
}

function Log(level, message) {
    let date_ob = new Date();
    let date = ("0" + date_ob.getDate()).slice(-2);
    let month = ("0" + (date_ob.getMonth() + 1)).slice(-2);
    let year = ("0" + date_ob.getFullYear()).slice(-2);
    let hours = ("0" + date_ob.getHours()).slice(-2);
    let minutes = ("0" + date_ob.getMinutes()).slice(-2);
    let seconds = ("0" + date_ob.getSeconds()).slice(-2);
    let stamp = year + "-" + month + "-" + date + " " + hours + ":" + minutes + ":" + seconds;
    console.log(`${stamp} - ${message}`);
    logStream.write(`${stamp} - ${message}\n`);
}


var proxyArgs = process.argv.slice(2);
if (proxyArgs.length > 0) {
  if (proxyArgs[0] == "-v") {
      console.log(`Version: ${version}`);
      exit(0);
  }  
  wsPort = proxyArgs[0];
  pcmPort = proxyArgs[1];
}

Log(1, "Websocket Proxy:");
Log(1, "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-");
Log(1, "This software is for use on amateur radio networks only,");
Log(1, "it is to be used for educational purposes only. Its use on");
Log(1, "commercial networks is strictly prohibited.");
Log(1, "Copyright(C) 2020 by Mike Zingman, N4IRR and DVSwitch, INAD.");
Log(1, "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-");
Log(1, `Version: ${version}`);
Log(1, "");

server.bind(pcmPort);
openSocket();
