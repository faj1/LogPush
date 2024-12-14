<?php

namespace Faj1\Push;

class Installer
{
    public static function postInstall($event): void
    {
        echo '开始处理配置文件'.PHP_EOL;
        $io = $event->getIO();

        $configFile = getcwd() . '/LogConfig.json';

        // 检查配置文件是否存在
        if (!file_exists($configFile)) {
            // 提示用户是否要生成配置文件
            if ($io->askConfirmation('配置文件 config.json 不存在。是否要生成一个默认配置文件？[Y/n] ', true)) {
                // 从模板生成配置文件
                $defaultConfig = [
                    "ServerUrl" => "http://127.0.0.1:8000",
                    "UdpServerUrl"=>"http://127.0.0.1:8000"
                ];
                file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $io->write('<info>默认配置文件已生成: LogConfig.json</info>');
            } else {
                $io->write('<comment>请手动创建 LogConfig.json 文件。</comment>');
            }
        } else {
            $io->write('<info>配置文件已存在，跳过。</info>');
        }
    }
}
