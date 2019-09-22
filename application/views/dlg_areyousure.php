<div id="dlg_areyousure" data-dojo-type="dijit/Dialog" title=""
style="display:none;width:300px">
<table>
<tr>
  <td id="dlg_areyousure_text"></td>
</tr>
<tr>
  <td align="center">
    <button data-dojo-type="dijit/form/Button" type="button">
      Yes
      <script type="dojo/on" data-dojo-event="click">
        require(['dijit/registry'], function(registry){
          registry.byId('dlg_areyousure').okayed = true;
          registry.byId('dlg_areyousure').hide();
        });
      </script>
    </button>
    <button data-dojo-type="dijit/form/Button" type="button">
      No
      <script type="dojo/on" data-dojo-event="click">
        require(['dijit/registry'], function(registry){
          registry.byId('dlg_areyousure').hide();
        });
      </script>
    </button>
  </td>
</tr>
</table>
<script type="dojo/on" data-dojo-event="show">
  require(['dijit/registry'], function(registry){
    registry.byId('dlg_areyousure').okayed = false;
  });
</script>
<script type="dojo/on" data-dojo-event="hide">
  require(['dijit/registry'], function(registry){
    var dlg_areyousure = registry.byId('dlg_areyousure');
    if (dlg_areyousure.cb_def) {
      dlg_areyousure.cb_def.resolve(dlg_areyousure.okayed);
      dlg_areyousure.cb_def = null;
    }
  });
</script>
</div>
