<?php

require 'tcpdump.php';
require 'pcapFilterExpression.php';
$expr = new pcapFilterExpression();
$tcpdump = new tcpdump();

try {
    // initialize expression generator
    $expr->init();
    // compile expression
    $pcap_filter = $expr->begin()
            ->host('192.168.0.2')
            ->concate()
            ->port(5060)
            ->end()
            ->getPcapFilterExpressionString();

    // Init tcpdump
    $tcpdump->init(array('-s' => 0, '-A' => null));
    // Set tcpdump options
    $tcpdump->setInterface('eth0')
            ->setOutputFile('/tmp/192.168.0.3.pcap')
            ->setFileSize('100')
            ->setExprFilter($pcap_filter);

    // Compile the command
    echo $tcpdump->compileShellCoammnd() . "\r\n";
} catch (InvalidArgumentException $e) {
    trigger_error($e->getMessage(), E_USER_ERROR);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
