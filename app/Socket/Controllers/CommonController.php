<?php


namespace App\Socket\Controllers;


abstract class CommonController
{

    /**
     * Processes the request string (gamespy v1) and builds some associative arrays
     * The number of arrays is based off the number of requests in one query
     * @param $message
     * @return array
     */
    public function messageToArray($message): array
    {
        // Process the message
        $query = [];
        $queryId = null;

        $requests = explode('final\\', $message);

        foreach($requests as $index => $request) {
            // This regex will match \queryid\%d.%d\
            $regex = '/[\\\\]?(queryid\\\\\d\.\d\\\\)[\\\\]?/m';
            $match = [];

            // Ok actually look for it, and we didn't find it, skip to the next map
            if(!preg_match($regex, $request, $match)) {
                continue;
            }

            // Remove the queryid from the request list (optional, we could just skip it!)
            $requests[$index] = str_replace($match[1], '', $requests[$index]);

            $regex = '/(\d\.\d)/m';
            $digitMatch = [];

            // Ok just extract the digits now, more reliable with regex
            if(!preg_match($regex, $match[1], $digitMatch)) {
                \Log::warning("[CommonController::messageToArray] Query somehow lost its digits! Data: {$request}");
                continue;
            }

            $queryId = $digitMatch[1];
        }

        // Bad query, but let's try to roll with it!
        if ($queryId === null) {
            //\Log::warning("[CommonController::messageToArray] No query id in query! Assigning 1.1. Data: {$message}");
            $queryId = '1.1';
        }

        // Loop through the number of requests we got
        foreach ($requests as $index => $request) {
            if ($request === '') {
                continue;
            }

            $request_array = explode("\\", $request);

            // Trim the array if needed
            if ($request_array[0] === '') {
                array_shift($request_array);
            }

            if ($request_array[count($request_array) - 1] === '') {
                array_pop($request_array);
            }


            $requestCount = count($request_array);

            // Loop through the individual requests!
            for ($i = 0; $i < $requestCount; $i += 2) {
                if($request_array[$i] === '') {
                    $i--;
                    continue;
                }
                $key   = strtolower($request_array[$i]);
                $value = $request_array[$i + 1] ?? null;

                \Arr::set($query, "{$index}.{$key}", $value);
            }
        }

        // Append our query id to the end!
        $query[] = ['queryid' => $queryId];


        return $query;
    }

    public function packIP($ip)
    {
        // ip2long reverses endianess...so pack it and unpack it in reverse, and then pack it again.
        $packed_reversed = ip2long($ip);
        $packed_reversed = pack('N', $packed_reversed);
        $ip              = unpack("V", $packed_reversed);
        $ip              = array_shift($ip);
        return pack("V", $ip);
    }
}
