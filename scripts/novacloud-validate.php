<?php

require_once dirname(__DIR__) . '/src/Appwrite/NovaCloud/ArchitectureValidator.php';

use Appwrite\NovaCloud\ArchitectureValidator;

$validator = new ArchitectureValidator(dirname(__DIR__));
$failures = $validator->failures();

if ($failures !== []) {
    fwrite(STDERR, "NovaCloud validation failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "NovaCloud architecture validation passed.\n";
