<table cellpadding="0" cellspacing="0">
  <tr>
    <td valign="top">

<div style="width:235px;margin:5px">
  <div title="Network Configuration" data-dojo-type="dijit/TitlePane">
    <div>
      <button data-dojo-type="dijit/form/Button" type="button">
        Delete network...
        <script type="dojo/on" data-dojo-event="click">
          require(['secomdhcp/webapp'], function(webapp){
            webapp.AreYouSureDialog("Are you sure you want to delete network '<?php echo $network['name']; ?>'?",
            'Really delete?').then(function(okayed){
              if (okayed) {
                webapp.XhrJsonPost('/appdata/network/delete', {content: {id: <?php echo $network['id']; ?>}})
                .then(function(){
                  webapp.RefreshNetworkTree();
                  webapp.Navigate('network_root');
                });
              }
            });
          });
        </script>
      </button>
    </div>
    <div id="network_config_form" data-dojo-type="dijit/form/Form" method="post"
    action="" style="margin:15px 0 0 0">
      <input type="hidden" name="network_id" value="<?php echo $network['id']; ?>" />
      <div style="font-weight:bold">Name:</div>
      <div><div name="name" data-dojo-type="dijit/form/ValidationTextBox"
      data-dojo-props="required: 'true', trim: 'true', maxLength: 254"
      value="<?php echo $network['name']; ?>"></div></div>
      <div style="margin:8px 0 0 0">
        <button id="btn_network_update" data-dojo-type="dijit/form/Button"
        type="submit">Save</button>
      </div>
      <script type="dojo/on" data-dojo-event="submit" data-dojo-args="e">
        e.preventDefault();
        require(['dijit/registry', 'secomdhcp/webapp'], function(registry, webapp){
          if (registry.byId('network_config_form').isValid()) {
            registry.byId('btn_network_update').attr('disabled', true);
            webapp.XhrJsonPost('/appdata/network/update', {form: 'network_config_form'})
            .then(function(data){
              webapp.RefreshNetworkTree();
              webapp.Navigate('network_root');
            }, function(error){
              registry.byId('btn_network_update').attr('disabled', false);
            });
          }
        });
      </script>
    </div>
  </div>
</div>

    </td>
    <td valign="top">

<div style="width:235px;margin:5px">
  <div title="New Subnet" data-dojo-type="dijit/TitlePane">
    <div id="new_subnet_form" data-dojo-type="dijit/form/Form" method="post" action="">
      <input type="hidden" name="network_id" value="<?php echo $network['id']; ?>" />
      <div style="font-weight:bold">Network address</div>
      <div>
        <div name="address" data-dojo-type="dijit/form/ValidationTextBox"
        data-dojo-props="required: 'true', trim: 'true', maxLength: 39"></div>
        /
        <div name="slash" data-dojo-type="dijit/form/ValidationTextBox"
        data-dojo-props="required: 'true', trim: 'true', regExp: '[0-9]{1,3}', maxLength: 3"
        style="width:30px"></div>
      </div>
      <button id="btn_subnet_create" data-dojo-type="dijit/form/Button" type="submit">Create</button>
      <script type="dojo/on" data-dojo-event="submit" data-dojo-args="e">
        e.preventDefault();
        require(['dijit/registry', 'secomdhcp/webapp'], function(registry, webapp){
          if (registry.byId('new_subnet_form').isValid()) {
            registry.byId('btn_subnet_create').attr('disabled', true);
            webapp.XhrJsonPost('/appdata/subnet/create', {form: 'new_subnet_form'})
            .then(function(data){
              webapp.RefreshNetworkTree();
              webapp.Navigate(data.obj_id);
            }, function(error){
              registry.byId('btn_subnet_create').attr('disabled', false);
            });
          }
        });
      </script>
    </div>
  </div>
</div>

    </td>
  </tr>
</table>
