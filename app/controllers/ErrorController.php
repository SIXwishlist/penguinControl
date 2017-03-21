<?php

class ErrorController extends BaseController
{
	public function show ()
	{
		$ex = Session::get ('ex');
		$alerts = Session::get ('alerts');
		$strAlerts = '';
		$mailSent = false;
		
		if (! (empty ($ex) && empty ($alerts)))
		{
			if (! empty ($alerts))
			{
				foreach ($alerts as $key => $alert)
					$strAlerts .= '[' . $key . '] ' . $alert->getMessage () . PHP_EOL;
			}
		}
		
		return View::make ('layout.error', compact ('ex', 'alerts', 'mailSent'));
	}
}
