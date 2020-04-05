<?php

namespace Typemill\Models;

class Helpers{

	public static function printTimer($timer)
	{
		$lastTime = NULL;
		$table = '<html><body><table>';
		$table .= '<tr><th>Breakpoint</th><th>Time</th><th>Duration</th></tr>';
		foreach($timer as $breakpoint => $time)
		{
			$duration = $time - $lastTime;
			
			$table .= '<tr>';
			$table .= '<td>' . $breakpoint . '</td>';
			$table .= '<td>' . $time . '</td>';
			$table .= '<td>' . $duration . '</td>';
			$table .= '</tr>';
			
			$lastTime = $time;
		}
		$table .= '</table></body></html>';
		echo $table;
	}

	public static function array_sort($array, $on, $order=SORT_ASC)
	{
	    $new_array = array();
	    $sortable_array = array();

	    if (count($array) > 0) {
	        foreach ($array as $k => $v) {
	            if (is_array($v)) {
	                foreach ($v as $k2 => $v2) {
	                    if ($k2 == $on) {
	                        $sortable_array[$k] = $v2;
	                    }
	                }
	            } else {
	                $sortable_array[$k] = $v;
	            }
	        }

	        switch ($order) {
	            case SORT_ASC:
	                asort($sortable_array);
	            break;
	            case SORT_DESC:
	                arsort($sortable_array);
	            break;
	        }

	        foreach ($sortable_array as $k => $v) {
	            $new_array[] = $array[$k];
			}
		}

		return $new_array;
	}
}