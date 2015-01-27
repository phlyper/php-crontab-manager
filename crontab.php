<?php

/**
 * @author Ryan Faerman <ryan.faerman@gmail.com>
 * @version 0.1
 * @package PHPCronTab
 *
 * Copyright (c) 2009 Ryan Faerman <ryan.faerman@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */
class Crontab {

	public $sudo = ''; //'sudo -u root';

	/**
	 * Location of the crontab executable
	 * @var string
	 */
	public $crontab = '/etc/crontab';

	/**
	 * Location to save the crontab file.
	 * @var string
	 */
	public $destination = '/tmp/cronManager';

	/**
	 * @var $regex
	 */
	public static $regex = array(
		'minute' => '/[\*,\/\-0-9]+/',
		'hour' => '/[\*,\/\-0-9]+/',
		'dayOfMonth' => '/[\*,\/\-\?LW0-9A-Za-z]+/',
		'month' => '/[\*,\/\-0-9A-Z]+/',
		'dayOfWeek' => '/[\*,\/\-0-9A-Z]+/',
		'user' => '/^[a-z][\_\-a-z0-9]*$/',
		'command' => '/^(.)*$/',
	);

	/**
	 * Minute (0 - 59)
	 * @var string
	 */
	public $minute = 0;

	/**
	 * Hour (0 - 23)
	 * @var string
	 */
	public $hour = 10;

	/**
	 * Day of Month (1 - 31)
	 * @var string
	 */
	var $dayOfMonth = '*';

	/**
	 * Month (1 - 12) OR jan,feb,mar,apr...
	 * @var string
	 */
	public $month = '*';

	/**
	 * Day of week (0 - 6) (Sunday=0 or 7) OR sun,mon,tue,wed,thu,fri,sat
	 * @var string
	 */
	public $dayOfWeek = '*';
	
	public $user = 'root';
	
	public $file_output = null;

	/**
	 * @var array
	 */
	public $jobs = array();

	public function __construct() {
		$out = array();
		exec('whoami', $out);
		debug($out);
		$user = $out[0];
		$out = array();
		exec($this->sudo . " ls /var/spool/cron/contabs/{$user}", $out);
		debug($out);
		if (empty($out)) {
			$out = array();
			exec($this->sudo . " touch /var/spool/cron/contabs/{$user}", $out);
			debug($out);
		}
		$out = array();
		exec($this->sudo . " ls /var/spool/cron/contabs/{$user}", $out);
		debug($out);
	}

	public function __toString() {
		pr($this, true);
		return "";//print_r($this, true);
	}

	/**
	 * Set minute or minutes
	 * @param string $minute required
	 * @return object
	 */
	public function onMinute($minute) {
		if (preg_match(self::$regex['minute'], $minute)) {
			$this->minute = $minute;
		}
		return $this;
	}

	/**
	 * Set hour or hours
	 * @param string $hour required
	 * @return object
	 */
	public function onHour($hour) {
		if (preg_match(self::$regex['hour'], $hour)) {
			$this->hour = $hour;
		}
		return $this;
	}

	/**
	 * Set day of month or days of month
	 * @param string $dayOfMonth required
	 * @return object
	 */
	public function onDayOfMonth($dayOfMonth) {
		if (preg_match(self::$regex['dayOfMonth'], $dayOfMonth)) {
			$this->dayOfMonth = $dayOfMonth;
		}
		return $this;
	}

	/**
	 * Set month or months
	 * @param string $month required
	 * @return object
	 */
	public function onMonth($month) {
		if (preg_match(self::$regex['month'], $month)) {
			$this->month = $month;
		}
		return $this;
	}

	/**
	 * Set day of week or days of week
	 * @param string $dayOfWeek required
	 * @return object
	 */
	public function onDayOfWeek($dayOfWeek) {
		if (preg_match(self::$regex['dayOfWeek'], $dayOfWeek)) {
			$this->dayOfWeek = $dayOfWeek;
		}
		return $this;
	}
	
	public function setUser($user) {
		if(preg_match(self::$user, $user)) {
		$this->user = $user;
		}
		return this;
	}
	
	public function setFileOutput($file_output) {
		$this->file_output = $file_output;
		return $this;
	}

	/**
	 * Set entire time code with one public function. 
	 * This has to be a complete entry. 
	 * See http://en.wikipedia.org/wiki/Cron#crontab_syntax
	 * @param string $timeCode required
	 * @return object
	 */
	public function on($timeCode) {
		list(
				$minute,
				$hour,
				$dayOfMonth,
				$month,
				$dayOfWeek
				) = explode(' ', $timeCode);
		$this->onMinute($minute)
				->onHour($hour)
				->onDayOfMonth($dayOfMonth)
				->onMonth($month)
				->onDayOfWeek($dayOfWeek);

		return $this;
	}

	/**
	 * Add job to the jobs array. Each time segment should be set before calling this method. The job should include the absolute path to the commands being used.
	 * @param string $command required
	 * @return object
	 */
	public function doJob($command) {
		if (preg_match(self::$regex['command'], $command)) {
			$this->jobs[] = $this->minute . ' ' .
					$this->hour . ' ' .
					$this->dayOfMonth . ' ' .
					$this->month . ' ' .
					$this->dayOfWeek . ' ' .
					$this->user . ' ' .
					$command.
					($this->file_output != null ?  " >> {$this->file_output} 2>&1" : "");
		}
		return $this;
	}

	/**
	 * Save the jobs to disk, remove existing cron
	 * @param boolean $includeOldJobs optional
	 * @return boolean
	 */
	public function activate($includeOldJobs = true) {
		$contents = implode("\n", $this->jobs);
		$contents .= "\n";

		if ($includeOldJobs) {
			$contents .= $this->listJobs();
		}

		if (is_writable($this->destination) || !file_exists($this->destination)) {
			$out = array();
			exec($this->sudo . ' ' . $this->crontab . ' -r', $out);
			debug($out);
			file_put_contents($this->destination, $contents, LOCK_EX);
			exec($this->sudo . ' ' . $this->crontab . ' ' . $this->destination, $out);
			debug($out);
			return true;
		}

		return false;
	}

	/**
	 * List current cron jobs
	 * @return string
	 */
	public function listJobs() {
		$out = array();
		exec($this->sudo . ' ' . $this->crontab . ' -l', $out);
		return $out;
	}

}
