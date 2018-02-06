<?php

namespace ShellExecutor\Common;

/**
 * Time controller.
 *
 * @package ShellExecutor\Common
 */
class Timer {

  const MIKROSECONDS_IN_SECOND = 1000000;

  /**
   * Timeout for a execution in seconds.
   *
   * @var int
   */
  protected $timeout = 5;

  /**
   * Sleep time in mikroseconds.
   *
   * @var int
   */
  protected $sleepTime = 100000;

  /**
   * Provides current timeout time
   *
   * @return int
   */
  public function getTimeout() {
    return $this->timeout;
  }

  /**
   * Sets timeout time.
   *
   * @param int $timeout
   *   Time in seconds.
   */
  public function setTimeout($timeout) {
    $this->timeout = $timeout;
  }


  /**
   * Convert seconds to mikroseconds.
   *
   * @param $seconds
   *
   * @return float|int
   */
  protected function secondsToMikroSeconds($seconds) {
    return $seconds * static::MIKROSECONDS_IN_SECOND;
  }

  /**
   * Loop time
   *
   * @return float|int
   */
  public function getLoopTime() {
    return $this->secondsToMikroSeconds($this->timeout) / $this->sleepTime;
  }

  public function sleep() {
    usleep($this->sleepTime);
  }

}
