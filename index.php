<?php



  /////////////////////////////////////////////
  //                                         //
  //                                         //
  //                                         //
  //           Igor Meshcheryakov            //
  //                                         //
  //                                         //
  //  https://github.com/garik-code          //
  //  https://www.garik.site                 //
  //                                         //
  //  mail@garik.site                        //
  //                                         //
  //                                         //
  /////////////////////////////////////////////



  include 'vendor/autoload.php';

  header ('Access-Control-Allow-Origin: *');
  ini_set('display_errors', 0);
  ini_set('display_startup_errors', 0);
  date_default_timezone_set('UTC');

  $app = new Slim\App();

  $container = $app->getContainer();
  $container['errorHandler'] = function ($container){
    return function ($request, $response, $exception) use ($container) {
      return $container['response']->write(json_encode(['success'=>'false', 'error'=>'501']));
    };
  };
  $container['notFoundHandler'] = function ($container){
    return function ($request, $response, $exception) use ($container) {
      return $container['response']->write(json_encode(['success'=>'false', 'error'=>'404']));
    };
  };
  $container['notAllowedHandler'] = function ($container){
    return function ($request, $response, $exception) use ($container) {
      return $container['response']->write(json_encode(['success'=>'false', 'error'=>'405']));
    };
  };
  $container['phpErrorHandler'] = function ($container){
    return function ($request, $response, $exception) use ($container) {
      return $container['response']->write(json_encode(['success'=>'false', 'error'=>'500']));
    };
  };

  $app->get('/url/{url}', function ($request, $response, $args) {
    
    $url = strtr($args['url'], ['$'=>'/']);
    return $response->getBody()->write(file_get_contents($url));
    
  });

  $app->get('/exchanges', function ($request, $response, $args) {

    $exchanges = \ccxt\Exchange::$exchanges;
    return $response->getBody()->write(json_encode(['success'=>'true', 'return'=>$exchanges]));

  });

  $app->get('/{exchange}/info', function ($request, $response, $args) {

    $exchange = mb_strtolower($args['exchange']);
    $exchange = '\\ccxt\\'.$exchange;
    $exchange = new $exchange();
    $info = ['exchange'=>$exchange->id, 'urls'=>$exchange->urls, 'limits'=>$exchange->limits, 'ohlcv'=>$exchange->timeframes];

    return $response->getBody()->write(json_encode(['success'=>'true', 'return'=>$info]));

  });

  $app->get('/{exchange}/markets', function ($request, $response, $args) {

    $exchange = mb_strtolower($args['exchange']);
    $exchange = '\\ccxt\\'.$exchange;
    $exchange = new $exchange();

    return $response->getBody()->write(json_encode(['success'=>'true', 'return'=>$exchange->load_markets()]));

  });

  $app->get('/{exchange}/tickers', function ($request, $response, $args) {

    $exchange = mb_strtolower($args['exchange']);
    $exchange = '\\ccxt\\'.$exchange;
    $exchange = new $exchange();

    return $response->getBody()->write(json_encode(['success'=>'true', 'return'=>$exchange->fetch_tickers()]));

  });

  $app->get('/{exchange}/orderbook/{market_a}/{market_b}/{count}', function ($request, $response, $args) {

    $exchange = mb_strtolower($args['exchange']);
    $exchange = '\\ccxt\\'.$exchange;
    $exchange = new $exchange();

    $market = strtoupper($args['market_a'].'/'.$args['market_b']);

    return $response->getBody()->write(json_encode(['success'=>'true', 'return'=>$exchange->fetch_order_book($market, $args['count'])]));

  });

  $app->get('/{exchange}/ticker/{market_a}/{market_b}', function ($request, $response, $args) {

    $exchange = mb_strtolower($args['exchange']);
    $exchange = '\\ccxt\\'.$exchange;
    $exchange = new $exchange();

    $market = strtoupper($args['market_a'].'/'.$args['market_b']);

    return $response->getBody()->write(json_encode(['success'=>'true', 'return'=>$exchange->fetch_ticker($market)]));

  });

  $app->get('/{exchange}/history/{market_a}/{market_b}', function ($request, $response, $args) {

    $exchange = mb_strtolower($args['exchange']);
    $exchange = '\\ccxt\\'.$exchange;
    $exchange = new $exchange();

    $market = strtoupper($args['market_a'].'/'.$args['market_b']);

    return $response->getBody()->write(json_encode(['success'=>'true', 'return'=>$exchange->fetch_trades($market)]));

  });

  $app->get('/{exchange}/ohlcv/{market_a}/{market_b}/{timeframe}/{limit}', function ($request, $response, $args) {

    $cryptocompare = json_decode(file_get_contents('https://min-api.cryptocompare.com/data/'.$args['timeframe'].'?fsym='.mb_strtoupper($args['market_a']).'&tsym='.mb_strtoupper($args['market_b']).'&limit='.$args['limit'].'&aggregate=1&e='.$args['exchange']), true);
    print json_encode($cryptocompare['Data']);

  });

  $app->post('/{exchange}/balance', function ($request, $response, $args) {

    if(isset($_POST['key']) && !empty($_POST['key'])){

      $login = base64_decode($_POST['key']);
      $login = explode(':', $login);

      if(!isset($login[1]) || empty($login[1])){
        $return = json_encode(['success'=>'false', 'error'=>'Error post key']);
      }else{

        $exchange = mb_strtolower($args['exchange']);
        $exchange = '\\ccxt\\'.$exchange;
        $exchange = new $exchange();

        $exchange->apiKey = $login[0];
        $exchange->secret = $login[1];

        $return = json_encode(['success'=>'true', 'return'=>$exchange->fetch_balance()]);

      }

    }else{
      $return = json_encode(['success'=>'false', 'error'=>'Empty post key']);
    }

    return $response->getBody()->write($return);

  });

  $app->post('/{exchange}/ordersopen', function ($request, $response, $args) {

    if(isset($_POST['key']) && !empty($_POST['key'])){

      $login = base64_decode($_POST['key']);
      $login = explode(':', $login);

      if(!isset($login[1]) || empty($login[1])){
        $return = json_encode(['success'=>'false', 'error'=>'Error post key']);
      }else{

        $exchange = mb_strtolower($args['exchange']);
        $exchange = '\\ccxt\\'.$exchange;
        $exchange = new $exchange();

        $exchange->apiKey = $login[0];
        $exchange->secret = $login[1];

        if($exchange->has['fetchOpenOrders']){
          $orders = $exchange->fetchOpenOrders();
          if(!empty($orders)){
            $return = json_encode(['success'=>'true', 'return'=>$orders]);
          }else{
            $return = json_encode(['success'=>'false']);
          }
        }else{
          $return = json_encode(['success'=>'false']);
        }

      }

    }else{
      $return = json_encode(['success'=>'false', 'error'=>'Empty post key']);
    }

    return $response->getBody()->write($return);

  });

  $app->post('/{exchange}/ordersclose', function ($request, $response, $args) {

    if(isset($_POST['key']) && !empty($_POST['key'])){

      $login = base64_decode($_POST['key']);
      $login = explode(':', $login);

      if(!isset($login[1]) || empty($login[1])){
        $return = json_encode(['success'=>'false', 'error'=>'Error post key']);
      }else{

        $exchange = mb_strtolower($args['exchange']);
        $exchange = '\\ccxt\\'.$exchange;
        $exchange = new $exchange();

        $exchange->apiKey = $login[0];
        $exchange->secret = $login[1];

        if($exchange->has['fetchClosedOrders']){
          $orders = $exchange->fetchClosedOrders();
          if(!empty($orders)){
            $return = json_encode(['success'=>'true', 'return'=>$orders]);
          }else{
            $return = json_encode(['success'=>'false']);
          }
        }else{
          $return = json_encode(['success'=>'false']);
        }

      }

    }else{
      $return = json_encode(['success'=>'false', 'error'=>'Empty post key']);
    }

    return $response->getBody()->write($return);

  });

  $app->post('/{exchange}/buy/{market_a}/{market_b}/{quantity}/{rate}', function ($request, $response, $args) {

    if(isset($_POST['key']) && !empty($_POST['key'])){

      $login = base64_decode($_POST['key']);
      $login = explode(':', $login);

      if(!isset($login[1]) || empty($login[1])){
        $return = json_encode(['success'=>'false', 'error'=>'Error post key']);
      }else{

        $exchange = mb_strtolower($args['exchange']);
        $exchange = '\\ccxt\\'.$exchange;
        $exchange = new $exchange();

        $exchange->apiKey = $login[0];
        $exchange->secret = $login[1];
        //$exchange->verbose = true;

        $market = $args['market_a'].'/'.$args['market_b'];
        $market = strtoupper($market);

        $return = $exchange->create_order($market, 'limit', 'buy', $args['quantity'], $args['rate']);
        $return = json_encode(['success'=>'true', 'return'=>$return]);

      }

    }else{
      $return = json_encode(['success'=>'false', 'error'=>'Empty post key']);
    }

    return $response->getBody()->write($return);

  });

  $app->post('/{exchange}/sell/{market_a}/{market_b}/{quantity}/{rate}', function ($request, $response, $args) {

    if(isset($_POST['key']) && !empty($_POST['key'])){

      $login = base64_decode($_POST['key']);
      $login = explode(':', $login);

      if(!isset($login[1]) || empty($login[1])){
        $return = json_encode(['success'=>'false', 'error'=>'Error post key']);
      }else{

        $exchange = mb_strtolower($args['exchange']);
        $exchange = '\\ccxt\\'.$exchange;
        $exchange = new $exchange();

        $exchange->apiKey = $login[0];
        $exchange->secret = $login[1];
        //$exchange->verbose = true;

        $market = $args['market_a'].'/'.$args['market_b'];
        $market = strtoupper($market);

        $return = $exchange->create_order($market, 'limit', 'sell', $args['quantity'], $args['rate']);
        $return = json_encode(['success'=>'true', 'return'=>$return]);

      }

    }else{
      $return = json_encode(['success'=>'false', 'error'=>'Empty post key']);
    }

    return $response->getBody()->write($return);

  });

  $app->post('/{exchange}/cancel/{id}', function ($request, $response, $args) {

    if(isset($_POST['key']) && !empty($_POST['key'])){

      $login = base64_decode($_POST['key']);
      $login = explode(':', $login);

      if(!isset($login[1]) || empty($login[1])){
        $return = json_encode(['success'=>'false', 'error'=>'Error post key']);
      }else{

        $exchange = mb_strtolower($args['exchange']);
        $exchange = '\\ccxt\\'.$exchange;
        $exchange = new $exchange();

        $exchange->apiKey = $login[0];
        $exchange->secret = $login[1];

        $return = json_encode(['success'=>'true', 'return'=>$exchange->cancel_order($args['id'])]);

      }

    }else{
      $return = json_encode(['success'=>'false', 'error'=>'Empty post key']);
    }

    return $response->getBody()->write($return);

  });

  $app->run();



?>
