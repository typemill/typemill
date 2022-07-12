<?php

namespace Typemill\Static;

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
}