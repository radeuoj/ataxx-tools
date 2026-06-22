Hello. I would like your assistance in writing a browser-based viewer for the game Ataxx.

I have placed all the relevant documents at https://nerdvana.ro/ataxx-viewer/

# The game of Ataxx

* Ataxx is an abstract strategy game for two players.
* It is played on a 7×7 square grid.
* Rows are numbered `1` through `7` from top to bottom.
* Columns are named `a` through `g` from left to right.
* In a physical game there are 49 two-sided pieces, red on one side and blue on the other (similar to Reversi / Othello).
* In the starting position, the first player ("Red") has two red pieces at `a1` and `g7`.
* The other player ("Blue") has two blue pieces at `a7` and `g1`.
* Players take turns moving, with Red going first.
* There are two types of moves. A player may _clone_ a piece by placing a piece of their color on an empty square that is adjacent vertically, horizontally or diagonally to one of their existing pieces.
* A player may _jump_ by moving one of their pieces to an empty square that is two squares away horizontally, vertically or diagonally. For example, in the starting position Red may jump from `a1` to `c1`, `c2`, `a3`, `b3`, or `c3`. To clarify, a player may not jump one square away.
* After the move, all of the opponent player's pieces adjacent to the destination square are converted (flipped) to the color of the moving player.
* The game ends when the board fills up. The player who has more pieces wins. Since the number of squares is odd, there are no ties.

For our implementation, we use the following modification to the original rules.

* In the original rules, if a player may not move, either because he has no pieces left or because none of those pieces can reach an empty square by jumping or cloning, then he must pass. In our rules, if a player has no moves, the game ends and the opponent gets all the empty squares.

# Code organization

This section may or may not be relevant to you.

