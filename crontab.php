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

    /**
     * @access private
     * @var string
     */
    private $sudo = "";
    
    /**
     * @access private
     * @var string
     */
    private $usernameRoot = "root";
    
    /**
     * @access private
     * @var string
     */
    private $passwordRoot = "";

    /**
     * Location of the crontab executable
     * @access private
     * @var string
     */
    private $crontab = "/usr/bin/crontab";

    /**
     * Location to save the crontab file.
     * @access private
     * @var string
     */
    private $destination = "/tmp/cronManager";
    
    /**
     * The user executing the comment 'crontab'
     * @access private
     * @var string
     */
    private $user = null;
    
    /*
     * @access private
     * @var bool
     */
    private $useUser = false;

    /**
     * @access private
     * @var $regex
     */
    private static $regex = array(
        "user" => "/^[a-z][\_\-A-Za-z0-9]*$/",
        "minute" => "/[\*,\/\-0-9]+/",
        "hour" => "/[\*,\/\-0-9]+/",
        "dayOfMonth" => "/[\*,\/\-\?LW0-9A-Za-z]+/",
        "month" => "/[\*,\/\-0-9A-Z]+/",
        "dayOfWeek" => "/[\*,\/\-0-9A-Z]+/",
        "command" => "/^(.)*$/",
    );

    /**
     * Minute (0 - 59)
     * @access private
     * @var string
     */
    private $minute = 10;

    /**
     * Hour (0 - 23)
     * @access private
     * @var string
     */
    private $hour = "*";

    /**
     * Day of Month (1 - 31)
     * @access private
     * @var string
     */
    private $dayOfMonth = "*";

    /**
     * Month (1 - 12) OR jan,feb,mar,apr...
     * @access private
     * @var string
     */
    private $month = "*";

    /**
     * Day of week (0 - 6) (Sunday=0 or 7) OR sun,mon,tue,wed,thu,fri,sat
     * @access private
     * @var string
     */
    private $dayOfWeek = "*";
    
    /**
     * @access private
     * @var string
     */
    private $file_output = null;

    /**
     * @access private
     * @var array
     */
    private $jobs = array();
	
	/**
	 * @acces private
	 * @var string
	 */
	private $id_token = null;

	/**
     * Constructor
     */
    public function __construct($id_token) {
		$this->id_token = $id_token;
        $out = $this->exec("whoami");
        if(isset($out[0])) {
			$user = $out[0];
			$this->setUser($user);
			$this->exec("{$this->sudo} cat /var/spool/cron/crontabs/{$this->user}");
		}
    }
    
    /**
     * Destrutor
     */
    public function __destruct() {
        if ($this->destination && is_file($this->destination)) {
            @unlink($this->destination);
        }
    }
    
    /**
     * Set username and password root
     * @param $username Username Root
     * @param $password Password Root
     * @return void
     */
    public function setUsernamePasswordRoot($username, $password) {
        if(!empty($username)) {
            $this->usernameRoot = $username;
        }
        if(!empty($password)) {
            $this->passwordRoot = $password;
        }
        $this->sudo = "echo \"{$this->passwordRoot}\" | sudo -u {$this->usernameRoot} ";
    }
	
	/**
	 * Get username Root
	 * @return string
	 */
	public function getUsernameRoot() {
		return $this->usernameRoot;
	}
	
	/**
	 * Get password Root
	 * @return string
	 */
	public function getPasswordRoot() {
		return $this->passwordRoot;
	}

    /**
     * Method exec 
     * @param string $cmd
     * @param bool $debug
     * @return array
     */
    public function exec($cmd, $debug = false) {
        $output = array();
        $return_var = -1;
        if(!empty($cmd)) {
            exec($cmd, $output, $return_var);
            if($debug == true) {
                debug(array("cmd" => $cmd, "output" => $output, "return_var" => $return_var));
            }
        }
        return $output;
    }

    /**
     * Method __toString
     * @return string
     */
    public function __toString() {
        return print_r($this, true);
    }

    /**
     * Set minute or minutes
     * @param string $minute required
     * @return object
     */
    public function onMinute($minute) {
        if (preg_match(self::$regex["minute"], $minute)) {
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
        if (preg_match(self::$regex["hour"], $hour)) {
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
        if (preg_match(self::$regex["dayOfMonth"], $dayOfMonth)) {
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
        if (preg_match(self::$regex["month"], $month)) {
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
        if (preg_match(self::$regex["dayOfWeek"], $dayOfWeek)) {
            $this->dayOfWeek = $dayOfWeek;
        }
        return $this;
    }
    
    /**
     * Set the user owner of the crontab
     * @param string $user required
     * @return object
     */
    public function setUser($user) {
        if(preg_match(self::$regex["user"], $user)) {
            $this->user = $user;
        }
        return $this;
    }
    
    /**
     * Set if is used the user in the cron job
     * @param bool $use required
     * @return object
     */
    public function setUseUser($use) {
        if(is_bool($use)) {
            $this->useUser = $use;
        }
        return $this;
    }
    
    /**
     * Set output file
     * @param string $file_output required
     * @return object
     */
    public function setFileOutput($file_output) {
        $this->file_output = $file_output;
        return $this;
    }

	/**
	 * Get jobs list
	 * @return array
	 */
	public function getJobs() {
		return $this->jobs;
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
            ) = explode(" ", $timeCode);
        
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
        if (preg_match(self::$regex["command"], $command)) {
            $this->jobs[] = $this->minute . " " .
                    $this->hour . " " .
                    $this->dayOfMonth . " " .
                    $this->month . " " .
                    $this->dayOfWeek . " " .
                    $command .
                    ($this->file_output != null ?  " >> {$this->file_output} 2>&1" : "");
        }
        return $this;
    }
	
	/**
     * Render to string method
     * 
	 * @param array $newContents
	 * @param array|null $oldContents
     * @return string
     */
	public function render($newContents, $oldContents = null) {
		$contents = "";
		
		if($oldContents != null && is_array($oldContents)) {
			$beginIndex = $endIndex = -1;
			foreach($oldContents as $key => $val) {
				if(isset($newContents[0]) && $val == $newContents[0]) {
					$beginIndex = $key;
				}
				if(isset($newContents[count($newContents)-1]) && $val == $newContents[count($newContents)-1]) {
					$endIndex = $key;
				}
			}

			if($beginIndex > -1 && $endIndex > -1) {
				foreach(range($beginIndex, $endIndex) as $index) {
					unset($oldContents[$index]);
				}
			}
			
			$contents .= implode(PHP_EOL, $oldContents);
			$contents .= PHP_EOL;
		}
		
		$contents .= implode(PHP_EOL, $newContents);
		$contents .= PHP_EOL;
		
		return $contents;
	}

    /**
     * Save the jobs to disk, remove existing cron
     * @param bool $includeOldJobs optional
     * @return bool
     */
    public function activate($includeOldJobs = true) {
		$newContents = array();
		$newContents[] = sprintf("# BEGIN %s", ($this->id_token ? $this->id_token : ""));
        foreach($this->jobs as $key => $val) {
			$newContents[] = $val;
		}
        $newContents[] = sprintf("# END %s", ($this->id_token ? $this->id_token : ""));

		$oldContents = null;
        if ($includeOldJobs) {
            $oldContents = $this->listJobs();
        }
		
		$contents = $this->render($newContents, $oldContents);
        
        @chmod($this->destination, 0755);
        if (is_writable($this->destination) || !file_exists($this->destination)) {
            $this->exec("{$this->sudo} " . $this->crontab . ($this->useUser ? " -u {$this->user} " : "") . "-r");

            file_put_contents($this->destination, $contents, LOCK_EX);
            $this->exec("{$this->sudo} " . $this->crontab . ($this->useUser ? " -u {$this->user} " : ""). "{$this->destination}");
            return true;
        }

        return false;
    }

    /**
     * List current cron jobs
     * @return string
     */
    public function listJobs() {
        $output = $this->exec("{$this->sudo} " . $this->crontab . ($this->useUser ? " -u {$this->user} " : "") . "-l");
        return $output;
    }

}
