<?php

namespace Faj1\Push;

use Swoole\Coroutine\Client;
use function Swoole\Coroutine\run;

class LogPush
{


    private string $serverUrl;
    private array $UdpServer; // 服务器接收日志的接口地址

    private bool $debug = false;

    /**
     * 构造函数
     * @param string $serverUrl 日志接收服务器的 URL（例如：http://example.com/api/logs）
     */
    public function __construct(string $serverUrl, array $UdpServer,$debug)
    {
        $this->serverUrl = $serverUrl;
        $this->UdpServer = $UdpServer;
        $this->debug = $debug;

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

    public function UdpSendLog(array $logData): void
    {
        if($this->debug){
            echo '服务器链接:'.$this->UdpServer['host']. PHP_EOL;
            echo '服务器端口:'.$this->UdpServer['port']. PHP_EOL;
            echo "发送的内容文本:".json_encode($logData).PHP_EOL;
        }
        try {
            // 压缩数据
            $compressed = gzcompress(json_encode($logData));
            echo "压缩后的数据: " . $compressed . "\n";
            run(function () use ($compressed) {
                $client = new Client(SWOOLE_SOCK_UDP);
                if (!$client->connect($this->UdpServer['host'], $this->UdpServer['port'], 0.5))
                {
                    echo "connect failed. Error: {$client->errCode}\n";
                }
                $client->send($compressed);
                echo $client->recv();
                $client->close();
            });
        } catch (\Exception $e) {
            // 捕捉任何异常，不做任何错误处理，保证系统不中断
            if($this->debug){
                echo "connect failed. Error: ".$e->getMessage()."\n";
            }
        }

    }

}
