<?php

namespace tekstari;

class tekstari {

  const LINK = 'http://www.yle.fi/tekstitv/ttv/';
  const MARK_BEGIN = '<center><pre>';
  const MARK_END = '</pre></center>';
  const GET_PLAIN = 0;
  const GET_ASIS = 1;
  const GET_ALL = 2;

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
    if (strlen($data[2]) < 10) {
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

}

?>