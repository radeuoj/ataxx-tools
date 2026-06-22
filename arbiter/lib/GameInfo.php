<?php

/**
 * O clasă care reține informațiile despre o partidă jucată.
 **/

class GameInfo {
  private array $scores; // scorurile finale: 0.0, 0.5 sau 1.0
  private array $pieces; // numerele de piese la final

  private array $turns;  // un vector de TurnInfo
  private array $inputs; // fișierele de intrare la fiecare mutare
  private string $error; // pentru cazul cînd partida se termină cu eroare

  function __construct() {
    $this->scores = [0.0, 0.0];
    $this->pieces = [0, 0];
    $this->turns = [];
    $this->inputs = [];
    $this->error = '';
  }

  function addTurn(TurnInfo $turn): void {
    $this->turns[] = $turn;
  }

  function addInput(string $input): void {
    $this->inputs[] = $input;
  }

  // Victorie la masa verde cu 25-0.
  function forfeit(bool $side, string $msg): void {
    $this->error = $msg;
    $this->scores[!$side] = 1.0;
    $this->pieces[!$side] = (Config::BOARD_SIZE ** 2 + 1) / 2;
  }

  function draw(): void {
    $pcs = (Config::BOARD_SIZE ** 2 - 1) / 2;
    $this->setPieces([$pcs, $pcs]);
    $this->error = sprintf("Am declarat partida remiză după %s salturi.",
                           Config::MAX_JUMPS);
    Log::warning($this->error);
  }

  function setPieces(array $pieces): void {
    $this->pieces = $pieces;
    if ($pieces[0] > $pieces[1]) {
      $this->scores[0] = 1.0;
    } else if ($pieces[0] < $pieces[1]) {
      $this->scores[1] = 1.0;
    } else {
      $this->scores = [0.5, 0.5];
    }
  }

  function getNumTurns(): int {
    return count($this->turns);
  }

  function getScore(int $id): float {
    return $this->scores[$id];
  }

  function getPieces(int $id): int {
    return $this->pieces[$id];
  }

  function getInputs(): array {
    return $this->inputs;
  }

  function asArray(): array {
    $turnData = [];
    foreach ($this->turns as $t) {
      $turnData[] = $t->asArray();
    }

    return [
      'time_per_game' => Config::TIME_LIMIT_PER_GAME,
      'scores' => $this->scores,
      'pieces' => $this->pieces,
      'turns' => $turnData,
      'error' => $this->error,
    ];
  }
}
