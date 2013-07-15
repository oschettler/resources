<?php
/**
 * Controllers.
 */
const PER_PAGE = 5;
const EXCEL_TYPE = 'Excel2007';
const EXCEL_WORKSHEET = 'Entwickler';
const EXCEL_FILENAME = 'var/Ressourcenplanung 2013.xlsx';

/**
 * Paginated search for slots
 */
function _find($condition, $fields = '*', $distinct = '') {
  global $db;
  
  $page = empty($_GET['page']) ? 1 : intval($_GET['page']);
  $limit = (($page-1) * PER_PAGE) . ',' . PER_PAGE; 
  
  $sql = "from days";
  if ($condition) {
    $sql .= ' ' . $condition;
  }
  $_distinct = $distinct ? $distinct : '*';
  $count = $db->querySingle("
  select count({$_distinct}) 
  {$sql}");
  
  $_distinct = $distinct ? ($fields ? "{$distinct}," : $distinct) : '';
  $rs = $db->query($q = "
  select {$_distinct}{$fields} 
  {$sql}
  limit {$limit}");
  
  $entries = array();
  while ($entry = $rs->fetchArray(SQLITE3_ASSOC)) {
    $entry = (object)$entry;
    if (isset($entry->slots)) {
      $entry->slots = json_decode($entry->slots);
    }
    $entries[] = $entry;
  }
  
  return array(
    'page' => $page,
    'per_page' => PER_PAGE,
    'count' => $count,
    'entries' => $entries,
  );
}

/**
 * Save a slot
 */
function _save($user, $day, $slots) {
  global $db;
  static $userdays;
  static $stmt;
  
  if (empty($userdays)) {
    echo "Caching ... ";
    
    $sql = "select username, day from days";
    $rs = $db->query($sql);

    $userdays = array();
    while ($userday = $rs->fetchArray(SQLITE3_ASSOC)) {
      $userdays[$userday['username']][$userday['day']] = TRUE;
    }

    echo "Done.\n";
  }

  $exists = isset($userdays[$user['name']][$day['date']]);

  if ($exists) {
    
    $sql = "update days 
    set slots = :slots, updated = :updated
    where username = :username
    and day = :day";
    if (empty($stmt[$sql])) {
      $stmt[$sql] = $db->prepare($sql);
      if (!$stmt[$sql]) {
        die( "ERR: " . $db->lastErrorMsg() . "\n");
      }
    }
    
    $stmt[$sql]->bindValue(':updated', strftime('%Y-%m-%d %H:%M:%S'), SQLITE3_TEXT);
    $stmt[$sql]->bindValue(':username', $user['name'], SQLITE3_TEXT);
    $stmt[$sql]->bindValue(':day', $day['date'], SQLITE3_TEXT);
    $stmt[$sql]->bindValue(':slots', json_encode($slots), SQLITE3_TEXT);
    
    if (!$stmt[$sql]->execute()) {
      die( "ERR: " . $db->lastErrorMsg() . "\n");
    }
  }
  else {
    $sql = "insert into days(username, day, slots, created) 
    values (:username, :day, :slots, :created)";
    if (empty($stmt[$sql])) {
      $stmt[$sql] = $db->prepare($sql);
      if (!$stmt[$sql]) {
        die( "ERR: " . $db->lastErrorMsg() . "\n");
      }
    }    
    
    $stmt[$sql]->bindValue(':username', $user['name'], SQLITE3_TEXT);
    $stmt[$sql]->bindValue(':day', $day['date'], SQLITE3_TEXT);
    $stmt[$sql]->bindValue(':slots', json_encode($slots), SQLITE3_TEXT);
    $stmt[$sql]->bindValue(':created', strftime('%Y-%m-%d %H:%M:%S'), SQLITE3_TEXT);
    
    if (!$stmt[$sql]->execute()) {
      die( "ERR: " . $db->lastErrorMsg() . "\n");
    }
  }
}

/**
 * Homepage = Default Controller
 */
function index() {
  global $db;
  
  if (isset($_REQUEST['day'])) {
    if (!empty($_REQUEST['day'])) {
      $cond = "where day like '" . $db->escapeString($_REQUEST['day']) . "%'";
    }
    else {
      $cond = 'where 1=1';
    }
  }
  else {
    $cond = "where day = '" . strftime('%Y-%m-%d') . "'";
  }

  if (!empty($_REQUEST['username'])) {
    $_SESSION['username'] = $_REQUEST['username'];
    $cond .= " and username = '" . $db->escapeString($_REQUEST['username']) . "'";
  }

  return _find($cond);    
}

/**
 * Update database from the Excel file.
 * Invoke as "REQUEST_URI=/update php index.php"
 * ...  from the htdocs directory
 */
function update() {
  global $db;
  set_time_limit(0);
  
  echo "Loading lib...\n";
  
  require_once 'lib/PHPExcel/Classes/PHPExcel/IOFactory.php';
  
  echo "Reading excel ", EXCEL_FILENAME, "...\n";
  
  $reader = PHPExcel_IOFactory::createReader(EXCEL_TYPE);
  $reader->setReadDataOnly(TRUE);
  $reader->setLoadSheetsOnly(EXCEL_WORKSHEET); 
  $excel = $reader->load(EXCEL_FILENAME);
  
  $properties = $excel->getProperties();
  //var_dump($properties);
  
  $worksheet = $excel->getActiveSheet();
  
  echo "Days...\n";
  
  $high_col = $worksheet->getHighestColumn();
  for ($i = 1; $i <= 3; $i++) {
    $title[$i] = $worksheet->rangeToArray("A{$i}:{$high_col}{$i}");
  }
  
  $days = array();
  for ($i = 1; $i < count($title[1][0]); $i++) {
    // stop at first empty column
    if (trim($title[3][0][$i]) == '') {
      break;
    }

    $dateobj = PHPExcel_Shared_Date::ExcelToPHPObject($title[3][0][$i]);
    
    $days[$i] = array(
      'week' => $title[1][0][$i],
      'weekday' => $title[2][0][$i],
      'date' => $dateobj->format('Y-m-d'),
      'timeofday' => $dateobj->getTimestamp(),
    );
  }

  echo "Users...\n";

  $high_row = $worksheet->getHighestRow();
  $user_info = $worksheet->rangeToArray("A1:A{$high_row}");

  $office = NULL;
  $users = array();
  foreach ($user_info as $i => $line) {
    $info = trim($line[0]);
    if (empty($info)) {
      continue;
    }
    if (strpos($info, 'DEV') === 0) {
      $office = $info;
      continue;
    }
    $users[] = array(
      'name' => $info,
      'line' => $i + 1,
      'office' => $office,
    );
  }

  echo "Slots...";

  $db->exec('PRAGMA synchronous = OFF');
  $db->exec('PRAGMA journal_mode = MEMORY');
  $db->exec('BEGIN TRANSACTION');

  foreach ($users as $i => $user) {
    echo "\n{$user['name']}\n";
    
    $twoyearsago = strtotime('2 years ago');
    
    foreach ($days as $col => $day) {
      if ($day['timeofday'] < $twoyearsago) {
        continue;
      }
      
      echo ".";
      $slots = array();
      for ($slot = 1; $slot <= 4; $slot++) {
        $assignment = $worksheet
          ->getCellByColumnAndRow($col, $user['line'] + $slot)
          ->getValue();
        if (strpos($assignment, '=') === 0) {
          continue;
        }
        $slots[$slot] = $assignment;
      }
      _save($user, $day, $slots);
    }
  }
  $db->exec('END TRANSACTION');
  
  echo "Done.\n";
}
