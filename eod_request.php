<?php

// Check if the last day is a US holiday
function getOpenDaysUS($data_array) {
    $todays_day = date("D");
    $previous = "";
    $holiday = true;
    if ($todays_day == "Sun") {
        // Get the starting day of the week
        $previous = date("c", strtotime("-7 days"));
        $previous_value = 0;

        // Get the latest day in the list that is before the starting day of the week
        foreach($data_array as $itm) {
            if ($previous > $itm["date"]) {
                $previous_value = $itm["close"];
            }
            else {
                break;
            }
        }
        return array(
            "previous" => $previous_value,
            "latest" => array_slice($data_array, -1)[0]["close"]
        );


    }
    else if ($todays_day == "Mon") {
        // Get the Friday value (3 days ago)
        $previous = substr(date("c", strtotime("-3 days")),0,10);

        // Check if the previous day is included i the data (i.e. it is not a holiday)
        foreach($data_array as $itm) {
            if(substr($itm["date"],0,10) == $previous) {
                $holiday = false;
            }
        }
        if ($holiday == false) {
            return array(
                "previous" => array_slice($data_array, -2)[0]["close"],
                "latest" => array_slice($data_array, -1)[0]["close"]
            );
        }
    }
    else {
        // Get the previous day value (1 day ago)
        $previous = substr(date("c", strtotime("-1 day")),0,10);

        // Check if the previous day is included i the data (i.e. it is not a holiday)
        foreach($data_array as $itm) {
            if(substr($itm["date"],0,10) == $previous) {
                $holiday = false;
            }
        }
        if ($holiday == false) {
            return array(
                "previous" => array_slice($data_array, -2)[0]["close"],
                "latest" => array_slice($data_array, -1)[0]["close"]
            );
        }
    }
    
    return null;
}

// Check if the last day is a MENA holiday
function getOpenDaysMENA($data_array) {
    $todays_day = date("D");
    $previous = "";
    $holiday = true;
    if ($todays_day == "Sun") {
        // Get the starting day of the week
        $previous = date("c", strtotime("-8 days"));
        $previous_value = 0;

        // Get the latest day in the list that is before the starting day of the week
        foreach($data_array as $itm) {
            if ($previous > $itm["date"]) {
                $previous_value = $itm["close"];
            }
            else {
                break;
            }
        }
        return array(
            "previous" => $previous_value,
            "latest" => array_slice($data_array, -1)[0]["close"]
        );

    }
    else if ($todays_day == "Saturday") {
        // Get the Thursday value (2 days ago)
        $previous = substr(date("c", strtotime("-2 days")),0,10);

        // Check if the previous day is included i the data (i.e. it is not a holiday)
        foreach($data_array as $itm) {
            if(substr($itm["date"],0,10) == $previous) {
                $holiday = false;
            }
        }
        if ($holiday == false) {
            return array(
                "previous" => array_slice($data_array, -2)[0]["close"],
                "latest" => array_slice($data_array, -1)[0]["close"]
            );
        }
    }
    else {
        // Get the previous day value (1 day ago)
        $previous = substr(date("c", strtotime("-1 day")),0,10);

        // Check if the previous day is included i the data (i.e. it is not a holiday)
        foreach($data_array as $itm) {
            if(substr($itm["date"],0,10) == $previous) {
                $holiday = false;
            }
        }
        if ($holiday == false) {
            return array(
                "previous" => array_slice($data_array, -2)[0]["close"],
                "latest" => array_slice($data_array, -1)[0]["close"]
            );
        }
    }
    
    return null;
}

$api_key = "API_KEY_HERE";

$URL = "https://eodhistoricaldata.com/api/eod/"; // US10Y.indx?api_token=&fmt=json&from=2020-11-06";

$symbols = array(
    "GSPC.INDX", // S&P 500
    "IXIC.INDX", // Nasdaq
    "DJI.INDX",  // Dow Jones
    "TASI.INDX", // Tadawul
    "US10Y.INDX",// 10 Year US Treasury
    // "BTC",       // Bitcoin
    "BCOMCO.INDX"// Brent Crude Oil
);

$requests = array();

$mh = curl_multi_init();

// Calculate start and end date
$curr_day = (integer) date("d");
$start_day = $curr_day - 14;
$start_month = (integer) date("m");
$start_year = (integer) date("Y");

if($start_day < 1) {
    $start_day += 30;
    $start_month -= 1;
    if($start_month < 1){
        $start_month += 12;
        $start_year -= 1;
    }
}
if($start_day < 10){
    $start_day = "0".$start_day;
}
if($start_month < 10){
    $start_month = "0".$start_month;
}

$start = $start_year . "-" . $start_month . "-" . $start_day;
$end = substr(date("c"),0,19);

