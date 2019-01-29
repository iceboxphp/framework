<?php

namespace Icebox;

use Icebox\App;
// use Symfony\Component\HttpFoundation\Response;
use Icebox\Response;

class Controller {

  public $layout = 'application';
  public $status = 200;
  public $headers = array();
  public $from_controller = true;
  public $_content = array();
  public $_controller = '';
  public $_action = '';

  public function render($view = null, Array $var = array(), Array $options = array()) {

    // find controller and action
    if($this->from_controller == true) {
      $this->_controller = substr(get_class($this), 15, -10);
      $this->_action = print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'], true);
    }


    if($view == null) {
       $view = $this->_action;
    }


    // if first character is slash: load from Framework::view_root()/$view
    // if first character is not slash: load from Framework::view_root()/controller/$view
    if($view[0] == '/') {
      $view_file = App::view_root() . $view . '.html.php';
    } else {
      $view_file = App::view_root() . DIRECTORY_SEPARATOR . $this->_controller . DIRECTORY_SEPARATOR . $view . '.html.php';
    }


    if($this->from_controller == true) {

      $this->from_controller = false;

      if(array_key_exists('layout', $options)) { $this->layout = $options['layout']; }
      if(array_key_exists('status', $options)) { $this->status = $options['status']; }
      if(array_key_exists('headers', $options)) { $this->headers = $options['headers']; }

      ob_start();
      include($view_file);
      $this->_content['content'] = ob_get_clean();

      if($this->layout == false) {
        return new Response($this->_content['content'], $this->status, $this->headers);
      } else {
        ob_start();
        include(App::view_root() . DIRECTORY_SEPARATOR . 'Layout' . DIRECTORY_SEPARATOR . $this->layout . '.html.php');
        return new Response(ob_get_clean(), $this->status, $this->headers);
      }

    } else {

      include($view_file);

    }

  }

  public function yield($view = null) {
    if($view == null) { $view = 'content'; }
    if(isset($this->_content[$view])) {
      echo $this->_content[$view];
    }
  }

  public function start_content($block_name) {
    ob_start();
  }

  public function end_content($block_name) {
    if(isset($this->_content[$block_name])) {
      $this->_content[$block_name] .= ob_get_clean();
    } else {
      $this->_content[$block_name] = ob_get_clean();
    }
  }

  public function redirect($url = '', $status = 302) {
    return new Response($this->getRedirectContent($url), $status, [['Location', $url]]);
  }

  public function flash($name, $message = '', $now = '') {
    $life = ($now == 'now') ? 0 : 1;

    if( func_num_args() == 1 ) {
      if( isset($_SESSION['_ice_flash'][$name]['message']) ) { return $_SESSION['_ice_flash'][$name]['message']; }
    } elseif(func_num_args() > 1) {
      if( !isset($_SESSION['_ice_flash']) ) { $_SESSION['_ice_flash'] = array(); }
      $_SESSION['_ice_flash'][$name] = ['message' => $message, 'life' => $life];
    }
  }

  private function getRedirectContent($url) {
      return sprintf('<!DOCTYPE html>
  <html>
      <head>
          <meta charset="UTF-8" />
          <meta http-equiv="refresh" content="10;url=%1$s" />

          <title>Redirecting to %1$s</title>
      </head>
      <body>
          Redirecting to <a href="%1$s">%1$s</a>.
      </body>
  </html>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
  }
}
