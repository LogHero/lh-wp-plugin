<?php
namespace LogHero\Wordpress;


require_once __DIR__ . '/mock-microtime.php';


class LogHero_PluginTestImpl extends LogHero_Plugin {

    public function __construct($fileKeyStorage, $logBuffer, $apiAccessStub) {
        parent::__construct();
        $this->logHeroClient = new \LogHero\Wordpress\LogHeroPluginClient(
            '/flush.php',
            $fileKeyStorage,
            $logBuffer,
            $apiAccessStub
        );
    }

    public function onShutdownAction() {
        $this->logHeroClient->submitLogEvent();
    }

    public function onAsyncFlushAction($apiKey) {
        $this->logHeroClient->flush($apiKey);
    }

    public function getFlushTriggerUrl() {
        return $this->flushEndpoint();
    }

}


class LogHeroPluginTest extends \WP_UnitTestCase {
    private $apiKey = 'API_KEY';
    private $apiKeyStorage;
    private $plugin;
    private $apiAccessStub;

    function setUp() {
        parent::setUp();
        $this->apiKeyStorage = new \LogHero\Client\APIKeyMemStorage();
        $this->apiKeyStorage->setKey($this->apiKey);
        $this->apiAccessStub = $this->getMockBuilder(\LogHero\Client\APIAccessInterface::class)->getMock();
        $this->plugin = new LogHero_PluginTestImpl($this->apiKeyStorage, new \LogHero\Client\MemLogBuffer(10), $this->apiAccessStub);
    }

    function tearDown() {
        remove_action('shutdown', array(LogHero_Plugin::getInstance(), 'sendLogEvent'));
        remove_action('shutdown', array($this->plugin, 'sendLogEvent'));
    }

    function testNoSendOnSubmit() {
        $this->setupServerGlobal('/page-url');
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $this->plugin->onShutdownAction();
    }

	function testSubmitLogEventOnFlush() {
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
        $this->plugin->onShutdownAction();
        $this->plugin->onAsyncFlushAction($this->apiKey);
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
        $this->plugin->onShutdownAction();
        $this->plugin->onAsyncFlushAction($this->apiKey);
    }

    function testSendLogEventsInBatch() {
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
        $this->plugin->onShutdownAction();
        $this->setupServerGlobal('/page-url-2');
        $this->plugin->onShutdownAction();
        $this->plugin->onAsyncFlushAction($this->apiKey);
    }

    function testIgnoreLogEventsSentByPluginItself() {
        $this->setupServerGlobal('/page-url');
        $_SERVER['HTTP_USER_AGENT'] = \LogHero\Wordpress\LogHeroSettings::$clientId;
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $this->plugin->onShutdownAction();
        $this->plugin->onAsyncFlushAction($this->apiKey);
    }

    function testCreateUrlForFlushEndpoint() {
        static::assertEquals('http://example.org/var/www/html/wp-content/plugins/loghero/flush.php', $this->plugin->getFlushTriggerUrl());
    }

    /**
     * @expectedException LogHero\Wordpress\InvalidTokenException
     * @expectedExceptionMessage Token is invalid
     */
    function testAsyncLogTransportRejectOnFlushIfWrongToken() {
        $this->setupServerGlobal('/page-url');
        $this->plugin->onShutdownAction();
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $this->plugin->onAsyncFlushAction('INVALID_TOKEN');
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
