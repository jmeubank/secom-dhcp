<?php
$this->load->helper('ip');
$is_ipv6 = (boolean)($subnet['address'][0] == '1');
$saddr = bitcard2addrmask(substr($subnet['address'], 1), $is_ipv6);
?>

<table cellpadding="0" cellspacing="0">
  <tr>
    <td valign="top">

<div style="width:485px;margin:5px">
  <div title="Subnet Configuration" data-dojo-type="dijit/TitlePane">
    <div style="margin:0 0 10px 0">
      <span style="font-weight:bold">SNMP Instance:</span>
      <?php echo $subnet['snmp_instance']; ?>
    </div>
    <div>
      <button data-dojo-type="dijit/form/Button" type="button">
        Delete subnet...
        <script type="dojo/on" data-dojo-event="click">
          require(['secomdhcp/webapp'], function(webapp){
            webapp.AreYouSureDialog("Are you sure you want to delete subnet <?php echo int2ip($saddr[0], $is_ipv6); ?>?",
            'Really delete?').then(function(okayed){
              if (okayed) {
                webapp.XhrJsonPost('/appdata/subnet/delete', {content: {address: '<?php echo $subnet['address']; ?>'}})
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
    <div id="subnet_config_form" data-dojo-type="dijit/form/Form" method="post"
    action="" style="width:100%;height:248px;overflow:auto;margin:15px 0 0 0">
      <input type="hidden" name="subnet_address" value="<?php echo $subnet['address']; ?>" />
      <div style="font-weight:bold">Gateway IP</div>
      <div><div name="gateway" data-dojo-type="dijit/form/ValidationTextBox"
      data-dojo-props="trim: 'true', maxLength: 39"
      value="<?php echo $subnet['gateway']; ?>"></div></div>
      <div style="font-weight:bold">Additional subnet-level config</div>
      <div>
        <textarea name="addl_config" data-dojo-type="dijit/form/Textarea"
        style="width:100%"><?php echo $subnet['addl_config']; ?></textarea>
      </div>
      <div style="font-weight:bold">Additional pool-level config</div>
      <div>
        <textarea name="addl_pool_config" data-dojo-type="dijit/form/Textarea"
        style="width:100%"><?php echo $subnet['addl_pool_config']; ?></textarea>
      </div>
      <div>
        <button id="btn_subnet_update" data-dojo-type="dijit/form/Button"
        type="submit">Save</button>
      </div>
      <script type="dojo/on" data-dojo-event="submit" data-dojo-args="e">
        e.preventDefault();
        require(['dijit/registry', 'secomdhcp/webapp'], function(registry, webapp){
          if (registry.byId('subnet_config_form').isValid()) {
            registry.byId('btn_subnet_update').attr('disabled', true);
            webapp.XhrJsonPost('/appdata/subnet/update', {form: 'subnet_config_form'})
            .then(function(data){
              registry.byId('btn_subnet_update').attr('disabled', false);
            }, function(error){
              registry.byId('btn_subnet_update').attr('disabled', false);
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
  <div title="New Pool" data-dojo-type="dijit/TitlePane">
    <div id="new_pool_form" data-dojo-type="dijit/form/Form" method="post" action="">
      <input type="hidden" name="subnet_address" value="<?php echo $subnet['address']; ?>" />
      <div style="font-weight:bold">Pool start IP</div>
      <div><div id="txt_pool_start" name="start" data-dojo-type="dijit/form/ValidationTextBox"
      data-dojo-props="required: 'true', trim: 'true', maxLength: 39"></div></div>
      <div style="font-weight:bold">Pool end IP</div>
      <div><div id="txt_pool_end" name="end" data-dojo-type="dijit/form/ValidationTextBox"
      data-dojo-props="required: 'true', trim: 'true', maxLength: 39"></div></div>
      <button id="btn_pool_create" data-dojo-type="dijit/form/Button" type="submit">Create</button>
      <script type="dojo/on" data-dojo-event="submit" data-dojo-args="e">
        e.preventDefault();
        require(['dijit/registry', 'secomdhcp/webapp'], function(registry, webapp){
          if (registry.byId('new_pool_form').isValid()) {
            registry.byId('btn_pool_create').attr('disabled', true);
            webapp.XhrJsonPost('/appdata/pool/create', {form: 'new_pool_form'})
            .then(function(data){
              webapp.RefreshNetworkTree();
              webapp.Navigate('subnet-<?php echo substr($subnet['address'], 0, strlen($subnet['address']) - 1); ?>');
            }, function(error){
              registry.byId('btn_pool_create').attr('disabled', false);
            });
          }
        });
      </script>
    </div>
  </div>
</div>

<div style="width:235px;margin:10px 5px">
  <div title="New Static Host" data-dojo-type="dijit/TitlePane">
    <div id="new_fixed_form" data-dojo-type="dijit/form/Form" method="post" action="">
      <input type="hidden" name="subnet_address" value="<?php echo $subnet['address']; ?>" />
      <div style="font-weight:bold">IP address</div>
      <div><div id="txt_fixed_ip" name="ip" data-dojo-type="dijit/form/ValidationTextBox"
      data-dojo-props="required: 'true', trim: 'true', maxLength: 39"></div></div>
      <div style="font-weight:bold">MAC address</div>
      <div><div id="txt_fixed_mac" name="mac" data-dojo-type="dijit/form/ValidationTextBox"
      data-dojo-props="required: 'true', trim: 'true', maxLength: 17"></div></div>
      <div style="font-weight:bold">Name</div>
      <div><div id="txt_fixed_name" name="name" data-dojo-type="dijit/form/ValidationTextBox"
      data-dojo-props="required: 'true', trim: 'true', maxLength: 254"></div></div>
      <button id="btn_fixed_create" data-dojo-type="dijit/form/Button" type="submit">Create</button>
      <script type="dojo/on" data-dojo-event="submit" data-dojo-args="e">
        e.preventDefault();
        require(['dijit/registry', 'secomdhcp/webapp'], function(registry, webapp){
          if (registry.byId('new_fixed_form').isValid()) {
            registry.byId('btn_fixed_create').attr('disabled', true);
            webapp.XhrJsonPost('/appdata/fixed/create', {form: 'new_fixed_form'})
            .then(function(data){
              webapp.RefreshNetworkTree();
              webapp.Navigate('subnet-<?php echo substr($subnet['address'], 0, strlen($subnet['address']) - 1); ?>');
            }, function(error){
              registry.byId('btn_fixed_create').attr('disabled', false);
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
