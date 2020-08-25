<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/11 0011
 * Time: 17:35
 */
namespace App\Components;

use Illuminate\Support\Facades\Redis;

class RedisLimit
{
	//限制单个ip的流量
	public function limit_ip(){
		//接口时间限流，这种方式可以防止钻时间漏洞无限的访问接口 比如在59秒的时候访问，就钻了空子
		$ip = $this->get_client_ip(true);
		$len = Redis::lLen($ip);
		if($len === 0)
		{
			Redis::lPush($ip,time());
			Redis::expire($ip,30);
			return true;
		}else{
			//判断有没有超过1分钟
			$max_time = Redis::lRange($ip,0,0);
			//判断最后一次访问的时间比对是否超过了1分钟
			if((time()- $max_time[0]) < 30){
				if($len > 3){
					//return false;
					echo '访问超过了限制';exit;
				}else{
					Redis::lPush($ip,time());
					//return true;
					echo "访问{$len}次<br>";exit;
				}
			}else{
				Redis::del($ip);
				return true;
			}
		}
	}

	//限制总人数
	public function limit_total(){
		$key = 'total';
		$len = Redis::lLen($key);
		if($len === 0)
		{
			Redis::lPush($key,time());
			Redis::expire($key,5);
			return true;
		}else{
			$max_time = Redis::lRange($key,0,0);
			//判断最后一次访问的时间比对是否超过了1分钟
			if((time()- $max_time[0]) < 5){
				if($len > 100){
					return false;
					echo '访问超过了限制';exit;
				}else{
					Redis::lPush($key,time());
					return true;
					echo "访问{$len}次<br>";exit;
				}
			}else{
				Redis::del($key);
				return true;
			}
		}
	}

	public function get_client_ip($type = 0) {
		$type       =  $type ? 1 : 0;
		static $ip  =   NULL;
		if ($ip !== NULL) return $ip[$type];
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$pos    =   array_search('unknown',$arr);
			if(false !== $pos) unset($arr[$pos]);
			$ip     =   trim($arr[0]);
		}elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip     =   $_SERVER['HTTP_CLIENT_IP'];
		}elseif (isset($_SERVER['REMOTE_ADDR'])) {
			$ip     =   $_SERVER['REMOTE_ADDR'];
		}
		// IP地址合法验证
		$long = ip2long($ip);
		$ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
		return $ip[$type];
	}


}
