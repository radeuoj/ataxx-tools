Arbitrul organizează turnee între mai mulți agenți. Arbitrul ține evidența partidei curente și invocă pe rînd fiecare agent, comunicîndu-i starea partidei. Apoi citește răspunsul agentului și actualizează starea jocului. După fiecare partidă, arbitrul raportează clasamentul.

Puteți folosi arbitrul și ca să rulați o singură partidă, configurînd un turneu cu doi jucători și o singură rundă.

## Pas specific pentru Windows: instalează WSL

Recomandarea mea întotdeauna este să rulați Linux nativ, din motive prea ample ca să încapă într-un singur paragraf. Dacă totuși doriți să folosiți Windows, arbitrul este scris în PHP. Există mai multe moduri de a rula PHP în Windows. Instrucțiunile următoare documentează prima metodă (WSL).

1. Prin [WSL]([url](https://learn.microsoft.com/en-us/windows/wsl/)) (Windows Subsystem for Linux).
2. Printr-o mașină virtuală.
3. Direct cu PHP pentru Windows (nu am încercat).

Instalați o distribuție de GNU/Linux (implicit Ubuntu). Din terminal, listați distribuțiile disponibilie:

```bash
wsl --list --online
```

Apoi instalați-o pe cea dorită, de exemplu

```bash
wsl --install Ubuntu-26.04
```

Va cere reboot, apoi va finaliza instalarea. Alegeți-vă un nume de utilizator și o parolă. Apoi vă veți găsi într-un prompt de Linux.

Aduceți la zi sistemul și instalați PHP. Pentru Ubuntu, comenzile necesare sînt:

```bash
sudo apt update
sudo apt upgrade
sudo apt install php
```

Testați că PHP merge:

```bash
php --version
```

Puteți vedea sistemul de fișiere Windows din Linux:

```bash
ls /mnt/c/
```

De asemenea, puteți vedea sistemul de fișiere Linux din Windows. Din File Explorer, navighați la Linux > Ubuntu-26.04 > /home/\<username\>/ etc.

## Clonați repoul și testați arbitrul

Navigați într-un director bine ales. 🙂 Apoi:

```bash
git clone https://github.com/nerdvana-ro/ataxx-tools
cd ataxx-tools
```

Pe viitor, avînd în vedere că eu continui să lucrez la cod, puteți obține ultima versiune a codului executînd, din interiorul directorului, comanda:

```bash
git pull
```

Rulați arbitrul, fără argumente, ca să vă asigurați că merge:

```bash
php arbiter/tournament.php
```

Dacă merge, veți vedea un mesaj cu instrucțiuni de apelare.

## Compilați agentul Doofus

**Notă**: Puteți folosi și agentul Simpleton, care constă dintr-un singur fișier C++ și include și un binar deja compilat. Dar vă recomand să exersați compilarea lui Doofus ca să vă obișnuiți cu organizarea codului.

Pentru aceasta, veți avea nevoie de compilatorul de C++ (`g++`) și de utilitarele `cmake` și `make`. Vom descoperi împreună ce pachete trebuie instalate. Pentru Ubuntu, cred că sînt acestea:

```bash
sudo apt install build-essential cmake
```

Acum puteți compila agentul:

```bash
cd agent/doofus/build
cmake ../
make
cd ../../../
```

## Rulați o partidă între două copii ale agentului Doofus

```bash
php arbiter/tournament.php --binary agent/doofus/build/doofus --name doofus1 --binary agent/doofus/build/doofus --name doofus2
```

Arbitrul va vărsa ecrane întregi de informații, cu starea jocului după fiecare mutare (grafică text).

Avem nevoie și să salvăm partidele ca să le putem studia. Creați un director pentru partidele salvate, de exemplu:

```bash
mkdir ~/Desktop/games
```

Rulați din nou arbitrul și spuneți-i să salveze partida:

```bash
php arbiter/tournament.php --binary agent/doofus/build/doofus --name doofus1 --binary agent/doofus/build/doofus --name doofus2 --save ~/Desktop/games/
```

Acum în `~/Desktop/games` veți găsi fișierul `round-001-doofus1-doofus2`.

## Script Bash

Dacă vă ajută, puteți colecta toate opțiunile într-un script Bash. Repoul include scriptul `arbiter/tournament.sh`, pe care îl puteți modifica.

```bash
#!/usr/bin/bash

SAVE_DIR=~/Desktop/ataxx-games

rm -rf $SAVE_DIR
mkdir $SAVE_DIR

php arbiter/tournament.php \
    --binary agent/doofus/build/doofus --name doofus1 \
    --binary agent/doofus/build/doofus --name doofus2 \
    --rounds 1 \
    --save $SAVE_DIR \
    --save-inputs
```

## Opțiuni de configurare pentru arbitru

Arbitrul mai admite opțiunile `--rounds <număr>` pentru a organiza mai mult de o partidă și `--save-inputs` pentru a salva toate fișierele de intrare pentru fiecare partidă. Aceasta vă poate ajuta să depanați un bug care survine pe parcursul partidei.

În plus, puteți modifica valorile constantelor din `Config.php`. Fiecare constantă este documentată. De exemplu, puteți reduce nivelul de zgomot modificînd valoarea lui `LOG_LEVEL` la `Log::INFO` ca să nu mai tipărească mesajele de debug.

## Adversar uman

Dacă doriți să jucați voi înșivă o partidă contra agentului, puteți pasa `--binary human --name orice_nume`. Cînd vă vine rîndul, arbitrul va aștepta o mutare de la tastatură, în formatul cunoscut.
