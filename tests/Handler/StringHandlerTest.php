<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Test\Handler;

use GuzzleHttp\RequestOptions;
use GuzzleLogMiddleware\Handler\StringHandler;
use GuzzleLogMiddleware\LogMiddleware;
use GuzzleLogMiddleware\Test\AbstractLoggerMiddlewareTest;
use Psr\Log\LogLevel;

final class StringHandlerTest extends AbstractLoggerMiddlewareTest
{
    /**
     * @var StringHandler
     */
    private $handler;

    public function setUp()
    {
        parent::setUp();
        $this->handler = new StringHandler();
    }

    /**
     * @test
     */
    public function logSuccessfulTransaction()
    {
        $this->appendResponse(200, [], 'response_body')
            ->createClient()
            ->get('/', [
                RequestOptions::BODY => 'request_body',
            ]);

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertStringStartsWith("Guzzle HTTP request:
GET / HTTP/1.1\r
Host: \r
User-Agent: GuzzleHttp/6.3.3 curl/7.55.1", $this->logger->records[0]['message']);
        $this->assertCount(0, $this->logger->records[0]['context']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame("Guzzle HTTP response:
HTTP/1.1 200 OK\r
\r
response_body", $this->logger->records[1]['message']);
        $this->assertCount(0, $this->logger->records[1]['context']);
    }

    /**
     * @test
     */
    public function handlerWillLogAllPossibleValues()
    {
        $this->handler->log($this->logger, $this->request, $this->response, $this->reason, $this->stats, []);
        $this->assertCount(3, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[2]['level']);
        $this->logger->reset();
    }

    /**
     * @test
     */
    public function handlerWillRecordRequest()
    {
        $this->handler->log($this->logger, $this->request, null, null, null, []);
        $this->assertCount(1, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertStringStartsWith('Guzzle HTTP request:', $this->logger->records[0]['message']);
        $this->assertCount(0, $this->logger->records[0]['context']);
    }

    /**
     * @test
     */
    public function handlerWithValueException()
    {
        $this->handler->log($this->logger, $this->request, null, $this->reason, null, []);
        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertStringStartsWith('Guzzle HTTP exception: ', $this->logger->records[1]['message']);
    }

    /**
     * @test
     */
    public function handlerWithValueTransferStats()
    {
        $this->handler->log($this->logger, $this->request, $this->response, null, $this->stats, []);
        $this->assertCount(3, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP transfer time: 0.01 for uri: http://www.test.com/', $this->logger->records[1]['message']);
    }

    protected function createMiddleware(): LogMiddleware
    {
        return new LogMiddleware($this->logger, $this->handler);
    }
}
