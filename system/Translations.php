<?php 

namespace Typemill;

class Translations
{
  public static function loadTranslations($environment)
  {
    define('DS', '/');
    
    $yaml = new Models\WriteYaml();
    $settings = $yaml->getYaml('settings', 'settings.yaml');

    if($settings === FALSE){
      $language = \Typemill\Settings::whichLanguage();
    } else {
      $language = $settings['language'];
    }

    $theme = 'cyanine';
    if($settings !== NULL){
      if(is_array($settings)){
        if (array_key_exists('theme', $settings)) {
          $theme = $settings['theme'];
        }
      }
    }

    // theme labels selected according to the environment: admin or user
    $theme_labels = [];
    $theme_language_folder = 'themes' . DS . $theme . DS . 'languages' . DS . $environment . DS;
    $theme_language_file = $language . '.yaml';
    if (file_exists($theme_language_folder . $theme_language_file))
    {
      $theme_labels = $yaml->getYaml($theme_language_folder, $theme_language_file);
    }

    $system_labels = [];
    $plugins_labels = [];
    if($environment=='admin'){
      // system labels
      $system_language_folder ='system' . DS . 'author' . DS . 'languages' . DS;
      $system_language_file = $language . '.yaml';
      if (file_exists($system_language_folder . $system_language_file))
      {
        $system_labels = $yaml->getYaml($system_language_folder, $system_language_file);
      }

      // Next change, to provide labels for the admin and user environments.
      // There may be plugins that only work in the user environment,
      // only in the admin environment, or in both environments.
      $plugin_labels = [];
      if($settings !== NULL){
        if(is_array($settings)){
          if (array_key_exists('plugins', $settings)) {
            if($settings['plugins'] !== NULL) {
              foreach($settings['plugins'] as $plugin => $config){
                if($config['active']=='on'){
                  $plugin_language_folder = 'plugins' . DS . $plugin . DS . 'languages' . DS;
                  $plugin_language_file = $language . '.yaml';
                  if (file_exists($plugin_language_folder . $plugin_language_file)){
                    $plugin_labels[$plugin] = $yaml->getYaml($plugin_language_folder, $plugin_language_file);
                  }
                }
              }
              foreach($plugin_labels as $key => $value) {
                $plugins_labels = array_merge($plugins_labels, $value);
              }
            }
          }
        }
      }
    }

    $labels = [];
    if(is_array($plugins_labels)){
      $labels = array_merge($labels, $plugins_labels);
    }
    if(is_array($system_labels)){
      $labels = array_merge($labels, $system_labels);
    }
    if(is_array($theme_labels)){
      $labels = array_merge($labels, $theme_labels);
    }

    return $labels;
  }

}