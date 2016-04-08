<?php
/**
 * 自定义 ID 生成器
 * ID 生成规则: ID长达 64 bits
 *
 * | 41 bits: Timestamp (毫秒) | 3 bits: 区域（机房） | 10 bits: 机器编号 | 10 bits: 序列号 |
 */
class CustomUUID {
	// 基准时间
	private  $twepoch = 1288834974657; //Thu, 04 Nov 2010 01:42:54 GMT
	// 区域标志位数
	private  static $regionIdBits = 3;
	// 机器标识位数
	private  static $workerIdBits = 10;
	// 序列号识位数
	private  static $sequenceBits = 10;

	
	
	private static $lastTimestamp = -1;

	private  $sequence = 0;
	private   $workerId;
	private  $regionId;

	
	// 区域标志ID最大值
	private  static $maxRegionId ;
	// 机器ID最大值
	private  static $maxWorkerId ;
	// 序列号ID最大值
	private  static $sequenceMask ;
	
	// 机器ID偏左移10位
	private  static $workerIdShift ;
	// 业务ID偏左移20位
	private  static $regionIdShift ;
	// 时间毫秒左移23位
	private  static $timestampLeftShift ;
	
	
	function  init() {
		// 区域标志ID最大值
		self::$maxRegionId = -1 ^ (-1 << self::$regionIdBits);
		// 机器ID最大值
		self::$maxWorkerId = -1 ^ (-1 << self::$workerIdBits);
		// 序列号ID最大值
		self::$sequenceMask = -1 ^ (-1 << self::$sequenceBits);
		
		// 机器ID偏左移10位
		self::$workerIdShift = self::$sequenceBits;
		// 业务ID偏左移20位
		self::$regionIdShift = self::$sequenceBits + self::$workerIdBits;
		// 时间毫秒左移23位
		self::$timestampLeftShift = self::$sequenceBits + self::$workerIdBits + self::$regionIdBits;
		
		
	}
	
	function  __construct($workerId, $regionId) {

		// 如果超出范围就抛出异常
		if ($workerId > self::$maxWorkerId || $workerId < 0) {
			//throw new IllegalArgumentException("worker Id can't be greater than %d or less than 0");
		}
		if ($regionId > self::$maxRegionId || $regionId < 0) {
			//throw new IllegalArgumentException("datacenter Id can't be greater than %d or less than 0");
		}

		$this->workerId = $workerId;
		$this->regionId = $regionId;
	}
	
	//

	function  generate() {
		return $this->nextId(false, 0);
	}

	/**
	 * 实际产生代码的
	 *
	 * @param isPadding
	 * @param busId
	 * @return
	 */
	private function   nextId( $isPadding,  $busId) {

		 $timestamp = $this->timeGen();
		 $paddingnum = $this->regionId;

		if ($isPadding) {
			$paddingnum = $busId;
		}

		if ($timestamp < self::$lastTimestamp) {
			try {
				throw new Exception("Clock moved backwards.  Refusing to generate id for " + (lastTimestamp - timestamp) + " milliseconds");
			} catch (Exception $e) {
				echo $e->getTrace();
			}
		}

		//如果上次生成时间和当前时间相同,在同一毫秒内
		if (self::$lastTimestamp == $timestamp) {
			//sequence自增，因为sequence只有10bit，所以和sequenceMask相与一下，去掉高位
			$this->sequence = ($this->sequence + 1) & self::$sequenceMask;
			//判断是否溢出,也就是每毫秒内超过1024，当为1024时，与sequenceMask相与，sequence就等于0
			if ($this->sequence == 0) {
				//自旋等待到下一毫秒
				$timestamp = $this->tailNextMillis(self::$lastTimestamp);
			}
		} else {
			// 如果和上次生成时间不同,重置sequence，就是下一毫秒开始，sequence计数重新从0开始累加,
			// 为了保证尾数随机性更大一些,最后一位设置一个随机数
			//$sequence = new SecureRandom().nextInt(10);
			$this->sequence = rand(1, 10);
		}

		self::$lastTimestamp = $timestamp;

		return (($timestamp - $this->twepoch) << self::$timestampLeftShift) | ($paddingnum << self::$regionIdShift) | ($this->workerId << self::$workerIdShift) | $this->sequence;
	}

	// 防止产生的时间比之前的时间还要小（由于NTP回拨等问题）,保持增量的趋势.
	private function tailNextMillis(  $lastTimestamp) {
		$timestamp = $this->timeGen();
		while ($timestamp <= $lastTimestamp) {
			$timestamp = $this->timeGen();
		}
		return $timestamp;
	}

	// 获取当前的时间戳
	protected function  timeGen() {
		//return System.currentTimeMillis();
		return round(microtime(true)*1000,0);
	}
}