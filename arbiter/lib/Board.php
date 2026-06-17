<?php

class Board {
  const EMPTY = -1;

  private array $mat; // Matrice de BOARD_SIZE × BOARD_SIZE
  public bool $side;

  function __construct() {
    $this->mat = Config::INIT_BOARD;
    $this->side = 0;
  }

  function asInputFile(): string {
    $l = [];
    $l[] = (int)$this->side;
    foreach ($this->mat as $r) {
      $s = '';
      foreach ($r as $cell) {
        switch ($cell) {
          case self::EMPTY: $s .= '.'; break;
          case 0:           $s .= 'x'; break;
          case 1:           $s .= 'o'; break;
        }
      }
      $l[] = $s;
    }
    return implode("\n", $l);
  }

  function print(): void {
    $this->printTopSeparatorLine();

    for ($r = 0; $r < Config::BOARD_SIZE; $r++) {
      $s = '│';
      for ($c = 0; $c < Config::BOARD_SIZE; $c++) {
        if ($this->mat[$r][$c] == self::EMPTY) {
          $s .= '   ';
        } else {
          $s .= AnsiColors::PIECES[$this->mat[$r][$c]];
          $s .= ' ⬤ ';
          $s .= AnsiColors::DEFAULT;
        }
        $s .= '│';
      }
      Log::debug($s);
      if ($r < Config::BOARD_SIZE - 1) {
        $this->printMiddleSeparatorLine();
      }
    }
    $this->printBottomSeparatorLine();
    Log::debug('La mutare: %s⬤%s',
               [AnsiColors::PIECES[$this->side], AnsiColors::DEFAULT]);
  }

  private function printTopSeparatorLine(): void {
    $this->printSeparatorLine('┌', '┬', '┐');
  }

  private function printMiddleSeparatorLine(): void {
    $this->printSeparatorLine('├', '┼', '┤');
  }

  private function printBottomSeparatorLine(): void {
    $this->printSeparatorLine('└', '┴', '┘');
  }

  private function printSeparatorLine(string $left, string $center, string $right): void {
    $s = $left;
    for ($i = 0; $i < Config::BOARD_SIZE; $i++) {
      $s .= "───";
      $s .= ($i < Config::BOARD_SIZE - 1) ? $center : $right;
    }
    Log::debug($s);
  }

  function anyMoves(): bool {
    for ($r = 0; $r < Config::BOARD_SIZE; $r++) {
      for ($c = 0; $c < Config::BOARD_SIZE; $c++) {
        if ($this->mat[$r][$c] == self::EMPTY) {
          for ($r2 = $r - 2; $r2 <= $r + 2; $r2++) {
            for ($c2 = $c - 2; $c2 <= $c + 2; $c2++) {
              if ($this->test($r2, $c2, $this->side)) {
                return true;
              }
            }
          }
        }
      }
    }
    return false;
  }

  private function test(int $r, int $c, int $val): bool {
    return ($r >= 0) && ($r < Config::BOARD_SIZE) &&
      ($c >= 0) && ($c < Config::BOARD_SIZE) &&
      ($this->mat[$r][$c] == $val);
  }

  function countSquares(int $type): int {
    $cnt = 0;

    for ($r = 0; $r < Config::BOARD_SIZE; $r++) {
      for ($c = 0; $c < Config::BOARD_SIZE; $c++) {
        $cnt += ($this->mat[$r][$c] == $type);
      }
    }
    return $cnt;
  }

  function isLegalMove(Move $move): bool {
    switch ($move->type) {
      case Move::T_CLONE: return $this->canClone($move->dest);
      case Move::T_JUMP: return $this->canJump($move->src, $move->dest);
    }
  }

  private function canClone(int $dest): bool {
    $r = intdiv($dest, Config::BOARD_SIZE);
    $c = $dest % Config::BOARD_SIZE;
    if (!$this->test($r, $c, self::EMPTY)) {
      return false;
    }
    for ($r2 = $r - 1; $r2 <= $r + 1; $r2++) {
      for ($c2 = $c - 1; $c2 <= $c + 1; $c2++) {
        if ($this->test($r2, $c2, $this->side)) {
          return true;
        }
      }
    }
    return false;
  }

  private function canJump(int $src, int $dest): bool {
    $r1 = intdiv($src, Config::BOARD_SIZE);
    $c1 = $src % Config::BOARD_SIZE;
    $r2 = intdiv($dest, Config::BOARD_SIZE);
    $c2 = $dest % Config::BOARD_SIZE;

    return (max(abs($r2 - $r1), abs($c2 - $c1)) == 2) &&
      $this->test($r1, $c1, $this->side) &&
      $this->test($r2, $c2, self::EMPTY);
  }

  // Presupune că mutarea este corectă.
  function makeMove(Move $m): void {
    $r1 = intdiv($m->src, Config::BOARD_SIZE);
    $c1 = $m->src % Config::BOARD_SIZE;
    $r2 = intdiv($m->dest, Config::BOARD_SIZE);
    $c2 = $m->dest % Config::BOARD_SIZE;

    if ($m->type == Move::T_JUMP) {
      $this->mat[$r1][$c1] = self::EMPTY;
    }

    $this->mat[$r2][$c2] = (int)$this->side;
    $this->infectNeighbors($r2, $c2);
    $this->side = !$this->side;
  }

  private function infectNeighbors(int $r, int $c): void {
    for ($r2 = $r - 1; $r2 <= $r + 1; $r2++) {
      for ($c2 = $c - 1; $c2 <= $c + 1; $c2++) {
        if ($this->test($r2, $c2, !$this->side)) {
          $this->mat[$r2][$c2] = (int)$this->side;
        }
      }
    }
  }
}
