<?php
sleep(1);

if (!isset($_GET['v'])) {
    $_GET['v'] = 'wtf';
}

if (isset($_GET['done'])) {
    echo ':)';
} else {
    header('Location: /leak?done=' . $_GET['v']);
}
