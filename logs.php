#!/usr/bin/php
<?php

$files = null;
$targetStrings = null;
$daysold = null;
$timeline = array();
$outfile = time() . ".csv";
$searchString = null;
$minutesBefore = null;
$minutesAfter = null;
$portRange = array(30000, 40000);
$opts = array();

function parseArgs()
{
    global $argv, $daysold, $searchString, $minutesBefore, $minutesAfter, $opts;

    $defaults = array(
        "d" => "",
        "a" => 10,
        "b" => 20,
        "s" => "panic",
        "o" => "7",
        "o" => "7",
        "t" => 5,
        "w" => false,
    );

    $opts = getopt('d:a:b:o:s:t:w') + $defaults;

    if (trim($opts['d']) === "" || is_dir($opts['d']) === false || stristr($opts['d'], "/auto/cores/") === false) {
        $usage = "\n Usage: " . $argv[0] . " -d /auto/cores/c23213213/bundle/ -o 7 -b 25 -a 15 -s \"panic string\" -t 5\n";
        $usage = " Usage: " . $argv[0] . " -w 23213213213.csv\n\n";
        $usage .= "  -d /path  Directory to scan\n";
        $usage .= "  -b 20     loglines minutes before\n";
        $usage .= "  -a 10     loglines minutes after\n";
        $usage .= "  -s \"rpc\"  a search string\n";
        $usage .= "  -o 7      ignore files older than X days\n";
        $usage .= "  -t 5      number of time lines to build based upon match list\n";
        $usage .= "  -w file   start web server g\n\n";
        die($usage);
    }

    $daysold = intval($opts['o']);
    $searchString = $opts['s'];
    $minutesBefore = intval($opts['b']);
    $minutesAfter = intval($opts['a']);
}

function bundleDetails()
{
    global $opts;

    $size = trim(shell_exec("du -sh " . $opts['d'] . " | awk '{print \$1}'"));

    print shell_exec("head -n 18 " . $opts['d'] . "/ddr/var/support/autosupport");

    print "\n  Bundle size: $size\n\n";
}

function getDirContents($dir, &$results = array())
{
    global $daysold;
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            if (filemtime($path) > time() - 86400 * $daysold) {
                $results[] = $path;
            }
        } else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
}

function findTargetStrings($file, $i, $num_files)
{
    global $searchString;
    printf("  Searching [%s/%s] %-140s", $i, $num_files, $file);

    if (substr($file, -3) === ".gz") {
        if (file_exists(substr($file, 0, -3)) === false) {
            shell_exec("gunzip -k " . $file);
        }
        $file = substr($file, 0, -3);
    }

    $lines = file($file);
    $lines = preg_grep("/^.*" . $searchString . ".*$/i", $lines);

    print chr(13);

    return $lines;
}

function findAllTargetStrings()
{
    global $files, $targetStrings, $searchString, $opts;
    $logdir = $opts['d'] . "/ddr/var/log/debug";
    $targetStrings = array();

    print "  Looking for \"$searchString\" strings in $logdir\n";

    $num_files = count($files);
    $i = 1;

    foreach ($files as $file) {
        if (substr($file, 0, strlen($logdir)) === $logdir) {
            $targetStrings = array_merge($targetStrings, findTargetStrings($file, $i, $num_files));
        }
        $i++;
    }

    print "  Found " . count($targetStrings) . " matches\n\n";
}

function sortTargetStringsChronologically()
{
    global $targetStrings;
    $sortedTargetStrings = array();

    print "  Sorting match strings by time \n\n";

    foreach ($targetStrings as $targetStr) {
        if (preg_match("/[a-zA-Z]{3}\s+[0-9]{1,2}\s+[0-9]{2}:[0-9]{2}:[0-9]{2}/", $targetStr, $match) == 1) {
            $pTime = strtotime($match[0]);
            $sortedTargetStrings["$pTime"] = "$targetStr";
        }

        if (preg_match("/[0-9]{2}\/[0-9]{2}\s+[0-9]{2}:[0-9]{2}:[0-9]{2}/", $targetStr, $match) == 1) {
            $pTime = strtotime($match[0]);
            $sortedTargetStrings["$pTime"] = "$targetStr";
        }
    }

    ksort($sortedTargetStrings);
    $sortedTargetStrings = array_reverse($sortedTargetStrings);

    $targetStrings = $sortedTargetStrings;

    $i = 1;

    foreach ($targetStrings as $targetStr) {
        print " $i] " . trim($targetStr) . "\n";
        $i++;
    }
    print "\n";
}

function parseDirectory()
{
    global $files, $daysold, $opts;
    print "  Iterating directory " . $opts['d'] . "\n";
    $files = getDirContents($opts['d']);
    print "  Discovered " . count($files) . " files modified in the last $daysold days\n";
}

