<?php

namespace Faj1\Push;

class LogSender
{



    private static string $StaticServerUrl = "";


    public static  function sendLogData(\Exception $exception, array $context = [], string $level = 'ERROR', string $application = 'default_app', string $environment = 'production'): void
    {
        $logData = [
            'log_date'    => date('Y-m-d'),
            'timestamp'   => date('Y-m-d H:i:s'),
            'level'       => $level,
            'message'     => $exception->getMessage(),
            'application' => $application,
            'module'      => null,
            'logger'      => null,
            'thread'      => null,
            'host'        => gethostname(),
            'user_id'     => $context['user_id'] ?? null,
            'request_id'  => $context['request_id'] ?? null,
            'context'     => json_encode($context),
            'exception'   => $exception->getTraceAsString(),
            'environment' => $environment,
            'file_name'   => $exception->getFile(),
            'line_number' => $exception->getLine(),
        ];
        if(!self::$StaticServerUrl){
            // 获取用户项目的根目录路径
            $projectRoot = dirname(__DIR__, 2); // 根据实际路径调整，假设当前库代码在项目的 vendor/your-vendor/your-package/ 目录下
            var_dump($projectRoot);
            $configFile = $projectRoot . '/LogConfig.json';

            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('配置文件 config.json 格式错误: ' . json_last_error_msg());
                }
                // 使用配置数据
                var_dump($config);
            } else {
                throw new \RuntimeException('配置文件 config.json 不存在。');
            }
        }











    }







    private string $serverUrl; // 服务器接收日志的接口地址

    /**
     * 构造函数
     * @param string $serverUrl 日志接收服务器的 URL（例如：http://example.com/api/logs）
     */
    public function __construct(string $serverUrl)
    {
        $this->serverUrl = $serverUrl;
    }

    /**
     * 发送日志
     *
     * @param array $logData 日志数据，必须是键值对数组，键名对应数据库字段
     */
    public function sendLog(array $logData): void
    {
        try {
            // 异步发送 HTTP 请求
            $this->sendAsyncRequest($this->serverUrl, $logData);
        } catch (\Exception $e) {
            // 捕捉任何异常，不做任何错误处理，保证系统不中断
        }
    }

    /**
     * 异步发送 HTTP 请求（使用 cURL）
     * @param string $url 目标地址
     * @param array $data 发送的数据
     */
    private function sendAsyncRequest(string $url, array $data): void
    {
        // 初始化 cURL 会话
        $ch = curl_init();
        // 配置请求选项
        curl_setopt($ch, CURLOPT_URL, $url); // 请求地址
        curl_setopt($ch, CURLOPT_POST, true); // POST 请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // 使用 JSON 格式发送数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // 不需要返回响应内容
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100); // 超时时间（异步场景下设置尽量短）
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json', // 设置请求头，标明内容类型为 JSON
        ]);
        // 使用非阻塞模式发送请求
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        // 执行 cURL 请求
        curl_exec($ch);
        // 忽略请求结果和可能出现的错误，确保不抛出异常
        curl_close($ch);
    }

}
