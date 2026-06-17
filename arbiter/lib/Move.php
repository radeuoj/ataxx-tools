<?php

class Move {
  const T_CLONE = 1;
  const T_JUMP = 2;

  public int $type;
  public int $src;
  public int $dest;

  function __construct(int $type, $src, $dest) {
    $this->type = $type;
    $this->src = $src;
    $this->dest = $dest;
  }

  function asArray(): array {
    switch ($this->type) {
      case self::T_CLONE:
        return [ self::T_CLONE, $this->dest ];
      case self::T_JUMP:
        return [ self::T_JUMP, $this->src, $this->dest ];
    }
  }
}
