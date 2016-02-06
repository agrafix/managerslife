# Manager's Life

This is the source of a browsergame I wrote in 2011. It's written in PHP5 and plain JavaScript. Note that the game content is in german. The game used to run at managerslife.de, but is now discontinued and open sourced. If you would like to run and host the game somewhere, please let me know :-)

## Features

* JavaScript 2D Game Engine
* Item/NPC Engine
* Poker Engine
* Quest Engine
* Black Jack implementation
* Payment Gateway for Premium features
* ... and much more

## Install

Note that his is probably not complete!

### Requirements

* PHP5
* Apache (+ModRewrite) (might also work with nginx)
* mysql

### Preparation

```bash
git clone https://github.com/agrafix/managerslife
cd managerslife
emacs config.php # adjust database values etc
mysql < schema.sql
mysql < prepare.sql
```

## License

Everything is released under the MIT license, except stated otherwise in a source file. Copyright (c) 2011-2016 Alexander Thiemann. See LICENSE File.

Graphics are under the Creative Commons Attribution Share-Alike and No Derivatives license.