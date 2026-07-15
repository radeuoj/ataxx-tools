<?php

/**
 * Fișier de configurare.
 **/

class Config {
  // Una dintre constantele din lib/Log.php. Cu cît este mai mare, cu atît
  // arbitrul va scrie pe ecran mai multe informații de debug.
  static int $LOG_LEVEL = Log::DEBUG;

  const KIBITZ_PREFIX = 'kibitz ';

  // Numărul de salturi consecutive la care partida este declarată remiză.
  const MAX_JUMPS = 100;

  // Numele fișierelor salvate în directorul indicat de --save. Vom înlocui cu
  // sprintf() numărul rundei și al mutării și numele jucătorilor.
  const SAVE_GAME_FILE = 'round-%03d-%s-%s.json';
  const SAVE_INPUT_DIR = 'inputs-%03d-%s-%s';
  const SAVE_INPUT_FILE = 'input-%03d.txt';

  const BOARD_SIZE = 7;
  const INIT_BOARD = [
    [  0, -1, -1, -1, -1, -1,  1 ],
    [ -1, -1, -1, -1, -1, -1, -1 ],
    [ -1, -1, -1, -1, -1, -1, -1 ],
    [ -1, -1, -1, -1, -1, -1, -1 ],
    [ -1, -1, -1, -1, -1, -1, -1 ],
    [ -1, -1, -1, -1, -1, -1, -1 ],
    [  1, -1, -1, -1, -1, -1,  0 ],
  ];
}
