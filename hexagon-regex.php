<?php
/*

--------------------------------------------------------------------------------

 _A_______________________  _B_______________________  _C_______________________
 .*H.*H.*                   .*SE.*UE.*                 .*G.*V.*H.*              
 (DI|NS|TH|OM)*             .*LR.*RL.*                 [CR]*                    
 F.*[AO].*[AO].*            .*OXR.*                    .*XEXM*                  
 (O|RHH|MM)*                ([^EMC]|EM)*               .*DD.*CCM.*              
 .*                         (HHX|[^HX])*               .*XHCR.*X.*              
 C*MC(CCC|MM)*              .*PRR.*DDC.*               .*(.)(.)(.)(.)\4\3\2\1.* 
 [^C]*[^R]*III.*            .*                         .*(IN|SE|HI)             
 (...?)\1*                  [AM]*CM(RC)*R?             [^C]*MMM[^C]*            
 ([^X]|XCC)*                ([^MC]|MM|CC)*             .*(.)C\1X\1.*            
 (RR|HHH)*.?                (E|CR|MN)*                 [CEIMU]*OH[AEMOR]*       
 N.*X.X.X.*E                P+(..)\1.*                 (RX|[^R])*               
 R*D*M*                     [CHMNOR]*I[CHMNOR]*        [^M]*M[^M]*              
 .(C|HH)*                   (ND|ET|IN)[^X]*            (S|MM|HHH)*              


--------------------------------------------------------------------------------
*/
class RegExCrossWords {

  const HEXAGON_LENGTH = 7;
  const BOARD_LEFT = 5;


  function __construct() {
    $this->consoleApp = new ConsoleApp($this, STDIN, STDOUT);

    $this->initRotationMatirx();
    $this->initRegExpressions();
    $this->infoLines = array(
      "",
      Style::text("RegEx CrossWords", Style::BOLD),
      "(c) 2013 Mathias Leppich",
      "",
      Style::text("Keys:", Style::BOLD),
      "  ARROWS  : navigate cursor",
      "  0-9 A-Z : change field",
      "  SPACE   : clear field",
      "  RETURN  : rotate hexagon",
      "",
      "Inspired by 'a regular crossword'",
      "See: ".Style::text("http://goo.gl/QKL2nD", array(Style::TEXT_CYAN,Style::UNDERSCORE)),
    );
  }

  function initRegExpressions() {
    $sep = str_repeat("-", 80);
    list(,$comment) = explode($sep, file_get_contents(__FILE__));
    $lines = explode("\n", $comment);
    $beginRegexes = false;
    $exp = array();
    foreach ($lines as $line) {
      if (!$beginRegexes) {
        $beginRegexes = preg_match('/^\s*(_(A|B|C)_+\s*)/', $line, $m);
        $len = strlen($m[1]);
      }
      elseif (trim($line)) {
        list($A,$B,$C) = array_map("trim",str_split($line,$len));
        $exp[0][] = $A;
        $exp[1][] = $B;
        $exp[2][] = $C;
      }
      else {
        break;
      }
    }
    $this->regExp = $exp;
  }

  var $regexOk = array();
  var $abcOk = array();
  function evaluateRegExps() {
    $abc = "ABC";
    $allAllOk = true;
    foreach ($this->regExp as $r => $exps) {
      $R = $abc[$r];
      $allOk = true;
      foreach ($exps as $x => $exp) {
        $line = $this->line($x, $r*2);
        $ok = preg_match('/'.$exp.'/', $line);
        #$this->debug(sprintf("  $R line = %-15s regex = %-25s ok = %s", json_encode($line), $exp, $ok?"√":""));
        $this->regexOk[$r][$x] = $ok;
        $allOk = $allOk && $ok;
      }
      $this->abcOk[$r] = $allOk;
      $allAllOk = $allAllOk && $allOk;
    }

    foreach ($this->rotation as $x => $rots) {
      foreach ($rots as $y => $void) {
        $rx = array(
          0 => $this->rotateBy($x,$y,0),
          1 => $this->rotateBy($x,$y,1),
          2 => $this->rotateBy($x,$y,2),
        );
        $aOK = $this->regexOk[0][$rx[0][0]];
        $bOK = $this->regexOk[1][$rx[1][0]];
        $cOK = $this->regexOk[2][$rx[2][0]];
        $fieldOk = $aOK && $bOK && $cOK;;
        if (array($x,$y) == $this->cursor) {
          #$this->debug("  $x,$y  =  ".json_encode(array($aOK,$bOK,$cOK)));
        }
        $this->fieldOk[$x][$y] = $fieldOk;
      }
    }

  }

