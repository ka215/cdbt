<?php
// GUI tool "table creator"

// inherit values
//var_dump($inherit_values);

// translate text
$table_creator_label = __('Table Creator', PLUGIN_SLUG);

$content_html .= <<<EOH
<!-- /* Table Creator Modal */ -->
<div class="modal fade mysql-table-creator" tabindex="-1" role="dialog" aria-labelledby="MySQLTableCreator" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove"></span></button>
        <h4 class="modal-title">$table_creator_label</h4>
      </div>
      <div class="modal-body">
$inherit_values
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> <span class="cancel-close"><?php _e('Cancel', PLUGIN_SLUG); ?></span></button>
        <button type="button" class="btn btn-primary"><span class="glyphicon glyphicon-ok"></span> <span class="run-process"><?php _e('Yes, run', PLUGIN_SLUG); ?></span></button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
EOH;
