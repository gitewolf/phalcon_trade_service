<?php
/**
 * Services are globally registered in this file
 */

use Phalcon\Mvc\Router;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\DI\FactoryDefault;
use Phalcon\Session\Adapter\Files as SessionAdapter;

use Phalcon\Events\Manager as EventsManager;


/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new FactoryDefault\Cli();

// -- 系统配置 ----------------------------------------------------------------------------------------------------

$di->setShared('config', function () {
    return new \Phalcon\Config(require JDS_DIR_CONFIG . '/config.' . JDS_RUNMODE . '.php');
});

/**
 * member the logger
 */
$di->setShared('logger', function () use (&$config) {
    if ('database' == strtolower($config->logger->adapter)) {
        $connection = new \Phalcon\Db\Adapter\Pdo\Mysql([
            "host" => $config->logger->host,
            "username" => $config->logger->username,
            "password" => $config->logger->password,
            "dbname" => $config->logger->name
        ]);
        $logger = new Phalcon\Logger\Adapter\Database('errors', [
            'db' => $connection,
            'table' => $config->logger->table
        ]);
        $logger->info("initialize database logger successfully.");
    } else {
        $logger = new \Phalcon\Logger\Adapter\File($config->logger->path . date("Ymd") . '.log',
            ['mode' => 'a']);
//        $logger->info("initialize file logger successfully.");
    }
    return $logger;
});

/**
 * member the logger
 */
$di->setShared('tradeLogger', function () use (&$config) {
    if ('database' == strtolower($config->logger->adapter)) {
        $connection = new \Phalcon\Db\Adapter\Pdo\Mysql([
            "host" => $config->logger->host,
            "username" => $config->logger->username,
            "password" => $config->logger->password,
            "dbname" => $config->logger->name
        ]);
        $logger = new Phalcon\Logger\Adapter\Database('errors', [
            'db' => $connection,
            'table' => $config->logger->table
        ]);
        $logger->info("initialize database logger successfully.");
    } else {
        $logger = new \Phalcon\Logger\Adapter\File($config->logger->path . 'trade_'.date("Ymd") . '.log',
            ['mode' => 'a']);
//        $logger->info("initialize file logger successfully.");
    }
    return $logger;
});
//
///**
// * Loading routes from the routes.php file
// */
//$di->set('router', function () {
//    return require JDS_DIR_CONFIG . '/routes.php';
//});

$config = $di->getShared('config');



/**
 * The URL component is used to generate all kind of urls in the application
 */
$di['url'] = function () {
    $url = new UrlResolver();
    $url->setBaseUri('/');

    return $url;
};

/**
 * Start the session the first time some component request the session service
 */
$di['session'] = function () {
    $session = new SessionAdapter();
    $session->start();
    return $session;
};

/**
 * MongoDB
 * Connecting to a domain socket,
 * falling back to localhost connection
 */
$di->set('collectionManager',function () {//Register mongoDB Collection Manager
    return new Phalcon\Mvc\Collection\Manager();
});

$di->set('mongo', function () use (&$config){
    //$mongo = new MongoClient("mongodb:///tmp/mongodb-27017.sock,localhost:27017");
    $options = [];
    if(isset($config->mongodb->replicaSet)){
        $options['replicaSet'] = $config->mongodb->replicaSet;
        $options['readPreference'] = MongoClient::RP_SECONDARY_PREFERRED;
    }
    $mongo = new MongoClient($config->mongodb->servers[0],$options);
    return $mongo->selectDB('pyk_sns');
}, true);

/**
 * PYK SimpleDB
 *
 */
$di->setShared('SimpleDB',function () use (&$config) {
    $SimpleDB = \PIKEX\Storage\SimpleDB::getInstance($config->ssdb->host, $config->ssdb->port);
    return $SimpleDB;
});

$di->setShared('db', function () use ($di, $config) {
    $connection = new \Phalcon\Db\Adapter\Pdo\Mysql($config->db->toArray());
    return $connection;
});

/**
 * member the logger
 */
$di->setShared('logger',function () use (&$config) {
    if ('database' == strtolower($config->logger->adapter)) {
        $connection = new \Phalcon\Db\Adapter\Pdo\Mysql([
            "host"     => $config->logger->host,
            "username" => $config->logger->username,
            "password" => $config->logger->password,
            "dbname"   => $config->logger->name
        ]);
        $logger     = new Phalcon\Logger\Adapter\Database('errors',[
            'db'    => $connection,
            'table' => $config->logger->table
        ]);
        $logger->info("initialize database logger successfully.");
    } else {
        $logger = new \Phalcon\Logger\Adapter\File($config->logger->path . date("Ymd") . '.log',
            ['mode' => 'a']);
//        $logger->info("initialize file logger successfully.");
    }

    return $logger;
});



//消息队列Redis
$di->setShared('redis', function () use ($di, $config) {
    $redisQueue = new Redis();
    $redisQueue->pconnect($config->redis->host,$config->redis->port);
    $redisQueue->select($config->redis->db);
    return $redisQueue;
});


/***********定义service服务**********************/
require_once __DIR__.'/jdsServices.php';



