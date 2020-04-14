<?php

namespace Yosmy\Phone\Test\Twilio;

use Yosmy;
use Yosmy\Http;
use Yosmy\Phone\Twilio;
use PHPUnit\Framework\TestCase;
use LogicException;

class SendSmsTest extends TestCase
{
    public function testSend()
    {
        $country = 'country';
        $prefix = 'prefix';
        $number = 'number';
        $text = 'text';

        $accountSid = 'account-sid';
        $authToken = 'auth-token';
        $serviceSid = 'service-sid';

        $executeRequest = $this->createMock(Http\ExecuteRequest::class);

        $response = $this->createMock(Http\Response::class);

        $body = ['body'];

        $response->expects($this->once())
            ->method('getBody')
            ->with()
            ->willReturn($body);

        $executeRequest->expects($this->once())
            ->method('execute')
            ->with(
                Http\ExecuteRequest::METHOD_POST,
                sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $accountSid),
                [
                    'auth' => [$accountSid, $authToken],
                    'form_params' => [
                        'MessagingServiceSid' => $serviceSid,
                        'To' => sprintf('+%s%s', $prefix, $number),
                        'Body' => $text
                    ]
                ]
            )
            ->willReturn($response);

        $logEvent = $this->createMock(Yosmy\LogEvent::class);

        $logEvent->expects($this->once())
            ->method('log')
            ->with(
                [
                    'yosmy.phone.twilio.send_sms_success',
                    'success'
                ],
                [
                    'request' => [
                        'MessagingServiceSid' => $serviceSid,
                        'To' => sprintf('+%s%s', $prefix, $number),
                        'Body' => $text
                    ],
                    'response' => $body
                ],
                []
            );

        $reportError = $this->createMock(Yosmy\ReportError::class);

        $sendSms = new Twilio\SendSms(
            $accountSid,
            $authToken,
            $serviceSid,
            $executeRequest,
            $logEvent,
            $reportError
        );

        try {
            $sendSms->send(
                $country,
                $prefix,
                $number,
                $text
            );
        } catch (Yosmy\Phone\SmsException $e) {
            throw new LogicException();
        }
    }

    /**
     * @throws Yosmy\Phone\SmsException
     */
    public function testSendHavingHttpException()
    {
        $country = 'country';
        $prefix = 'prefix';
        $number = 'number';
        $text = 'text';

        $accountSid = 'account-sid';
        $authToken = 'auth-token';
        $serviceSid = 'service-sid';

        $executeRequest = $this->createMock(Http\ExecuteRequest::class);

        $logEvent = $this->createMock(Yosmy\LogEvent::class);

        $response = ['response'];

        $logEvent->expects($this->once())
            ->method('log')
            ->with(
                [
                    'yosmy.phone.twilio.send_sms_fail',
                    'fail'
                ],
                [
                    'request' => [
                        'MessagingServiceSid' => $serviceSid,
                        'To' => sprintf('+%s%s', $prefix, $number),
                        'Body' => $text
                    ],
                    'response' => $response
                ],
                []
            );

        $exception = $this->createMock(Yosmy\Http\Exception::class);

        $exception->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);

        $executeRequest->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);

        $reportError = $this->createMock(Yosmy\ReportError::class);

        $reportError->expects($this->once())
            ->method('report')
            ->with($exception);

        $this->expectException(Yosmy\Phone\SmsException::class);

        $sendSms = new Twilio\SendSms(
            $accountSid,
            $authToken,
            $serviceSid,
            $executeRequest,
            $logEvent,
            $reportError
        );

        $sendSms->send(
            $country,
            $prefix,
            $number,
            $text
        );
    }
}