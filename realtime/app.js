var express = require('express'),
    faye = require('./faye/node/faye-node'),
    http = require('http'),
    util = require('util'),
    daemon = null;

if (!process.argv || process.argv.length < 3 || process.argv[2] != 'nodaemon')
  daemon = require('daemon');

var byx = new faye.NodeAdapter({mount: '/faye'});

var app = express();
app.use(express.bodyParser());
app.post('/', function(req, res){
  var payload = JSON.parse(req.body.payload);
  if (!daemon)
    util.log(util.inspect(payload));
  byx.getClient().publish(req.body.channel_name, payload);
  res.end();
  if (!daemon)
    util.log('Published to ' + req.body.channel_name);
});

var http_server = http.createServer(app);
byx.attach(http_server);
http_server.listen(8003);

if (daemon) {
  daemon.daemonize(__dirname + '/realtime.log', __dirname + '/realtime.pid', function(err, pid){
    if (err)
      return util.log('Error starting realtime daemon: ' + err);
    util.log('Realtime daemon successfully started with pid: ' + pid);
  });
}
