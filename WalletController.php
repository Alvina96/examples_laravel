<?php

namespace App\Http\Controllers\Member\Profile;

use App\Http\Controllers\AuthenticatedController;
use App\Models\UserBanks;
use App\Models\UserCards;
use App\Models\UserCryptoWallets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\BankAddedMailable;
use App\Mail\CreditCardAddedMailable;
use App\Mail\CryptoWalletsAddedMailable;

class WalletController extends AuthenticatedController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $this->response->title = __('member.profile.wallet.title');
        $this->response->icon = 'wallet';

        return $this->render('member.profile.wallet');
    }

    public function banks(Request $request)
    {
        $this->response->title = __('member.profile.wallet.banks.title');
        $this->response->icon = 'university';

        $data = $request->all();
        if (count($data)) {
            $validate = [
                'name' => ['required', 'string'],
                'address' => ['required', 'string'],
                'iban' => ['required', 'string'],
                'swift' => ['required', 'string'],
                'currency_id' => ['required', 'integer'],
            ];

            if ($request->validate($validate)) {
                try {
                    $data['user_id'] = $this->currentUser->id;

                    DB::transaction(function () use ($data) {
                        UserBanks::create($data);
                    });

                    $this->response->success = true;
                    $data = [];
                    Mail::to($this->currentUser->email)->send(new BankAddedMailable());
                } catch (\Exception $err) {
                    $this->response->error = $err;
                }
            }
        }

        # Generate fields
        $this->response->fields = [
            'name' => (object) [
                'name' => 'name',
                'label' => __('member.profile.wallet.banks.bankname'),
                'value' => isset($data['name']) ? $data['name'] : '',
                'placeholder' => '',
                'type' => 'text',
                'required' => true,
                'icon' => 'university',
                'autofocus' => true
            ],

            'address' => (object) [
                'name' => 'address',
                'label' => __('member.profile.wallet.banks.address'),
                'value' => isset($data['address']) ? $data['address'] : '',
                'placeholder' => '',
                'type' => 'text',
                'required' => true,
                'icon' => 'signature',
                'autofocus' => false
            ],

            'iban' => (object) [
                'name' => 'iban',
                'label' => __('member.profile.wallet.banks.iban'),
                'value' => isset($data['iban']) ? $data['iban'] : '',
                'placeholder' => '',
                'type' => 'text',
                'required' => true,
                'icon' => 'signature',
                'autofocus' => false
            ],

            'swift' => (object) [
                'name' => 'swift',
                'label' => __('member.profile.wallet.banks.swift'),
                'value' => isset($data['swift']) ? $data['swift'] : '',
                'placeholder' => '',
                'type' => 'text',
                'required' => true,
                'icon' => 'signature',
                'autofocus' => false
            ],

            'currency_id' => (object) [
                'name' => 'currency_id',
                'label' => __('member.profile.wallet.banks.currency'),
                'type' => 'select',
                'required' => true,
                'icon' => 'coins',
                'autofocus' => false,
                'value' => isset($data['currency_id']) ? $data['currency_id'] : $this->currentUser->currency_id,
                'data' => [
                    'url' => '/api/currencies/list',
                    'value' => 'id',
                    'name' => 'name'
                ]
            ],
        ];

        return $this->render('member.profile.wallet.banks');
    }

    public function cards(Request $request)
    {
        $this->response->title = __('member.profile.wallet.cards.title');
        $this->response->icon = 'credit-card';

        $data = $request->all();
        if (count($data)) {
            $validate = [
                'name' => ['required', 'string'],
                'number' => ['required', 'string'],
                'month' => ['required', 'string'],
                'year' => ['required', 'string'],
                'cvv' => ['required', 'integer'],
                'currency_id' => ['required', 'integer'],
            ];

            if ($request->validate($validate)) {
                try {
                    DB::transaction(function () use ($data) {
                        $data['user_id'] = $this->currentUser->id;

                        UserCards::create($data);
                    });

                    Mail::to($this->currentUser->email)->send(new CreditCardAddedMailable());
                    $this->response->success = true;
                    $data = [];
                } catch (\Exception $err) {
                    $this->response->error = $err;
                }
            }
        }

        # Generate fields
        $this->response->fields = [
            'name' => (object) [
                'name' => 'name',
                'label' => __('member.profile.wallet.cards.fullname'),
                'value' => isset($data['name']) ? $data['name'] : '',
                'placeholder' => __('member.profile.wallet.cards.fullname'),
                'type' => 'text',
                'required' => true,
                'icon' => 'user',
                'autofocus' => true
            ],

            'number' => (object) [
                'name' => 'number',
                'label' => __('member.profile.wallet.cards.cardno'),
                'value' => isset($data['number']) ? $data['number'] : '',
                'placeholder' => __('member.profile.wallet.cards.cardnopl'),
                'type' => 'text',
                'required' => true,
                'icon' => 'cc-visa, cc-amex, cc-mastercard',
                'autofocus' => false
            ],

            'expiration' => [
                'month' => (object) [
                    'name' => 'month',
                    'label' => __('member.profile.wallet.cards.mmm'),
                    'value' => isset($data['month']) ? $data['month'] : '',
                    'placeholder' => __('member.profile.wallet.cards.mm'),
                    'type' => 'text',
                    'required' => true,
                    'autofocus' => false
                ],

                'year' => (object) [
                    'name' => 'year',
                    'label' => __('member.profile.wallet.cards.yyy'),
                    'value' => isset($data['year']) ? $data['year'] : '',
                    'placeholder' => __('member.profile.wallet.cards.yy'),
                    'type' => 'text',
                    'required' => true,
                    'autofocus' => false
                ],

                'cvv' => (object) [
                    'name' => 'cvv',
                    'label' => __('member.profile.wallet.cards.cvv'),
                    'value' => isset($data['cvv']) ? $data['cvv'] : '',
                    'placeholder' => __('member.profile.wallet.cards.cvv'),
                    'type' => 'text',
                    'required' => true,
                    'autofocus' => false,
                    'help' => __('member.profile.wallet.cards.help'),
                ],
            ],

            'currency_id' => (object) [
                'name' => 'currency_id',
                'label' => 'Preferred Currency',
                'type' => 'select',
                'required' => true,
                'icon' => 'coins',
                'autofocus' => false,
                'value' => isset($data['currency_id']) ? $data['currency_id'] : $this->currentUser->currency_id,
                'data' => [
                    'url' => '/api/currencies/list',
                    'value' => 'id',
                    'name' => 'name'
                ]
            ],
        ];

        return $this->render('member.profile.wallet.cards');
    }

    public function crypto_wallets(Request $request)
    {
        $this->response->title = __('member.profile.wallet.crypto_wallets.title');
        $this->response->icon = 'coins';

        $data = $request->all();
        if (count($data)) {
            $validate = [
                'name' => ['required', 'string'],
                'account' => ['required', 'string'],
                'crypto_coin_id' => ['required', 'integer'],
            ];

            if ($request->validate($validate)) {
                try {
                    $data['user_id'] = $this->currentUser->id;

                    DB::transaction(function () use ($data) {
                        UserCryptoWallets::create($data);
                    });
                    Mail::to($this->currentUser->email)->send(new CryptoWalletsAddedMailable());
                    $this->response->success = true;
                    $data = [];
                } catch (\Exception $err) {
                    $this->response->error = $err;
                }
            }
        }

        # Generate fields
        $this->response->fields = [
            'name' => (object) [
                'name' => 'name',
                'label' => __('member.profile.wallet.crypto_wallets.crname'),
                'value' => isset($data['name']) ? $data['name'] : '',
                'placeholder' => '',
                'type' => 'text',
                'required' => true,
                'icon' => 'signature',
                'autofocus' => true
            ],

            'account' => (object) [
                'name' => 'account',
                'label' => __('member.profile.wallet.crypto_wallets.crwallet'),
                'value' => isset($data['account']) ? $data['account'] : '',
                'placeholder' => '',
                'type' => 'text',
                'required' => true,
                'icon' => 'signature',
                'autofocus' => false
            ],

            'crypto_coin_id' => (object) [
                'name' => 'crypto_coin_id',
                'label' =>  __('member.profile.wallet.crypto_wallets.crpref'),
                'type' => 'select',
                'required' => true,
                'icon' => 'coins',
                'autofocus' => false,
                'value' => isset($data['crypto_coin_id']) ? $data['crypto_coin_id'] : '',
                'data' => [
                    'url' => '/api/crypto_coins/list',
                    'value' => 'id',
                    'name' => 'name'
                ]
            ],
        ];

        return $this->render('member.profile.wallet.crypto_wallets');
    }
}
