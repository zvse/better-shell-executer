<?php

namespace ShellExecutor;

use Exception;
use ShellExecutor\Exceptions\ProcessWasNotCreatedException;
use ShellExecutor\Exceptions\ShellExecutorExceptionBase;
use ShellExecutor\Common\Files;
use ShellExecutor\Common\Timer;
use ShellExecutor\Exceptions\TimeoutException;

/**
 * Executes a shell command and kills it if it runs too long
 *
 * Based on the Peeter Tomberg library
 * https://github.com/peeter-tomberg/php-shell-executer
 */
class ShellExecutor {

  const PIPE_STDIN = 0;
  const PIPE_STDOUT = 1;
  const PIPE_STDERR = 2;

  const DEFAULT_EXECUTE_TIMEOUT = 5;

  /**
   * Command to execute
   *
   * @var String
   */
  private $command;

  /**
   * This points to the process we start
   **/
  private $resource;

  /**
   * This is where we write different files
   **/
  private $files;

  /**
   * Descriptor spec
   *
   * @var array
   */
  protected $descriptorSpec = [
    self::PIPE_STDOUT => ['pipe', 'w'],
    self::PIPE_STDOUT => ['pipe', 'w'],
  ];

  public $pipes;

  /**
   * Timer object.
   *
   * @var \ShellExecutor\Common\Timer
   */
  private $timer;

  /**
   * Determines if current process is running.
   *
   * @var boolean
   */
  private $processIsRunning = FALSE;

  /**
   *
   * Construct the ShellExecuter
   *
   * @param string $command - the command to execute
   * @param integer $timeout - the default timeout
   */
  function __construct($command, $timeout = self::DEFAULT_EXECUTE_TIMEOUT) {
    $this->command = $command;
    $this->timer = new Timer();
    $this->files = new Files();
  }

  public function getTimer() {
    return $this->timer;
  }

  /**
   * Provides full command line.
   *
   * @return string
   *   Command to execute.
   */
  protected function getFullCommand() {
    $successFile = $this->files->getSuccess();
    $processIdFile = $this->files->getProcessId();
    $command = $this->command;

    // Execute command and create success file. write process id into the
    // process ID file. Run it background.
    return "( $command && touch $successFile) & echo $! > $processIdFile &";
  }

  /**
   * Provides descriptor spec array.
   *
   * @return array
   *   Descriptor spec array.
   */
  protected function getDescriptorSpec() {
    // 0 - STDIN, 1 - STDOUT, 2 STDERR.
    return $this->descriptorSpec;
  }

  /**
   * Executes the command (blocking)
   *
   * @throws Exception when timeout reached
   */
  public function execute() {
    try {
      $this->resource = $this->runProcess();

      $looptime = $this->timer->getLoopTime();
      for ($i = 0; $i <= $looptime; $i++) {

        if ($this->isRunning()) {
          if ($i == $looptime) {
            $this->kill();
          }

          $this->timer->sleep();      }
        else {
          break;
        }
      }

      // Get data from the pipes.
      $stdout = stream_get_contents($this->pipes[1]);
      $stderr = stream_get_contents($this->pipes[2]);

      // If we didn't touch the success file, the processes executed with a
      // failure.
      if (!file_exists($this->files["success"])) {
        throw new Exception("Command executed with failure: " . $stderr);
      }

      return $stdout;
    }
    catch (ShellExecutorExceptionBase $exception) {
      // Delete created files.
      $this->files->cleanup();
      // Kill opened process.
      if ($this->resource !== NULL) {
        proc_terminate($this->resource);
      }

      throw $exception;
    }
  }

  /**
   * Run process with the all the collected data. Also makes sure that
   * process is running.
   *
   * @return resource
   *   Process resource.
   */
  protected function runProcess() {
    $resource = proc_open(
      $this->getFullCommand(),
      $this->getDescriptorSpec(),
      $this->pipes,
      NULL,
      $_ENV
    );

    return $resource;
  }

  /**
   * Lets get the actual pid of the process we're backgrounding.
   *
   * @return int
   *   Process identifier.
   *
   * @throws \ShellExecutor\Exceptions\ProcessWasNotCreatedException
   *   If max attempts reached and we did not get a process id.
   */
  protected function getProcessId() {
    $maxAttempts = 200;

    for ($currentAttempt = 0; $currentAttempt <= $maxAttempts; $currentAttempt ++) {
      $currentAttempt++;

      if (file_exists($this->files->getProcessId())) {
        $pid = (int) file_get_contents($this->files->getProcessId());
        if ($pid > 0) {
          return $pid;
        }
      }
      else {
        if ($currentAttempt === $maxAttempts) {
          throw new ProcessWasNotCreatedException();
        }

        // Since PHP doesn't run our process right away, lets sleep until we
        // actually have a pid.
        $this->timer->sleep();
        // We don't run this process - we can set it as FALSE.
        $this->processIsRunning = FALSE;
      }
    }
    throw new ProcessWasNotCreatedException();
  }

  /**
   * Determines if the pid is running
   *
   * @throws \ShellExecutor\Exceptions\ProcessWasNotCreatedException
   *   In case if process ID not found.
   */
  private function isRunning() {
    $pid = $this->getProcessId();

    if ($pid == NULL) {
      return FALSE;
    }

    return file_exists("/proc/$pid");
  }

  /**
   * Kills this process.
   *
   * @throws \ShellExecutor\Exceptions\TimeoutException
   *   That means that timeout reached and process stopped.
   *
   * @throws \ShellExecutor\Exceptions\ProcessWasNotCreatedException
   *   In case if process ID file is empty.
   */
  private function kill() {
    $processId = $this->getProcessId();
    if ($this->resource !== NULL ) {
      proc_terminate($this->resource);
      shell_exec("kill -9 " . $processId);
    }

    throw new TimeoutException($processId, $this->getFullCommand());
  }

  /**
   * Setter for the descriptor spec.
   *
   * @param array $descriptorSpec
   *   See available values on https://php.net/manual/en/function.proc-open.php
   */
  public function setDescriptorSpec($descriptorSpec) {
    $this->descriptorSpec = $descriptorSpec;
  }

}
