<?php

declare(strict_types=1);

use src\bootstrap\Bootstrap;
use src\bootstrap\RunMode;

require __DIR__ . '/../vendor/autoload.php';

(new Bootstrap(dirname(__DIR__)))->init(RunMode::Http);
