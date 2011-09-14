<?php

    function substr_between($s, $l, $r)
    {  
        $il = strpos($s, $l, 0) + strlen($l);
        $ir = strpos($s, $r, $il);
        return substr($s, $il, ($ir - $il));
    }

    include('simple_html_dom.php');

    set_time_limit(0); // prevent maximum execution time timeout

    $values = Array();
    array_push($values, '40');
    array_push($values, '-40');
    array_push($values, '38');
    array_push($values, '-4');
    array_push($values, '55');
    array_push($values, '16');
    array_push($values, '36');
    array_push($values, '10');
    array_push($values, '23');
    array_push($values, '12');

    $attack_delay = false;

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
?>
