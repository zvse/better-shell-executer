<?php

namespace ShellExecutor\Exceptions;


class UniqueIdAlreadyExistsException extends ShellExecutorExceptionBase {

  public function __construct($uniqueId) {
    parent::__construct("Unique Id already exists for the files ($uniqueId");
  }

}
