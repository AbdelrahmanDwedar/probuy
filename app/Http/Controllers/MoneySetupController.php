<?php

// TODO

namespace App\Http\Controllers;

use Cartalyst\Stripe\Stripe;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Validator;

class MoneySetupController extends Controller
{
    public function paymentStripe()
    {
        return view('paymentstripe');
    }

    public function postPaymentStripe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_no' => 'required',
            'ccExpiryMonth' => 'required',
            'ccExpiryYear' => 'required',
            'cvvNumber' => 'required',
            'amount' => 'required',
        ]);
        $input = $request->all();
        if ($validator->passes()) {
            $input = array_except($input, ['_token']);
            $stripe = Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
            try {
                $token = $stripe->tokens()->create([
                    'card' => [
                        'number' => $request->get('card_no'),
                        'exp_month' => $request->get('ccExpiryMonth'),
                        'exp_year' => $request->get('ccExpiryYear'),
                        'cvc' => $request->get('cvvNumber'),
                    ],
                ]);
                if (! isset($token['id'])) {
                    return to_route('addmoney.paymentstripe');
                }
                $charge = $stripe->charges()->create([
                    'card' => $token['id'],
                    'currency' => 'USD',
                    'amount' => 20.49,
                    'description' => 'wallet',
                ]);

                if ($charge['status'] == 'succeeded') {
                    echo '<pre>';
                    print_r($charge);
                    exit();

                    return to_route('addmoney.paymentstripe');
                } else {
                    \Session::put('error', 'Money not add in wallet!!');

                    return to_route('addmoney.paymentstripe');
                }
            } catch (Exception|Cartalyst\Stripe\Exception\MissingParameterException $e) {
                Session::put('error', $e->getMessage());

                return to_route('addmoney.paymentstripe');
            } catch (Cartalyst\Stripe\Exception\CardErrorException $e) {
                Session::put('error', $e->getMessage());

                return to_route('addmoney.paywithstripe');
            }
        }

        return null;
    }
}
