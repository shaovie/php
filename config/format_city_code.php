<?php

$cityCode = include 'city_code_book.php';

echo "<?php\n\n";
echo 'return array(' . "\n";
foreach ($cityCode as $key => $val) {
    if ($key % 10000 == 0 && ((int)($key / 10000)) * 10000 == $key) {
        echo "    $key" . ' => array(' . "\n";
        echo "        'name' => '$val',\n";
        echo "        'city' => array(\n";
        foreach ($cityCode as $key1 => $val1) {
            if ($key1 % 10000 != 0 && (int)($key / 10000) == (int)($key1 / 10000) && $key1 % 100 == 0) {
                echo "            $key1" . ' => array(' . "\n";
                echo "                'name' => '$val1',\n";
                echo "                'district' => array(\n";
                    foreach ($cityCode as $key2 => $val2) {
                        if ((int)($key1 / 100) == (int)($key2 / 100) && $key2 % 100 != 0) {
                            echo "                    $key2 => '$val2',\n";
                        }
                    }
                echo "                ),\n";
                echo "            ),\n";
            }
        }
        echo "        ),\n";
        echo "    ),\n";
    }
}
echo ");\n\n";