The subdirectory [ataxx-tools/arbiter](https://nerdvana.ro/ataxx-viewer/ataxx-tools/arbiter/) contains a rudimentary arbiter written in PHP. Its job is to organize round-robin Ataxx tournaments between several agents (computer programs). The arbiter schedules the rounds and runs the games. For each game, the arbiter invokes the two agents alternatively, giving them the current state of the game. Each agent must read the input (the game state), figure out the best move according to its algorithm, print that move and terminate.

The complete protocol is described at [ataxx-tools/protocol.md]([url](https://nerdvana.ro/ataxx-viewer/ataxx-tools/protocol.md)) (in Romanian). Here is a brief summary. Here is a brief summary.

On its turn, each agent receives the following data at the standard input:

* A line containing 0 or 1 to let the agent know if it has the red (0) or the blue (1) pieces.
* Seven lines with 7 characters per line describing the board. `x` denotes a red piece, `o` denotes a blue piece, and `.` denotes an empty square.
* A line containing two integers which denote the remaining clock times, in milliseconds, for Red and Blue respectively.

For example, assuming 3 minutes per person, the initial input data is:

```txt
0
x.....o
.......
.......
.......
.......
.......
o.....x
180000 180000
```

The agent must output a single line at the standard output. The line must contain a move in algebraic notation. For example, on its first move Red may output:

* `b2` to indicate cloning the piece from `a1` at `b2`
* `a1-b3` to indicate jumping from `a1` to `b3`
* etc.

The agent may print any debug information at the standard error. The arbiter will ignore all this **except** lines that begin with the text `kibitz `, which the arbiter collects and writes to the saved game. The agent may kibitz information such as analysis results, forced wins / losses etc.

The subdirectory [andromeda](https://nerdvana.ro/ataxx-viewer/andromeda/) contains a reasonably good agent using alpha-beta with iterative deepening and transposition tables. The subdirectory [ataxx-tools/agent](https://nerdvana.ro/ataxx-viewer/ataxx-tools/agent/) contains more agents which hang / throw errors / print incorrect outputs and so on. I used them to test the robustness of the arbiter.

The arbiter and the Andromeda agent use console graphics to show the board during the game, for debugging purposes. The file [board-screenshot](https://nerdvana.ro/ataxx-viewer/board-screenshot.png) contains a screenshot.

If any player times out, crashes or prints an incorrect moves, the opponent is awarded a 25-0 victory.

# The format of the saved game

This is the relevant part for writing the game viewer.

When the game concludes, the arbiter writes a JSON-formatted file. You can see some examples in the [games](https://nerdvana.ro/ataxx-viewer/games/) directory. I will use [round-001-andromeda-stable.json](https://nerdvana.ro/ataxx-viewer/games/round-001-andromeda-stable.json) as an example to describe the format.

* `players` contains the names of the red player and blue player, in this order.
* `time_per_game` contains the initial clock times in milliseconds (180000 milliseconds = 3 minutes per player per game in this example).
* `scores` contains the final score of the match. This can only be `[1, 0]` if Red wins or `[0, 1]` if Blue wins.
* `pieces` contains the final piece count of Red and Blue, in this order. Their sum will be 49 if the game ended normally. If either agent was disqualified, the piece counts will be 25-0 or 0-25.
* `turns` contains the turns that completed successfully. For each turn,
  * `move` is the move that the agent made.
  * `kibitzes` is an array of the lines that the agent kibitzed, possibly empty. In this example, Andromeda kibitzes some statistics about its alpha-beta algorithm. Stable does not kibitz.
  * `time` is the time the agent took for the move, in milliseconds
* Finally, `error` is empty if the game completed normally. If not empty, it represents the arbiter's message about the final turn (which is NOT included in `turns` above, because the agent did not succeed in making a move). Typically the arbiter will note what went wrong -- incorrect move, timeout, crash etc. See the remaining JSON files for examples.

The directory also includes a subdirectory matching the JSON file's name, for example [inputs-001-andromeda-stable/](https://nerdvana.ro/ataxx-viewer/games/inputs-001-andromeda-stable/). You don't need to worry about this. It stores all the inputs given to the agents, in case the programmers want to use them to debug their agents' behavior.

# What is needed

I would like a browser-based viewer where we can view individual games and entire tournaments.

## Game viewer

For the game viewer, I would like to upload a JSON file in the format given above, then view the game. If you are familiar with chess games replays on lichess.org or on chess.com, I would like you to mimic that UI. Here is an arbitrary [sample game](https://lichess.org/broadcast/fide-world-team-rapid-blitz-chess-championships-2026-blitz-knockout/final-game-2/S0rYQx6z/yatUC9iu).

Specifically, the UI should have the following components:

* A complete table of moves on two columns. Each row should list a move by Red (such as `a2`) and a move by Blue (such `a7-b5`).
* The board that displays the move currently being viewed, with the following elements:
  * The names of the players.
  * The 7×7 grid of pieces and empty squares (obviously).
  * Coordinates on the sides (`a` through `g` and `1` through `7`).
  * The clock times.
  * The present piece counts.
* Four buttons for:
  * going forward one move;
  * going back one move;
  * going back to the beginning of the game
  * going forward to the end of the game
* These buttons should also be controllable with the right arrow / left arrow / home / end keys respectively.
* Clicking on any move in the table should update the board to the point in time after that move is played.
* The board should initially display the initial position (before the first move is made).
* The UI must NOT display the final score and piece counts, unless the operator scrolls to the end of the game (we want to have the element of surprise while replaying games).

## Tournament viewer

For the tournament viewer, I would like to upload a zip file containing one JSON file per game. Games will be indexed by round and you can assume that it is a multi-round robin. For example, if there are four players named `abe`, `bee`, `cal` and `dee` which play a double round robin, then you can assume the following things:

* There will be 6 rounds.
* In rounds 1-3 everybody will play everybody.
* Rounds 4-6 are identical to rounds 1-4, but with colors reversed.
* If the tournament is a triple, quadruple etc. round robin, then you will see more rounds with more JSON files.
* JSON files inside the zip file are always coded as `round-XXX-player1-player2.json`, where `XXX` is the round number (three digits with leading zeroes) and `player1` and `player2` are the players' names, which will not contain dashes.
* For each file, the players' names in the file name match the players' names in the JSON.

The UI for the tournament viewer should have these components:

* A list (or table) of rounds.
* For each round, a list of games.
* When clicking on a game, the game interface described in the previous section should update to the beginning of that game.
* A rankings table with total scores up to the game currently being viewed.

The rankings table should look as follows:

* Each player should have a row in the table.
* The information for each player should include the name, the number of points scored in completed games and the sum of piece counts in completed games.
* Players should be sorted in decreasing order by points scored. As a tiebreaker, they should be sorted in decreasing order of piece counts.

The rankings table must NOT include the game currently being viewed until the operator scrolls to the end of the game, at which point the rankings table must update to include the game being viewed.
