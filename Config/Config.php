<?php

error_reporting(E_ALL);
set_time_limit(0);
date_default_timezone_set('Asia/Chongqing');

define('APP_HOME', dirname(dirname(__FILE__)) . '/');

$spiderCfg = array(
    'depth'  => 2,
    'wait'   => 3 * 1000000,
);

$dbConfig["hostname"]    = "127.0.0.1";    //��������ַ
$dbConfig["username"]    = "root";        //���ݿ��û���
$dbConfig["password"]    = "";        //���ݿ�����
$dbConfig["database"]    = "spider_data";        //���ݿ�����
$dbConfig["charset"]        = "gbk";
