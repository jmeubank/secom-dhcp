<span data-dojo-type="dojo/data/ItemFileReadStore" data-dojo-id="hist_store"
data-dojo-props="url: '/appdata/history/<?php echo $searchby; ?>?term=<?php echo urlencode($term); ?>'"></span>
<table data-dojo-type="dojox/grid/DataGrid" data-dojo-id="hist_grid"
data-dojo-props="clientSort: 'true'"
style="width:100%;height:100%">
  <thead>
    <tr>
      <th width="200px" field="ip">IP</th>
      <th width="120px" field="mac">MAC</th>
      <th width="200px" field="hostname">Hostname</th>
      <th width="150px" field="begin">Start</th>
      <th width="150px" field="end">End</th>
    </tr>
  </thead>
</table>
