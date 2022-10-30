<?php

namespace Drupal\majuelo_custom\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;



/**
* Provides a block with a simple text.
*
* @Block(
*   id = "years_block_basic",
*   admin_label = @Translation("Majuelo school years list"),
*   category = "Majuelo"
* )
*/
class YearsBlockBasic extends BlockBase {

 /**
  * {@inheritdoc}
  */
 public function build() {
  $database = \Drupal::database();
  $query = $database->query("SELECT field_ano_escolar_string_value FROM node__field_ano_escolar_string GROUP BY field_ano_escolar_string_value ORDER BY field_ano_escolar_string_value DESC");
  $result = $query->fetchAll(); 

  // configuration
  $config = $this->getConfiguration();
  $url = $config['url'];

  $variable = array_map(function($object) use ($url) {
    $year = $object->field_ano_escolar_string_value;
    return [
      'link' => str_replace("%year%", $year, $url),
      'text' => $year
    ];
  }, $result);


  return [
    '#theme' => 'majuelo_custom_years_block',
    '#years' => $variable,
  ];
 }

 /**
* {@inheritdoc}
*/
public function blockForm($form, FormStateInterface $form_state) : array {
  $form = parent::blockForm($form, $form_state);
 
  // Retrieve the blocks configuration as the values provided in the form
  // are stored there.
  $config = $this->getConfiguration();
 
  // The form field is defined and added to the form array here.
  $form['url'] = [
    '#type' => 'textfield',
    '#title' => $this->t('URL for year link'),
    '#description' => $this->t('Add url for the item link. Use %year% for set variable in link.'),
    '#default_value' => $config['url'] ?? '',
  ];
 
  return $form;
 }

 /**
* {@inheritdoc}
*/
public function blockSubmit($form, FormStateInterface $form_state) : void {
  // We do this to ensure no other configuration options get lost.
  parent::blockSubmit($form, $form_state);
 
  // Here the value entered by the user is saved into the configuration.
  $this->configuration['url'] = $form_state->getValue('url');
 }

}