<?php

/**
 * O clasă care organizează meciurile din fiecare rundă.
 **/

class Fixtures {
  private array $v;
  private int $roundNo;

  function __construct(int $n) {
    $this->v = range(0, $n - 1);
    shuffle($this->v);
    $this->roundNo = 0;
  }

  // Returnează un vector de perechi.
  function getMatches(): array {
    $result = [];
    $half = count($this->v) / 2;
    for ($i = 0; $i < $half; $i++) {
      $x = $this->v[$i];
      $y = $this->v[$i + $half];
      if ($this->roundNo % 2) {
        $tmp = $x;
        $x = $y;
        $y = $tmp;
      }
      $result[] = [$x, $y];
    }
    shuffle($result);

    $this->rotate();
    $this->roundNo++;
    return $result;
  }

  private function rotate(): void {
    $half = count($this->v) / 2;
    $elem = array_splice($this->v, $half, 1);
    array_splice($this->v, 1, 0, $elem);
    $elem = array_splice($this->v, $half, 1);
    array_splice($this->v, count($this->v), 0, $elem);
  }
}