  function drawRegExps() {
    $abc = "ABC";
    $this->consoleApp->println("");
    $header = " ";
    for ($r=0; $r < 3; $r++) {
      $ok = $this->abcOk[$r];
      $styles = array(Style::BOLD, Style::UNDERSCORE);
      if ($ok) $styles[] = Style::TEXT_GREEN;
      $header.= Style::text(sprintf(" %-24s", $abc[$r]), $styles)." ";
    }
    $this->consoleApp->println($header);
    list($x,$y) = $this->cursor;
    $rx = array(
      0 => $this->rotateBy($x,$y,$this->rot+0),
      1 => $this->rotateBy($x,$y,$this->rot+1),
      2 => $this->rotateBy($x,$y,$this->rot+2),
    );
    foreach ($this->regExp[0] as $x => $A) {
      $line = "";
      for ($r=0; $r < 3; $r++) { 
        $ok = $this->regexOk[$r][$x];
        $cur = $rx[$r][0] == $x;
        $styles = array();
        if ($ok) $styles[] = Style::TEXT_GREEN;
        if ($cur) $styles[] = Style::BOLD;
        $line.= ($cur ? Style::text(" »", array(Style::TEXT_YELLOW,Style::BOLD)) : "  ")
          .Style::text(sprintf(" %-22s ", $this->regExp[$r][$x]), $styles);
      }
      $this->consoleApp->println($line);
    }
    $this->consoleApp->println("");
  }

  function line($x, $r = null) {
    $line = "";
    foreach ($this->rotation[$x] as $y => $void) {
      list($cx, $cy) = $this->rotateBy($x,$y,$r);
      if (isset($this->matrix[$cx][$cy])) {
        $line.= $this->matrix[$cx][$cy];
      } else {
        $line.= " ";
      }
    }
    return $line;
  }

  function initRotationMatirx() {
    $S = static::HEXAGON_LENGTH;
    $MX = $S*2-1;
    
    $fields = array();
    for ($X=0; $X < $MX; $X++) { 
      $P = abs($S-1-$X);
      $F1 = max(0,$X-$S+1);
      $MY = $S*2+$P*-1-1;
      for ($Y=0; $Y < $MY; $Y++) { 
        $NX = $MY-$Y-1 + $F1;
        $NY = min($Y,$X) + $F1;
        if ($X>=$S && $Y>=$S) {
          $NY = $X;
        }
        $fields[] = array($X, $Y, $NX, $NY);
      }
    }

    $rotation = array();
    foreach ($fields as $list) {
      list($x,$y,$ex,$ey) = $list;
      $rotation[$x][$y] = array($ex,$ey);
    }
    $this->rotation = $rotation;
  }
  
  var $matrix = array();
  var $rot = 0;

  function rotate($x,$y) {
    return $this->rotateBy($x,$y,$this->rot);
  }

  function rotateBy($x,$y,$r) {
    $r = ($r % 3) * 2;
    if ($r < 0) {
      $r = 6 - $r;
    }
    for ($i=0; $i < $r; $i++) { 
      list($x,$y) = $this->rotation[$x][$y];
    }
    return array($x,$y);
  }

  static $BLANK_FIELD = "_";
  static $CURSOR_STYLE_BLOCK = array(Style::BACK_WHITE, Style::TEXT_BLACK);
  static $CURSOR_STYLE_DASH = array(Style::UNDERSCORE);

  function field($x,$y) {
    $cur = array($x,$y) == $this->cursor;
    list($x,$y) = $this->rotate($x,$y);
    if ($cur) {
      #$this->debug(sprintf("cursor: [%2d,%2d]   field:[%2d,%2d]",
      #  $this->cursor[0],$this->cursor[1],
      #  $x,$y));
    }
    $ok = $this->fieldOk[$x][$y];
    $styles = ($cur && $ok) ? array(Style::BACK_GREEN, Style::TEXT_BLACK) :(
              ($cur)        ? array(Style::BACK_WHITE, Style::TEXT_BLACK) :(
              ($ok)         ? array(Style::TEXT_GREEN, Style::BOLD) :(
                              array()
              )));

    if (isset($this->matrix[$x][$y])) {
      $str = $this->matrix[$x][$y];
    } else {
      $str = " ";
      $styles[] = Style::UNDERSCORE;
    }
    return Style::text($str, $styles);
  }
  var $cursor = array(0,0);

  function cursorMove($x,$y) {
    $ok = $this->cursorMoveTry($x,$y);
    if (!$ok) {
      if ($x==0 && $y>0) {
        $ok = $this->cursorMoveTry($x+1,$y) || $this->cursorMoveTry($x-1,$y);
      }
      elseif ($y==0 && $x != 0) {
        $ok = $this->cursorMoveTry($x,$y-1);
      }
    }
    return $ok;
  }

