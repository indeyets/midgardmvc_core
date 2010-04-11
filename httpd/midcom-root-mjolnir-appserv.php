<?php

$cfg = new midgard_config();
$cfg->read_file('appserv.conf', true);

$cnc = midgard_connection::get_instance();
$cnc->open_config($cfg);

if (!$cnc->is_connected())
{
    throw new Exception("Couldn't connect: ".$cnc->get_error_string());
}

require 'AppServer/autoload.php';
require 'midgardmvc_core/framework.php';

class StartNewRequestException extends RuntimeException {}

class midgardmvc_appserv_app
{
    private $midgardmvc = null;
    public function __construct()
    {
        $this->midgardmvc = midgardmvc_core::get_instance('appserv');
    }

    public function __invoke($context)
    {
        // setting emulated superglobals
        $_SERVER = $context['env'];
        $_COOKIE = $context['_COOKIE'];

        if (isset($context['_POST']))
        {
            $_POST = $context['_POST'];
            if (isset($context['_FILES']))
            {
                $_FILES = $context['_FILES'];
            }
        }

        // starting processing
        try {
            $this->midgardmvc->dispatcher->set_request_data($context);

            call_user_func($context['logger'], "-> starting midgardmvc");
            try {
                ob_start();
                $this->midgardmvc->process();
                $this->midgardmvc->serve();
                $body = ob_get_clean();
            } catch (StartNewRequestException $e) {
                $body = ob_get_clean();
                call_user_func($context['logger'], "-> [!] StartNewRequestException exception arrived");
            } catch (Exception $e) {
                ob_end_clean();
                call_user_func($context['logger'], "-> [!] ".get_class($e)." exception arrived");
                throw $e;
            }
            call_user_func($context['logger'], "-> done with midgardmvc");

            return array(
                $this->midgardmvc->dispatcher->_get_status(),
                $this->midgardmvc->dispatcher->_get_headers(),
                $body
            );
        } catch (Exception $e) {
            echo $e;
            return array(500, array('Content-type', 'text/plain'), "Internal Server Error \n(check log)");
        }
    }
}

$app = new midgardmvc_appserv_app();
$app = new \MFS\AppServer\Middleware\PHP_Compat\PHP_Compat($app);

$file_app = new \MFS\AppServer\Apps\FileServe\FileServe(realpath(dirname(__FILE__).'/../static'));
$file_app2 = new \MFS\AppServer\Apps\FileServe\FileServe(realpath(dirname(__FILE__).'/../../net_nemein_dasboard/static'));

$app = new \MFS\AppServer\Middleware\URLMap\URLMap(array(
    '/' => $app,
    '/midcom-static/net_nemein_dasboard' => $file_app2,
    '/midcom-static/midgardmvc_core' => $file_app,
));

$handler = new \MFS\AppServer\DaemonicHandler('tcp://127.0.0.1:8080', 'HTTP');
$handler->serve($app);
