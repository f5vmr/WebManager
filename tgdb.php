<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/* talkgroup / number alias database */
$tgdb_array = [
    '0'     => 'Idle',
    '91'   => 'FM Worldwide',
    '202' => 'Greece',
    '208' => 'France RI49',
    '235'   => 'FM United Kingdom',
    '240' => 'Sweden',
    '655' => 'South Africa - Johannesburg',
    '2345'  => 'FM North Pennines',
    '2350'   => 'FM UK Chat 0',
    '2351'   => 'FM UK Chat 1',
    '2600'  => 'FM - Poland',
    '23450' => 'FM Yorkshire Net',
    '23500' => 'FM UK South West',
    '23510' => 'FM UK South East',
    '23520' => 'FM UK North East',
    '23525' => 'FM UK Freedom Nets',
    '23529' => 'FM UK Anglo Scottish',
    '23530' => 'FM UK Yorks & Humberside',
    '23540' => 'FM UK Wales',
    '23550' => 'FM UK Scotland',
    '23556' => 'DVS Scotland',
    '23560' => 'FM UK North East',
    '23561' => 'FM UK Engineering Channel',
    '23570' => 'FM UK Northern Ireland',
    '23580' => 'FM UK West Midlands',
    '23590' => 'FM UK East Midlands',
    '240'   => 'FM Sweden',
//    '260'    => 'FM Poland',
//    '262'   => 'FM Germany',
    '2585' => 'Rochester Hampton ASL',
    '9050'  => 'USA East Coast Net - ASL',
    '43136' => 'Ham Radio 2.0',
    '53573' => 'NorthWest Allstar Group',
    '47920' => 'Online Amateur Radio Community',
    '49562' => 'USA Boredom Net - ASL',
    '2585' => 'USA Rochester Hampton Net - ASL',
    'Any TG'   => 'can be used anywhere even if unlisted',
    ];
?>

