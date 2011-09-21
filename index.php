<html>

<head>

    <meta http-equiv="content-type" content="text/html" charset="utf-8" />

    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>

    <link rel="stylesheet" type="text/css" href="index.css" />

    <title>fomelo2wiki</title>

</head>

<body>

    <div id="top"></div>

    <div id="id_floating_links"><a href="http://shardsofdalaya.com/">Shards of Dalaya</a> | <a href="http://forum.shardsofdalaya.com/">Forums</a> | <a href="http://wiki.shardsofdalaya.com/">Wiki</a> | <a href="http://shardsofdalaya.com/fomelo/">Fomelo</a> | <a href="http://www.shardsofdalaya.com/vendorlist/listinvdata.php">Vendor List</a><br /><a href="#top">Top</a> | <a href="#bottom">Bottom</a></div>

    <h1>fomelo2wiki</h1>

    <form action="index.php" method="get">

        <p>
            Character Name:
            <br>
            <input type="search" id="id_search_text" name="search" />
            <input type="submit" id="id_search_button" value="Search" />
        </p>

        <p>
            Slot:
            <select id="id_slot_select" name="slot">
                <option value="all" selected>All</option>
                <option value="t_neck">Neck</option>
                <option value="t_head">Head</option>
                <option value="t_left_ear">Left Ear</option>
                <option value="t_right_ear">Right Ear</option>
                <option value="t_face">Face</option>
                <option value="t_chest">Chest</option>
                <option value="t_shoulders">Shoulders</option>
                <option value="t_arms">Arms</option>
                <option value="t_hands">Hands</option>
                <option value="t_left_wrist">Left Wrist</option>
                <option value="t_right_wrist">Right Wrist</option>
                <option value="t_back">Back</option>
                <option value="t_left_finger">Left Finger</option>
                <option value="t_right_finger">Right Finger</option>
                <option value="t_waist">Waist</option>
                <option value="t_legs">Legs</option>
                <option value="t_feet">Feet</option>
                <option value="t_primary">Primary</option>
                <option value="t_secondary">Secondary</option>
                <option value="t_charm">Charm</option>
                <option value="t_range">Range</option>
                <option value="t_ammo">Ammo</option>
            </select>
        </p>

        <p>
            <input type="checkbox" id="id_get_wiki_information_checkbox" name="get_wiki_information" value="true">Get information from Wiki (Slow, but used to determine if a page has no text or is using an outdated format)</input>
            <br>
            <input type="checkbox" id="id_only_show_wiki_information_items_checkbox" name="only_show_wiki_information_items" value="true">Only show items with information found from Wiki (Useful for adding new items and updating outdated items)</input>
        </p>

    </form>

    <?php

        function substr_between($s, $l, $r)
        {  
            $il = strpos($s, $l, 0) + strlen($l);
            $ir = strpos($s, $r, $il);
            return substr($s, $il, ($ir - $il));
        }

        function str_remove_spaces_from_end_of_lines($text)
        {
            $lines = explode("\n", $text);

            $result = '';

            foreach ($lines as $line)
            {
                if ((strpos($line, "\n") == 0) && (strlen($line) == 0))
                    continue;

                $line = rtrim($line, ' ');
                $line .= "\n";

                $result .= $line;
            }

            return $result;
        }

        function str_fix_item_name($text)
        {
            $text = str_replace('`', "'", $text); // tilde fix

            $text = str_replace('&lsquo;', "'", $text); // quote fix

            $text = str_replace('Shirtri', 'Shiritri', $text); // Shiritri typo fix

            return $text;
        }

        function str_convert_item_name_to_wiki_name($text)
        {
            $text = str_replace('Song:', 'Spell:', $text); // redirect Songs to Spells

            $text = str_replace(' ', '_', $text);

            return $text;
        }

        include('simple_html_dom.php');

        set_time_limit(0); // prevent maximum execution time timeout

        $item_name = 'null';

        function get_item_exp_level($text)
        {
            if (preg_match('/Level:\s+(\d+)\/\d+/', $text, $matches))
                return $matches[1];

            return 0;
        }

        function get_item_exp_levels($text)
        {
            if (preg_match('/Level:\s+\d+\/(\d+)/', $text, $matches))
                return $matches[1];

            return 0;
        }

        function get_item_exp_growth_rate($exp_level, $exp_mod)
        {
            $percentage = false;

            if (strpos($exp_mod, '%') !== false)
                $percentage = true;

            $find = strpos($exp_mod, '+');

            $value = substr($exp_mod, $find + 1);

            if ($percentage == true)
                $value = substr_between($exp_mod, '+', '%');

            $exp_growth_rate = $value / $exp_level;

            if ($percentage == true)
                $exp_growth_rate .= '%';
            else
                $exp_growth_rate = '+' . $exp_growth_rate;

            return $exp_growth_rate;
        }

        function get_item_property($text, $property)
        {
            if (preg_match('/' . "[\n]?" . $property . ':\s+([*]?[+-]?\d+[%]?[*]?)/', $text, $matches))
            {
                //return $matches[1];

                $value = $matches[1];

                if (strpos($value, '%') === false)
                {
                    if (strpos($value, '*') !== false)
                    {
                        $positive_or_negative = 'null';

                        if (strpos($value, '+') !== false)
                            $positive_or_negative = '+';

                        if (strpos($value, '-') !== false)
                            $positive_or_negative = '-';

                        $find = strpos($value, $positive_or_negative);

                        if ($positive_or_negative == 'null')
                            $find = strpos($value, '*');

                        $pristine = substr($value, $find + 1, -1);

                        if ($positive_or_negative == '-')
                            $pristine *= -1;

                        $base = 0;

                        if ($pristine > 0)
                        {
                            if (strpos($matches[0], 'Atk Delay') !== false)
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

                        if ($base > 0)
                        {
                            if
                            (
                                strpos($matches[0], 'AC')        === false &&
                                strpos($matches[0], 'Atk Delay') === false &&
                                strpos($matches[0], 'DMG') === false
                            )
                            {
                                $base = '+' . $base;
                            }
                        }

                        return $base;
                    }
                }

                return $value;
            }

            return 0;
        }

        function get_item_property_list($text, $property)
        {
            $list = substr_between($text, $property . ': ', "\n");

            $values = explode(' ', $list);

            return $values;
        }

        function get_item_decimal($text, $property)
        {
            if (preg_match('/'. $property . ':\s+(\d+\.\d+)/', $text, $matches))
                return $matches[1];

            return 0;
        }

        function get_item_skill($text)
        {
            if (preg_match('/Skill:\s+(1H Blunt|1H Slash|1H Slashing|2H Blunt|2H Slash|2H Slashing|Piercing|Archery|Throwing|Hand to Hand)/', $text, $matches))
                return $matches[1];

            return 0;
        }

        function get_item_skill_mod($text)
        {
            $text = substr_between($text, 'Skill Mod: ', "\n");

            $find = strpos($text, ' +');

            if (strpos($text, '-') !== false)
                $find = strpos($text, ' -');

            $key = substr($text, 0, $find);

            $value = substr($text, $find + 1);

            $skill_mod = Array();
            array_push($skill_mod, $key);
            array_push($skill_mod, $value);

            return $skill_mod;
        }

        function get_item_instrument_modifier($text)
        {
            $text = substr_between($text, 'Instrument Modifier: ', "\n");

            $find = strpos($text, ' (');

            $key = substr($text, 0, $find);

            $value = substr_between($text, '(', ')');

            $instrument_modifier = Array();
            array_push($instrument_modifier, $key);
            array_push($instrument_modifier, $value);

            return $instrument_modifier;
        }

        function get_item_bane_damage($text)
        {
            if (preg_match('/Bane\s+DMG:\s+(\d+)\s+(\w+)\n/', $text, $matches))
            {
                $bane_damage = Array();
                array_push($bane_damage, $matches[1]);
                array_push($bane_damage, $matches[2]);

                return $bane_damage;
            }

            return 0;
        }

        function get_item_spell_damage($text)
        {
            if (preg_match('/(Fire|Cold|Magic|Disease|Poison)\s+DMG:\s+(\d+)/', $text, $matches))
            {
                $spell_damage = Array();
                array_push($spell_damage, $matches[1]);
                array_push($spell_damage, $matches[2]);

                return $spell_damage;
            }

            return 0;
        }

        function get_item_focus_effect($text)
        {
            $focus_effect = substr_between($text, 'Focus Effect: ', "\n");

            return $focus_effect;
        }

        function get_item_effect($text)
        {
            $text = substr_between($text, "\nEffect: ", "\n");

            $find = strpos($text, ' (');

            $effect_name = substr($text, 0, $find);

            if (strpos($effect_name, 'Haste') !== false)
            {
                $find = strpos($text, 'Haste');

                $effect_name = substr($effect_name, $find);
            }

            $effect_flag = 'null';
            $effect_cast = 'null';

            if (strpos($text, '(Worn)') !== false)
            {
                $effect_flag = 'Worn';

                $effect_cast = 'null';
            }
            else
            {
                $effect_flag = substr_between($text, '(', ',');

                $effect_cast = substr_between($text, ', ', ')');
            }

            if ($effect_cast == 'Instant')
                $effect_cast = 0;

            $effect = Array();
            array_push($effect, $effect_name);
            array_push($effect, $effect_flag);
            array_push($effect, $effect_cast);

            return $effect;
        }

        function get_item_haste($text)
        {
            if (preg_match('/Effect:\s+(\d+%)\s+Haste/', $text, $matches))
                return $matches[1];

            return 0;
        }

        function get_item_size($text)
        {
            if (preg_match('/Size:\s+(Tiny|Small|Medium|Large|Giant)/', $text, $matches))
                return $matches[1];

            return 0;

            //$size = substr_between($text, 'Size: ', "\n");

            //return $size;
        }

        function get_item_aug_slots($text)
        {
            if (preg_match_all('/Type\s+(\d+)\s+Aug\s+Slot:/', $text, $matches, PREG_SET_ORDER))
                return $matches;

            return 0;
        }

        $file_get_context = stream_context_create(array('http' => array('header' => 'Connection: close')));

        if (isset($_GET['search']))
            $search = $_GET['search'];
        else
            $search = '';

        $search = ucwords($search);

        if ($search == '')
            die('No search found!');

        if (isset($_GET['slot']))
            $slot = $_GET['slot'];
        else
            $slot = 'all';

        if (isset($_GET['get_wiki_information']))
            $get_wiki_information = $_GET['get_wiki_information'];
        else
            $get_wiki_information = 'false';

        if (isset($_GET['only_show_wiki_information_items']))
            $only_show_wiki_information_items = $_GET['only_show_wiki_information_items'];
        else
            $only_show_wiki_information_items = 'false';

        echo '<p>' . 'Search: ' . $search . '</p>';

        $url = 'http://shardsofdalaya.com/fomelo/fomelo.php?char=' . $search;

        echo '<p>' . 'Fomelo URL: ' . '<a href="' . $url . '">' . $url . '</a>' . '</p>';

        $html = file_get_html($url, false, $file_get_context);

        $fomelo_information_character_not_found = 'Character not found';

        if (strpos($html->plaintext, $fomelo_information_character_not_found) !== false)
            die('<p class="class_red_paragraph">Fomelo Information: ' . $fomelo_information_character_not_found . '</p>');

        echo '<hr>';

        foreach ($html->find('span') as $span)
        {
            foreach ($span->find('img') as $img)
            {
                if (strpos($img->src, 'images/icons/item_') !== false)
                {
                    $item_image = substr_between($img->src, 'icons/item_', '.png');

                    $img->alt   = $item_image;
                    $img->title = $item_image;
                }
            }

            if (strpos($span->id, 't_') !== false)
            {
                if ((strlen($slot) > 0) && ($slot !== 'all'))
                    if ($span->id !== $slot)
                        continue;

                $slot_name = $span->id;

                $slot_name = substr($slot_name, 2);

                $slot_name = str_replace('_', ' ', $slot_name);

                $slot_name = ucwords($slot_name);

                echo '<p>' . 'Slot: ' . $slot_name . '</p>';

                $item_name = 0;

                $found_first_item_in_slot = 0;

                foreach ($span->find('font') as $font)
                {
                    if ($font->size == 2 && $found_first_item_in_slot == 0)
                    {
                        $found_first_item_in_slot = 1;

                        if (strpos($item_name, 'Empty slot') !== false)
                        {
                            echo '<p>' . 'Empty Slot' . '</p>';
                            continue;
                        }

                        $wiki_item_name = $item_name;

                        $wiki_item_name = str_fix_item_name($wiki_item_name);

                        $wiki_item_name = str_convert_item_name_to_wiki_name($wiki_item_name);

                        $wiki_item_url = 'http://wiki.shardsofdalaya.com/index.php/' . $wiki_item_name;

                        echo '<p>';
                        echo 'Wiki URL: ';
                        echo '<a href="' . $wiki_item_url . '">';
                        echo $wiki_item_url;
                        echo '</a>';
                        echo '</p>';

                        if ($get_wiki_information == 'true')
                        {
                            $wiki_item_html = file_get_html($wiki_item_url, false, $file_get_context);

                            $wiki_information_found = 0;

                            $wiki_information_no_text = 'There is currently no text in this page';

                            if (strpos($wiki_item_html->plaintext, $wiki_information_no_text) !== false)
                            {
                                $wiki_information_found = 1;

                                echo '<p class="class_green_paragraph">Wiki Information: ' . $wiki_information_no_text . '</p>';
                            }

                            $wiki_information_outdated_format = 'This item is using an outdated format!';

                            if (strpos($wiki_item_html->plaintext, $wiki_information_outdated_format) !== false)
                            {
                                $wiki_information_found = 1;

                                echo '<p class="class_red_paragraph">Wiki Information: ' . $wiki_information_outdated_format . '</p>';
                            }
                        }

                        if ($get_wiki_information == 'true' && $only_show_wiki_information_items == 'true')
                        {
                            if ($wiki_information_found == 0)
                            {
                                echo '<p>' . 'No Wiki Information found! Skipped.' . '</p>';
                                continue;
                            }
                        }

                        $item_data_html = $font->innertext;

                        $item_data_html = str_replace('images/icons/item_', 'http://www.shardsofdalaya.com/fomelo/images/icons/item_', $item_data_html);

                        $item_data = $item_data_html;

                        $item_data = preg_replace('#<br\s*/?>#i', "\n", $item_data);

                        if (strpos($item_data, '[EXPABLE]') === false)
                        {
                            $item_data = str_replace("<span style='color:#4DFF2F'> ", ' *', $item_data);

                            $item_data = str_replace("<span style='color:#4DFF2F'>", '*', $item_data);
                            $item_data = str_replace('</span>', '*', $item_data);
                        }

                        if (strpos($item_data, "<span style='color:#FF0000'>") !== false)
                        {
                            //echo '<p>' . 'This item has a Required Level! Skipped.' . '</p>';
                            //continue;

                            $item_data = str_replace("<span style='color:#FF0000'> ", ' *', $item_data);

                            $item_data = str_replace("<span style='color:#FF0000'>", '*', $item_data);
                            $item_data = str_replace('</span>', '*', $item_data);

                            $item_data .= "Required Level of ??.\n";
                        }

                        $item_data = strip_tags($item_data);

                        $item_data = str_remove_spaces_from_end_of_lines($item_data);

                        $item_data = str_replace('  Size:', ' Size:', $item_data);

                        $wiki_data = '{{Itemstats' . "\n";

                        $wiki_data .= '| name = ' . $item_name . "\n";

                        $item_image_found = 0;

                        foreach ($span->find('img') as $img)
                        {
                            if ($item_image_found == 1)
                                continue;

                            if (strpos($img->src, 'icons/item_') !== false)
                            {
                                $item_image_found = 1;

                                $item_image = substr_between($img->src, 'icons/item_', '.png');

                                $wiki_data .= '| image = ' . $item_image . "\n";
                            }
                        }

                        $wiki_data .= '| source' . "\n";

                        if (strpos($item_data, '[EXPABLE]') !== false)
                        {
                            $wiki_data .= '| expable = 1' . "\n";

                            if ((strpos($item_data, 'Level:') !== false) && (strpos($item_data, 'Mod:') !== false))
                            {
                                $item_exp_level = get_item_exp_level($item_data);

                                $item_exp_mod = get_item_property($item_data, 'Mod');

                                $item_exp_growth_rate = get_item_exp_growth_rate($item_exp_level, $item_exp_mod);

                                $wiki_data .= '| expgrowthrate = ' . $item_exp_growth_rate . "\n";
                            }
                        }

                        if (strpos($item_data, 'Level:') !== false)
                        {
                            $item_exp_levels = get_item_exp_levels($item_data);

                            $wiki_data .= '| explevels = ' . $item_exp_levels . "\n";

                            $wiki_data .= '| expperlevel' . "\n";
                        }

                        $wiki_data .= "\n";

                        $wiki_data .= '<!--End Infobox - Below are stats-->' . "\n";

                        if
                        (
                            (strpos($item_data, '[BOUND]')         !== false) ||
                            (strpos($item_data, '[BIND]')          !== false) ||
                            (strpos($item_data, '[BIND ON EQUIP]') !== false) ||
                            (strpos($item_data, '[Bind on Equip]') !== false)
                        )
                        {
                            $wiki_data .= '| flagboe = 1' . "\n";
                        }

                        if (strpos($item_data, '[FACTION BOUND]') !== false)
                            $wiki_data .= '| flagfac = 1' . "\n";

                        if (strpos($item_data, '[LORE]') !== false)
                            $wiki_data .= '| flaglore = 1' . "\n";

                        if (strpos($item_data, '[MAGIC]') !== false)
                            $wiki_data .= '| flagmagic = 1' . "\n";

                        if (strpos($item_data, '[NO DROP]') !== false)
                            $wiki_data .= '| flagnodrop = 1' . "\n";

                        if (strpos($item_data, '[NO RENT]') !== false)
                            $wiki_data .= '| flagnorent = 1' . "\n";

                        //if (strpos($item_data, '[PRISTINE]') !== false)
                            //$wiki_data .= '| flagpristine = 1' . "\n";

                        if (strpos($item_data, '[AUGMENTATION]') !== false)
                            $wiki_data .= '| flagaug = 1' . "\n";

                        if (strpos($item_data, 'Slot:') !== false)
                        {
                            $item_slots = get_item_property_list($item_data, 'Slot');

                            $slot_number = 1;

                            foreach ($item_slots as $item_slot)
                            {
                                if ($item_slot == 'Shoulder')
                                    $item_slot = 'Shoulders';

                                $wiki_data .= '| slot' . $slot_number . ' = ' . $item_slot . "\n";

                                $slot_number++;
                            }
                        }

                        if (strpos($item_data, 'Note: Ammo slot stats are not counted') !== false)
                            $wiki_data .= '| ammonote = 1' . "\n";

                        if (strpos($item_data, 'AC:') !== false)
                        {
                            $item_property = get_item_property($item_data, 'AC');

                            $wiki_data .= '| ac = ' . $item_property . "\n";
                        }

                        if (strpos($item_data, "\nDMG:") !== false)
                        {
                            $item_property = get_item_property($item_data, "\nDMG");

                            $wiki_data .= '| dmg = ' . $item_property . "\n";
                        }

                        if (strpos($item_data, 'Atk Delay:') !== false)
                        {
                            $item_property = get_item_property($item_data, 'Atk Delay');

                            $wiki_data .= '| atkdelay = ' . $item_property . "\n";
                        }

                        if (strpos($item_data, 'Skill:') !== false)
                        {
                            $item_property = get_item_skill($item_data);

                            $item_property = preg_replace('/(\d+H)\s+Slash/', '$1 Slashing', $item_property);

                            $wiki_data .= '| skill = ' . $item_property . "\n";
                        }

                        if (strpos($item_data, 'Skill Mod:') !== false)
                        {
                            $item_property = get_item_skill_mod($item_data);

                            $item_property[0] = preg_replace('/(\d+)\s+Hand/', '$1H', $item_property[0]);

                            $item_property[0] = str_replace('Hand To Hand', 'Hand to Hand', $item_property[0]);

                            $wiki_data .= '| skillmod = '    . $item_property[0] . "\n";
                            $wiki_data .= '| skillmodnum = ' . $item_property[1] . "\n";
                        }

                        if (strpos($item_data, 'Instrument Modifier:') !== false)
                        {
                            $item_property = get_item_instrument_modifier($item_data);

                            $wiki_data .= '| insttypemod = ' . $item_property[0] . "\n";
                            $wiki_data .= '| instmod = '     . $item_property[1] . "\n";
                        }

                        if (strpos($item_data, 'Bane DMG:') !== false)
                        {
                            $item_property = get_item_bane_damage($item_data);

                            $wiki_data .= '| banedmgmod = ' . $item_property[1] . "\n";
                            $wiki_data .= '| banedmgnum = ' . $item_property[0] . "\n";
                        }

                        if
                        (
                            (strpos($item_data, 'Fire')    !== false) ||
                            (strpos($item_data, 'Cold')    !== false) ||
                            (strpos($item_data, 'Magic')   !== false) ||
                            (strpos($item_data, 'Disease') !== false) ||
                            (strpos($item_data, 'Poison')  !== false)
                            &&
                            (strpos($item_data, 'DMG:') !== false)
                        )
                        {
                            $item_property = get_item_spell_damage($item_data);

                            $wiki_data .= '| spdmgtype = ' . $item_property[0] . "\n";
                            $wiki_data .= '| spdmgnum  = ' . $item_property[1] . "\n";
                        }

                        $base_stats = Array();
                        array_push($base_stats, 'STR');
                        array_push($base_stats, 'STA');
                        array_push($base_stats, 'AGI');
                        array_push($base_stats, 'DEX');
                        array_push($base_stats, 'INT');
                        array_push($base_stats, 'WIS');
                        array_push($base_stats, 'CHA');

                        foreach ($base_stats as $base_stat)
                        {
                            if (strpos($item_data, $base_stat . ':') !== false)
                            {
                                $item_property = get_item_property($item_data, $base_stat);

                                $wiki_data .= '| ' . strtolower($base_stat) . ' = ' . $item_property . "\n";
                            }
                        }

                        if (strpos($item_data, 'HP:') !== false)
                        {
                            $item_property = get_item_property($item_data, 'HP');

                            $wiki_data .= '| health = ' . $item_property . "\n";
                        }

                        if (strpos($item_data, 'MANA:') !== false)
                        {
                            $item_property = get_item_property($item_data, 'MANA');

                            $wiki_data .= '| mana = ' . $item_property . "\n";
                        }

                        $resist_stats = Array();
                        array_push($resist_stats, 'FR');
                        array_push($resist_stats, 'CR');
                        array_push($resist_stats, 'MR');
                        array_push($resist_stats, 'DR');
                        array_push($resist_stats, 'PR');

                        $resist_stats_wiki = Array();
                        array_push($resist_stats_wiki, 'svfire');
                        array_push($resist_stats_wiki, 'svcold');
                        array_push($resist_stats_wiki, 'svmagic');
                        array_push($resist_stats_wiki, 'svdisease');
                        array_push($resist_stats_wiki, 'svpoison');

                        foreach ($resist_stats as $key => $resist_stat)
                        {
                            if (strpos($item_data, $resist_stat . ':') !== false)
                            {
                                $item_property = get_item_property($item_data, $resist_stat);

                                $wiki_data .= '| ' . $resist_stats_wiki[$key] . ' = ' . $item_property . "\n";
                            }
                        }

                        if (strpos($item_data, 'Focus Effect:') !== false)
                        {
                            $item_property = get_item_focus_effect($item_data);

                            $wiki_data .= '| focuseffect = ' . $item_property . "\n";
                        }

                        if (strpos($item_data, "\nEffect:") !== false)
                        {
                            $item_property = get_item_effect($item_data);

                            $wiki_data .= '| effect = ' . $item_property[0] . "\n";

                            if ($item_property[1] !== 'null')
                                $wiki_data .= '| effectflag = ' . $item_property[1] . "\n";

                            if ($item_property[2] !== 'null')
                                $wiki_data .= '| effectcast = ' . $item_property[2] . "\n";
                        }

                        if ((strpos($item_data, 'Effect:') !== false) && (strpos($item_data, 'Haste')))
                        {
                            $item_property = get_item_haste($item_data);

                            $wiki_data .= '| haste = ' . $item_property . "\n";
                        }

                        $advanced_item_effects = Array();
                        array_push($advanced_item_effects, 'Aggression');
                        array_push($advanced_item_effects, 'Critical Strike');
                        array_push($advanced_item_effects, 'Damage Reduction');
                        array_push($advanced_item_effects, 'Flowing Thought');
                        array_push($advanced_item_effects, 'Mind Shield');
                        array_push($advanced_item_effects, 'Spell Ward');
                        array_push($advanced_item_effects, 'Stun Resist');

                        $advanced_item_effects_wiki = Array();
                        array_push($advanced_item_effects_wiki, 'aggression');
                        array_push($advanced_item_effects_wiki, 'critstrike');
                        array_push($advanced_item_effects_wiki, 'dmgreduction');
                        array_push($advanced_item_effects_wiki, 'flowingthought');
                        array_push($advanced_item_effects_wiki, 'mindshield');
                        array_push($advanced_item_effects_wiki, 'spellward');
                        array_push($advanced_item_effects_wiki, 'stunresist');

                        foreach ($advanced_item_effects as $key => $advanced_item_effect)
                        {
                            if (strpos($item_data, $advanced_item_effect . ':') !== false)
                            {
                                $item_property = get_item_property($item_data, $advanced_item_effect);

                                $wiki_data .= '| ' . $advanced_item_effects_wiki[$key] . ' = ' . $item_property . "\n";
                            }
                        }

                        if (strpos($item_data, 'Range:') !== false)
                        {
                            $item_property = get_item_property($item_data, 'Range');

                            $wiki_data .= '| range = ' . $item_property . "\n";
                        }

                        if (strpos($item_data, 'Weight:') !== false)
                        {
                            $item_property = get_item_decimal($item_data, 'Weight');

                            $wiki_data .= '| wt = ' . $item_property . "\n";
                        }

                        if (strpos($item_data, 'Size:') !== false)
                        {
                            $item_property = get_item_size($item_data);

                            $wiki_data .= '| size = ' . $item_property . "\n";
                        }

                        if ((strpos($item_data, 'Type') !== false) && (strpos($item_data, 'Aug Slot:') !== false))
                        {
                            $item_aug_slots = get_item_aug_slots($item_data);

                            $aug_slot_number = 1;

                            foreach ($item_aug_slots as $item_aug_slot)
                            {
                                $wiki_data .= '| augslot' . $aug_slot_number . ' = ' . $item_aug_slot[1] . "\n";

                                $aug_slot_number++;
                            }
                        }

                        $aug_slot_number = 1;

                        foreach ($span->find('font') as $font)
                        {
                            if ($font->size == 2 && $found_first_item_in_slot == 1)
                            {
                                if (strpos($font->plaintext, 'Type:') !== false)
                                {
                                    if (preg_match('/Type:\s+(\d+)/', $font->plaintext, $matches))
                                    {
                                        if (strpos($wiki_data, '| augslot1 = ') !== false)
                                            $aug_slot_number = 2;

                                        $item_data .= 'Type ' . $matches[1] . ' Aug Slot: Not Empty' . "\n";

                                        $wiki_data .= '| augslot' . $aug_slot_number . ' = ' . $matches[1] . "\n";
                                    }
                                }
                            }
                        }

                        if (strpos($item_data, 'Class:') !== false)
                        {
                            $item_classes = get_item_property_list($item_data, 'Class');

                            if (in_array('ALL', $item_classes))
                            {
                                $item_classes = Array();
                                array_push($item_classes, 'WAR');
                                array_push($item_classes, 'CLR');
                                array_push($item_classes, 'PAL');
                                array_push($item_classes, 'RNG');
                                array_push($item_classes, 'SHD');
                                array_push($item_classes, 'DRU');
                                array_push($item_classes, 'MNK');
                                array_push($item_classes, 'BRD');
                                array_push($item_classes, 'ROG');
                                array_push($item_classes, 'SHM');
                                array_push($item_classes, 'NEC');
                                array_push($item_classes, 'WIZ');
                                array_push($item_classes, 'MAG');
                                array_push($item_classes, 'ENC');
                                array_push($item_classes, 'BST');
                            }

                            $class_number = 1;

                            foreach ($item_classes as $item_class)
                            {
                                $wiki_data .= '| class' . $class_number . ' = ' . $item_class . "\n";

                                $class_number++;
                            }
                        }

                        if (strpos($item_data, 'Race:') !== false)
                        {
                            $item_races = get_item_property_list($item_data, 'Race');

                            if (in_array('ALL', $item_races))
                            {
                                $item_races = Array();
                                array_push($item_races, 'BAR');
                                array_push($item_races, 'DEF');
                                array_push($item_races, 'DWF');
                                array_push($item_races, 'ERU');
                                array_push($item_races, 'FRG');
                                array_push($item_races, 'GNM');
                                array_push($item_races, 'HEF');
                                array_push($item_races, 'HFL');
                                array_push($item_races, 'HIE');
                                array_push($item_races, 'HUM');
                                array_push($item_races, 'IKS');
                                array_push($item_races, 'OGR');
                                array_push($item_races, 'TRL');
                                array_push($item_races, 'VAH');
                                array_push($item_races, 'ELF');
                            }

                            $race_number = 1;

                            foreach ($item_races as $item_race)
                            {
                                $wiki_data .= '| race' . $race_number . ' = ' . $item_race . "\n";

                                $race_number++;
                            }
                        }

                        $wiki_data .= '| }}';

                        echo '<p>';

                        echo '<div class="class_item_box">' . '<b>' . $item_name . '</b>' . '</div>';
                        echo '<br>';
                        echo '<div class="class_item_box">' . $item_data_html . '</div>';
                        echo '<br>';

                        foreach ($span->find('font') as $font)
                        {
                            if ($font->size == 2 && $found_first_item_in_slot == 1)
                            {
                                if (strpos($font->plaintext, 'Type:') !== false)
                                {
                                    $augment_html = $font->innertext;

                                    $augment_html = str_replace('images/icons/item_', 'http://www.shardsofdalaya.com/fomelo/images/icons/item_', $augment_html);

                                    $augment_name = substr_between($augment_html, '<u>', '</u>');

                                    $augment_name_wiki = $augment_name;

                                    $augment_name_wiki = str_fix_item_name($augment_name_wiki);

                                    $augment_name_wiki = str_convert_item_name_to_wiki_name($augment_name_wiki);

                                    $augment_url_wiki = 'http://wiki.shardsofdalaya.com/index.php/' . $augment_name_wiki;

                                    $augment_link_wiki = '<a href="' . $augment_url_wiki . '" class="class_white_text">' . $augment_name_wiki . '</a>';

                                    $augment_html = str_replace($augment_name, $augment_link_wiki, $augment_html);

                                    echo '<div class="class_item_box">' . $augment_html . '</div>';
                                    echo '<br>';
                                }
                            }
                        }

                        if (strpos($item_data, '[PRISTINE]') !== false)
                            echo '<p class="class_orange_paragraph">Warning: This item is [PRISTINE] and may have stat bonuses applied to it!' . '</p>';

                        if (strpos($item_data, '[EXPABLE]') !== false)
                            echo '<p class="class_orange_paragraph">Warning: This item is [EXPABLE] and may have stat bonuses applied to it!' . '</p>';

                        if (strpos($item_data, 'Required Level') !== false)
                            echo '<p class="class_orange_paragraph">Warning: This item has a Required Level and may have stat reductions applied to it!' . '</p>';

                        echo 'Wiki Code:' . '<br>';
                        echo '<textarea cols="75" rows="25">' . $wiki_data . '</textarea>';
                        echo '<textarea cols="75" rows="25">' . $item_data . '</textarea>';

                        echo '</p>';
                    }
                    else
                    {
                        $item_name = $font->plaintext;

                        $item_name = str_fix_item_name($item_name);

                        $item_name = trim($item_name, "\n");
                    }
                }

                echo '<hr>';
            }
        }

    ?>

    <div style="clear: both;"></div>

    <p>
        fomelo2wiki @ GitHub
        <br>
        <a href="https://github.com/evrehuntera/fomelo2wiki">https://github.com/evrehuntera/fomelo2wiki</a>
    </p>

    <div id="bottom"><a href="#top">Top</a></div>

    <script type="text/javascript">

        $("#id_search_text").focus();

        $.extend
        (
            {
                get_url_vars: function ()
                {
                    var vars = [], hash;
                    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
                    for (var i = 0; i < hashes.length; i++)
                    {
                        hash = hashes[i].split('=');
                        vars.push(hash[0]);
                        vars[hash[0]] = hash[1];
                    }
                    return vars;
                },
                get_url_var: function (name)
                {
                    return $.get_url_vars()[name];
                }
            }
        );

        var querySearch = $.get_url_var('search');

        $('#id_search_text').attr('value', querySearch);

        var querySlot = $.get_url_var('slot');

        $('#id_slot_select').attr('value', querySlot);

        var queryGetWikiInformation = $.get_url_var('get_wiki_information');

        if (queryGetWikiInformation == 'true')
            $('#id_get_wiki_information_checkbox').attr('checked', true);
        else
            $('#id_get_wiki_information_checkbox').attr('checked', false);

        var queryOnlyShowWikiInformationItems = $.get_url_var('only_show_wiki_information_items');

        if (queryOnlyShowWikiInformationItems == 'true')
            $('#id_only_show_wiki_information_items_checkbox').attr('checked', true);
        else
            $('#id_only_show_wiki_information_items_checkbox').attr('checked', false);

    </script>

</body>

</html>
