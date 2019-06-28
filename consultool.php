<?php
/**
 * Created by Afei.
 * User: feiwang
 * Date: 2019-06-23
 * Time: 08:35
 *
 * Consul工具类 注册 取消注册服务
 *
 * *依赖
 * *center.ini
 * *生成的config.ini
 *
 */

define("PROJECT_ROOT", realpath(dirname(dirname(__FILE__))));

$config = parse_ini_file(PROJECT_ROOT . DIRECTORY_SEPARATOR . 'center.ini');

//存储配置文件名
define("CONFIG_FILE", DIRECTORY_SEPARATOR . $config['localconffilename']);

class ConsulToolClass
{
    //consul service config
    public $ip = "127.0.0.1";
    public $port = "8500";

    //local instance config
    public $config = '';

    public $serviceid = '';
    public function __construct()
    {
        $this->config = $this->getlocalconfiginfo();
        $this->serviceid = $this->config['system.versionid']."--".$this->config['system.instanceid'];
    }

    /**
     * 注册服务
     * @param $json
     * @return mixed
     */
    public function registerService($json)
    {
        return $this->curlPUT("/v1/agent/service/register", $json);
    }

    /**
     * 销毁服务
     * @param $service_id
     * @return mixed
     */
    public function deregisterService()
    {

        return $this->curlPUT("/v1/agent/service/deregister/".$this->serviceid, null);
    }

    /**
     * PUT请求
     * @param $request_uri
     * @param $data
     * @return mixed
     */
    public function curlPUT($request_uri, $data)
    {
        $ch = curl_init();
        $header[] = "Content-type:application/json";
        curl_setopt($ch, CURLOPT_URL, "http://" . $this->ip . ":" . $this->port . $request_uri);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /**
     * 返回注册本地实例信息
     * @return array
     */
    public function services()
    {
        return array(

            //配置实例id
            "ID" => $this->serviceid,
            //配置功能件id
            "Name" => $this->config['system.id'],
            "Tags" => array("primary"),
            "Address" => "127.0.0.1",
            "Port" => 7001,
            "Check" => array(
                "HTTP" => "http://127.0.0.1:7001/",
                "Interval" => "5s"
            )

        );
    }

    /**
     * 获取项目本地文件
     * @return array|bool
     */
    public function getlocalconfiginfo()
    {
        if (is_file(PROJECT_ROOT . CONFIG_FILE)) {
            return parse_ini_file(PROJECT_ROOT . CONFIG_FILE);
        } else {
            return false;
        }
    }

    public function run(){
        return $this->registerService(json_encode($this->services()));
    }
}

//命令行参数  php consultool.php [register] [deregister]
$arg = $_SERVER['argv'];
if (empty($arg[1])||(($arg[1]!=='register')&&($arg[1]!=='deregister'))){
    echo "you input $arg[1]".PHP_EOL;
    echo "please input register or deregister".PHP_EOL;exit;
}
if ($arg[1] == 'register'){
    $consulserver = new ConsulToolClass();
    $re = $consulserver->run();
    if (empty($re)){
        echo "register success".PHP_EOL;

    }else{
        echo "redister error ".$re.PHP_EOL;
    }
}elseif($arg[1] == 'deregister'){
    $consulserver = new ConsulToolClass();
    $re = $consulserver->deregisterService();
    if (empty($re)){
        echo "deregister success".PHP_EOL;

    }else{
        echo "deredister error ".$re.PHP_EOL;
    }
}
