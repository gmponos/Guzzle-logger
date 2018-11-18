<?php

namespace Gmponos\GuzzleLogger\Test\Unit\Handler\LogLevelStrategy;

use Gmponos\GuzzleLogger\Handler\ArrayHandler;
use Gmponos\GuzzleLogger\Handler\LogLevelStrategy\StatusCodeStrategy;
use Gmponos\GuzzleLogger\Middleware\LoggerMiddleware;
use Gmponos\GuzzleLogger\Test\Unit\AbstractLoggerMiddlewareTest;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LogLevel;

final class StatusCodeStrategyTest extends AbstractLoggerMiddlewareTest
{
    protected function createMiddleware(): LoggerMiddleware
    {
        $strategy = new StatusCodeStrategy();
        $strategy->setLevel(300, LogLevel::WARNING);
        $strategy->setLevel(301, LogLevel::WARNING);
        $strategy->setLevel(400, LogLevel::WARNING);
        $strategy->setLevel(403, LogLevel::WARNING);
        $strategy->setLevel(500, LogLevel::WARNING);
        return new LoggerMiddleware($this->logger, new ArrayHandler($strategy));
    }

    /**
     * @test
     */
    public function logTransactionWithNoCustomLevels()
    {
        $this->appendResponse(401)
            ->createClient([
                RequestOptions::HTTP_ERRORS => false,
            ])
            ->get('/');

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
    }

    /**
     * @test
     */
    public function logTransactionWhenTransferExceptionOccurs()
    {
        try {
            $this->mockHandler->append(new TransferException());
            $this->createClient()->get('/');
        } catch (\Exception $e) {
        }

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::CRITICAL, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP exception', $this->logger->history[1]['message']);
    }

    /**
     * @test
     */
    public function logTransactionWithCustomLevel()
    {
        $this->appendResponse(300)
            ->createClient([
                'log' => [
                    'levels' => [
                        300 => LogLevel::WARNING,
                        301 => LogLevel::WARNING,
                        402 => LogLevel::WARNING,
                        403 => LogLevel::WARNING,
                        500 => LogLevel::WARNING,
                    ],
                ],
            ])
            ->get('/');

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::WARNING, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
    }
}
