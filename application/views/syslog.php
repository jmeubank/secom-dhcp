<div id="syslog_msgs_div" style="display:none"></div>
<div id="syslog_msgs_div_display"></div>

<script type="text/javascript">
  require(['dojo/dom', 'dojo/dom-construct', 'dojo/query', 'secomdhcp/webapp'],
  function(dom, domConstruct, query, webapp){
    webapp.syslog_sub = webapp.faye_connection.subscribe('/syslog', function(msg){
      var smd = dom.byId('syslog_msgs_div');
      if (smd) {
        query('#syslog_msgs_div > div:nth-child(30)').forEach(function(node, index, arr){
          query('#syslog_msgs_div > div:first-child').forEach(function(node2, index2, arr2){
            domConstruct.destroy(node2);
          });
        });
        domConstruct.place('<div><nobr>' + msg.line + '</nobr></div>', smd, 'last');
        if (!webapp.syslog_frozen)
          dom.byId('syslog_msgs_div_display').innerHTML = smd.innerHTML;
      }
    });
  });
</script>
