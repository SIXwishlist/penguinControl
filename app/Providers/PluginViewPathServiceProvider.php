<?php

namespace App\Providers;


use App\Plugin;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class PluginViewPathServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot ()
	{
		$viewFinder = View::getFinder ();

		foreach (Plugin::all () as $plugin) {
			if ($plugin->overrideViews)
				$viewFinder->prependLocation ($plugin->getFolder () . 'views');
		}
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register ()
	{
	}
}