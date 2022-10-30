<?php

namespace Drupal\majuelo_custom;

use Twig\Extension\AbstractExtension;
// use Twig\Extension\ExtensionInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Custom twig functions.
 */
class CustomTwig extends AbstractExtension {

  public function getFunctions() {
    return [
      new TwigFunction('months_calendar', [$this, 'monthsCalendar'], ['is_safe' => ['html']]),
    ];
  }

  public function monthsCalendar($node) {
    if (!($node instanceof \Drupal\node\Entity\Node)) {
      return;
    }
    if(!$node->hasField(SchoolarCalendarHtml::FIELD_FIN) || !$node->hasField(SchoolarCalendarHtml::FIELD_INICIO))
      return;
    
    $inicio = $node->get(SchoolarCalendarHtml::FIELD_INICIO)->getValue()[0]['value'];
    $fin = $node->get(SchoolarCalendarHtml::FIELD_FIN)->getValue()[0]['value'];
    $festivos = $node->get(SchoolarCalendarHtml::FIELD_FESTIVOS)->getValue();

    $calendar = new SchoolarCalendarHtml($inicio, $fin, $festivos);
    return $calendar->html();
  }

}