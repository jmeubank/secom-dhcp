<?php
$this->load->helper('ip');
$is_ipv6 = (boolean)($fixed['ip'][0] == '1');
$addr = bitcard2addrmask(substr($fixed['ip'], 1), $is_ipv6);
?>

<div style="width:235px">
  <div title="Static Host Configuration" data-dojo-type="dijit/TitlePane">
    <div>
      <button data-dojo-type="dijit/form/Button" type="button">
        Delete static host...
        <script type="dojo/on" data-dojo-event="click">
          require(['secomdhcp/webapp'], function(webapp){
            webapp.AreYouSureDialog("Are you sure you want to delete static host " +
            "<?php echo int2ip($addr[0], $is_ipv6); ?>?",
            'Really delete?').then(function(okayed){
              if (okayed) {
                webapp.XhrJsonPost('/appdata/fixed/delete', {content: {ip: '<?php echo $fixed['ip']; ?>'}})
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
    <div id="fixed_config_form" data-dojo-type="dijit/form/Form" method="post"
    action="" style="margin:15px 0 0 0">
      <input type="hidden" name="ip" value="<?php echo $fixed['ip']; ?>" />
      <div style="font-weight:bold">IP address:</div>
      <div><?php echo int2ip($addr[0], $is_ipv6); ?></div>
      <div style="font-weight:bold;margin:8px 0 0 0">MAC address:</div>
      <div><div name="mac" data-dojo-type="dijit/form/ValidationTextBox"
      data-dojo-props="required: 'true', trim: 'true'"
      value="<?php echo $fixed['mac']; ?>"></div></div>
      <div style="font-weight:bold;margin:8px 0 0 0">Name:</div>
      <div><div name="name" data-dojo-type="dijit/form/ValidationTextBox"
      data-dojo-props="required: 'true', trim: 'true'"
      value="<?php echo $fixed['name']; ?>"></div></div>
      <div style="margin:8px 0 0 0">
        <button id="btn_fixed_update" data-dojo-type="dijit/form/Button"
        type="submit">Save</button>
      </div>
      <script type="dojo/on" data-dojo-event="submit" data-dojo-args="e">
        e.preventDefault();
        require(['dijit/registry', 'secomdhcp/webapp'], function(registry, webapp){
          if (registry.byId('fixed_config_form').isValid()) {
            registry.byId('btn_fixed_update').attr('disabled', true);
            webapp.XhrJsonPost('/appdata/fixed/update', {form: 'fixed_config_form'})
            .then(function(data){
              webapp.RefreshNetworkTree();
              webapp.Navigate('fixed-<?php echo $fixed['ip']; ?>');
            }, function(error){
              registry.byId('btn_fixed_update').attr('disabled', false);
            });
          }
        });
      </script>
    </div>
  </div>
</div>
