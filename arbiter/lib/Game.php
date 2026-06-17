<?php

class Game {
  private array $players;
  private Board $board;
  private bool $badMove;

  private GameInfo $gameInfo;

  function __construct(Player $p1, Player $p2) {
    $this->players = [$p1, $p2];
    $this->board = new Board();
    $this->badMove = false;
    $this->gameInfo = new GameInfo();
  }

  function run(): void {
    $this->players[0]->startGame();
    $this->players[1]->startGame();

    while (!$this->badMove && $this->board->anyMoves()) {
      $this->playMove();
    }

    $this->computeScores();
  }

  private function playMove(): void {
    Log::info("Mutarea %s", [$this->gameInfo->getNumTurns()]);
    $this->print();
    $input = $this->board->asInputFile();
    $this->gameInfo->addInput($input);
    $pl = $this->players[$this->board->side];

    try {
      $output = $pl->requestAction($input);
      $move = $this->getMove($output->tokens);
      $this->board->makeMove($move);
      $ti = new TurnInfo($move, $output->kibitzes, $pl->lastMoveTime);
      $this->gameInfo->addTurn($ti);
    } catch (AtaxxException $e) {
      $msg = sprintf('Eroare: %s', $e->getMessage());
      $this->gameInfo->disqualify($this->board->side, $msg);
      Log::warn($msg);
      $this->badMove = true;
    }

  }

  private function getMove(array $tokens): Move {
    $type = $this->shiftAndCheck($tokens, 0, Move::T_NUM_TYPES - 1);
    $src = 0;
    $dest = 0;
    $numSquares = Config::BOARD_SIZE * Config::BOARD_SIZE;

    switch ($type) {
      case Move::T_PASS:
        break;

      case Move::T_CLONE:
        $dest = $this->shiftAndCheck($tokens, 0, $numSquares - 1);
        break;

      case Move::T_JUMP:
        $src = $this->shiftAndCheck($tokens, 0, $numSquares - 1);
        $dest = $this->shiftAndCheck($tokens, 0, $numSquares - 1);
        break;
    }

    if (count($tokens)) {
      throw new AtaxxException("Cuvîntul {$tokens[0]} este în plus.");
    }

    $move = new Move($type, $src, $dest);
    if (!$this->board->isLegalMove($move)) {
      throw new AtaxxException('Mutarea este ilegală.');
    }

    return $move;
  }

  private function shiftAndCheck(array &$v, int $lo, int $hi): int {
    if (empty($v)) {
      throw new AtaxxException('Mutarea este prea scurtă.');
    }

    $first = array_shift($v);
    if (filter_var($first, FILTER_VALIDATE_INT) === false) {
      throw new AtaxxException("Valoarea [$first] nu este un întreg.");
    }

    if (($first < $lo) || ($first > $hi)) {
      throw new AtaxxException("Valoarea $first nu este cuprinsă între $lo și $hi.");
    }

    return $first;
  }

  function getInfo(): GameInfo {
    return $this->gameInfo;
  }

  private function computeScores(): void {
    $b = $this->board;
    if (!$this->badMove) {
      $pieces = [$b->countSquares(0), $b->countSquares(1)];

      if (!$b->anyMoves()) {
        // Partea care nu este la mutare ocupă toate spațiile rămase.
        $pieces[!$b->side] += $b->countSquares(Board::EMPTY);
      }

      $this->gameInfo->setPieces($pieces);
    }

    Log::info('======== Finalul partidei');
    $this->print();
    for ($i = 0; $i < 2; $i++) {
      Log::info('%s: %0.1f puncte, %d piese', [
        $this->players[$i]->name,
        $this->gameInfo->getScore($i),
        $this->gameInfo->getPieces($i),
      ]);
    }
  }

  function print(): void {
    $p = $this->players;
    $padLen = Str::maxLength(array_column($p, 'name'));

    $this->board->print();
    for ($i = 0; $i < 2; $i++) {
      Log::debug('%s⬤%s %s    %2d piese %0.3f s', [
        AnsiColors::PIECES[$i],
        AnsiColors::DEFAULT,
        mb_str_pad($p[$i]->name, $padLen),
        $this->board->countSquares($i),
        $p[$i]->remainingTime / 1000,
      ]);
    }
  }
}
