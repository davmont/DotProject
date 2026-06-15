<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

class GanttRenderer {
    private static $assetsLoaded = false;

    static function assets() {
        if (self::$assetsLoaded) return;
        self::$assetsLoaded = true;
        $base = DP_BASE_URL . '/lib/frappe-gantt/';
        echo '<link rel="stylesheet" href="' . $base . 'frappe-gantt.min.css" />' . "\n";
        echo '<script src="' . $base . 'frappe-gantt.min.js"></script>' . "\n";
    }

    /**
     * Render a frappe-gantt chart.
     *
     * @param string $containerId  Unique HTML id for the SVG element.
     * @param array  $tasks        Rows: {id, name, start (YYYY-MM-DD), end (YYYY-MM-DD), progress, dependencies (csv)}.
     * @param string $title        Optional heading text.
     * @param string $viewMode     Initial view: Day|Week|Month.
     */
    static function render($containerId, array $tasks, $title = '', $viewMode = 'Week') {
        self::assets();
        if (empty($tasks)) {
            echo '<p style="padding:10px;color:#666;">';
            if ($title) echo htmlspecialchars($title) . ': ';
            echo 'No items to display.</p>';
            return;
        }
        $json  = json_encode(array_values($tasks), JSON_HEX_TAG | JSON_HEX_APOS);
        $cid   = htmlspecialchars($containerId);
        $jsVar = 'dpGantt_' . preg_replace('/[^a-zA-Z0-9]/', '_', $containerId);
        $vm    = htmlspecialchars($viewMode);
        ?>
<?php if ($title): ?>
<h3 style="margin:8px 0 4px;font-size:14px;"><?php echo htmlspecialchars($title); ?></h3>
<?php endif; ?>
<div style="margin:0 0 4px;font-size:12px;">
  <strong><?php echo 'View:'; ?></strong>
  <?php foreach (['Day', 'Week', 'Month'] as $v): ?>
  <button type="button" onclick="<?php echo $jsVar; ?>.change_view_mode('<?php echo $v; ?>')" style="margin:0 2px;padding:2px 8px;cursor:pointer;"><?php echo $v; ?></button>
  <?php endforeach; ?>
  &nbsp;&nbsp;<button type="button" onclick="window.print()" style="padding:2px 8px;cursor:pointer;">Print</button>
</div>
<div style="overflow-x:auto;background:#fff;padding:10px;border:1px solid #ccc;max-width:100%;">
  <svg id="<?php echo $cid; ?>"></svg>
</div>
<script>
(function(){
  var tasks = <?php echo $json; ?>;
  var <?php echo $jsVar; ?> = new Gantt('#<?php echo $cid; ?>', tasks, {
    view_mode: '<?php echo $vm; ?>',
    date_format: 'YYYY-MM-DD',
    custom_popup_html: function(task) {
      return '<div style="padding:8px 10px;max-width:240px;">'
        + '<strong>' + task.name + '</strong><br>'
        + task.start + ' → ' + task.end + '<br>'
        + task.progress + '% complete</div>';
    }
  });
  window.<?php echo $jsVar; ?> = <?php echo $jsVar; ?>;
}());
</script>
        <?php
    }
}
