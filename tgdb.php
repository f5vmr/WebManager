<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/* talkgroup / number alias database */
$tgdb_array = [
    '0'     => 'Idle',
    '34'    => 'Locale',
    '91'   => 'FM Worldwide',
    '214'   => 'FM Espagne',
    '2350'   => 'FM UK Chat',
    '240'   => 'FM Sweden',
//    '260'    => 'FM Poland',
//    '262'   => 'FM Germany',
    '9050'  => 'USA East Coast Net - ASL',
    '49562' => 'USA Boredom Net - ASL',
    '2585' => 'USA Rochester Hampton Net - ASL',
    'Any TG'   => 'can be used anywhere even if unlisted',
    ];
?>
