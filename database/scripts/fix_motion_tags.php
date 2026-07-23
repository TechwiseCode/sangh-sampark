<?php
$files = [
    __DIR__ . '/../../app/Views/organization/events/index.php',
    __DIR__ . '/../../app/Views/organization/events/show.php',
];
$fromClose = '</mo' . 'tion>';
$toClose = '</div>';
$fromOpen = '<mo' . 'tion ';
$toOpen = '<div ';
foreach ($files as $f) {
    if (!is_file($f)) {
        continue;
    }
    $c = file_get_contents($f);
    $c = str_replace($fromClose, $toClose, $c);
    $c = str_replace($fromOpen, $toOpen, $c);
    file_put_contents($f, $c);
    echo "Fixed: $f\n";
}
