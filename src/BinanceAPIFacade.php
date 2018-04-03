<?php
namespace Nateritter\Binance;

use Illuminate\Support\Facades\Facade;

class BinanceAPIFacade extends Facade {

	protected static function getFacadeAccessor() {
		return 'binance';
	}
}
