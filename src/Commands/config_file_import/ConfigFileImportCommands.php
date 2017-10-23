<?php

namespace Drush\Commands\config_file_import;

use Drupal\Core\Serialization\Yaml;
use Drush\Commands\DrushCommands;

class ConfigFileImportCommands extends DrushCommands {

  /**
   * Import default images and embed buttons.
   *
   * @command config:file:import
   * @param string $file YAML File which contains the files to import.
   * @aliases config-file-import
   * @bootstrap full
   */
  public function fileImport($file)
  {
    if (!file_exists($file)) {
      return drush_set_error('File does not exist.');
    }
    $content = Yaml::decode(file_get_contents($file));
    $path = dirname($file);
    foreach ($content['files'] as $file) {
      $directory = dirname($file['path']);
      $filename = basename($file['path']);
      $uuid = $file['uuid'];
      $filepath = $path  . '/' . $file['path'];
      $directory = 'public://' . $directory;
      file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
      /**
       * @var \Drupal\file\FileInterface $file_entity
       */
      $file_entity = \Drupal::entityManager()->loadEntityByUuid('file', $uuid);
      if ($file_entity) {
        file_unmanaged_copy($filepath, $file_entity->getFileUri(), FILE_EXISTS_REPLACE);
        $file_entity = $file_entity->load($file_entity->id());
        $file_entity->set('uid', 1);
        $file_entity->save();
      }
      else {
        $file_entity = file_save_data(file_get_contents($filepath), $directory . '/'. $filename);
        if ($file_entity) {
          $file_entity->set('uuid', $uuid);
          $file_entity->set('uid', 1);
          $file_entity->save();
        }
      }
    }
  }

}
