<?php
namespace Nateritter\Binance;

use Illuminate\Support\ServiceProvider;

class BinanceServiceProvider extends ServiceProvider {

	public function boot()
	{
		$this->publishes([
			__DIR__.'/../config/binance.php' => config_path('binance.php')
		]);
	} // boot

	public function register()
	{
		$this->mergeConfigFrom(__DIR__.'/../config/binance.php', 'binance');
		$this->app->bind('binance', function() {
			return new BinanceAPI(config('binance'));
		});
	} // register
}
