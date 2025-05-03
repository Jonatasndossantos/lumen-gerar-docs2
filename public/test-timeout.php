<?php

// Mostra todas as configurações de timeout
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "max_input_time: " . ini_get('max_input_time') . "\n";
echo "set_time_limit(300) result: " . (set_time_limit(300) ? "true" : "false") . "\n";

// Tenta executar por mais de 30 segundos
$start = time();
while (time() - $start < 35) {
    echo "Still running... " . (time() - $start) . " seconds passed\n";
    sleep(5);
}

echo "Test completed successfully!\n"; 