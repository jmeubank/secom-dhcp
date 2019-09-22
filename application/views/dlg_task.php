<div id="dlg_task" data-dojo-type="dijit/Dialog" title="Task Results"
style="display:none;width:600px;height:560px">
  <div style="height:515px;width:100%">
    <div id="task_messages_div"></div>
    <div style="text-align:center">
      <button id="btn_taskdlg_close" data-dojo-type="dijit/form/Button"
      type="button" style="margin:5px">
        Cancel
        <script type="dojo/on" data-dojo-event="click">
          require(['dijit/registry'], function(registry){
            registry.byId('dlg_task').hide();
          });
        </script>
      </button>
    </div>
  </div>
  <script type="dojo/on" data-dojo-event="show">
    require(['dijit/registry'], function(registry){
      registry.byId('btn_taskdlg_close').set('label', 'Cancel');
    });
  </script>
</div>
