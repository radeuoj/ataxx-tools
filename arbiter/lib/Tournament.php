<?php

class Tournament {
  private array $players;
  private int $numRounds;
  private string $saveDir;
  private bool $saveInputs;
  private Fixtures $fixtures;
  private int $maxJobs;

  function __construct(array $players, int $numRounds, string $saveDir,
                       bool $saveInputs, int $maxJobs = 0) {
    $this->players = $players;
    $this->numRounds = $numRounds;
    $this->saveDir = $saveDir;
    $this->saveInputs = $saveInputs;
    $this->fixtures = new Fixtures(count($players));
    $this->maxJobs = $maxJobs;
  }

  function run(): void {
    $allMatches = [];
    for ($r = 0; $r < $this->numRounds; $r++) {
      $allMatches[] = $this->fixtures->getMatches();
    }

    $pids = [];
    $resultFiles = [];

    for ($round = 0; $round < $this->numRounds; $round++) {
      if ($this->maxJobs > 0) {
        while (count($pids) >= $this->maxJobs) {
          $pid = pcntl_waitpid(-1, $status);
          if ($pid > 0) {
            unset($pids[$pid]);
          }
        }
      }

      $resultFile = tempnam('/tmp', 'ataxx_round_');
      $resultFiles[$round] = $resultFile;

      $pid = pcntl_fork();
      if ($pid === 0) {
        $this->runRoundChild($round, $allMatches[$round], $resultFile);
        exit(0);
      }
      $pids[$pid] = true;
    }

    while (!empty($pids)) {
      $pid = pcntl_waitpid(-1, $status);
      if ($pid > 0) {
        unset($pids[$pid]);
      }
    }

    for ($round = 0; $round < $this->numRounds; $round++) {
      $resultFile = $resultFiles[$round];
      $data = @unserialize(file_get_contents($resultFile));
      @unlink($resultFile);

      $this->roundBanner($round);

      if ($data === false || isset($data['error'])) {
        $msg = $data['error'] ?? 'eroare necunoscută';
        Log::error("Eroare în runda %d: %s", [$round + 1, $msg]);
        continue;
      }

      foreach ($data['results'] as $entry) {
        [$id1, $id2, $s0, $s1, $p0, $p1] = $entry;
        $this->players[$id1]->addResult($s0, $p0);
        $this->players[$id2]->addResult($s1, $p1);
      }

      $this->report();
    }
  }

  private function runRoundChild(int $round, array $matches,
                                 string $resultFile): void {
    try {
      $results = [];
      foreach ($matches as [$id1, $id2]) {
        $p1 = $this->players[$id1];
        $p2 = $this->players[$id2];
        $g = new Game($p1, $p2);
        $g->run();

        $gi = $g->getInfo();
        $saver = new Saver($gi, [$p1, $p2], $round,
                           $this->saveDir, $this->saveInputs);
        $saver->saveAll();

        $results[] = [
          $id1, $id2,
          $gi->getScore(0), $gi->getScore(1),
          $gi->getPieces(0), $gi->getPieces(1),
        ];
      }
      file_put_contents($resultFile, serialize(['results' => $results]));
    } catch (\Throwable $e) {
      file_put_contents($resultFile, serialize([
        'error' => $e->getMessage(),
      ]));
    }
  }

  private function roundBanner(int $id): void {
    $msg = sprintf("        Runda %d / %d        ", $id + 1, $this->numRounds);
    Log::successBanner($msg);
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
