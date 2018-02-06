<?php

namespace ShellExecutor\Exceptions;

/**
 * Class ShellExecuterTimeoutException
 *
 * @package ShellExecuter\Exceptions
 */
class TimeoutException extends ShellExecutorExceptionBase {

  public function __construct($processId, $command) {
    $message = "Exec timeout reached, process ($processId) killed. Command: $command";

    parent::__construct($message);
  }

}
