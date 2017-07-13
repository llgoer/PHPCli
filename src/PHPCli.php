<?php
/*!
 * PHP Cli
 * 
 * Version 0.0.2
 *
 * Portions of this code were initially from the FuelPHP Framework,
 * version 1.7.x, and used here under the MIT license they were
 * originally made available under.
 *
 * https://github.com/yanghuxiao/PHPCli <yanghuxiao@vip.qq.com>
 *
 * Released under the MIT license
 */

namespace PHPCli;

class PHPCli
{
    public static $readline_support = false;
    protected static $initialized = false;
    protected static $segments = [];
    protected static $options = [];

    protected static $foreground_colors = [
        'black'        => '0;30',
        'dark_gray'    => '1;30',
        'blue'         => '0;34',
        'dark_blue'    => '1;34',
        'light_blue'   => '1;34',
        'green'        => '0;32',
        'light_green'  => '1;32',
        'cyan'         => '0;36',
        'light_cyan'   => '1;36',
        'red'          => '0;31',
        'light_red'    => '1;31',
        'purple'       => '0;35',
        'light_purple' => '1;35',
        'light_yellow' => '0;33',
        'yellow'       => '1;33',
        'light_gray'   => '0;37',
        'white'        => '1;37',
    ];

    protected static $background_colors = [
        'black'      => '40',
        'red'        => '41',
        'green'      => '42',
        'yellow'     => '43',
        'blue'       => '44',
        'magenta'    => '45',
        'cyan'       => '46',
        'light_gray' => '47',
    ];

    public static function init()
    {
        static::$readline_support = extension_loaded('readline');
        static::parseCommandLine();
        static::$initialized = true;
    }

    private static function parseCommandLine()
    {
        $optionsFound = false;
        for ($i=1; $i < $_SERVER['argc']; $i++)
        {
            if (! $optionsFound && mb_strpos($_SERVER['argv'][$i], '-') === false)
            {
                static::$segments[] = $_SERVER['argv'][$i];
                continue;
            }
            $optionsFound = true;
            if (mb_substr($_SERVER['argv'][$i], 0, 1) != '-')
            {
                continue;
            }
            $arg = str_replace('-', '', $_SERVER['argv'][$i]);
            $value = null;
            // if the next item doesn't have a dash it's a value.
            if (isset($_SERVER['argv'][$i+1]) && mb_substr($_SERVER['argv'][$i+1], 0, 1) != '-')
            {
                $value = $_SERVER['argv'][$i+1];
                $i++;
            }
            static::$options[$arg] = $value;

            $optionsFound = false;
        }
    }

    public static function getOption(string $name)
    {
        if (! array_key_exists($name, static::$options))
        {
            return null;
        }
        $val = static::$options[$name] === null
            ? true
            : static::$options[$name];
        return $val;
    }

    public static function getOptions()
    {
        return static::$options;
    }

    public static function getOptionString(): string
    {
        if (! count(static::$options))
        {
            return '';
        }
        $out = '';
        foreach (static::$options as $name => $value)
        {
            // If there's a space, we need to group
            // so it will pass correctly.
            if (mb_strpos($value, ' ') !== false)
            {
                $value = '"'.$value.'"';
            }
            $out .= "-{$name} $value ";
        }
        return $out;
    }

    public static function newLine(int $num = 1)
    {
        for ($i = 0; $i < $num; $i++)
        {
            static::write('');
        }
    }

    public static function isWindows()
    {
        return 'win' === strtolower(substr(php_uname("s"), 0, 3));
    }

    public static function color(string $text, string $foreground, string $background = null, string $format = null)
    {
        if (static::isWindows() && ! isset($_SERVER['ANSICON']))
        {
            return $text;
        }
        if ( ! array_key_exists($foreground, static::$foreground_colors))
        {
            throw new \RuntimeException('Invalid CLI foreground color: '.$foreground);
        }
        if ($background !== null && ! array_key_exists($background, static::$background_colors))
        {
            throw new \RuntimeException('Invalid CLI background color: '.$background);
        }
        $string = "\033[".static::$foreground_colors[$foreground]."m";
        if ($background !== null)
        {
            $string .= "\033[".static::$background_colors[$background]."m";
        }
        if ($format === 'underline')
        {
            $string .= "\033[4m";
        }
        $string .= $text."\033[0m";
        return $string;
    }

    public static function getWidth(int $default = 80): int
    {
        if (static::isWindows())
        {
            return $default;
        }
        return (int)shell_exec('tput cols');
    }

    public static function getHeight(int $default = 32): int
    {
        if (static::isWindows())
        {
            return $default;
        }
        return (int)shell_exec('tput lines');
    }

    public static function showProgress($thisStep = 1, int $totalSteps = 10)
    {
        static $inProgress = false;
        // restore cursor position when progress is continuing.
        if ($inProgress !== false && $inProgress <= $thisStep)
        {
            fwrite(STDOUT, "\033[1A");
        }
        $inProgress = $thisStep;
        if ($thisStep !== false)
        {
            // Don't allow div by zero or negative numbers....
            $thisStep   = abs($thisStep);
            $totalSteps = $totalSteps < 1 ? 1 : $totalSteps;
            $percent = intval(($thisStep / $totalSteps) * 100);
            $step    = (int)round($percent / 10);
            // Write the progress bar
            fwrite(STDOUT, "[\033[32m".str_repeat('#', $step).str_repeat('.', 10 - $step)."\033[0m]");
            // Textual representation...
            fwrite(STDOUT, sprintf(" %3d%% Complete", $percent).PHP_EOL);
        }
        else
        {
            fwrite(STDOUT, "\007");
        }
    }