foreach($symbols as $k => $symbol){
    $requests[$k] = array();
    $requests[$k]['url'] = $URL. $symbol . "?" . "api_token=" . $api_key . "&fmt=json&from=" . $start;
    $requests[$k]['curl_handle'] = curl_init($requests[$k]['url']);
    curl_setopt($requests[$k]['curl_handle'], CURLOPT_HEADER, false);
    curl_setopt($requests[$k]['curl_handle'], CURLOPT_RETURNTRANSFER, true);
    curl_setopt($requests[$k]['curl_handle'], CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($requests[$k]['curl_handle'], CURLOPT_TIMEOUT, 30);
    curl_setopt($requests[$k]['curl_handle'], CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($requests[$k]['curl_handle'], CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($requests[$k]['curl_handle'], CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($requests[$k]['curl_handle'], CURLOPT_HTTPHEADER,
        array("Content-type: application/json")
    );

    curl_multi_add_handle($mh, $requests[$k]['curl_handle']);
}

//Execute our requests
$stillRunning = false;
do {
    curl_multi_exec($mh, $stillRunning);
} while ($stillRunning);

//Loop through the executed requests
foreach($requests as $k => $request){

    //Get the response content and the HTTP status code
    $requests[$k]['content'] = curl_multi_getcontent($request['curl_handle']);
    $requests[$k]['http_code'] = curl_getinfo($request['curl_handle'], CURLINFO_HTTP_CODE);
    $requests[$k]['error'] = curl_error($request['curl_handle']);
    curl_multi_remove_handle($mh, $request['curl_handle']);
    curl_close($requests[$k]['curl_handle']);
}

//Close multi handle.
curl_multi_close($mh);

$today = substr($end,0,10);

// Check if today is sunday
$todays_day = date("D");

// S&P 500

try {
    $data_array = json_decode($requests[0]["content"], true);

    $days = getOpenDaysUS($data_array);

    if($days == null) {
        $SNP = "-";
    }
    else {
        $SNP = round(100*($days["latest"] - $days["previous"])/$days["previous"], 2);
    }
}
catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    $SNP = null;
}


// Nasdaq

try {
    $data_array = json_decode($requests[1]["content"], true);

    $days = getOpenDaysUS($data_array);

    if($days == null) {
        $NASDAQ = "-";
    }
    else {
        $NASDAQ = round(100*($days["latest"] - $days["previous"])/$days["previous"], 2);
    }
}
catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    $NASDAQ = null;
}


// Dow Jones

try {
    $data_array = json_decode($requests[2]["content"], true);

    $days = getOpenDaysUS($data_array);

    if($days == null) {
        $DOW = "-";
    }
    else {
        $DOW = round(100*($days["latest"] - $days["previous"])/$days["previous"], 2);
    }
}
catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    $DOW = null;
}

// ADX

// try {
//     $content = json_decode($requests[3]["content"], true);
//     $data_array = $content["GetInterdayTimeSeries_Response_5"]["Row"];


//     $days = getOpenDaysMENA($data_array);


//     if($days == null) {
//         $ADX = "-";
//     }
//     else {
//         $ADX = round(100*($days["latest"] - $days["previous"])/$days["previous"], 2);
//     }
// }
// catch (Exception $e) {
//     echo "ERROR: " . $e->getMessage() . "<br>";
//     $ADX = null;
// }


// DFM

// try {
//     $content = json_decode($requests[5]["content"], true);
//     $data_array = $content["GetInterdayTimeSeries_Response_5"]["Row"];

//     $days = getOpenDaysMENA($data_array);

//     if($days == null) {
//         $DFM = "-";
//     }
//     else {
//         $DFM = round(100*($days["latest"] - $days["previous"])/$days["previous"], 2);
//     }
// }
// catch (Exception $e) {
//     echo "ERROR: " . $e->getMessage() . "<br>";
//     $DFM = null;
// }


// Tadawul

try {
    $data_array = json_decode($requests[3]["content"], true);

    $days = getOpenDaysMENA($data_array);

    if($days == null) {
        $TADAWUL = "-";
    }
    else {
        $TADAWUL = round(100*($days["latest"] - $days["previous"])/$days["previous"], 2);
    }
}
catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    $TADAWUL = null;
}


// 10-yr-ust

try {
    $data_array = json_decode($requests[4]["content"], true);

    $days = getOpenDaysUS($data_array);

    if($days == null) {
        $UST = "-";
    }
    else {
        $UST = round(100*($days["latest"] - $days["previous"])/$days["previous"], 2);
    }
}
catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    $UST = null;
}

// Bitcoin

// try {
//     $content = json_decode($requests[7]["content"], true);
//     $data_array = $content["GetInterdayTimeSeries_Response_5"]["Row"];
//     $latest = array_slice($data_array, -1)[0]["CLOSE"];

//     if ($todays_day == "Sun") {
//         $previous = array_slice($data_array, -7)[0]["CLOSE"];
//     }
//     else {
//         $previous = array_slice($data_array, -2)[0]["CLOSE"];
//     }

//     $BITCOIN = round(100*($latest - $previous)/$previous, 2);
// }
// catch (Exception $e) {
//     echo "ERROR: " . $e->getMessage() . "<br>";
//     $BITCOIN = null;
// }

// Oil

try {
    $data_array = json_decode($requests[5]["content"], true);

    $days = getOpenDaysUS($data_array);

    if($days == null) {
        $OIL = "-";
    }
    else {
        $OIL = round(100*($days["latest"] - $days["previous"])/$days["previous"], 2);
    }
}
catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    $OIL = null;
}


$indices = array(
    "S&P" => number_format((float)$SNP, 2, '.', ''),
    "Nasdaq" => number_format((float)$NASDAQ, 2, '.', ''),
    "Dow" => number_format((float)$DOW, 2, '.', ''),
    // "ADX" => number_format((float)$ADX, 2, '.', ''),
    // "DFM" => number_format((float)$DFM, 2, '.', ''),
    "Tadawul" => number_format((float)$TADAWUL, 2, '.', ''),
    "UST" => number_format((float)$UST, 2, '.', ''),
    // "Bitcoin" => number_format((float)$BITCOIN, 2, '.', ''),
    "Oil" => number_format((float)$OIL, 2, '.', ''),
);

print "<pre>";
print_r($indices);
print "<pre>";


?>
