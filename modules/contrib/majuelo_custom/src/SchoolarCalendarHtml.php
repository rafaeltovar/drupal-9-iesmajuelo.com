<?php

namespace Drupal\majuelo_custom;

class SchoolarCalendarHtml {

  const FIELD_FIN = "field_fin_de_curso";
  const FIELD_INICIO = "field_inicio_del_curso";
  const FIELD_FESTIVOS = "field_festivo";

  const MAX_MONTH = 12;
  const TEXT_INICIO = "Primer día lectivo";
  const TEXT_FIN = "Último día lectivo";

  private $monthsNames = [
    "",
    "Enero",
    "Febrero",
    "Marzo",
    "Abril",
    "Mayo",
    "Junio",
    "Julio",
    "Agosto",
    "Septiembre",
    "Octubre",
    "Noviembre",
    "Diciembre"
  ];

  private $inicio;
  private $fin;
  private $festivos;

  private $weekShortNames = [
    "Lun",
    "Mar",
    "Mie",
    "Jue",
    "Vie",
    "Sab",
    "Dom"
  ];

  public function __construct(string $inicio, string $fin, array $festivos) {
    $this->setInicio($inicio);
    $this->setFin($fin);
    $this->setFestivos($festivos);
  }

  public function html() : string {
   
    $yearInicio = intval($this->inicio->format("Y"));
    $monthInicio = intval($this->inicio->format("m"));
    $yearFin = intval($this->fin->format("Y"));
    $monthFin = intval($this->fin->format("m"));
    
    $content = "<div class=\"container calendar-container\"><div class=\"row align-items-start\">";
    $fin = false;
    $iterator = 0;
    while(!$fin) {
      if($monthInicio == 13) {
        $monthInicio = 1;
        $yearInicio++;
      }

      $content.= $this->monthHtml($monthInicio, $yearInicio);

      // iterator is used to avoid an infinite loop
      $fin = ($monthFin == $monthInicio && $yearFin == $yearInicio) || $iterator > 12;

      $monthInicio++;
      $iterator++;

      if($iterator % 3 == 0) 
        $content.= "</div><div class=\"row\">";
    }

    // create last columns
    while($iterator % 3 !== 0) {
      $content.="<div class=\"col\"></div>";
      $iterator++;
    }

    $content.="</div></div>";
    return $content;
  }

  private function monthHtml(int $month, int $year) {
    $firstOfMonth = mktime (0,0,0, $month, 1, $year);

    $dateInfo = getdate($firstOfMonth);
    $lastMonthDay = date('t', $firstOfMonth);
  // $month = $date_info['mon'];

    //si el Domingo es el primer dia de la semana
    $weekday = $dateInfo['wday']-1;
    //weekday (zero based) of the first day of the month
    $weekday = $weekday == -1? 6 : $weekday; //Por si el Domingo es el dia 1 del mes

    // primeros dias "vacios" del mes
    // if($weekday > 0){
    //   $calendar .= ("<td bgcolor='".$ColorFondoTabla).
    //   ("' colspan='".$weekday."'></td>\n");
    // }

    // header of table with month name
    $table = "<div class=\"col\"><table class=\"majuelo-calendar\">
      <thead><tr><td colspan=\"7\">".sprintf("%s, %s", $this->monthsNames[$month], $year)."</td></tr></thead>
      <tbody><tr class=\"majuelo-calendar-days\">";
    // week days header  
    foreach($this->weekShortNames as $dayName)
      $table.=sprintf("<td><span>%s</span></td>", $dayName);
      
    $table.="</tr><tr>";

    if($weekday > 0)
      $table.=sprintf("<td colspan=\"%s\"></td>", $weekday);
    
    // days of month
    $day = 1;
    while($day <= $lastMonthDay) {

      if($weekday == 7) { // start new week
        $table.="</tr><tr>";
        $weekday = 0;
      }

      $table.=$this->dayField($day, $month, $year);

      // increments variables
      $day++;
      $weekday++;
    }   

    // last days of month
    if($weekday !== 7)
      $table.=sprintf("<td colspan=\"%s\"></td>", (7-$weekday));

    $table.="</tbody></table></div>";

    return $table;
  }

  private function dayField($day, $month, $year) {
    $motivo = $this->motivoFestivo($day, $month, $year);
    $class = "freeday";
    $content = $day;

    if(!isset($motivo)) {
      $date = \DateTime::createFromFormat("d/m/Y", sprintf("%s/%s/%s", $day, $month, $year));
      $class = intval($date->format("N")) > 5 ? "weekend" : "labour";

      if($this->isInicio($day, $month, $year)) {
        $class = "start";
        $motivo = self::TEXT_INICIO;
      }

      if($this->isFin($day, $month, $year)) {
        $class = "end";
        $motivo = self::TEXT_FIN;
      }

      // is today?
      $today = new \DateTime("today");
      $class.= $today->format("d/m/Y") == $date->format("d/m/Y")? " today" : "";
    }

    if(isset($motivo))
      $content = sprintf("<a title=\"%s\">%s</a>", $motivo, $day);

    // poner la clase, el contenido, etc...
    return sprintf("<td class=\"%s\"><span>%s</span></td>", $class, $content);
  }

  // private function contentFreeday($day, $month, $details) {
  //   return sprintf("<a title=\"%s\">%s</a>", $details, $day);
  // }

  private function setInicio(string $inicio) {
    $this->inicio = \DateTime::createFromFormat("Y-m-d", $inicio);
  }

  private function setFin(string $fin) { 
    $this->fin = \DateTime::createFromFormat("Y-m-d", $fin);
  }

  private function setFestivos(array $festivos) {
    $this->festivos = [];

    foreach($festivos as $f) {
      $fest = explode(",", $f['value']);
      $this->festivos[trim($fest[0])] = trim($fest[1]);
    }
  }

  private function isInicio($day, $month, $year) : bool {
    $compare = \DateTime::createFromFormat("d/m/Y", sprintf("%s/%s/%s", $day, $month, $year));
    return $this->inicio->format("d/m/Y") == $compare->format("d/m/Y");
  }

  private function isFin($day, $month, $year) : bool {
    $compare = \DateTime::createFromFormat("d/m/Y", sprintf("%s/%s/%s", $day, $month, $year));
    return $this->fin->format("d/m/Y") == $compare->format("d/m/Y");
  }

  private function motivoFestivo($day, $month, $year) : ?string {
    $compare = \DateTime::createFromFormat("d/m/Y", sprintf("%s/%s/%s", $day, $month, $year));
    $date = $compare->format("d/m/Y");
    if(array_key_exists($date, $this->festivos))
      return $this->festivos[$date];

    return null;
  }

}