    public static function wrap(string $string = null, int $max = 0, int $pad_left = 0): string
    {
        if (empty($string))
        {
            return '';
        }
        if ($max == 0)
        {
            $max = CLI::getWidth();
        }
        if (CLI::getWidth() < $max)
        {
            $max = CLI::getWidth();
        }
        $max = $max - $pad_left;
        $lines = wordwrap($string, $max);
        if ($pad_left > 0)
        {
            $lines = explode(PHP_EOL, $lines);
            $first = true;
            array_walk($lines, function (&$line, $index) use ($max, $pad_left, &$first)
            {
                if ( ! $first)
                {
                    $line = str_repeat(" ", $pad_left).$line;
                }
                else
                {
                    $first = false;
                }
            });
            $lines = implode(PHP_EOL, $lines);
        }
        return $lines;
    }


    public static function clearScreen()
    {
        static::isWindows()
            // Windows is a bit crap at this, but their terminal is tiny so shove this in
            ? static::newLine(40)
            // Anything with a flair of Unix will handle these magic characters
            : fwrite(STDOUT, chr(27)."[H".chr(27)."[2J");
    }

    public static function input(string $prefix = null): string
    {
        if (static::$readline_support)
        {
            return readline($prefix);
        }
        echo $prefix;
        return fgets(STDIN);
    }

    /**
     * Asks the user for input.  This can have either 1 or 2 arguments.
     *
     * Usage:
     *
     * // Waits for any key press
     * CLI::prompt();
     *
     * // Takes any input
     * $color = CLI::prompt('What is your favorite color?');
     *
     * // Takes any input, but offers default
     * $color = CLI::prompt('What is your favourite color?', 'white');
     *
     * // Will only accept the options in the array
     * $ready = CLI::prompt('Are you ready?', array('y','n'));
     *
     * @return    string    the user input
     */
    public static function prompt(): string
    {
        $args = func_get_args();
        $options = [];
        $output  = '';
        $default = null;
        // How many we got
        $arg_count = count($args);
        // Is the last argument a boolean? True means required
        $required = end($args) === true;
        // Reduce the argument count if required was passed, we don't care about that anymore
        $required === true && --$arg_count;
        // This method can take a few crazy combinations of arguments, so lets work it out
        switch ($arg_count)
        {
            case 2:
                // E.g: $ready = CLI::prompt('Are you ready?', array('y','n'));
                if (is_array($args[1]))
                {
                    list($output, $options) = $args;
                }
                // E.g: $color = CLI::prompt('What is your favourite color?', 'white');
                elseif (is_string($args[1]))
                {
                    list($output, $default) = $args;
                }
                break;
            case 1:
                // No question (probably been asked already) so just show options
                // E.g: $ready = CLI::prompt(array('y','n'));
                if (is_array($args[0]))
                {
                    $options = $args[0];
                }
                // Question without options
                // E.g: $ready = CLI::prompt('What did you do today?');
                elseif (is_string($args[0]))
                {
                    $output = $args[0];
                }
                break;
        }
        // If a question has been asked with the read
        if ($output !== '')
        {
            $extra_output = '';
            if ($default !== null)
            {
                $extra_output = ' [ Default: "'.$default.'" ]';
            }
            elseif ($options !== [])
            {
                $extra_output = ' [ '.implode(', ', $options).' ]';
            }
            fwrite(STDOUT, $output.$extra_output.': ');
        }
        // Read the input from keyboard.
        $input = trim(static::input()) ? : $default;
        // No input provided and we require one (default will stop this being called)
        if (empty($input) && $required === true)
        {
            static::write('This is required.');
            static::newLine();
            $input = forward_static_call_array([__CLASS__, 'prompt'], $args);
        }
        // If options are provided and the choice is not in the array, tell them to try again
        if ( ! empty($options) && ! in_array($input, $options))
        {
            static::write('This is not a valid option. Please try again.');
            static::newLine();
            $input = forward_static_call_array([__CLASS__, 'prompt'], $args);
        }
        return empty($input) ? '' : $input;
    }

    public static function wait(int $seconds, bool $countdown = false)
    {
        if ($countdown === true)
        {
            $time = $seconds;
            while ($time > 0)
            {
                fwrite(STDOUT, $time.'... ');
                sleep(1);
                $time--;
            }
            static::write();
        }
        else
        {
            if ($seconds > 0)
            {
                sleep($seconds);
            }
            else
            {
                static::write(static::$wait_msg);
                static::input();
            }
        }
    }

    public static function error(string $text, string $foreground = 'light_red', string $background = null)
    {
        if ($foreground || $background)
        {
            $text = static::color($text, $foreground, $background);
        }
        fwrite(STDERR, $text.PHP_EOL);
    }

    public static function write(string $text = '', string $foreground = null, string $background = null)
    {
        if ($foreground || $background)
        {
            $text = static::color($text, $foreground, $background);
        }
        fwrite(STDOUT, $text.PHP_EOL);
    }
}