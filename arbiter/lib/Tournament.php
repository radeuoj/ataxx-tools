<?php

class Tournament {
  private array $players;
  private int $numRounds;
  private string $saveDir;
  private bool $saveInputs;
  private Fixtures $fixtures;

  function __construct(array $players, int $numRounds, string $saveDir,
                       bool $saveInputs) {
    $this->players = $players;
    $this->numRounds = $numRounds;
    $this->saveDir = $saveDir;
    $this->saveInputs = $saveInputs;
    $this->fixtures = new Fixtures(count($players));
  }

  function run(): void {
    $round = 0;
    $half = count($this->players) / 2;
    for ($round = 0; $round < $this->numRounds; $round++) {
      $this->roundBanner($round);
      foreach ($this->fixtures->getMatches() as [$id1, $id2]) {
        $this->runGame($round, $id1, $id2);
      }
    }
  }

  private function roundBanner(int $id): void {
    $msg = sprintf("        Runda %d / %d        ", $id + 1, $this->numRounds);
    Log::successBanner($msg);
  }

  private function runGame(int $round, int $id1, int $id2): void {
    $p1 = $this->players[$id1];
    $p2 = $this->players[$id2];
    Log::info("Partida: {$p1->name} - {$p2->name}");
    $g = new Game($p1, $p2);
    $g->run();

    $gi = $g->getInfo();
    $p1->addResult($gi->getScore(0), $gi->getPieces(0));
    $p2->addResult($gi->getScore(1), $gi->getPieces(1));
    $saver = new Saver($gi, [$p1, $p2], $round, $this->saveDir,
                       $this->saveInputs);
    $saver->saveAll();
    $this->report();
  }

  private function report(): void {
    $ord = $this->sortPlayers();
    $len = Str::maxLength(array_column($this->players, 'name'));

    Log::success('');
    Log::success('%s  Partide    Puncte    Piese', [mb_str_pad('Nume', $len)]);
    Log::success(mb_str_pad('', $len + 28, '-'));
    foreach ($ord as $x) {
      $p = $this->players[$x];
      $name = mb_str_pad($p->name, $len + 2);
      Log::success("%s  %3d       %4.1f     %4d",
                   [$name, $p->numGames, $p->score, $p->pieces]);
    }
    Log::success('');
  }

  private function sortPlayers(): array {
    $ord = range(0, count($this->players) - 1);

    usort($ord, function($a, $b) {
      $pa = $this->players[$a];
      $pb = $this->players[$b];
      if ($pa->score != $pb->score) {
        return $pb->score <=> $pa->score;
      }
      return $pb->pieces <=> $pa->pieces;
    });

    return $ord;
  }
}
