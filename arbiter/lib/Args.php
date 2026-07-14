<?php

class Args {

  const OPTIONS = [
    'binary:',
    'name:',
    'rounds:',
    'save:',
    'save-inputs',
    'time:',
    'jobs:',
  ];

  private array $binaries;
  private array $names;
  private int $numRounds;
  private string $saveDir;
  private bool $saveInputs;
  private int $time;
  private int $jobs;

  function parse(): void {
    $opts = getopt('', self::OPTIONS);
    if (empty($opts)) {
      $this->usage();
      exit(1);
    }

    $this->binaries = $this->asArray($opts['binary'] ?? []);
    $this->names = $this->asArray($opts['name'] ?? []);
    $this->numRounds = $opts['rounds'] ?? 1;
    $this->saveDir = $opts['save'] ?? '';
    $this->saveInputs = isset($opts['save-inputs']);
    $this->time = $opts['time'] ?? 60;
    $this->jobs = $opts['jobs'] ?? 0;
    $this->validate();
  }

  private function usage(): void {
    $scriptName = $_SERVER['SCRIPT_FILENAME'];
    print "Apel: $scriptName --binary <cale> --name <nume> [...]\n";
    print "\n";
    print "    --binary <cale>    Fișierul binar executabil al unui agent sau 'human' pentru jucător uman.\n";
    print "    --name <nume>      Numele agentului.\n";
    print "    --rounds <număr>   Numărul de runde (implicit: 1).\n";
    print "    --save <cale>      Directorul unde vom salva partidele.\n";
    print "    --save-inputs      Salvează și toate datele de intrare.\n";
    print "    --time <număr>     Timpul permis în secunde (implicit: 60).\n";
    print "    --jobs <număr>     Numărul de meciuri rulate în paralel (implicit: toate).\n";
    print "\n";
    print "Opțiunile --binary și --name pot fi repetate pentru fiecare agent.\n";
  }

  // getopt() returnează un string dacă argumentul apare o singură dată, dar
  // un vector dacă argumentul apare de mai multe ori. Convertește și prima
  // situație la vector.
  private function asArray(mixed $x): array {
    return (gettype($x) == 'string') ? [$x] : $x;
  }

  private function validate(): void {
    if (count($this->binaries) < 2) {
      throw new AtaxxException('Trebuie să specifici cel puțin două binare.');
    }
    if (count($this->binaries) != count($this->names)) {
      throw new AtaxxException('Numărul de binare nu corespunde cu numărul de nume.');
    }
    if (Util::hasDuplicates($this->names)) {
      throw new AtaxxException('Jucătorii trebuie să aibă nume distincte.');
    }
    if (!$this->numRounds) {
      throw new AtaxxException('Argumentul --rounds nu poate fi 0.');
    }
    if ($this->saveDir && !is_dir($this->saveDir)) {
      throw new AtaxxException("Directorul {$this->saveDir} nu există.");
    }
    if ($this->saveInputs && !$this->saveDir) {
      $msg = 'Dacă specifici --save-inputs, trebuie să specifici și --save <cale>';
      throw new AtaxxException($msg);
    }
  }

  function getPlayers(): array {
    $result = [];
    foreach ($this->binaries as $i => $binary) {
      $realBinary = ($binary == 'human')
        ? $binary
        : realpath($binary);

      if ($realBinary != 'human') {
        if (!file_exists($realBinary)) {
          throw new AtaxxException("Fișierul {$binary} nu există.");
        }
        if (is_dir($realBinary)) {
          throw new AtaxxException("Fișierul {$binary} este un director.");
        }
        if (!is_executable($realBinary)) {
          throw new AtaxxException("Fișierul {$binary} nu este executabil.");
        }
      }

      $result[] = new Player($realBinary, $this->names[$i], $this->getTimeMillis());
    }
    return $result;
  }

  function getNumRounds(): int {
    return $this->numRounds;
  }

  function getSaveDir(): string {
    return $this->saveDir;
  }

  function getSaveInputs(): bool {
    return $this->saveInputs;
  }

  function getTimeMillis(): int {
    return $this->time * 1_000;
  }

  function getJobs(): int {
    return $this->jobs;
  }
}
