<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\I18n\I18n;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
  const LANG_FR = "fr";
  const LANG_EN = "en";
    
  public $lang;
  public $agents_param = "agents";
  public $lang_param = "lang";
    
  public function initialize()
  {
      parent::initialize();

      $this->loadComponent('RequestHandler', [
          'enableBeforeRedirect' => false,
      ]);
      $this->loadComponent('Flash');

      $session = $this->request->session();
      $session->write('Config.DEFAULT_LANG', self::LANG_FR);
      $this->lang = isset($_GET['lang']) ? $_GET['lang'] : ( $session->read('Config.language') ? $session->read('Config.language') : $session->read('Config.DEFAULT_LANG') );
      $this->setLang($this->lang);
      /*
       * Enable the following component for recommended CakePHP security settings.
       * see https://book.cakephp.org/3.0/en/controllers/components/security.html
       */
      //$this->loadComponent('Security');
  }
  
    
  public function setLang($lang = null)
  {
    $session = $this->request->session();
    if ( $lang == null ) {
      $lang = $session->read('Config.language');
    }
    $newLocale = $lang."_CA";
    I18n::setLocale($newLocale);
    
    $session->write('Config.language', $lang);
    return $lang;
  }
}