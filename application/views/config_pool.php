<?php
$this->load->helper('ip');
$is_ipv6 = (boolean)($pool['start'][0] == '1');
$saddr = bitcard2addrmask(substr($pool['start'], 1), $is_ipv6);
$eaddr = bitcard2addrmask(substr($pool['end'], 1), $is_ipv6);
?>

<div style="width:235px">
  <div title="Pool Configuration" data-dojo-type="dijit/TitlePane">
    <div>
      <button data-dojo-type="dijit/form/Button" type="button">
        Delete pool...
        <script type="dojo/on" event="click">
          require(['secomdhcp/webapp'], function(webapp){
            webapp.AreYouSureDialog("Are you sure you want to delete pool " +
            "<?php echo int2ip($saddr[0], $is_ipv6); ?> - " +
            "<?php echo int2ip($eaddr[0], $is_ipv6); ?>?",
            'Really delete?').then(function(okayed){
              if (okayed) {
                webapp.XhrJsonPost('/appdata/pool/delete', {content: {start: '<?php echo $pool['start']; ?>'}})
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
    <div id="pool_config_form" data-dojo-type="dijit/form/Form" method="post"
    action="" style="margin:15px 0 0 0">
      <input type="hidden" name="start_orig" value="<?php echo $pool['start']; ?>" />
      <div style="font-weight:bold">Pool start IP:</div>
      <div><div name="start_new" data-dojo-type="dijit/form/ValidationTextBox"
      data-dojo-props="required: 'true', trim: 'true'"
      value="<?php echo int2ip($saddr[0], $is_ipv6); ?>"></div></div>
      <div style="font-weight:bold;margin:8px 0 0 0">Pool end IP:</div>
      <div><div name="end" data-dojo-type="dijit/form/ValidationTextBox"
      data-dojo-props="required: 'true', trim: 'true'"
      value="<?php echo int2ip($eaddr[0], $is_ipv6); ?>"></div></div>
      <div style="margin:8px 0 0 0">
        <button id="btn_pool_update" data-dojo-type="dijit/form/Button"
        type="submit">Save</button>
      </div>
      <script type="dojo/on" data-dojo-event="submit" data-dojo-args="e">
        e.preventDefault();
        require(['dijit/registry', 'secomdhcp/webapp'], function(registry, webapp){
          if (registry.byId('pool_config_form').isValid()) {
            registry.byId('btn_pool_update').attr('disabled', true);
            webapp.XhrJsonPost('/appdata/pool/update', {form: 'pool_config_form'})
            .then(function(data){
              webapp.RefreshNetworkTree();
              webapp.Navigate(data.obj_id);
            }, function(error){
              registry.byId('btn_pool_update').attr('disabled', false);
            });
          }
        });
      </script>
    </div>
  </div>
</div>
