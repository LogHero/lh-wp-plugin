<?php
namespace LogHero\Wordpress;
require_once __DIR__ . '/mock-microtime.php';


class LogHeroClient_PluginTestImpl extends LogHeroClient_Plugin {

    public function setLogTransport(\LogHero\Client\LogTransportInterface $logTranport) {
        $this->logTransport = $logTranport;
    }

    public function getFlushTriggerUrl() {
        return $this->flushEndpoint();
    }

}


class LogHeroClientPluginTest extends \WP_UnitTestCase {
    private $plugin;
    private $apiAccessStub;

    function setUp() {
        parent::setUp();
        update_option('api_key', 'API_KEY');
        $this->apiAccessStub = $this->getMockBuilder(\LogHero\Client\APIAccessInterface::class)->getMock();
        $this->plugin = new LogHeroClient_PluginTestImpl($this->apiAccessStub);
        $logBuffer = new \LogHero\Client\MemLogBuffer(1);
        $this->plugin->setLogTransport(new \LogHero\Client\LogTransport($logBuffer, $this->apiAccessStub));
    }

    function tearDown() {
        remove_action('shutdown', array(LogHeroClient_Plugin::getInstance(), 'sendLogEvent'));
        remove_action('shutdown', array($this->plugin, 'sendLogEvent'));
    }

	function testSubmitLogEvent() {
        $this->setupServerGlobal('/page-url');
        $this->apiAccessStub
            ->expects(static::once())
            ->method('submitLogPackage')
            ->with($this->equalTo($this->buildExpectedPayload([[
                'd113ff3141723d50fec2933977c89ea6',
                'example.org',
                '/page-url',
                'POST',
                301,
                '2018-04-11T06:48:18+00:00',
                2389,
                'f528764d624db129b32c21fbca0cb8d6',
                'Firefox',
                'https://www.loghero.io'
            ]])));
        $this->plugin->submitLogEvent();
	}

	function testSubmitLogEventWithoutPageLoadTimeIfNoRequestTime() {
        $this->setupServerGlobal('/page-url');
        $_SERVER['REQUEST_TIME_FLOAT'] = null;
        $this->apiAccessStub
            ->expects(static::once())
            ->method('submitLogPackage')
            ->with($this->equalTo($this->buildExpectedPayload([[
                'd113ff3141723d50fec2933977c89ea6',
                'example.org',
                '/page-url',
                'POST',
                301,
                '2018-04-11T06:48:20+00:00',
                null,
                'f528764d624db129b32c21fbca0cb8d6',
                'Firefox',
                'https://www.loghero.io'
            ]])));
        $this->plugin->submitLogEvent();
    }

    function testSendLogEventsInBatch() {
        remove_action('shutdown', array($this->plugin, 'submitLogEvent'));
        $logBuffer = new \LogHero\Client\MemLogBuffer(2);
        $this->plugin->setLogTransport(new \LogHero\Client\LogTransport($logBuffer, $this->apiAccessStub));
        $this->apiAccessStub
            ->expects(static::once())
            ->method('submitLogPackage')
            ->with($this->equalTo($this->buildExpectedPayload([
                [
                    'd113ff3141723d50fec2933977c89ea6',
                    'example.org',
                    '/page-url-1',
                    'POST',
                    301,
                    '2018-04-11T06:48:18+00:00',
                    2389,
                    'f528764d624db129b32c21fbca0cb8d6',
                    'Firefox',
                    'https://www.loghero.io'
                ],
                [
                    'd113ff3141723d50fec2933977c89ea6',
                    'example.org',
                    '/page-url-2',
                    'POST',
                    301,
                    '2018-04-11T06:48:18+00:00',
                    2389,
                    'f528764d624db129b32c21fbca0cb8d6',
                    'Firefox',
                    'https://www.loghero.io'
                ]
            ])));
        $this->setupServerGlobal('/page-url-1');
        $this->plugin->submitLogEvent();
        $this->setupServerGlobal('/page-url-2');
        $this->plugin->submitLogEvent();
    }

    function testIgnoreLogEventsSentByPluginItself() {
        $this->setupServerGlobal('/page-url');
        $_SERVER['HTTP_USER_AGENT'] = $this->plugin->clientId;
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $this->plugin->submitLogEvent();
    }

    function testCreateUrlForFlushEndpoint() {
        static::assertEquals('http://example.org/var/www/html/wp-content/plugins/loghero/flush.php', $this->plugin->getFlushTriggerUrl());
    }

    function testAsyncLogTransportNoSendOnSubmit() {
        $this->setupAsyncLogTransport();
        $this->setupServerGlobal('/page-url');
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $this->plugin->submitLogEvent();
    }

    function testAsyncLogTransportSendOnFlush() {
        $this->setupAsyncLogTransport();
        $this->setupServerGlobal('/page-url');
        $this->plugin->submitLogEvent();
        $this->apiAccessStub
            ->expects(static::once())
            ->method('submitLogPackage')
            ->with($this->equalTo($this->buildExpectedPayload([[
                'd113ff3141723d50fec2933977c89ea6',
                'example.org',
                '/page-url',
                'POST',
                301,
                '2018-04-11T06:48:18+00:00',
                2389,
                'f528764d624db129b32c21fbca0cb8d6',
                'Firefox',
                'https://www.loghero.io'
            ]])));
        $this->plugin->flush('API_KEY');
    }

    /**
     * @expectedException LogHero\Wordpress\InvalidTokenException
     * @expectedExceptionMessage Token is invalid
     */
    function testAsyncLogTransportRejectOnFlushIfWrongToken() {
        $this->setupAsyncLogTransport();
        $this->setupServerGlobal('/page-url');
        $this->plugin->submitLogEvent();
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $this->plugin->flush('INVALID_TOKEN');
    }

    private function setupAsyncLogTransport() {
        $logBuffer = new \LogHero\Client\MemLogBuffer(1);
        $logTransport = new \LogHero\Client\AsyncLogTransport(
            $logBuffer,
            $this->apiAccessStub,
            'CLIENT_ID',
            'CLIENT_SECRET',
            '/flush.php'
        );
        $this->plugin->setLogTransport($logTransport);
    }

    private function buildExpectedPayload($rows) {
        return json_encode(array(
            'columns' => [
                'cid',
                'hostname',
                'landingPage',
                'method',
                'statusCode',
                'timestamp',
                'pageLoadTime',
                'ip',
                'ua',
                'referer'
            ],
            'rows' => $rows
        ));
    }

    private function setupServerGlobal($pageUrl) {
        $_SERVER['REQUEST_URI'] = $pageUrl;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_USER_AGENT'] = 'Firefox';
        $_SERVER['REQUEST_TIME_FLOAT'] = 1523429298.4109;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_REFERER'] = 'https://www.loghero.io';
        http_response_code(301);
    }
}
