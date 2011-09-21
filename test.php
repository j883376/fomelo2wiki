<?php

    function substr_between($s, $l, $r)
    {  
        $il = strpos($s, $l, 0) + strlen($l);
        $ir = strpos($s, $r, $il);
        return substr($s, $il, ($ir - $il));
    }

    include('simple_html_dom.php');

    set_time_limit(0); // prevent maximum execution time timeout

    /*
    $values = '40 -40 38 4 -4 55 16 36 10 23 12';

    $attack_delay = true;

    $values = explode(' ', $values);

    foreach ($values as $pristine)
    {
        echo '<p>';

        $base = '0';

        echo 'pristine: ' . $pristine . '<br>';

        if ($pristine > 0)
        {
            if ($attack_delay === true)
            {
                $base = round($pristine * 1.10);
            }
            else
            {
                if ($pristine <= 10)
                    $base = $pristine - 1;
                else
                    $base = round($pristine / 1.10);
            }
        }
        else if ($pristine < 0)
        {
            if ($pristine < 0 && $pristine > -11)
                $base = $pristine - 1;
            else
                $base = round($pristine * 1.10);
        }

        echo 'base: ' . $base . '<br>';

        echo '</p>';
    }
    */

    $text = "Atk Delay: 21\n";

    $property = 'Atk Delay';

    if (preg_match('/' . $property . ':\s+([*]?[+-]?\d+[%]?[*]?)/', $text, $matches))
        print_r($matches);
?>
