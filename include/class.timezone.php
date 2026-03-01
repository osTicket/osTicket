<?php
/*********************************************************************
    class.timezone.php

    Database time zone get utils.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

// This class adopted from jstimezone

class DbTimezone {
    const HEMISPHERE_SOUTH = 's';
    const DAY = 86400;
    const HOUR = 3600;
    const MINUTE = 60;
    const SECOND = 1;
    const BASELINE_YEAR = 2014;
    const MAX_SCORE = 864000; // 10 days

    static $AMBIGUITIES = array(
        'America/Denver' =>       array('America/Mazatlan'),
        'America/Chicago' =>      array('America/Mexico_City'),
        'America/Santiago' =>     array('America/Asuncion', 'America/Campo_Grande'),
        'America/Montevideo' =>   array('America/Sao_Paulo'),
        // Europe/Minsk should not be in this list... but Windows.
        'Asia/Beirut' =>          array('Asia/Amman', 'Asia/Jerusalem', 'Europe/Helsinki', 'Asia/Damascus', 'Africa/Cairo', 'Asia/Gaza', 'Europe/Minsk'),
        'Pacific/Auckland' =>     array('Pacific/Fiji'),
        'America/Los_Angeles' =>  array('America/Santa_Isabel'),
        'America/New_York' =>     array('America/Havana'),
        'America/Halifax' =>      array('America/Goose_Bay'),
        'America/Godthab' =>      array('America/Miquelon'),
        'Asia/Dubai' =>           array('Asia/Yerevan'),
        'Asia/Jakarta' =>         array('Asia/Krasnoyarsk'),
        'Asia/Shanghai' =>        array('Asia/Irkutsk', 'Australia/Perth'),
        'Australia/Sydney' =>     array('Australia/Lord_Howe'),
        'Asia/Tokyo' =>           array('Asia/Yakutsk'),
        'Asia/Dhaka' =>           array('Asia/Omsk'),
        // In the real world Yerevan is not ambigous for Baku... but Windows.
        'Asia/Baku' =>            array('Asia/Yerevan'),
        'Australia/Brisbane' =>   array('Asia/Vladivostok'),
        'Pacific/Noumea' =>       array('Asia/Vladivostok'),
        'Pacific/Majuro' =>       array('Asia/Kamchatka', 'Pacific/Fiji'),
        'Pacific/Tongatapu' =>    array('Pacific/Apia'),
        'Asia/Baghdad' =>         array('Europe/Minsk', 'Europe/Moscow'),
        'Asia/Karachi' =>         array('Asia/Yekaterinburg'),
        'Africa/Johannesburg' =>  array('Asia/Gaza', 'Africa/Cairo')
    );

    static $olsonTimezones = array(
        '-720,0' => 'Etc/GMT+12',
        '-660,0' => 'Pacific/Pago_Pago',
        '-660,1,s' => 'Pacific/Apia', // Why? Because windows... cry!
        '-600,1' => 'America/Adak',
        '-600,0' => 'Pacific/Honolulu',
        '-570,0' => 'Pacific/Marquesas',
        '-540,0' => 'Pacific/Gambier',
        '-540,1' => 'America/Anchorage',
        '-480,1' => 'America/Los_Angeles',
        '-480,0' => 'Pacific/Pitcairn',
        '-420,0' => 'America/Phoenix',
        '-420,1' => 'America/Denver',
        '-360,0' => 'America/Guatemala',
        '-360,1' => 'America/Chicago',
        '-360,1,s' => 'Pacific/Easter',
        '-300,0' => 'America/Bogota',
        '-300,1' => 'America/New_York',
        '-270,0' => 'America/Caracas',
        '-240,1' => 'America/Halifax',
        '-240,0' => 'America/Santo_Domingo',
        '-240,1,s' => 'America/Santiago',
        '-210,1' => 'America/St_Johns',
        '-180,1' => 'America/Godthab',
        '-180,0' => 'America/Argentina/Buenos_Aires',
        '-180,1,s' => 'America/Montevideo',
        '-120,0' => 'America/Noronha',
        '-120,1' => 'America/Noronha',
        '-60,1' => 'Atlantic/Azores',
        '-60,0' => 'Atlantic/Cape_Verde',
        '0,0' => 'UTC',
        '0,1' => 'Europe/London',
        '60,1' => 'Europe/Berlin',
        '60,0' => 'Africa/Lagos',
        '60,1,s' => 'Africa/Windhoek',
        '120,1' => 'Asia/Beirut',
        '120,0' => 'Africa/Johannesburg',
        '180,0' => 'Asia/Baghdad',
        '180,1' => 'Europe/Moscow',
        '210,1' => 'Asia/Tehran',
        '240,0' => 'Asia/Dubai',
        '240,1' => 'Asia/Baku',
        '270,0' => 'Asia/Kabul',
        '300,1' => 'Asia/Yekaterinburg',
        '300,0' => 'Asia/Karachi',
        '330,0' => 'Asia/Kolkata',
        '345,0' => 'Asia/Kathmandu',
        '360,0' => 'Asia/Dhaka',
        '360,1' => 'Asia/Omsk',
        '390,0' => 'Asia/Rangoon',
        '420,1' => 'Asia/Krasnoyarsk',
        '420,0' => 'Asia/Jakarta',
        '480,0' => 'Asia/Shanghai',
        '480,1' => 'Asia/Irkutsk',
        '525,0' => 'Australia/Eucla',
        '525,1,s' => 'Australia/Eucla',
        '540,1' => 'Asia/Yakutsk',
        '540,0' => 'Asia/Tokyo',
        '570,0' => 'Australia/Darwin',
        '570,1,s' => 'Australia/Adelaide',
        '600,0' => 'Australia/Brisbane',
        '600,1' => 'Asia/Vladivostok',
        '600,1,s' => 'Australia/Sydney',
        '630,1,s' => 'Australia/Lord_Howe',
        '660,1' => 'Asia/Kamchatka',
        '660,0' => 'Pacific/Noumea',
        '690,0' => 'Pacific/Norfolk',
        '720,1,s' => 'Pacific/Auckland',
        '720,0' => 'Pacific/Majuro',
        '765,1,s' => 'Pacific/Chatham',
        '780,0' => 'Pacific/Tongatapu',
        '780,1,s' => 'Pacific/Apia',
        '840,0' => 'Pacific/Kiritimati'
    );


    function get_date_offset($checks) {
        static $fragment =
            "time_to_sec(timediff('%s', convert_tz('%s', @@session.time_zone, '+00:00'))) DIV 60";

        if (!is_array($checks))
            $checks = func_get_args();
        $dates = array();
        foreach ($checks as $time) {
            $date = date('Y-m-d h:i:s', $time);
            $dates[] = sprintf($fragment, $date, $date);
        }

        $sql = 'SELECT '.implode(',', $dates);
        return db_fetch_row(db_query($sql));
    }

    function lookup_key() {
        list($january_offset, $june_offset) =
            $this->get_date_offset(
                mktime(0, 0, 0, 1, 2, self::BASELINE_YEAR),
                mktime(0, 0, 0, 6, 2, self::BASELINE_YEAR));
        $diff = $january_offset - $june_offset;

        if ($diff < 0) {
            return $january_offset . ",1";
        } else if ($diff > 0) {
            return $june_offset . ",1," . self::HEMISPHERE_SOUTH;
        }

        return $january_offset . ",0";
    }

    function get_from_database() {
        // Attempt to fetch timezone direct from the database
        $TZ = db_timezone();

        // Translate ambiguous 'GMT' timezone
        if ($TZ === 'GMT') {
            // PHP assumes GMT == UTC, MySQL assumes GMT == Europe/London.
            // To shore up the difference, assuming use of MySQL, use the
            // timezone in PHP which honors BST (British Summer Time)
            return 'Europe/London';
        }

        return Format::timezone($TZ);
    }

    function dst_dates($year) {
        $yearstart = mktime(0, 0, 1, 1, 1, $year);
        $yearend = mktime(23, 59, 59, 12, 31, $year);
        $current = $yearstart;
        list($date_offset) = $this->get_date_offset($current);
        $dst_start = null;
        $dst_end = null;

        $checks = array();
        while ($current < $yearend - 86400) {
            $checks[] = $current;
            $current += 86400;
        }

        foreach ($this->get_date_offset($checks) as $i=>$offset) {
            if ($offset !== $date_offset) {
                if ($offset < $date_offset) {
                    $dst_start = $checks[$i];
                }
                if ($offset > $date_offset) {
                    $dst_end = $checks[$i];
                }
            }
        }
        // $offset will remain the last item in ::get_date_offset($checks)

        if ($dst_start && $dst_end) {
            return array(
                's' => $this->find_dst_fold($dst_start),
                'e' => $this->find_dst_fold($dst_end),
            );
        }

        return false;
    }

    function find_dst_fold($a_date, $padding=self::DAY, $iterator=self::HOUR) {
        $date_start = $a_date - $padding;
        $date_end = $a_date + $padding;
        list($date_offset) = $this->get_date_offset($date_start);

        $current = $date_start;

        $dst_change = null;
        while ($current < $date_end - $iterator) {
            $checks = array();
            for ($i=0; $i<12; $i++) {
                $checks[] = $current;
                $current += $iterator;
            }

            foreach ($this->get_date_offset($checks) as $i=>$offset) {
                if ($offset !== $date_offset) {
                    $dst_change = $checks[$i];
                    break;
                }
            }
            if ($dst_change)
                break;
        }

        if ($padding === self::DAY) {
            return $this->find_dst_fold($dst_change, self::HOUR, self::MINUTE);
        }

        if ($padding === self::HOUR) {
            return $this->find_dst_fold($dst_change, self::MINUTE, self::SECOND);
        }

        return $dst_change;
    }

    function windows7_adaptations($rule_list, $preliminary_timezone, $score, $sample) {
        if ($score !== 'N/A') {
            return $score;
        }
        if ($preliminary_timezone === 'Asia/Beirut') {
            if ($sample['name'] === 'Africa/Cairo') {
                if ($rule_list[6]['s'] === 1398376800 && $rule_list[6]['e'] === 1411678800) {
                    return 0;
                }
            }
            if ($sample['name'] === 'Asia/Jerusalem') {
                if ($rule_list[6]['s'] === 1395964800 && $rule_list[6]['e'] === 1411858800) {
                    return 0;
                }
            }
        } else if ($preliminary_timezone === 'America/Santiago') {
            if ($sample['name'] === 'America/Asuncion') {
                if ($rule_list[6]['s'] === 1412481600 && $rule_list[6]['e'] === 1397358000) {
                    return 0;
                }
            }
            if ($sample['name'] === 'America/Campo_Grande') {
                if ($rule_list[6]['s'] === 1413691200 && $rule_list[6]['e'] === 1392519600) {
                    return 0;
                }
            }
        } else if ($preliminary_timezone === 'America/Montevideo') {
            if ($sample['name'] === 'America/Sao_Paulo') {
                if ($rule_list[6]['s'] === 1413687600 && $rule_list[6]['e'] === 1392516000) {
                    return 0;
                }
            }
        } else if ($preliminary_timezone === 'Pacific/Auckland') {
            if ($sample['name'] === 'Pacific/Fiji') {
                if ($rule_list[6]['s'] === 1414245600 && $rule_list[6]['e'] === 1396101600) {
                    return 0;
                }
            }
        }

        return $score;
    }

    function best_dst_match($rule_list, $preliminary_timezone) {
        $self = $this;
        $score_sample = function ($sample) use ($rule_list, $self, $preliminary_timezone) {
            $score = 0;

            for ($j = 0; $j < count($rule_list); $j++) {

                // Both sample and current time zone report DST during the year.
                if (!!$sample['rules'][$j] && !!$rule_list[$j]) {

                    // The current time zone's DST rules are inside the sample's. Include.
                    if ($rule_list[$j]['s'] >= $sample['rules'][$j]['s'] && $rule_list[$j]['e'] <= $sample['rules'][$j]['e']) {
                        $score = 0;
                        $score += abs($rule_list[$j]['s'] - $sample['rules'][$j]['s']);
                        $score += abs($sample['rules'][$j]['e'] - $rule_list[$j]['e']);

                    // The current time zone's DST rules are outside the sample's. Discard.
                    } else {
                        $score = 'N/A';
                        break;
                    }

                    // The max score has been reached. Discard.
                    if ($score > self::MAX_SCORE) {
                        $score = 'N/A';
                        break;
                    }
                }
            }

            $score = $self->windows7_adaptations($rule_list, $preliminary_timezone, $score, $sample);

            return $score;
        };
        $scoreboard = array();
        $dst_zones = self::$dst_rules['zones'];
        $ambiguities = @self::$AMBIGUITIES[$preliminary_timezone];

        foreach ($dst_zones as $sample) {
            $score = $score_sample($sample);

            if ($score !== 'N/A') {
                $scoreboard[$sample['name']] = $score;
            }
        }

        foreach ($scoreboard as $tz) {
            if (in_array($tz, $ambiguities)) {
                return $tz;
            }
        }

        return $preliminary_timezone;
    }

    function get_by_dst($preliminary_timezone) {
        $rules = array();
        foreach (self::$dst_rules['years'] as $Y) {
            $rules[] = $this->dst_dates($Y);
        }
        $has_dst = false;
        foreach ($rules as $R) {
            if ($R !== false) {
                $has_dst = true; break;
            }
        }

        if ($has_dst) {
            return $this->best_dst_match($rules, $preliminary_timezone);
        }

        return $preliminary_timezone;
    }

    static function determine() {
        $self = new static();
        $preliminary_tz = $self->get_from_database();

        if (!$preliminary_tz) {
            $preliminary_tz = self::$olsonTimezones[$self->lookup_key()];

            if (isset(self::$AMBIGUITIES[$preliminary_tz])) {
                $preliminary_tz = $self->get_by_dst($preliminary_tz);
            }
        }

        return $preliminary_tz;
    }

    // Rules compiled from jstz rules.js file by
    // str_replace('000,', ',', var_export(json_decode('...', true)));
    static $dst_rules = array('years'=>array(0=>2008,1=>2009,2=>2010,3=>2011,4=>2012,5=>2013,6=>2014,),'zones'=>array(0=>array('name'=>'Africa/Cairo','rules'=>array(0=>array('e'=>1219957200,'s'=>1209074400,),1=>array('e'=>1250802000,'s'=>1240524000,),2=>array('e'=>1285880400,'s'=>1284069600,),3=>false,4=>false,5=>false,6=>array('e'=>1411678800,'s'=>1406844000,),),),1=>array('name'=>'America/Asuncion','rules'=>array(0=>array('e'=>1205031600,'s'=>1224388800,),1=>array('e'=>1236481200,'s'=>1255838400,),2=>array('e'=>1270954800,'s'=>1286078400,),3=>array('e'=>1302404400,'s'=>1317528000,),4=>array('e'=>1333854000,'s'=>1349582400,),5=>array('e'=>1364094000,'s'=>1381032000,),6=>array('e'=>1395543600,'s'=>1412481600,),),),2=>array('name'=>'America/Campo_Grande','rules'=>array(0=>array('e'=>1203217200,'s'=>1224388800,),1=>array('e'=>1234666800,'s'=>1255838400,),2=>array('e'=>1266721200,'s'=>1287288000,),3=>array('e'=>1298170800,'s'=>1318737600,),4=>array('e'=>1330225200,'s'=>1350792000,),5=>array('e'=>1361070000,'s'=>1382241600,),6=>array('e'=>1392519600,'s'=>1413691200,),),),3=>array('name'=>'America/Goose_Bay','rules'=>array(0=>array('e'=>1225594860,'s'=>1205035260,),1=>array('e'=>1257044460,'s'=>1236484860,),2=>array('e'=>1289098860,'s'=>1268539260,),3=>array('e'=>1320555600,'s'=>1299988860,),4=>array('e'=>1352005200,'s'=>1331445600,),5=>array('e'=>1383454800,'s'=>1362895200,),6=>array('e'=>1414904400,'s'=>1394344800,),),),4=>array('name'=>'America/Havana','rules'=>array(0=>array('e'=>1224997200,'s'=>1205643600,),1=>array('e'=>1256446800,'s'=>1236488400,),2=>array('e'=>1288501200,'s'=>1268542800,),3=>array('e'=>1321160400,'s'=>1300597200,),4=>array('e'=>1352005200,'s'=>1333256400,),5=>array('e'=>1383454800,'s'=>1362891600,),6=>array('e'=>1414904400,'s'=>1394341200,),),),5=>array('name'=>'America/Mazatlan','rules'=>array(0=>array('e'=>1225008000,'s'=>1207472400,),1=>array('e'=>1256457600,'s'=>1238922000,),2=>array('e'=>1288512000,'s'=>1270371600,),3=>array('e'=>1319961600,'s'=>1301821200,),4=>array('e'=>1351411200,'s'=>1333270800,),5=>array('e'=>1382860800,'s'=>1365325200,),6=>array('e'=>1414310400,'s'=>1396774800,),),),6=>array('name'=>'America/Mexico_City','rules'=>array(0=>array('e'=>1225004400,'s'=>1207468800,),1=>array('e'=>1256454000,'s'=>1238918400,),2=>array('e'=>1288508400,'s'=>1270368000,),3=>array('e'=>1319958000,'s'=>1301817600,),4=>array('e'=>1351407600,'s'=>1333267200,),5=>array('e'=>1382857200,'s'=>1365321600,),6=>array('e'=>1414306800,'s'=>1396771200,),),),7=>array('name'=>'America/Miquelon','rules'=>array(0=>array('e'=>1225598400,'s'=>1205038800,),1=>array('e'=>1257048000,'s'=>1236488400,),2=>array('e'=>1289102400,'s'=>1268542800,),3=>array('e'=>1320552000,'s'=>1299992400,),4=>array('e'=>1352001600,'s'=>1331442000,),5=>array('e'=>1383451200,'s'=>1362891600,),6=>array('e'=>1414900800,'s'=>1394341200,),),),8=>array('name'=>'America/Santa_Isabel','rules'=>array(0=>array('e'=>1225011600,'s'=>1207476000,),1=>array('e'=>1256461200,'s'=>1238925600,),2=>array('e'=>1288515600,'s'=>1270375200,),3=>array('e'=>1319965200,'s'=>1301824800,),4=>array('e'=>1351414800,'s'=>1333274400,),5=>array('e'=>1382864400,'s'=>1365328800,),6=>array('e'=>1414314000,'s'=>1396778400,),),),9=>array('name'=>'America/Sao_Paulo','rules'=>array(0=>array('e'=>1203213600,'s'=>1224385200,),1=>array('e'=>1234663200,'s'=>1255834800,),2=>array('e'=>1266717600,'s'=>1287284400,),3=>array('e'=>1298167200,'s'=>1318734000,),4=>array('e'=>1330221600,'s'=>1350788400,),5=>array('e'=>1361066400,'s'=>1382238000,),6=>array('e'=>1392516000,'s'=>1413687600,),),),10=>array('name'=>'Asia/Amman','rules'=>array(0=>array('e'=>1225404000,'s'=>1206655200,),1=>array('e'=>1256853600,'s'=>1238104800,),2=>array('e'=>1288303200,'s'=>1269554400,),3=>array('e'=>1319752800,'s'=>1301608800,),4=>false,5=>false,6=>array('e'=>1414706400,'s'=>1395957600,),),),11=>array('name'=>'Asia/Damascus','rules'=>array(0=>array('e'=>1225486800,'s'=>1207260000,),1=>array('e'=>1256850000,'s'=>1238104800,),2=>array('e'=>1288299600,'s'=>1270159200,),3=>array('e'=>1319749200,'s'=>1301608800,),4=>array('e'=>1351198800,'s'=>1333058400,),5=>array('e'=>1382648400,'s'=>1364508000,),6=>array('e'=>1414702800,'s'=>1395957600,),),),12=>array('name'=>'Asia/Dubai','rules'=>array(0=>false,1=>false,2=>false,3=>false,4=>false,5=>false,6=>false,),),13=>array('name'=>'Asia/Gaza','rules'=>array(0=>array('e'=>1219957200,'s'=>1206655200,),1=>array('e'=>1252015200,'s'=>1238104800,),2=>array('e'=>1281474000,'s'=>1269640860,),3=>array('e'=>1312146000,'s'=>1301608860,),4=>array('e'=>1348178400,'s'=>1333058400,),5=>array('e'=>1380229200,'s'=>1364508000,),6=>array('e'=>1411678800,'s'=>1395957600,),),),14=>array('name'=>'Asia/Irkutsk','rules'=>array(0=>array('e'=>1224957600,'s'=>1206813600,),1=>array('e'=>1256407200,'s'=>1238263200,),2=>array('e'=>1288461600,'s'=>1269712800,),3=>false,4=>false,5=>false,6=>false,),),15=>array('name'=>'Asia/Jerusalem','rules'=>array(0=>array('e'=>1223161200,'s'=>1206662400,),1=>array('e'=>1254006000,'s'=>1238112000,),2=>array('e'=>1284246000,'s'=>1269561600,),3=>array('e'=>1317510000,'s'=>1301616000,),4=>array('e'=>1348354800,'s'=>1333065600,),5=>array('e'=>1382828400,'s'=>1364515200,),6=>array('e'=>1414278000,'s'=>1395964800,),),),16=>array('name'=>'Asia/Kamchatka','rules'=>array(0=>array('e'=>1224943200,'s'=>1206799200,),1=>array('e'=>1256392800,'s'=>1238248800,),2=>array('e'=>1288450800,'s'=>1269698400,),3=>false,4=>false,5=>false,6=>false,),),17=>array('name'=>'Asia/Krasnoyarsk','rules'=>array(0=>array('e'=>1224961200,'s'=>1206817200,),1=>array('e'=>1256410800,'s'=>1238266800,),2=>array('e'=>1288465200,'s'=>1269716400,),3=>false,4=>false,5=>false,6=>false,),),18=>array('name'=>'Asia/Omsk','rules'=>array(0=>array('e'=>1224964800,'s'=>1206820800,),1=>array('e'=>1256414400,'s'=>1238270400,),2=>array('e'=>1288468800,'s'=>1269720000,),3=>false,4=>false,5=>false,6=>false,),),19=>array('name'=>'Asia/Vladivostok','rules'=>array(0=>array('e'=>1224950400,'s'=>1206806400,),1=>array('e'=>1256400000,'s'=>1238256000,),2=>array('e'=>1288454400,'s'=>1269705600,),3=>false,4=>false,5=>false,6=>false,),),20=>array('name'=>'Asia/Yakutsk','rules'=>array(0=>array('e'=>1224954000,'s'=>1206810000,),1=>array('e'=>1256403600,'s'=>1238259600,),2=>array('e'=>1288458000,'s'=>1269709200,),3=>false,4=>false,5=>false,6=>false,),),21=>array('name'=>'Asia/Yekaterinburg','rules'=>array(0=>array('e'=>1224968400,'s'=>1206824400,),1=>array('e'=>1256418000,'s'=>1238274000,),2=>array('e'=>1288472400,'s'=>1269723600,),3=>false,4=>false,5=>false,6=>false,),),22=>array('name'=>'Asia/Yerevan','rules'=>array(0=>array('e'=>1224972000,'s'=>1206828000,),1=>array('e'=>1256421600,'s'=>1238277600,),2=>array('e'=>1288476000,'s'=>1269727200,),3=>array('e'=>1319925600,'s'=>1301176800,),4=>false,5=>false,6=>false,),),23=>array('name'=>'Australia/Lord_Howe','rules'=>array(0=>array('e'=>1207407600,'s'=>1223134200,),1=>array('e'=>1238857200,'s'=>1254583800,),2=>array('e'=>1270306800,'s'=>1286033400,),3=>array('e'=>1301756400,'s'=>1317483000,),4=>array('e'=>1333206000,'s'=>1349537400,),5=>array('e'=>1365260400,'s'=>1380987000,),6=>array('e'=>1396710000,'s'=>1412436600,),),),24=>array('name'=>'Australia/Perth','rules'=>array(0=>array('e'=>1206813600,'s'=>1224957600,),1=>false,2=>false,3=>false,4=>false,5=>false,6=>false,),),25=>array('name'=>'Europe/Helsinki','rules'=>array(0=>array('e'=>1224982800,'s'=>1206838800,),1=>array('e'=>1256432400,'s'=>1238288400,),2=>array('e'=>1288486800,'s'=>1269738000,),3=>array('e'=>1319936400,'s'=>1301187600,),4=>array('e'=>1351386000,'s'=>1332637200,),5=>array('e'=>1382835600,'s'=>1364691600,),6=>array('e'=>1414285200,'s'=>1396141200,),),),26=>array('name'=>'Europe/Minsk','rules'=>array(0=>array('e'=>1224979200,'s'=>1206835200,),1=>array('e'=>1256428800,'s'=>1238284800,),2=>array('e'=>1288483200,'s'=>1269734400,),3=>false,4=>false,5=>false,6=>false,),),27=>array('name'=>'Europe/Moscow','rules'=>array(0=>array('e'=>1224975600,'s'=>1206831600,),1=>array('e'=>1256425200,'s'=>1238281200,),2=>array('e'=>1288479600,'s'=>1269730800,),3=>false,4=>false,5=>false,6=>false,),),28=>array('name'=>'Pacific/Apia','rules'=>array(0=>false,1=>false,2=>false,3=>array('e'=>1301752800,'s'=>1316872800,),4=>array('e'=>1333202400,'s'=>1348927200,),5=>array('e'=>1365256800,'s'=>1380376800,),6=>array('e'=>1396706400,'s'=>1411826400,),),),29=>array('name'=>'Pacific/Fiji','rules'=>array(0=>false,1=>false,2=>array('e'=>1269698400,'s'=>1287842400,),3=>array('e'=>1327154400,'s'=>1319292000,),4=>array('e'=>1358604000,'s'=>1350741600,),5=>array('e'=>1390050000,'s'=>1382796000,),6=>array('e'=>1421503200,'s'=>1414850400,),),),),);
}

?>
