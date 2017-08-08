<?php

use Drupal\Core\Serialization\Yaml;

/**
 * Implements hook_drush_command().
 *
 * @return array
 */
function config_file_import_drush_command() {
  $items['config-file-import'] = [
    'description' => 'Import default images and embed buttons.',
    'arguments' => array(
      'file' => 'YAML File which contains the files to import.',
    ),
  ];
  return $items;
}

function drush_config_file_import($file) {
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
