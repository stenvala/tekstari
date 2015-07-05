<?php

namespace tekstari;

class tekstari {

  const LINK = 'http://www.yle.fi/tekstitv/ttv/';
  const MARK_BEGIN = '<center><pre>';
  const MARK_END = '</pre></center>';
  const GET_PLAIN = 0;
  const GET_ASIS = 1;
  const GET_ALL = 2;

  private static $FAV = null;
  private static $FAV_FILE = '/fav.json';
  private $page;
  private $contents = '';
  private $parsed;

  public function __construct($page) {

    $this->page = $page;
    if (($handle = @fopen(self::LINK . $this->page . '.html', 'rb')) === false) {
      throw new \Exception("Haettua sivua '$page' ei ole", 410);
    }
    while (!feof($handle)) {
      $this->contents .= fread($handle, 8192);
    }
    preg_match('@(' . self::MARK_BEGIN . ')(.*?)(' . self::MARK_END . ')@s', $this->contents, $data);
    if (count($data) < 3 || strlen($data[2]) < 10) {
      throw new \Exception("Haettua sivua '$page' ei ole", 410);
    }
    $this->parsed = $data[2];
    $this->replaceOUML();
  }

  public function getPage($format) {
    switch ($format) {
      case self::GET_PLAIN:
        return strip_tags($this->parsed);
      case self::GET_ASIS:
        return $this->parsed;
      case self::GET_ALL:
        return $this->contents;
    }
  }

  private function replaceOUML() {
    $map = array('&ouml;' => 'ö',
        '&Ouml;' => 'Ö',
        '&auml;' => 'ä',
        '&Auml;' => 'Ä',
        '&Uuml;' => 'Ü',
        '&uuml;' => 'ü',
        '&quot;' => '"');
    $this->parsed = str_replace(array_keys($map), array_values($map), $this->parsed);
  }

  // Static for managing favorites
  static public function getFavorites() {
    self::initFav();
    return self::$FAV;
  }

  static public function deleteFavorite($page) {
    self::initFav();
    $index = $this->getIndex($page);
    if (!is_null($index)) {
      array_splice(self::$FAV, $index, 1);
      self::saveFav();
    }
  }

  static public function addFavorite($page, $title = null) {
    self::initFav();
    if (is_null($title)) {
      $title = $page;
    }
    array_push(self::$FAV, array('page' => $page, 'title' => $title));
    $sortFun = function($a, $b) {
      if ($a['page'] == $b['page']) {
        return 0;
      }
      return ($a['page'] < $b['page']) ? -1 : 1;
    };
    array_usort(self::$FAV,$sortFun);
    self::saveFav();
  }

  static private function getIndex($page) {
    $index = null;
    $min = 0;
    $max = count(self::$FAV) - 1;
    $guess = ceil($max / 2);
    while (true) {
      $pageGuess = self::$FAV[$guess]['page'];
      if ($pageGuess == $page) {
        $index = $guess;
        break;
      } else if ($max == $min) {
        break;
      } elseif ($guess == $max) {
        $max = $min;
        $guess = $min;
      } else if ($pageGuess > $page) {
        $max = $guess;
      } else if ($pageGuess < $page) {
        $min = $guess;
      }
      $guess = ceil(($max + $min) / 2);
    }
    return $index;
  }

  static private function saveFav() {
    file_put_contents(__DIR__ . self::$FAV_FILE, json_encode(self::$FAV));
  }

  static private function initFav() {
    if (is_null(self::$FAV)) {
      self::$FAV = json_decode(file_get_contents(__DIR__ . self::$FAV_FILE), true);
    }
  }

}

?>