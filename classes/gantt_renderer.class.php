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
        // frappe-gantt writes the name into the bar label and the popup with
        // innerHTML, so every value is HTML-escaped here. Before, a task named
        // <image href=x onerror=...> ran its script when the chart was drawn.
        $rows = array();
        foreach ($tasks as $task) {
            $row = array();
            foreach ($task as $key => $value) {
                $row[$key] = is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
            }
            $rows[] = $row;
        }
        $json = json_encode($rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $cid  = htmlspecialchars($containerId, ENT_QUOTES, 'UTF-8');
        $vm   = htmlspecialchars($viewMode, ENT_QUOTES, 'UTF-8');
        ?>
<?php if ($title): ?>
<h3 style="margin:8px 0 4px;font-size:14px;"><?php echo htmlspecialchars($title); ?></h3>
<?php endif; ?>
<div style="margin:0 0 4px;font-size:12px;">
  <strong><?php echo 'View:'; ?></strong>
  <?php foreach (['Day', 'Week', 'Month'] as $v): ?>
  <button type="button"<?php echo dPaction('dpGanttViewMode', array($containerId, $v)); ?> style="margin:0 2px;padding:2px 8px;cursor:pointer;"><?php echo $v; ?></button>
  <?php endforeach; ?>
  &nbsp;&nbsp;<button type="button"<?php echo dPaction('window.print'); ?> style="padding:2px 8px;cursor:pointer;">Print</button>
</div>
<div style="overflow-x:auto;background:#fff;padding:10px;border:1px solid #ccc;max-width:100%;">
  <svg id="<?php echo $cid; ?>"></svg>
</div>
<?php // Drawn by js/dp-gantt.js; a JSON data block is not executed, so it needs no CSP nonce. ?>
<script type="application/json" class="dp-gantt-data" data-target="<?php echo $cid; ?>" data-view-mode="<?php echo $vm; ?>"><?php echo $json; ?></script>
        <?php
    }
}
