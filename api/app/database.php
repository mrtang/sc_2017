<?php

return array(

  /*
  |--------------------------------------------------------------------------
  | PDO Fetch Style
  |--------------------------------------------------------------------------
  |
  | By default, database results will be returned as instances of the PHP
  | stdClass object; however, you may desire to retrieve records in an
  | array format for simplicity. Here you can tweak the fetch style.
  |
  */

  'fetch' => PDO::FETCH_CLASS,

  /*
  |--------------------------------------------------------------------------
  | Default Database Connection Name
  |--------------------------------------------------------------------------
  |
  | Here you may specify which of the database connections below you wish
  | to use as your default connection for all database work. Of course
  | you may use many connections at once using the Database library.
  |
  */

  'default' => 'metadb',

  /*
  |--------------------------------------------------------------------------
  | Database Connections
  |--------------------------------------------------------------------------
  |
  | Here are each of the database connections setup for your application.
  | Of course, examples of configuring each database platform that is
  | supported by Laravel is shown below to make development simple.
  |
  |
  | All database work in Laravel is done through the PHP PDO facilities
  | so make sure you have the driver for your particular database of
  | choice installed on your machine before you begin development.
  |
  */

  'connections' => array(
        'courierdb'     => array(
            'read' => array(
                'host' => '10.0.0.81',
            ),
            'write' => array(
                'host' => '10.0.0.80',
            ),
          'driver'    => 'mysql',
          'database'  => 'bxm_courier',
          'username'  => 'shipchung2016',
          'password'  => 'rc2DyNQrWw72MvLL',
          'charset'   => 'utf8',
          'collation' => 'utf8_unicode_ci',
          'prefix'    => '',
        ),
        'metadb' => array(
            'read' => array(
                'host' => '10.0.0.81', //Config::get('config_api.connection')[array_rand(Config::get('config_api.connection'),1)],
            ),
            'write' => array(
                'host' => '10.0.0.80',
            ),
           'driver'    => 'mysql',
           'database'  => 'bxm_metadata',
           'username'  => 'shipchung2016',
           'password'  => 'rc2DyNQrWw72MvLL',
           'charset'   => 'utf8',
           'collation' => 'utf8_unicode_ci',
           'prefix'    => '',
        ),
        'noticedb' => array(
            'read' => array(
                'host' => '10.0.0.81',
            ),
            'write' => array(
                'host' => '10.0.0.80',
            ),
           'driver'    => 'mysql',
           'database'  => 'bxm_notification',
           'username'  => 'shipchung2016',
           'password'  => 'rc2DyNQrWw72MvLL',
           'charset'   => 'utf8',
           'collation' => 'utf8_unicode_ci',
           'prefix'    => '',
        ),
        'sellerdb' => array(
            'read' => array(
                'host' => '10.0.0.81',
            ),
            'write' => array(
                'host' => '10.0.0.80',
            ),
           'driver'    => 'mysql',
           'database'  => 'bxm_seller',
           'username'  => 'shipchung2016',
           'password'  => 'rc2DyNQrWw72MvLL',
           'charset'   => 'utf8',
           'collation' => 'utf8_unicode_ci',
           'prefix'    => '',
        ),
        'orderdb' => array(
           'driver'    => 'mysql',
            'read' => array(
                'host' => '10.0.0.81',
            ),
            'write' => array(
                'host' => '10.0.0.80',
            ),
           'database'  => 'bxm_orders',
           'username'  => 'shipchung2016',
           'password'  => 'rc2DyNQrWw72MvLL',
           'charset'   => 'utf8',
           'collation' => 'utf8_unicode_ci',
           'prefix'    => '',
        ),
        'ticketdb' => array(
           'driver'    => 'mysql',
            'read' => array(
                'host' => Config::get('config_api.connection')[array_rand(Config::get('config_api.connection'),1)],
            ),
            'write' => array(
                'host' => '10.0.0.80',
            ),
           'database'  => 'bxm_ticket',
           'username'  => 'shipchung2016',
           'password'  => 'rc2DyNQrWw72MvLL',
           'charset'   => 'utf8',
           'collation' => 'utf8_unicode_ci',
           'prefix'    => '',
        ),
        'omsdb' => array(
            'read' => array(
                'host' => Config::get('config_api.connection')[array_rand(Config::get('config_api.connection'),1)],
            ),
            'write' => array(
                'host' => '10.0.0.80',
            ),
           'driver'    => 'mysql',
           'database'  => 'bxm_oms',
           'username'  => 'shipchung2016',
           'password'  => 'rc2DyNQrWw72MvLL',
           'charset'   => 'utf8',
           'collation' => 'utf8_unicode_ci',
           'prefix'    => '',
        ),
        'accdb' => array(
            'host' => '10.0.0.80',
            'driver'    => 'mysql',
            'database'  => 'bxm_accounting',
            'username'  => 'shipchung2016',
            'password'  => 'rc2DyNQrWw72MvLL',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ),
        'accbm' => array(
            'host' => '10.0.0.80',
            'driver' => 'mysql',
            'database' => 'boxme_accounting',
            'username' => 'shipchung2016',
            'password' => 'rc2DyNQrWw72MvLL',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ),
	'acc_orderdb' => array(
            'driver'    => 'mysql',
            'host'      => '10.0.0.80',
            'database'  => 'bxm_orders',
            'username'  => 'shipchung2016',
            'password'  => 'rc2DyNQrWw72MvLL',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ),
        'ecmbm' => array(
            'host' => '10.0.0.80',
            'driver' => 'mysql',
            'database' => 'boxme_ecommerce',
            'username' => 'shipchung2016',
            'password' => 'rc2DyNQrWw72MvLL',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ),
        'sellerbm' => array(
            'host'      => '10.0.0.80',
            'driver'    => 'mysql',
            'database'  => 'boxme_seller',
            'username'  => 'shipchung2016',
            'password'  => 'rc2DyNQrWw72MvLL',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ),
        'warehousebm' => array(
            'host'      => '10.0.0.80',
            'driver'    => 'mysql',
            'database'  => 'boxme_warehouse',
            'username'  => 'shipchung2016',
            'password'  => 'rc2DyNQrWw72MvLL',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ),
	'freepbx'     => array(
	     'host' 	   => '10.0.0.87',
            'driver'    => 'mysql',
            'database'  => 'asteriskcdrdb',
            'username'  => 'pbx',
            'password'  => 'Shipchung123123',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        )
  ),

  /*
  |--------------------------------------------------------------------------
  | Migration Repository Table
  |--------------------------------------------------------------------------
  |
  | This table keeps track of all the migrations that have already run for
  | your application. Using this information, we can determine which of
  | the migrations on disk haven't actually been run in the database.
  |
  */

  'migrations' => 'migrations',

  /*
  |--------------------------------------------------------------------------
  | Redis Databases
  |--------------------------------------------------------------------------
  |
  | Redis is an open source, fast, and advanced key-value store that also
  | provides a richer set of commands than a typical key-value systems
  | such as APC or Memcached. Laravel makes it easy to dig right in.
  |
  */

  'redis' => array(

    'cluster' => false,

    'default' => array(
      'host'     => '10.0.0.81',
      'port'     => 6379,
      'database' => 0,
    ),

  ),

);
