<div style="width:190px">
  <div title="New Network" data-dojo-type="dijit/TitlePane">
    <div id="new_network_form" data-dojo-type="dijit/form/Form" method="post" action="">
      <div style="font-weight:bold">Name</div>
      <div><div name="name" data-dojo-type="dijit/form/ValidationTextBox"
      data-dojo-props="required: 'true', trim: 'true', maxLength: 254"></div></div>
      <button id="btn_network_create" data-dojo-type="dijit/form/Button"
      type="submit">Create</button>
      <script type="dojo/on" data-dojo-event="submit" data-dojo-args="e">
        e.preventDefault();
        require(['dijit/registry', 'secomdhcp/webapp'], function(registry, webapp){
          if (registry.byId('new_network_form').isValid()) {
            registry.byId('btn_network_create').attr('disabled', true);
            webapp.XhrJsonPost('/appdata/network/create', {form: 'new_network_form'})
            .then(function(data){
              webapp.RefreshNetworkTree();
              webapp.Navigate(data.obj_id);
            }, function(error){
              registry.byId('btn_network_create').attr('disabled', false);
            });
          }
        });
      </script>
    </div>
  </div>
</div>