function generateMergedTimeLine()
{
    global $targetStrings, $files, $timeline, $minutesBefore, $minutesAfter, $numTimelines, $opts;

    $matches = array();

    $tlines = 0;

    foreach ($targetStrings as $targetStr) {

        $thematch = null;

        if (preg_match("/[a-zA-Z]{3}\s+[0-9]{1,2}\s+[0-9]{2}:[0-9]{2}:[0-9]{2}/", $targetStr, $match) == 1) {
            $thematch = $match;
        }

        if (preg_match("/[0-9]{2}\/[0-9]{2}\s+[0-9]{2}:[0-9]{2}:[0-9]{2}/", $targetStr, $match) == 1) {
            $thematch = $match;
        }

        print "  Building timeline around \"" . trim($targetStr) . "\"\n\n";

        $originalTime = strtotime($thematch[0]);

        foreach ($files as $file) {
            print chr(13);
            printf("  Inspecting: %-160s\n", $file);
            $lines = file($file);

            $i = 1;

            while ($i < $minutesBefore + $minutesAfter) {
                $cTime = ($originalTime - ($minutesBefore * 60)) + ($i * 60) - 60;
                $cMonth = date("M", $cTime);
                $cMonthNum = date("m", $cTime);
                $cDay = date("j", $cTime);
                $cHour = date("H", $cTime);
                $cMinute = date("i", $cTime);
                $cSecond = date("s", $cTime);

                print chr(13);
                print "  Retrieving log lines for: ";
                printf("%s %2s %'.02s:%'.02s          ", $cMonth, $cDay, $cHour, $cMinute);

                $path_parts = pathinfo($file);

                $temp = preg_grep('/^' . sprintf("%s %2s %'.02s:%'.02s", $cMonth, $cDay, $cHour, $cMinute) . '.*?$/', $lines);
                $temp = array_merge(preg_grep('/^' . sprintf("%s\/%s %'.02s:%'.02s", $cMonthNum, $cDay, $cHour, $cMinute) . '.*?$/', $lines), $temp);

                foreach ($temp as $line) {
                    $matches[$path_parts['filename']][] = $line;
                }

                $i++;
            }
            print chr(13);
            print "\033[F";
        }

        $tlines++;

        if ($tlines >= $opts['t']) {
            print "  Stopping as only " . $opts['t'] . " match timeline requested\n\n";
            break;
        }
    }

    $timeline = $matches;
}

function printmergedSortedTimeLine()
{
    global $timeline, $outfile;
    $sortedTimeline = array();

    print "  Sorting chronologically; log file merged timeline \n";

    foreach ($timeline as $filename => $lines) {
        foreach ($lines as $line) {
            if (preg_match("/[a-zA-Z]{3}\s+[0-9]{1,2}\s+[0-9]{2}:[0-9]{2}:[0-9]{2}/", $line, $match) == 1) {
                $pTime = strtotime($match[0]);
                $sortedTimeline["$pTime"] = array($filename, $line);
            }

            if (preg_match("/[0-9]{2}\/[0-9]{2}\s+[0-9]{2}:[0-9]{2}:[0-9]{2}/", $line, $match) == 1) {
                $pTime = strtotime($match[0]);
                $sortedTimeline["$pTime"] = array($filename, $line);
            }
        }
    }

    ksort($sortedTimeline);

    $fp = fopen($outfile, "w+");

    print "\nTimeline\n" . str_repeat("=", 120) . "\n";

    foreach ($sortedTimeline as $line) {
        printf("%-20s %s\n", $line[0], trim($line[1]));
        fwrite($fp, $line[0] . "," . trim($line[1]) . "\n");
    }
    fclose($fp);
}

function launchApache()
{
    global $outfile, $portRange;

    @unlink("a.pid");
    @unlink("error.log");

    if ($opts['w'] !== false) {
        $outfile = $opts['w'];
    }

    $port = rand($portRange[0], $portRange[1]);

    while (trim(shell_exec("netstat -nl | grep \"$port\"")) !== "") {
        $port = rand($portRange[0], $portRange[1]);
    }

    print "\nTimeline Web\n" . str_repeat("=", 120) . "\n";
    print "You can access the web page on http://" . trim(shell_exec("hostname -f ")) . ":$port/temp.html?file=" . $outfile . "\n";

    $cwdesc = str_replace('/', '\/', getcwd());
    shell_exec("sed 's/^Listen.*/Listen $port/g' -i " . getcwd() . "/apache.conf");
    shell_exec("sed 's/^<Directory.*>#cmt/<Directory " . $cwdesc . ">#cmt/g' -i " . getcwd() . "/apache.conf");
    shell_exec("sed 's/^<VirtualHost.*>/<VirtualHost \*:$port>/g' -i " . getcwd() . "/apache.conf");
    shell_exec("sed 's/DocumentRoot.*/DocumentRoot " . $cwdesc . "/g' -i " . getcwd() . "/apache.conf");
    shell_exec("sed 's/ErrorLog.*/ErrorLog " . $cwdesc . "\/error.log/g' -i " . getcwd() . "/apache.conf");
    shell_exec("sed 's/PidFile.*/PidFile " . $cwdesc . "\/a.pid/g' -i " . getcwd() . "/apache.conf");

    shell_exec("/usr/sbin/apache2 -X -f " . getcwd() . "/apache.conf >/dev/null 2>&1");
}

function webserver()
{
    if ($opts['w'] !== false) {
        launchApache();
    }
}

parseArgs();
webserver();
bundleDetails();
parseDirectory();
findAllTargetStrings();
sortTargetStringsChronologically();
generateMergedTimeLine();
printmergedSortedTimeLine();
launchApache();
