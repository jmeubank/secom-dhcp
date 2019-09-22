<div id="dlg_syslog" data-dojo-type="dijit/Dialog" title="Syslog"
style="display:none;width:800px;height:560px">
  <div id="dlg_syslog_content" data-dojo-type="dojox/layout/ContentPane"
  data-dojo-props="renderStyles: 'true'"
  style="padding:0;width:780px;height:475px;overflow:auto"></div>
  <div style="text-align:center">
    <button id="btn_freeze_syslog" data-dojo-type="dijit/form/Button"
    type="button" style="margin:5px">
      Freeze
      <script type="dojo/on" data-dojo-event="click">
        require(['dojo/dom', 'dijit/registry', 'secomdhcp/webapp'], function(dom, registry, webapp){
          if (syslog_frozen) {
            registry.byId('btn_freeze_syslog').set('label', 'Freeze');
            dom.byId('syslog_msgs_div_display').innerHTML = dom.byId('syslog_msgs_div').innerHTML;
          } else
            registry.byId('btn_freeze_syslog').set('label', 'Thaw');
          webapp.syslog_frozen = !webapp.syslog_frozen;
        });
      </script>
    </button>
    <button data-dojo-type="dijit/form/Button" type="button" style="margin:5px">
      Close
      <script type="dojo/on" data-dojo-event="click">
        require(['dijit/registry'], function(registry){
          registry.byId('dlg_syslog').hide();
        });
      </script>
    </button>
  </div>
  <script type="dojo/on" data-dojo-event="show">
    require(['dijit/registry'], function(registry){
      registry.byId('dlg_syslog_content').set('href', '/page/syslog');
    });
  </script>
  <script type="dojo/on" data-dojo-event="hide">
    require(['dijit/registry', 'secomdhcp/webapp'], function(registry, webapp){
      if (webapp.syslog_sub) {
        webapp.syslog_sub.cancel();
        delete webapp.syslog_sub;
      }
      registry.byId('dlg_syslog_content').destroyDescendants();
    });
  </script>
</div>
