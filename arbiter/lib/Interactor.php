<?php

class Interactor {
  private string $inputFile;
  private string $outputFile;
  private string $errorFile;

  private string $binary;
  private string $input;

  private float $time;     // Timpul petrecut, inclusiv invocarea.
  private string $output;  // Conținutul fișierului de ieșire.
  private array $kibitzes; // Liniile chibițate, fără prefixul „kibitz ”.

  function __construct(string $binary, string $input) {
    $pid = getmypid();
    $this->inputFile  = "/tmp/input_$pid.txt";
    $this->outputFile = "/tmp/output_$pid.txt";
    $this->errorFile  = "/tmp/error_$pid.txt";
    $this->binary = $binary;
    $this->input = $input;
    $this->time = 0;
    $this->output = '';
    $this->kibitzes = [];
  }

  function getOutput(): Output {
    return new Output($this->output, $this->kibitzes);
  }

  function getKibitzes(): array {
    return $this->kibitzes;
  }

  function getTime(): int {
    return $this->time;
  }

  function parseAgentOutput(): void {
    $contents = @file_get_contents($this->outputFile);

    if ($contents === false) {
      Log::warning('Fișierul %s nu există.', [ $this->outputFile]);
      return;
    }

    $this->output = trim($contents);
    Log::info("Programul a tipărit [{$this->output}].");
  }

  function parseAgentError(): void {
    if (!file_exists($this->errorFile)) {
      return;
    }

    $contents = file_get_contents($this->errorFile);
    if ($contents) {
      Log::debug("Programul a tipărit la stderr:\n{$contents}");
    }

    $lines = file($this->errorFile);
    foreach ($lines as $line) {
      if (Str::startsWith($line, Config::KIBITZ_PREFIX)) {
        $suf = substr($line, strlen(Config::KIBITZ_PREFIX));
        $this->kibitzes[] = trim($suf);
      }
    }
  }

  function run(int $timeLimit): void {
    if ($this->binary == 'human') {
      $this->interactHuman();
    } else {
      $this->interactAgent($timeLimit);
    }
  }

  private function interactHuman(): void {
    $line = readline('Introdu o mutare: ');
    $this->output = $line;
  }

  private function interactAgent(int $timeLimit): void {
    $dir = dirname($this->binary);
    chdir($dir);
    file_put_contents($this->inputFile, $this->input);
    @unlink($this->outputFile);
    @unlink($this->errorFile);

    Log::debug('Apelez %s în directorul %s cu intrarea:', [ $this->binary, $dir ]);
    Log::debug(trim($this->input));
    $cmd = sprintf('ulimit -t %d && "%s" < %s > %s 2> %s',
                   intdiv($timeLimit, 1000) + 1, // ulimit cere secunde
                   $this->binary,
                   $this->inputFile,
                   $this->outputFile,
                   $this->errorFile);

    $resultCode = $this->runCmd($cmd);
    if ($resultCode !== 0) {
      $msg = "Agentul s-a terminat cu codul {$resultCode}.";
      throw new AtaxxException($msg);
    }

    $this->parseAgentOutput();
    $this->parseAgentError();
  }

  private function runCmd(string $cmd): int {
    $ignoredOutput = null;
    $resultCode = null;

    $startTime = Util::getTimeMillis();
    exec($cmd, $ignoredOutput, $resultCode);
    $endTime = Util::getTimeMillis();
    $this->time = $endTime - $startTime;
    Log::debug('Timp de rulare: %0.3f secunde.', [ $this->time / 1000 ]);
    return $resultCode;
  }
}
