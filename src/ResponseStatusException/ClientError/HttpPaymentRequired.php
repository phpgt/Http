<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;

/**
 * Reserved for future use. The original intention was that this code might be
 * used as part of some form of digital cash or micropayment scheme, as
 * proposed, for example, by GNU Taler, but that has not yet happened, and this
 * code is not widely used. Google Developers API uses this status if a
 * particular developer has exceeded the daily limit on requests. Sipgate uses
 * this code if an account does not have sufficient funds to start a call.
 * Shopify uses this code when the store has not paid their fees and is
 * temporarily disabled. Stripe uses this code for failed payments where
 * parameters were correct, for example blocked fraudulent payments.
 * @linkhttps://httpstatuses.com/402
 */
class HttpPaymentRequired extends ClientErrorException {
	public function getHttpCode():int {
		return StatusCode::PAYMENT_REQUIRED;
	}
}