  function cursorMoveTry($x,$y) {
    list($cx, $cy) = $this->cursor;
    list($nx, $ny) = array($cx+$x, $cy+$y);
    if (!isset($this->rotation[$nx][$ny])) {
      // invalid position;
      return false;
    }
    $this->cursor = array($nx, $ny);
    return true;
  }
  var $boardDrawn = 0;
  function drawBoard() {
    $this->boardDrawn++;
    $S = static::HEXAGON_LENGTH;
    $LP = static::BOARD_LEFT;

    $E = "ACB";
    $MX = $S*2-1;
    $e = $this->rot%3 + 3;
    $this->consoleApp->println(str_repeat(" ", $LP+$S*3+3).$E[($e-1)%3]);
    for ($X=0; $X < $MX; $X++) { 
      $P = abs($S-1-$X);
      $MY = $S*2+$P*-1-1;
      $line = str_repeat(" ", $P+$LP);
      $line.= ($X==$S-1) ? $E[($e-0)%3]."  " : "   ";
      for ($Y=0; $Y < $MY; $Y++) { 
        $line.= $this->field($X,$Y)." ";
      }
      $line.= str_repeat(" ", $P+4).($this->infoLines[$X]?:"");
      $this->consoleApp->println($line);
    }
    $this->consoleApp->println(str_repeat(" ", $LP+$S*3+3).$E[($e-2)%3]);
  }


  function debug($str) {
    fwrite(STDERR,sprintf("%s\n", $str));
    #$this->consoleApp->println(Style::text(rtrim($str), Style::TEXT_CYAN));
  }

  
  const KEY_ARROWS      = 91;
  const KEY_ARROW_UP    = 65;
  const KEY_ARROW_DOWN  = 66;
  const KEY_ARROW_RIGHT = 67;
  const KEY_ARROW_LEFT  = 68;
  
  const KEY_RETURN      = 10;
  const KEY_SPACE       = 32;
  const KEY_BACKSPACE   = 8;
  const KEY_TAB         = 9;


  function handleKeyPress($ret) {
    switch ($ret) {
    case array(self::KEY_ARROWS,self::KEY_ARROW_UP):
      return $this->cursorMove(-1,0);
    case array(self::KEY_ARROWS,self::KEY_ARROW_DOWN):
      return $this->cursorMove(+1,0);
    case array(self::KEY_ARROWS,self::KEY_ARROW_LEFT):
      return $this->cursorMove(0,-1);
    case array(self::KEY_ARROWS,self::KEY_ARROW_RIGHT):
      return $this->cursorMove(0,+1);
    case array(self::KEY_TAB):
    case array(self::KEY_RETURN):
      $this->rot++;
      list($x,$y) = $this->cursor;
      $this->cursor = $this->rotateBy($x,$y,2);
      return true;
    case array(self::KEY_BACKSPACE):
    case array(self::KEY_SPACE):
      list($x,$y) = $this->cursor;
      list($x,$y) = $this->rotate($x,$y);
      unset($this->matrix[$x][$y]);
      if ($ret == array(self::KEY_SPACE)) {
        $this->cursorMove(0,+1);
      }
      return true;

    default:
      $chr = null;
      $move = false;
      if ($ret[0] >= 48 && $ret[0] <= 57) { // 0-9
        $chr = chr($ret[0]);
        $move = true;
      }
      elseif ($ret[0] >= 65 && $ret[0] <= 90) { // A-Z
        $chr = chr($ret[0]);
      }
      elseif ($ret[0] >= 97 && $ret[0] <= 122) { // a-z
        $chr = chr($ret[0]-32);
        $move = true;
      }
      if ($chr !== null) {
        list($x,$y) = $this->cursor;
        list($x,$y) = $this->rotate($x,$y);
        $this->matrix[$x][$y] = $chr;
        if ($move) {
          $this->cursorMove(0,+1);
        }
        return true;
      }
      return false;
    }
    return true;
  }


  var $saveFilepath = "./.hexagon-regex-crossword.state";
  function persist() {
    #if (is_writable($this->saveFilepath)) {
      file_put_contents($this->saveFilepath,
        chunk_split(
          base64_encode(
            serialize($this->matrix)
          )
        )
      );
    #}
  }

  function restore() {
    if (is_readable($this->saveFilepath)) {
      $data = file_get_contents($this->saveFilepath);
      if (!empty($data)) {
        $data = unserialize(base64_decode($data));
        if (is_array($data)) {
          $this->matrix = $data;
        }
      }
    }
  }

  function draw() {
    $this->consoleApp->println("");
    #$this->debug(sprintf(" Last Key: %s", $this->lastKey));
    #usleep(1000000*0.5);
    $this->evaluateRegExps();
    $this->drawBoard();
    $this->drawRegExps();
  }

  function shouldRun() {
    $this->persist();
    return true;
  }


