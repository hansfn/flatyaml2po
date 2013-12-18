<?php

/* 

Converts flat YAML files to PO files, and back
==============================================

Written by Hans F. Nordhaug - hansfn@gmail.com

The script is licensed under GPL v3.

See README for more information.

*/

if (version_compare(PHP_VERSION, '5.0.0', '<')) {
    die("This script requires PHP version 5.0.0 or newer.\n");
}

if (!defined('STDIN')) {
    die("This script should only be run on the command line.\n");
}

define('CR', "\r");          // Carriage Return: Mac
define('LF', "\n");          // Line Feed: Unix
define('CRLF', "\r\n");      // Carriage Return and Line Feed: Windows

// Remove the script name from the argument vector and update the argument count
array_shift($argv);
$argc = count($argv);

if ($argc < 2 || $argc > 3) {
    $msg = "Usage: php yaml2po.php [option] yaml-file po-file\n";
    $msg .= "The only option is '-r' to reverse the process, convert from PO to YAML.\n";  
    die($msg);
} else {
    $reverse = false;
    if ($argc == 3) {
        if ($argv[0] == "-r") {
            array_shift($argv);
            $reverse = true;
        } else {
            die("Unknown option - the script accepts only '-r'.\n");
        }
    }
    $yaml_file = $argv[0];
    $po_file = $argv[1];
}

if (!$reverse) {
    if (!file_exists($yaml_file)) {
        die("The YAML file doesn't exist.\n");
    } else if (!is_readable($yaml_file)) {
        die("The YAML file isn't readable.\n");
    }
} else {
    if ($reverse && !file_exists($po_file)) {
        die("The PO file doesn't exist.\n");
    } else if (!is_readable($po_file)) {
        die("The PO file isn't readable.\n");
    }
}

error_reporting(E_ALL);
ini_set('display_errors',1);


if (!$reverse) {
    $po_text = yaml2po($yaml_file);
    if (file_put_contents($po_file, $po_text) === false) {
        die("Failed to write PO file.\n");
    } else {
        echo "Wrote PO file successfully.\n";
    }
} else {
    $yaml_text = po2yaml($po_file);
    if (file_put_contents($yaml_file, $yaml_text) === false) {
        die("Failed to write YAML file.\n");
    } else {
        echo "Wrote YAML file successfully.\n";
    }
}

function yaml2po($yaml_file) {
    // Loading the file into an array - ignore empty lines and new lines.
    $yaml_lines = file($yaml_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $po_lines = array();
    foreach ($yaml_lines as $line) {
        // Ignore comments and lines with only white space characters 
        if ((strpos($line, '#') === 0) || (strlen(trim($line)) == 0)) {
            continue;
        }
        // Handle the (flat) YAML lines:
        // - Strip leading and ending double quotes (if any) 
        // - Remove ending comment from empty translations.
        $with_quotes = false;
        if (strpos($line, '":') > 0) {
            list ($msgid, $msgstr) = explode('":', $line);
            $with_quotes = true;
        } else {
            list ($msgid, $msgstr) = explode(':', $line);
        }
        $msgid = trim($msgid);
        $msgstr = trim($msgstr);
        if ($with_quotes) {
            $msgid = preg_replace('/^"(.*?)"$/', '\1', $msgid);
            $msgstr = preg_replace('/^"(.*?)"$/', '\1', $msgstr);
        }
        $msgstr = preg_replace('/^#.*/', '', $msgstr);
        $po_lines[] = sprintf('msgid "%s"'. LF . 'msgstr "%s"' . LF, $msgid, $msgstr);
    }
    $po_header = 'msgid ""
msgstr ""
"Project-Id-Version: Bolt VERSION\n"
"Report-Msgid-Bugs-To: \n"
"POT-Creation-Date: CURR_DATE\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL ADDRESS>\n"
"Language-Team: LANGUAGE <LL li org>\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
';
    $po_header = str_replace('CURR_DATE', date("Y-m-d H:iO"), $po_header);
    return $po_header . LF . implode(LF, $po_lines);
}

function po2yaml($po_file) {
    // Loading the file into an array - ignore empty lines and new lines.
    $po_lines = file($po_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $yaml_lines = array();
    $count = count($po_lines);
    $i = 0;
    while ($i < $count) {
        $line = trim($po_lines[$i]);
        // Ignore comments and lines with only white space characters 
        if ((strpos($line, '#') === 0) || (strlen($line) == 0)) {
            $i++;
            continue;
        }
        // Handle msgid and msgstr - possibly multiline
        if (strpos($line, 'msgid') === 0) {
            // Handle last read msgstr and create yaml_line.
            if (!empty($yaml_key)) {
                $yaml_value = getyaml($po_text, 'msgstr');
                $yaml_lines[] = "$yaml_key: $yaml_value";
            }
            $po_text = $line;
        } else if (strpos($line, 'msgstr') === 0) {
            // Handle last read msgid and grab yaml key.
            $yaml_key = getyaml($po_text, 'msgid');
            $po_text = $line;
        } else {
            $po_text .= $line;
        }

        $i++;
    }
    $yaml_value = getyaml($po_text, 'msgstr');
    $yaml_lines[] = "$yaml_key: yaml_value";

    return implode(LF, $yaml_lines);;
}

function getyaml($po_text, $po_key) {
    $yaml = trim(preg_replace('/^' . preg_quote($po_key) .'[ ]+/', '', $po_text));
    if (($po_key == 'msgid') && ($yaml == '""')) {
        echo "Skipping PO header\n";
        $yaml = "";
    } else {
        // Remove artifects after merging multiline PO 
        $yaml = str_replace('"""', '"', $yaml);
        $yaml = str_replace('""', '', $yaml);
    }

    return $yaml;
}
