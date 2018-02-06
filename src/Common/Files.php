<?php

namespace ShellExecutor\Common;

use ShellExecutor\Exceptions\UniqueIdAlreadyExistsException;

/**
 * Files controller.
 *
 * @package ShellExecutor\Common
 */
class Files {

  /**
   * Prefix string for all the files.
   */
  const FILES_PREFIX = 'php_shell_executor';

  /**
   * Files work directory.
   *
   * @var string
   */
  protected $directory;

  /**
   * Unique ID for this files.
   *
   * @var string
   */
  private $uniqueId;

  public function __construct() {
    $this->directory = sys_get_temp_dir();
  }

  /**
   * Success file path.
   *
   * @return string
   */
  public function getSuccess() {
    return $this->getFilePath('success');
  }

  /**
   * Success process ID file path.
   *
   * @return string
   */
  public function getProcessId() {
    return $this->getFilePath('process_id');
  }

  /**
   * File path by the type.
   *
   * @param string $fileType
   *
   * @return string
   */
  protected function getFilePath($fileType) {
    $directoryName = $this->getDirectory();
    $fileName = static::FILES_PREFIX . '__' . $fileType .'__' . $this->getUniqueId();

    return "$directoryName/$fileName";
  }

  /**
   * Provides work directory for the files.
   *
   * @return string
   *   Work directory.
   */
  public function getDirectory() {
    return $this->directory;
  }

  /**
   * @param string $directory
   */
  public function setDirectory($directory) {
    $this->directory = $directory;
  }

  /**
   * Provides unique ID for the files. If not exists - it will be created.
   *
   * @Important
   *   Once UniqueId created it cannot be changed.
   *
   * @return string
   *   Files unique ID.
   */
  public function getUniqueId() {
    if (empty($this->uniqueId)) {
      $this->uniqueId = uniqid();
    }

    return $this->uniqueId;
  }

  /**
   * Sets unique ID for the files.
   *
   * @param mixed $uniqueId
   *   Unique ID string.
   *
   * @throws \ShellExecutor\Exceptions\UniqueIdAlreadyExistsException
   *   In case if it already exists.
   */
  public function setUniqueId($uniqueId) {
    if ($this->uniqueId) {
      throw new UniqueIdAlreadyExistsException($this->uniqueId);
    }

    $this->uniqueId = $uniqueId;
  }

  /**
   * Delete all the files.
   */
  public function cleanup() {
    @unlink($this->getProcessId());
    @unlink($this->getUniqueId());
  }

}
