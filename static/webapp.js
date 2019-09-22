// secomdhcp/webapp
define([
  'dojo/_base/lang',
  'dojo/_base/xhr',
  'dojo/Deferred',
  'dojo/data/ItemFileReadStore',
  'dojo/data/ItemFileWriteStore',
  'dojo/dom',
  'dojo/dom-style',
  'dijit/registry',
  'dijit/Tree',
  'dijit/tree/ForestStoreModel',
  'dojox/widget/Standby'
], function(lang, xhr, Deferred, ItemFileReadStore, ItemFileWriteStore, dom,
            domStyle, registry, Tree, ForestStoreModel, StandbyWidget,
            NetworkStore){
  var standby_w = null;

  var ShowStandby = function(standby_target){
    if (standby_w)
      standby_w.destroyRecursive();
    standby_w = new StandbyWidget({
      target: standby_target,
      zIndex: 999,
      duration: 250
    });
    document.body.appendChild(standby_w.domNode);
    standby_w.startup();
    standby_w.show();
  };
  var HideStandby = function(){
    if (standby_w)
        standby_w.hide();
  };

  var HandleJsonResult = function(data){
    HideStandby();
    if (data && data.error) {
      alert('' + data.error);
      throw new Error(data.error);
    } else
      return data;
  };
  var HandleJsonError = function(error){
    HideStandby();
    if (typeof(error) == 'string')
      alert(error);
    else {
      var msg = '';
      for (var i in error) {
        if (error[i])
          msg += '' + i + ': ' + error[i] + "\n";
      }
      alert(msg);
    }
    throw error;
  };
  var XhrJsonGeneric = function(data_func, u, options, standby_target){
    if (standby_target)
      ShowStandby(standby_target);
    if (!options)
      options = new Object();
    options.url = u;
    options.handleAs = 'json';
    var p = data_func(options);
    return p.then(HandleJsonResult, HandleJsonError);
  };

  var fayecon = null;
  if (typeof Faye == "undefined")
    alert("Warning: Can't connect to server for realtime updates!");
  else {
    fayecon = new Faye.Client('http://rt.dhcp.secom.net:8003/faye');
    fayecon.errback(function(err){
      alert('Secom-DHCP realtime error: ' + err.message);
    });
  }
  fayecon.subscribe('/base', function(msg){
    if ("dbchanged" in msg) {
      if (msg.dbchanged) {
        domStyle.set('config_status_unsync', 'visibility', 'visible');
        domStyle.set('config_status_sync', 'visibility', 'hidden');
      } else {
        domStyle.set('config_status_unsync', 'visibility', 'hidden');
        domStyle.set('config_status_sync', 'visibility', 'visible');
      }
    }
  });

  return {
    syslog_frozen: false,
    faye_connection: fayecon,

    XhrJsonGet: function(u, options, standby_target){
      return XhrJsonGeneric(lang.hitch(xhr, xhr.get), u, options, standby_target);
    },
    XhrJsonPost: function(u, options, standby_target){
      return XhrJsonGeneric(lang.hitch(xhr, xhr.post), u, options, standby_target);
    },

    AreYouSureDialog: function(text, title){
      var dlg_areyousure = registry.byId('dlg_areyousure');
      if (title)
        dlg_areyousure.set('title', title);
      dom.byId('dlg_areyousure_text').innerHTML = text;
      dlg_areyousure.cb_def = new Deferred();
      dlg_areyousure.show();
      return dlg_areyousure.cb_def;
    },

    LoadLevel: function(id){
      var istr = id.toString();
      this.LoadCurrentLeasesGrid(id);
      if (istr == 'network_root')
        registry.byId('tab_config').set('href', '/page/config_server');
      else if (istr.substr(0, 8) == 'network-')
        registry.byId('tab_config').set('href', '/page/config_network/' + istr.substr(8));
      else if (istr.substr(0, 7) == 'subnet-')
        registry.byId('tab_config').set('href', '/page/config_subnet/' + istr.substr(7));
      else if (istr.substr(0, 5) == 'pool-')
        registry.byId('tab_config').set('href', '/page/config_pool/' + istr.substr(5));
      else if (istr.substr(0, 6) == 'fixed-')
        registry.byId('tab_config').set('href', '/page/config_fixed/' + istr.substr(6));
    },

    Navigate: function(id){
      this.XhrJsonGet('/appdata/navpath/' + encodeURIComponent(id), {preventCache: true})
      .then(lang.hitch(this, function(data){
        registry.byId('network_tree').attr('path', data.ids);
        this.LoadLevel(id);
      }));
    },

    LoadNetworkTree: function(cb){
      var me = this;
      require(['secomdhcp/NetworkStore'], function(NetworkStore){
        var network_tree_div = dom.byId('network_tree_div');
        if (network_tree_div.tree)
          network_tree_div.tree.destroy(true);
        if (network_tree_div.model)
          network_tree_div.model.destroy();
        if (!network_tree_div.store) {
          //network_tree_div.store = new ItemFileReadStore({
            //url: '/appdata/networktree',
            //urlPreventCache: true,
            //clearOnClose: true
          //});
          network_tree_div.store = new NetworkStore();
        }
        network_tree_div.model = new ForestStoreModel({
          store: network_tree_div.store,
          rootId: 'network_root',
          rootLabel: 'Server: secom-dhcp-pair',
          //deferItemLoadingUntilExpand: true,
          //query: 'network_root',
          //childrenAttrs: ['children']
        });
        network_tree_div.tree = new Tree({
          id: 'network_tree',
          model: network_tree_div.model,
          showRoot: true
        });
        network_tree_div.a_item = null;
        network_tree_div.appendChild(network_tree_div.tree.domNode);
        network_tree_div.tree.on('click', lang.hitch(this, function(item){
          network_tree_div.a_item = item;
          me.LoadLevel(item.id);
        }));
        if (cb)
          cb();
      });
    },

    RefreshNetworkTree: function(){
      var network_tree_div = dom.byId('network_tree_div');
      network_tree_div.store.close();
      this.LoadNetworkTree(function(){
        network_tree_div.a_item = null;
      });
    },

    LoadCurrentLeasesGrid: function(obj_id){
      var current_leases_grid_div = dom.byId('current_leases_grid_div');
      if (current_leases_grid_div.sd_store) {
        current_leases_grid_div.sd_store.close();
        current_leases_grid_div.sd_store.url = '/appdata/currentleases/' + obj_id;
      } else {
        current_leases_grid_div.sd_store = new ItemFileWriteStore({
          url: '/appdata/currentleases/' + obj_id,
          urlPreventCache: true,
          clearOnClose: true
        });
      }
      if (current_leases_grid_div.sd_grid)
        current_leases_grid_div.sd_grid.setStore(current_leases_grid_div.sd_store);
      else {
        var st = [
          {field: 'ip', name: 'IP', width: '200px'},
          {field: 'mac', name: 'MAC', width: '120px'},
          {field: 'hostname', name: 'Hostname', width: '200px'},
          {field: 'begin', name: 'Start', width: '150px'}
        ];
        current_leases_grid_div.sd_grid = new dojox.grid.EnhancedGrid({
          query: {ip: '*'},
          store: current_leases_grid_div.sd_store,
          clientSort: true,
          structure: st,
          plugins: {
            pagination: true
          }
        }, document.createElement('div'));
        current_leases_grid_div.appendChild(current_leases_grid_div.sd_grid.domNode);
        current_leases_grid_div.sd_grid.startup();
      }
    }
  };
});
