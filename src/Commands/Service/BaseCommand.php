<?php

namespace Httpd\Commands\Service;

use Mix\Ini\IniParser;
use Mix\Console\Command;
use Mix\Console\CommandLine\Flag;
use Mix\Console\PidFileHandler;
use Mix\Helpers\FileSystemHelper;

/**
 * Class BaseCommand
 * @package Httpd\Commands\Service
 */
class BaseCommand extends Command
{

    /**
     * 提示
     */
    const IS_RUNNING = 'Service is running, PID : %d';
    const NOT_RUNNING = 'Service is not running.';
    const EXEC_SUCCESS = 'Command executed successfully.';

    /**
     * 配置信息
     * @var array
     */
    public $config;

    /**
     * 初始化事件
     */
    public function onInitialize()
    {
        parent::onInitialize(); // TODO: Change the autogenerated stub
        // 获取配置
        $filename = Flag::string(['c', 'configuration'], '');
        if ($filename == '') {
            throw new \Mix\Exceptions\InvalidArgumentException('Option \'-c/--configuration\' required.');
        }
        if (!FileSystemHelper::isAbsolute($filename)) {
            $filename = getcwd() . DIRECTORY_SEPARATOR . $filename;
        }
        $ini = new IniParser([
            'filename' => $filename,
        ]);
        if (!$ini->load()) {
            throw new \Mix\Exceptions\InvalidArgumentException("Configuration file not found: {$filename}");
        }
        $this->config = [
            'host'              => $ini->section('host'),
            'port'              => $ini->section('port'),
            'configurationFile' => $ini->section('configuration_file'),
            'settings'          => $ini->section('settings'),
        ];
        // 引入自动加载
        $autoloadFile = $ini->section('autoload_file');
        unset($GLOBALS['__composer_autoload_files']); // 重新载入composer_autoload_files
        require $autoloadFile;
        // 载入环境配置
        $environmentFile = $ini->section('environment_file');
        \Mix::loadEnvironmentFrom($environmentFile);
        // 配置日志组件
        $handler         = app()->log->handler;
        $handler->single = $this->config['settings']['log_file'] ?? '';
        // Swoole 判断
        if (!extension_loaded('swoole')) {
            throw new \RuntimeException('Need swoole extension to run, install: https://www.swoole.com/');
        }
    }

    /**
     * 获取pid
     * @return bool|string
     */
    public function getServicePid()
    {
        $handler = new PidFileHandler(['pidFile' => $this->config['settings']['pid_file'] ?? '']);
        return $handler->read();
    }

}