  function run(array $argv) {
    $this->restore();
    $this->consoleApp->run($argv);
  }

}

class Style {

  const TEXT_BLACK      = 30;
  const TEXT_RED        = 31;
  const TEXT_GREEN      = 32;
  const TEXT_YELLOW     = 33;
  const TEXT_BLUE       = 34;
  const TEXT_MAGENTA    = 35;
  const TEXT_CYAN       = 36;
  const TEXT_WHITE      = 37;
  const BACK_BLACK      = 40;
  const BACK_RED        = 41;
  const BACK_GREEN      = 42;
  const BACK_YELLOW     = 43;
  const BACK_BLUE       = 44;
  const BACK_MAGENTA    = 45;
  const BACK_CYAN       = 46;
  const BACK_WHITE      = 47;
  const BOLD           = 1;
  const UNDERSCORE     = 4;
  const BLINK          = 5;
  const REVERSE        = 7;
  const CONCEAL        = 8;

  static function text($str, $styles) {
    if (!is_array($styles)) {
      $styles = func_get_args();
      array_shift($styles); // strip str parameter
    }
    return $styles ? sprintf("\033[%sm%s\033[0m", implode(";", $styles), $str) : $str;
  }
}

class ConsoleApp {
  function __construct($delegate, $inputStream, $outputStream) {
    $this->delegate = $delegate;
    $this->inputStream = $inputStream;
    $this->outputStream = $outputStream;
  }

  var $printlnOffset = 0;
  function printlnRewind() {
    $this->buffer(str_repeat("\033[F", $this->printlnOffset));
    $this->printlnOffset = 0;
  }

  function println($line) {
    // Erase characters from cursor to end of line
    $output = "";# "\033[K";
    $output.= $line;
    $output.= "\n";
    $this->buffer($output);
    $this->printlnOffset++;
  }

  var $lastBuffer = null;
  var $buffer = array();
  private function buffer($str) {
    $this->buffer[] = $str;
  }

  private function bufferDiff($old, $new) {
    if ($old == null) return implode("", $new);
    $old = explode("\n",implode("", $old));
    $new = explode("\n",implode("", $new));

    $length = max(count($old), count($new));
    $diff = array();
    for ($i=0; $i < $length; $i++) { 
      if ($old[$i] !== $new[$i]) {
        $diff[] = "C: ".$new[$i];
      } elseif (isset($new[$i])) {
        $diff[] = "N: ";
      } else {
        $diff[] = "M: ";
      }
    }
    return implode("\n",$diff);
  }
  
  function flush() {

    //$str = $this->bufferDiff($this->lastBuffer, $this->buffer);
    $str = implode("", $this->buffer);
    fwrite($this->outputStream,$str);
    $this->lastBuffer = $this->buffer;
    $this->buffer = array();

    #$this->println("buffer-length: ".strlen($str));
  }
  
  function handleInput() {
    if ($this->abort) return;
    $in = $this->inputStream;
    $ret = null;
    do {
      $c = fread($in, 1);
      if ("\033" === $c) { // Did we read an escape sequence?
        $c .= fread($in, 2);
        $ret = array(ord($c[1]), ord($c[2]));
      } else {
        #$this->println("c = ".json_encode($c));
        $ret = array(ord($c[0]));
      }
      $ok = $this->callDelegate("handleKeyPress", $ret);
    } while (!$ok);

    $this->lastKey = json_encode($ret);

    return $ret;
  }

  private function callDelegate($name) {
    $args = func_get_args();
    array_shift($args);
    if (method_exists($this->delegate, $name)) {
      #$this->println("callDelegate: $name");
      return call_user_func_array(array($this->delegate, $name), $args);
    }
    return null;
  }

  private function shouldRun() {
    return $this->callDelegate("shouldRun")!==false && !$this->abort;
  }

  private function stty($opts = null) {
    if ($opts === null) {
      $opts = "-g";
    }
    return shell_exec('stty '.$opts);
  }

  function run(array $argv) {

    if (in_array("--abort", $argv)) {
      $this->abort = true;
    }

    $sttyMode = $this->stty();
    // Disable icanon (so we can fread each keypress) and echo (we'll do echoing here instead)
    $this->stty('-icanon -echo');
    
    do {
      $this->printlnRewind();
      $this->callDelegate("draw");
      $this->flush();
      $this->handleInput();
    } while ($this->shouldRun());

    // Reset stty so it behaves normally again
    $this->stty($sttyMode);
  }
}


$game = new RegExCrossWords;
$game->run($argv);

#$border = str_repeat("-", 80);
#$field = end(array_slice(explode($border,file_get_contents(__FILE__)),1,1));
#print $field;

#preg_match_all('/((?: _)* _)\n/', $field, $m);
#$hexagon = $m[1];

