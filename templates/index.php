<?php
$username = '';
if (!empty($_REQUEST['username'])) {
  $username = addslashes($_REQUEST['username']);
}

$day = strftime('%Y-%m-%d');
if (isset($_REQUEST['day'])) {
  $day = addslashes($_REQUEST['day']);
}
else {
  $day = strftime('%Y-%m-%d');
}
?>
<h1>Resources</h1>

<form method="GET">
  <div class="input">
    <label for="username">User</label>
    <input name="username"<?php echo ' value="' . $username . '"'; ?>>
    <p>e.g. Olav Schettler</p>
  </div>
  <div class="input">
    <label for="day">Date</label>
    <input name="day"<?php echo ' value="' . $day . '"'; ?>>
    <p>e.g. <?php echo strftime('%Y-%m-%d'); ?></p>
  </div>
  <div class="buttons clear">
    <input type="submit" value="Suchen">
  </div>
</form>

<?php
if (!empty($entries)) {
  echo render('_slots', array(
    'days' => $entries,
    'per_page' => $per_page,
    'count' => $count,
    'page' => $page,
  ));
}
?>
