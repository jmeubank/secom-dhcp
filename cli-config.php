<?php
require_once __DIR__ . '/application/libraries/Doctrine/Common/ClassLoader.php';
define('BASEPATH', '');
require_once __DIR__ . '/application/config/database.php';

$classLoader = new \Doctrine\Common\ClassLoader('models', __DIR__ . '/application');
$classLoader->register();
$classLoader = new \Doctrine\Common\ClassLoader('proxies', __DIR__ . '/application/models');
$classLoader->register();

$config = new \Doctrine\ORM\Configuration();
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
$driverImpl = $config->newDefaultAnnotationDriver(__DIR__ . '/application/models');
$config->setMetadataDriverImpl($driverImpl);
$config->setProxyDir(__DIR__ . '/application/models/proxies');
$config->setProxyNamespace('proxies');

// Database connection information
$connectionOptions = array(
  'driver' =>   'pdo_mysql',
  'user' =>     $db['default']['username'],
  'password' => $db['default']['password'],
  'host' =>     $db['default']['hostname'],
  'dbname' =>   $db['default']['database']
);

$em = \Doctrine\ORM\EntityManager::create($connectionOptions, $config);

$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
));
