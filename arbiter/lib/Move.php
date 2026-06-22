<?php

class Move {
  const T_CLONE = 1;
  const T_JUMP = 2;

  public int $type;
  public int $src;
  public int $dest;
  public string $text;

  function __construct(string $s) {
    $this->text = $s;
    $col = Str::charPlusInt('a', Config::BOARD_SIZE - 1);
    $row = Str::charPlusInt('1', Config::BOARD_SIZE - 1);
    $square = "[a-{$col}][1-{$row}]";

    if (preg_match("/^{$square}$/", $s)) {
      $this->type = self::T_CLONE;
      $this->src = 0;
      $this->dest = Str::coordsToSquare($s);
    } else if (preg_match("/^{$square}-{$square}$/", $s)) {
      $this->type = self::T_JUMP;
      $this->src = Str::coordsToSquare(substr($s, 0, 2));
      $this->dest = Str::coordsToSquare(substr($s, 3, 2));
    } else {
      throw new AtaxxException("Șirul [{$s}] nu este o mutare.");
    }
  }

  function isJump(): bool {
    return ($this->type == self::T_JUMP);
  }

  function toString(): string {
    return $this->text;
  }
}
