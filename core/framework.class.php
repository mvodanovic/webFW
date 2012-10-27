<?php
namespace WebFW\Core;

final class Framework
{
   private static $_ctlPath = 'Application\Controllers\\';
   private static $_cmpPath = 'Application\Components\\';

   private static function _loadConfig()
   {
      $file = \WebFW\Config\BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'current_specifics.inc.php';

      if (!file_exists($file))
      {
         throw new Exception('Required file missing: ' . $file);
      }

      require_once($file);

      if (!defined('\Config\SPECIFICS'))
      {
         throw new Exception('Required constant \'Config\SPECIFICS\' missing in file: ' . $file);
      }

      $file = \WebFW\Config\BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'specifics' . DIRECTORY_SEPARATOR . \Config\SPECIFICS . '.inc.php';

      if (!file_exists($file))
      {
         throw new Exception('Required file missing: ' . $file);
      }

      require_once($file);

      if (!class_exists('\Config\Specifics\Data'))
      {
         throw new Exception('Class \'\Config\Specifics\Data\' missing in file: ' . $file);
      }

      if (!method_exists('\Config\Specifics\Data', 'GetItem'))
      {
         throw new Exception('Method \'GetItem\' missing in class \'\Config\Specifics\Data\' in file: ' . $file);
      }

      $file = \Config\Specifics\Data::GetItem('ERROR_REPORTING');
      if ($file !== null) error_reporting($file);

      $file = \Config\Specifics\Data::GetItem('DISPLAY_ERRORS');
      if ($file !== null) ini_set('display_errors', $file);

      $dbUsername = \Config\Specifics\Data::GetItem('DB_USERNAME');
      $dbPassword = \Config\Specifics\Data::GetItem('DB_PASSWORD');
      $dbName = \Config\Specifics\Data::GetItem('DB_NAME');
      $dbHost = \Config\Specifics\Data::GetItem('DB_HOST');
      if ($dbUsername !== null && $dbPassword !== null && $dbName !== null) {
         \WebFW\Core\Database\PgSQLHandler::createNewConnection($dbUsername, $dbPassword, $dbName, $dbHost);
      }
   }

   public static function Start()
   {
      global $wFW_Controller;

      self::_loadConfig();

      $name = '';
      if (array_key_exists('ctl', $_REQUEST)) $name = trim($_REQUEST['ctl']);
      if ($name === '') $name = \Config\Specifics\Data::GetItem('DEFAULT_CTL');
      if ($name === null || $name === '')
      {
         echo \WebFW\Core\Doctype::XHTML11;
         require_once \WebFW\Config\FW_PATH . '/templates/helloworld.template.php';
         return;
      }
      if (!class_exists(self::$_ctlPath . $name))
      {
         self::Error404('Controller missing: ' . $name);
      }

      $name = self::$_ctlPath . $name;

      $wFW_Controller = new $name();
      $wFW_Controller->Init();
   }

   public static function ComponentRunner($name, &$params = null, $action = null)
   {
      if (!class_exists(self::$_cmpPath . $name))
      {
         throw new Exception('Component missing: ' . $name);
      }

      $name = self::$_cmpPath . $name;

      $component = new $name();

      if (is_string($action))
      {
         $component->SetAction($action);
      }

      if (is_array($params))
      {
         $component->SetParams($params);
      }

      return $component->Init();
   }

   public static function Error404($debugMessage = '404 Not Found')
   {
      if (\Config\Specifics\Data::GetItem('SHOW_DEBUG_INFO') === true)
      {
         throw new Exception($debugMessage, 404);
      }
      elseif (file_exists(\Config\Specifics\Data::GetItem('ERROR_404_PAGE')))
      {
         header("HTTP/1.1 404 Not Found");
         readfile(\Config\Specifics\Data::GetItem('ERROR_404_PAGE'));
         die;
      }
      else
      {
         throw new Exception($debugMessage, 404);
      }
   }

   private function __construct() {}
}

?>
