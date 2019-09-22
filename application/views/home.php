<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>SECOM DHCP</title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width">
  <!-- Place favicon.ico and apple-touch-icon.png in the root directory: mathiasbynens.be/notes/touch-icons -->
  <link rel="dns-prefetch" href="//rt.dhcp.secom.net">

  <style type="text/css">
    body, html {
      width: 100%;
      height: 100%;
      margin: 0;
      overflow: hidden;
      padding: 0;
    }
    .config_status {
      position: absolute;
      left: 0;
      top: 0;
      width: 348px;
      height: 34px;
      text-align: center;
      padding: 0;
    }
    #config_status_sync {
      background: lightgreen;
      border: 1px solid darkgreen;
    }
    #config_status_unsync {
      background: lightyellow;
      border: 1px solid gold;
    }
    .loading_animation {
      background:
        #fff
        url('/static/images/loading.gif')
        no-repeat center center;
    }
    #preloader {
      width: 100%;
      height: 100%;
      margin: 0;
      padding: 0;
      position: absolute;
      z-index: 950;
    }
  </style>

  <script type="text/javascript">
    var dojoConfig = {
      async: true,
      parseOnLoad: false,
      dojoBlankHtmlUrl: '/static/blank.html',
      paths: {
        'secomdhcp': '/support/staticfile'
      }
    };
  </script>
</head>

<body class="claro">
  <!--[if lt IE 7]><p class=chromeframe>Your browser is <em>ancient!</em> <a href="http://browsehappy.com/">Upgrade to a different browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to experience this site.</p><![endif]-->

  <div id="preloader" class="loading_animation"></div>

  <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/dojo/1.8/dijit/themes/claro/document.css">
  <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/dojo/1.8/dijit/themes/claro/claro.css">
  <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/dojo/1.8/dojox/grid/resources/Grid.css">
  <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/dojo/1.8/dojox/grid/resources/claroGrid.css">
  <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/dojo/1.8/dojox/grid/enhanced/resources/claro/EnhancedGrid.css">

  <!-- Grab Google CDN's Dojo, with a protocol relative URL; fall back to local if offline -->
  <script src="//ajax.googleapis.com/ajax/libs/dojo/1.8/dojo/dojo.js"></script>
  <script src="http://rt.dhcp.secom.net:8003/faye.js"></script>

  <div data-dojo-type="dijit/layout/BorderContainer"
  data-dojo-props="design: 'headline'" style="width:100%;height:100%">
    <div data-dojo-type="dijit/layout/ContentPane" data-dojo-props="region: 'top'"
    style="height:36px;border:0;padding:0">
      <div id="config_status_sync" class="config_status" style="visibility:hidden">
        <div style="margin:9px 0">Config synchronized</div>
      </div>
      <div id="config_status_unsync" class="config_status" style="visibility:hidden">
        Config changed:
        <button data-dojo-type="dijit/form/Button" style="margin:5px">
          Commit
          <script type="dojo/on" data-dojo-event="click">
            require(['dojo/dom', 'dojo/dom-construct', 'dijit/registry', 'secomdhcp/webapp'],
            function(dom, domConstruct, registry, webapp){
              dom.byId('task_messages_div').innerHTML = '';
              webapp.faye_connection.subscribe('/tasks', function(msg){
                var tmd = dom.byId('task_messages_div');
                if (tmd) {
                  domConstruct.place('<div><nobr>' + msg.message + '</nobr></div>', tmd,
                  'last');
                }
                if (msg.status != 0)
                  registry.byId('btn_taskdlg_close').set('label', 'Done');
              });
              webapp.XhrJsonPost('/appdata/config/commit').then(function(){
                registry.byId('dlg_task').show();
              });
            });
          </script>
        </button>
      </div>
      <script type="text/javascript">
        require(['dojo/dom-style', 'dojo/domReady!'], function(domStyle){
<?php
if ($dbchanged) {
?>
          domStyle.set('config_status_unsync', 'visibility', 'visible');
<?php
} else {
?>
          domStyle.set('config_status_sync', 'visibility', 'visible');
<?php
}
?>
        });
      </script>
      <div data-dojo-type="dijit/MenuBar" style="position:absolute;left:355px;top:0;right:0">
        <div data-dojo-type="dijit/MenuBarItem">
          Syslog...
          <script type="dojo/on" data-dojo-event="click">
            require(['dijit/registry'], function(registry){
              registry.byId('dlg_syslog').show();
            });
          </script>
        </div>
      </div>
    </div>
    <div data-dojo-type="dijit/layout/TabContainer"
    data-dojo-props="region: 'leading', splitter: 'true'" style="width:350px">
      <div title="Networks" data-dojo-type="dijit/layout/ContentPane" style="padding:0">
        <div id="network_tree_div"></div>
        <script type="text/javascript">
          require(['secomdhcp/webapp', 'dojo/domReady!'], function(webapp){
            webapp.LoadNetworkTree();
          });
        </script>
      </div>
      <div title="History" data-dojo-type="dijit/layout/ContentPane">
        <div id="hist_search_form" data-dojo-type="dijit/form/Form" method="get" action="">
          Search by:
          <select id="hist_searchby" data-dojo-type="dijit/form/Select">
            <option value="ip">IP address</option>
            <option value="mac">MAC address</option>
            <option value="hostname">Hostname</option>
          </select>
          <div id="hist_term" data-dojo-type="dijit/form/TextBox" required="true"
          trim="true" style="width:150px"></div>
          <button id="btn_hist_search" data-dojo-type="dijit/form/Button" type="submit">Search</button>
          <script type="dojo/on" data-dojo-event="submit" data-dojo-args="e">
            e.preventDefault();
            require(['dijit/registry', 'dijit/layout/ContentPane'],
            function(registry, ContentPane){
              registry.byId('btn_hist_search').attr('disabled', true);
              if (registry.byId('hist_search_form').isValid()) {
                var searchby = registry.byId('hist_searchby').attr('value');
                var term = registry.byId('hist_term').attr('value');
                var pane = new ContentPane({
                  title: 'History: ' + term,
                  href: '/page/history?searchby=' + searchby + '&term=' + encodeURIComponent(term),
                  closable: true
                });
                registry.byId('centertabs').addChild(pane);
                registry.byId('centertabs').selectChild(pane);
                pane.onLoadDeferred.then(function(){
                  hist_grid.setStore(hist_store);
                  registry.byId('btn_hist_search').attr('disabled', false);
                });
              }
            });
          </script>
        </div>
      </div>
    </div>
    <div id="centertabs" data-dojo-type="dijit/layout/TabContainer"
    data-dojo-props="region: 'center'">
      <div title="Current Leases" data-dojo-type="dijit/layout/ContentPane">
        <div id="current_leases_grid_div" style="width:100%;height:100%"></div>
      </div>
      <div id="tab_config" title="Configuration"
      data-dojo-type="dojox.layout.ContentPane"></div>
    </div>
  </div>

