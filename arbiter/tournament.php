<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/lib/Core.php';

main();

function main(): void {
  try {
    $args = new Args();
    $args->parse();

    $t = new Tournament(
      $args->getPlayers(),
      $args->getNumRounds(),
      $args->getSaveDir(),
      $args->getSaveInputs(),
      $args->getJobs()
    );
    $t->run();
  } catch (AtaxxException $e) {
    Log::fatal($e->getMessage());
  }
}
