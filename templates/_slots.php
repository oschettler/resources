
<div id="pager">
  <?php
  $page_count = ceil($count / $per_page);
  parse_str($_SERVER['QUERY_STRING'], $q);

  if ($page > 1) {
    $q['page'] = $page - 1;
    ?>
    <a href="<?php echo '?', http_build_query($q); ?>">&laquo;previous</a>
    <?php
  }

  printf("| Page %d of %d |", $page, $page_count);

  if ($page < $page_count) {
    $q['page'] = $page + 1;
    ?>
    <a href="<?php echo '?', http_build_query($q); ?>">next&raquo;</a>
    <?php
  }
  ?>
</div>

<table>
  <thead>
    <tr>
      <th>User</th>
      <th>Day</th>
      <th>Slot</th>
      <th>Assignment</th>
      <th>Created / Updated</th>
    </tr>
  </thead>
  <tbody>
<?php
$last_username = NULL;
$last_day = NULL;
foreach ($days as $day) {
  foreach ($day->slots as $slot => $assignment) {
    ?>
    <tr>
      <td><?php 
      echo $day->username == $last_username ? '&hellip;' : $day->username; $last_username = $day->username; ?></td>
      <td><?php echo $day->day == $last_day ? '&hellip;' : $day->day; $last_day = $day->day; ?></td>
      <td><?php echo $slot; ?></td>
      <td><?php echo $assignment; ?></td>
      <td class="moddate">
        <?php 
        if ($day->updated) {
          echo $day->updated; 
        }
        else {
          echo $day->created; 
        }
        ?>
      </td>
    </tr>
    <?php
  } // slots
} // days
?>
  <tbody>
</table>
