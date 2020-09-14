<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\AuthenticatedController;

class TradingController extends AuthenticatedController
{
    public function __construct()
    {
        session_start();
        parent::__construct();
    }

    public function index()
    {
        $this->response->title = __('member.trading.title');
        $this->response->icon = 'chart-line';
        $this->GenerateVerifyCode();

        return $this->render('member/trading');
    }

    public function GenerateVerifyCode()
    {
    $random_string = '';
    for ($i = 0; $i < 5; $i++)
      {
      $random_string .= rand(0, 9);
      }
    $_SESSION['verify_code'] = $random_string;
    }
}
