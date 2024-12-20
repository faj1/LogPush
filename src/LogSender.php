<?php

namespace Faj1\Push;

use Exception;


class LogSender
{
    private static string $staticServerUrl = '';
    private static array $staticUdpServerUrl = [];
    private static bool $debug = false;

    private static int $app_id = 0;

    private static string $push_type = '';

    /**
     * 发送日志数据
     *
     * @param mixed $exception 异常对象
     * @param array $context 日志上下文
     * @param string $level 日志级别
     * @param string $application 应用名称
     * @param string $environment 运行环境
     */
    public static function sendLogData(
        mixed $exception = null,
        array $context = [],
        string $level = 'ERROR',
        string $application = 'default_app',
        string $environment = 'production'
    ): void {
        try {
            self::initializeConfig();
            if(self::$debug){
                echo '配置的服务器链接为:'.self::$staticServerUrl.PHP_EOL;
                echo '配置的UDP服务器信息为:'.json_encode(self::$staticServerUrl).PHP_EOL;
                //var_dump(self::$staticServerUrl, self::$staticUdpServerUrl, self::$debug);
            }
            $logData = [
                'log_date' => date('Y-m-d'),
                'timestamp' => date('Y-m-d H:i:s'),
                'level' => $level,
                'application' => $application,
                'module' => null,
                'logger' => null,
                'thread' => null,
                'host' => gethostname(),
                'user_id' => $context['user_id'] ?? null,
                'request_id' => $context['request_id'] ?? null,
                'context' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'environment' => $environment,
                "message"=>null,
                "exception"=>null,
                "file_name"=>null,
                "line_number"=>null,
                "created_at"=>date('Y-m-d H:i:s'),
                "updated_at"=>date('Y-m-d H:i:s')
            ];

            if($exception){
                $logData['message'] = $exception->getMessage();
                $logData['exception'] = $exception->getTraceAsString();
                $logData['file_name'] = $exception->getFile();
                $logData['line_number'] = $exception->getLine();
            }
            $logPush = new LogPush(self::$staticServerUrl, self::$staticUdpServerUrl, self::$debug);
            $logData['app_id'] = self::$app_id;
            if(self::$push_type === "udp"){
                $logPush->UdpSendLog($logData);
            }else{
                $logPush->sendLog($logData);
            }

            /*if (extension_loaded('swoole')) {
                $logPush->UdpSendLog($logData);
            } else {
                $logPush->sendLog($logData);
            }*/
        } catch (\Throwable | Exception $e) {
            if(self::$debug){
                echo '出错了:'.$e->getMessage()."|".$e->getFile()."|".$e->getLine().PHP_EOL;
            }
        }
    }

    /**
     * 初始化配置文件
     *
     * @throws Exception
     */
    private static function initializeConfig(): void
    {
        try {
            // 如果已经初始化过，则跳过
            if (self::$staticServerUrl && self::$staticUdpServerUrl) {
                return;
            }
            $projectRoot = dirname(__DIR__, 4); // 假设当前库代码在 vendor/your-vendor/your-package/
            $configFile = $projectRoot . '/LogConfig.json';
            if (!file_exists($configFile)) {
                throw new Exception('配置文件 LogConfig.json 不存在, 目录路径为: ' . $projectRoot);
            }
            $config = json_decode(file_get_contents($configFile), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($config)) {
                throw new Exception('配置文件 LogConfig.json 格式错误，解析结果非数组');
            }
            self::$staticServerUrl = $config['ServerUrl'] ?? throw new Exception('缺少必备的配置项: ServerUrl');

            if (!isset($config['UdpServerHost']) || !isset($config['Port'])) {
                throw new Exception('缺少必备的配置项: UdpServerHost 或 Port');
            }
            if (!isset($config['app_id'] )) {
                throw new Exception(/** @lang text */ '缺少必备的配置项: app_id');
            }
            if (!isset($config['push_type'] )) {
                throw new Exception(/** @lang text */ '缺少必备的配置项: push_type');
            }
            self::$push_type = $config['push_type'];
            self::$app_id = $config['app_id'];
            self::$staticUdpServerUrl = [
                'host' => $config['UdpServerHost'],
                'port' => $config['Port'],
            ];
            self::$debug = $config['debug'] ?? false; // 默认值为 false
        } catch (\Throwable $e) {
            throw new Exception('JsonException: ' . $e->getMessage());
        }
    }
}
