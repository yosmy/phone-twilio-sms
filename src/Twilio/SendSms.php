<?php

namespace Yosmy\Phone\Twilio;

use Yosmy;
use Yosmy\Phone\SmsException;

/**
 * @di\service()
 */
class SendSms implements Yosmy\Phone\SendSms
{
    /**
     * @var string
     */
    private $accountSid;

    /**
     * @var string
     */
    private $authToken;

    /**
     * @var string
     */
    private $serviceSid;

    /**
     * @var Yosmy\Http\ExecuteRequest
     */
    private $executeRequest;

    /**
     * @var Yosmy\LogEvent
     */
    private $logEvent;

    /**
     * @var Yosmy\ReportError
     */
    private $reportError;

    /**
     * @di\arguments({
     *     accountSid: "%twilio_account_sid%",
     *     authToken:  "%twilio_auth_token%",
     *     serviceSid: "%twilio_sms_service_sid%",
     * })
     *
     * @param string                    $accountSid
     * @param string                    $authToken
     * @param string                    $serviceSid
     * @param Yosmy\Http\ExecuteRequest $executeRequest
     * @param Yosmy\LogEvent            $logEvent
     * @param Yosmy\ReportError         $reportError
     */
    public function __construct(
        string $accountSid,
        string $authToken,
        string $serviceSid,
        Yosmy\Http\ExecuteRequest $executeRequest,
        Yosmy\LogEvent $logEvent,
        Yosmy\ReportError $reportError
    ) {
        $this->accountSid = $accountSid;
        $this->authToken = $authToken;
        $this->serviceSid = $serviceSid;
        $this->executeRequest = $executeRequest;
        $this->logEvent = $logEvent;
        $this->reportError = $reportError;
    }

    /**
     * {@inheritDoc}
     */
    public function send(
        string $country,
        string $prefix,
        string $number,
        string $text
    ) {
        unset($country);

        $request = [
            'MessagingServiceSid' => $this->serviceSid,
            'To' => sprintf('+%s%s', $prefix, $number),
            'Body' => $text
        ];

        try {
            $response = $this->executeRequest->execute(
                Yosmy\Http\ExecuteRequest::METHOD_POST,
                sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $this->accountSid),
                [
                    'auth' => [$this->accountSid, $this->authToken],
                    'form_params' => $request
                ]
            );

            $this->logEvent->log(
                [
                    'yosmy.phone.twilio.send_sms_success',
                    'success'
                ],
                [
                    'request' => $request,
                    'response' => $response->getBody()
                ],
                []
            );
        } catch (Yosmy\Http\Exception $e) {
            $response = $e->getResponse();

            $this->logEvent->log(
                [
                    'yosmy.phone.twilio.send_sms_fail',
                    'fail'
                ],
                [
                    'request' => $request,
                    'response' => $response
                ],
                []
            );

            $this->reportError->report($e);

            throw new SmsException($response);
        }
    }
}