<?php
$this->load->view('dlg_areyousure');
$this->load->view('dlg_syslog');
$this->load->view('dlg_task');
?>

  <script>
    require([
      'dojo/parser',
      'dojo/data/ItemFileReadStore',
      'dijit/layout/BorderContainer',
      'dijit/layout/ContentPane',
      'dijit/layout/TabContainer',
      'dijit/Dialog',
      'dijit/form/Button',
      'dijit/form/Form',
      'dijit/form/Select',
      'dijit/form/Textarea',
      'dijit/form/TextBox',
      'dijit/form/ValidationTextBox',
      'dijit/Menu',
      'dijit/MenuBar',
      'dijit/MenuBarItem',
      'dijit/MenuItem',
      'dijit/PopupMenuBarItem',
      'dijit/TitlePane',
      'dojox/grid/DataGrid',
      'dojox/grid/EnhancedGrid',
      'dojox/grid/enhanced/plugins/Pagination',
      'dojox/layout/ContentPane'
    ], function(parser){
      parser.parse();
      require([
        'dijit/focus',
        'dojo/_base/fx',
        'dojo/dom',
        'dojo/dom-style',
        'dojo/domReady!'
      ], function(focusutil, fx, dom, domStyle){
        //focusutil.focus(dom.byId('device_ip'));
        fx.fadeOut({
          node: 'preloader',
          duration: 250,
          onEnd: function(){
            domStyle.set('preloader', 'display', 'none');
          }
        }).play();
      });
    });
    require(['secomdhcp/webapp']);
  </script>

</body>

</html>
