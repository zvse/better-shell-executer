<?php

namespace ShellExecutor\Exceptions;


class ProcessWasNotCreatedException extends ShellExecutorExceptionBase {

  public function __construct() {
    parent::__construct('Max attempts count reached, cannot create process. Please check system log.');
  }

}
