# PHPCli

The lightest PHP CLI helper classes

## Requirement

PHP 5.4+

## Get Started

### Install via composer

Add PHPCli to composer.json configuration file.
```
$ composer require yanghuxiao/PHPCli
```

And update the composer
```
$ composer update
```

```php
// If you installed via composer, just use this code to requrie autoloader on the top of your projects.
require 'vendor/autoload.php';

// Using Medoo namespace
use PHPCli\PHPCli;

// Initialize
PHPCli::init();

// Gets a single command-line option. Returns TRUE if the option exists, but doesn't have a value, and is simply acting as a flag.
PHPCli::getOption('a');

// Enter a number of empty lines
PHPCli::newLine(2);

// Outputs a string to the cli.
PHPCli::write('PHPCli','green','yellow');

// Clears the screen of output
PHPCli::clearScreen();

// Waits a certain number of seconds, optionally showing a wait message and waiting for a key press.
PHPCli::wait(3);

// Outputs an error to the CLI using STDERR instead of STDOUT
PHPCli::error('error.....');

// Asks the user for input.  This can have either 1 or 2 arguments.
PHPCli::prompt();
$color = PHPCli::prompt('What is your favorite color?');
$color = PHPCli::prompt('What is your favourite color?', 'white');
$ready = PHPCli::prompt('Are you ready?', array('y','n'));

```


## License

PHPCli is under the MIT license.