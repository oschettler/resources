<?php
/**
 * Front controller
 */
session_start();
require_once __DIR__ . '/app.php';

/**
 * Einfacher Renderer. 
 * @param $template wird in "templates/layout.php" eingebettet
 * @param $vars stehen als lokale Variable zur Verfügung
 */
function render($template, $vars = array(), $layout = FALSE) {
  global $logged_user;

  extract($vars);
  ob_start(); 
  require __DIR__ . '/templates/' . $template . '.php';
  $contents = ob_get_clean();

  if ($layout) {
    ob_start();
    require __DIR__ . '/templates/layout.php';
    return ob_get_clean();
  }
  else {
    return $contents;
  }
}

/**
 * @param Neue URL
 */
function redirect($location) {
  header('Location: ' . $location);
  exit;
}

/*
 * --------------------------------------------------------
 */

/*
 * Maximales Debugging zur Entwicklung
 * @todo für Produktivbetrieb deaktivieren
 */
error_reporting(-1);
ini_set('display_errors', TRUE);

/*
 * Einfacher Router auf der Basis von request_uri in nginx
 * Apache/mod_rewrite may require path_info instead
 */
if (preg_match('/^([^\?]*)\?/', $_SERVER['REQUEST_URI'], $matches)) {
  $path_info = $matches[1];
}
else {
  $path_info = $_SERVER['REQUEST_URI'];
}

$path_info = explode('/', empty($path_info) 
  ? '/index' 
  : $path_info); 

// Default-Controller ist 'index'
$path = empty($path_info[1]) ? 'index' : $path_info[1];

// Nur erlaubte Zeichen für Controller-Namen
$controller = preg_replace('/[^_0-9a-z]+/i', '_', $path);

/*
 * Verbindung mit der Datenbank.
 * Der Einfachheit halber wird der DB-Link implizit gespeichert.
 */
$config = parse_ini_file(__DIR__ . '/database.ini');
if (!empty($config['db_driver']) && $config['db_driver'] == 'sqlite') {
  // SQlite
  class DB extends SQLite3 {
    const type = 'sqlite';
    public function __construct($fname) {
      parent::__construct($fname);
    }
  }
  
  $GLOBALS['db'] = new DB($config['db_host']);
}
else {
  // MySQL
  class DB {
    const type = 'mysql';
    function __construct($config) {
      $this->db = new mysqli(
        $config['db_host'],
        $config['db_user'],
        $config['db_pass'],
        $config['db_name']
      );
      $this->query('SET NAMES UTF8');
    }
    
    function escapeString($txt) { return $this->db->real_escape_string($txt); }
    function exec($sql) { return $this->db->query($sql); }
    function query($sql) { $this->result = $this->db->query($sql); }
    function querySingle($sql) { return $this->db->query($sql)->fetch_object(); }
    function fetchArray() { return $this->result->fetch_row(); }
  }
  $GLOBALS['db'] = new DB($config);
}

/*
 * Aufruf des Controllers und rendern eines gleichnamigen Templates,
 * falls das Ergebnis ein Array ist
 */
try {
  if (function_exists($controller)) {
    $result = call_user_func_array($controller, $path_info);
  }
  else {
    header('Content-type: text/html', /*replace*/TRUE, /*Service Unavailable*/503);
    echo render('error', array(
      'message' => "Kein Controller {$controller}"), /*layout*/TRUE);
    exit();
  }
  if (is_array($result)) {
    echo render($controller, $result, /*layout*/TRUE);
  }
}
catch (Exception $e) {
  header('Content-type: text/html', /*replace*/TRUE, /*Service Unavailable*/503);
  echo render('error', array(
    'message' => $e->getMessage()), /*layout*/TRUE);
}
